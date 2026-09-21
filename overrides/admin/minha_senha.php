<?php
require_once __DIR__ . '/../includes/auth.php';

$user = current_user();

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
