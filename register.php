<?php
/**
 * SmartWake - Page d'inscription premium
 */
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: /smartwake/dashboard.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de sécurité invalide. Rechargez la page.';
    } else {
        $result = registerUser(
            trim($_POST['username'] ?? ''),
            trim($_POST['email']    ?? ''),
            trim($_POST['password'] ?? '')
        );
        if ($result['success']) {
            $success = "Compte créé avec succès ! Redirection vers la connexion...";
            header('refresh:2;url=/smartwake/login.php');
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
  <title>Inscription — SmartWake</title>
  <meta name="description" content="Créez votre compte SmartWake.">
  <meta name="robots" content="noindex">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/smartwake/assets/css/style.css">
</head>
<body class="auth-body">

  <!-- Particules de fond -->
  <div class="auth-bg" aria-hidden="true">
    <div class="auth-bg-orb orb-1"></div>
    <div class="auth-bg-orb orb-2"></div>
    <div class="auth-bg-orb orb-3"></div>
  </div>

  <div class="auth-page" role="main">
    <div class="auth-card fade-in">

      <!-- Logo & Titre -->
      <div class="auth-logo">
        <div class="auth-logo-icon" aria-hidden="true">🌅</div>
        <h1 class="auth-title">Créer un compte</h1>
        <p class="auth-subtitle">Rejoignez SmartWake et contrôlez votre réveil.</p>
      </div>

      <!-- Messages -->
      <?php if ($error): ?>
        <div class="alert alert-danger" role="alert" aria-live="assertive">
          <span aria-hidden="true">⚠️</span>
          <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success" role="alert" aria-live="polite">
          <span aria-hidden="true">✅</span>
          <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php else: ?>

        <!-- Formulaire -->
        <form method="POST" action="/smartwake/register.php" novalidate class="auth-form">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

          <div class="form-group">
            <label class="form-label" for="username">Nom d'utilisateur</label>
            <div class="form-input-wrap">
              <span class="form-input-icon" aria-hidden="true">👤</span>
              <input type="text" id="username" name="username" class="form-control"
                placeholder="Votre nom" required aria-required="true" minlength="3"
                value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="email">Adresse email</label>
            <div class="form-input-wrap">
              <span class="form-input-icon" aria-hidden="true">✉️</span>
              <input type="email" id="email" name="email" class="form-control"
                placeholder="vous@exemple.com" autocomplete="email" required aria-required="true"
                value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="password">Mot de passe</label>
            <div class="form-input-wrap">
              <span class="form-input-icon" aria-hidden="true">🔑</span>
              <input type="password" id="password" name="password" class="form-control"
                placeholder="Minimum 8 caractères" autocomplete="new-password" required aria-required="true" minlength="8">
            </div>
          </div>

          <button type="submit" class="btn btn-primary btn-block btn-lg" id="btn-register">
            S'inscrire
            <span class="btn-arrow" aria-hidden="true">→</span>
          </button>
        </form>

      <?php endif; ?>

      <div class="auth-divider"><span>ou</span></div>

      <p class="auth-switch">
        Déjà un compte ?
        <a href="/smartwake/login.php">Se connecter</a>
      </p>

    </div>
  </div>

  <footer class="auth-footer">
    <p>SmartWake © <?= date('Y') ?> — Projet ISEP</p>
  </footer>

</body>
</html>
