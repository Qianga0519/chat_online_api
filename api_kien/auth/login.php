<?php
header('Content-Type: application/json');
require_once '../../source/models/Auth.php';
$auth = new Auth();
$data = json_decode(file_get_contents("php://input"), true);
// $data = ['email' => 'quang@gmail.com', "password" => 'qua1ng1'];
$response = $auth->login($data['email'], $data['password']);
echo json_encode($response);
