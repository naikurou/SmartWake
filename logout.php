<?php
/**
 * SmartWake - Déconnexion
 * Détruit la session et redirige vers la page de connexion
 */
require_once __DIR__ . '/includes/auth.php';

// Vérification CSRF pour la déconnexion (GET token dans l'URL)
$token = $_GET['token'] ?? '';
if (isLoggedIn() && verifyCsrfToken($token)) {
    logoutUser();
}

header('Location: /smartwake/login.php');
exit;
