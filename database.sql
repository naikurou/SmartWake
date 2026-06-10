-- ============================================================
-- SmartWake - Réveil Intelligent Adapté à l'Environnement
-- Script SQL de création de la base de données
-- Compatible Azure Database for MySQL
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Création de la base de données (décommenter si nécessaire)
-- CREATE DATABASE IF NOT EXISTS `smartwake` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `smartwake`;

-- ============================================================
-- Table : users
-- Stocke les comptes utilisateurs du système
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `username`      VARCHAR(100)  NOT NULL,
  `email`         VARCHAR(255)  NOT NULL UNIQUE,
  `password_hash` VARCHAR(255)  NOT NULL,
  `created_at`    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table : light_sensor_data
-- Stocke les mesures envoyées par le capteur Tiva C
-- ============================================================
CREATE TABLE IF NOT EXISTS `light_sensor_data` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `light_value` INT          NOT NULL,
  `day_status`  VARCHAR(20)  NOT NULL DEFAULT 'UNKNOWN',
  `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Index pour accélérer les requêtes temporelles
-- ============================================================
CREATE INDEX idx_created_at ON `light_sensor_data` (`created_at` DESC);

-- ============================================================
-- Données de démonstration (optionnelles)
-- ============================================================
INSERT INTO `light_sensor_data` (`light_value`, `day_status`, `created_at`) VALUES
  (120, 'NIGHT', NOW() - INTERVAL 23 HOUR),
  (95,  'NIGHT', NOW() - INTERVAL 22 HOUR),
  (180, 'NIGHT', NOW() - INTERVAL 21 HOUR),
  (310, 'NIGHT', NOW() - INTERVAL 20 HOUR),
  (450, 'NIGHT', NOW() - INTERVAL 19 HOUR),
  (530, 'DAY',   NOW() - INTERVAL 18 HOUR),
  (620, 'DAY',   NOW() - INTERVAL 17 HOUR),
  (710, 'DAY',   NOW() - INTERVAL 16 HOUR),
  (780, 'DAY',   NOW() - INTERVAL 15 HOUR),
  (820, 'DAY',   NOW() - INTERVAL 14 HOUR),
  (890, 'DAY',   NOW() - INTERVAL 13 HOUR),
  (910, 'DAY',   NOW() - INTERVAL 12 HOUR),
  (870, 'DAY',   NOW() - INTERVAL 11 HOUR),
  (800, 'DAY',   NOW() - INTERVAL 10 HOUR),
  (750, 'DAY',   NOW() - INTERVAL 9  HOUR),
  (680, 'DAY',   NOW() - INTERVAL 8  HOUR),
  (590, 'DAY',   NOW() - INTERVAL 7  HOUR),
  (510, 'DAY',   NOW() - INTERVAL 6  HOUR),
  (440, 'NIGHT', NOW() - INTERVAL 5  HOUR),
  (360, 'NIGHT', NOW() - INTERVAL 4  HOUR),
  (280, 'NIGHT', NOW() - INTERVAL 3  HOUR),
  (200, 'NIGHT', NOW() - INTERVAL 2  HOUR),
  (150, 'NIGHT', NOW() - INTERVAL 1  HOUR),
  (742, 'DAY',   NOW());
