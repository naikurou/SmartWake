<?php
/**
 * SmartWake - Simulateur de capteur (sans matériel)
 * Fichier : serial/simulate.php
 *
 * Usage (CLI) : php serial/simulate.php
 *
 * Génère des valeurs de luminosité réalistes simulant
 * un cycle jour/nuit sur 24 heures, à des fins de test
 * sans avoir la carte Tiva C connectée.
 */

require_once __DIR__ . '/../includes/functions.php';

define('SIM_INTERVAL',   2);     // Secondes entre chaque mesure simulée
define('SIM_ITERATIONS', 50);    // Nombre de mesures à générer (0 = infini)
define('SIM_NOISE',      30);    // Amplitude du bruit aléatoire (lux)

function logSim(string $level, string $msg): void {
    echo '[' . date('H:i:s') . '] [' . strtoupper($level) . '] ' . $msg . PHP_EOL;
}

/**
 * Calcule une luminosité simulée selon l'heure du jour.
 * Suit une courbe sinusoïdale : 0 lux la nuit, ~900 lux à midi.
 *
 * @param int $noise Amplitude du bruit aléatoire
 * @return int Valeur en lux
 */
function simulateLux(int $noise = 30): int {
    $hour    = (float) date('G') + (float) date('i') / 60.0;
    // Courbe sinusoïdale : lever 6h, coucher 21h, pic à 13h30
    $sunrise = 6.0;
    $sunset  = 21.0;
    $peak    = ($sunrise + $sunset) / 2;

    if ($hour < $sunrise || $hour > $sunset) {
        $base = random_int(50, 150); // Luminosité nocturne (lueur, lampadaires)
    } else {
        $progress = ($hour - $sunrise) / ($sunset - $sunrise);
        $sine     = sin(M_PI * $progress); // 0 → 1 → 0
        $base     = (int) round(100 + $sine * 850);
    }

    // Ajouter un bruit gaussien simulé
    $noiseVal = random_int(-$noise, $noise);
    return max(0, min(1024, $base + $noiseVal));
}

logSim('info', '=== SmartWake Simulateur de capteur ===');
logSim('info', 'Intervalle : ' . SIM_INTERVAL . 's | Itérations : ' . (SIM_ITERATIONS ?: '∞'));
echo str_repeat('-', 50) . PHP_EOL;

$count = 0;
while (SIM_ITERATIONS === 0 || $count < SIM_ITERATIONS) {
    $lux    = simulateLux(SIM_NOISE);
    $status = getDayStatus($lux);

    if (insertLightMeasure($lux)) {
        $icon = $status === 'DAY' ? '☀️' : '🌙';
        logSim('ok', "{$icon} Mesure #{$count} → {$lux} lux → {$status}");
    } else {
        logSim('error', "Échec insertion mesure #{$count} ({$lux} lux)");
    }

    $count++;
    if (SIM_ITERATIONS > 0 && $count >= SIM_ITERATIONS) break;
    sleep(SIM_INTERVAL);
}

echo str_repeat('-', 50) . PHP_EOL;
logSim('info', "Simulation terminée. {$count} mesure(s) insérée(s).");
