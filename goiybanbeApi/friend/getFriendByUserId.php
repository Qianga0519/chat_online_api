<?php
// Đặt các header để hỗ trợ CORS và cho phép các phương thức HTTP
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

header('Content-Type: application/json');

// Bao gồm các model cần thiết
require_once '../../source/models/UserModel.php'; // Bao gồm model người dùng
require_once '../../source/models/FriendshipModel.php'; // Bao gồm model bạn bè

// Tạo đối tượng model Friendship
$fsModel = new FriendshipModel();

// Lấy user_id từ tham số GET
$userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;


if ($userId) {
    // Lấy danh sách gợi ý bạn bè từ phương thức
    $friendSuggestions = $fsModel->getFriendSuggestionsByUserId($userId);

    if ($friendSuggestions) {
        // Nếu có gợi ý bạn bè, trả về dữ liệu
        echo json_encode([
            'success' => true,
            'message' => "Có bạn bè gợi ý.",
            'data' => $friendSuggestions
        ]);
    } else {
        // Nếu không có gợi ý, trả về thông báo lỗi
        echo json_encode([
            'success' => false,
            'message' => "Không có bạn bè gợi ý.",
            'data' => []
        ]);
    }
} else {
    // Trường hợp không có user_id
    echo json_encode([
        'success' => false,
        'message' => "Không tìm thấy người dùng."
    ]);
}
?>
