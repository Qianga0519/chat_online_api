<?php
header("Access-Control-Allow-Origin: *"); // Cho phép tất cả các miền
header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); // Cho phép các phương thức
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Cho phép các header

header('Content-Type: application/json'); // Đặt loại nội dung cho phản hồi

require_once '../../source/models/UserModel.php'; // Nhúng model
require_once '../../source/models/TokenModel.php'; // Nhúng token model

// Tạo một instance của UserModel
$userModel = new UserModel();

// Lấy dữ liệu JSON từ yêu cầu
$data = json_decode(file_get_contents("php://input"), true);
// $data = "quang@gm1ail.com";

// Gọi phương thức createUser để thêm người dùng
$response = $userModel->getUserByEmail($data);

// Trả về phản hồi dưới dạng JSON
echo json_encode($response);
