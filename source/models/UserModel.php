<?php
require_once 'BaseModel.php'; // Đảm bảo đã bao gồm file BaseModel
require_once 'TokenModel.php'; // Đảm bảo đã bao gồm file BaseModel
class UserModel extends BaseModel
{
    private $tokenModel;
    public function __construct()
    {
        parent::__construct(); // Gọi đến constructor của lớp cha
        $this->tokenModel = new TokenModel(); // Khởi tạo TokenModel
    }
    public function verifyPassword($inputPassword, $storedPassword)
    {
        // Giải mã hóa mật khẩu base 64
        // Mã hóa mật khẩu đầu vào với khóa bí mật
        $hashedInputPassword = $this->hashPasswordWithBase64($inputPassword);
        // So sánh với mật khẩu đã lưu
        return hash_equals($hashedInputPassword, $storedPassword);
    }
    private function hashPasswordWithBase64($password)
    {
        // Kết hợp mật khẩu với secret key
        $combined = $password . SECRET_KEY;
        // Mã hóa mật khẩu và chuyển đổi sang Base64
        return base64_encode(hash('sha256', $combined, true));
    }
    // Phương thức tạo người dùng
    public function createUser(array $data)
    {
        // Mã hóa mật khẩu
        $passwordHash = $this->hashPasswordWithBase64($data['password']);
        $sql = "INSERT INTO users (email, password, status, name, created_at) 
                VALUES (:email, :password, :status, :name, NOW())";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':email' => $data['email'],
            ':password' => $passwordHash,
            ':status' => 0, // Giả sử trạng thái mặc định là 1 (hoạt động)
            ':name' => htmlspecialchars($data['name']) // Xử lý HTML để ngăn chặn XSS
        ]);
        $userId = $this->conn->lastInsertId(); // Lấy ID người dùng vừa tạo
        // Tạo token cho người dùng
        $token = $this->tokenModel->createToken($userId);
        return [
            'success' => true,
            'user_id' => $userId,
            'token' => $token // Trả về token vừa tạo
        ];
    }
    public function updateUser($id, array $data)
    {
        $user = $this->getUserById($id); // Lấy người dùng theo ID

        // Tạo mảng để lưu các phần cần cập nhật
        $fieldsToUpdate = [];
        $params = [':id' => $id];

        // Cập nhật tên người dùng nếu có
        if (!empty($data['name'])) {
            $fieldsToUpdate[] = 'name = :name';
            $params[':name'] = htmlspecialchars($data['name']); // Xử lý HTML để ngăn chặn XSS
        }

        // Cập nhật email nếu có
        if (!empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Email không hợp lệ.'];
            }
            $fieldsToUpdate[] = 'email = :email';
            $params[':email'] = $data['email'];
        }

        // Cập nhật mật khẩu nếu có
        if (!empty($data['password'])) {
            $passwordHash = $this->hashPasswordWithBase64($data['password']);
            $fieldsToUpdate[] = 'password = :password';
            $params[':password'] = $passwordHash;
        }

        // Cập nhật trạng thái nếu có
        if (isset($data['status'])) {
            $fieldsToUpdate[] = 'status = :status';
            $params[':status'] = $data['status'];
        }

        // Nếu không có trường nào để cập nhật, trả về thông báo
        if (empty($fieldsToUpdate)) {
            return ['success' => false, 'message' => 'Không có thông tin nào để cập nhật.'];
        }

        // Tạo chuỗi câu lệnh SQL
        $sql = "UPDATE " . $this->getTable() . " SET " . implode(', ', $fieldsToUpdate) . " WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return [
            'success' => true,
            'user' => [
                'id' => $id,
                'name' => $data['name'] ?? $user['name'], // Trả về tên cũ nếu không có tên mới
                'email' => $data['email'] ?? $user['email'], // Trả về email cũ nếu không có email mới
            ],
        ];
    }
    // Triển khai phương thức read để đọc dữ liệu từ bảng users
    public function getUserById($id)
    {
        $sql = "SELECT * FROM " . $this->getTable() . " WHERE id = :id ";
        $sql = "SELECT u.*, m.url FROM " . $this->getTable() . " as u LEFT JOIN media as m ON m.user_id = :id AND m.is_avatar = 1  WHERE u.id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserInfoByUserId($id)
    {
        $sql = "SELECT * FROM user_info WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':user_id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserWithInfo($user_id)
    {
        $sql = "
            SELECT u.id, u.email, u.name, u.status, 
                   ui.age, ui.gender, ui.phone, ui.created_at AS user_info_created_at, ui.updated_at 
            FROM users u
            LEFT JOIN user_info ui ON u.id = ui.user_id
            WHERE u.id = :user_id
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Triển khai phương thức delete để xóa dữ liệu trong bảng users
    public function deleteUser($id)
    {
        $sql = "DELETE FROM " . $this->getTable() . " WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        return true;
    }
    // Phương thức tìm kiếm người dùng theo email
    public function getUserByEmail($email)
    {
        try {
            $sql = "SELECT * FROM " . $this->getTable() . " WHERE email = :email";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':email' => $email]);
            return $stmt->fetch(PDO::FETCH_ASSOC); // Trả về người dùng tìm thấy
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return null;
        }
    }


    // Tìm kiếm người dùng phân trang
    public function searchUsers($keyword, $offset, $limit)
    {
        try {
            $sql = "SELECT *, users.id as iduser FROM users LEFT JOIN media ON media.user_id = users.id AND media.is_avatar = 1 WHERE name LIKE :keyword LIMIT :offset, :limit";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':keyword', '%' . $keyword . '%', PDO::PARAM_STR);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return [];
        }
    }
    public function checkFriendshipStatus($user_id, $friend_id)
    {
        $sql = "
        SELECT status FROM friendships 
        WHERE (user_id = :user_id AND friend_id = :friend_id) 
           OR (user_id = :friend_id AND friend_id = :user_id)
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':friend_id', $friend_id, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['status'] : null; // Trả về trạng thái nếu tìm thấy, hoặc null nếu không
    }
    public function getFriendship($user_id)
    {
        $sql = "
        SELECT media.url, u.id, u.name, u.email, u.status, f.status AS friendship_status
FROM friendships f
JOIN users u ON (f.friend_id = u.id OR f.user_id = u.id)
LEFT JOIN media ON media.user_id = u.id AND media.is_avatar = 1 
WHERE 
    (f.user_id = :user_id OR f.friend_id = :user_id)
    AND u.id != :user_id
    AND f.status = 'accepted'
    ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function make_friend($user_id)
    {
        $sql = "
      SELECT media.url, f.friend_id, f.user_id AS idf, u.id, u.name, u.email, u.status, f.status AS friendship_status
FROM friendships f
LEFT JOIN users u ON f.user_id = u.id
LEFT JOIN media ON media.user_id = u.id AND media.is_avatar = 1  
WHERE 
    f.friend_id = :user_id
    AND f.status = 'pending'
    ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // Trả về tên bảng cho model này
    protected function getTable()
    {
        return 'users';
    }

    public function updateUserInfo1($user_id, $name, $age, $gender, $phone)
    {
        // Cập nhật thông tin trong bảng users
        $sqlUpdateUser = "
            UPDATE users
            SET name = :name
            WHERE id = :user_id
        ";

        $stmtUser = $this->conn->prepare($sqlUpdateUser);
        $stmtUser->bindParam(':name', $name);
        $stmtUser->bindParam(':user_id', $user_id);
        $stmtUser->execute();

        // Cập nhật thông tin trong bảng user_info
        $sqlUpdateUserInfo = "
            UPDATE user_info
            SET age = :age, gender = :gender, phone = :phone
            WHERE user_id = :user_id
        ";

        $stmtUserInfo = $this->conn->prepare($sqlUpdateUserInfo);
        $stmtUserInfo->bindParam(':age', $age);
        $stmtUserInfo->bindParam(':gender', $gender);
        $stmtUserInfo->bindParam(':phone', $phone);
        $stmtUserInfo->bindParam(':user_id', $user_id);

        return $stmtUserInfo->execute(); // Trả về kết quả của lần cập nhật thông tin người dùng
    }

    //SELECT * FROM `media INNER JOIN users ON users.id = media.user_id WHERE user_id = 4 AND is_avatar = 1 LIMIT 1 LAY AVATAR
    public function GetAvatarUserByMedia($id)
    {
        $sql = "SELECT media.url FROM users LEFT JOIN media ON media.user_id = users.id AND media.is_avatar = 1 WHERE users.id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function updatePassword($userId, $oldPassword, $newPassword)
    {
        $query = "SELECT password FROM users WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $storedPassword = $stmt->fetchColumn();

        if ($this->verifyPassword($oldPassword, $storedPassword)) {
            $hashedNewPassword = $this->hashPasswordWithBase64($newPassword);
            $updateQuery = "UPDATE users SET password = :new_password WHERE id = :user_id";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':new_password', $hashedNewPassword);
            $updateStmt->bindParam(':user_id', $userId);
            return $updateStmt->execute();
        } else {
            return false;
        }
    }
    public function userActive($id)
    {
        // Tạo chuỗi câu lệnh SQL
        $sql = "UPDATE " . $this->getTable() . " SET status = 1 WHERE id = :id";

        // Chuẩn bị câu lệnh
        $stmt = $this->conn->prepare($sql);

        // Liên kết tham số
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        // Thực thi câu lệnh
        return $stmt->execute(); // Trả về true nếu thực thi thành công, ngược lại false
    }
    public function userUnActive($id)
    {
        // Tạo chuỗi câu lệnh SQL
        $sql = "UPDATE users SET status = 0 WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute(); // Trả về true nếu thực thi thành công, ngược lại false
    }

    public function acceptFriendRequest($userId, $friendId)
    {
        $query = "UPDATE friendships SET status = 'accepted' 
                  WHERE (user_id = :user_id AND friend_id = :friend_id) 
                     OR (user_id = :friend_id AND friend_id = :user_id)
                     AND status = 'pending'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':friend_id', $friendId);
        return $stmt->execute();
    }
    public function declineFriendRequest($userId, $friendId)
    {
        $query = "UPDATE friendships SET status = 'declined' 
                  WHERE (user_id = :user_id AND friend_id = :friend_id) 
                     OR (user_id = :friend_id AND friend_id = :user_id)
                     AND status = 'pending'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':friend_id', $friendId);
        return $stmt->execute();
    }
    public function sendFriendRequest($userId, $friendId)
    {
        // Kiểm tra nếu đã tồn tại yêu cầu kết bạn
        $checkSql = "SELECT status FROM friendships WHERE user_id = :user_id AND friend_id = :friend_id";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bindParam(':user_id', $userId);
        $checkStmt->bindParam(':friend_id', $friendId);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            $friendship = $checkStmt->fetch(PDO::FETCH_ASSOC);

            // Kiểm tra trạng thái của yêu cầu
            if ($friendship['status'] === 'accepted') {
                return "Đã là bạn bè";
            } else if ($friendship['status'] === 'pending') {
                return "Yêu cầu kết bạn đã tồn tại";
            }
        }

        // Chèn yêu cầu kết bạn mới
        $sql = "INSERT INTO friendships (user_id, friend_id, status, created_at) 
            VALUES (:user_id, :friend_id, 'pending', NOW())";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':friend_id', $friendId);

        return $stmt->execute();
    }
    public function cancelFriendship($userId, $friendId)
{
    // Kiểm tra nếu mối quan hệ bạn bè đã tồn tại và có trạng thái 'accepted'
    $checkSql = "SELECT status FROM friendships WHERE (user_id = :user_id AND friend_id = :friend_id) 
                 OR (user_id = :friend_id AND friend_id = :user_id)";
    $checkStmt = $this->conn->prepare($checkSql);
    $checkStmt->bindParam(':user_id', $userId);
    $checkStmt->bindParam(':friend_id', $friendId);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        $friendship = $checkStmt->fetch(PDO::FETCH_ASSOC);

        // Kiểm tra xem trạng thái có phải là 'accepted' hay không
        if ($friendship['status'] === 'accepted') {
            // Xóa mối quan hệ bạn bè
            $deleteSql = "DELETE FROM friendships WHERE (user_id = :user_id AND friend_id = :friend_id) 
                          OR (user_id = :friend_id AND friend_id = :user_id)";
            $deleteStmt = $this->conn->prepare($deleteSql);
            $deleteStmt->bindParam(':user_id', $userId);
            $deleteStmt->bindParam(':friend_id', $friendId);

            return $deleteStmt->execute() ? "Đã hủy kết bạn" : "Có lỗi xảy ra khi hủy kết bạn";
        } else {
            return "Không thể hủy kết bạn vì yêu cầu chưa được chấp nhận";
        }
    }

    return "Không tìm thấy mối quan hệ bạn bè";
}

}
