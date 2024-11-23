<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Cho phép tất cả các nguồn
header('Access-Control-Allow-Methods: POST'); // Các phương thức được phép
header('Access-Control-Allow-Headers: Content-Type'); // Các header được phép

require_once '../../source/models/PostCommentRepModel.php'; // Đường dẫn đến model của bạn

$postModel = new PostCommentRepModel();

// Lấy dữ liệu từ request body
$data = json_decode(file_get_contents("php://input"));

if (isset($data->cmt_id) && isset($data->user_id) && isset($data->content) && isset($data->order)) {
    $cmtId = $data->cmt_id;
    $userId = $data->user_id; // Giả sử đây là ID của người phản hồi
    $content = $data->content;
    $order = $data->order;

    try {
        $result = $postModel->createCommentRep($cmtId, $userId, $content, $order);
        // Gọi phương thức để thêm phản hồi
        if ($result) {
            echo json_encode(["success" => true, "message" => "Phản hồi đã được thêm thành công.", 'data'=> $result]);
        } else {
            echo json_encode(["success" => false, "message" => "Không thể thêm phản hồi."]);
        }
        
    } catch (Exception $e) {
        // Xử lý lỗi
        echo json_encode(["error" => $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Thiếu thông tin cần thiết."]);
}
?>
