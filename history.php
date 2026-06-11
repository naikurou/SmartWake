<?php
/**
 * SmartWake - Historique
 * Design premium SaaS
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 20;
$history   = getHistoryPaginated($page, $perPage);
$data      = $history['data'];
$total     = $history['total'];
$pages     = $history['pages'];
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Historique — SmartWake</title>
  <meta name="description" content="Historique complet des mesures de luminosité de SmartWake.">
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
        <li><a href="/smartwake/dashboard.php" class="nav-link">Dashboard</a></li>
        <li><a href="/smartwake/history.php" class="nav-link active" aria-current="page">Historique</a></li>
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
      <div class="dash-hero-inner" style="justify-content: flex-start;">
        <div class="dash-hero-text">
          <h1 class="dash-title">Historique des mesures</h1>
          <p class="dash-subtitle">Explorez les <?= number_format($total, 0, ',', ' ') ?> relevés enregistrés par votre capteur Tiva C.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== MAIN CONTENT ===== -->
  <main class="container dash-main" id="main-content">

    <div class="card reveal" style="padding: 0; overflow: hidden;">
      <?php if (empty($data)): ?>
        <div style="padding: 3rem; text-align: center; color: var(--color-text-muted);">
          <span style="font-size: 3rem; display: block; margin-bottom: 1rem;">📭</span>
          <p>Aucune donnée enregistrée pour le moment.</p>
        </div>
      <?php else: ?>
        <div class="table-wrapper">
          <table class="data-table" id="history-table">
            <thead>
              <tr>
                <th scope="col" style="width: 25%;">Date & Heure</th>
                <th scope="col" style="width: 25%;">Luminosité (lux)</th>
                <th scope="col" style="width: 50%;">Niveau détecté</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($data as $row): ?>
                <?php
                  $valLux = (int)$row['light_value'];
                  $status = $row['day_status'];
                ?>
                <tr>
                  <td><?= e(formatDate($row['created_at'])) ?></td>
                  <td style="font-family: var(--font-display); font-weight: 700; color: var(--color-primary-glow);">
                    <?= e($valLux) ?>
                  </td>
                  <td>
                    <?= statusBadge($status, $valLux) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- ===== PAGINATION ===== -->
    <?php if ($pages > 1): ?>
      <nav class="pagination reveal" aria-label="Pagination de l'historique">
        <?php if ($page > 1): ?>
          <a href="?page=<?= $page - 1 ?>" class="page-btn" aria-label="Page précédente">←</a>
        <?php else: ?>
          <button class="page-btn" disabled aria-disabled="true">←</button>
        <?php endif; ?>

        <?php
          $start = max(1, $page - 2);
          $end   = min($pages, $page + 2);

          if ($start > 1) {
              echo '<a href="?page=1" class="page-btn">1</a>';
              if ($start > 2) echo '<span class="page-dots">...</span>';
          }

          for ($i = $start; $i <= $end; $i++) {
              $active = ($i === $page) ? 'active' : '';
              $aria   = ($i === $page) ? 'aria-current="page"' : '';
              echo "<a href=\"?page=$i\" class=\"page-btn $active\" $aria>$i</a>";
          }

          if ($end < $pages) {
              if ($end < $pages - 1) echo '<span class="page-dots">...</span>';
              echo "<a href=\"?page=$pages\" class=\"page-btn\">$pages</a>";
          }
        ?>

        <?php if ($page < $pages): ?>
          <a href="?page=<?= $page + 1 ?>" class="page-btn" aria-label="Page suivante">→</a>
        <?php else: ?>
          <button class="page-btn" disabled aria-disabled="true">→</button>
        <?php endif; ?>
      </nav>
    <?php endif; ?>

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
<script src="/smartwake/assets/js/app.js" defer></script>
</body>
</html>
