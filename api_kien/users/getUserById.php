<?php
header('Content-Type: application/json'); // Đặt loại nội dung cho phản hồi
require_once '../../source/models/UserModel.php'; // Đảm bảo đã bao gồm UserModel

// Lấy dữ liệu JSON từ yêu cầu
$data = json_decode(file_get_contents("php://input"), true);

// Kiểm tra nếu có `user_id` trong dữ liệu đầu vào
if (isset($data['user_id'])) {
    $user_id = $data['user_id'];

    // Tạo một instance của UserModel
    $userModel = new UserModel();

    // Gọi phương thức getUserById để lấy thông tin người dùng
    $user = $userModel->getUserById($user_id);

    if ($user) {
        echo json_encode($user);
    } else {
        echo json_encode([
            'error' => true,
            'message' => 'Không tìm thây người dùng'
        ]);
    }
} else {
    echo json_encode([
        'error' => true,
        'message' => 'ID người dùng không hợp lệ!'
    ]);
}
