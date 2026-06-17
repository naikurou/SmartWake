<?php
/**
 * SmartWake - Page d'accueil
 * Redirige vers le dashboard si connecté, sinon vers login
 */
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'dashboard.php');
} else {
    header('Location: ' . BASE_URL . 'login.php');
}
exit;
