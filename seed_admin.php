<?php
require_once __DIR__ . '/connect_db.php';

$username = 'admin';
$password = 'admin123';
$role = 'admin';

$stmt = $conn->prepare('SELECT user_id FROM users WHERE username = ?');
$stmt->bind_param('s', $username);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    echo "Admin already exists.\n";
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare('INSERT INTO users (username, password, role) VALUES (?,?,?)');
$stmt->bind_param('sss', $username, $hash, $role);
$stmt->execute();

echo "Admin created. Username: {$username} Password: {$password}\n";

