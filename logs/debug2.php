<?php
require_once __DIR__ . '/../includes/auth.php';
$email = 'nahilchine2021@gmail.com';
$db = getDB();
$stmt = $db->prepare('SELECT id, username, password_hash FROM users WHERE email = :email LIMIT 1');
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();
var_dump($user);
