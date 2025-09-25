<?php
// update_privacy.php - Update collection privacy settings
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-Google-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $googleUserId = $_SERVER['HTTP_X_GOOGLE_USER_ID'] ?? '';
    
    if (!$input || !isset($input['privacy']) || !$googleUserId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request']);
        exit;
    }
    
    $privacyFile = "privacy_" . $googleUserId . ".json";
    $privacyData = [];
    
    if (file_exists($privacyFile)) {
        $privacyData = json_decode(file_get_contents($privacyFile), true) ?: [];
    }
    
    $privacyData['privacy'] = $input['privacy'];
    $privacyData['updated'] = date('c');
    
    if (file_put_contents($privacyFile, json_encode($privacyData, JSON_PRETTY_PRINT))) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update privacy']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>