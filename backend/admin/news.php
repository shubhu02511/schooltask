<?php
// ==========================================================================
// BRIO WORLD SCHOOL - Core PHP Admin News & Events Management System
// Actions: Create, Edit, Delete, Publish / Unpublish
// ==========================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_helper.php';

AuthHelper::requireAdmin();

$db = getCoreDB();
$message = '';
$error = '';

// --------------------------------------------------------------------------
// HANDLE ACTIONS (CREATE, EDIT, DELETE, TOGGLE STATUS)
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = cleanInput($_POST['action'] ?? '');
    
    // 1. CREATE NEWS / EVENT
    if ($action === 'create') {
        $title = cleanInput($_POST['title'] ?? '');
        $description = cleanInput($_POST['description'] ?? '');
        $category = cleanInput($_POST['category'] ?? 'General');
        $eventDate = cleanInput($_POST['event_date'] ?? date('Y-m-d'));
        $imagePath = cleanInput($_POST['image_url'] ?? '');
        $status = cleanInput($_POST['status'] ?? 'published');

        // Handle uploaded image file
        if (!empty($_FILES['image_file']['name'])) {
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $fileExt = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
            if (in_array($fileExt, $allowedExts)) {
                $uploadDir = __DIR__ . '/../../storage/uploads/news/';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }
                $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $_FILES['image_file']['name']);
                if (move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadDir . $filename)) {
                    $imagePath = '/storage/uploads/news/' . $filename;
                }
            }
        }

        if (empty($title) || empty($description)) {
            $error = 'Title and Description are required.';
        } else {
            // Save to MySQL
            try {
                if ($db) {
                    $stmt = $db->prepare("INSERT INTO news_events (title, description, category, event_date, image_path, status) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $description, $category, $eventDate, $imagePath, $status]);
                }
            } catch (Exception $e) {}

            // Save to JSON Backup File
            $jsonFile = __DIR__ . '/../../storage/database/news_events.json';
            $existing = file_exists($jsonFile) ? (json_decode(file_get_contents($jsonFile), true) ?: []) : [];
            $newEntry = [
                'id' => time(),
                'title' => $title,
                'description' => $description,
                'category' => $category,
                'event_date' => $eventDate,
                'image_path' => $imagePath,
                'status' => $status,
                'created_at' => date('Y-m-d H:i:s')
            ];
            array_unshift($existing, $newEntry);
            @file_put_contents($jsonFile, json_encode($existing, JSON_PRETTY_PRINT));

            $message = 'News/Event created successfully!';
        }
    }

    // 2. EDIT NEWS / EVENT
    elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $title = cleanInput($_POST['title'] ?? '');
        $description = cleanInput($_POST['description'] ?? '');
        $category = cleanInput($_POST['category'] ?? 'General');
        $eventDate = cleanInput($_POST['event_date'] ?? date('Y-m-d'));
        $imagePath = cleanInput($_POST['image_url'] ?? '');
        $status = cleanInput($_POST['status'] ?? 'published');

        if (!empty($_FILES['image_file']['name'])) {
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $fileExt = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
            if (in_array($fileExt, $allowedExts)) {
                $uploadDir = __DIR__ . '/../../storage/uploads/news/';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }
                $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $_FILES['image_file']['name']);
                if (move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadDir . $filename)) {
                    $imagePath = '/storage/uploads/news/' . $filename;
                }
            }
        }

        if ($id > 0 && !empty($title)) {
            try {
                if ($db) {
                    $stmt = $db->prepare("UPDATE news_events SET title=?, description=?, category=?, event_date=?, image_path=?, status=? WHERE id=?");
                    $stmt->execute([$title, $description, $category, $eventDate, $imagePath, $status, $id]);
                }
            } catch (Exception $e) {}

            $jsonFile = __DIR__ . '/../../storage/database/news_events.json';
            if (file_exists($jsonFile)) {
                $all = json_decode(file_get_contents($jsonFile), true) ?: [];
                foreach ($all as &$n) {
                    if (($n['id'] ?? 0) == $id) {
                        $n['title'] = $title;
                        $n['description'] = $description;
                        $n['category'] = $category;
                        $n['event_date'] = $eventDate;
                        if (!empty($imagePath)) $n['image_path'] = $imagePath;
                        $n['status'] = $status;
                    }
                }
                @file_put_contents($jsonFile, json_encode($all, JSON_PRETTY_PRINT));
            }
            $message = 'News/Event updated successfully!';
        }
    }

    // 3. TOGGLE PUBLISH / UNPUBLISH
    elseif ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        $newStatus = cleanInput($_POST['new_status'] ?? 'published');

        if ($id > 0) {
            try {
                if ($db) {
                    $stmt = $db->prepare("UPDATE news_events SET status=? WHERE id=?");
                    $stmt->execute([$newStatus, $id]);
                }
            } catch (Exception $e) {}

            $jsonFile = __DIR__ . '/../../storage/database/news_events.json';
            if (file_exists($jsonFile)) {
                $all = json_decode(file_get_contents($jsonFile), true) ?: [];
                foreach ($all as &$n) {
                    if (($n['id'] ?? 0) == $id) {
                        $n['status'] = $newStatus;
                    }
                }
                @file_put_contents($jsonFile, json_encode($all, JSON_PRETTY_PRINT));
            }
            $message = 'Status updated to ' . strtoupper($newStatus) . '!';
        }
    }

    // 4. DELETE NEWS / EVENT
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                if ($db) {
                    $stmt = $db->prepare("DELETE FROM news_events WHERE id=?");
                    $stmt->execute([$id]);
                }
            } catch (Exception $e) {}

            $jsonFile = __DIR__ . '/../../storage/database/news_events.json';
            if (file_exists($jsonFile)) {
                $all = json_decode(file_get_contents($jsonFile), true) ?: [];
                $filtered = array_values(array_filter($all, fn($n) => ($n['id'] ?? 0) != $id));
                @file_put_contents($jsonFile, json_encode($filtered, JSON_PRETTY_PRINT));
            }
            $message = 'News item deleted successfully.';
        }
    }
}

// --------------------------------------------------------------------------
// FETCH NEWS LIST (MySQL + JSON Backup)
// --------------------------------------------------------------------------
$newsItems = [];
try {
    if ($db) {
        $stmt = $db->prepare("SELECT * FROM news_events ORDER BY id DESC");
        $stmt->execute();
        $newsItems = $stmt->fetchAll() ?: [];
    }
} catch (Exception $e) {}

$jsonFile = __DIR__ . '/../../storage/database/news_events.json';
if (empty($newsItems) && file_exists($jsonFile)) {
    $newsItems = json_decode(file_get_contents($jsonFile), true) ?: [];
}

// Seed initial items if completely empty
if (empty($newsItems)) {
    $newsItems = [
        ['id' => 1, 'title' => 'National Science & Robotics Expo 2026', 'description' => 'Over 50 innovative student projects featured in our annual STEM exhibition.', 'category' => 'STEM & Innovation', 'event_date' => '2026-08-12', 'image_path' => 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?auto=format&fit=crop&w=600&q=80', 'status' => 'published'],
        ['id' => 2, 'title' => 'B-SAT Scholarship Entrance Test Announced', 'description' => 'Registration opens for Pre-K to Grade 11 scholarship entrance test for 2026-27.', 'category' => 'Admissions', 'event_date' => '2026-09-01', 'image_path' => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=600&q=80', 'status' => 'published'],
        ['id' => 3, 'title' => 'Inter-School Athletics & Aquatics Meet', 'description' => 'Over 500 athletes competed in track events, 50m swimming trials, and football finals.', 'category' => 'Sports & Athletics', 'event_date' => '2026-07-25', 'image_path' => 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?auto=format&fit=crop&w=600&q=80', 'status' => 'published'],
        ['id' => 4, 'title' => 'Annual Cultural Fest & Musical Gala', 'description' => 'Classical orchestra, theatrical drama plays, and choir performances in the grand auditorium.', 'category' => 'Arts & Culture', 'event_date' => '2026-07-18', 'image_path' => 'https://images.unsplash.com/photo-1469488865564-c2de10f69f96?auto=format&fit=crop&w=600&q=80', 'status' => 'published']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage News &amp; Events - BRIO Admin Panel</title>
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
    .thumb-img { width: 50px; height: 50px; border-radius: 8px; object-fit: cover; border: 1px solid #475569; }
    .badge-published { background: rgba(16,185,129,0.15); color: #10B981; padding: 0.25rem 0.65rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
    .badge-draft { background: rgba(239,68,68,0.15); color: #FCA5A5; padding: 0.25rem 0.65rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
    .action-btn { background: #334155; color: white; border: none; padding: 0.4rem 0.75rem; border-radius: 6px; font-size: 0.8rem; font-weight: 600; cursor: pointer; margin-right: 0.35rem; }
    .action-btn:hover { background: #475569; }
    .action-btn.delete { background: rgba(220,38,38,0.2); color: #FCA5A5; }
    .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 1000; align-items: center; justify-content: center; padding: 1.5rem; }
    .modal.active { display: flex; }
    .modal-card { background: #1E293B; border: 1px solid #334155; border-radius: 16px; width: 100%; max-width: 650px; padding: 2rem; max-height: 90vh; overflow-y: auto; }
    .form-group { margin-bottom: 1rem; }
    label { display: block; font-size: 0.82rem; font-weight: 700; color: #CBD5E1; margin-bottom: 0.35rem; text-transform: uppercase; }
    input, select, textarea { width: 100%; padding: 0.75rem 0.9rem; border-radius: 8px; border: 1px solid #475569; background: #0F172A; color: white; outline: none; font-size: 0.9rem; }
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
      <a href="news.php" class="active"><i class="fa-solid fa-newspaper"></i> News &amp; Events</a>
      <a href="enquiries.php"><i class="fa-solid fa-envelope"></i> Enquiries</a>
      <a href="logout.php" style="color: #FCA5A5;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
  </header>

  <div class="container">
    <div class="header-bar">
      <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.8rem; color: white;">
        News &amp; Events Management
      </h1>
      <button onclick="openModal('createModal')" class="btn-add"><i class="fa-solid fa-plus"></i> Create News / Event</button>
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
            <th>Cover Image</th>
            <th>Title</th>
            <th>Category</th>
            <th>Event Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($newsItems as $n): ?>
            <tr>
              <td>#<?= $n['id'] ?></td>
              <td>
                <img src="<?= htmlspecialchars($n['image_path'] ?: 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?auto=format&fit=crop&w=200&q=80') ?>" class="thumb-img" alt="Cover">
              </td>
              <td>
                <strong style="color: white; font-size: 0.95rem;"><?= htmlspecialchars($n['title'] ?? '') ?></strong>
                <div style="font-size: 0.78rem; color: #94A3B8; margin-top: 2px;">
                  <?= htmlspecialchars(substr($n['description'] ?? '', 0, 60)) ?>...
                </div>
              </td>
              <td><span style="color: #F59E0B; font-weight: 700; font-size: 0.8rem; text-transform: uppercase;"><?= htmlspecialchars($n['category'] ?? 'General') ?></span></td>
              <td><?= date('M d, Y', strtotime($n['event_date'] ?? 'now')) ?></td>
              <td>
                <?php if (($n['status'] ?? '') === 'published'): ?>
                  <span class="badge-published"><i class="fa-solid fa-globe"></i> Published</span>
                <?php else: ?>
                  <span class="badge-draft"><i class="fa-solid fa-eye-slash"></i> Draft</span>
                <?php endif; ?>
              </td>
              <td>
                <button class="action-btn" onclick='editNews(<?= json_encode($n) ?>)'><i class="fa-solid fa-pen"></i> Edit</button>

                <form method="POST" action="news.php" style="display:inline;">
                  <input type="hidden" name="action" value="toggle_status">
                  <input type="hidden" name="id" value="<?= $n['id'] ?>">
                  <input type="hidden" name="new_status" value="<?= ($n['status'] ?? '') === 'published' ? 'draft' : 'published' ?>">
                  <button type="submit" class="action-btn" title="Toggle Publish Status">
                    <i class="fa-solid <?= ($n['status'] ?? '') === 'published' ? 'fa-eye-slash' : 'fa-globe' ?>"></i>
                    <?= ($n['status'] ?? '') === 'published' ? 'Unpublish' : 'Publish' ?>
                  </button>
                </form>

                <form method="POST" action="news.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this news item?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $n['id'] ?>">
                  <button type="submit" class="action-btn delete"><i class="fa-solid fa-trash"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- 1. CREATE NEWS MODAL -->
  <div class="modal" id="createModal">
    <div class="modal-card">
      <h2 style="font-family: 'Outfit', sans-serif; margin-bottom: 1.25rem; color: white;">Create News / Event</h2>
      <form method="POST" action="news.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="create">

        <div class="form-group">
          <label>Title *</label>
          <input type="text" name="title" required placeholder="e.g. National Robotics &amp; AI Championship 2026">
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label>Category *</label>
            <input type="text" name="category" required placeholder="e.g. STEM &amp; Innovation, Sports, Arts">
          </div>
          <div class="form-group">
            <label>Event Date *</label>
            <input type="date" name="event_date" required value="<?= date('Y-m-d') ?>">
          </div>
        </div>

        <div class="form-group">
          <label>Cover Image URL (or upload below)</label>
          <input type="url" name="image_url" placeholder="https://images.unsplash.com/photo-...">
        </div>

        <div class="form-group">
          <label>Or Upload Cover Image File</label>
          <input type="file" name="image_file" accept="image/*" style="padding: 0.5rem; background: #0F172A;">
        </div>

        <div class="form-group">
          <label>Description / Details *</label>
          <textarea name="description" rows="4" required placeholder="Event summary, highlights, venue, timing..."></textarea>
        </div>

        <div class="form-group">
          <label>Publish Status</label>
          <select name="status">
            <option value="published">Published (Visible on Website)</option>
            <option value="draft">Draft (Hidden)</option>
          </select>
        </div>

        <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
          <button type="submit" class="btn-add" style="flex: 1;"><i class="fa-solid fa-plus"></i> Save &amp; Create Event</button>
          <button type="button" onclick="closeModal('createModal')" style="background: #475569; color: white; border: none; padding: 0.75rem 1.25rem; border-radius: 8px; font-weight: 600; cursor: pointer;">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <!-- 2. EDIT NEWS MODAL -->
  <div class="modal" id="editModal">
    <div class="modal-card">
      <h2 style="font-family: 'Outfit', sans-serif; margin-bottom: 1.25rem; color: white;">Edit News / Event</h2>
      <form method="POST" action="news.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit_id">

        <div class="form-group">
          <label>Title *</label>
          <input type="text" name="title" id="edit_title" required>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label>Category *</label>
            <input type="text" name="category" id="edit_category" required>
          </div>
          <div class="form-group">
            <label>Event Date *</label>
            <input type="date" name="event_date" id="edit_event_date" required>
          </div>
        </div>

        <div class="form-group">
          <label>Cover Image URL</label>
          <input type="url" name="image_url" id="edit_image_url">
        </div>

        <div class="form-group">
          <label>Or Upload New Cover Image File</label>
          <input type="file" name="image_file" accept="image/*" style="padding: 0.5rem; background: #0F172A;">
        </div>

        <div class="form-group">
          <label>Description / Details *</label>
          <textarea name="description" id="edit_description" rows="4" required></textarea>
        </div>

        <div class="form-group">
          <label>Publish Status</label>
          <select name="status" id="edit_status">
            <option value="published">Published (Visible on Website)</option>
            <option value="draft">Draft (Hidden)</option>
          </select>
        </div>

        <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
          <button type="submit" class="btn-add" style="flex: 1;"><i class="fa-solid fa-check"></i> Update Event</button>
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
    function editNews(n) {
      document.getElementById('edit_id').value = n.id || '';
      document.getElementById('edit_title').value = n.title || '';
      document.getElementById('edit_category').value = n.category || '';
      document.getElementById('edit_event_date').value = n.event_date || '';
      document.getElementById('edit_image_url').value = n.image_path || '';
      document.getElementById('edit_description').value = n.description || '';
      document.getElementById('edit_status').value = n.status || 'published';
      openModal('editModal');
    }
  </script>
</body>
</html>
