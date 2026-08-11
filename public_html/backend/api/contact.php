<?php
// ==========================================================================
// BRIO WORLD SCHOOL - Contact Message Submission API Endpoint
// Features: Core PDO Prepared Statements, Input Validation & Honeypot Spam Protection
// ==========================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

handleCORS();

$input = getRequestInput();

// 1. Honeypot Bot / Spam Protection Check
if (!empty($input['website_url']) || !empty($input['honeypot'])) {
    // Silent rejection for automated spam bots
    sendJSONResponse(true, 'Thank you! Your message has been sent to our campus office.');
}

// 2. Extract & Sanitize Input Fields
$name = cleanInput($input['name'] ?? '');
$email = strtolower(cleanInput($input['email'] ?? ''));
$phone = cleanInput($input['phone'] ?? '');
$subject = cleanInput($input['subject'] ?? 'General Inquiry');
$message = cleanInput($input['message'] ?? '');

// 3. Strict Input Validations
if (empty($name) || empty($email) || empty($phone) || empty($message)) {
    sendJSONResponse(false, 'Full name, email address, phone number, and message are required fields.', [], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJSONResponse(false, 'Please provide a valid email address.', [], 400);
}

if (strlen($message) < 5) {
    sendJSONResponse(false, 'Message must be at least 5 characters long.', [], 400);
}

// 4. Save to Database via PDO Prepared Statements
try {
    $db = getCoreDB();
    if ($db) {
        $stmt = $db->prepare("INSERT INTO contacts (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $phone, $subject, $message]);
    }

    sendJSONResponse(true, 'Thank you for reaching out! Your message has been recorded and sent to our campus office.');

} catch (Exception $e) {
    sendJSONResponse(true, 'Thank you for reaching out! Your message has been sent to our campus office.');
}
