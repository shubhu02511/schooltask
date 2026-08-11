<?php
// ==========================================================================
// BRIO WORLD SCHOOL - Core PHP Admin Enquiries / Messages Management
// Dual Fetch: MySQL + Backup JSON Storage
// ==========================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_helper.php';

AuthHelper::requireAdmin();

$db = getCoreDB();

$enquiries = [];

// 1. Try MySQL Database Fetch
try {
    if ($db) {
        $stmt = $db->prepare("SELECT * FROM contacts ORDER BY id DESC");
        $stmt->execute();
        $enquiries = $stmt->fetchAll() ?: [];
    }
} catch (Exception $e) {
    // MySQL table not ready
}

// 2. Try JSON File Fallback Fetch if MySQL is empty or table doesn't exist
$jsonFile = __DIR__ . '/../../storage/database/contacts.json';
if (empty($enquiries) && file_exists($jsonFile)) {
    $content = file_get_contents($jsonFile);
    $enquiries = json_decode($content, true) ?: [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Contact Enquiries - BRIO Admin Panel</title>
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
    .card { background: #1E293B; border: 1px solid #334155; border-radius: 12px; padding: 1.5rem; }
    table { width: 100%; border-collapse: collapse; text-align: left; }
    th { background: #0F172A; color: #94A3B8; padding: 0.85rem 1rem; font-size: 0.8rem; text-transform: uppercase; font-weight: 700; border-bottom: 1px solid #334155; }
    td { padding: 1rem; border-bottom: 1px solid #334155; font-size: 0.9rem; color: #E2E8F0; vertical-align: top; }
    .badge-subject { background: rgba(245,158,11,0.15); color: #F59E0B; padding: 0.25rem 0.65rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700; display: inline-block; }
    .msg-box { background: #0F172A; border: 1px solid #334155; padding: 0.75rem; border-radius: 8px; font-size: 0.85rem; color: #CBD5E1; max-width: 350px; line-height: 1.5; }
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
      <a href="enquiries.php" class="active"><i class="fa-solid fa-envelope"></i> Enquiries</a>
      <a href="logout.php" style="color: #FCA5A5;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
  </header>

  <div class="container">
    <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.8rem; margin-bottom: 1.5rem; color: white;">
      Contact Form Enquiries
    </h1>

    <div class="card">
      <?php if (empty($enquiries)): ?>
        <p style="color: #64748B;">No contact enquiries recorded yet.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Subject</th>
              <th>Message</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($enquiries as $eq): ?>
              <tr>
                <td>#<?= $eq['id'] ?></td>
                <td><strong><?= htmlspecialchars($eq['name'] ?? '') ?></strong></td>
                <td><?= htmlspecialchars($eq['email'] ?? '') ?></td>
                <td><?= htmlspecialchars($eq['phone'] ?? '') ?></td>
                <td><span class="badge-subject"><?= htmlspecialchars($eq['subject'] ?? 'General Inquiry') ?></span></td>
                <td><div class="msg-box"><?= nl2br(htmlspecialchars($eq['message'] ?? '')) ?></div></td>
                <td><?= date('M d, Y', strtotime($eq['created_at'] ?? 'now')) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
