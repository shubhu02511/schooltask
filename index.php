<?php
// Catch ALL errors including fatal ones
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo json_encode([
            'success' => false,
            'fatal' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ]);
    }
});

ini_set('display_errors', 0);
error_reporting(E_ALL);

$GLOBALS['RAW_INPUT'] = @file_get_contents('php://input');

header('Content-Type: application/json');

try {
    // CLI dev server — serve static files directly
    if (php_sapi_name() === 'cli-server') {
        $filePath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if ($filePath !== '/' && file_exists(__DIR__ . $filePath) && !is_dir(__DIR__ . $filePath)) {
            return false;
        }
    }

    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/mail_helper.php';
    require_once __DIR__ . '/router.php';
    require_once __DIR__ . '/controllers/AuthController.php';
    require_once __DIR__ . '/controllers/CareerController.php';

    $router = new Router();

    // Auth System Routes
    $router->post('/api/auth/register', [AuthController::class, 'register']);
    $router->post('/api/auth/verify-otp', [AuthController::class, 'verifyOTP']);
    $router->post('/api/auth/login', [AuthController::class, 'login']);
    $router->post('/api/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    $router->post('/api/auth/reset-password', [AuthController::class, 'resetPassword']);
    $router->get('/api/auth/me', [AuthController::class, 'me']);
    $router->post('/api/auth/logout', [AuthController::class, 'logout']);

    // Career Application Routes
    $router->post('/api/career/apply', [CareerController::class, 'apply']);
    $router->get('/api/career/applications', [CareerController::class, 'listApplications']);

    $router->dispatch();
} catch (Throwable $t) {
    echo json_encode([
        'success' => false,
        'message' => 'Index error: ' . $t->getMessage(),
        'file' => $t->getFile(),
        'line' => $t->getLine(),
        'trace' => $t->getTraceAsString()
    ]);
}
