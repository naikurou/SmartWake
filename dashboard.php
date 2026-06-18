<?php
/**
 * SmartWake - Tableau de bord principal
 * Design premium — version SaaS
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$latest    = getLatestMeasure();

// Si le capteur n'a rien envoyé depuis plus de 15s, on considère qu'il est déconnecté
if ($latest && isset($latest['seconds_ago']) && $latest['seconds_ago'] > 15) {
    $latest = null;
}

$stats     = getTodayStats();
$data24h   = getLast24HoursData();
$data100   = getLast100Measures();

$lux       = $latest ? (int)$latest['light_value'] : 0;
$status    = $latest ? $latest['day_status']        : 'UNKNOWN';
$timestamp = $latest ? $latest['created_at']        : null;
$wake      = $latest ? getWakeRecommendation($lux, $status) : ['optimal' => false, 'action' => 'sleep', 'message' => 'Matériel non détecté', 'detail' => 'En attente de connexion du capteur Tiva C...'];
$level     = $latest ? getLuxLevel($lux) : 'NIGHT_FULL';
$meta      = $latest ? getLuxLevelMeta($level) : ['label' => 'Hors ligne', 'icon' => '🔌', 'css' => 'level-night-full', 'range' => ''];
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
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
  <script>
    function applyTheme(theme) {
      document.documentElement.setAttribute('data-theme', theme);
      localStorage.setItem('smartwake-theme', theme);
      document.querySelectorAll('.theme-toggle').forEach(btn => {
        btn.textContent = theme === 'light' ? '🌙' : '☀️';
      });
    }
    function toggleTheme() {
      const current = document.documentElement.getAttribute('data-theme') || 'dark';
      applyTheme(current === 'light' ? 'dark' : 'light');
    }
    const savedTheme = localStorage.getItem('smartwake-theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);
    window.addEventListener('DOMContentLoaded', () => applyTheme(savedTheme));
  </script>
</head>
<body>
<div class="page-wrapper">

  <!-- ===== NAVBAR ===== -->
  <nav class="navbar" role="navigation" aria-label="Navigation principale">
    <div class="container navbar-inner">
      <a href="<?= BASE_URL ?>dashboard.php" class="navbar-brand" aria-label="SmartWake">
        <span class="brand-icon" aria-hidden="true">🌅</span>
        <span class="brand-text">Smart<span class="brand-accent">Wake</span></span>
      </a>

      <button class="navbar-toggle" id="navbar-toggle"
        aria-controls="navbar-nav" aria-expanded="false" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>

      <ul class="navbar-nav" id="navbar-nav" role="list">
        <li><a href="<?= BASE_URL ?>dashboard.php" class="nav-link active" aria-current="page">Dashboard</a></li>
        <li><a href="<?= BASE_URL ?>history.php" class="nav-link">Historique</a></li>
        <li class="nav-separator"></li>
        <li>
          <span class="nav-user">
            <span class="nav-user-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></span>
            <?= e($_SESSION['username']) ?>
          </span>
        </li>
        <li>
          <button class="nav-link btn-settings" aria-label="Paramètres d'alarme" title="Paramètres d'alarme" onclick="openSettingsModal()">⚙️ Paramètres</button>
        </li>
        <li>
          <button class="theme-toggle" aria-label="Passer en mode clair" title="Changer le thème" onclick="toggleTheme()">☀️</button>
        </li>
        <li>
          <a href="<?= BASE_URL ?>logout.php?token=<?= urlencode($csrfToken) ?>"
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
          <p class="dash-subtitle">Capteur LDR · Microcontrôleur TIVA EK123GXL · Code sur Energia & VSCode</p>
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
          <h2 id="wake-message" class="wake-title"><?= e($wake['message'] ?? '') ?></h2>
          <p id="wake-detail" class="wake-detail"><?= e($wake['detail'] ?? '') ?></p>
        </div>
        <div class="wake-footer">
          <?php if (!$latest): ?>
            <span class="badge badge-night">Veuillez brancher le capteur</span>
          <?php elseif ($wake['optimal']): ?>
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
            <span class="sensor-info-val">TIVA EK123GXL</span>
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
      <a href="<?= BASE_URL ?>history.php" class="btn btn-outline">
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
    </div>
  </footer>

</div><!-- /page-wrapper -->

<!-- ===== SETTINGS MODAL ===== -->
<div id="settings-modal" class="modal-overlay" style="display: none;">
  <div class="modal-content card">
    <div class="modal-header">
      <h2>⚙️ Paramètres d'Alarme</h2>
      <button class="modal-close" onclick="closeSettingsModal()">✖</button>
    </div>
    <div class="modal-body">
      <form id="settings-form">
        <div class="form-group">
          <label class="checkbox-label">
            <input type="checkbox" id="alarm-active" checked>
            Activer l'alarme intelligente
          </label>
        </div>
        <div class="form-group">
          <label for="night-lux">Seuil Nuit (Lux) - Déclenchement si intrusion lumineuse</label>
          <input type="number" id="night-lux" class="form-control" value="50" min="0">
        </div>
        <div class="form-group">
          <label for="day-lux">Seuil Jour (Lux) - Déclenchement réveil normal</label>
          <input type="number" id="day-lux" class="form-control" value="500" min="0">
        </div>
        <div class="form-actions" style="margin-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
          <button type="button" class="btn btn-primary" onclick="saveSettings()">💾 Sauvegarder</button>
          <span style="color:var(--text-muted); margin: 0 0.5rem;">|</span>
          <button type="button" class="btn btn-outline" style="color:var(--text-main); border-color:var(--border-light);" onclick="testBuzzer()">🚨 Tester le Buzzer</button>
        </div>
        <p id="settings-msg" style="margin-top:0.5rem; font-size:0.9rem;"></p>
      </form>
    </div>
  </div>
</div>

<style>
/* CSS très basique pour la modale, s'intégrant au thème existant */
.modal-overlay {
  position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
  background: rgba(0,0,0,0.5); z-index: 1000;
  display: flex; align-items: center; justify-content: center;
  backdrop-filter: blur(4px);
}
.modal-content {
  background: var(--bg-card); width: 90%; max-width: 450px;
  border-radius: var(--radius-lg); padding: 1.5rem;
}
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.modal-header h2 { margin: 0; font-size: 1.25rem; }
.modal-close { background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--text-muted); }
.modal-close:hover { color: var(--text-main); }
.checkbox-label { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; }
.btn-settings { background: transparent; border: none; cursor: pointer; }
</style>

<script>
function openSettingsModal() {
  document.getElementById('settings-modal').style.display = 'flex';
  // Charger les paramètres depuis l'API
  fetch('<?= BASE_URL ?>api/get_settings.php')
    .then(r => r.json())
    .then(data => {
      if(data.success) {
        document.getElementById('alarm-active').checked = data.is_active;
        document.getElementById('night-lux').value = data.night_lux;
        document.getElementById('day-lux').value = data.day_lux;
      }
    });
}
function closeSettingsModal() {
  document.getElementById('settings-modal').style.display = 'none';
  document.getElementById('settings-msg').textContent = '';
}
function saveSettings() {
  const btn = document.querySelector('#settings-form .btn-primary');
  btn.textContent = 'Sauvegarde...';
  
  const payload = {
    is_active: document.getElementById('alarm-active').checked ? 1 : 0,
    night_lux: document.getElementById('night-lux').value,
    day_lux: document.getElementById('day-lux').value
  };

  fetch('<?= BASE_URL ?>api/save_settings.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(r => r.json())
  .then(data => {
    btn.textContent = '💾 Sauvegarder';
    const msg = document.getElementById('settings-msg');
    msg.textContent = data.success ? '✅ Paramètres sauvegardés !' : '❌ Erreur de sauvegarde';
    msg.style.color = data.success ? 'green' : 'red';
    if(data.success) setTimeout(closeSettingsModal, 1500);
  });
}
function testBuzzer() {
  const btn = document.querySelectorAll('#settings-form .btn-outline')[0];
  const oldText = btn.textContent;
  btn.textContent = 'Test en cours...';
  
  fetch('<?= BASE_URL ?>api/test_buzzer.php', { method: 'POST' })
  .then(r => r.json())
  .then(data => {
    btn.textContent = data.success ? '✅ Test OK !' : '❌ Erreur';
    setTimeout(() => { btn.textContent = oldText; }, 2000);
  });
}
</script>

<script>
  const CHART_24H_DATA = <?= json_encode($data24h,  JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  const CHART_100_DATA = <?= json_encode($data100, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"
        integrity="sha256-oVuCdcZBQCLlBt4H8D0lUV5J+LbGGJPULXgKpnXoUHU="
        crossorigin="anonymous"></script>
<script>window.SMARTWAKE_BASE = '<?= BASE_URL ?>';</script>
<script src="<?= BASE_URL ?>assets/js/app.js?v=<?= filemtime(__DIR__ . '/assets/js/app.js') ?>" defer></script>
</body>
</html>
