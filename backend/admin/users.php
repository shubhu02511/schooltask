<?php
// ==========================================================================
// BRIO WORLD SCHOOL - Core PHP Admin Users Management
// ==========================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_helper.php';

AuthHelper::requireAdmin();

$db = getCoreDB();
$users = [];

try {
    $users = $db->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
} catch (Exception $e) {
    // Handle error
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Users - BRIO Admin Panel</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Inter', sans-serif; background: #0F172A; color: #F8FAFC; min-height: 100vh; }
    header { background: #1E293B; border-bottom: 1px solid #334155; padding: 1rem 2rem; display: flex; align-items: center; justify-content: space-between; }
    .brand { font-family: 'Outfit', sans-serif; font-size: 1.25rem; font-weight: 800; color: #F59E0B; text-decoration: none; display: flex; align-items: center; gap: 0.6rem; }
    .nav-links a { color: #94A3B8; text-decoration: none; margin-left: 1.5rem; font-size: 0.9rem; font-weight: 600; }
    .nav-links a:hover, .nav-links a.active { color: #F59E0B; }
    .container { padding: 2rem; max-width: 1200px; margin: 0 auto; width: 100%; }
    .card { background: #1E293B; border: 1px solid #334155; border-radius: 12px; padding: 1.5rem; }
    table { width: 100%; border-collapse: collapse; text-align: left; }
    th { background: #0F172A; color: #94A3B8; padding: 0.85rem 1rem; font-size: 0.8rem; text-transform: uppercase; font-weight: 700; border-bottom: 1px solid #334155; }
    td { padding: 1rem; border-bottom: 1px solid #334155; font-size: 0.9rem; color: #E2E8F0; }
    .badge-verified { background: rgba(5,150,105,0.15); color: #10B981; padding: 0.25rem 0.65rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
    .badge-unverified { background: rgba(220,38,38,0.15); color: #FCA5A5; padding: 0.25rem 0.65rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
  </style>
</head>
<body>
  <header>
    <a href="index.php" class="brand"><i class="fa-solid fa-graduation-cap"></i> BRIO ADMIN PANEL</a>
    <div class="nav-links">
      <a href="index.php"><i class="fa-solid fa-chart-pie"></i> Overview</a>
      <a href="users.php" class="active"><i class="fa-solid fa-users"></i> Users</a>
      <a href="admissions.php"><i class="fa-solid fa-file-pen"></i> Admissions</a>
      <a href="careers.php"><i class="fa-solid fa-briefcase"></i> Careers</a>
      <a href="logout.php" style="color: #FCA5A5;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
  </header>

  <div class="container">
    <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.8rem; margin-bottom: 1.5rem; color: white;">
      Registered Portal Users
    </h1>

    <div class="card">
      <?php if (empty($users)): ?>
        <p style="color: #64748B;">No users registered yet.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Full Name</th>
              <th>Email Address</th>
              <th>Role</th>
              <th>Status</th>
              <th>Joined Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
              <tr>
                <td>#<?= $u['id'] ?></td>
                <td><strong><?= htmlspecialchars($u['name'] ?? '') ?></strong></td>
                <td><?= htmlspecialchars($u['email'] ?? '') ?></td>
                <td><span style="text-transform: uppercase; font-size: 0.75rem; font-weight: 700; color: #F59E0B;"><?= htmlspecialchars($u['role'] ?? 'user') ?></span></td>
                <td>
                  <?php if (!empty($u['is_verified'])): ?>
                    <span class="badge-verified"><i class="fa-solid fa-check"></i> Verified</span>
                  <?php else: ?>
                    <span class="badge-unverified"><i class="fa-solid fa-clock"></i> Unverified</span>
                  <?php endif; ?>
                </td>
                <td><?= date('M d, Y', strtotime($u['created_at'] ?? 'now')) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
