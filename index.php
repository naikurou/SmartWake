<?php
/**
 * SmartWake - Page d'accueil
 * Redirige vers le dashboard si connecté, sinon vers login
 */
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: /smartwake/dashboard.php');
} else {
    header('Location: /smartwake/login.php');
}
exit;
