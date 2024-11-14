<?php

class FriendshipModel extends BaseModel
{
    protected function getTable()
    {
        return 'friendships';
    }

    // Thêm mối quan hệ bạn bè
    public function createFriendship($userId, $friendId, $status = 'pending')
    {
        $sql = "INSERT INTO " . $this->getTable() . " (user_id, friend_id, status, created_at) 
                VALUES (:user_id, :friend_id, :status, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':friend_id', $friendId);
        $stmt->bindParam(':status', $status);

        return $stmt->execute();
    }

    // Cập nhật trạng thái mối quan hệ bạn bè
    public function updateFriendship($id, $status)
    {
        $sql = "UPDATE " . $this->getTable() . " SET status = :status, created_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Xóa mối quan hệ bạn bè
    public function deleteFriendship($id)
    {
        $sql = "DELETE FROM " . $this->getTable() . " WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getFriendSuggestionsByUserId($userId)
    {
        // Câu SQL để lấy bạn bè của bạn bè (second-degree friends)
        $sql = "SELECT m.url AS avatar, f2.id, f2.user_id, f2.friend_id, f2.status, f2.created_at, u.name AS friend_name
                FROM " . $this->getTable() . " f1
                JOIN " . $this->getTable() . " f2 ON f1.friend_id = f2.user_id
                JOIN users u ON u.id = f2.friend_id
                LEFT JOIN media m ON m.user_id = u.id AND m.is_avatar = 1
                WHERE f1.user_id = :user_id
                AND f2.status = 'accepted'
                AND f2.friend_id != :user_id
                AND NOT EXISTS (
                    SELECT 1 FROM " . $this->getTable() . " f3 
                    WHERE f3.user_id = :user_id AND f3.friend_id = f2.friend_id
                )";
    
        // Chuẩn bị và thực thi câu lệnh SQL
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':user_id', $userId); // Để tránh bạn được gợi ý lại
        $stmt->execute();
    
        // Trả về kết quả dưới dạng mảng
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Lấy tất cả bạn bè của một người dùng
    public function getFriendsByUserId($userId)
{
    // Câu SQL đã sửa
    $sql = "SELECT m.url AS avatar, f.id, f.user_id, f.friend_id, f.status, f.created_at, u.name AS friend_name
            FROM " . $this->getTable() . " f
            JOIN users u ON u.id = f.friend_id
                LEFT JOIN media m ON m.user_id = u.id AND m.is_avatar = 1
            WHERE f.user_id = :user_id AND f.status = 'accepted'";

    // Chuẩn bị và thực thi câu lệnh SQL
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();

    // Trả về kết quả dưới dạng mảng
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    // Lấy tất cả yêu cầu kết bạn của một người dùng
    public function getPendingFriendRequests($userId)
    {
        $sql = "SELECT * FROM " . $this->getTable() . " WHERE friend_id = :user_id AND status = 'pending'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Kiểm tra mối quan hệ bạn bè
    public function checkFriendship($userId, $friendId)
    {
        $sql = "SELECT * FROM " . $this->getTable() . " WHERE (user_id = :user_id AND friend_id = :friend_id) 
                OR (user_id = :friend_id AND friend_id = :user_id)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':friend_id', $friendId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    function blockUser($user_id, $friend_id)
    {
        // Kiểm tra quan hệ bạn bè hiện có
        $query = "SELECT * FROM friendships WHERE user_id = ? AND friend_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id, $friend_id]);

        if ($stmt->rowCount() > 0) {
            // Nếu quan hệ tồn tại, cập nhật trạng thái thành 'blocked'
            $updateQuery = "UPDATE friendships SET status = 'blocked' WHERE user_id = ? AND friend_id = ?";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->execute([$user_id, $friend_id]);
            return ['success' =>  true, 'message' => 'blocked', 'state' => 'update'];
        } else {
            // Nếu chưa có quan hệ, tạo mới với trạng thái 'blocked'
            $insertQuery = "INSERT INTO friendships (user_id, friend_id, status) VALUES (?, ?, 'blocked')";
            $insertStmt = $this->conn->prepare($insertQuery);
            $insertStmt->execute([$user_id, $friend_id]);
            return ['success' =>  true, 'message' => 'blocked friend', 'state' => 'create'];
        }
    }
}


