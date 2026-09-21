<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $user = find_user($username);

    if (!$user || empty($user['active'])) {
        flash('Se o usuário informado estiver ativo, a solicitação será processada.', 'success');
        header('Location: recuperar_senha.php');
        exit;
    }

    if (($user['role'] ?? '') === 'general_admin') {
        $_SESSION['recovery_admin_username'] = $user['username'];
        header('Location: recuperar_admin.php');
        exit;
    }

    create_recovery_request($user);
    flash('Solicitação registrada. O administrador geral poderá liberar uma senha temporária para este acesso.', 'success');
    header('Location: recuperar_senha.php');
    exit;
}

$adminTitle = 'Recuperar senha — RT Bem Viver';
require __DIR__ . '/_header.php';
?>
<div class="login-card">
  <h1>Recuperar senha</h1>
  <p>Informe seu usuário administrativo. Administradores de cidade terão a solicitação encaminhada ao administrador geral.</p>

  <form method="post" class="form-grid">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <label>Usuário
      <input name="username" required autocomplete="username">
    </label>
    <button class="btn" type="submit">Continuar</button>
    <a class="btn btn-light" href="login.php">Voltar ao login</a>
  </form>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
