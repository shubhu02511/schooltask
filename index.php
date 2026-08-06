<?php
// Front Controller Index File
require_once __DIR__ . '/config.php';
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

// Dispatch Incoming Request
$router->dispatch();
