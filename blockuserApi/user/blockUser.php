<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Cho phép tất cả các nguồn (hoặc thay * bằng địa chỉ cụ thể của bạn)
header('Access-Control-Allow-Methods: POST'); // Các phương thức được phép
header('Access-Control-Allow-Headers: Content-Type'); // Các header được phép
require_once '../../source/models/PostCommentModel.php';
require_once '../../source/models/FriendshipModel.php';

$friendshipModel = new FriendshipModel();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'));

    // Kiểm tra sự tồn tại của các biến
    if (isset($data->user_id) && isset($data->friend_id) && isset($data->authToken)) {
        $user_id = $data->user_id;
        $friend_id = $data->friend_id;
        $authToken = $data->authToken;

        // Gọi hàm blockUser
        $res = $friendshipModel->blockUser($user_id, $friend_id);

        if ($res['success']) {
            if ($res['state'] == 'update') {
                echo json_encode(['success' => true, 'message' => 'Đã chặn người dùng.']);
            } else {
                echo json_encode(['success' => true, 'message' => 'Quan hệ đã được thiết lập chặn.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Đã có lỗi xảy ra.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.']);
}
