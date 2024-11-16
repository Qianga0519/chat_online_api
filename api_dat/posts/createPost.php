<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../source/config/Database.php';
require_once '../../source/models/PostModel.php';
require_once '../../source/models/MediaModel.php';

$postModel = new Post();
$mediaModel = new MediaModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = isset($_POST['userId']) ? $_POST['userId'] : null;
    $content = isset($_POST['content']) ? $_POST['content'] : null;
    $uploadDir = '../../uploads/'; // Đảm bảo thư mục này đã tồn tại và có quyền ghi

    // Kiểm tra và tạo thư mục nếu chưa có
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $isImage = false;
    $url = '';
    $respon = $postModel->create(['content' => $content, 'user_id' => $userId]);

    if ($respon) {
        if (isset($_FILES['mediaFiles']) && $_FILES['mediaFiles']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['mediaFiles'];

            // Kiểm tra loại file
            $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($fileType, $allowedTypes)) {
                echo json_encode(['error' => 'Chỉ cho phép tải lên các tệp hình ảnh (jpg, jpeg, png, gif)']);
                exit;
            }

            // Kiểm tra kích thước file (giới hạn 5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                echo json_encode(['error' => 'Kích thước file không được vượt quá 5MB']);
                exit;
            }

            $url = $file['name'];
            $filePath = $uploadDir . basename($file['name']);

            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                $dataMedia = [
                    'post_id' => $respon,
                    'user_id' => $userId,
                    'url' => $url,
                    'is_avatar' => 0,
                    'media_type' => 'image'
                ];
                $resultMedia = $mediaModel->createMedia($dataMedia);
                $isImage = $resultMedia;
            } else {
                echo json_encode(['success' => false, 'error' => 'Lỗi khi tải lên tệp tin']);
                exit;
            }
        }

        echo json_encode([
            'success' => true,
            'isImage' => $isImage,
            'message' => 'Create post success.',
            'fileUrl' => $filePath // Trả về URL của file đã tải lên
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Create post failed.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ']);
}
