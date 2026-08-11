<?php
// ==========================================================================
// BRIO WORLD SCHOOL - Protected Transfer Certificate PDF Download API
// Security: Session token authorization required, prevents public file exposure
// ==========================================================================

require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    @session_start();
}

$tcNumber = strtoupper(cleanInput($_GET['tc'] ?? ''));
$token = cleanInput($_GET['token'] ?? '');

if (empty($tcNumber) || empty($token)) {
    http_response_code(403);
    die('Access Denied: Missing authorization parameters.');
}

$sessionKey = 'tc_download_auth_' . md5($tcNumber);
$authData = $_SESSION[$sessionKey] ?? null;

if (!$authData || ($authData['token'] ?? '') !== $token || time() > ($authData['expires'] ?? 0)) {
    http_response_code(403);
    die('Access Denied: Invalid or expired download authorization token. Please verify TC details again.');
}

$filename = basename($authData['pdf_filename']);
$filePath = __DIR__ . '/../../storage/private/tc_docs/' . $filename;

if (!file_exists($filePath)) {
    http_response_code(404);
    die('Error: The requested Transfer Certificate document file could not be found.');
}

// Clear output buffers to prevent corrupt PDF delivery
if (ob_get_level()) {
    ob_end_clean();
}

// Send Secure PDF Download Headers
header('Content-Description: File Transfer');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="BRIO_Transfer_Certificate_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $tcNumber) . '.pdf"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));
header('X-Content-Type-Options: nosniff');

readfile($filePath);
exit;
