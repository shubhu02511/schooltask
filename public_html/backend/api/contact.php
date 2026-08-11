<?php
// ==========================================================================
// BRIO WORLD SCHOOL - Contact Message Submission API Endpoint
// Features: Core PDO Prepared Statements, Input Validation, Honeypot & Dual Storage Fallback
// ==========================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

handleCORS();

$input = getRequestInput();

// 1. Honeypot Bot / Spam Protection Check
if (!empty($input['website_url']) || !empty($input['honeypot'])) {
    sendJSONResponse(true, 'Thank you! Your message has been sent to our campus office.');
}

// 2. Extract & Sanitize Input Fields
$name = cleanInput($input['name'] ?? '');
$email = strtolower(cleanInput($input['email'] ?? ''));
$phone = cleanInput($input['phone'] ?? '');
$subject = cleanInput($input['subject'] ?? 'General Inquiry');
$message = cleanInput($input['message'] ?? '');

// 3. Strict Input Validations
if (empty($input['human_verification'])) {
    sendJSONResponse(false, 'Please check the "I am not a robot" box before submitting.', [], 400);
}

if (empty($name) || empty($email) || empty($phone) || empty($message)) {
    sendJSONResponse(false, 'Full name, email address, phone number, and message are required fields.', [], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJSONResponse(false, 'Please provide a valid email address.', [], 400);
}

// 4. Dual Storage Insertion (MySQL + JSON Backup)
$saved = false;

try {
    $db = getCoreDB();
    if ($db) {
        $stmt = $db->prepare("INSERT INTO contacts (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
        $saved = $stmt->execute([$name, $email, $phone, $subject, $message]);
    }
} catch (Exception $e) {
    // MySQL table not ready yet, continue to JSON backup file
}

// Fallback JSON Backup File Storage
$jsonFile = __DIR__ . '/../../storage/database/contacts.json';
$storageDir = dirname($jsonFile);
if (!is_dir($storageDir)) {
    @mkdir($storageDir, 0755, true);
}

$existing = [];
if (file_exists($jsonFile)) {
    $content = file_get_contents($jsonFile);
    $existing = json_decode($content, true) ?: [];
}

$newEntry = [
    'id' => count($existing) + 1,
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'subject' => $subject,
    'message' => $message,
    'created_at' => date('Y-m-d H:i:s')
];

array_unshift($existing, $newEntry);
@file_put_contents($jsonFile, json_encode($existing, JSON_PRETTY_PRINT));

sendJSONResponse(true, 'Thank you for reaching out! Your message has been recorded and sent to our campus office.');
