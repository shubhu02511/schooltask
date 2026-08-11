<?php
// ==========================================================================
// BRIO WORLD SCHOOL - Core PHP Admin Transfer Certificate (TC) Management
// Actions: Add TC (with DOB), Upload PDF, Edit TC, Replace PDF, Delete TC
// ==========================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_helper.php';

AuthHelper::requireAdmin();

$db = getCoreDB();
$message = '';
$error = '';

// --------------------------------------------------------------------------
// HANDLE ACTIONS (CREATE, EDIT, REPLACE PDF, DELETE)
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = cleanInput($_POST['action'] ?? '');
    
    // 1. ADD / CREATE TC RECORD
    if ($action === 'create') {
        $studentName = cleanInput($_POST['student_name'] ?? '');
        $tcNumber = strtoupper(cleanInput($_POST['tc_number'] ?? ''));
        $dob = cleanInput($_POST['dob'] ?? '');
        $admissionNo = strtoupper(cleanInput($_POST['admission_no'] ?? ''));
        $className = cleanInput($_POST['class_name'] ?? '');
        $issueDate = cleanInput($_POST['issue_date'] ?? date('Y-m-d'));
        $campus = cleanInput($_POST['campus'] ?? 'Gujarat Campus');
        $status = cleanInput($_POST['verification_status'] ?? 'verified');

        $pdfFilename = '';

        if (!empty($_FILES['tc_pdf']['name'])) {
            $fileExt = strtolower(pathinfo($_FILES['tc_pdf']['name'], PATHINFO_EXTENSION));
            if ($fileExt !== 'pdf') {
                $error = 'Only PDF documents (.pdf) are allowed for Transfer Certificates.';
            } else {
                $uploadDir = __DIR__ . '/../../storage/private/tc_docs/';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }
                $pdfFilename = time() . '_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $tcNumber) . '.pdf';
                if (!move_uploaded_file($_FILES['tc_pdf']['tmp_name'], $uploadDir . $pdfFilename)) {
                    $error = 'Failed to save PDF file on server.';
                }
            }
        } else {
            $error = 'Please select a TC PDF document file to upload.';
        }

        if (empty($error)) {
            if (empty($studentName) || empty($tcNumber) || empty($dob) || empty($className)) {
                $error = 'Student Name, TC Number, Date of Birth (DOB), and Class are required.';
            } else {
                try {
                    if ($db) {
                        $check = $db->prepare("SELECT id FROM transfer_certificates WHERE UPPER(tc_number) = ?");
                        $check->execute([$tcNumber]);
                        if ($check->fetch()) {
                            $error = 'A Transfer Certificate with TC Number "' . $tcNumber . '" already exists.';
                        } else {
                            $stmt = $db->prepare("INSERT INTO transfer_certificates (student_name, tc_number, dob, admission_no, class_name, issue_date, campus, verification_status, pdf_filename) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->execute([$studentName, $tcNumber, $dob, $admissionNo, $className, $issueDate, $campus, $status, $pdfFilename]);
                            $message = 'Transfer Certificate added successfully!';
                        }
                    }
                } catch (Exception $e) {}

                if (empty($error)) {
                    $jsonFile = __DIR__ . '/../../storage/database/transfer_certificates.json';
                    $existing = file_exists($jsonFile) ? (json_decode(file_get_contents($jsonFile), true) ?: []) : [];
                    $newEntry = [
                        'id' => time(),
                        'student_name' => $studentName,
                        'tc_number' => $tcNumber,
                        'dob' => $dob,
                        'admission_no' => $admissionNo,
                        'class_name' => $className,
                        'issue_date' => $issueDate,
                        'campus' => $campus,
                        'verification_status' => $status,
                        'pdf_filename' => $pdfFilename,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    array_unshift($existing, $newEntry);
                    @file_put_contents($jsonFile, json_encode($existing, JSON_PRETTY_PRINT));
                    $message = 'Transfer Certificate added successfully!';
                }
            }
        }
    }

    // 2. EDIT TC DETAILS / REPLACE PDF
    elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $studentName = cleanInput($_POST['student_name'] ?? '');
        $tcNumber = strtoupper(cleanInput($_POST['tc_number'] ?? ''));
        $dob = cleanInput($_POST['dob'] ?? '');
        $admissionNo = strtoupper(cleanInput($_POST['admission_no'] ?? ''));
        $className = cleanInput($_POST['class_name'] ?? '');
        $issueDate = cleanInput($_POST['issue_date'] ?? date('Y-m-d'));
        $campus = cleanInput($_POST['campus'] ?? 'Gujarat Campus');
        $status = cleanInput($_POST['verification_status'] ?? 'verified');

        $newPdfFilename = '';
        if (!empty($_FILES['tc_pdf']['name'])) {
            $fileExt = strtolower(pathinfo($_FILES['tc_pdf']['name'], PATHINFO_EXTENSION));
            if ($fileExt === 'pdf') {
                $uploadDir = __DIR__ . '/../../storage/private/tc_docs/';
                $newPdfFilename = time() . '_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $tcNumber) . '.pdf';
                move_uploaded_file($_FILES['tc_pdf']['tmp_name'], $uploadDir . $newPdfFilename);
            }
        }

        if ($id > 0 && !empty($studentName)) {
            try {
                if ($db) {
                    if (!empty($newPdfFilename)) {
                        $stmt = $db->prepare("UPDATE transfer_certificates SET student_name=?, tc_number=?, dob=?, admission_no=?, class_name=?, issue_date=?, campus=?, verification_status=?, pdf_filename=? WHERE id=?");
                        $stmt->execute([$studentName, $tcNumber, $dob, $admissionNo, $className, $issueDate, $campus, $status, $newPdfFilename, $id]);
                    } else {
                        $stmt = $db->prepare("UPDATE transfer_certificates SET student_name=?, tc_number=?, dob=?, admission_no=?, class_name=?, issue_date=?, campus=?, verification_status=? WHERE id=?");
                        $stmt->execute([$studentName, $tcNumber, $dob, $admissionNo, $className, $issueDate, $campus, $status, $id]);
                    }
                }
            } catch (Exception $e) {}

            $jsonFile = __DIR__ . '/../../storage/database/transfer_certificates.json';
            if (file_exists($jsonFile)) {
                $all = json_decode(file_get_contents($jsonFile), true) ?: [];
                foreach ($all as &$tc) {
                    if (($tc['id'] ?? 0) == $id) {
                        $tc['student_name'] = $studentName;
                        $tc['tc_number'] = $tcNumber;
                        $tc['dob'] = $dob;
                        $tc['admission_no'] = $admissionNo;
                        $tc['class_name'] = $className;
                        $tc['issue_date'] = $issueDate;
                        $tc['campus'] = $campus;
                        $tc['verification_status'] = $status;
                        if (!empty($newPdfFilename)) {
                            $tc['pdf_filename'] = $newPdfFilename;
                        }
                    }
                }
                @file_put_contents($jsonFile, json_encode($all, JSON_PRETTY_PRINT));
            }
            $message = 'Transfer Certificate updated successfully!';
        }
    }

    // 3. DELETE TC RECORD
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                if ($db) {
                    $stmt = $db->prepare("DELETE FROM transfer_certificates WHERE id=?");
                    $stmt->execute([$id]);
                }
            } catch (Exception $e) {}

            $jsonFile = __DIR__ . '/../../storage/database/transfer_certificates.json';
            if (file_exists($jsonFile)) {
                $all = json_decode(file_get_contents($jsonFile), true) ?: [];
                $filtered = array_values(array_filter($all, fn($t) => ($t['id'] ?? 0) != $id));
                @file_put_contents($jsonFile, json_encode($filtered, JSON_PRETTY_PRINT));
            }
            $message = 'Transfer Certificate record deleted successfully.';
        }
    }
}

// FETCH TC LIST
$tcList = [];
try {
    if ($db) {
        $stmt = $db->prepare("SELECT * FROM transfer_certificates ORDER BY id DESC");
        $stmt->execute();
        $tcList = $stmt->fetchAll() ?: [];
    }
} catch (Exception $e) {}

$jsonFile = __DIR__ . '/../../storage/database/transfer_certificates.json';
if (empty($tcList) && file_exists($jsonFile)) {
    $tcList = json_decode(file_get_contents($jsonFile), true) ?: [];
}

if (empty($tcList)) {
    $tcList = [
        ['id' => 1, 'student_name' => 'Aarav Sharma', 'tc_number' => 'TC2026/001', 'dob' => '2010-05-15', 'admission_no' => 'ADM9821', 'class_name' => 'Grade 10', 'issue_date' => '2026-06-15', 'campus' => 'Gujarat Campus', 'verification_status' => 'verified', 'pdf_filename' => 'TC2026_001.pdf', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 2, 'student_name' => 'Ananya Verma', 'tc_number' => 'TC2026/002', 'dob' => '2008-11-20', 'admission_no' => 'ADM9822', 'class_name' => 'Grade 12', 'issue_date' => '2026-06-20', 'campus' => 'Delhi NCR Campus', 'verification_status' => 'verified', 'pdf_filename' => 'TC2026_002.pdf', 'created_at' => date('Y-m-d H:i:s')]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Transfer Certificates - BRIO Admin Panel</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Inter', sans-serif; background: #0F172A; color: #F8FAFC; min-height: 100vh; }
    header { background: #1E293B; border-bottom: 1px solid #334155; padding: 1rem 2rem; display: flex; align-items: center; justify-content: space-between; }
    .brand { font-family: 'Outfit', sans-serif; font-size: 1.25rem; font-weight: 800; color: #F59E0B; text-decoration: none; display: flex; align-items: center; gap: 0.6rem; }
    .nav-links a { color: #94A3B8; text-decoration: none; margin-left: 1.5rem; font-size: 0.9rem; font-weight: 600; transition: color 0.2s; }
    .nav-links a:hover, .nav-links a.active { color: #F59E0B; }
    .container { padding: 2rem; max-width: 1200px; margin: 0 auto; width: 100%; }
    .header-bar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; }
    .btn-add { background: #F59E0B; color: #0F172A; padding: 0.65rem 1.25rem; border-radius: 8px; font-weight: 700; border: none; cursor: pointer; text-decoration: none; font-size: 0.9rem; }
    .btn-add:hover { background: #D97706; }
    .card { background: #1E293B; border: 1px solid #334155; border-radius: 12px; padding: 1.5rem; }
    table { width: 100%; border-collapse: collapse; text-align: left; }
    th { background: #0F172A; color: #94A3B8; padding: 0.85rem 1rem; font-size: 0.8rem; text-transform: uppercase; font-weight: 700; border-bottom: 1px solid #334155; }
    td { padding: 1rem; border-bottom: 1px solid #334155; font-size: 0.9rem; color: #E2E8F0; vertical-align: middle; }
    .badge-verified { background: rgba(16,185,129,0.15); color: #10B981; padding: 0.25rem 0.65rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
    .badge-revoked { background: rgba(239,68,68,0.15); color: #FCA5A5; padding: 0.25rem 0.65rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
    .action-btn { background: #334155; color: white; border: none; padding: 0.4rem 0.75rem; border-radius: 6px; font-size: 0.8rem; font-weight: 600; cursor: pointer; margin-right: 0.35rem; }
    .action-btn:hover { background: #475569; }
    .action-btn.delete { background: rgba(220,38,38,0.2); color: #FCA5A5; }
    .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 1000; align-items: center; justify-content: center; padding: 1.5rem; }
    .modal.active { display: flex; }
    .modal-card { background: #1E293B; border: 1px solid #334155; border-radius: 16px; width: 100%; max-width: 650px; padding: 2rem; max-height: 90vh; overflow-y: auto; }
    .form-group { margin-bottom: 1rem; }
    label { display: block; font-size: 0.82rem; font-weight: 700; color: #CBD5E1; margin-bottom: 0.35rem; text-transform: uppercase; }
    input, select { width: 100%; padding: 0.75rem 0.9rem; border-radius: 8px; border: 1px solid #475569; background: #0F172A; color: white; outline: none; font-size: 0.9rem; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .alert-success { background: rgba(16,185,129,0.2); border: 1px solid #10B981; color: #6EE7B7; padding: 0.75rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.88rem; }
    .alert-error { background: rgba(220,38,38,0.2); border: 1px solid #DC2626; color: #FCA5A5; padding: 0.75rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.88rem; }
  </style>
</head>
<body>
  <header>
    <a href="index.php" class="brand"><i class="fa-solid fa-graduation-cap"></i> BRIO ADMIN PANEL</a>
    <div class="nav-links">
      <a href="index.php"><i class="fa-solid fa-chart-pie"></i> Overview</a>
      <a href="users.php"><i class="fa-solid fa-users"></i> Users</a>
      <a href="admissions.php"><i class="fa-solid fa-file-pen"></i> Admissions</a>
      <a href="careers.php"><i class="fa-solid fa-briefcase"></i> Careers</a>
      <a href="vacancies.php"><i class="fa-solid fa-layer-group"></i> Vacancies</a>
      <a href="news.php"><i class="fa-solid fa-newspaper"></i> News &amp; Events</a>
      <a href="tc_manage.php" class="active"><i class="fa-solid fa-file-shield"></i> TC Records</a>
      <a href="enquiries.php"><i class="fa-solid fa-envelope"></i> Enquiries</a>
      <a href="logout.php" style="color: #FCA5A5;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
  </header>

  <div class="container">
    <div class="header-bar">
      <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.8rem; color: white;">
        Transfer Certificate (TC) Records
      </h1>
      <button onclick="openModal('createModal')" class="btn-add"><i class="fa-solid fa-plus"></i> Add Student TC</button>
    </div>

    <?php if (!empty($message)): ?>
      <div class="alert-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Student Name</th>
            <th>TC Number</th>
            <th>Date of Birth (DOB)</th>
            <th>Class</th>
            <th>Campus</th>
            <th>Issue Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tcList as $tc): ?>
            <tr>
              <td>#<?= $tc['id'] ?></td>
              <td><strong><?= htmlspecialchars($tc['student_name'] ?? '') ?></strong></td>
              <td><span style="color: #F59E0B; font-weight: 700;"><?= htmlspecialchars($tc['tc_number'] ?? '') ?></span></td>
              <td><span style="color: #6EE7B7; font-weight: 600;"><?= !empty($tc['dob']) ? date('d/m/Y', strtotime($tc['dob'])) : 'N/A' ?></span></td>
              <td><?= htmlspecialchars($tc['class_name'] ?? '') ?></td>
              <td><?= htmlspecialchars($tc['campus'] ?? 'Gujarat Campus') ?></td>
              <td><?= date('M d, Y', strtotime($tc['issue_date'] ?? 'now')) ?></td>
              <td>
                <?php if (($tc['verification_status'] ?? '') === 'verified'): ?>
                  <span class="badge-verified"><i class="fa-solid fa-check"></i> Verified</span>
                <?php else: ?>
                  <span class="badge-revoked"><i class="fa-solid fa-ban"></i> <?= htmlspecialchars($tc['verification_status'] ?? 'Pending') ?></span>
                <?php endif; ?>
              </td>
              <td>
                <button class="action-btn" onclick='editTC(<?= json_encode($tc) ?>)'><i class="fa-solid fa-pen"></i> Edit / PDF</button>

                <form method="POST" action="tc_manage.php" style="display:inline;" onsubmit="return confirm('Delete this Transfer Certificate record?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $tc['id'] ?>">
                  <button type="submit" class="action-btn delete"><i class="fa-solid fa-trash"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- 1. ADD TC MODAL -->
  <div class="modal" id="createModal">
    <div class="modal-card">
      <h2 style="font-family: 'Outfit', sans-serif; margin-bottom: 1.25rem; color: white;">Add Student Transfer Certificate</h2>
      <form method="POST" action="tc_manage.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="create">

        <div class="form-group">
          <label>Student Full Name *</label>
          <input type="text" name="student_name" required placeholder="e.g. Aarav Sharma">
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label>Unique TC Number *</label>
            <input type="text" name="tc_number" required placeholder="e.g. TC2026/001">
          </div>
          <div class="form-group">
            <label>Date of Birth (DOB) *</label>
            <input type="date" name="dob" required value="2010-01-01">
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label>Admission / Reg No. (Optional)</label>
            <input type="text" name="admission_no" placeholder="e.g. ADM9821">
          </div>
          <div class="form-group">
            <label>Class / Grade *</label>
            <input type="text" name="class_name" required placeholder="e.g. Grade 10">
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label>Issue Date *</label>
            <input type="date" name="issue_date" required value="<?= date('Y-m-d') ?>">
          </div>
          <div class="form-group">
            <label>Campus Location</label>
            <select name="campus">
              <option value="Gujarat Campus">Gujarat Campus (Vadodara)</option>
              <option value="Delhi NCR Campus">Delhi NCR Campus (South Delhi)</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>Verification Status</label>
          <select name="verification_status">
            <option value="verified">Verified Official Record</option>
            <option value="pending">Pending Verification</option>
            <option value="revoked">Revoked / Invalid</option>
          </select>
        </div>

        <div class="form-group" style="background: #0F172A; padding: 1rem; border-radius: 8px; border: 1px dashed #475569;">
          <label><i class="fa-solid fa-file-pdf text-gold"></i> Upload TC Document (.PDF Only) *</label>
          <input type="file" name="tc_pdf" accept=".pdf" required style="padding: 0.5rem; background: #1E293B;">
        </div>

        <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
          <button type="submit" class="btn-add" style="flex: 1;"><i class="fa-solid fa-plus"></i> Upload &amp; Add TC Record</button>
          <button type="button" onclick="closeModal('createModal')" style="background: #475569; color: white; border: none; padding: 0.75rem 1.25rem; border-radius: 8px; font-weight: 600; cursor: pointer;">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <!-- 2. EDIT TC MODAL -->
  <div class="modal" id="editModal">
    <div class="modal-card">
      <h2 style="font-family: 'Outfit', sans-serif; margin-bottom: 1.25rem; color: white;">Edit Transfer Certificate Record</h2>
      <form method="POST" action="tc_manage.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit_id">

        <div class="form-group">
          <label>Student Full Name *</label>
          <input type="text" name="student_name" id="edit_student_name" required>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label>Unique TC Number *</label>
            <input type="text" name="tc_number" id="edit_tc_number" required>
          </div>
          <div class="form-group">
            <label>Date of Birth (DOB) *</label>
            <input type="date" name="dob" id="edit_dob" required>
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label>Admission No</label>
            <input type="text" name="admission_no" id="edit_admission_no">
          </div>
          <div class="form-group">
            <label>Class / Grade *</label>
            <input type="text" name="class_name" id="edit_class_name" required>
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label>Issue Date *</label>
            <input type="date" name="issue_date" id="edit_issue_date" required>
          </div>
          <div class="form-group">
            <label>Campus Location</label>
            <select name="campus" id="edit_campus">
              <option value="Gujarat Campus">Gujarat Campus (Vadodara)</option>
              <option value="Delhi NCR Campus">Delhi NCR Campus (South Delhi)</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>Verification Status</label>
          <select name="verification_status" id="edit_verification_status">
            <option value="verified">Verified Official Record</option>
            <option value="pending">Pending Verification</option>
            <option value="revoked">Revoked / Invalid</option>
          </select>
        </div>

        <div class="form-group" style="background: #0F172A; padding: 1rem; border-radius: 8px; border: 1px dashed #475569;">
          <label><i class="fa-solid fa-file-pdf text-gold"></i> Replace TC PDF Document (Optional)</label>
          <input type="file" name="tc_pdf" accept=".pdf" style="padding: 0.5rem; background: #1E293B;">
        </div>

        <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
          <button type="submit" class="btn-add" style="flex: 1;"><i class="fa-solid fa-check"></i> Update Record</button>
          <button type="button" onclick="closeModal('editModal')" style="background: #475569; color: white; border: none; padding: 0.75rem 1.25rem; border-radius: 8px; font-weight: 600; cursor: pointer;">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openModal(id) {
      document.getElementById(id).classList.add('active');
    }
    function closeModal(id) {
      document.getElementById(id).classList.remove('active');
    }
    function editTC(tc) {
      document.getElementById('edit_id').value = tc.id || '';
      document.getElementById('edit_student_name').value = tc.student_name || '';
      document.getElementById('edit_tc_number').value = tc.tc_number || '';
      document.getElementById('edit_dob').value = tc.dob || '2010-05-15';
      document.getElementById('edit_admission_no').value = tc.admission_no || '';
      document.getElementById('edit_class_name').value = tc.class_name || '';
      document.getElementById('edit_issue_date').value = tc.issue_date || '';
      document.getElementById('edit_campus').value = tc.campus || 'Gujarat Campus';
      document.getElementById('edit_verification_status').value = tc.verification_status || 'verified';
      openModal('editModal');
    }
  </script>
</body>
</html>
