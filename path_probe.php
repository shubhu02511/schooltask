<?php
header('Content-Type: application/json');
echo json_encode([
    'file' => __FILE__,
    'dir' => __DIR__,
    'time' => date('Y-m-d H:i:s'),
    'post' => $_POST,
    'raw' => @file_get_contents('php://input')
]);
