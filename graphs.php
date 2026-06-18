<?php
/**
 * SmartWake - Historique
 * Design premium SaaS
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$data24h   = getLast24HoursData();
$data100   = getLast100Measures();
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Historique — Smart Alarm</title>
  <meta name="description" content="Historique complet des mesures de luminosité de SmartWake.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <script>
    function applyTheme(theme) {
      document.documentElement.setAttribute('data-theme', theme);
      localStorage.setItem('smartwake-theme', theme);
      document.querySelectorAll('.theme-toggle').forEach(btn => {
        btn.innerHTML = theme === 'light' ? '<i class="ph-fill ph-moon"></i>' : '<i class="ph-fill ph-sun"></i>';
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
      <a href="<?= BASE_URL ?>dashboard.php" class="navbar-brand" aria-label="Smart Alarm">
        <span class="brand-icon" aria-hidden="true"><i class="ph-fill ph-sun-horizon" style="font-size: 1.5rem; color: #00A4EF;"></i></span>
        <span class="brand-text">Smart<span class="brand-accent"> Alarm</span></span>
      </a>

      <button class="navbar-toggle" id="navbar-toggle"
        aria-controls="navbar-nav" aria-expanded="false" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>

      <ul class="navbar-nav" id="navbar-nav" role="list">
        <li><a href="<?= BASE_URL ?>history.php" class="nav-link">Historique</a></li>
        <li><a href="<?= BASE_URL ?>graphs.php" class="nav-link active" aria-current="page">Graphiques</a></li>
        <li class="nav-separator"></li>
        <li>
          <span class="nav-user">
            <span class="nav-user-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></span>
            <?= e($_SESSION['username']) ?>
          </span>
        </li>
        <li>
          <a href="<?= BASE_URL ?>logout.php?token=<?= urlencode($csrfToken) ?>"
             class="btn btn-ghost btn-sm" aria-label="Se déconnecter">
            Déconnexion
          </a>
        </li>
        <li>
          <button class="theme-toggle" aria-label="Passer en mode clair" title="Changer le thème" onclick="toggleTheme()">
            <i class="ph-fill ph-sun"></i>
          </button>
        </li>
      </ul>
    </div>
  </nav>

  <!-- ===== HERO HEADER ===== -->
  <section class="dash-hero">
    <div class="container">
      <div class="dash-hero-inner" style="justify-content: flex-start;">
        <div class="dash-hero-text">
          <h1 class="dash-title">Évolution de la luminosité</h1>
          <p class="dash-subtitle">Consultez les tendances de luminosité sur les dernières 24 heures et les 100 dernières mesures.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== MAIN CONTENT ===== -->
  <main class="container dash-main" id="main-content">

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

  </main>

  <!-- ===== FOOTER ===== -->
  <footer class="site-footer" role="contentinfo">
    <div class="container footer-inner" style="flex-direction: column; gap: 0.5rem;">
      <p style="margin: 0; color: var(--text-muted);">Smart Alarm © <?= date('Y') ?></p>
      <div class="auth-footer-links" style="margin-top: 0;">
        <a href="<?= BASE_URL ?>cgu.php">CGU</a>
        <span class="separator">&middot;</span>
        <a href="<?= BASE_URL ?>mentions-legales.php">Mentions Légales</a>
        <span class="separator">&middot;</span>
        <a href="mailto:contact@smartwake.isep.fr">Contact</a>
      </div>
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
<script>window.SMARTWAKE_BASE = '<?= BASE_URL ?>';</script>
<script src="<?= BASE_URL ?>assets/js/app.js?v=<?= filemtime(__DIR__ . '/assets/js/app.js') ?>" defer></script>

</body>
</html>
