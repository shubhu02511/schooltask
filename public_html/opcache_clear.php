<?php
header('Content-Type: application/json');
$files = glob(__DIR__ . '/*.php');
$files = array_merge($files, glob(__DIR__ . '/controllers/*.php'));
$invalidated = [];

foreach ($files as $f) {
    if (function_exists('opcache_invalidate')) {
        $res = @opcache_invalidate($f, true);
        $invalidated[basename($f)] = $res ? 'cleared' : 'failed';
    }
}

if (function_exists('opcache_reset')) {
    @opcache_reset();
}

echo json_encode(['status' => 'done', 'invalidated' => $invalidated, 'time' => date('H:i:s')]);
