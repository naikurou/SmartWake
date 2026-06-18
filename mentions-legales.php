<?php
require_once __DIR__ . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mentions Légales — Smart Alarm</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <script>
    const savedTheme = localStorage.getItem('smartwake-theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);
  </script>
</head>
<body style="min-height: 100vh; display: flex; flex-direction: column; background: var(--bg-main);">
  <header style="padding: 1rem 2rem; border-bottom: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center; background: var(--bg-card); backdrop-filter: blur(24px);">
    <a href="<?= BASE_URL ?>" style="text-decoration: none; color: var(--text-main); font-weight: 600; font-size: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
      <i class="ph-fill ph-sun-horizon" style="color: #00A4EF;"></i> Smart Alarm
    </a>
    <a href="javascript:history.back()" class="btn btn-outline" style="padding: 0.5rem 1rem;">Retour</a>
  </header>

  <main style="flex: 1; max-width: 800px; width: 90%; margin: 2rem auto; padding: 2rem; background: var(--bg-card); border-radius: var(--radius-lg); border: 1px solid var(--border-light); box-shadow: var(--shadow-md);">
    <h1 style="margin-bottom: 2rem; color: var(--text-main);">Mentions Légales</h1>
    
    <div style="color: var(--text-muted); line-height: 1.6;">
      <h2 style="color: var(--text-main); margin: 1.5rem 0 0.5rem; font-size: 1.25rem;">Éditeur du Service</h2>
      <p>Le site <strong>Smart Alarm</strong> (anciennement SmartWake) est édité par l'<strong>Équipe Smart Alarm</strong> dans le cadre d'un projet technique et pédagogique au sein de l'<strong>ISEP</strong> (Institut Supérieur d'Électronique de Paris).</p>

      <h2 style="color: var(--text-main); margin: 1.5rem 0 0.5rem; font-size: 1.25rem;">Hébergement</h2>
      <p>Le site est hébergé sur le serveur <strong>Hangar GarageISEP</strong>.<br>
      Adresse IP du serveur : <code>178.33.122.21</code></p>

      <h2 style="color: var(--text-main); margin: 1.5rem 0 0.5rem; font-size: 1.25rem;">Propriété Intellectuelle</h2>
      <p>Tous les éléments graphiques, la structure et le code source de l'application web Smart Alarm (HTML, CSS, PHP, JavaScript) ainsi que les scripts embarqués sur microcontrôleurs (PowerShell, langage C/Energia) ont été conçus exclusivement pour ce projet étudiant. Toute reproduction non autorisée en dehors du cadre académique est strictement interdite.</p>

      <h2 style="color: var(--text-main); margin: 1.5rem 0 0.5rem; font-size: 1.25rem;">Contact</h2>
      <p>Pour toute question liée au projet ou au fonctionnement du réveil connecté, vous pouvez contacter l'équipe projet via l'administration de l'ISEP ou par l'adresse mail de l'équipe.</p>
    </div>
  </main>
  
  <footer style="text-align: center; padding: 2rem; color: var(--text-muted); font-size: 0.9rem; border-top: 1px solid var(--border-light);">
    <p>Smart Alarm © <?= date('Y') ?></p>
  </footer>
</body>
</html>
