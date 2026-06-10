# 🌅 SmartWake — Réveil Intelligent Adapté à l'Environnement

> **Projet ISEP** — Système IoT de surveillance de luminosité via capteur Tiva C, avec stockage Azure MySQL et interface web temps réel.

---

## 📋 Table des matières

1. [Présentation](#présentation)
2. [Architecture](#architecture)
3. [Prérequis](#prérequis)
4. [Installation XAMPP (local)](#installation-xampp)
5. [Configuration Azure Database](#configuration-azure)
6. [Démarrage du serveur série](#démarrage-série)
7. [Structure du projet](#structure)
8. [API JSON](#api)
9. [Sécurité](#sécurité)
10. [Éco-conception](#éco-conception)

---

## Présentation

**SmartWake** est un système connecté qui :

- Lit en temps réel la **luminosité ambiante** via un capteur LDR branché sur une **carte Tiva C (TM4C123GH6PM)**
- Communique les mesures via **port série USB (COM)**
- Stocke les données dans **Azure Database for MySQL**
- Affiche un **tableau de bord web** avec graphiques, historique et recommandation de réveil
- Détermine automatiquement si c'est le **JOUR** (> 500 lux) ou la **NUIT** (≤ 500 lux)

| Valeur lumière | État |
|----------------|------|
| > 700 lux      | ☀️ Réveil idéal |
| 501–700 lux    | ☀️ Jour |
| ≤ 500 lux      | 🌙 Nuit |

---

## Architecture

```
Tiva C (TM4C123GH6PM)
    │  LDR → ADC 12 bits
    │  UART0 → USB → COM22
    ↓
read_sensor.php  (PHP CLI)
    │  fopen('COM22') ou PHP DIO
    │  Parsing valeur brute → lux
    ↓
Azure Database for MySQL
    │  Table light_sensor_data
    ↓
Site Web PHP
    │  dashboard.php  (temps réel, polling 5s)
    │  history.php    (historique paginé)
    └  api/latest.php (JSON REST)
```

---

## Prérequis

| Composant | Version minimale |
|-----------|-----------------|
| PHP | 8.1+ |
| Apache | 2.4+ avec mod_rewrite |
| MySQL | 8.0+ (Azure Database for MySQL Flexible Server) |
| XAMPP | 8.2+ (développement local) |
| PHP Extension | PDO, PDO_MySQL |
| PHP Extension (optionnel) | DIO (pour le mode série avancé) |

---

## Installation XAMPP

### 1. Copier le projet

```bash
# Placer le dossier dans le répertoire htdocs de XAMPP
C:\xampp\htdocs\smartwake\
```

### 2. Créer la base de données

```sql
CREATE DATABASE smartwake CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Puis importer le script SQL :

```bash
mysql -u root -p smartwake < database.sql
```

### 3. Configurer la connexion

Modifier `includes/db.php` :

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'smartwake');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 4. Activer mod_rewrite

Dans `C:\xampp\apache\conf\httpd.conf` :

```apache
LoadModule rewrite_module modules/mod_rewrite.so
```

Et dans le bloc `<Directory "C:/xampp/htdocs">` :

```apache
AllowOverride All
```

### 5. Accéder au site

```
http://localhost/smartwake/
```

---

## Configuration Azure

### Créer un serveur Flexible MySQL

1. Portail Azure → **Azure Database for MySQL Flexible Server**
2. Créer un serveur (ex : `smartwake-mysql.mysql.database.azure.com`)
3. Autoriser votre IP dans **Networking → Add current client IP**
4. Créer la base : `smartwake`

### Configurer `includes/db.php`

```php
define('DB_HOST', 'smartwake-mysql.mysql.database.azure.com');
define('DB_PORT', '3306');
define('DB_NAME', 'smartwake');
define('DB_USER', 'adminuser@smartwake-mysql');
define('DB_PASS', 'VotreMotDePasse!');
```

### Télécharger le certificat SSL Azure

```bash
# Télécharger le certificat TLS Azure
curl -o certs/DigiCertGlobalRootG2.crt.pem \
  https://dl.cacerts.digicert.com/DigiCertGlobalRootG2.crt.pem
```

---

## Démarrage série

### Avec matériel Tiva C

```bash
# 1. Brancher la carte Tiva C via USB
# 2. Vérifier le port COM dans le Gestionnaire de périphériques

# 3. Configurer le port dans serial/read_sensor.php
define('SERIAL_PORT', 'COM22');   # Adapter selon votre port
define('BAUD_RATE',   9600);

# 4. Lancer le script en CLI (PowerShell ou CMD en administrateur)
php serial\read_sensor.php
```

### Sans matériel (simulation)

```bash
# Injecter des données simulées (cycle jour/nuit réaliste)
php serial\simulate.php
```

**Sortie attendue :**

```
[14:22:01] [INFO]  === SmartWake Serial Reader ===
[14:22:01] [INFO]  Port     : COM22
[14:22:01] [INFO]  Baudrate : 9600 baud
[14:22:02] [INFO]  Lecture → brut=742 | lux=742 | statut=DAY
[14:22:02] [OK]    ✅ Enregistré : 742 lux (DAY)
```

---

## Structure

```
smartwake/
├── index.php               # Redirection (login/dashboard)
├── login.php               # Page de connexion
├── register.php            # Page d'inscription
├── logout.php              # Déconnexion sécurisée
├── dashboard.php           # Tableau de bord temps réel
├── history.php             # Historique paginé des mesures
├── database.sql            # Script SQL complet
├── .htaccess               # Sécurité Apache + CSP
│
├── api/
│   └── latest.php          # API REST JSON
│
├── serial/
│   ├── read_sensor.php     # Lecture port série Tiva C
│   ├── simulate.php        # Simulateur (sans matériel)
│   └── .htaccess           # Blocage accès HTTP
│
├── includes/
│   ├── db.php              # Connexion PDO Azure MySQL
│   ├── auth.php            # Authentification + CSRF + sessions
│   ├── functions.php       # Fonctions capteur + utilitaires
│   └── .htaccess           # Blocage accès HTTP
│
├── assets/
│   ├── css/
│   │   └── style.css       # Design futuriste complet
│   └── js/
│       └── app.js          # Polling temps réel + Chart.js
│
└── logs/
    ├── .htaccess           # Blocage accès HTTP
    └── sensor.log          # Log généré automatiquement
```

---

## API

### `GET /smartwake/api/latest.php`

Retourne la dernière mesure au format JSON :

```json
{
  "light_value": 742,
  "status": "DAY",
  "timestamp": "2026-06-08 14:22:00",
  "wake_recommendation": {
    "optimal": true,
    "message": "Conditions idéales pour le réveil détectées",
    "detail": "Luminosité de 742 lux — Lumière naturelle suffisante."
  },
  "thresholds": {
    "day_night": 500,
    "ideal_wake": 700
  }
}
```

**Codes HTTP :**

| Code | Signification |
|------|---------------|
| 200 | Succès |
| 404 | Aucune mesure en base |
| 405 | Méthode non autorisée |
| 500 | Erreur serveur |

---

## Sécurité

| Mesure | Implémentation |
|--------|---------------|
| Authentification | `password_hash()` + `password_verify()` (bcrypt cost 12) |
| Sessions | `session_regenerate_id()` + cookies `HttpOnly` + `SameSite=Strict` |
| CSRF | Token `bin2hex(random_bytes(32))` sur tous les formulaires |
| XSS | `htmlspecialchars()` sur toutes les sorties |
| Injection SQL | PDO + requêtes préparées exclusivement |
| Headers HTTP | `X-Frame-Options`, `CSP`, `X-Content-Type-Options` |
| Accès fichiers | `.htaccess` sur `includes/`, `serial/`, `logs/` |
| SSL Azure | Certificat DigiCert + `PDO::MYSQL_ATTR_SSL_CA` |
| Brute-force | `usleep(random_int(...))` sur échec de connexion |

---

## Éco-conception

- **Pas de framework JS lourd** : Chart.js uniquement (CDN avec SRI hash)
- **Polices Google** : Inter + Orbitron chargées avec `display=swap`
- **Compression Gzip** : activée via `.htaccess` (mod_deflate)
- **Cache assets** : CSS/JS mis en cache 24h côté navigateur
- **Polling ciblé** : uniquement l'endpoint `/api/latest.php` (réponse JSON légère)
- **Pas d'images** : interface 100% CSS/emoji, aucune image externe
- **PDO singleton** : une seule connexion DB par requête

---

## Code Tiva C (référence)

```c
// main.c — Tiva C TM4C123GH6PM
// Lecture LDR sur PE3 (AIN0) → UART0 USB → COM

#include <stdint.h>
#include "inc/hw_memmap.h"
#include "driverlib/adc.h"
#include "driverlib/uart.h"
#include "driverlib/sysctl.h"
#include "utils/uartstdio.h"

int main(void) {
    // 80 MHz
    SysCtlClockSet(SYSCTL_SYSDIV_2_5 | SYSCTL_USE_PLL |
                   SYSCTL_OSC_MAIN   | SYSCTL_XTAL_16MHZ);

    // Init UART0 (USB) à 9600 baud
    // Init ADC0 séquenceur 3 sur AIN0 (PE3)
    // ... (init code complet dans le rapport)

    while (1) {
        uint32_t adcValue[1];
        ADCProcessorTrigger(ADC0_BASE, 3);
        while (!ADCIntStatus(ADC0_BASE, 3, false));
        ADCSequenceDataGet(ADC0_BASE, 3, adcValue);
        ADCIntClear(ADC0_BASE, 3);

        // Envoyer la valeur brute (0–4095) via UART → USB
        UARTprintf("%d\n", adcValue[0]);

        SysCtlDelay(SysCtlClockGet() / 3); // ~1 seconde
    }
}
```

---

## Auteurs

Projet ISEP — Département Électronique & Informatique  
Module : Systèmes embarqués connectés  
Année : 2025-2026

---

*SmartWake — « La lumière comme réveil naturel »* 🌅
