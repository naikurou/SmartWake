<?php
/**
 * SmartWake - Page de connexion premium
 */
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$error   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de sécurité invalide. Rechargez la page.';
    } else {
        $result = loginUser(
            trim($_POST['email']    ?? ''),
            trim($_POST['password'] ?? '')
        );
        if ($result['success']) {
            header('Location: ' . BASE_URL . 'dashboard.php');
            exit;
        }
        $error = $result['message'];
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
  <meta name="description" content="Connectez-vous à SmartWake pour surveiller votre capteur de luminosité.">
  <meta name="robots" content="noindex">
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
<body class="auth-body">
<header class="auth-header">
  <div class="auth-header-title">
    🎓 Projet ISEP — <span>Équipe SmartWake</span>
  </div>
  <button class="theme-toggle" aria-label="Passer en mode clair" title="Changer le thème" onclick="toggleTheme()">☀️</button>
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
        <div class="auth-logo-icon" aria-hidden="true">🌅</div>
        <h1 class="auth-title">Smart<span class="auth-title-accent">Wake</span></h1>
        <p class="auth-subtitle">Réveil Intelligent Adapté à l'Environnement</p>
      </div>

      <!-- Erreur -->
      <?php if ($error): ?>
        <div class="alert alert-danger" role="alert" aria-live="assertive">
          <span aria-hidden="true">⚠️</span>
          <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <!-- Formulaire -->
      <form method="POST" action="<?= BASE_URL ?>login.php" novalidate class="auth-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

        <div class="form-group">
          <label class="form-label" for="email">Adresse email</label>
          <div class="form-input-wrap">
            <span class="form-input-icon" aria-hidden="true">✉️</span>
            <input type="email" id="email" name="email" class="form-control"
              placeholder="vous@exemple.com" autocomplete="email"
              required aria-required="true"
              value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Mot de passe</label>
          <div class="form-input-wrap">
            <span class="form-input-icon" aria-hidden="true">🔑</span>
            <input type="password" id="password" name="password" class="form-control"
              placeholder="••••••••" autocomplete="current-password"
              required aria-required="true" minlength="8">
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg" id="btn-login">
          Se connecter
          <span class="btn-arrow" aria-hidden="true">→</span>
        </button>
      </form>

      <div class="auth-divider"><span>ou</span></div>

      <p class="auth-switch">
        Pas encore de compte ?
        <a href="<?= BASE_URL ?>register.php">Créer un compte</a>
      </p>

    </div>
  </div>

  <footer class="auth-footer">
    <p>SmartWake © <?= date('Y') ?> — Projet ISEP · Capteur LDR · TIVA EK123GXL · Energia & VSCode</p>
  </footer>

<script src="<?= BASE_URL ?>assets/js/app.js?v=<?= filemtime(__DIR__ . '/assets/js/app.js') ?>" defer></script>
</body>
</html>
