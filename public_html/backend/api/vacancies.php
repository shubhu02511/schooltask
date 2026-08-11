<?php
// ==========================================================================
// BRIO WORLD SCHOOL - Public Vacancies API Endpoint
// Returns published vacancies for the Careers page with dual storage fallback
// ==========================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

handleCORS();

$slug = cleanInput($_GET['slug'] ?? '');
$vacancies = [];

try {
    $db = getCoreDB();
    if ($db) {
        if (!empty($slug)) {
            $stmt = $db->prepare("SELECT * FROM vacancies WHERE slug = ? AND status = 'published' LIMIT 1");
            $stmt->execute([$slug]);
            $item = $stmt->fetch();
            if ($item) {
                sendJSONResponse(true, 'Vacancy details fetched', ['vacancy' => $item]);
            } else {
                sendJSONResponse(false, 'Vacancy not found', [], 404);
            }
        } else {
            $stmt = $db->prepare("SELECT * FROM vacancies WHERE status = 'published' ORDER BY id DESC");
            $stmt->execute();
            $vacancies = $stmt->fetchAll() ?: [];
        }
    }
} catch (Exception $e) {
    // MySQL table not ready, fallback to JSON
}

// Fallback JSON File Storage
if (empty($vacancies)) {
    $jsonFile = __DIR__ . '/../../storage/database/vacancies.json';
    if (file_exists($jsonFile)) {
        $all = json_decode(file_get_contents($jsonFile), true) ?: [];
        foreach ($all as $v) {
            if (($v['status'] ?? 'published') === 'published') {
                if (!empty($slug) && ($v['slug'] ?? '') === $slug) {
                    sendJSONResponse(true, 'Vacancy details fetched', ['vacancy' => $v]);
                }
                $vacancies[] = $v;
            }
        }
    }
}

sendJSONResponse(true, 'Published vacancies fetched', ['vacancies' => $vacancies]);
