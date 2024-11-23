<?php

header('Content-Type: appliaction/json');
require_once '../../source/models/GeneralModel.php';
$general = new GeneralModel();
try {
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $result = $general->getUserPosts();
        if (count($result) > 0) {
            echo json_encode($result); // Trả về dữ liệu người dùng dưới dạng JSON
        } else {
            echo json_encode([]); // Trả về mảng rỗng nếu không có người dùng
        }
    }
} catch (Exception $e) {
    // Xử lý lỗi
    echo json_encode(["error" => $e->getMessage()]);
}
