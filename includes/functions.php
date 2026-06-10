<?php
/**
 * SmartWake - Fonctions utilitaires
 * Gestion des capteurs, formatage, échappement XSS
 */

require_once __DIR__ . '/db.php';

// ============================================================
// Constante du seuil Jour/Nuit
// ============================================================
define('DAY_THRESHOLD', 500);
define('IDEAL_WAKE_THRESHOLD', 700);

// ============================================================
// Fonctions capteur
// ============================================================

/**
 * Détermine le statut Jour/Nuit à partir d'une valeur de luminosité.
 *
 * @param int $lightValue Valeur en lux
 * @return string 'DAY' ou 'NIGHT'
 */
function getDayStatus(int $lightValue): string {
    return $lightValue > DAY_THRESHOLD ? 'DAY' : 'NIGHT';
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
 * Retourne un badge HTML pour le statut Jour/Nuit.
 *
 * @param string $status 'DAY' ou 'NIGHT'
 * @return string HTML sécurisé
 */
function statusBadge(string $status): string {
    if ($status === 'DAY') {
        return '<span class="badge badge-day" aria-label="Jour">☀️ Jour</span>';
    }
    return '<span class="badge badge-night" aria-label="Nuit">🌙 Nuit</span>';
}

/**
 * Retourne la recommandation de réveil basée sur la luminosité et le statut.
 *
 * @param int    $lightValue
 * @param string $status
 * @return array ['optimal' => bool, 'message' => string]
 */
function getWakeRecommendation(int $lightValue, string $status): array {
    if ($status === 'DAY' && $lightValue > IDEAL_WAKE_THRESHOLD) {
        return [
            'optimal' => true,
            'message' => 'Conditions idéales pour le réveil détectées',
            'detail'  => "Luminosité de {$lightValue} lux — Lumière naturelle suffisante pour un réveil en douceur.",
        ];
    }
    return [
        'optimal' => false,
        'message' => 'Conditions de réveil non optimales',
        'detail'  => $status === 'NIGHT'
            ? "Il fait encore nuit ({$lightValue} lux). Continuez à dormir !"
            : "La luminosité ({$lightValue} lux) n'est pas encore suffisante pour un réveil idéal.",
    ];
}
