<?php
// ==========================================================================
// BRIO WORLD SCHOOL - Core PHP Admin Dashboard Overview
// ==========================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_helper.php';

AuthHelper::requireAdmin();

$db = getCoreDB();

function safeCount($db, $table) {
    if (!$db) return 0;
    try {
        if (method_exists($db, 'query')) {
            $res = $db->query("SELECT COUNT(*) FROM {$table}");
            return $res ? (int)$res->fetchColumn() : 0;
        } else {
            $stmt = $db->prepare("SELECT * FROM {$table}");
            $stmt->execute();
            return count($stmt->fetchAll() ?: []);
        }
    } catch (Exception $e) {
        return 0;
    }
}

function safeFetchRecent($db, $sql) {
    if (!$db) return [];
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    } catch (Exception $e) {
        return [];
    }
}

$userCount = safeCount($db, 'users');
$admCount = safeCount($db, 'admissions');
$carCount = safeCount($db, 'careers');
$conCount = safeCount($db, 'contacts');

$recentAdmissions = safeFetchRecent($db, "SELECT * FROM admissions ORDER BY id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - BRIO World School</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Inter', sans-serif; background: #0F172A; color: #F8FAFC; min-height: 100vh; display: flex; flex-direction: column; }
    header { background: #1E293B; border-bottom: 1px solid #334155; padding: 1rem 2rem; display: flex; align-items: center; justify-content: space-between; }
    .brand { font-family: 'Outfit', sans-serif; font-size: 1.25rem; font-weight: 800; color: #F59E0B; text-decoration: none; display: flex; align-items: center; gap: 0.6rem; }
    .nav-links a { color: #94A3B8; text-decoration: none; margin-left: 1.5rem; font-size: 0.9rem; font-weight: 600; transition: color 0.2s; }
    .nav-links a:hover, .nav-links a.active { color: #F59E0B; }
    .container { padding: 2rem; max-width: 1200px; margin: 0 auto; width: 100%; }
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; margin-bottom: 2.5rem; }
    .stat-card { background: #1E293B; border: 1px solid #334155; border-radius: 12px; padding: 1.5rem; }
    .stat-card i { font-size: 1.8rem; color: #F59E0B; margin-bottom: 0.75rem; }
    .stat-num { font-family: 'Outfit', sans-serif; font-size: 2rem; font-weight: 800; color: white; }
    .stat-label { color: #94A3B8; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; }
    .card { background: #1E293B; border: 1px solid #334155; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; }
    .card-header { font-family: 'Outfit', sans-serif; font-size: 1.2rem; font-weight: 700; color: white; margin-bottom: 1.25rem; display: flex; align-items: center; justify-content: space-between; }
    table { width: 100%; border-collapse: collapse; text-align: left; }
    th { background: #0F172A; color: #94A3B8; padding: 0.85rem 1rem; font-size: 0.8rem; text-transform: uppercase; font-weight: 700; border-bottom: 1px solid #334155; }
    td { padding: 1rem; border-bottom: 1px solid #334155; font-size: 0.9rem; color: #E2E8F0; }
    .status-badge { padding: 0.25rem 0.65rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; background: rgba(245,158,11,0.15); color: #F59E0B; }
    @media (max-width: 768px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } table { display: block; overflow-x: auto; } }
  </style>
</head>
<body>
  <header>
    <a href="index.php" class="brand"><i class="fa-solid fa-graduation-cap"></i> BRIO ADMIN PANEL</a>
    <div class="nav-links">
      <a href="index.php" class="active"><i class="fa-solid fa-chart-pie"></i> Overview</a>
      <a href="users.php"><i class="fa-solid fa-users"></i> Users</a>
      <a href="admissions.php"><i class="fa-solid fa-file-pen"></i> Admissions</a>
      <a href="careers.php"><i class="fa-solid fa-briefcase"></i> Careers</a>
      <a href="vacancies.php"><i class="fa-solid fa-layer-group"></i> Vacancies</a>
      <a href="news.php"><i class="fa-solid fa-newspaper"></i> News &amp; Events</a>
      <a href="enquiries.php"><i class="fa-solid fa-envelope"></i> Enquiries</a>
      <a href="logout.php" style="color: #FCA5A5;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
  </header>

  <div class="container">
    <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.8rem; margin-bottom: 1.5rem; color: white;">
      Welcome back, <?= htmlspecialchars($_SESSION['brio_user_name'] ?? 'Admin') ?>!
    </h1>

    <div class="stats-grid">
      <div class="stat-card">
        <i class="fa-solid fa-users"></i>
        <div class="stat-num"><?= $userCount ?></div>
        <div class="stat-label">Registered Users</div>
      </div>
      <div class="stat-card">
        <i class="fa-solid fa-file-pen"></i>
        <div class="stat-num"><?= $admCount ?></div>
        <div class="stat-label">Admissions</div>
      </div>
      <div class="stat-card">
        <i class="fa-solid fa-briefcase"></i>
        <div class="stat-num"><?= $carCount ?></div>
        <div class="stat-label">Career Applicants</div>
      </div>
      <div class="stat-card">
        <i class="fa-solid fa-envelope"></i>
        <div class="stat-num"><?= $conCount ?></div>
        <div class="stat-label">Messages</div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <span><i class="fa-solid fa-clock-rotate-left"></i> Recent Admission Applications</span>
        <a href="admissions.php" style="font-size: 0.85rem; color: #F59E0B; text-decoration: none;">View All &rarr;</a>
      </div>
      <?php if (empty($recentAdmissions)): ?>
        <p style="color: #64748B;">No admission applications recorded yet.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Student Name</th>
              <th>Parent Name</th>
              <th>Grade</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentAdmissions as $adm): ?>
              <tr>
                <td>#<?= $adm['id'] ?></td>
                <td><strong><?= htmlspecialchars($adm['student_name'] ?? '') ?></strong></td>
                <td><?= htmlspecialchars($adm['parent_name'] ?? '') ?></td>
                <td><span class="status-badge"><?= htmlspecialchars($adm['grade'] ?? '') ?></span></td>
                <td><?= htmlspecialchars($adm['email'] ?? '') ?></td>
                <td><?= htmlspecialchars($adm['phone'] ?? '') ?></td>
                <td><?= date('M d, Y', strtotime($adm['created_at'] ?? 'now')) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
