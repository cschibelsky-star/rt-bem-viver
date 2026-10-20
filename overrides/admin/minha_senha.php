<?php
require_once __DIR__ . '/../includes/auth.php';

$user = current_user();
$tab = $_GET['tab'] ?? '';

if ($tab === 'atrativos') {
    $allAttractions = read_json('attractions', []);
    if (!is_array($allAttractions)) $allAttractions = [];

    $allowedCity = is_general_admin() ? '' : (string)($user['city_slug'] ?? '');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();

        $action = $_POST['action'] ?? 'save_attraction';
        $id = trim((string)($_POST['id'] ?? ''));

        if ($action === 'delete_attraction') {
            $before = count($allAttractions);
            $allAttractions = array_values(array_filter($allAttractions, function ($item) use ($id, $allowedCity) {
                if (($item['id'] ?? '') !== $id) return true;
                if ($allowedCity !== '' && ($item['city_slug'] ?? '') !== $allowedCity) return true;
                return false;
            }));

            if (count($allAttractions) < $before) {
                write_json('attractions', $allAttractions);
                flash('Atrativo excluído.', 'success');
            } else {
                flash('Atrativo não encontrado ou sem permissão para excluir.', 'error');
            }

            header('Location: minha_senha.php?tab=atrativos');
            exit;
        }

        $citySlug = is_general_admin()
            ? trim((string)($_POST['city_slug'] ?? ''))
            : $allowedCity;

        $city = city_by_slug($citySlug);
        if (!$city) {
            flash('Selecione uma cidade válida.', 'error');
            header('Location: minha_senha.php?tab=atrativos');
            exit;
        }

        $existingIndex = null;
        $existing = null;
        if ($id !== '') {
            foreach ($allAttractions as $i => $item) {
                if (($item['id'] ?? '') === $id) {
                    $existingIndex = $i;
                    $existing = $item;
                    break;
                }
            }

            if ($existing && $allowedCity !== '' && ($existing['city_slug'] ?? '') !== $allowedCity) {
                flash('Você só pode alterar atrativos da sua cidade.', 'error');
                header('Location: minha_senha.php?tab=atrativos');
                exit;
            }
        }

        $image = $existing['image'] ?? '';
        $uploadError = (int)($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadError !== UPLOAD_ERR_OK && $uploadError !== UPLOAD_ERR_NO_FILE) {
            flash(in_array($uploadError, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
                ? 'A imagem excede o limite de envio do servidor. Envie um arquivo menor.'
                : 'O envio da imagem falhou. A imagem atual foi mantida; tente novamente.', 'error');
            header('Location: minha_senha.php?tab=atrativos');
            exit;
        }
        if (!empty($_FILES['image']['name']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $tmp = $_FILES['image']['tmp_name'];
            $size = (int)($_FILES['image']['size'] ?? 0);
            $info = @getimagesize($tmp);

            $mime = $info['mime'] ?? '';
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

            if (!$info || !in_array($mime, $allowedMimes, true)) {
                flash('Envie uma imagem JPG, PNG ou WEBP válida.', 'error');
                header('Location: minha_senha.php?tab=atrativos');
                exit;
            }

            if ($size > 5 * 1024 * 1024) {
                flash('A imagem deve ter no máximo 5 MB antes da otimização.', 'error');
                header('Location: minha_senha.php?tab=atrativos');
                exit;
            }

            $uploadDir = __DIR__ . '/../uploads/attractions';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

            // A GD pode estar indisponível ou compilada sem suporte a WebP.
            // Sem esse controle, o upload provoca erro fatal no administrador.
            if (!function_exists('imagecreatetruecolor')
                || !function_exists('imagecopyresampled')
                || !function_exists('imagepng')) {
                flash('O servidor não está preparado para processar imagens. Tente novamente após a atualização.', 'error');
                header('Location: minha_senha.php?tab=atrativos');
                exit;
            }

            $decoder = match ($mime) {
                'image/jpeg' => 'imagecreatefromjpeg',
                'image/png' => 'imagecreatefrompng',
                'image/webp' => 'imagecreatefromwebp',
                default => null,
            };
            if ($decoder === null || !function_exists($decoder)) {
                flash('O servidor ainda não suporta este formato de imagem.', 'error');
                header('Location: minha_senha.php?tab=atrativos');
                exit;
            }

            $source = @$decoder($tmp);
            if (!$source) {
                flash('Não foi possível processar a imagem enviada.', 'error');
                header('Location: minha_senha.php?tab=atrativos');
                exit;
            }

            $srcW = imagesx($source);
            $srcH = imagesy($source);
            $maxW = 1200;
            $maxH = 800;
            $scale = min(1, $maxW / max(1, $srcW), $maxH / max(1, $srcH));
            $dstW = max(1, (int)round($srcW * $scale));
            $dstH = max(1, (int)round($srcH * $scale));

            $target = imagecreatetruecolor($dstW, $dstH);
            if (!$target) {
                imagedestroy($source);
                flash('Não foi possível preparar a imagem para otimização.', 'error');
                header('Location: minha_senha.php?tab=atrativos');
                exit;
            }
            imagealphablending($target, false);
            imagesavealpha($target, true);
            $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
            imagefilledrectangle($target, 0, 0, $dstW, $dstH, $transparent);
            imagecopyresampled($target, $source, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);

            $webpSupported = function_exists('imagewebp')
                && function_exists('gd_info')
                && (gd_info()['WebP Support'] ?? false);
            $extension = $webpSupported ? 'webp' : 'png';
            $filename = $citySlug . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
            $outputPath = $uploadDir . '/' . $filename;
            $saved = $webpSupported
                ? @imagewebp($target, $outputPath, 78)
                : @imagepng($target, $outputPath, 7);

            // O encoder pode falhar mesmo quando a função existe.
            // Use PNG antes de desistir, sem substituir a imagem anterior.
            clearstatcache(true, $outputPath);
            if ($webpSupported && (!$saved || !is_file($outputPath) || filesize($outputPath) === 0)) {
                @unlink($outputPath);
                $filename = pathinfo($filename, PATHINFO_FILENAME) . '.png';
                $outputPath = $uploadDir . '/' . $filename;
                $saved = @imagepng($target, $outputPath, 7);
            }

            imagedestroy($target);
            imagedestroy($source);
            clearstatcache(true, $outputPath);

            if (!$saved || !is_file($outputPath) || filesize($outputPath) === 0) {
                @unlink($outputPath);
                flash('Não foi possível salvar a imagem otimizada.', 'error');
                header('Location: minha_senha.php?tab=atrativos');
                exit;
            }

            $image = 'uploads/attractions/' . $filename;
        }

        if ($image === '') {
            flash('Envie uma foto principal do atrativo.', 'error');
            header('Location: minha_senha.php?tab=atrativos');
            exit;
        }

        $record = [
            'id' => $id !== '' ? $id : bin2hex(random_bytes(8)),
            'city_slug' => $citySlug,
            'name' => trim((string)($_POST['name'] ?? '')),
            'category' => trim((string)($_POST['category'] ?? '')),
            'description' => trim((string)($_POST['description'] ?? '')),
            'address' => trim((string)($_POST['address'] ?? '')),
            'hours' => trim((string)($_POST['hours'] ?? '')),
            'accessibility' => trim((string)($_POST['accessibility'] ?? '')),
            'more_info_url' => trim((string)($_POST['more_info_url'] ?? '')),
            'image' => $image,
            'status' => !empty($_POST['published']) ? 'published' : 'draft',
            'updated_at' => date(DATE_ATOM),
        ];

        if ($record['name'] === '' || $record['description'] === '') {
            flash('Informe o nome e a descrição curta do atrativo.', 'error');
            header('Location: minha_senha.php?tab=atrativos');
            exit;
        }

        if (mb_strlen($record['description']) > 360) {
            flash('A descrição deve ter no máximo 360 caracteres.', 'error');
            header('Location: minha_senha.php?tab=atrativos');
            exit;
        }

        if ($existingIndex !== null) {
            $record['created_at'] = $existing['created_at'] ?? date(DATE_ATOM);
            $allAttractions[$existingIndex] = $record;
        } else {
            $record['created_at'] = date(DATE_ATOM);
            $allAttractions[] = $record;
        }

        write_json('attractions', array_values($allAttractions));
        flash('Atrativo salvo com sucesso.', 'success');
        header('Location: minha_senha.php?tab=atrativos');
        exit;
    }

    $editId = trim((string)($_GET['edit'] ?? ''));
    $editing = null;
    if ($editId !== '') {
        foreach ($allAttractions as $item) {
            if (($item['id'] ?? '') === $editId && ($allowedCity === '' || ($item['city_slug'] ?? '') === $allowedCity)) {
                $editing = $item;
                break;
            }
        }
    }

    $visibleAttractions = array_values(array_filter($allAttractions, fn($item) =>
        $allowedCity === '' || ($item['city_slug'] ?? '') === $allowedCity
    ));

    usort($visibleAttractions, fn($a, $b) => strcmp(
        ($b['updated_at'] ?? ''),
        ($a['updated_at'] ?? '')
    ));

    $adminTitle = 'Atrativos turísticos — RT Bem Viver';
    require __DIR__ . '/_header.php';
    ?>
    <style>
      .attractions-admin-grid{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(320px,.8fr);gap:28px;align-items:start}
      .attraction-admin-list{display:grid;gap:14px}
      .attraction-admin-card{display:grid;grid-template-columns:150px 1fr;gap:16px;align-items:center;padding:14px;border:1px solid #dbe5ee;border-radius:18px;background:#fff}
      .attraction-admin-card img{width:150px;height:100px;object-fit:cover;border-radius:12px;background:#eef4f8}
      .attraction-admin-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
      .attraction-admin-actions form{margin:0}
      .attraction-form-card textarea{min-height:150px;resize:vertical}
      .attraction-status{display:inline-flex;padding:4px 9px;border-radius:999px;background:#eef4f8;font-size:.8rem;font-weight:700}
      @media(max-width:900px){.attractions-admin-grid{grid-template-columns:1fr}.attraction-admin-card{grid-template-columns:100px 1fr}.attraction-admin-card img{width:100px;height:84px}}
    </style>

    <section class="admin-section">
      <h1>Atrativos turísticos</h1>
      <p>Cadastre uma única foto leve e uma descrição curta. O objetivo é despertar interesse e direcionar o visitante para fontes oficiais com mais informações.</p>

      <div class="attractions-admin-grid">
        <div>
          <h2><?= is_general_admin() ? 'Atrativos cadastrados' : 'Atrativos da minha cidade' ?></h2>

          <div class="attraction-admin-list">
            <?php if (!$visibleAttractions): ?>
              <div class="content-card"><p>Nenhum atrativo cadastrado ainda.</p></div>
            <?php endif; ?>

            <?php foreach ($visibleAttractions as $item): $c = city_by_slug($item['city_slug'] ?? ''); ?>
              <article class="attraction-admin-card">
                <img src="../<?= e($item['image'] ?? '') ?>" alt="<?= e($item['name'] ?? '') ?>">
                <div>
                  <strong><?= e($item['name'] ?? '') ?></strong>
                  <div><small><?= e($c['name'] ?? '') ?> · <?= e($item['category'] ?? 'Atrativo') ?></small></div>
                  <span class="attraction-status"><?= ($item['status'] ?? '') === 'published' ? 'Publicado' : 'Rascunho' ?></span>

                  <div class="attraction-admin-actions">
                    <a class="btn btn-small btn-light" href="minha_senha.php?tab=atrativos&edit=<?= e($item['id'] ?? '') ?>">Editar</a>
                    <form method="post" onsubmit="return confirm('Excluir este atrativo?');">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="delete_attraction">
                      <input type="hidden" name="id" value="<?= e($item['id'] ?? '') ?>">
                      <button class="btn btn-small" type="submit">Excluir</button>
                    </form>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </div>

        <form method="post" enctype="multipart/form-data" class="form-grid content-card attraction-form-card">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="save_attraction">
          <input type="hidden" name="id" value="<?= e($editing['id'] ?? '') ?>">

          <h2><?= $editing ? 'Editar atrativo' : 'Novo atrativo' ?></h2>

          <?php if (is_general_admin()): ?>
            <label>Cidade
              <select name="city_slug" required>
                <option value="">Selecione</option>
                <?php foreach (cities(true) as $city): ?>
                  <option value="<?= e($city['slug']) ?>" <?= ($editing['city_slug'] ?? '') === $city['slug'] ? 'selected' : '' ?>><?= e($city['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
          <?php else: $myCity = city_by_slug($allowedCity); ?>
            <label>Cidade
              <input value="<?= e($myCity['name'] ?? $allowedCity) ?>" disabled>
            </label>
          <?php endif; ?>

          <label>Nome do atrativo
            <input name="name" required value="<?= e($editing['name'] ?? '') ?>">
          </label>

          <label>Categoria
            <select name="category">
              <?php foreach (['Natureza','Cultura e História','Gastronomia','Lazer','Religioso','Arquitetura','Outro'] as $cat): ?>
                <option value="<?= e($cat) ?>" <?= ($editing['category'] ?? '') === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
              <?php endforeach; ?>
            </select>
          </label>

          <label>Descrição curta
            <textarea name="description" required maxlength="360" placeholder="Resuma o atrativo em poucas linhas: o que é e por que vale a visita."><?= e($editing['description'] ?? '') ?></textarea>
            <small>Máximo de 360 caracteres.</small>
          </label>

          <label>Endereço / localização
            <input name="address" value="<?= e($editing['address'] ?? '') ?>">
          </label>

          <label>Horário de funcionamento
            <input name="hours" value="<?= e($editing['hours'] ?? '') ?>">
          </label>

          <label>Acessibilidade
            <input name="accessibility" value="<?= e($editing['accessibility'] ?? '') ?>" placeholder="Ex.: acesso para cadeirantes">
          </label>

          <label>Link para mais informações
            <input name="more_info_url" type="url" value="<?= e($editing['more_info_url'] ?? $editing['map_url'] ?? '') ?>" placeholder="Site oficial, Instagram, Prefeitura ou Google Maps">
          </label>

          <label>Foto principal <?= $editing ? '(deixe vazio para manter a atual)' : '' ?>
            <input name="image" type="file" accept="image/jpeg,image/png,image/webp" <?= $editing ? '' : 'required' ?>>
            <small>Apenas 1 foto. O sistema reduz automaticamente para até 1200 × 800 px e converte para WebP.</small>
          </label>

          <label class="check">
            <input type="checkbox" name="published" <?= ($editing['status'] ?? 'published') === 'published' ? 'checked' : '' ?>>
            Publicar no site
          </label>

          <button class="btn" type="submit">Salvar atrativo</button>
          <?php if ($editing): ?><a class="btn btn-light" href="minha_senha.php?tab=atrativos">Cancelar edição</a><?php endif; ?>
        </form>
      </div>
    </section>
    <?php
    require __DIR__ . '/_footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $new = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if ($new !== $confirm) {
        flash('As senhas não coincidem.', 'error');
    } elseif (!password_is_strong($new)) {
        flash('Use pelo menos 10 caracteres, com maiúscula, minúscula, número e símbolo.', 'error');
    } else {
        $stored = find_user((string)$user['username']);

        if (!$stored) {
            flash('Usuário não encontrado.', 'error');
        } else {
            $stored['password_hash'] = password_hash($new, PASSWORD_DEFAULT);
            $stored['must_change_password'] = false;
            $stored['password_changed_at'] = date(DATE_ATOM);

            save_user($stored);

            unset($stored['password_hash']);
            $_SESSION['user'] = $stored;

            flash('Sua senha foi alterada com sucesso.', 'success');
            header('Location: index.php');
            exit;
        }
    }
}

$adminTitle = 'Minha senha — RT Bem Viver';
require __DIR__ . '/_header.php';
?>
<section class="admin-section narrow">
  <h1><?= !empty($_GET['obrigatorio']) ? 'Defina uma nova senha' : 'Alterar minha senha' ?></h1>
  <p><?= !empty($_GET['obrigatorio'])
      ? 'Por segurança, a senha temporária precisa ser substituída antes de continuar.'
      : 'Atualize a senha do seu acesso administrativo.' ?></p>

  <form method="post" class="form-grid">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

    <label>Nova senha
      <input type="password" name="new_password" required autocomplete="new-password">
    </label>

    <label>Confirmar nova senha
      <input type="password" name="confirm_password" required autocomplete="new-password">
    </label>

    <small>Mínimo de 10 caracteres, com maiúscula, minúscula, número e símbolo.</small>

    <button class="btn" type="submit">Salvar nova senha</button>
  </form>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
