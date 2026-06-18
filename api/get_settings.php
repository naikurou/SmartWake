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
    // On s'assure qu'une ligne existe au moins
    $pdo->exec("INSERT IGNORE INTO alarm_settings (id, is_active, night_lux_threshold, day_lux_threshold) VALUES (1, 1, 50, 500)");
    
    $stmt = $pdo->query("SELECT is_active, night_lux_threshold as night_lux, day_lux_threshold as day_lux FROM alarm_settings WHERE id = 1");
    $settings = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'is_active' => (bool)$settings['is_active'],
        'night_lux' => (int)$settings['night_lux'],
        'day_lux' => (int)$settings['day_lux']
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
