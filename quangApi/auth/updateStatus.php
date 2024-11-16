<?php session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once '../../source/models/Auth.php';
require_once '../../source/models/UserModel.php';
require_once '../../source/models/TokenModel.php';

$auth = new Auth();
$tokenModel = new TokenModel();
$userModel = new UserModel();

// Lấy dữ liệu JSON từ request
$data = json_decode(file_get_contents('php://input'), true);
$token = isset($data['token']) ? $data['token'] : null; // Lấy token từ dữ liệu JSON
file_put_contents('log.txt', "Request received: " . $token, FILE_APPEND);
if ($token) {
    // Xác thực token và lấy thông tin người dùng
    $user = $tokenModel->verifyToken($token);

    // Cập nhật trạng thái thành không hoạt động
    if ($user) {
        $result = $userModel->userUnActive($user['user_id']);
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Logout success']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to deactivate user']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid token']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No token provided']);
}
