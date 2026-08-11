<?php
// ==========================================================================
// BRIO WORLD SCHOOL - Core PHP Admin Vacancy & Job Management System
// Actions: Create, Edit, Delete, Publish / Unpublish with Slug Generation
// ==========================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_helper.php';

AuthHelper::requireAdmin();

$db = getCoreDB();
$message = '';
$error = '';

// Helper function to create URL-friendly unique slug
function createSlug($title) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    return trim($slug, '-');
}

// --------------------------------------------------------------------------
// HANDLE ACTIONS (CREATE, EDIT, DELETE, TOGGLE STATUS)
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = cleanInput($_POST['action'] ?? '');
    
    // 1. CREATE VACANCY
    if ($action === 'create') {
        $jobTitle = cleanInput($_POST['job_title'] ?? '');
        $position = cleanInput($_POST['position'] ?? 'Faculty Educator');
        $qualification = cleanInput($_POST['qualification'] ?? '');
        $experience = cleanInput($_POST['experience'] ?? '0+ Years');
        $location = cleanInput($_POST['location'] ?? 'Gujarat & Delhi Campuses');
        $jobType = cleanInput($_POST['job_type'] ?? 'Full-Time');
        $description = cleanInput($_POST['description'] ?? '');
        $requirements = cleanInput($_POST['requirements'] ?? '');
        $status = cleanInput($_POST['status'] ?? 'published');

        if (empty($jobTitle) || empty($qualification)) {
            $error = 'Job Title and Qualification are required.';
        } else {
            $slug = createSlug($jobTitle);
            $saved = false;

            // MySQL Save
            try {
                if ($db) {
                    // Check duplicate slug
                    $check = $db->prepare("SELECT id FROM vacancies WHERE slug = ?");
                    $check->execute([$slug]);
                    if ($check->fetch()) {
                        $slug .= '-' . time();
                    }

                    $stmt = $db->prepare("INSERT INTO vacancies (job_title, position, qualification, experience, location, job_type, description, requirements, slug, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $saved = $stmt->execute([$jobTitle, $position, $qualification, $experience, $location, $jobType, $description, $requirements, $slug, $status]);
                }
            } catch (Exception $e) {
                // Ignore DB error, use JSON fallback
            }

            // JSON Backup Save
            $jsonFile = __DIR__ . '/../../storage/database/vacancies.json';
            $existing = file_exists($jsonFile) ? (json_decode(file_get_contents($jsonFile), true) ?: []) : [];
            $newVac = [
                'id' => time(),
                'job_title' => $jobTitle,
                'position' => $position,
                'qualification' => $qualification,
                'experience' => $experience,
                'location' => $location,
                'job_type' => $jobType,
                'description' => $description,
                'requirements' => $requirements,
                'slug' => $slug,
                'status' => $status,
                'created_at' => date('Y-m-d H:i:s')
            ];
            array_unshift($existing, $newVac);
            @file_put_contents($jsonFile, json_encode($existing, JSON_PRETTY_PRINT));

            $message = 'Vacancy created successfully!';
        }
    }

    // 2. EDIT VACANCY
    elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $jobTitle = cleanInput($_POST['job_title'] ?? '');
        $position = cleanInput($_POST['position'] ?? 'Faculty Educator');
        $qualification = cleanInput($_POST['qualification'] ?? '');
        $experience = cleanInput($_POST['experience'] ?? '0+ Years');
        $location = cleanInput($_POST['location'] ?? 'Gujarat & Delhi Campuses');
        $jobType = cleanInput($_POST['job_type'] ?? 'Full-Time');
        $description = cleanInput($_POST['description'] ?? '');
        $requirements = cleanInput($_POST['requirements'] ?? '');
        $status = cleanInput($_POST['status'] ?? 'published');

        if ($id > 0 && !empty($jobTitle)) {
            try {
                if ($db) {
                    $stmt = $db->prepare("UPDATE vacancies SET job_title=?, position=?, qualification=?, experience=?, location=?, job_type=?, description=?, requirements=?, status=? WHERE id=?");
                    $stmt->execute([$jobTitle, $position, $qualification, $experience, $location, $jobType, $description, $requirements, $status, $id]);
                }
            } catch (Exception $e) {}

            // JSON Backup Update
            $jsonFile = __DIR__ . '/../../storage/database/vacancies.json';
            if (file_exists($jsonFile)) {
                $all = json_decode(file_get_contents($jsonFile), true) ?: [];
                foreach ($all as &$v) {
                    if (($v['id'] ?? 0) == $id) {
                        $v['job_title'] = $jobTitle;
                        $v['position'] = $position;
                        $v['qualification'] = $qualification;
                        $v['experience'] = $experience;
                        $v['location'] = $location;
                        $v['job_type'] = $jobType;
                        $v['description'] = $description;
                        $v['requirements'] = $requirements;
                        $v['status'] = $status;
                    }
                }
                @file_put_contents($jsonFile, json_encode($all, JSON_PRETTY_PRINT));
            }
            $message = 'Vacancy updated successfully!';
        }
    }

    // 3. TOGGLE PUBLISH / UNPUBLISH STATUS
    elseif ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        $newStatus = cleanInput($_POST['new_status'] ?? 'published');

        if ($id > 0) {
            try {
                if ($db) {
                    $stmt = $db->prepare("UPDATE vacancies SET status=? WHERE id=?");
                    $stmt->execute([$newStatus, $id]);
                }
            } catch (Exception $e) {}

            $jsonFile = __DIR__ . '/../../storage/database/vacancies.json';
            if (file_exists($jsonFile)) {
                $all = json_decode(file_get_contents($jsonFile), true) ?: [];
                foreach ($all as &$v) {
                    if (($v['id'] ?? 0) == $id) {
                        $v['status'] = $newStatus;
                    }
                }
                @file_put_contents($jsonFile, json_encode($all, JSON_PRETTY_PRINT));
            }
            $message = 'Vacancy status updated to ' . strtoupper($newStatus) . '!';
        }
    }

    // 4. DELETE VACANCY
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                if ($db) {
                    $stmt = $db->prepare("DELETE FROM vacancies WHERE id=?");
                    $stmt->execute([$id]);
                }
            } catch (Exception $e) {}

            $jsonFile = __DIR__ . '/../../storage/database/vacancies.json';
            if (file_exists($jsonFile)) {
                $all = json_decode(file_get_contents($jsonFile), true) ?: [];
                $filtered = array_values(array_filter($all, fn($v) => ($v['id'] ?? 0) != $id));
                @file_put_contents($jsonFile, json_encode($filtered, JSON_PRETTY_PRINT));
            }
            $message = 'Vacancy deleted successfully.';
        }
    }
}

// --------------------------------------------------------------------------
// FETCH VACANCIES LIST (MySQL + JSON Backup)
// --------------------------------------------------------------------------
$vacancies = [];
try {
    if ($db) {
        $stmt = $db->prepare("SELECT * FROM vacancies ORDER BY id DESC");
        $stmt->execute();
        $vacancies = $stmt->fetchAll() ?: [];
    }
} catch (Exception $e) {}

$jsonFile = __DIR__ . '/../../storage/database/vacancies.json';
if (empty($vacancies) && file_exists($jsonFile)) {
    $vacancies = json_decode(file_get_contents($jsonFile), true) ?: [];
}

// If completely empty, insert default seed vacancies
if (empty($vacancies)) {
    $vacancies = [
        ['id' => 1, 'job_title' => 'PGT Physics & JEE Foundation Lead', 'position' => 'Senior Secondary Wing', 'qualification' => 'M.Sc. Physics & B.Ed', 'experience' => '5+ Years', 'location' => 'Gujarat & Delhi Campuses', 'job_type' => 'Full-Time', 'slug' => 'pgt-physics-jee-foundation-lead', 'status' => 'published', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 2, 'job_title' => 'AI & Robotics STEM Coach', 'position' => 'STEM & AI Innovation', 'qualification' => 'B.Tech / M.Tech (CS / Robotics)', 'experience' => '3+ Years', 'location' => 'Vadodara, Gujarat Campus', 'job_type' => 'Full-Time', 'slug' => 'ai-robotics-stem-coach', 'status' => 'published', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 3, 'job_title' => 'TGT English & Drama Facilitator', 'position' => 'Middle School Wing', 'qualification' => 'M.A. English & B.Ed', 'experience' => '4+ Years', 'location' => 'South Delhi, NCR Campus', 'job_type' => 'Full-Time', 'slug' => 'tgt-english-drama-facilitator', 'status' => 'published', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 4, 'job_title' => 'Head Aquatics & Swimming Coach', 'position' => 'Sports Academy', 'qualification' => 'NSNIS Diploma in Swimming', 'experience' => '5+ Years', 'location' => 'Vadodara, Gujarat Campus', 'job_type' => 'Full-Time', 'slug' => 'head-aquatics-swimming-coach', 'status' => 'published', 'created_at' => date('Y-m-d H:i:s')]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Vacancies - BRIO Admin Panel</title>
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
      <a href="vacancies.php" class="active"><i class="fa-solid fa-layer-group"></i> Vacancies</a>
      <a href="enquiries.php"><i class="fa-solid fa-envelope"></i> Enquiries</a>
      <a href="logout.php" style="color: #FCA5A5;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
  </header>

  <div class="container">
    <div class="header-bar">
      <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.8rem; color: white;">
        Job / Vacancy Management
      </h1>
      <button onclick="openModal('createModal')" class="btn-add"><i class="fa-solid fa-plus"></i> Create New Vacancy</button>
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
            <th>Job Title &amp; Slug</th>
            <th>Position / Wing</th>
            <th>Qualification</th>
            <th>Experience</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($vacancies as $v): ?>
            <tr>
              <td>#<?= $v['id'] ?></td>
              <td>
                <strong style="color: white; font-size: 0.95rem;"><?= htmlspecialchars($v['job_title'] ?? '') ?></strong>
                <div style="font-size: 0.75rem; color: #F59E0B; margin-top: 2px;">
                  <i class="fa-solid fa-link"></i> /careers/<?= htmlspecialchars($v['slug'] ?? '') ?>
                </div>
              </td>
              <td><?= htmlspecialchars($v['position'] ?? 'Faculty Educator') ?></td>
              <td><?= htmlspecialchars($v['qualification'] ?? '') ?></td>
              <td><?= htmlspecialchars($v['experience'] ?? '') ?></td>
              <td>
                <?php if (($v['status'] ?? '') === 'published'): ?>
                  <span class="badge-published"><i class="fa-solid fa-globe"></i> Published</span>
                <?php else: ?>
                  <span class="badge-draft"><i class="fa-solid fa-eye-slash"></i> Draft</span>
                <?php endif; ?>
              </td>
              <td>
                <button class="action-btn" onclick='editVacancy(<?= json_encode($v) ?>)'><i class="fa-solid fa-pen"></i> Edit</button>

                <form method="POST" action="vacancies.php" style="display:inline;">
                  <input type="hidden" name="action" value="toggle_status">
                  <input type="hidden" name="id" value="<?= $v['id'] ?>">
                  <input type="hidden" name="new_status" value="<?= ($v['status'] ?? '') === 'published' ? 'draft' : 'published' ?>">
                  <button type="submit" class="action-btn" title="Toggle Publish Status">
                    <i class="fa-solid <?= ($v['status'] ?? '') === 'published' ? 'fa-eye-slash' : 'fa-globe' ?>"></i>
                    <?= ($v['status'] ?? '') === 'published' ? 'Unpublish' : 'Publish' ?>
                  </button>
                </form>

                <form method="POST" action="vacancies.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this vacancy?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $v['id'] ?>">
                  <button type="submit" class="action-btn delete"><i class="fa-solid fa-trash"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- 1. CREATE VACANCY MODAL -->
  <div class="modal" id="createModal">
    <div class="modal-card">
      <h2 style="font-family: 'Outfit', sans-serif; margin-bottom: 1.25rem; color: white;">Create New Job Vacancy</h2>
      <form method="POST" action="vacancies.php">
        <input type="hidden" name="action" value="create">

        <div class="form-group">
          <label>Job Title *</label>
          <input type="text" name="job_title" required placeholder="e.g. PGT Mathematics &amp; Olympiad Coach">
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label>Position / Wing *</label>
            <input type="text" name="position" required placeholder="e.g. Senior Secondary Wing">
          </div>
          <div class="form-group">
            <label>Qualification *</label>
            <input type="text" name="qualification" required placeholder="e.g. M.Sc. Math &amp; B.Ed">
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label>Experience Required *</label>
            <input type="text" name="experience" required placeholder="e.g. 3+ Years">
          </div>
          <div class="form-group">
            <label>Job Type *</label>
            <select name="job_type">
              <option value="Full-Time">Full-Time</option>
              <option value="Part-Time">Part-Time</option>
              <option value="Contract">Contractual</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>Campus Location</label>
          <input type="text" name="location" value="Gujarat &amp; Delhi Campuses" placeholder="e.g. Vadodara, Gujarat Campus">
        </div>

        <div class="form-group">
          <label>Job Description</label>
          <textarea name="description" rows="3" placeholder="Overview of key responsibilities and curriculum goals..."></textarea>
        </div>

        <div class="form-group">
          <label>Requirements</label>
          <textarea name="requirements" rows="2" placeholder="Degrees, subject expertise, soft skills..."></textarea>
        </div>

        <div class="form-group">
          <label>Publish Status</label>
          <select name="status">
            <option value="published">Published (Visible on Careers Page)</option>
            <option value="draft">Draft (Hidden)</option>
          </select>
        </div>

        <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
          <button type="submit" class="btn-add" style="flex: 1;"><i class="fa-solid fa-plus"></i> Save &amp; Create Vacancy</button>
          <button type="button" onclick="closeModal('createModal')" style="background: #475569; color: white; border: none; padding: 0.75rem 1.25rem; border-radius: 8px; font-weight: 600; cursor: pointer;">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <!-- 2. EDIT VACANCY MODAL -->
  <div class="modal" id="editModal">
    <div class="modal-card">
      <h2 style="font-family: 'Outfit', sans-serif; margin-bottom: 1.25rem; color: white;">Edit Job Vacancy</h2>
      <form method="POST" action="vacancies.php">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit_id">

        <div class="form-group">
          <label>Job Title *</label>
          <input type="text" name="job_title" id="edit_job_title" required>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label>Position / Wing *</label>
            <input type="text" name="position" id="edit_position" required>
          </div>
          <div class="form-group">
            <label>Qualification *</label>
            <input type="text" name="qualification" id="edit_qualification" required>
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label>Experience Required *</label>
            <input type="text" name="experience" id="edit_experience" required>
          </div>
          <div class="form-group">
            <label>Job Type *</label>
            <select name="job_type" id="edit_job_type">
              <option value="Full-Time">Full-Time</option>
              <option value="Part-Time">Part-Time</option>
              <option value="Contract">Contractual</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>Campus Location</label>
          <input type="text" name="location" id="edit_location">
        </div>

        <div class="form-group">
          <label>Job Description</label>
          <textarea name="description" id="edit_description" rows="3"></textarea>
        </div>

        <div class="form-group">
          <label>Requirements</label>
          <textarea name="requirements" id="edit_requirements" rows="2"></textarea>
        </div>

        <div class="form-group">
          <label>Publish Status</label>
          <select name="status" id="edit_status">
            <option value="published">Published (Visible on Careers Page)</option>
            <option value="draft">Draft (Hidden)</option>
          </select>
        </div>

        <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
          <button type="submit" class="btn-add" style="flex: 1;"><i class="fa-solid fa-check"></i> Update Vacancy</button>
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
    function editVacancy(vac) {
      document.getElementById('edit_id').value = vac.id || '';
      document.getElementById('edit_job_title').value = vac.job_title || '';
      document.getElementById('edit_position').value = vac.position || '';
      document.getElementById('edit_qualification').value = vac.qualification || '';
      document.getElementById('edit_experience').value = vac.experience || '';
      document.getElementById('edit_location').value = vac.location || 'Gujarat & Delhi Campuses';
      document.getElementById('edit_job_type').value = vac.job_type || 'Full-Time';
      document.getElementById('edit_description').value = vac.description || '';
      document.getElementById('edit_requirements').value = vac.requirements || '';
      document.getElementById('edit_status').value = vac.status || 'published';
      openModal('editModal');
    }
  </script>
</body>
</html>
