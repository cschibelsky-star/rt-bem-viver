<?php
require_once __DIR__ . '/includes/functions.php';
$title = 'RT Bem Viver — Região Turística';
$bodyClass = 'home-exact-layout';
$activeCities = cities(true);
$featuredEvents = array_slice(events(['status' => 'approved', 'upcoming' => true]), 0, 4);
require __DIR__ . '/includes/header.php';
?>
<section class="hero hero-photo">
  <div class="hero-overlay"></div>
  <div class="container hero-grid hero-home-grid">
    <div class="hero-copy">
      <span class="eyebrow light">Região Turística Bem Viver</span>
      <h1>Região Turística<br><span>Bem <em>Viver</em></span></h1>
      <p class="lead">Turismo, cultura e eventos conectando as cidades da Região Turística Bem Viver.</p>
      <div class="hero-actions">
        <a class="btn" href="agenda.php">Ver eventos</a>
        <a class="btn btn-glass" href="#cidades">Conheça as cidades</a>
      </div>
    </div>
    <div class="hero-brandmark" aria-hidden="true">
      <img class="hero-logo-large" src="assets/img/logo-rt-bem-viver.svg" alt="">
    </div>
  </div>
</section>

<section class="section regional-visual-section" aria-labelledby="regional-visual-title">
  <div class="container">
    <div class="regional-visual-heading">
      <span class="eyebrow">Região Turística Bem Viver</span>
      <h2 id="regional-visual-title">Cidades que inspiram</h2>
      <p>Uma região, cinco cidades e experiências que se completam.</p>
    </div>

    <div class="regional-city-panorama">
      <?php foreach ($activeCities as $city):
        $visual = $city['image'] ?? 'assets/img/city-default.svg';
        if (($city['slug'] ?? '') === 'campinas') {
          $visual = 'assets/img/city-campinas-torre.jpg';
        }
      ?>
        <a class="regional-city-panel regional-city-<?= e($city['slug']) ?>"
           href="cidade.php?slug=<?= e($city['slug']) ?>"
           style="--regional-city-img:url('<?= e($visual) ?>')">
          <span class="regional-city-shade"></span>
          <span class="regional-city-name"><?= e($city['name']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="regional-visual-footer">
      <strong>Cidades que se completam para você viver mais.</strong>
      <a class="btn btn-outline-blue" href="#cidades">Explorar as cidades</a>
    </div>
  </div>
</section>

<section class="cities-band" id="cidades">
  <div class="container cities-layout">
    <div class="cities-title">
      <span class="round-icon">⌖</span>
      <h2>Cidades da Região</h2>
      <p>Conheça as cidades que integram a RT Bem Viver.</p>
    </div>
    <div class="city-suspended-row">
      <?php foreach ($activeCities as $city):
        $img = $city['image'] ?? 'assets/img/city-default.svg';
      ?>
        <a class="suspended-city-card city-photo-card" href="cidade.php?slug=<?= e($city['slug']) ?>" style="--city-img:url('<?= e($img) ?>')">
          <span class="city-card-label">
            <strong><?= e($city['name']) ?></strong>
            <small>Conheça a cidade</small>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section agenda-showcase" id="agenda">
  <div class="container agenda-panel">
    <aside class="agenda-intro">
      <span class="calendar-icon">▣</span>
      <h2>Agenda Regional</h2>
      <p>Eventos cadastrados pelas cidades participantes e publicados após revisão.</p>
      <a class="btn" href="agenda.php">Ver agenda completa</a>
    </aside>
    <div class="event-photo-grid">
      <?php if (!$featuredEvents): ?>
        <div class="agenda-empty-state">
          <h3>Agenda em atualização</h3>
          <p>As cidades estão iniciando o cadastramento de suas programações. Os eventos aprovados aparecerão aqui automaticamente.</p>
        </div>
      <?php endif; ?>

      <?php foreach ($featuredEvents as $event):
        $city = city_by_slug($event['city_slug'] ?? '');
        $img = $event['image'] ?? 'assets/img/event-cultura.jpg';
      ?>
        <article class="event-photo-card" style="--event-img:url('<?= e($img) ?>')">
          <div class="event-date-badge">
            <strong><?= e(substr(format_date_br($event['date_start'] ?? ''), 0, 2)) ?></strong>
            <span><?= e(substr(format_date_br($event['date_start'] ?? ''), 3, 2)) ?></span>
          </div>
          <div class="event-photo-content">
            <h3><a href="evento.php?id=<?= e($event['id']) ?>"><?= e($event['title']) ?></a></h3>
            <p><?= e($city['name'] ?? 'Regional') ?></p>
            <span class="pill"><?= e($event['category'] ?? 'Evento') ?></span>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section about-region-section" id="sobre-regiao">
  <div class="container about-region-panel">
    <div>
      <span class="eyebrow">Sobre a Região</span>
      <h2>Uma região conectada por experiências</h2>
      <p>A RT Bem Viver reúne Sumaré, Americana, Campinas, Hortolândia e Santa Bárbara d’Oeste em uma vitrine regional de turismo, cultura e eventos.</p>
      <p>Cada município administra suas próprias informações e agenda, mantendo o conteúdo local atualizado dentro de uma apresentação regional integrada.</p>
    </div>
    <img src="assets/img/logo-rt-bem-viver.svg" alt="RT Bem Viver" class="about-region-logo">
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
