<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Conditions Générales d'Utilisation — Smart Alarm</title>
  <meta name="robots" content="noindex">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <style>
    .legal-page { width: 100%; max-width: 800px; margin: 80px auto 2rem auto; padding: 1rem; position: relative; z-index: 10; }
    .legal-card { background: var(--bg-card); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 3rem; backdrop-filter: blur(24px); box-shadow: var(--shadow-lg); color: var(--text-main); }
    .legal-title { font-size: 2.5rem; font-weight: 700; margin-bottom: 2rem; background: var(--brand-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-align: center; }
    .legal-card h2 { font-size: 1.25rem; font-weight: 600; margin-top: 2rem; margin-bottom: 1rem; color: var(--text-main); }
    .legal-card p, .legal-card ul { margin-bottom: 1rem; color: var(--text-muted); line-height: 1.6; }
    .legal-card ul { padding-left: 1.5rem; }
    .legal-card a { color: var(--text-main); text-decoration: underline; }
    @media (max-width: 768px) {
      .legal-page { margin-top: 60px; padding: 0.5rem; }
      .legal-card { padding: 1.5rem; }
      .legal-title { font-size: 1.75rem; }
    }
    [data-theme="light"] .legal-card { background: rgba(255, 255, 255, 0.85); }
  </style>
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
<body class="auth-body" style="justify-content: flex-start; display: block;">
<header class="auth-header" style="position: fixed; background: rgba(17,17,17,0.8); backdrop-filter: blur(12px);">
  <div class="auth-header-title" style="cursor: pointer;" onclick="window.location.href='<?= BASE_URL ?>'">
    <i class="ph-fill ph-arrow-left" style="font-size: 1.25rem; margin-right: 0.5rem;"></i> Retour à l'accueil
  </div>
  <button class="theme-toggle" aria-label="Passer en mode clair" title="Changer le thème" onclick="toggleTheme()">
    <i class="ph-fill ph-sun"></i>
  </button>
</header>
<div class="auth-wrapper" style="display: block;">

  <!-- Particules de fond -->
  <div class="auth-bg" aria-hidden="true">
    <div class="auth-bg-orb orb-1"></div>
    <div class="auth-bg-orb orb-2"></div>
    <div class="auth-bg-orb orb-3"></div>
  </div>

  <div class="legal-page" role="main">
    <div class="legal-card fade-in">
      <h1 class="legal-title">Conditions Générales d'Utilisation</h1>
      <p><em>Dernière mise à jour : <?= date('d/m/Y') ?></em></p>

      <h2>1. Objet</h2>
      <p>Les présentes Conditions Générales d'Utilisation (CGU) encadrent l'accès et l'utilisation de l'application Smart Alarm développée par l'Équipe Projet ISEP.</p>

      <h2>2. Accès au service</h2>
      <p>L'accès à l'application nécessite la création d'un compte utilisateur. Vous êtes responsable du maintien de la confidentialité de vos identifiants.</p>

      <h2>3. Matériel</h2>
      <p>L'utilisation de Smart Alarm requiert un équipement matériel spécifique (carte TIVA EK-TM4C123GXL, capteur LDR, buzzer, LED). L'Équipe ISEP ne saurait être tenue responsable de dommages causés par un montage électrique défectueux ou une mauvaise utilisation du matériel physique.</p>

      <h2>4. Données Personnelles</h2>
      <p>Nous collectons votre adresse e-mail pour le fonctionnement de votre compte. Les données de luminosité issues de vos capteurs sont anonymisées et conservées à des fins d'affichage sur votre tableau de bord.</p>

      <h2>5. Propriété Intellectuelle</h2>
      <p>L'application, l'interface graphique, ainsi que le code source associé au projet Smart Alarm sont la propriété intellectuelle de leurs auteurs (Équipe Projet ISEP).</p>

      <h2>6. Modifications</h2>
      <p>Nous nous réservons le droit de modifier les présentes CGU à tout moment. Vous serez informés des changements importants.</p>
    </div>
    
    <div style="text-align: center; margin-top: 2rem;">
      <p style="color: var(--text-muted); font-size: 0.85rem;">
        Smart Alarm © <?= date('Y') ?><br>
        <a href="<?= BASE_URL ?>cgu.php" style="color: inherit;">CGU</a> · 
        <a href="<?= BASE_URL ?>mentions-legales.php" style="color: inherit;">Mentions Légales</a>
      </p>
    </div>
  </div>

</div>
</body>
</html>
