<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Cho phép tất cả các nguồn
header('Access-Control-Allow-Methods: POST'); // Phương thức POST cho API
header('Access-Control-Allow-Headers: Content-Type'); // Các header được phép

require_once '../../source/models/PostCommentModel.php';
require_once '../../source/models/PostCommentRepModel.php';

// Tạo đối tượng CommentModel
$commentModel = new PostCommentModel();
$commentRepModel = new PostCommentRepModel();

try {
    // Nhận dữ liệu từ yêu cầu POST
    $data = json_decode(file_get_contents("php://input"), true);

    // Kiểm tra nếu các trường bắt buộc tồn tại
    if (isset($data['post_id']) && isset($data['user_cmt_id']) && isset($data['content']) && isset($data['order'])) {
        $postId = $data['post_id'];
        $userCmtId = $data['user_cmt_id'];
        $content = $data['content'];
        $order = $data['order'];

        // Tạo bình luận
        $isCreated = $commentModel->createComment($postId, $userCmtId, $content, $order);

        if ($isCreated != false) {
            // $post_coment = [];
            // $replies = [];
            // $replies = $commentRepModel->getRepsByCommentId($isCreated['id']);
            // $post_coment = $commentModel->getCommentsByPostId($isCreated['post_id']);
            echo json_encode(["success" => "Bình luận đã được thêm thành công", 'data' => $isCreated]);
        } else {
            echo json_encode(["error" => "Không thể thêm bình luận"]);
        }
    } else {
        echo json_encode(["error" => "Thiếu dữ liệu yêu cầu"]);
    }
} catch (Exception $e) {
    // Xử lý lỗi
    echo json_encode(["error" => $e->getMessage()]);
}
