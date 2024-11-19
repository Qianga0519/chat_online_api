<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../source/models/Auth.php';
require_once '../../source/models/MessageModel.php';
require_once '../../source/models/ChatRoomModel.php';
require_once '../../source/models/UserModel.php';
// Khởi tạo model
$messageModel = new MessageModel();
$roomModel = new ChatRoomModel();
$userModel = new UserModel();
// Nhận dữ liệu từ client
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['room_id'])) {
    $room_id = $data['room_id'];
    $room = $roomModel->getChatRommId($room_id);
    $user_id_1 = $room['user_id_1'];
    $user_id_2 = $room['user_id_2'];
    if ($userModel->getUserById($user_id_1) && $userModel->getUserById($user_id_2)) {
        if ($room) {
            $messages = $messageModel->getMessagesByRoomId($room_id);
            if ($messages) {
                echo json_encode([
                    'success' => true,
                    'data' => $messages
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'data' => [],
                    'message' => 'No chat rooms found.'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Not found room.'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Tài khoản này không còn hoạt động',
            'status' => false
        ]);
    }   
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Missing user_id'
    ]);
}
