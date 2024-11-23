<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Cho phép tất cả các nguồn (hoặc thay * bằng địa chỉ cụ thể của bạn)
header('Access-Control-Allow-Methods: GET'); // Các phương thức được phép
header('Access-Control-Allow-Headers: Content-Type'); // Các header được phép
require_once '../../source/models/PostModel.php';
// Tạo đối tượng PostModel
$postModel = new Post();
try {
    // Lấy tất cả bai viet
    $posts = $postModel->getAll();

    // Kiểm tra xem có người dùng nào không
    if (count($posts) > 0) {
        echo json_encode($posts); // Trả về dữ liệu người dùng dưới dạng JSON
    } else {
        echo json_encode([]); // Trả về mảng rỗng nếu không có người dùng
    }
} catch (Exception $e) {
    // Xử lý lỗi
    echo json_encode(["error" => $e->getMessage()]);
}




