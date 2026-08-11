<?php
// ==========================================================================
// BRIO WORLD SCHOOL - Student Transfer Certificate (TC) Verification API
// Security: Requires matching TC Number + Date of Birth (DOB)
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
$tcNumber = strtoupper(cleanInput($input['tc_number'] ?? $_POST['tc_number'] ?? ''));
$rawDob = cleanInput($input['dob'] ?? $_POST['dob'] ?? '');

// Format DOB to YYYY-MM-DD
$dobFormatted = '';
if (!empty($rawDob)) {
    $time = strtotime($rawDob);
    if ($time) {
        $dobFormatted = date('Y-m-d', $time);
    }
}

if (empty($tcNumber) || empty($dobFormatted)) {
    sendJSONResponse(false, 'Please enter both TC Number and Student Date of Birth (DOB).', [], 400);
}

$tcRecord = null;

// 1. Query MySQL Database
try {
    $db = getCoreDB();
    if ($db) {
        $stmt = $db->prepare("SELECT * FROM transfer_certificates WHERE UPPER(tc_number) = ? AND dob = ? AND verification_status = 'verified' LIMIT 1");
        $stmt->execute([$tcNumber, $dobFormatted]);
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
            $itemDob = !empty($item['dob']) ? date('Y-m-d', strtotime($item['dob'])) : '';
            if (strtoupper($item['tc_number'] ?? '') === $tcNumber && 
                $itemDob === $dobFormatted && 
                ($item['verification_status'] ?? 'verified') === 'verified') {
                $tcRecord = $item;
                break;
            }
        }
    }
}

// 3. Fallback Initial Demo Seeds
if (!$tcRecord) {
    if ($tcNumber === 'TC2026/001' && $dobFormatted === '2010-05-15') {
        $tcRecord = [
            'id' => 1,
            'student_name' => 'Aarav Sharma',
            'tc_number' => 'TC2026/001',
            'dob' => '2010-05-15',
            'admission_no' => 'ADM9821',
            'class_name' => 'Grade 10',
            'issue_date' => '2026-06-15',
            'campus' => 'Gujarat Campus',
            'verification_status' => 'verified',
            'pdf_filename' => 'TC2026_001.pdf'
        ];
    } elseif ($tcNumber === 'TC2026/002' && $dobFormatted === '2008-11-20') {
        $tcRecord = [
            'id' => 2,
            'student_name' => 'Ananya Verma',
            'tc_number' => 'TC2026/002',
            'dob' => '2008-11-20',
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
    sendJSONResponse(false, 'TC not found or verification details (TC Number / Date of Birth) are incorrect.', [], 404);
}

// Generate Short-lived Secure Session Token for Download
$token = bin2hex(random_bytes(16));
$_SESSION['tc_download_auth_' . md5($tcRecord['tc_number'])] = [
    'token' => $token,
    'tc_number' => $tcRecord['tc_number'],
    'student_name' => $tcRecord['student_name'],
    'pdf_filename' => $tcRecord['pdf_filename'],
    'expires' => time() + 1800 // 30 minutes
];

sendJSONResponse(true, 'Transfer Certificate verified successfully!', [
    'tc' => [
        'student_name' => $tcRecord['student_name'],
        'tc_number' => $tcRecord['tc_number'],
        'dob' => date('d/m/Y', strtotime($tcRecord['dob'])),
        'admission_no' => $tcRecord['admission_no'] ?? 'N/A',
        'class_name' => $tcRecord['class_name'],
        'issue_date' => date('M d, Y', strtotime($tcRecord['issue_date'])),
        'campus' => $tcRecord['campus'] ?? 'Gujarat Campus',
        'status' => 'Verified Official Record'
    ],
    'download_url' => '/backend/api/download-tc.php?tc=' . urlencode($tcRecord['tc_number']) . '&token=' . $token
]);
