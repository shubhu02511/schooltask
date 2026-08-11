<?php
// ==========================================================================
// BRIO WORLD SCHOOL - Contact Message Submission API Endpoint
// ==========================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

handleCORS();

$input = getRequestInput();
$name = cleanInput($input['name'] ?? '');
$email = strtolower(cleanInput($input['email'] ?? ''));
$phone = cleanInput($input['phone'] ?? '');
$subject = cleanInput($input['subject'] ?? 'General Inquiry');
$message = cleanInput($input['message'] ?? '');

if (empty($name) || empty($email) || empty($message)) {
    sendJSONResponse(false, 'Name, email, and message are required', [], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJSONResponse(false, 'Please provide a valid email address', [], 400);
}

try {
    $db = getCoreDB();
    $stmt = $db->prepare("INSERT INTO contacts (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $email, $phone, $subject, $message]);

    sendJSONResponse(true, 'Thank you for reaching out! Your message has been sent to our campus office.');

} catch (Exception $e) {
    sendJSONResponse(false, 'Failed to send message: ' . $e->getMessage(), [], 500);
}
