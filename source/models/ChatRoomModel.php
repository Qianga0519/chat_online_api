<?php
require_once 'BaseModel.php'; // Đảm bảo đã bao gồm file BaseModel
class ChatRoomModel extends BaseModel
{
    protected function getTable()
    {
        return 'chat_rooms';
    }

    // Tạo phòng chat
    public function createChatRoom($userId1, $userId2)
    {
        $sql = "INSERT INTO " . $this->getTable() . " (user_id_1, user_id_2, created_at) 
                VALUES (:user_id_1, :user_id_2, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id_1', $userId1);
        $stmt->bindParam(':user_id_2', $userId2);
        return $stmt->execute();
    }

    // Xóa phòng chat
    public function deleteChatRoom($id)
    {
        $sql = "DELETE FROM " . $this->getTable() . " WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Lấy phòng chat giữa hai người dùng
    public function getChatRoom($userId1, $userId2)
    {
        $sql = "SELECT 
                    chat_rooms.*, 
                    media.url AS avatar_user_2 
                FROM 
                    " . $this->getTable() . " AS chat_rooms
                LEFT JOIN 
                    media ON chat_rooms.user_id_2 = media.user_id AND media.is_avatar = 1
                WHERE 
                    (chat_rooms.user_id_1 = :user_id_1 AND chat_rooms.user_id_2 = :user_id_2) 
                    OR (chat_rooms.user_id_1 = :user_id_2 AND chat_rooms.user_id_2 = :user_id_1)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id_1', $userId1);
        $stmt->bindParam(':user_id_2', $userId2);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getChatRoomsByUserId($userId)
    {
        $sql = "SELECT 
            chat_rooms.*,
            media.url AS user2_avatar,
            users.name, users.status
        FROM 
            chat_rooms
        LEFT JOIN 
            media ON chat_rooms.user_id_2 = media.user_id AND media.is_avatar = 1
        LEFT JOIN 
            users ON chat_rooms.user_id_2 = users.id
        WHERE 
            chat_rooms.user_id_1 = :user_id OR chat_rooms.user_id_2 = :user_id";
        // $sql = "SELECT * FROM chat_rooms WHERE user_id_1 = :user_id OR user_id_2 = :user_id";
        $stmt = $this->conn->prepare($sql);
        // Ràng buộc tham số và thực thi
        $stmt->execute([':user_id' => $userId]);

        // Lấy tất cả các phòng chat cùng với avatar của user_id_2
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Trả về danh sách phòng chat nếu có, nếu không trả về mảng rỗng
        return $rooms ?: [];
    }
    public function getChatRoomById($roomId)
    {
        $sql = "SELECT 
                chat_rooms.*, 
                media.url AS avatar_user_2 
            FROM 
                " . $this->getTable() . " AS chat_rooms
            LEFT JOIN 
                media ON chat_rooms.user_id_2 = media.user_id AND media.is_avatar = 1
            WHERE 
                chat_rooms.id = :room_id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':room_id', $roomId);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getChatRommId($roomId)
    {
        $sql = "SELECT * FROM " . $this->getTable() .  " WHERE 
        chat_rooms.id = :room_id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':room_id', $roomId);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
