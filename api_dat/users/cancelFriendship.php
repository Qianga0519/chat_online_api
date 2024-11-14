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

    // Gọi hàm cancelFriendship trong class của bạn
    $result = $response->cancelFriendship($userId, $friendId);

    // Trả về kết quả cho client
    if ($result === "Đã hủy kết bạn") {
        echo json_encode(["status" => "success", "message" => $result]);
    } else {
        echo json_encode(["status" => "error", "message" => $result]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Thiếu thông tin user_id hoặc friend_id"]);
}
?>
