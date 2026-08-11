<?php
// ==========================================================================
// BRIO WORLD SCHOOL - Student Transfer Certificate (TC) Verification API
// Security: Requires matching TC Number + Admission Number
// ==========================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    @session_start();
}

handleCORS();

$input = getRequestInput();
$tcNumber = strtoupper(cleanInput($input['tc_number'] ?? ''));
$admissionNo = strtoupper(cleanInput($input['admission_no'] ?? ''));

if (empty($tcNumber) || empty($admissionNo)) {
    sendJSONResponse(false, 'Please enter both TC Number and Admission / Registration Number.', [], 400);
}

$tcRecord = null;

// 1. Try MySQL Query
try {
    $db = getCoreDB();
    if ($db) {
        $stmt = $db->prepare("SELECT * FROM transfer_certificates WHERE UPPER(tc_number) = ? AND UPPER(admission_no) = ? AND verification_status = 'verified' LIMIT 1");
        $stmt->execute([$tcNumber, $admissionNo]);
        $tcRecord = $stmt->fetch();
    }
} catch (Exception $e) {
    // Fallback to JSON
}

// 2. Fallback to JSON File Storage
if (!$tcRecord) {
    $jsonFile = __DIR__ . '/../../storage/database/transfer_certificates.json';
    if (file_exists($jsonFile)) {
        $all = json_decode(file_get_contents($jsonFile), true) ?: [];
        foreach ($all as $item) {
            if (strtoupper($item['tc_number'] ?? '') === $tcNumber && 
                strtoupper($item['admission_no'] ?? '') === $admissionNo && 
                ($item['verification_status'] ?? 'verified') === 'verified') {
                $tcRecord = $item;
                break;
            }
        }
    }
}

// 3. Fallback Initial Demo Seeds
if (!$tcRecord) {
    if ($tcNumber === 'TC2026/001' && $admissionNo === 'ADM9821') {
        $tcRecord = [
            'id' => 1,
            'student_name' => 'Aarav Sharma',
            'tc_number' => 'TC2026/001',
            'admission_no' => 'ADM9821',
            'class_name' => 'Grade 10',
            'issue_date' => '2026-06-15',
            'campus' => 'Gujarat Campus',
            'verification_status' => 'verified',
            'pdf_filename' => 'TC2026_001.pdf'
        ];
    } elseif ($tcNumber === 'TC2026/002' && $admissionNo === 'ADM9822') {
        $tcRecord = [
            'id' => 2,
            'student_name' => 'Ananya Verma',
            'tc_number' => 'TC2026/002',
            'admission_no' => 'ADM9822',
            'class_name' => 'Grade 12',
            'issue_date' => '2026-06-20',
            'campus' => 'Delhi NCR Campus',
            'verification_status' => 'verified',
            'pdf_filename' => 'TC2026_002.pdf'
        ];
    }
}

// If Verification Fails
if (!$tcRecord) {
    sendJSONResponse(false, 'TC not found or verification details are incorrect.', [], 404);
}

// Generate Short-lived Secure Session Token for Download
$token = bin2hex(random_bytes(16));
$_SESSION['tc_download_auth_' . md5($tcRecord['tc_number'])] = [
    'token' => $token,
    'tc_number' => $tcRecord['tc_number'],
    'pdf_filename' => $tcRecord['pdf_filename'],
    'expires' => time() + 1800 // 30 minutes
];

sendJSONResponse(true, 'Transfer Certificate verified successfully!', [
    'tc' => [
        'student_name' => $tcRecord['student_name'],
        'tc_number' => $tcRecord['tc_number'],
        'admission_no' => $tcRecord['admission_no'],
        'class_name' => $tcRecord['class_name'],
        'issue_date' => date('M d, Y', strtotime($tcRecord['issue_date'])),
        'campus' => $tcRecord['campus'] ?? 'Gujarat Campus',
        'status' => 'Verified Official Record'
    ],
    'download_url' => '/backend/api/download-tc.php?tc=' . urlencode($tcRecord['tc_number']) . '&token=' . $token
]);
