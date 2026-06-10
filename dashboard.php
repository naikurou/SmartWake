<?php
/**
 * SmartWake - Tableau de bord principal
 * Affiche la luminosité en temps réel, statut Jour/Nuit,
 * réveil intelligent, statistiques et graphiques.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

// Récupération des données
$latest    = getLatestMeasure();
$stats     = getTodayStats();
$data24h   = getLast24HoursData();
$data100   = getLast100Measures();

$lux       = $latest ? (int)$latest['light_value'] : 0;
$status    = $latest ? $latest['day_status']        : 'UNKNOWN';
$timestamp = $latest ? $latest['created_at']        : null;
$wake      = getWakeRecommendation($lux, $status);
$luxPct    = min(round(($lux / 1024) * 100), 100);
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tableau de bord — SmartWake</title>
  <meta name="description" content="Surveillance en temps réel de la luminosité ambiante et état Jour/Nuit via capteur Tiva C.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/smartwake/assets/css/style.css">
</head>
<body>
<div class="page-wrapper">

  <!-- ===== NAVBAR ===== -->
  <nav class="navbar" role="navigation" aria-label="Navigation principale">
    <div class="container navbar-inner">
      <a href="/smartwake/dashboard.php" class="navbar-brand" aria-label="SmartWake — Accueil">
        <span class="brand-icon" aria-hidden="true">🌅</span>
        <span class="brand-glow">SmartWake</span>
      </a>

      <button
        class="navbar-toggle"
        id="navbar-toggle"
        aria-controls="navbar-nav"
        aria-expanded="false"
        aria-label="Ouvrir le menu"
      >☰</button>

      <ul class="navbar-nav" id="navbar-nav" role="list">
        <li><a href="/smartwake/dashboard.php" class="nav-link active" aria-current="page">
          <span aria-hidden="true">📊</span> Dashboard
        </a></li>
        <li><a href="/smartwake/history.php" class="nav-link">
          <span aria-hidden="true">📋</span> Historique
        </a></li>
        <li><a href="/smartwake/api/latest.php" class="nav-link" target="_blank" rel="noopener">
          <span aria-hidden="true">🔌</span> API
        </a></li>
        <li>
          <span style="color:var(--color-text-muted);font-size:0.85rem;padding:0.5rem 0.5rem;">
            <span aria-hidden="true">👤</span> <?= e($_SESSION['username']) ?>
          </span>
        </li>
        <li>
          <a href="/smartwake/logout.php?token=<?= urlencode($csrfToken) ?>"
             class="btn btn-outline btn-sm"
             aria-label="Se déconnecter">
            <span aria-hidden="true">🚪</span> Déconnexion
          </a>
        </li>
      </ul>
    </div>
  </nav>

  <!-- ===== CONTENU PRINCIPAL ===== -->
  <main class="container" id="main-content">

    <!-- En-tête de page -->
    <div class="page-header">
      <h1>
        <span aria-hidden="true">📡</span> Tableau de bord
        <span class="live-dot" title="Mise à jour automatique toutes les 5 secondes" aria-label="En direct"></span>
      </h1>
      <p>Surveillance en temps réel &middot; Mise à jour automatique toutes les 5 secondes</p>
    </div>

    <!-- ===== CARTES PRINCIPALES ===== -->
    <div class="dashboard-grid" role="region" aria-label="Mesures en temps réel">

      <!-- Carte 1 : Luminosité actuelle -->
      <article class="card fade-in" aria-label="Luminosité actuelle">
        <p class="card-title">
          <span aria-hidden="true">💡</span> Luminosité actuelle
        </p>

        <!-- Cercle visuel -->
        <div
          id="lux-circle"
          class="lux-circle <?= $status === 'DAY' ? 'is-day' : 'is-night' ?>"
          role="img"
          aria-label="Indicateur de luminosité"
        >
          <span class="lux-icon" aria-hidden="true"><?= $status === 'DAY' ? '☀️' : '🌙' ?></span>
          <span class="lux-val"><?= e($lux) ?> lux</span>
        </div>

        <p
          id="live-lux"
          class="card-value glow-blue"
          style="text-align:center;"
          aria-live="polite"
          aria-atomic="true"
        ><?= e($lux) ?> lux</p>

        <!-- Barre de progression -->
        <div class="lux-bar-wrap" aria-label="Niveau de luminosité">
          <div class="lux-bar-label">
            <span>0 lux</span><span>1024 lux (max)</span>
          </div>
          <div class="lux-bar" role="progressbar" aria-valuenow="<?= $lux ?>" aria-valuemin="0" aria-valuemax="1024">
            <div id="lux-bar-fill" class="lux-bar-fill" style="width:<?= $luxPct ?>%"></div>
          </div>
        </div>
      </article>

      <!-- Carte 2 : État Jour/Nuit -->
      <article class="card fade-in-delay-1" aria-label="État détecté">
        <p class="card-title">
          <span aria-hidden="true">🔍</span> État détecté
        </p>

        <div style="text-align:center; padding: 1.5rem 0;">
          <div id="live-status" style="font-size:1.1rem; margin-bottom:0.75rem;">
            <?php if ($status === 'DAY'): ?>
              <span class="badge badge-day">☀️ Jour</span>
            <?php else: ?>
              <span class="badge badge-night">🌙 Nuit</span>
            <?php endif; ?>
          </div>

          <p
            id="live-status-text"
            class="card-value <?= $status === 'DAY' ? 'text-day glow-day' : 'text-night glow-night' ?>"
            aria-live="polite"
            aria-atomic="true"
          ><?= $status === 'DAY' ? 'JOUR' : 'NUIT' ?></p>

          <p class="card-sub" style="margin-top:0.5rem;">
            Seuil : &gt; 500 lux = Jour
          </p>
        </div>

        <!-- Statistiques du jour -->
        <div class="stat-row" aria-label="Statistiques du jour">
          <div class="stat-item">
            <div class="stat-val"><?= e($stats['min_lux'] ?? 0) ?></div>
            <div class="stat-lbl">Min lux</div>
          </div>
          <div class="stat-item">
            <div class="stat-val"><?= e($stats['max_lux'] ?? 0) ?></div>
            <div class="stat-lbl">Max lux</div>
          </div>
          <div class="stat-item">
            <div class="stat-val"><?= e($stats['avg_lux'] ?? 0) ?></div>
            <div class="stat-lbl">Moy lux</div>
          </div>
          <div class="stat-item">
            <div class="stat-val"><?= e($stats['total_measures'] ?? 0) ?></div>
            <div class="stat-lbl">Mesures</div>
          </div>
        </div>
      </article>

      <!-- Carte 3 : Dernière mesure -->
      <article class="card fade-in-delay-2" aria-label="Dernière mesure reçue">
        <p class="card-title">
          <span aria-hidden="true">🕐</span> Dernière mesure reçue
        </p>

        <div style="text-align:center; padding:1rem 0;">
          <span aria-hidden="true" style="font-size:3rem; display:block; margin-bottom:0.5rem;">📡</span>
          <p
            id="live-timestamp"
            class="card-value text-accent"
            style="font-size:1.1rem;"
            aria-live="polite"
            aria-atomic="true"
          >
            <?= $timestamp ? e(formatDate($timestamp)) : 'Aucune mesure' ?>
          </p>
          <p class="card-sub" style="margin-top:0.5rem;">
            Capteur : Tiva C via port série
          </p>
        </div>

        <hr class="divider">

        <div style="font-size:0.85rem; color:var(--color-text-muted);">
          <p>🔌 <strong style="color:var(--color-text);">Port :</strong> COM22 — 9600 baud</p>
          <p style="margin-top:0.3rem;">📊 <strong style="color:var(--color-text);">Capteur :</strong> Résistance LDR / photorésistance</p>
          <p style="margin-top:0.3rem;">🗄️ <strong style="color:var(--color-text);">Stockage :</strong> Azure Database for MySQL</p>
        </div>
      </article>

    </div>
    <!-- /dashboard-grid -->

    <!-- ===== SECTION RÉVEIL INTELLIGENT ===== -->
    <div class="section-label" id="wake-section">
      <span aria-hidden="true">⏰</span> Réveil Intelligent
    </div>

    <div
      id="wake-card"
      class="wake-card card <?= $wake['optimal'] ? 'optimal' : 'not-optimal' ?>"
      role="region"
      aria-label="Recommandation de réveil"
      style="margin-bottom:2rem;"
    >
      <span
        id="wake-icon"
        class="wake-icon"
        aria-hidden="true"
      ><?= $wake['optimal'] ? '🌅' : '😴' ?></span>

      <h2 id="wake-message" style="font-size:1.2rem; margin-bottom:0.5rem;">
        <?= e($wake['message']) ?>
      </h2>
      <p id="wake-detail" class="card-sub">
        <?= e($wake['detail']) ?>
      </p>

      <?php if ($wake['optimal']): ?>
        <p style="margin-top:1rem;">
          <span class="badge badge-success">✅ Conditions optimales</span>
        </p>
      <?php else: ?>
        <p style="margin-top:1rem;">
          <span class="badge badge-night">💤 Pas encore</span>
        </p>
      <?php endif; ?>
    </div>

    <!-- ===== GRAPHIQUES ===== -->
    <div class="section-label">
      <span aria-hidden="true">📈</span> Évolution de la luminosité
    </div>

    <div class="chart-grid" role="region" aria-label="Graphiques de luminosité">

      <!-- Graphique 24h -->
      <div class="card fade-in">
        <p class="card-title">Dernières 24 heures</p>
        <div class="chart-container">
          <canvas
            id="chart-24h"
            role="img"
            aria-label="Graphique de luminosité sur les dernières 24 heures"
          ></canvas>
        </div>
      </div>

      <!-- Graphique 100 mesures -->
      <div class="card fade-in-delay-1">
        <p class="card-title">100 dernières mesures</p>
        <div class="chart-container">
          <canvas
            id="chart-100"
            role="img"
            aria-label="Graphique des 100 dernières mesures de luminosité"
          ></canvas>
        </div>
      </div>

    </div>
    <!-- /chart-grid -->

    <!-- Lien vers historique complet -->
    <div style="text-align:center; margin-bottom:2rem;">
      <a href="/smartwake/history.php" class="btn btn-outline">
        <span aria-hidden="true">📋</span> Voir l'historique complet
      </a>
    </div>

  </main>

  <!-- ===== FOOTER ===== -->
  <footer role="contentinfo">
    <p>
      SmartWake &copy; <?= date('Y') ?> &mdash; Projet ISEP &middot;
      <a href="/smartwake/api/latest.php" target="_blank" rel="noopener">API JSON</a> &middot;
      Capteur Tiva C &middot; Azure Database for MySQL
    </p>
  </footer>

</div><!-- /page-wrapper -->

<!-- Données PHP → JavaScript (JSON sécurisé) -->
<script>
  const CHART_24H_DATA = <?= json_encode($data24h,  JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  const CHART_100_DATA = <?= json_encode($data100, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
</script>

<!-- Chart.js CDN (sans plugin inutile) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"
        integrity="sha256-oVuCdcZBQCLlBt4H8D0lUV5J+LbGGJPULXgKpnXoUHU="
        crossorigin="anonymous"></script>
<script src="/smartwake/assets/js/app.js" defer></script>
</body>
</html>
