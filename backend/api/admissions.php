<?php
// ==========================================================================
// BRIO WORLD SCHOOL - Admission Application Submission API Endpoint
// ==========================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

handleCORS();

$input = getRequestInput();
$studentName = cleanInput($input['student_name'] ?? '');
$parentName = cleanInput($input['parent_name'] ?? '');
$email = strtolower(cleanInput($input['email'] ?? ''));
$phone = cleanInput($input['phone'] ?? '');
$grade = cleanInput($input['grade'] ?? '');
$campus = cleanInput($input['campus'] ?? 'Gujarat Campus');
$message = cleanInput($input['message'] ?? '');

if (empty($studentName) || empty($parentName) || empty($email) || empty($phone) || empty($grade)) {
    sendJSONResponse(false, 'Student name, parent name, email, phone, and grade are required', [], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJSONResponse(false, 'Please provide a valid email address', [], 400);
}

try {
    $db = getCoreDB();
    $stmt = $db->prepare("INSERT INTO admissions (student_name, parent_name, email, phone, grade, campus, message) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$studentName, $parentName, $email, $phone, $grade, $campus, $message]);

    sendJSONResponse(true, 'Admission application submitted successfully! Our admissions office will contact you shortly.', [
        'application_id' => $db->lastInsertId()
    ]);

} catch (Exception $e) {
    sendJSONResponse(false, 'Failed to submit admission application: ' . $e->getMessage(), [], 500);
}
