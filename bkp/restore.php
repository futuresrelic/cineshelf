<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user = isset($_GET['user']) ? $_GET['user'] : 'default';
    $user = preg_replace('/[^a-zA-Z0-9_-]/', '', $user); // Sanitize filename
    $filename = "backup_" . $user . ".json";
    
    if (file_exists($filename)) {
        $data = file_get_contents($filename);
        echo $data;
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'No backup found']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>