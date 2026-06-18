<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mentions Légales — Smart Alarm</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <script>
    const savedTheme = localStorage.getItem('smartwake-theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);
  </script>
</head>
<body class="dashboard-body">
  <nav class="dash-nav">
    <div class="container dash-nav-inner">
      <div class="dash-brand">
        <a href="<?= BASE_URL ?>" class="brand-link">
          <i class="ph-fill ph-sun-horizon" style="color: #00A4EF; font-size: 1.5rem;"></i>
          Smart<span class="brand-accent"> Alarm</span>
        </a>
      </div>
      <ul class="dash-nav-links">
        <li>
          <a href="javascript:history.back()" class="btn btn-outline btn-sm">Retour</a>
        </li>
      </ul>
    </div>
  </nav>

  <main class="container" style="max-width: 800px; margin-top: 3rem; margin-bottom: 3rem; margin-left: auto; margin-right: auto;">
    <div class="card" style="padding: 2.5rem;">
      <h1 class="dash-title" style="margin-bottom: 2rem; font-size: 2.25rem; word-wrap: break-word;">Mentions Légales</h1>
      
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
    </div>
  </main>
  
  <footer class="container" style="text-align: center; margin-top: 2rem; padding-bottom: 2rem; color: var(--text-muted); font-size: 0.9rem;">
    <p>Smart Alarm © 2026</p>
  </footer>
</body>
</html>
