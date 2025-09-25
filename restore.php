<?php
// restore.php - Place this in your website root directory
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: X-User-ID, X-Restore-Version, X-Device-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the user parameter
$user = isset($_GET['user']) ? $_GET['user'] : '';

// Clean the username (same as backup.php)
$user = preg_replace('/[^a-zA-Z0-9_-]/', '', $user);
if (empty($user)) {
    http_response_code(400);
    echo json_encode(['error' => 'User parameter required']);
    exit;
}

// Use EXACT SAME filename format as backup.php
$filename = "data/cineshelf_backup_{$user}.json";

// Check if backup exists
if (!file_exists($filename)) {
    // Help the user understand what's wrong
    $available_files = [];
    if (file_exists('data')) {
        $files = glob("data/cineshelf_backup_*.json");
        foreach ($files as $file) {
            $available_files[] = basename($file);
        }
    }
    
    http_response_code(404);
    echo json_encode([
        'error' => 'No backup found for user: ' . $user,
        'looking_for' => basename($filename),
        'available_backups' => $available_files,
        'suggestion' => empty($available_files) 
            ? 'No backups exist yet. Please backup from your main device first.' 
            : 'Available users: ' . implode(', ', array_map(function($f) {
                return str_replace(['cineshelf_backup_', '.json'], '', $f);
            }, $available_files))
    ]);
    exit;
}

// Read the backup file
$data = file_get_contents($filename);
if ($data === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to read backup file']);
    exit;
}

// Verify it's valid JSON
$jsonData = json_decode($data, true);
if ($jsonData === null) {
    http_response_code(500);
    echo json_encode(['error' => 'Backup file contains invalid JSON']);
    exit;
}

// Add restore metadata (optional, for debugging)
$jsonData['_restore_metadata'] = [
    'restored_from' => basename($filename),
    'restored_at' => date('c'),
    'restored_for_user' => $user
];

// Return the backup data
echo json_encode($jsonData);
?>