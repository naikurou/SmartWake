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
    // Migration automatique pour ajouter la durée si elle n'existe pas
    try {
        $pdo->exec("ALTER TABLE alarm_settings ADD COLUMN duration_minutes INT NOT NULL DEFAULT 5");
    } catch(Exception $e) {
        // Ignore si la colonne existe déjà
    }

    // On s'assure qu'une ligne existe au moins
    $pdo->exec("INSERT IGNORE INTO alarm_settings (id, is_active, night_lux_threshold, day_lux_threshold, duration_minutes) VALUES (1, 1, 50, 500, 5)");
    
    $stmt = $pdo->query("SELECT is_active, night_lux_threshold as night_lux, day_lux_threshold as day_lux, duration_minutes as duration FROM alarm_settings WHERE id = 1");
    $settings = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'is_active' => (bool)$settings['is_active'],
        'night_lux' => (int)$settings['night_lux'],
        'day_lux' => (int)$settings['day_lux'],
        'duration' => (int)$settings['duration']
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
