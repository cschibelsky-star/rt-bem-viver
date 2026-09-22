<?php require_once __DIR__ . '/functions.php'; $settings = settings(); ?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? APP_NAME) ?></title>
  <meta name="description" content="Região Turística Bem Viver: cidades, turismo, cultura e agenda regional de eventos.">
  <link rel="stylesheet" href="assets/css/style.css?v=1.10">
  <link rel="stylesheet" href="assets/css/production-fixes.css?v=2">
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<header class="site-header transparent-header">
  <div class="container nav">
    <a class="brand" href="index.php" aria-label="RT Bem Viver">
      <img class="brand-logo" src="assets/img/logo-rt-bem-viver.svg" alt="Logo RT Bem Viver">
      <span><strong>RT Bem Viver</strong><small>Região Turística Bem Viver</small></span>
    </a>
    <nav>
      <a href="index.php">Início</a>
      <a href="index.php#cidades">Cidades</a>
      <a href="agenda.php">Eventos</a>
      <a href="index.php#sobre-regiao">Sobre a Região</a>
      <a href="<?= e($settings['instagram_profile_url']) ?>" target="_blank" rel="noopener">Contato</a>
    </nav>
  </div>
</header>
<main>
