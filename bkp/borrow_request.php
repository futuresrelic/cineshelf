<?php
// borrow_request.php - Handle borrowing requests between users
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-Requester-ID');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $requesterId = $_SERVER['HTTP_X_REQUESTER_ID'] ?? '';
    
    if (!$input || !isset($input['copy_id']) || !isset($input['owner_id']) || !$requesterId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request']);
        exit;
    }
    
    $borrowRequest = [
        'id' => uniqid('borrow_', true),
        'copy_id' => $input['copy_id'],
        'owner_id' => $input['owner_id'],
        'requester_id' => $requesterId,
        'requester_name' => $input['requester_name'] ?? '',
        'requester_email' => $input['requester_email'] ?? '',
        'status' => 'pending',
        'requested_at' => date('c')
    ];
    
    $borrowFile = "borrows_" . $input['owner_id'] . ".json";
    $borrows = [];
    
    if (file_exists($borrowFile)) {
        $borrows = json_decode(file_get_contents($borrowFile), true) ?: [];
    }
    
    $borrows[] = $borrowRequest;
    
    if (file_put_contents($borrowFile, json_encode($borrows, JSON_PRETTY_PRINT))) {
        echo json_encode(['success' => true, 'request_id' => $borrowRequest['id']]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save borrow request']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>