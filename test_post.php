<?php
// Step-by-step crash locator
ob_start();
header('Content-Type: application/json');

$steps = [];
$steps[] = 'PHP started';

try {
    $steps[] = 'Before config.php';
    require_once __DIR__ . '/config.php';
    $steps[] = 'After config.php OK';

    $steps[] = 'Before db.php';
    require_once __DIR__ . '/db.php';
    $steps[] = 'After db.php OK';

    $steps[] = 'Before mail_helper.php';
    require_once __DIR__ . '/mail_helper.php';
    $steps[] = 'After mail_helper.php OK';

    $steps[] = 'Before router.php';
    require_once __DIR__ . '/router.php';
    $steps[] = 'After router.php OK';

    $steps[] = 'Before AuthController.php';
    require_once __DIR__ . '/controllers/AuthController.php';
    $steps[] = 'After AuthController.php OK';

    // Test POST data reading
    $steps[] = 'POST name: ' . ($_POST['name'] ?? 'EMPTY');
    $steps[] = 'POST email: ' . ($_POST['email'] ?? 'EMPTY');

    // Test DB connection
    $steps[] = 'Before getDB()';
    $db = getDB();
    $steps[] = 'After getDB() - class: ' . get_class($db);

    $steps[] = 'ALL OK';

} catch (Throwable $t) {
    $steps[] = 'CRASH: ' . $t->getMessage() . ' in ' . basename($t->getFile()) . ':' . $t->getLine();
}

$error = error_get_last();
echo json_encode([
    'steps' => $steps,
    'last_error' => $error,
    'php_version' => phpversion(),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time')
]);
