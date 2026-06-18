<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

try {
    $pdo = getDB();
    // Insère ou met à jour la table etats_actionneurs pour forcer le buzzer à 1
    $stmt = $pdo->prepare("INSERT INTO etats_actionneurs (composant, etat, declenche_par) VALUES ('buzzer', 1, 'groupe_ldr_test') ON DUPLICATE KEY UPDATE etat = 1, declenche_par = 'groupe_ldr_test', derniere_action = CURRENT_TIMESTAMP");
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
