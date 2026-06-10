<?php
/**
 * SmartWake - Page d'inscription
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
        $username  = trim($_POST['username']  ?? '');
        $email     = trim($_POST['email']     ?? '');
        $password  = trim($_POST['password']  ?? '');
        $password2 = trim($_POST['password2'] ?? '');

        if ($password !== $password2) {
            $error = 'Les mots de passe ne correspondent pas.';
        } else {
            $result = registerUser($username, $email, $password);
            if ($result['success']) {
                $success = $result['message'] . ' Vous pouvez maintenant vous connecter.';
            } else {
                $error = $result['message'];
            }
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
  <title>Créer un compte — SmartWake</title>
  <meta name="description" content="Créez votre compte SmartWake pour accéder au système de réveil intelligent connecté.">
  <meta name="robots" content="noindex">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/smartwake/assets/css/style.css">
</head>
<body>
<div class="auth-page" role="main">
  <div class="auth-card fade-in">

    <div class="auth-logo">
      <span class="logo-icon" aria-hidden="true">🌅</span>
      <h1>SmartWake</h1>
      <p class="subtitle">Créer un compte</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger" role="alert" aria-live="assertive">
        <span aria-hidden="true">⚠️</span>
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert alert-success" role="status" aria-live="polite">
        <span aria-hidden="true">✅</span>
        <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
      </div>
      <p style="text-align:center; margin-top:1rem;">
        <a href="/smartwake/login.php" class="btn btn-primary">Se connecter</a>
      </p>
    <?php else: ?>

    <form method="POST" action="/smartwake/register.php" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

      <div class="form-group">
        <label class="form-label" for="username">Nom d'utilisateur</label>
        <input
          type="text"
          id="username"
          name="username"
          class="form-control"
          placeholder="Votre nom"
          autocomplete="name"
          required
          aria-required="true"
          minlength="3"
          maxlength="100"
          value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
        >
      </div>

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
          placeholder="Minimum 8 caractères"
          autocomplete="new-password"
          required
          aria-required="true"
          minlength="8"
          aria-describedby="password-hint"
        >
        <p id="password-hint" class="card-sub" style="margin-top:0.3rem;font-size:0.78rem;">
          Au moins 8 caractères recommandés.
        </p>
      </div>

      <div class="form-group">
        <label class="form-label" for="password2">Confirmer le mot de passe</label>
        <input
          type="password"
          id="password2"
          name="password2"
          class="form-control"
          placeholder="Répéter le mot de passe"
          autocomplete="new-password"
          required
          aria-required="true"
          minlength="8"
        >
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg" id="btn-register">
        <span aria-hidden="true">✨</span> Créer mon compte
      </button>
    </form>

    <?php endif; ?>

    <hr class="divider">

    <p style="text-align:center; font-size:0.9rem; color:var(--color-text-muted);">
      Déjà inscrit ?
      <a href="/smartwake/login.php" style="color:var(--color-accent);">Se connecter</a>
    </p>

  </div>
</div>

<footer>
  <p>SmartWake &copy; <?= date('Y') ?> — Projet ISEP &middot; Capteur de luminosité Tiva C</p>
</footer>
</body>
</html>
