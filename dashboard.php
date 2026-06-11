<?php
/**
 * SmartWake - Tableau de bord principal
 * Design premium — version SaaS
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$latest    = getLatestMeasure();
$stats     = getTodayStats();
$data24h   = getLast24HoursData();
$data100   = getLast100Measures();

$lux       = $latest ? (int)$latest['light_value'] : 0;
$status    = $latest ? $latest['day_status']        : 'UNKNOWN';
$timestamp = $latest ? $latest['created_at']        : null;
$wake      = getWakeRecommendation($lux, $status);
$level     = getLuxLevel($lux);
$meta      = getLuxLevelMeta($level);
$luxPct    = min(round(($lux / 600) * 100), 100);
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — SmartWake</title>
  <meta name="description" content="Surveillance en temps réel de la luminosité ambiante via capteur Tiva C.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/smartwake/assets/css/style.css">
</head>
<body>
<div class="page-wrapper">

  <!-- ===== NAVBAR ===== -->
  <nav class="navbar" role="navigation" aria-label="Navigation principale">
    <div class="container navbar-inner">
      <a href="/smartwake/dashboard.php" class="navbar-brand" aria-label="SmartWake">
        <span class="brand-icon" aria-hidden="true">🌅</span>
        <span class="brand-text">Smart<span class="brand-accent">Wake</span></span>
      </a>

      <button class="navbar-toggle" id="navbar-toggle"
        aria-controls="navbar-nav" aria-expanded="false" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>

      <ul class="navbar-nav" id="navbar-nav" role="list">
        <li><a href="/smartwake/dashboard.php" class="nav-link active" aria-current="page">Dashboard</a></li>
        <li><a href="/smartwake/history.php" class="nav-link">Historique</a></li>
        <li><a href="/smartwake/api/latest.php" class="nav-link" target="_blank" rel="noopener">API</a></li>
        <li class="nav-separator"></li>
        <li>
          <span class="nav-user">
            <span class="nav-user-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></span>
            <?= e($_SESSION['username']) ?>
          </span>
        </li>
        <li>
          <a href="/smartwake/logout.php?token=<?= urlencode($csrfToken) ?>"
             class="btn btn-ghost btn-sm" aria-label="Se déconnecter">
            Déconnexion
          </a>
        </li>
      </ul>
    </div>
  </nav>

  <!-- ===== HERO HEADER ===== -->
  <section class="dash-hero">
    <div class="container">
      <div class="dash-hero-inner">
        <div class="dash-hero-text">
          <div class="dash-live-badge">
            <span class="live-dot"></span>
            <span>Temps réel — mise à jour toutes les 5s</span>
          </div>
          <h1 class="dash-title">Tableau de bord</h1>
          <p class="dash-subtitle">Capteur de luminosité LDR · Tiva C TM4C123GH6PM · Base de données distante</p>
        </div>
        <div class="dash-hero-lux">
          <div id="lux-circle" class="lux-circle level-<?= strtolower(str_replace('_', '-', $level)) ?>"
               role="img" aria-label="Niveau de luminosité actuel">
            <span class="lux-icon" aria-hidden="true"><?= $meta['icon'] ?></span>
            <span class="lux-val" id="live-lux-hero"><?= e($lux) ?> lux</span>
            <span class="lux-label"><?= e($meta['label']) ?></span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== MAIN CONTENT ===== -->
  <main class="container dash-main" id="main-content">

    <!-- === CARTES MÉTRIQUES === -->
    <div class="metrics-grid">

      <!-- Carte 1 : Luminosité -->
      <article class="card metric-card reveal" aria-label="Luminosité actuelle">
        <div class="card-header">
          <span class="card-icon">💡</span>
          <span class="card-label">Luminosité actuelle</span>
        </div>
        <p id="live-lux" class="metric-value" aria-live="polite"><?= e($lux) ?> <small>lux</small></p>
        <div class="lux-bar-wrap" aria-label="Niveau de luminosité sur 600 lux max">
          <div class="lux-bar" role="progressbar" aria-valuenow="<?= $lux ?>" aria-valuemin="0" aria-valuemax="600">
            <div id="lux-bar-fill" class="lux-bar-fill" style="width:<?= $luxPct ?>%"></div>
          </div>
          <div class="lux-bar-labels">
            <span>0</span><span>600 lux max</span>
          </div>
        </div>
        <div id="live-status" class="metric-badge-wrap" style="margin-top:1rem;">
          <?= statusBadge($status, $lux) ?>
        </div>
        <span id="live-status-text" class="sr-only"><?= e($meta['label']) ?></span>
      </article>

      <!-- Carte 2 : Réveil Intelligent -->
      <article id="wake-card"
               class="card metric-card wake-card <?= $wake['optimal'] ? 'optimal' : 'not-optimal' ?> action-<?= e($wake['action'] ?? 'sleep') ?> reveal"
               role="region" aria-label="Recommandation de réveil">
        <div class="card-header">
          <span class="card-icon">⏰</span>
          <span class="card-label">Réveil Intelligent</span>
        </div>
        <div class="wake-body">
          <span id="wake-icon" class="wake-icon" aria-hidden="true">
            <?= [
              'sleep'         => '😴',
              'simulate_dawn' => '🌙',
              'soft_alarm'    => '🌅',
              'main_alarm'    => '🔔',
              'day_mode'      => '☀️',
              'alert'         => '⚠️',
            ][$wake['action'] ?? 'sleep'] ?? '😴' ?>
          </span>
          <h2 id="wake-message" class="wake-title"><?= e($wake['message']) ?></h2>
          <p id="wake-detail" class="wake-detail"><?= e($wake['detail']) ?></p>
        </div>
        <div class="wake-footer">
          <?php if ($wake['optimal']): ?>
            <span class="badge badge-success">✅ Conditions optimales</span>
          <?php else: ?>
            <span class="badge badge-night">💤 Pas encore</span>
          <?php endif; ?>
        </div>
      </article>

      <!-- Carte 3 : Dernière mesure -->
      <article class="card metric-card reveal" aria-label="Informations capteur">
        <div class="card-header">
          <span class="card-icon">📡</span>
          <span class="card-label">Dernière mesure</span>
        </div>
        <p id="live-timestamp" class="metric-timestamp" aria-live="polite">
          <?= $timestamp ? e(formatDate($timestamp)) : 'En attente...' ?>
        </p>
        <div class="sensor-info">
          <div class="sensor-info-row">
            <span class="sensor-info-label">Capteur</span>
            <span class="sensor-info-val">LDR / Photorésistance</span>
          </div>
          <div class="sensor-info-row">
            <span class="sensor-info-label">Port</span>
            <span class="sensor-info-val">COM7 · 9600 baud</span>
          </div>
          <div class="sensor-info-row">
            <span class="sensor-info-label">Carte</span>
            <span class="sensor-info-val">Tiva C TM4C123GH6PM</span>
          </div>
          <div class="sensor-info-row">
            <span class="sensor-info-label">Base de données</span>
            <span class="sensor-info-val">MySQL distant</span>
          </div>
        </div>
      </article>

    </div><!-- /metrics-grid -->

    <!-- === STATISTIQUES DU JOUR === -->
    <div class="section-label reveal">
      <span>📊</span> Statistiques du jour
    </div>

    <div class="stats-band reveal">
      <div class="stat-pill">
        <span class="stat-pill-val"><?= e($stats['min_lux'] ?? 0) ?></span>
        <span class="stat-pill-lbl">Min lux</span>
      </div>
      <div class="stat-pill-divider"></div>
      <div class="stat-pill">
        <span class="stat-pill-val"><?= e($stats['max_lux'] ?? 0) ?></span>
        <span class="stat-pill-lbl">Max lux</span>
      </div>
      <div class="stat-pill-divider"></div>
      <div class="stat-pill">
        <span class="stat-pill-val"><?= e($stats['avg_lux'] ?? 0) ?></span>
        <span class="stat-pill-lbl">Moyenne</span>
      </div>
      <div class="stat-pill-divider"></div>
      <div class="stat-pill">
        <span class="stat-pill-val"><?= e($stats['total_measures'] ?? 0) ?></span>
        <span class="stat-pill-lbl">Mesures</span>
      </div>
    </div>

    <!-- === GRAPHIQUES === -->
    <div class="section-label reveal">
      <span>📈</span> Évolution de la luminosité
    </div>

    <div class="chart-grid reveal" role="region" aria-label="Graphiques">
      <div class="card chart-card">
        <p class="card-label">Dernières 24 heures</p>
        <div class="chart-container">
          <canvas id="chart-24h" role="img" aria-label="Luminosité — 24h"></canvas>
        </div>
      </div>
      <div class="card chart-card">
        <p class="card-label">100 dernières mesures</p>
        <div class="chart-container">
          <canvas id="chart-100" role="img" aria-label="100 dernières mesures"></canvas>
        </div>
      </div>
    </div>

    <!-- Lien historique -->
    <div class="section-cta reveal">
      <a href="/smartwake/history.php" class="btn btn-outline">
        📋 Voir l'historique complet
      </a>
    </div>

  </main>

  <!-- ===== FOOTER ===== -->
  <footer class="site-footer" role="contentinfo">
    <div class="container footer-inner">
      <span class="footer-brand">🌅 SmartWake</span>
      <span class="footer-sep">·</span>
      <span>Projet ISEP <?= date('Y') ?></span>
      <span class="footer-sep">·</span>
      <a href="/smartwake/api/latest.php" target="_blank" rel="noopener">API JSON</a>
    </div>
  </footer>

</div><!-- /page-wrapper -->

<script>
  const CHART_24H_DATA = <?= json_encode($data24h,  JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  const CHART_100_DATA = <?= json_encode($data100, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"
        integrity="sha256-oVuCdcZBQCLlBt4H8D0lUV5J+LbGGJPULXgKpnXoUHU="
        crossorigin="anonymous"></script>
<script src="/smartwake/assets/js/app.js" defer></script>
</body>
</html>
