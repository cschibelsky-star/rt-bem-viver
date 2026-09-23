<?php
require_once __DIR__ . '/includes/functions.php';
$title = 'RT Bem Viver — Região Turística';
$bodyClass = 'home-poster-layout';
$activeCities = cities(true);
$featuredEvents = array_slice(events(['status' => 'approved', 'upcoming' => true]), 0, 4);

$heroCityImages = [
  'sumare' => 'assets/img/city-sumare.jpg',
  'americana' => 'assets/img/city-americana.jpg',
  'campinas' => 'assets/img/city-campinas-torre.jpg',
  'hortolandia' => 'assets/img/city-hortolandia.jpg',
  'santa-barbara-doeste' => 'assets/img/city-santa-barbara.jpg',
];

require __DIR__ . '/includes/header.php';
?>

<section class="rt-poster-hero" aria-label="Região Turística Bem Viver">
  <div class="rt-poster-sky">
    <div class="container rt-poster-head">
      <div class="rt-poster-title">
        <span class="rt-poster-kicker">REGIÃO TURÍSTICA</span>
        <h1>Bem Viver</h1>
      </div>

      <div class="rt-poster-story">MAIS<br>QUE DESTINOS,<br>BOAS HISTÓRIAS.</div>
      <div class="rt-poster-script">Aqui<br>a vida<br>inspira.</div>
    </div>

    <div class="container rt-poster-cityline">
      Sumaré <span>•</span> Americana <span>•</span> Campinas <span>•</span> Hortolândia <span>•</span> Santa Bárbara d’Oeste
    </div>
  </div>

  <div class="rt-poster-panels">
    <?php foreach ($activeCities as $city):
      $slug = $city['slug'] ?? '';
      $img = $heroCityImages[$slug] ?? ($city['image'] ?? 'assets/img/city-default.svg');
    ?>
      <a class="rt-poster-city rt-city-<?= e($slug) ?>"
         href="cidade.php?slug=<?= e($slug) ?>"
         style="--rt-city-img:url('<?= e($img) ?>')">
        <span class="rt-poster-city-label"><?= e($city['name']) ?></span>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="rt-poster-bottom">
    <div class="container">
      <div class="rt-poster-tagline">CIDADES QUE SE COMPLETAM<br><strong>PARA VOCÊ VIVER MAIS.</strong></div>
      <div class="rt-poster-icons" aria-label="Experiências da Região">
        <span>♧ <small>NATUREZA</small></span>
        <span>▥ <small>CULTURA</small></span>
        <span>◉ <small>GASTRONOMIA</small></span>
        <span>▣ <small>EXPERIÊNCIAS</small></span>
        <span>♡ <small>PESSOAS</small></span>
      </div>
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
