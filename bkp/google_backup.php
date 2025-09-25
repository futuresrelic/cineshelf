<?php
// google_backup.php - Enhanced backup with Google user integration
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-Google-User-ID, X-User-Email');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['user']) || !isset($input['email'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data']);
        exit;
    }
    
    $googleUserId = $_SERVER['HTTP_X_GOOGLE_USER_ID'] ?? '';
    $userEmail = $_SERVER['HTTP_X_USER_EMAIL'] ?? '';
    
    // Sanitize filename using Google user ID
    $user = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['user']);
    $filename = "backup_" . $user . ".json";
    
    // Add Google-specific metadata
    $input['google_user_id'] = $googleUserId;
    $input['google_email'] = $userEmail;
    $input['backup_type'] = 'google_cloud';
    $input['server_timestamp'] = date('c');
    
    if (file_put_contents($filename, json_encode($input, JSON_PRETTY_PRINT))) {
        // Also save privacy settings separately
        if (isset($input['privacy'])) {
            $privacyData = [
                'user_id' => $googleUserId,
                'email' => $userEmail,
                'privacy' => $input['privacy'],
                'name' => $input['name'] ?? '',
                'updated' => date('c')
            ];
            file_put_contents("privacy_" . $googleUserId . ".json", json_encode($privacyData));
        }
        
        echo json_encode(['success' => true, 'backup_type' => 'google_cloud']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save backup']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>