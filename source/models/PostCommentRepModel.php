<?php
require_once 'BaseModel.php';
class PostCommentRepModel extends BaseModel
{
    protected function getTable()
    {
        return 'post_comment_rep';
    }

    // Thêm phản hồi cho bình luận
    public function createCommentRep($cmtId, $userId, $content, $order)
    {
        // Câu lệnh SQL để thêm dữ liệu
        $sql = "INSERT INTO " . $this->getTable() . " (cmt_id, user_id, content, `order`, created_at, updated_at) 
                VALUES (:cmt_id, :user_id, :content, :order, NOW(), NOW())";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':cmt_id', $cmtId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':order', $order);

        if ($stmt->execute()) {
            // Lấy ID của bản ghi vừa được thêm
            $lastInsertId = $this->conn->lastInsertId();

            // Lấy dữ liệu của bản ghi vừa thêm
            $sql = "SELECT * FROM " . $this->getTable() . " WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $lastInsertId);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return false; // Trả về false nếu thêm thất bại
    }

    // Cập nhật phản hồi
    public function updateCommentRep($id, $content)
    {
        $sql = "UPDATE " . $this->getTable() . " SET content = :content, updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    // Xóa phản hồi
    public function deleteCommentRep($id)
    {
        $sql = "DELETE FROM " . $this->getTable() . " WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    // Lấy tất cả phản hồi của một bình luận
    public function getRepsByCommentId($cmtId)
    {
        $sql = "SELECT * FROM " . $this->getTable() . " WHERE cmt_id = :cmt_id ORDER BY `order`";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':cmt_id', $cmtId);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
