<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$etat = isset($input['etat']) ? (int)$input['etat'] : 1;

try {
    $pdo = getDB();
    // Insère ou met à jour la table etats_actionneurs pour forcer le buzzer à l'état demandé
    $stmt = $pdo->prepare("INSERT INTO etats_actionneurs (composant, etat, declenche_par) VALUES ('buzzer', ?, 'groupe_ldr_test') ON DUPLICATE KEY UPDATE etat = VALUES(etat), declenche_par = 'groupe_ldr_test', derniere_action = CURRENT_TIMESTAMP");
    $stmt->execute([$etat]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
