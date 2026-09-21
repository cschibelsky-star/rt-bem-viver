<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $user = find_user($username);

    if ($user && !empty($user['active']) && password_verify($password, $user['password_hash'] ?? '')) {
        session_regenerate_id(true);
        unset($user['password_hash']);
        $_SESSION['user'] = $user;
        header('Location: index.php');
        exit;
    }

    flash('Usuário ou senha inválidos.', 'error');
}

$adminTitle = 'Login — RT Bem Viver';
require __DIR__ . '/_header.php';
?>
<div class="login-card">
  <h1>Acesso Administrativo</h1>
  <p>O mesmo acesso atende a Administração Geral e os administradores das cidades. O sistema libera apenas as permissões do seu perfil.</p>

  <form method="post" class="form-grid">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <label>Usuário
      <input name="username" required autocomplete="username">
    </label>
    <label>Senha
      <input type="password" name="password" required autocomplete="current-password">
    </label>
    <button class="btn" type="submit">Entrar</button>
  </form>

  <p class="small" style="margin-top:18px">
    <a href="recuperar_senha.php"><strong>Esqueci minha senha</strong></a>
  </p>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
