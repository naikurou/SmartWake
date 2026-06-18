<?php
/**
 * API pour la synchronisation matérielle (PowerShell <-> Serveur Web)
 * Reçoit les données du capteur et l'état du buzzer, et renvoie les paramètres de l'alarme.
 */
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

// Vérifier que la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée. Utilisez POST.']);
    exit;
}

// Récupérer les données envoyées par PowerShell
$lux = isset($_POST['lux']) ? (int)$_POST['lux'] : null;
$status = isset($_POST['status']) ? $_POST['status'] : null;
$buzzer = isset($_POST['buzzer']) ? (int)$_POST['buzzer'] : 0;

if ($lux === null || $status === null) {
    echo json_encode(['success' => false, 'error' => 'Paramètres lux et status manquants.']);
    exit;
}

try {
    $pdo = getDB();
    
    // 1. Enregistrer la mesure de luminosité
    $stmtLux = $pdo->prepare("INSERT INTO light_sensor_data (light_value, day_status) VALUES (?, ?)");
    $stmtLux->execute([$lux, $status]);
    
    // 2. Mettre à jour l'état du buzzer
    $stmtBuzzer = $pdo->prepare("INSERT INTO etats_actionneurs (composant, etat, declenche_par) VALUES ('buzzer', ?, 'groupe_ldr') ON DUPLICATE KEY UPDATE etat=?, declenche_par='groupe_ldr'");
    $stmtBuzzer->execute([$buzzer, $buzzer]);
    
    // 3. Récupérer les paramètres actuels de l'alarme pour les renvoyer au script
    $stmtSettings = $pdo->query("SELECT is_active, night_lux_threshold, day_lux_threshold, duration_minutes FROM alarm_settings WHERE id = 1");
    $settings = $stmtSettings->fetch();
    
    if (!$settings) {
        // Au cas où la table est vide, on renvoie des valeurs par défaut
        $settings = [
            'is_active' => 0,
            'night_lux_threshold' => 50,
            'day_lux_threshold' => 500,
            'duration_minutes' => 5
        ];
    }
    
    echo json_encode([
        'success' => true,
        'settings' => [
            'is_active' => (int)$settings['is_active'],
            'night_lux' => (int)$settings['night_lux_threshold'],
            'day_lux' => (int)$settings['day_lux_threshold'],
            'duration' => (int)$settings['duration_minutes']
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
