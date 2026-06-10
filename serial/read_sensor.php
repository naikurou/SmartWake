<?php
/**
 * SmartWake - Script de lecture du capteur via port série
 * Fichier : serial/read_sensor.php
 *
 * Objectif :
 *   1. Ouvrir le port série (COM) où est branchée la carte Tiva C
 *   2. Lire les valeurs de luminosité envoyées par le capteur LDR
 *   3. Déterminer le statut Jour/Nuit
 *   4. Enregistrer les données dans Azure Database for MySQL
 *
 * Usage (ligne de commande) :
 *   php serial/read_sensor.php
 *
 * Prérequis :
 *   - Extension PHP DIO (pecl install dio) sur Windows/Linux
 *   - OU mode de compatibilité via fopen() sur Windows
 *   - PHP CLI avec accès au port COM
 *   - La carte Tiva C envoie des données au format : "742\r\n"
 *
 * Tiva C envoie via UART :
 *   UARTprintf("%d\n", lightValue);   // Valeur brute ADC (0–4095) ou lux convertis
 */
// ============================================================
// CONFIGURATION — Modifier selon votre environnement
// ============================================================
define('SERIAL_PORT', '\\\\.\\COM7'); // Port série (Windows: COM22, Linux: /dev/ttyUSB0)
define('BAUD_RATE',      9600);      // Vitesse de communication
define('READ_INTERVAL',  1);         // Secondes entre chaque lecture (0 = lecture continue)
define('MAX_ITERATIONS', 0);         // 0 = infini, sinon nombre de mesures puis arrêt
define('ADC_MAX',        4095);      // Résolution ADC Tiva C (12 bits)
define('LUX_MAX',        1024);      // Valeur max lux affichée sur le site
define('USE_DIO',        false);     // true = PHP DIO, false = fopen() natif (Windows)
define('LOG_FILE',       __DIR__ . '/../logs/sensor.log');
// ============================================================
// Chargement des dépendances
// ============================================================
require_once __DIR__ . '/../includes/functions.php';
// ============================================================
// Utilitaires
// ============================================================
/**
 * Enregistre un message dans le log et l'affiche en console.
 */
function logMsg(string $level, string $msg): void {
    $line = '[' . date('Y-m-d H:i:s') . '] [' . strtoupper($level) . '] ' . $msg . PHP_EOL;
    echo $line;
    // Créer le dossier logs si besoin
    $logDir = dirname(LOG_FILE);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    file_put_contents(LOG_FILE, $line, FILE_APPEND | LOCK_EX);
}
/**
 * Valide qu'une ligne lue depuis le port série est une donnée numérique valide (entier ou flottant).
 *
 * @param string $line
 * @return int|null Valeur entière ou null si invalide
 */
function parseSerialLine(string $line): ?int {
    $line = trim($line);
    // Si c'est vide ou pas un nombre valide (ex: texte pur), on ignore
    if ($line === '' || !is_numeric($line)) {
        return null;
    }
    
    // Convertir en float d'abord (car la carte Tiva C envoie des floats)
    // puis arrondir à l'entier le plus proche pour les lux
    $val = (int) round((float) $line);
    
    // Ignorer les valeurs manifestement aberrantes (hors ADC/Lux réalistes)
    if ($val < 0 || $val > 65535) {
        return null;
    }
    return $val;
}
// ============================================================
// Ouverture du port série
// ============================================================
logMsg('info', '=== SmartWake Serial Reader ===');
logMsg('info', 'Port     : ' . SERIAL_PORT);
logMsg('info', 'Baudrate : ' . BAUD_RATE . ' baud');
logMsg('info', 'Mode     : ' . (USE_DIO ? 'PHP DIO' : 'fopen() natif'));
logMsg('info', 'Démarrage de la lecture...');
echo str_repeat('-', 50) . PHP_EOL;
// ---- Mode PHP DIO (extension pecl/dio) ----
if (USE_DIO) {
    if (!function_exists('dio_open')) {
        logMsg('error', 'L\'extension PHP DIO n\'est pas installée. Installez-la avec : pecl install dio');
        exit(1);
    }
    $fd = @dio_open(SERIAL_PORT, O_RDWR | O_NOCTTY | O_NONBLOCK);
    if ($fd === false) {
        logMsg('error', 'Impossible d\'ouvrir le port ' . SERIAL_PORT . ' en mode DIO.');
        exit(1);
    }
    // Configuration du port série
    dio_tcsetattr($fd, [
        'baud'   => BAUD_RATE,
        'bits'   => 8,
        'stop'   => 1,
        'parity' => 0,
    ]);
    logMsg('info', 'Port série ouvert (DIO). En attente de données...');
    $iteration = 0;
    $buffer    = '';
    while (MAX_ITERATIONS === 0 || $iteration < MAX_ITERATIONS) {
        $data = @dio_read($fd, 128);
        if ($data !== false && strlen($data) > 0) {
            $buffer .= $data;
            // Traiter chaque ligne complète (\n)
            while (($pos = strpos($buffer, "\n")) !== false) {
                $line   = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 1);
                $lux = parseSerialLine($line);
                if ($lux !== null) {
                    $status = getDayStatus($lux);
                    logMsg('info', "Lecture → lux=$lux | statut=$status");
                    if (insertLightMeasure($lux)) {
                        logMsg('ok', "Mesure enregistrée : {$lux} lux ({$status})");
                    } else {
                        logMsg('warn', 'Échec de l\'enregistrement en base de données.');
                    }
                    $iteration++;
                    if (MAX_ITERATIONS > 0 && $iteration >= MAX_ITERATIONS) break 2;
                } else {
                    logMsg('debug', 'Ligne ignorée (non numérique) : ' . json_encode($line));
                }
            }
        }
        if (READ_INTERVAL > 0) {
            sleep(READ_INTERVAL);
        } else {
            usleep(100000); // 100ms pour éviter de saturer le CPU
        }
    }
    dio_close($fd);
// ---- Mode fopen() natif (Windows, XAMPP) ----
} else {
    /*
     * Sur Windows, PHP peut ouvrir un port COM via fopen().
     * Configurer d'abord le port avec la commande MODE :
     *   MODE COM22: BAUD=9600 PARITY=N DATA=8 STOP=1
     * ou via le Gestionnaire de périphériques.
     */
    // Configurer le port automatiquement (Windows uniquement)
    if (PHP_OS_FAMILY === 'Windows') {
        // Nettoyer le préfixe \\.\ pour la commande MODE
        $modePort = str_replace('\\\\.\\', '', SERIAL_PORT);
        $modeCmd = sprintf(
            'MODE %s: BAUD=%d PARITY=N DATA=8 STOP=1 DTR=ON RTS=ON 2>&1',
            $modePort,
            BAUD_RATE
        );
        $modeOut = shell_exec($modeCmd);
        logMsg('info', 'Configuration port Windows : ' . trim($modeOut ?? 'OK'));
    }
    // Ouvrir le port
    $handle = @fopen(SERIAL_PORT, 'r+b');
    if ($handle === false) {
        logMsg('error', 'Impossible d\'ouvrir le port ' . SERIAL_PORT . '.');
        logMsg('error', 'Vérifiez : port disponible, XAMPP lancé en administrateur, pilote Tiva C installé.');
        exit(1);
    }
    // Mode non-bloquant pour éviter les deadlocks
    stream_set_blocking($handle, false);
    stream_set_timeout($handle, 3);
    logMsg('info', 'Port série ouvert (fopen). En attente de données...');
    $iteration = 0;
    $buffer    = '';
    $lastRead  = time();
    while (MAX_ITERATIONS === 0 || $iteration < MAX_ITERATIONS) {
        $chunk = fread($handle, 256);
        if ($chunk !== false && strlen($chunk) > 0) {
            $buffer .= $chunk;
            $lastRead = time();
            // Traiter chaque ligne complète
            while (($pos = strpos($buffer, "\n")) !== false) {
                $line   = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 1);
                $lux = parseSerialLine($line);
                if ($lux !== null) {
                    $status = getDayStatus($lux);
                    logMsg('info', "Lecture → lux=$lux | statut=$status");
                    if (insertLightMeasure($lux)) {
                        logMsg('ok', "✅ Enregistré : {$lux} lux ({$status})");
                    } else {
                        logMsg('warn', '⚠️  Échec enregistrement DB.');
                    }
                    $iteration++;
                    if (MAX_ITERATIONS > 0 && $iteration >= MAX_ITERATIONS) break 2;
                } else {
                    if (trim($line) !== '') {
                        logMsg('debug', 'Ligne ignorée : ' . json_encode(trim($line)));
                    }
                }
            }
        }
        // Watchdog : avertissement si aucune donnée depuis 30s
        if (time() - $lastRead > 30) {
            logMsg('warn', 'Aucune donnée reçue depuis 30 secondes. Vérifiez la connexion Tiva C.');
            $lastRead = time();
        }
        usleep(100000); // Pause 100ms
    }
    fclose($handle);
}
logMsg('info', 'Lecture terminée. ' . ($iteration ?? 0) . ' mesure(s) enregistrée(s).');
echo str_repeat('-', 50) . PHP_EOL;
/*
 * ============================================================
 * NOTES D'INTÉGRATION TIVA C
 * ============================================================
 *
 * Code C côté Tiva C (TM4C123GH6PM) :
 *
 * #include <stdint.h>
 * #include "inc/hw_memmap.h"
 * #include "driverlib/adc.h"
 * #include "driverlib/uart.h"
 * #include "utils/uartstdio.h"
 *
 * // Initialiser l'ADC sur AIN0 (PE3)
 * // Initialiser UART0 à 9600 baud
 *
 * while(1) {
 *     uint32_t adcValue;
 *     ADCProcessorTrigger(ADC0_BASE, 3);
 *     while(!ADCIntStatus(ADC0_BASE, 3, false));
 *     ADCSequenceDataGet(ADC0_BASE, 3, &adcValue);
 *     ADCIntClear(ADC0_BASE, 3);
 *
 *     UARTprintf("%d\n", adcValue);   // Envoie la valeur sur le port USB
 *     SysCtlDelay(SysCtlClockGet());  // Délai ~1 seconde
 * }
 *
 * ============================================================
 * TESTER SANS MATÉRIEL (simulation)
 * ============================================================
 * Modifier READ_SENSOR_SIMULATE = true dans les constantes
 * pour injecter des données aléatoires sans carte Tiva.
 * ============================================================
 */