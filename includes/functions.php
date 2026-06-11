<?php
/**
 * SmartWake - Fonctions utilitaires
 * Gestion des capteurs, formatage, échappement XSS
 */

require_once __DIR__ . '/db.php';

// ============================================================
// Seuils de luminosité (lux) — configurable
// ============================================================
define('LUX_NIGHT_FULL',   1);    // < 1 lux   : Nuit complète
define('LUX_NIGHT_DIM',   10);    // 1-10 lux  : Nuit avec faible éclairage
define('LUX_DAWN',        50);    // 10-50 lux : Aube naissante
define('LUX_MORNING',    200);    // 50-200 lux: Matin clair
define('LUX_DAY',        500);    // 200-500   : Plein jour
define('LUX_ALERT',      500);    // > 500 lux : Alerte lumière soudaine
// Compat ascendante
define('DAY_THRESHOLD',    200);
define('IDEAL_WAKE_THRESHOLD', 50);

// ============================================================
// Fonctions capteur
// ============================================================

/**
 * Détermine le niveau de luminosité en 6 paliers.
 *
 * @param int $lux Valeur en lux
 * @return string Identifiant du niveau
 */
function getLuxLevel(int $lux): string {
    if ($lux < LUX_NIGHT_FULL)  return 'NIGHT_FULL';   // < 1 lux
    if ($lux < LUX_NIGHT_DIM)   return 'NIGHT_DIM';    // 1-10 lux
    if ($lux < LUX_DAWN)        return 'DAWN';          // 10-50 lux
    if ($lux < LUX_MORNING)     return 'MORNING';       // 50-200 lux
    if ($lux < LUX_ALERT)       return 'DAY';           // 200-500 lux
    return 'ALERT';                                      // >= 500 lux
}

/**
 * Retourne les métadonnées d'un niveau de luminosité.
 *
 * @param string $level
 * @return array
 */
function getLuxLevelMeta(string $level): array {
    $meta = [
        'NIGHT_FULL' => ['label' => 'Nuit complète',            'icon' => '⬛', 'css' => 'level-night-full',  'range' => '< 1 lux'],
        'NIGHT_DIM'  => ['label' => 'Nuit — faible éclairage', 'icon' => '🌙', 'css' => 'level-night-dim',   'range' => '1–10 lux'],
        'DAWN'       => ['label' => 'Aube naissante',            'icon' => '🌅', 'css' => 'level-dawn',         'range' => '10–50 lux'],
        'MORNING'    => ['label' => 'Matin clair',               'icon' => '🌤️', 'css' => 'level-morning',      'range' => '50–200 lux'],
        'DAY'        => ['label' => 'Plein jour',                'icon' => '☀️', 'css' => 'level-day',          'range' => '200–500 lux'],
        'ALERT'      => ['label' => 'Lumière soudaine',          'icon' => '💡', 'css' => 'level-alert',        'range' => '> 500 lux'],
    ];
    return $meta[$level] ?? $meta['NIGHT_FULL'];
}

/**
 * Compatibilité ascendante : retourne 'DAY' ou 'NIGHT' (pour la BDD existante).
 *
 * @param int $lux
 * @return string
 */
function getDayStatus(int $lux): string {
    return $lux >= DAY_THRESHOLD ? 'DAY' : 'NIGHT';
}

/**
 * Enregistre une mesure de luminosité dans la base de données.
 *
 * @param int $lightValue Valeur en lux
 * @return bool Succès de l'insertion
 */
function insertLightMeasure(int $lightValue): bool {
    try {
        $db     = getDB();
        $status = getDayStatus($lightValue);
        $stmt   = $db->prepare(
            'INSERT INTO light_sensor_data (light_value, day_status) VALUES (:val, :status)'
        );
        return $stmt->execute([':val' => $lightValue, ':status' => $status]);
    } catch (Exception $e) {
        error_log('[SmartWake] Erreur insertLightMeasure : ' . $e->getMessage());
        return false;
    }
}

/**
 * Récupère la dernière mesure enregistrée.
 *
 * @return array|null Dernière mesure ou null
 */
function getLatestMeasure(): ?array {
    $db   = getDB();
    $stmt = $db->query(
        'SELECT light_value, day_status, created_at FROM light_sensor_data ORDER BY created_at DESC LIMIT 1'
    );
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Récupère les mesures des dernières 24 heures pour le graphique.
 *
 * @return array Tableau de mesures
 */
function getLast24HoursData(): array {
    $db   = getDB();
    $stmt = $db->query(
        "SELECT light_value, day_status, created_at
         FROM light_sensor_data
         WHERE created_at >= NOW() - INTERVAL 24 HOUR
         ORDER BY created_at ASC"
    );
    return $stmt->fetchAll();
}

/**
 * Récupère les 100 dernières mesures pour le graphique.
 *
 * @return array Tableau de mesures
 */
function getLast100Measures(): array {
    $db   = getDB();
    $stmt = $db->query(
        'SELECT light_value, day_status, created_at
         FROM light_sensor_data
         ORDER BY created_at DESC
         LIMIT 100'
    );
    $rows = $stmt->fetchAll();
    return array_reverse($rows); // Ordre chronologique pour Chart.js
}

/**
 * Récupère l'historique paginé des mesures.
 *
 * @param int $page   Numéro de page (1-indexé)
 * @param int $perPage Nombre d'éléments par page
 * @return array ['data' => [...], 'total' => int, 'pages' => int]
 */
function getHistoryPaginated(int $page = 1, int $perPage = 20): array {
    $db     = getDB();
    $offset = ($page - 1) * $perPage;

    // Compter le total
    $totalStmt = $db->query('SELECT COUNT(*) FROM light_sensor_data');
    $total     = (int) $totalStmt->fetchColumn();

    // Récupérer la page
    $stmt = $db->prepare(
        'SELECT light_value, day_status, created_at
         FROM light_sensor_data
         ORDER BY created_at DESC
         LIMIT :limit OFFSET :offset'
    );
    $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
    $stmt->execute();

    return [
        'data'  => $stmt->fetchAll(),
        'total' => $total,
        'pages' => (int) ceil($total / $perPage),
    ];
}

/**
 * Calcule les statistiques de la journée actuelle.
 *
 * @return array Statistiques (min, max, avg, count)
 */
function getTodayStats(): array {
    $db   = getDB();
    $stmt = $db->query(
        "SELECT
            MIN(light_value) AS min_lux,
            MAX(light_value) AS max_lux,
            ROUND(AVG(light_value)) AS avg_lux,
            COUNT(*) AS total_measures
         FROM light_sensor_data
         WHERE DATE(created_at) = CURDATE()"
    );
    return $stmt->fetch() ?: ['min_lux' => 0, 'max_lux' => 0, 'avg_lux' => 0, 'total_measures' => 0];
}

// ============================================================
// Fonctions utilitaires générales
// ============================================================

/**
 * Échappe une chaîne pour l'affichage HTML (protection XSS).
 *
 * @param mixed $value
 * @return string
 */
function e($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Formate une date MySQL en format lisible français.
 *
 * @param string $datetime
 * @return string
 */
function formatDate(string $datetime): string {
    $ts = strtotime($datetime);
    return $ts ? date('d/m/Y à H:i:s', $ts) : $datetime;
}

/**
 * Retourne un badge HTML pour le niveau de luminosité.
 *
 * @param string $status 'DAY' ou 'NIGHT' (champ BDD)
 * @param int    $lux    Valeur en lux pour obtenir le vrai niveau
 * @return string HTML sécurisé
 */
function statusBadge(string $status, int $lux = -1): string {
    if ($lux >= 0) {
        $level = getLuxLevel($lux);
        $meta  = getLuxLevelMeta($level);
        return '<span class="badge badge-level ' . $meta['css'] . '" aria-label="' . $meta['label'] . '">'
            . $meta['icon'] . ' ' . $meta['label']
            . '</span>';
    }
    // Fallback binaire
    if ($status === 'DAY') {
        return '<span class="badge badge-day" aria-label="Jour">☀️ Jour</span>';
    }
    return '<span class="badge badge-night" aria-label="Nuit">🌙 Nuit</span>';
}

/**
 * Retourne la recommandation de réveil basée sur les 6 niveaux de luminosité.
 *
 * @param int    $lux
 * @param string $status (inutilisé, gardé pour compatibilité)
 * @return array
 */
function getWakeRecommendation(int $lux, string $status = ''): array {
    $level = getLuxLevel($lux);
    switch ($level) {
        case 'NIGHT_FULL':
            return [
                'optimal' => false,
                'level'   => $level,
                'message' => 'Obscurité totale — Mode veille',
                'detail'  => "Il fait nuit noire ({$lux} lux). Le réveil est en mode veille, luminosité minimale.",
                'action'  => 'sleep',
            ];
        case 'NIGHT_DIM':
            return [
                'optimal' => false,
                'level'   => $level,
                'message' => 'Veilleuse détectée — Simulation d\'aube',
                'detail'  => "Faible éclairage ({$lux} lux). Simulation d'aube douce en cours.",
                'action'  => 'simulate_dawn',
            ];
        case 'DAWN':
            return [
                'optimal' => true,
                'level'   => $level,
                'message' => 'Aube naissante — Alarme douce',
                'detail'  => "Lumière du matin ({$lux} lux). C'est le moment idéal pour une alarme douce !",
                'action'  => 'soft_alarm',
            ];
        case 'MORNING':
            return [
                'optimal' => true,
                'level'   => $level,
                'message' => 'Matin clair — Alarme principale',
                'detail'  => "Pièce bien éclairée ({$lux} lux). L'alarme principale se déclenche.",
                'action'  => 'main_alarm',
            ];
        case 'DAY':
            return [
                'optimal' => true,
                'level'   => $level,
                'message' => 'Plein jour — Dashboard adapté',
                'detail'  => "Lumière naturelle franche ({$lux} lux). L'écran du dashboard réduit sa luminosité.",
                'action'  => 'day_mode',
            ];
        case 'ALERT':
        default:
            return [
                'optimal' => false,
                'level'   => $level,
                'message' => '⚠️ Lumière soudaine détectée !',
                'detail'  => "Changement brusque ({$lux} lux). Quelqu'un a allumé la lumière dans la pièce.",
                'action'  => 'alert',
            ];
    }
}
