<?php
header("Access-Control-Allow-Origin: *"); // Cho phép tất cả các miền
header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); // Cho phép các phương thức
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Cho phép các header
header('Content-Type: application/json'); // Đặt loại nội dung cho phản hồi

require_once '../../source/models/MessageModel.php'; // Đảm bảo đã bao gồm MessageModel
require_once '../../source/models/UserModel.php'; // Đảm bảo đã bao gồm MessageModel
require_once '../../source/models/TokenModel.php'; // Đảm bảo đã bao gồm MessageModel

$messModel = new MessageModel();
$userModel = new UserModel();
$tokenModel = new TokenModel();

// Định nghĩa route cho API tạo tin nhắn
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Lấy dữ liệu từ body của yêu cầu
    $data = json_decode(file_get_contents('php://input'), true);
    // --------------

    // Kiểm tra và làm sạch dữ liệu đầu vào
    $roomId = isset($data['room_id']) ? htmlspecialchars($data['room_id'], ENT_QUOTES, 'UTF-8') : null;
    $userId = isset($data['user_id']) ? htmlspecialchars($data['user_id'], ENT_QUOTES, 'UTF-8') : null;
    $content = isset($data['content']) ? htmlspecialchars($data['content'], ENT_QUOTES, 'UTF-8') : null;
    $authToken = isset($data['authToken']) ? htmlspecialchars($data['authToken'], ENT_QUOTES, 'UTF-8') : null;
    checkUserStatus($userId);

    if (checkToken($authToken) != true) {
        echo json_encode(['success' => false, 'error' => 'Xác thực người dùng thất bại. Vui lòng đăng nhập lại.']);
        exit();
    }

    // Kiểm tra dữ liệu đầu vào
    if (is_null($roomId) || is_null($userId) || is_null($content)) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'error' => 'Tất cả các tham số đều bắt buộc.']);
        exit();
    }

    // Kiểm tra độ dài của nội dung tin nhắn
    if (strlen($content) > 65000) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Số lượng dữ liệu nhập vào của bạn độ dài không phù hợp.']);
        exit();
    }

    // Kiểm tra nếu nội dung chỉ có khoảng trắng
    if (trim($content) === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Vui lòng nhập nội dung nhắn.', "data"=> $data]);
        exit();
    }

    // Kiểm tra nếu nội dung chứa HTML
    if ($content !== strip_tags($content)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Bạn nhập dữ liệu không phù hợp.']);
        exit();
    }

    try {
        // Tạo tin nhắn mới
        $messageId = $messModel->createMessage($roomId, $userId, $content);
        // Trả về mã phản hồi và ID của tin nhắn
        http_response_code(201); // Created
        echo json_encode(['success' => true, 'messageId' => $messageId]);
    } catch (InvalidArgumentException $e) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    } catch (Exception $e) {
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'error' => 'Có lỗi xảy ra trong quá trình tạo tin nhắn.']);
    }
}


// Kiểm tra tài khoản đã xóa
function checkUserStatus($userId)
{
    global $userModel; // Để sử dụng $messModel trong hàm này
    $userStatus = $userModel->getUserById($userId); // Giả sử có phương thức để kiểm tra trạng thái người dùng
    if (!$userStatus) {
        http_response_code(410); // Gone
        echo json_encode(['success' => false, 'error' => 'Tài khoản này không còn hoạt động!']);
        exit();
    }
}
function checkToken($token)
{
    global $tokenModel; // Để sử dụng $messModel trong hàm này
    if ($token) {
        // Gọi hàm verifyToken để kiểm tra token
        $isValid = $tokenModel->verifyToken($token); // Đúng tên biến
        if ($isValid) {
            return true;
        } else {
            return false;
        }
    }
}
