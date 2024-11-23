<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Cho phép tất cả các nguồn (hoặc thay * bằng địa chỉ cụ thể của bạn)
header('Access-Control-Allow-Methods: DELETE, OPTIONS'); // Các phương thức được phép
header('Access-Control-Allow-Headers: Content-Type'); // Các header được phép
require_once '../../source/models/PostModel.php';
try {
    // Kiểm tra xem có dữ liệu được gửi đến không
    if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
        // Lấy ID bài viết từ query parameters
        $post_id = $_GET['id']; // Sử dụng $_GET để lấy ID từ query parameters
        $post = new Post();

        // Kiểm tra nếu ID hợp lệ
        if (isset($post_id) && is_numeric($post_id)) {
            $result = $post->delete($post_id);
            if ($result > 0) {
                echo json_encode(["message" => "Bài viết đã được xóa."]);
            } else {
                echo json_encode(["message" => "Không tìm thấy bài viết với ID này."]);
            }
        } else {
            echo json_encode(["message" => "ID bài viết không hợp lệ."]);
        }
    } else {
        echo json_encode(["message" => "Yêu cầu không hợp lệ."]);
    }
} catch (Exception $e) {

    echo json_encode(["error" => $e->getMessage()]);
}
