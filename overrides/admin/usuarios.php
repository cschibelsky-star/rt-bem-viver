<?php
require_once __DIR__ . '/../includes/auth.php';
require_general_admin();

$adminTitle = 'Usuários — RT Bem Viver';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = $_POST['action'] ?? 'save';
    $username = trim($_POST['username'] ?? '');

    if ($action === 'temporary_password') {
        $user = find_user($username);

        if ($user && ($user['role'] ?? '') === 'city_admin') {
            $temporary = random_temporary_password();
            $user['password_hash'] = password_hash($temporary, PASSWORD_DEFAULT);
            $user['must_change_password'] = true;
            $user['password_reset_at'] = date(DATE_ATOM);

            save_user($user);
            close_recovery_requests($username);

            $_SESSION['temporary_password_once'] = [
                'username' => $username,
                'password' => $temporary
            ];

            flash('Senha temporária criada. Copie-a agora: ela será exibida apenas nesta tela.', 'warning');
        } else {
            flash('A redefinição por este painel é permitida somente para administradores de cidade.', 'error');
        }

        header('Location: usuarios.php');
        exit;
    }

    $role = $_POST['role'] ?? 'city_admin';
    $citySlug = $_POST['city_slug'] ?? '';
    $existing = find_user($username);
    $user = $existing ?: ['username' => $username];

    $user['name'] = trim($_POST['name'] ?? $username);
    $user['email'] = trim($_POST['email'] ?? '');
    $user['role'] = $role;
    $user['city_slug'] = $role === 'city_admin' ? $citySlug : '';
    $user['active'] = !empty($_POST['active']);

    if (!empty($_POST['password'])) {
        if (!password_is_strong((string)$_POST['password'])) {
            flash('A senha deve ter pelo menos 10 caracteres, com maiúscula, minúscula, número e símbolo.', 'error');
            header('Location: usuarios.php');
            exit;
        }

        $user['password_hash'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $user['must_change_password'] = false;
        $user['password_changed_at'] = date(DATE_ATOM);
    }

    save_user($user);

    flash('Usuário salvo.');
    header('Location: usuarios.php');
    exit;
}

$users = read_json('users', []);
$requests = array_values(array_filter(
    recovery_requests(),
    fn($r) => ($r['status'] ?? '') === 'pending'
));

$temporaryOnce = $_SESSION['temporary_password_once'] ?? null;
unset($_SESSION['temporary_password_once']);

require __DIR__ . '/_header.php';
?>

<?php if ($temporaryOnce): ?>
<section class="admin-section" style="border-color:#f59e0b;background:#fffaf0">
  <h2>Senha temporária — copie agora</h2>
  <p><strong>Usuário:</strong> <?= e($temporaryOnce['username']) ?></p>
  <p><strong>Senha temporária:</strong>
    <code style="font-size:18px;user-select:all"><?= e($temporaryOnce['password']) ?></code>
  </p>
  <p class="small">No próximo login, o administrador da cidade será obrigado a criar uma nova senha.</p>
</section>
<?php endif; ?>

<?php if ($requests): ?>
<section class="admin-section">
  <h2>Solicitações de recuperação</h2>
  <p>Pedidos feitos pelos administradores das cidades na tela “Esqueci minha senha”.</p>

  <div class="table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Usuário</th>
          <th>Cidade</th>
          <th>Solicitado</th>
          <th>Ação</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($requests as $r): $c = city_by_slug($r['city_slug'] ?? ''); ?>
        <tr>
          <td>
            <?= e($r['username'] ?? '') ?><br>
            <small><?= e($r['name'] ?? '') ?></small>
          </td>
          <td><?= e($c['name'] ?? '') ?></td>
          <td><?= e($r['created_at'] ?? '') ?></td>
          <td>
            <form method="post">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="action" value="temporary_password">
              <input type="hidden" name="username" value="<?= e($r['username'] ?? '') ?>">
              <button class="btn btn-small" type="submit">Gerar senha temporária</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php endif; ?>

<section class="admin-section two-admin-cols">
  <div>
    <h1>Usuários</h1>
    <p>Um único login identifica automaticamente o administrador geral ou o administrador de cada cidade.</p>

    <div class="table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Usuário</th>
            <th>Perfil</th>
            <th>Cidade</th>
            <th>Status</th>
            <th>Recuperação</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): $c = city_by_slug($u['city_slug'] ?? ''); ?>
          <tr>
            <td>
              <?= e($u['username']) ?><br>
              <small>
                <?= e($u['name'] ?? '') ?>
                <?= !empty($u['email']) ? ' · ' . e($u['email']) : '' ?>
              </small>
            </td>
            <td><?= ($u['role'] ?? '') === 'general_admin' ? 'Admin geral' : 'Admin da cidade' ?></td>
            <td><?= e($c['name'] ?? '') ?></td>
            <td>
              <?= !empty($u['active']) ? 'Ativo' : 'Inativo' ?>
              <?= !empty($u['must_change_password']) ? ' · Troca de senha pendente' : '' ?>
            </td>
            <td>
              <?php if (($u['role'] ?? '') === 'city_admin'): ?>
              <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="temporary_password">
                <input type="hidden" name="username" value="<?= e($u['username']) ?>">
                <button class="btn btn-small btn-light" type="submit">Redefinir senha</button>
              </form>
              <?php else: ?>
                <small>Via código do proprietário</small>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <form method="post" class="form-grid content-card admin-card">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="save">

    <h2>Novo/alterar usuário</h2>

    <label>Nome
      <input name="name">
    </label>

    <label>Usuário
      <input name="username" required>
    </label>

    <label>E-mail de contato
      <input name="email" type="email" placeholder="Opcional">
    </label>

    <label>Senha
      <input name="password" type="password" placeholder="Preencher para criar ou alterar">
    </label>

    <small>Mínimo de 10 caracteres, com maiúscula, minúscula, número e símbolo.</small>

    <label>Perfil
      <select name="role">
        <option value="city_admin">Administrador da cidade</option>
        <option value="general_admin">Administrador geral</option>
      </select>
    </label>

    <label>Cidade vinculada
      <select name="city_slug">
        <option value="">Sem cidade</option>
        <?php foreach (cities(true) as $city): ?>
          <option value="<?= e($city['slug']) ?>"><?= e($city['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label class="check">
      <input type="checkbox" name="active" checked>
      Ativo
    </label>

    <button class="btn" type="submit">Salvar usuário</button>
  </form>
</section>

<?php require __DIR__ . '/_footer.php'; ?>
