<?php
// ==========================================================================
// BRIO WORLD SCHOOL - Standalone TC Verification Portal
// Reference: https://sspublicschool.com/sspstcv/tc-verification.php
// Features: Core PHP + MySQL Verification by TC Number + Date of Birth (DOB)
// ==========================================================================

require_once __DIR__ . '/backend/config/db.php';
require_once __DIR__ . '/backend/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    @session_start();
}

$message = '';
$error = '';
$tcRecord = null;
$downloadUrl = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tcNumber = strtoupper(cleanInput($_POST['tc_number'] ?? ''));
    $rawDob = cleanInput($_POST['dob'] ?? '');

    $dobFormatted = '';
    if (!empty($rawDob)) {
        $time = strtotime($rawDob);
        if ($time) {
            $dobFormatted = date('Y-m-d', $time);
        }
    }

    if (empty($tcNumber) || empty($dobFormatted)) {
        $error = 'Please enter both TC Number and Date of Birth (DOB).';
    } else {
        // Query Database
        try {
            $db = getCoreDB();
            if ($db) {
                $stmt = $db->prepare("SELECT * FROM transfer_certificates WHERE UPPER(tc_number) = ? AND dob = ? AND verification_status = 'verified' LIMIT 1");
                $stmt->execute([$tcNumber, $dobFormatted]);
                $tcRecord = $stmt->fetch();
            }
        } catch (Exception $e) {}

        // Fallback JSON
        if (!$tcRecord) {
            $jsonFile = __DIR__ . '/storage/database/transfer_certificates.json';
            if (file_exists($jsonFile)) {
                $all = json_decode(file_get_contents($jsonFile), true) ?: [];
                foreach ($all as $item) {
                    $itemDob = !empty($item['dob']) ? date('Y-m-d', strtotime($item['dob'])) : '';
                    if (strtoupper($item['tc_number'] ?? '') === $tcNumber && $itemDob === $dobFormatted && ($item['verification_status'] ?? 'verified') === 'verified') {
                        $tcRecord = $item;
                        break;
                    }
                }
            }
        }

        // Demo Initial Seeds
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

        if ($tcRecord) {
            $token = bin2hex(random_bytes(16));
            $_SESSION['tc_download_auth_' . md5($tcRecord['tc_number'])] = [
                'token' => $token,
                'tc_number' => $tcRecord['tc_number'],
                'student_name' => $tcRecord['student_name'],
                'pdf_filename' => $tcRecord['pdf_filename'],
                'expires' => time() + 1800
            ];
            $downloadUrl = '/backend/api/download-tc.php?tc=' . urlencode($tcRecord['tc_number']) . '&token=' . $token;
            $message = 'Transfer Certificate Verified Successfully!';
        } else {
            $error = 'TC not found or verification details (TC Number / Date of Birth) are incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Transfer Certificate (TC) Verification - BRIO World School</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Inter', sans-serif; background: #F8FAFC; color: #0F172A; min-height: 100vh; display: flex; flex-direction: column; }
    
    header { background: #091224; color: white; padding: 1.25rem 2rem; border-bottom: 3px solid #F59E0B; text-align: center; }
    .brand-title { font-family: 'Outfit', sans-serif; font-size: 1.6rem; font-weight: 800; color: #FFFFFF; letter-spacing: 0.5px; }
    .brand-sub { color: #F59E0B; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-top: 2px; }
    
    .container { max-width: 700px; margin: 2.5rem auto; width: 90%; flex: 1; }
    
    .card { background: white; border-radius: 16px; border: 1.5px solid #E2E8F0; box-shadow: 0 10px 30px rgba(15,23,42,0.06); padding: 2.25rem; }
    .card-title { font-family: 'Outfit', sans-serif; font-size: 1.35rem; font-weight: 700; color: #0F172A; text-align: center; margin-bottom: 1.5rem; padding-bottom: 0.75rem; border-bottom: 2px solid #F1F5F9; }
    
    .form-group { margin-bottom: 1.25rem; }
    label { display: block; font-size: 0.82rem; font-weight: 700; color: #334155; margin-bottom: 0.4rem; text-transform: uppercase; }
    input[type="text"], input[type="date"] { width: 100%; padding: 0.8rem 1rem; border-radius: 8px; border: 1.5px solid #CBD5E1; font-size: 0.95rem; outline: none; transition: border-color 0.2s; }
    input:focus { border-color: #F59E0B; box-shadow: 0 0 0 3px rgba(245,158,11,0.15); }
    
    .btn-verify { background: #091224; color: #F59E0B; width: 100%; padding: 0.85rem; border-radius: 10px; font-weight: 800; font-size: 1rem; border: 2px solid #F59E0B; cursor: pointer; text-transform: uppercase; letter-spacing: 0.5px; transition: all 0.2s; margin-top: 0.5rem; }
    .btn-verify:hover { background: #F59E0B; color: #091224; }
    
    .alert-error { background: #FEF2F2; border: 1px solid #FCA5A5; color: #991B1B; padding: 0.85rem 1rem; border-radius: 8px; font-size: 0.9rem; font-weight: 600; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.6rem; }
    .alert-success { background: #ECFDF5; border: 1px solid #6EE7B7; color: #065F46; padding: 0.85rem 1rem; border-radius: 8px; font-size: 0.9rem; font-weight: 600; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.6rem; }
    
    .result-box { background: #091224; color: white; border-radius: 12px; padding: 1.75rem; margin-top: 1.5rem; border: 1.5px solid #F59E0B; }
    .result-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin: 1.25rem 0; font-size: 0.9rem; }
    .btn-download { display: block; text-align: center; background: #F59E0B; color: #091224; font-weight: 800; padding: 0.85rem; border-radius: 8px; text-decoration: none; font-size: 0.95rem; text-transform: uppercase; margin-top: 1rem; }
    .btn-download:hover { background: #D97706; }
    
    footer { background: #091224; color: #94A3B8; text-align: center; padding: 1.25rem; font-size: 0.85rem; border-top: 1px solid #1E293B; }
  </style>
</head>
<body>
  <header>
    <div class="brand-title">BRIO WORLD SCHOOL</div>
    <div class="brand-sub"><i class="fa-solid fa-shield-halved"></i> Online Transfer Certificate (TC) Verification</div>
  </header>

  <div class="container">
    <div class="card">
      <h2 class="card-title">Transfer Certificate (TC) Online Verification</h2>

      <?php if (!empty($error)): ?>
        <div class="alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if (!empty($message)): ?>
        <div class="alert-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <form method="POST" action="tc-verification.php">
        <div class="form-group">
          <label>Transfer Certificate (TC) Number *</label>
          <input type="text" name="tc_number" required placeholder="e.g. TC2026/001" value="<?= htmlspecialchars($_POST['tc_number'] ?? '') ?>">
          <small style="color: #64748B; font-size: 0.75rem; margin-top: 2px; display: block;">Enter TC number printed on certificate (e.g. TC2026/001)</small>
        </div>

        <div class="form-group">
          <label>Date of Birth (DOB) *</label>
          <input type="date" name="dob" required value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>">
          <small style="color: #64748B; font-size: 0.75rem; margin-top: 2px; display: block;">Enter student Date of Birth matching official school record</small>
        </div>

        <button type="submit" class="btn-verify"><i class="fa-solid fa-magnifying-glass"></i> Verify Certificate</button>
      </form>

      <?php if ($tcRecord): ?>
        <div class="result-box">
          <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.15); padding-bottom: 0.75rem;">
            <span style="background: rgba(16,185,129,0.2); color: #10B981; padding: 0.25rem 0.6rem; border-radius: 20px; font-weight: 700; font-size: 0.75rem; text-transform: uppercase;">
              <i class="fa-solid fa-check-circle"></i> Official Verified Record
            </span>
            <span style="color: #94A3B8; font-size: 0.8rem;"><?= htmlspecialchars($tcRecord['campus'] ?? 'Gujarat Campus') ?></span>
          </div>

          <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.35rem; color: white; margin-top: 1rem;"><?= htmlspecialchars($tcRecord['student_name']) ?></h3>

          <div class="result-grid">
            <div>
              <span style="color: #94A3B8; font-size: 0.78rem; display: block;">TC NUMBER</span>
              <strong style="color: #F59E0B;"><?= htmlspecialchars($tcRecord['tc_number']) ?></strong>
            </div>
            <div>
              <span style="color: #94A3B8; font-size: 0.78rem; display: block;">DATE OF BIRTH (DOB)</span>
              <strong style="color: white;"><?= date('d/m/Y', strtotime($tcRecord['dob'])) ?></strong>
            </div>
            <div>
              <span style="color: #94A3B8; font-size: 0.78rem; display: block;">CLASS / GRADE</span>
              <strong style="color: white;"><?= htmlspecialchars($tcRecord['class_name']) ?></strong>
            </div>
            <div>
              <span style="color: #94A3B8; font-size: 0.78rem; display: block;">ISSUE DATE</span>
              <strong style="color: white;"><?= date('M d, Y', strtotime($tcRecord['issue_date'])) ?></strong>
            </div>
          </div>

          <a href="<?= htmlspecialchars($downloadUrl) ?>" target="_blank" class="btn-download">
            <i class="fa-solid fa-file-pdf"></i> Download Official TC (PDF)
          </a>
        </div>
      <?php endif; ?>

    </div>
  </div>

  <footer>
    &copy; <?= date('Y') ?> BRIO World School. All Rights Reserved. | Transfer Certificate Verification Portal
  </footer>
</body>
</html>
