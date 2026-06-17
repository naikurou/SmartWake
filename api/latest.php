<?php
/**
 * SmartWake - API REST : dernière mesure
 * Endpoint : GET /smartwake/api/latest.php
 *
 * Retour JSON :
 * {
 *   "light_value": 742,
 *   "status": "DAY",
 *   "timestamp": "2026-06-08 14:22:00",
 *   "wake_recommendation": { "optimal": true, "message": "...", "detail": "..." }
 * }
 */

// En-têtes CORS et JSON
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Seules les requêtes GET sont autorisées
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée. Utilisez GET.']);
    exit;
}

require_once __DIR__ . '/../includes/functions.php';

try {
    $latest = getLatestMeasure();

    if (!$latest) {
        http_response_code(404);
        echo json_encode([
            'error'     => 'Aucune mesure disponible.',
            'light_value' => null,
            'status'    => null,
            'timestamp' => null,
        ]);
        exit;
    }

    $lux    = (int)$latest['light_value'];
    $status = $latest['day_status'];
    $level  = getLuxLevel($lux);
    $meta   = getLuxLevelMeta($level);
    $wake   = getWakeRecommendation($lux, $status);

    http_response_code(200);
    echo json_encode([
        'light_value'         => $lux,
        'status'              => $status,
        'lux_level'           => $level,
        'lux_label'           => $meta['label'],
        'lux_icon'            => $meta['icon'],
        'lux_range'           => $meta['range'],
        'timestamp'           => $latest['created_at'],
        'wake_recommendation' => $wake,
        'thresholds' => [
            'night_full' => LUX_NIGHT_FULL,
            'night_dim'  => LUX_NIGHT_DIM,
            'dawn'       => LUX_DAWN,
            'morning'    => LUX_MORNING,
            'day'        => LUX_DAY,
            'alert'      => LUX_ALERT,
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    error_log('[SmartWake API] ' . $e->getMessage());
    echo json_encode(['error' => 'Erreur interne du serveur.']);
}
