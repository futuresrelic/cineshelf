<?php
// search_collection.php - Search and view shared collections
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: X-Searcher-ID');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $query = $_GET['q'] ?? '';
    $searcherId = $_SERVER['HTTP_X_SEARCHER_ID'] ?? '';
    
    if (!$query) {
        http_response_code(400);
        echo json_encode(['error' => 'Query required']);
        exit;
    }
    
    // Search by email or share code
    $foundCollection = null;
    
    if (strpos($query, '@') !== false) {
        // Search by email
        $privacyFiles = glob("privacy_*.json");
        foreach ($privacyFiles as $file) {
            $privacy = json_decode(file_get_contents($file), true);
            if ($privacy && 
                strtolower($privacy['email']) === strtolower($query) && 
                in_array($privacy['privacy'], ['friends', 'public'])) {
                
                $backupFile = "backup_google_" . $privacy['user_id'] . ".json";
                if (file_exists($backupFile)) {
                    $foundCollection = json_decode(file_get_contents($backupFile), true);
                    $foundCollection['owner_name'] = $privacy['name'];
                    $foundCollection['user_id'] = $privacy['user_id'];
                    break;
                }
            }
        }
    } else {
        // Search by share code (decode base64)
        try {
            $decoded = base64_decode($query . str_repeat('=', (4 - strlen($query) % 4) % 4));
            $backupFile = "backup_google_" . $decoded . ".json";
            
            if (file_exists($backupFile)) {
                $privacyFile = "privacy_" . $decoded . ".json";
                if (file_exists($privacyFile)) {
                    $privacy = json_decode(file_get_contents($privacyFile), true);
                    if ($privacy && in_array($privacy['privacy'], ['friends', 'public'])) {
                        $foundCollection = json_decode(file_get_contents($backupFile), true);
                        $foundCollection['owner_name'] = $privacy['name'];
                        $foundCollection['user_id'] = $privacy['user_id'];
                    }
                }
            }
        } catch (Exception $e) {
            // Invalid share code
        }
    }
    
    if ($foundCollection) {
        // Remove sensitive data before sending
        unset($foundCollection['google_user_id']);
        unset($foundCollection['google_email']);
        echo json_encode($foundCollection);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Collection not found or not shared']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>