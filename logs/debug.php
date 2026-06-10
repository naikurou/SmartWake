<?php
require_once __DIR__ . '/includes/auth.php';

$email = 'nahilchine2021@gmail.com';
// Je ne connais pas le mot de passe que le user a entré, mais on va le faire logguer l'erreur avec l'email donné.
$db = getDB();
$stmt = $db->prepare('SELECT id, username, password_hash FROM users WHERE email = :email LIMIT 1');
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

echo "User: "; var_dump($user);
