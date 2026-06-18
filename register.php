<?php
/**
 * SmartWake - Page d'inscription premium
 */
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'dashboard.php');
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
            header('refresh:2;url=' . BASE_URL . 'login.php');
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
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
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
<body class="auth-body">
<header class="auth-header">
  <div class="auth-header-title">
    🎓 Projet ISEP — <span>Équipe SmartWake</span>
  </div>
  <button class="theme-toggle" aria-label="Passer en mode clair" title="Changer le thème" onclick="toggleTheme()">
    <i class="ph-fill ph-sun"></i>
  </button>
</header>
<div class="auth-wrapper">

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
        <div class="auth-logo-icon" aria-hidden="true">
          <i class="ph-fill ph-sun-horizon" style="color: #00A4EF;"></i>
        </div>
        <h1 class="auth-title">Créer un compte</h1>
        <p class="auth-subtitle">Rejoignez SmartWake et contrôlez votre réveil.</p>
      </div>

      <!-- Messages -->
      <?php if ($error): ?>
        <div class="alert alert-danger" role="alert" aria-live="assertive">
          <span aria-hidden="true"><i class="ph-fill ph-warning-circle"></i></span>
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
        <form method="POST" action="<?= BASE_URL ?>register.php" novalidate class="auth-form">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

          <div class="form-group">
            <label class="form-label" for="username">Nom d'utilisateur</label>
            <div class="form-input-wrap">
              <span class="form-input-icon" aria-hidden="true"><i class="ph-fill ph-user"></i></span>
              <input type="text" id="username" name="username" class="form-control"
                placeholder="Votre nom" required aria-required="true" minlength="3"
                value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="email">Adresse email</label>
            <div class="form-input-wrap">
              <span class="form-input-icon" aria-hidden="true"><i class="ph-fill ph-envelope"></i></span>
              <input type="email" id="email" name="email" class="form-control"
                placeholder="vous@exemple.com" autocomplete="email" required aria-required="true"
                value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="password">Mot de passe</label>
            <div class="form-input-wrap">
              <span class="form-input-icon" aria-hidden="true"><i class="ph-fill ph-key"></i></span>
              <input type="password" id="password" name="password" class="form-control"
                placeholder="Minimum 8 caractères" autocomplete="new-password" required aria-required="true" minlength="8">
            </div>
          </div>

          <button type="submit" class="btn btn-primary btn-block btn-lg" id="btn-register">
            S'inscrire
            <span class="btn-arrow" aria-hidden="true"><i class="ph-bold ph-arrow-right"></i></span>
          </button>
        </form>

      <?php endif; ?>

      <div class="auth-divider"><span>ou</span></div>

      <p class="auth-switch">
        Déjà un compte ?
        <a href="<?= BASE_URL ?>login.php">Se connecter</a>
      </p>

    </div>
  </div>
</div>

  <footer class="auth-footer">
    <p>SmartWake © <?= date('Y') ?></p>
    <div class="auth-footer-links">
      <a href="#">CGU</a>
      <span class="separator">&middot;</span>
      <a href="#">Mentions Légales</a>
      <span class="separator">&middot;</span>
      <a href="mailto:contact@smartwake.isep.fr">Contact</a>
    </div>
  </footer>

<script src="<?= BASE_URL ?>assets/js/app.js?v=<?= filemtime(__DIR__ . '/assets/js/app.js') ?>" defer></script>
</body>
</html>
