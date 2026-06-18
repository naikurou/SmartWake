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
    
    // 1. Allume le buzzer (ON)
    $stmtON = $pdo->prepare("INSERT INTO etats_actionneurs (composant, etat, declenche_par) VALUES ('buzzer', 1, 'groupe_ldr_test') ON DUPLICATE KEY UPDATE etat = 1, declenche_par = 'groupe_ldr_test', derniere_action = CURRENT_TIMESTAMP");
    $stmtON->execute();
    
    // 2. Attend 1 seconde pour que le buzzer retentisse brièvement
    sleep(1);
    
    // 3. Éteint le buzzer (OFF)
    $stmtOFF = $pdo->prepare("INSERT INTO etats_actionneurs (composant, etat, declenche_par) VALUES ('buzzer', 0, 'groupe_ldr_test') ON DUPLICATE KEY UPDATE etat = 0, declenche_par = 'groupe_ldr_test', derniere_action = CURRENT_TIMESTAMP");
    $stmtOFF->execute();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
