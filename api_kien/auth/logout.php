<?php
header('Content-Type: application/json');
require_once '../../source/models/Auth.php'; // Đảm bảo đường dẫn đúng tới Auth.php

$auth = new Auth();
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['token'])) {
    // Nếu không có token trong request body
    echo json_encode(['error' => 'Token is required']);
    http_response_code(400);
    exit();
}

$response = $auth->logout($data['token']);
echo json_encode($response);
?>
