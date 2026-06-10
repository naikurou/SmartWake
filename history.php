<?php
/**
 * SmartWake - Page Historique
 * Tableau paginé de toutes les mesures de luminosité
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

// Pagination
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$result  = getHistoryPaginated($page, $perPage);
$rows    = $result['data'];
$total   = $result['total'];
$pages   = $result['pages'];

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Historique des mesures — SmartWake</title>
  <meta name="description" content="Historique complet des mesures de luminosité enregistrées par le capteur Tiva C.">
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
        <li><a href="/smartwake/dashboard.php" class="nav-link">
          <span aria-hidden="true">📊</span> Dashboard
        </a></li>
        <li><a href="/smartwake/history.php" class="nav-link active" aria-current="page">
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

    <!-- En-tête -->
    <div class="page-header">
      <h1><span aria-hidden="true">📋</span> Historique des mesures</h1>
      <p>
        <?= number_format($total, 0, ',', ' ') ?> mesure<?= $total > 1 ? 's' : '' ?> enregistrée<?= $total > 1 ? 's' : '' ?>
        &middot; Page <?= e($page) ?> / <?= e($pages ?: 1) ?>
      </p>
    </div>

    <?php if (empty($rows)): ?>
      <!-- État vide -->
      <div class="card" style="text-align:center; padding:3rem;">
        <span aria-hidden="true" style="font-size:3rem; display:block; margin-bottom:1rem;">📭</span>
        <h2 style="color:var(--color-text-muted); font-size:1.2rem;">Aucune mesure enregistrée</h2>
        <p class="card-sub" style="margin-top:0.5rem;">
          Démarrez le script <code>serial/read_sensor.php</code> pour commencer à collecter des données.
        </p>
        <a href="/smartwake/dashboard.php" class="btn btn-primary" style="margin-top:1.5rem;">
          Retour au dashboard
        </a>
      </div>

    <?php else: ?>

      <!-- Tableau des mesures -->
      <div class="card fade-in" style="padding:0; overflow:hidden;">
        <div class="table-wrapper">
          <table
            class="data-table"
            id="history-table"
            aria-label="Historique des mesures de luminosité"
            aria-rowcount="<?= e($total) ?>"
          >
            <thead>
              <tr>
                <th scope="col" abbr="Numéro">#</th>
                <th scope="col">Date &amp; Heure</th>
                <th scope="col">Luminosité (lux)</th>
                <th scope="col">État</th>
                <th scope="col">Réveil</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $rowNum = $total - ($page - 1) * $perPage;
              foreach ($rows as $row):
                $lux    = (int)$row['light_value'];
                $status = $row['day_status'];
                $wake   = ($status === 'DAY' && $lux > 700);
              ?>
              <tr>
                <td style="color:var(--color-text-muted); font-size:0.8rem;"><?= e($rowNum--) ?></td>
                <td>
                  <time datetime="<?= e($row['created_at']) ?>">
                    <?= e(formatDate($row['created_at'])) ?>
                  </time>
                </td>
                <td>
                  <span style="font-family:var(--font-display); font-weight:700; color:<?= $status === 'DAY' ? 'var(--color-day)' : 'var(--color-night)' ?>;">
                    <?= e($lux) ?>
                  </span>
                  <span style="font-size:0.8rem; color:var(--color-text-muted);"> lux</span>

                  <!-- Mini barre visuelle -->
                  <div style="height:3px; background:rgba(255,255,255,0.07); border-radius:999px; margin-top:4px; width:100%;">
                    <div style="
                      height:3px;
                      width:<?= min(round(($lux/1024)*100), 100) ?>%;
                      border-radius:999px;
                      background:<?= $status === 'DAY' ? 'var(--color-day)' : 'var(--color-night)' ?>;
                    "></div>
                  </div>
                </td>
                <td><?= statusBadge($status) ?></td>
                <td>
                  <?php if ($wake): ?>
                    <span class="badge badge-success" title="Conditions idéales de réveil">🌅 Optimal</span>
                  <?php elseif ($status === 'DAY'): ?>
                    <span class="badge" style="background:rgba(37,99,235,0.15);color:var(--color-primary-lt);border:1px solid rgba(37,99,235,0.3);">
                      ☀️ Possible
                    </span>
                  <?php else: ?>
                    <span class="badge badge-night" title="Il fait nuit">💤 Non</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ===== PAGINATION ===== -->
      <?php if ($pages > 1): ?>
      <nav class="pagination" role="navigation" aria-label="Pagination de l'historique">

        <!-- Première page -->
        <?php if ($page > 1): ?>
          <a href="?page=1" class="page-btn" aria-label="Première page">«</a>
          <a href="?page=<?= $page - 1 ?>" class="page-btn" aria-label="Page précédente">‹</a>
        <?php else: ?>
          <span class="page-btn" aria-disabled="true">«</span>
          <span class="page-btn" aria-disabled="true">‹</span>
        <?php endif; ?>

        <!-- Pages numérotées (fenêtre glissante de 5) -->
        <?php
        $window = 2;
        $start  = max(1, $page - $window);
        $end    = min($pages, $page + $window);
        if ($start > 1): ?><span class="page-btn" style="pointer-events:none;">…</span><?php endif;
        for ($p = $start; $p <= $end; $p++): ?>
          <?php if ($p === $page): ?>
            <span class="page-btn active" aria-current="page" aria-label="Page <?= $p ?>"><?= $p ?></span>
          <?php else: ?>
            <a href="?page=<?= $p ?>" class="page-btn" aria-label="Page <?= $p ?>"><?= $p ?></a>
          <?php endif; ?>
        <?php endfor;
        if ($end < $pages): ?><span class="page-btn" style="pointer-events:none;">…</span><?php endif; ?>

        <!-- Dernière page -->
        <?php if ($page < $pages): ?>
          <a href="?page=<?= $page + 1 ?>" class="page-btn" aria-label="Page suivante">›</a>
          <a href="?page=<?= $pages ?>" class="page-btn" aria-label="Dernière page">»</a>
        <?php else: ?>
          <span class="page-btn" aria-disabled="true">›</span>
          <span class="page-btn" aria-disabled="true">»</span>
        <?php endif; ?>

      </nav>
      <p style="text-align:center; font-size:0.8rem; color:var(--color-text-muted); margin-top:0.5rem;">
        Affichage de <?= ($page - 1) * $perPage + 1 ?> à <?= min($page * $perPage, $total) ?> sur <?= number_format($total, 0, ',', ' ') ?> mesures
      </p>
      <?php endif; ?>

    <?php endif; ?>

    <!-- Bouton export CSV -->
    <div style="text-align:center; margin: 2rem 0;">
      <a href="/smartwake/api/latest.php" class="btn btn-outline btn-sm" target="_blank" rel="noopener">
        <span aria-hidden="true">🔌</span> Voir l'API JSON
      </a>
      <a href="/smartwake/dashboard.php" class="btn btn-primary btn-sm" style="margin-left:0.5rem;">
        <span aria-hidden="true">📊</span> Dashboard
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

<script src="/smartwake/assets/js/app.js" defer></script>
</body>
</html>
