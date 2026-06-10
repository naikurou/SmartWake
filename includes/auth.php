<?php
/**
 * SmartWake - Gestion de l'authentification
 * Sessions sécurisées, protection CSRF, hash de mots de passe
 */

require_once __DIR__ . '/db.php';

// ============================================================
// Configuration des sessions sécurisées
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();

    // Régénérer l'ID de session pour prévenir la fixation
    if (empty($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
    }
}

// ============================================================
// Fonctions d'authentification
// ============================================================

/**
 * Vérifie si l'utilisateur est connecté.
 * 
 * @return bool
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Redirige vers la page de connexion si non authentifié.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /smartwake/login.php');
        exit;
    }
}

/**
 * Inscrit un nouvel utilisateur.
 * 
 * @param string $username
 * @param string $email
 * @param string $password
 * @return array ['success' => bool, 'message' => string]
 */
function registerUser(string $username, string $email, string $password): array {
    // Validation
    if (strlen($username) < 3 || strlen($username) > 100) {
        return ['success' => false, 'message' => 'Le nom doit contenir entre 3 et 100 caractères.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Adresse email invalide.'];
    }
    if (strlen($password) < 8) {
        return ['success' => false, 'message' => 'Le mot de passe doit contenir au moins 8 caractères.'];
    }

    $db = getDB();

    // Vérifier si l'email est déjà utilisé
    $stmt = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Cet email est déjà utilisé.'];
    }

    // Insérer l'utilisateur avec un hash sécurisé
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $db->prepare(
        'INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :hash)'
    );
    $stmt->execute([
        ':username' => htmlspecialchars($username, ENT_QUOTES, 'UTF-8'),
        ':email'    => $email,
        ':hash'     => $hash,
    ]);

    return ['success' => true, 'message' => 'Compte créé avec succès !'];
}

/**
 * Connecte un utilisateur.
 * 
 * @param string $email
 * @param string $password
 * @return array ['success' => bool, 'message' => string]
 */
function loginUser(string $email, string $password): array {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Email ou mot de passe incorrect.'];
    }

    $db = getDB();
    $stmt = $db->prepare('SELECT id, username, password_hash FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    // Utiliser password_verify pour comparer de manière sécurisée
    if (!$user || !password_verify($password, $user['password_hash'])) {
        // Délai pour décourager le brute-force
        usleep(random_int(100000, 500000));
        return ['success' => false, 'message' => 'Email ou mot de passe incorrect.'];
    }

    // Régénérer la session après connexion (protection contre la fixation)
    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['initiated'] = true;

    return ['success' => true, 'message' => 'Connexion réussie.'];
}

/**
 * Déconnecte l'utilisateur et détruit la session.
 */
function logoutUser(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

// ============================================================
// Protection CSRF
// ============================================================

/**
 * Génère un token CSRF et le stocke en session.
 * 
 * @return string Token CSRF
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie le token CSRF soumis par un formulaire.
 * 
 * @param string $token
 * @return bool
 */
function verifyCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
