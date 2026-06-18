<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Données invalides']);
    exit;
}

$is_active = isset($input['is_active']) ? (int)$input['is_active'] : 1;
$night_lux = isset($input['night_lux']) ? (int)$input['night_lux'] : 50;
$day_lux   = isset($input['day_lux']) ? (int)$input['day_lux'] : 500;
$duration  = isset($input['duration'])  ? (int)$input['duration']  : 5;

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE alarm_settings SET is_active = ?, night_lux_threshold = ?, day_lux_threshold = ?, duration_minutes = ? WHERE id = 1");
    $stmt->execute([$is_active, $night_lux, $day_lux, $duration]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
