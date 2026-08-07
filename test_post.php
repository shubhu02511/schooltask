<?php
// Minimal POST test - no sessions, no DB, no includes
header('Content-Type: application/json');
$name = $_POST['name'] ?? 'EMPTY';
$email = $_POST['email'] ?? 'EMPTY';
echo json_encode([
    'success' => true,
    'method' => $_SERVER['REQUEST_METHOD'],
    'post_name' => $name,
    'post_email' => $email,
    'php_version' => phpversion(),
    'time' => date('H:i:s')
]);
