<?php
// ==========================================================================
// BRIO WORLD SCHOOL - Career Application Submission API Endpoint
// ==========================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

handleCORS();

$input = getRequestInput();
$fullName = cleanInput($input['full_name'] ?? '');
$email = strtolower(cleanInput($input['email'] ?? ''));
$phone = cleanInput($input['phone'] ?? '');
$jobTitle = cleanInput($input['job_title'] ?? 'Faculty Educator');
$experience = (int)($input['experience'] ?? 0);
$message = cleanInput($input['message'] ?? '');

if (empty($fullName) || empty($email) || empty($phone)) {
    sendJSONResponse(false, 'Full name, email, and phone number are required', [], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJSONResponse(false, 'Please provide a valid email address', [], 400);
}

$resumePath = null;
if (!empty($_FILES['resume']['name'])) {
    $allowedExts = ['pdf', 'doc', 'docx'];
    $fileExt = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
    
    if (!in_array($fileExt, $allowedExts)) {
        sendJSONResponse(false, 'Invalid file type. Only PDF and Word documents are allowed.', [], 400);
    }

    $uploadDir = __DIR__ . '/../../storage/uploads/resumes/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }

    $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $_FILES['resume']['name']);
    $targetFile = $uploadDir . $filename;

    if (move_uploaded_file($_FILES['resume']['tmp_name'], $targetFile)) {
        $resumePath = 'storage/uploads/resumes/' . $filename;
    }
}

try {
    $db = getCoreDB();
    $stmt = $db->prepare("INSERT INTO careers (full_name, email, phone, job_title, experience, resume_path, message) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$fullName, $email, $phone, $jobTitle, $experience, $resumePath, $message]);

    sendJSONResponse(true, 'Career application submitted successfully! Our HR recruitment team will review your application.', [
        'application_id' => $db->lastInsertId()
    ]);

} catch (Exception $e) {
    sendJSONResponse(false, 'Failed to submit career application: ' . $e->getMessage(), [], 500);
}
