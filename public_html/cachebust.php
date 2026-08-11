<?php
// Cache buster - forces LiteSpeed/OPCache to reload PHP files
header('Content-Type: application/json');

$results = [];

// 1. Reset OPCache
if (function_exists('opcache_reset')) {
    $results['opcache_reset'] = opcache_reset() ? 'done' : 'failed';
} else {
    $results['opcache_reset'] = 'not available';
}

// 2. Reset APCu cache
if (function_exists('apcu_clear_cache')) {
    $results['apcu_clear'] = apcu_clear_cache() ? 'done' : 'failed';
} else {
    $results['apcu_clear'] = 'not available';
}

// 3. Check php://input right now
$rawInput = @file_get_contents('php://input');
$results['php_input'] = $rawInput;
$results['post'] = $_POST;
$results['server_request_method'] = $_SERVER['REQUEST_METHOD'];
$results['server_content_type'] = $_SERVER['CONTENT_TYPE'] ?? 'not set';
$results['php_version'] = phpversion();
$results['time'] = date('Y-m-d H:i:s');

echo json_encode($results);
