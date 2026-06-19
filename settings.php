<?php
/**
 * SmartWake - Paramètres de l'alarme
 * Configuration dynamique des seuils
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$db = getDB();
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Session expirée ou requête invalide.";
    } else {
        $nightThresh = (int)$_POST['night_lux_threshold'];
        $dayThresh = (int)$_POST['day_lux_threshold'];
        
        if ($nightThresh < 0 || $dayThresh < 0 || $nightThresh >= $dayThresh) {
            $error = "Les seuils sont invalides. Le seuil de nuit doit être inférieur au seuil de jour.";
        } else {
            try {
                $stmt = $db->prepare('UPDATE alarm_settings SET night_lux_threshold = :night, day_lux_threshold = :day WHERE id = 1');
                $stmt->execute([':night' => $nightThresh, ':day' => $dayThresh]);
                $success = true;
            } catch (Exception $e) {
                $error = "Erreur lors de la sauvegarde : " . $e->getMessage();
            }
        }
    }
}

$settings = getAlarmSettings();
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Paramètres — SmartWake</title>
  <link rel="stylesheet" href="/smartwake/assets/css/style.css">
</head>
<body>
<div class="page-wrapper">
  
  <nav class="navbar" role="navigation">
    <div class="container navbar-inner">
      <a href="/smartwake/dashboard.php" class="navbar-brand">
        <span class="brand-text">Smart<span class="brand-accent">Alarm</span></span>
      </a>
      <ul class="navbar-nav" id="navbar-nav">
        <li><a href="/smartwake/dashboard.php" class="nav-link">Dashboard</a></li>
        <li><a href="/smartwake/history.php" class="nav-link">Historique</a></li>
        <li><a href="/smartwake/settings.php" class="nav-link active">Paramètres ⚙️</a></li>
        <li class="nav-separator"></li>
        <li>
          <span class="nav-user">
            <span class="nav-user-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></span>
            <?= e($_SESSION['username']) ?>
          </span>
        </li>
      </ul>
    </div>
  </nav>

  <main class="container dash-main">
    <section class="dash-hero">
      <div class="dash-hero-text">
        <h1 class="dash-title">Paramètres du Réveil</h1>
        <p class="dash-subtitle">Ajustez la sensibilité du capteur pour déclencher l'alarme selon vos préférences.</p>
      </div>
    </section>

    <div class="card" style="max-width: 600px; margin: 0 auto;">
      <?php if ($success): ?>
        <div class="alert alert-success">✅ Paramètres sauvegardés avec succès ! La carte Tiva C prendra en compte ces changements dans quelques secondes.</div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        
        <div class="form-group" style="margin-bottom: 2rem;">
          <label class="form-label">Seuil de tolérance Nuit (Lux)</label>
          <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
            Si la lumière dépasse ce seuil entre 22h et 6h, l'alarme se déclenchera pour vous avertir d'une lumière anormale.
          </p>
          <input type="number" class="form-control" name="night_lux_threshold" value="<?= e($settings['night_lux_threshold']) ?>" required min="1" max="200">
        </div>

        <div class="form-group" style="margin-bottom: 2rem;">
          <label class="form-label">Seuil de Réveil Jour (Lux)</label>
          <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
            Si la lumière dépasse ce seuil entre 6h et 22h, le réveil optimal s'actionnera (Plein jour).
          </p>
          <input type="number" class="form-control" name="day_lux_threshold" value="<?= e($settings['day_lux_threshold']) ?>" required min="50" max="4000">
        </div>

        <button type="submit" class="btn btn-primary btn-block">Enregistrer les paramètres</button>
      </form>
    </div>
  </main>
</div>
</body>
</html>
