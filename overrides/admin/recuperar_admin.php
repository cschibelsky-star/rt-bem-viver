<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

$username = (string)($_SESSION['recovery_admin_username'] ?? '');
$user = $username ? find_user($username) : null;

if (!$user || ($user['role'] ?? '') !== 'general_admin') {
    header('Location: recuperar_senha.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $code = trim($_POST['recovery_code'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    $expected = (string)getenv('ADMIN_RECOVERY_CODE');

    if ($expected === '' || !hash_equals($expected, $code)) {
        flash('Código de recuperação inválido.', 'error');
    } elseif ($new !== $confirm) {
        flash('As senhas não coincidem.', 'error');
    } elseif (!password_is_strong($new)) {
        flash('Use pelo menos 10 caracteres, com maiúscula, minúscula, número e símbolo.', 'error');
    } else {
        $user['password_hash'] = password_hash($new, PASSWORD_DEFAULT);
        $user['must_change_password'] = false;
        $user['password_changed_at'] = date(DATE_ATOM);

        save_user($user);
        unset($_SESSION['recovery_admin_username']);

        flash('Senha do administrador geral redefinida com sucesso. Faça o login.', 'success');
        header('Location: login.php');
        exit;
    }
}

$adminTitle = 'Recuperação do administrador geral';
require __DIR__ . '/_header.php';
?>
<div class="login-card">
  <h1>Administrador geral</h1>
  <p>Use o código de recuperação do proprietário, armazenado fora do site, para definir uma nova senha.</p>

  <form method="post" class="form-grid">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

    <label>Código de recuperação
      <input type="password" name="recovery_code" required autocomplete="one-time-code">
    </label>

    <label>Nova senha
      <input type="password" name="new_password" required autocomplete="new-password">
    </label>

    <label>Confirmar nova senha
      <input type="password" name="confirm_password" required autocomplete="new-password">
    </label>

    <small>Mínimo de 10 caracteres, com maiúscula, minúscula, número e símbolo.</small>

    <button class="btn" type="submit">Redefinir senha</button>
    <a class="btn btn-light" href="login.php">Cancelar</a>
  </form>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
