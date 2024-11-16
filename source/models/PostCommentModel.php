<?php
require_once 'BaseModel.php';
class PostCommentModel extends BaseModel
{
    protected function getTable()
    {
        return 'post_comments';
    }

    // Thêm bình luận mới
    public function createComment($postId, $userCmtId, $content)
    {
        // Đầu tiên, lấy thứ tự hiện tại của các bình luận cho bài viết
        $sqlOrder = "SELECT COALESCE(MAX(`order`), 0) + 1 AS next_order FROM " . $this->getTable() . " WHERE post_id = :post_id";
        $stmtOrder = $this->conn->prepare($sqlOrder);
        $stmtOrder->bindParam(':post_id', $postId);
        $stmtOrder->execute();
        $nextOrder = $stmtOrder->fetchColumn();

        // Sau đó, chèn bình luận mới vào cơ sở dữ liệu
        $sql = "INSERT INTO " . $this->getTable() . " (post_id, user_cmt_id, content, `order`, created_at, updated_at) 
                VALUES (:post_id, :user_cmt_id, :content, :order, NOW(), NOW())";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':post_id', $postId);
        $stmt->bindParam(':user_cmt_id', $userCmtId);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':order', $nextOrder); // Gán thứ tự mới cho bình luận
        if ($stmt->execute()) {
            // Lấy ID của bình luận vừa chèn
            $lastId = $this->conn->lastInsertId();
            // Truy vấn để lấy thông tin chi tiết của bình luận vừa chèn
            $sqlSelect = "SELECT * FROM " . $this->getTable() . " WHERE id = :id";
            $stmtSelect = $this->conn->prepare($sqlSelect);
            $stmtSelect->bindParam(':id', $lastId);
            $stmtSelect->execute();
            return $stmtSelect->fetch(PDO::FETCH_ASSOC); // Trả về dữ liệu bình luận vừa được tạo
        }

        return false; // Trả về false nếu không chèn được
    }

    // Cập nhật bình luận
    public function updateComment($id, $content)
    {
        $sql = "UPDATE " . $this->getTable() . " SET content = :content, updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    // Xóa bình luận
    public function deleteComment($id)
    {
        $sql = "DELETE FROM " . $this->getTable() . " WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    // Lấy tất cả bình luận của một bài viết
    public function getCommentsByPostId($postId)
    {
        $sql = "SELECT * FROM " . $this->getTable() . " WHERE post_id = :post_id ORDER BY `order` ASC, created_at ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':post_id', $postId);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy bình luận theo ID
    public function getCommentById($id)
    {
        $sql = "SELECT * FROM " . $this->getTable() . " WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
