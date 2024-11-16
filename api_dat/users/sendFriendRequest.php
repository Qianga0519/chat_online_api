<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../source/models/UserModel.php';

$response = new UserModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['friend_id'])) {
    $userId = $_POST['user_id'];
    $friendId = $_POST['friend_id'];

    // Gọi hàm sendFriendRequest và lưu kết quả vào $result
    $result = $response->sendFriendRequest($userId, $friendId);

    if ($result === true) {
        echo json_encode(["status" => "success", "message" => "Yêu cầu kết bạn đã được gửi."]);
    } else {
        echo json_encode(["status" => "error", "message" => $result]);
    }
} else {
    // Trường hợp yêu cầu không hợp lệ
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
}
