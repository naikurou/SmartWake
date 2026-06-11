<?php
/**
 * SmartWake - Connexion à la base de données
 * Utilise PDO avec des options sécurisées pour Azure Database for MySQL
 */

// ============================================================
// Configuration Azure Database for MySQL
// Modifier ces valeurs selon votre environnement
// ============================================================
define('DB_HOST',    getenv('DB_HOST')     ?: '178.33.122.21');
define('DB_PORT',    getenv('DB_PORT')     ?: '3306');
define('DB_NAME',    getenv('DB_NAME')     ?: 'hangardb_axst62997');
define('DB_USER',    getenv('DB_USER')     ?: 'axst62997');
define('DB_PASS',    getenv('DB_PASS')     ?: 'vN98OBrkug96JSeUmiFxuZGp');
define('DB_CHARSET', 'utf8mb4');

/**
 * Retourne une instance PDO singleton vers la base de données.
 * 
 * @return PDO Instance PDO configurée et sécurisée
 * @throws RuntimeException Si la connexion échoue
 */
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            // SSL pour Azure Database for MySQL (TLS obligatoire)
            PDO::MYSQL_ATTR_SSL_CA       => __DIR__ . '/../certs/DigiCertGlobalRootG2.crt.pem',
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ];

        // Si le certificat SSL n'existe pas, connexion sans SSL (dev local)
        if (!file_exists(__DIR__ . '/../certs/DigiCertGlobalRootG2.crt.pem')) {
            unset($options[PDO::MYSQL_ATTR_SSL_CA]);
            unset($options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT]);
        }

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Ne jamais exposer les détails de connexion en production
            error_log('[SmartWake] Erreur DB : ' . $e->getMessage());
            throw new RuntimeException('Impossible de se connecter à la base de données.');
        }
    }

    return $pdo;
}
