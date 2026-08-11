<?php
// ==========================================================================
// BRIO WORLD SCHOOL - Public News & Events API Endpoint
// Returns published news and events with dual storage fallback
// ==========================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

handleCORS();

$news = [];

try {
    $db = getCoreDB();
    if ($db) {
        $stmt = $db->prepare("SELECT * FROM news_events WHERE status = 'published' ORDER BY event_date DESC");
        $stmt->execute();
        $news = $stmt->fetchAll() ?: [];
    }
} catch (Exception $e) {
    // Fallback to JSON
}

// Fallback JSON File Storage
if (empty($news)) {
    $jsonFile = __DIR__ . '/../../storage/database/news_events.json';
    if (file_exists($jsonFile)) {
        $all = json_decode(file_get_contents($jsonFile), true) ?: [];
        foreach ($all as $item) {
            if (($item['status'] ?? 'published') === 'published') {
                $news[] = $item;
            }
        }
    }
}

sendJSONResponse(true, 'Published news and events fetched', ['news' => $news]);
