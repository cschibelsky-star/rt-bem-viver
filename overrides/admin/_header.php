<?php
require_once __DIR__ . '/../includes/functions.php';
if (file_exists(__DIR__ . '/../includes/security.php')) {
    require_once __DIR__ . '/../includes/security.php';
}
$user = current_user();
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title><?= e($adminTitle ?? 'Admin RT Bem Viver') ?></title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">
<header class="admin-top">
  <div>
    <strong>RT Bem Viver — Admin</strong>
    <small>
      <?= $user ? e($user['name'] ?? $user['username']) : '' ?>
      <?= $user && !is_general_admin() ? ' · Admin da cidade' : ($user ? ' · Admin geral' : '') ?>
    </small>
  </div>

  <?php if ($user): ?>
  <nav>
    <a href="index.php">Painel</a>
    <a href="eventos.php">Eventos</a>
    <a href="cidades.php"><?= is_general_admin() ? 'Cidades' : 'Minha cidade' ?></a>

    <?php if (is_general_admin()): ?>
      <a href="revisao.php">Revisão</a>
      <a href="instagram.php">Instagram</a>
      <a href="usuarios.php">Usuários</a>
    <?php endif; ?>

    <a href="minha_senha.php">Minha senha</a>
    <a href="../index.php" target="_blank" rel="noopener">Site</a>
    <a href="logout.php">Sair</a>
  </nav>
  <?php endif; ?>
</header>

<main class="admin-main">
<?php if ($f = flash()): ?>
  <div class="flash <?= e($f['type']) ?>"><?= e($f['message']) ?></div>
<?php endif; ?>
