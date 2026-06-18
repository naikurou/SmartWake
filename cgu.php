<?php
require_once __DIR__ . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CGU — Smart Alarm</title>
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
    <h1 style="margin-bottom: 2rem; color: var(--text-main);">Conditions Générales d'Utilisation (CGU)</h1>
    
    <div style="color: var(--text-muted); line-height: 1.6;">
      <h2 style="color: var(--text-main); margin: 1.5rem 0 0.5rem; font-size: 1.25rem;">1. Objet du Projet</h2>
      <p>Les présentes CGU ont pour objet de définir les modalités de mise à disposition des services du site <strong>Smart Alarm</strong>, développé dans le cadre d'un projet étudiant à l'ISEP (Institut Supérieur d'Électronique de Paris).</p>

      <h2 style="color: var(--text-main); margin: 1.5rem 0 0.5rem; font-size: 1.25rem;">2. Accès aux Services</h2>
      <p>Le service permet à l'utilisateur de configurer et visualiser les données d'un réveil intelligent connecté via un microcontrôleur TIVA C. L'accès nécessite la création d'un compte utilisateur personnel.</p>

      <h2 style="color: var(--text-main); margin: 1.5rem 0 0.5rem; font-size: 1.25rem;">3. Données Personnelles</h2>
      <p>Smart Alarm collecte uniquement l'adresse e-mail et le nom d'utilisateur à des fins d'authentification. Les données d'éclairage (capteurs LDR) ne sont rattachées à aucune donnée permettant une identification physique. Conformément au RGPD, vous disposez d'un droit de modification et de suppression de vos données via votre espace personnel.</p>

      <h2 style="color: var(--text-main); margin: 1.5rem 0 0.5rem; font-size: 1.25rem;">4. Limites de Responsabilité</h2>
      <p>S'agissant d'un projet académique expérimental (POC), l'Équipe Smart Alarm décline toute responsabilité en cas de panne de l'alarme, de retard au travail, de non-déclenchement du buzzer ou de perte de données. Le matériel TIVA EK123GXL est utilisé à des fins strictement pédagogiques.</p>
    </div>
  </main>
  
  <footer style="text-align: center; padding: 2rem; color: var(--text-muted); font-size: 0.9rem; border-top: 1px solid var(--border-light);">
    <p>Smart Alarm © <?= date('Y') ?></p>
  </footer>
</body>
</html>
