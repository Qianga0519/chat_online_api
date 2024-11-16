<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../source/models/UserModel.php';

// Khởi tạo model
$userModel = new UserModel();

if (!isset($_GET['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'user_id is required']);
    exit;
}

$user_id = intval($_GET['user_id']);
$respon = $userModel->GetAvatarUserByMedia(68);
if ($respon) {
    echo json_encode(['success' => true, 'data' => $respon]);
} else {
    echo json_encode(['success' => false, 'error' => 'Room not found']);
}
   
