<?php
require __DIR__ . '/config/db.php';

$username = 'admin';
$password = 'Admin@123';   // you can change this

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)');
$stmt->execute([$username, $hash]);

echo 'Admin created. Username: ' . $username . ' | Password: ' . $password;