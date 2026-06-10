<?php
/**
 * SmartWake - Page de connexion
 */
require_once __DIR__ . '/includes/auth.php';

// Déjà connecté → dashboard
if (isLoggedIn()) {
    header('Location: /smartwake/dashboard.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de sécurité invalide. Rechargez la page.';
    } else {
        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');
        $result   = loginUser($email, $password);

        if ($result['success']) {
            header('Location: /smartwake/dashboard.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion — SmartWake</title>
  <meta name="description" content="Connectez-vous à SmartWake pour accéder à votre tableau de bord de réveil intelligent.">
  <meta name="robots" content="noindex">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/smartwake/assets/css/style.css">
</head>
<body>
<div class="auth-page" role="main">
  <div class="auth-card fade-in">

    <!-- Logo -->
    <div class="auth-logo">
      <span class="logo-icon" aria-hidden="true">🌅</span>
      <h1>SmartWake</h1>
      <p class="subtitle">Réveil Intelligent Adapté à l'Environnement</p>
    </div>

    <!-- Message d'erreur -->
    <?php if ($error): ?>
      <div class="alert alert-danger" role="alert" aria-live="assertive">
        <span aria-hidden="true">⚠️</span>
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <!-- Formulaire de connexion -->
    <form method="POST" action="/smartwake/login.php" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

      <div class="form-group">
        <label class="form-label" for="email">Adresse email</label>
        <input
          type="email"
          id="email"
          name="email"
          class="form-control"
          placeholder="vous@exemple.com"
          autocomplete="email"
          required
          aria-required="true"
          value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
        >
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Mot de passe</label>
        <input
          type="password"
          id="password"
          name="password"
          class="form-control"
          placeholder="••••••••"
          autocomplete="current-password"
          required
          aria-required="true"
          minlength="8"
        >
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg" id="btn-login">
        <span aria-hidden="true">🔑</span> Se connecter
      </button>
    </form>

    <hr class="divider">

    <p style="text-align:center; font-size:0.9rem; color:var(--color-text-muted);">
      Pas encore de compte ?
      <a href="/smartwake/register.php" style="color:var(--color-accent);">Créer un compte</a>
    </p>

  </div>
</div>

<footer>
  <p>SmartWake &copy; <?= date('Y') ?> — Projet ISEP &middot; Capteur de luminosité Tiva C</p>
</footer>
</body>
</html>
