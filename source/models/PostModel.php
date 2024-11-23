<?php
require_once 'BaseModel.php';
class Post extends BaseModel
{
    protected function getTable()
    {
        return 'posts'; // Tên bảng

    }

    private $table = 'posts';
    public function create(array $data)
    {
        $stmt = $this->conn->prepare("INSERT INTO " . $this->getTable() . " (user_id, content, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
        $stmt->execute([$data['user_id'], $data['content']]);
        return $this->conn->lastInsertId();
    }
    public function read($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->getTable() . " WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function update($id, array $data)
    {
        $stmt = $this->conn->prepare("UPDATE " . $this->getTable() . " SET content = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$data['content'], $id]);
        return $stmt->rowCount(); // Trả về số bản ghi bị ảnh hưởng
    }

    public function delete($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM " . $this->getTable() . " WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount(); // Trả về số bản ghi bị xóa
    }
    public function getPaginatedUsers($page, $limit)
    {
        $offset = ($page - 1) * $limit; // Tính toán offset dựa trên số trang và giới hạn
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->getTable() . " LIMIT :limit OFFSET :offset");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getAll()
    {
        $sql = "SELECT * FROM " . $this->getTable();
        $stmt = $this->conn->query($sql); // Sử dụng kết nối đã lưu trữ
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    //POst id 
    public function getPostsByUserId($userId)
    {
        // $sql = "SELECT * FROM `posts` INNER JOIN users ON users.id = posts.user_id   WHERE user_id = :id";
        $sql = "SELECT 
    posts.id AS id, 
    posts.content AS post_content, 
    posts.created_at AS post_created_at,
    users.name, 
    'original' AS post_type,
    media.url AS post_image_url,
    NULL AS sharer_name,
    -- Subquery để lấy số lượt like cho mỗi bài viết
    (SELECT COUNT(*) FROM post_like WHERE post_like.post_id = posts.id) AS total_likes,
     (SELECT COUNT(*) FROM post_comments WHERE post_comments.post_id = posts.id) AS total_cmt,
    -- Subquery để lấy số lượt share cho mỗi bài viết
    (SELECT COUNT(*) FROM post_share WHERE post_share.post_id = posts.id) AS share_count
FROM 
    posts 
INNER JOIN 
    users ON users.id = posts.user_id 
LEFT JOIN 
    media ON media.post_id = posts.id AND media.is_avatar = 0
    
WHERE 
    posts.user_id = :id

UNION ALL

SELECT 
    posts.id AS post_id, 
    posts.content AS post_content, 
    post_share.created_at AS post_created_at,
    users.name AS user_name, 
    'shared' AS post_type,
    media.url AS post_image_url,
    sharer.name AS sharer_name,
    -- Subquery để lấy số lượt like cho bài viết được chia sẻ
    -- (SELECT COUNT(*) FROM post_like WHERE post_like.post_id = posts.id) AS like_count,
     (SELECT COUNT(*) FROM post_like WHERE post_like.post_id = post_share.id) AS like_count,
    (SELECT COUNT(*) FROM post_comments WHERE post_comments.post_cmt_id = post_share.id) AS total_cmt,
    (SELECT COUNT(*) FROM post_share WHERE post_share.post_id = posts.id) AS share_count
FROM 
    post_share 
INNER JOIN 
    posts ON post_share.post_id = posts.id 
INNER JOIN 
    users ON users.id = posts.user_id 
LEFT JOIN 
    media ON media.post_id = posts.id AND media.is_avatar = 0
     INNER JOIN users AS sharer ON post_share.user_share_id = sharer.id 
WHERE 
    post_share.user_share_id = :id
    ORDER BY 
    post_created_at DESC

";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function searchPost($keyword, $offset, $limit, $currentUserId, $postFrom, $selectedOrder)
    {
        try {
            $sql = "";

            // Truy vấn bài viết gốc
            $sql .= "SELECT 
            avatar_media.url AS urluser, 
            avatar_post.url AS urlpost, 
            users.name, 
            posts.content, 
            posts.id as id,
            posts.created_at AS post_created_at,  
            (SELECT COUNT(*) FROM post_like WHERE post_like.post_id = posts.id) AS total_likes,
              (SELECT COUNT(*) FROM post_comments WHERE post_comments.post_id = posts.id) AS total_cmt,
            (SELECT COUNT(*) FROM post_share WHERE post_share.post_id = posts.id) AS share_count,
            'original' AS post_type,
            NULL AS sharer_name
        FROM posts 
        JOIN users ON posts.user_id = users.id 
        LEFT JOIN media AS avatar_media ON avatar_media.user_id = users.id AND avatar_media.is_avatar = 1 
        LEFT JOIN media AS avatar_post ON avatar_post.post_id = posts.id AND avatar_post.is_avatar = 0 
        WHERE (posts.content LIKE :keyword OR MATCH(posts.content) AGAINST (:keyword IN BOOLEAN MODE))";

            // Lọc theo `postFrom`
            if ($postFrom == 1) {
                // Bài viết từ bạn bè
                $sql .= " AND posts.user_id IN (
                SELECT friend_id FROM friendships 
                WHERE user_id = :currentUserId AND status = 'accepted'
            )";
            } elseif ($postFrom == 2) {
                // Bài viết từ người khác (không phải bạn bè và không phải của user)
                $sql .= " AND posts.user_id NOT IN (
                SELECT friend_id FROM friendships 
                WHERE user_id = :currentUserId AND status = 'accepted'
            ) 
            AND posts.user_id != :currentUserId";
            }

            $sql .= " GROUP BY posts.id, avatar_media.url, avatar_post.url, users.name, posts.content, posts.created_at";

            // UNION ALL để bao gồm bài viết chia sẻ
            $sql .= " UNION ALL 
        SELECT 
            avatar_media.url AS urluser, 
            avatar_post.url AS urlpost, 
            users.name, 
            posts.content, 
            posts.id as id,
            post_share.created_at AS post_created_at,
            (SELECT COUNT(*) FROM post_like WHERE post_like.post_id = posts.id) AS like_count,
             (SELECT COUNT(*) FROM post_comments WHERE post_comments.post_cmt_id = post_share.id) AS total_cmt,
            (SELECT COUNT(*) FROM post_share WHERE post_share.post_id = posts.id) AS share_count,
            'shared' AS post_type,
            sharer.name AS sharer_name
        FROM post_share 
        INNER JOIN users AS sharer ON post_share.user_share_id = sharer.id 
        INNER JOIN posts ON post_share.post_id = posts.id 
        INNER JOIN users ON users.id = posts.user_id 
        LEFT JOIN media AS avatar_media ON avatar_media.user_id = users.id AND avatar_media.is_avatar = 1 
        LEFT JOIN media AS avatar_post ON avatar_post.post_id = posts.id AND avatar_post.is_avatar = 0 
        WHERE (posts.content LIKE :keyword OR MATCH(posts.content) AGAINST (:keyword IN BOOLEAN MODE))";

            // Điều kiện cho bài viết chia sẻ theo `postFrom`
            if ($postFrom == 1) {
                // Bài chia sẻ từ bạn bè
                $sql .= " AND post_share.user_share_id IN (
                SELECT friend_id FROM friendships 
                WHERE user_id = :currentUserId AND status = 'accepted'
            )";
            } elseif ($postFrom == 2) {
                // Bài chia sẻ từ người khác (không phải bạn bè và không phải của user)
                $sql .= " AND post_share.user_share_id NOT IN (
                SELECT friend_id FROM friendships 
                WHERE user_id = :currentUserId AND status = 'accepted'
            ) 
            AND post_share.user_share_id != :currentUserId";
            }

            // Sắp xếp theo lựa chọn của người dùng
            if ($selectedOrder == 1) {
                $sql .= " ORDER BY post_created_at DESC"; // Thời gian mới nhất
            } elseif ($selectedOrder == 2) {
                $sql .= " ORDER BY post_created_at ASC"; // Thời gian cũ nhất
            }

            $sql .= " LIMIT :offset, :limit";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':keyword', '%' . $keyword . '%', PDO::PARAM_STR);

            if ($postFrom == 1 || $postFrom == 2) {
                $stmt->bindValue(':currentUserId', $currentUserId, PDO::PARAM_INT);
            }

            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return [];
        }
    }

    
    // public function searchPost($keyword, $offset, $limit, $currentUserId, $postFrom, $selectedOrder)
    // {
    //     try {
    //         $sql = "";

    //         // Truy vấn cho bài viết gốc
    //         $sql .= "SELECT 
    //         avatar_media.url AS urluser, 
    //         avatar_post.url AS urlpost, 
    //         users.name, 
    //         posts.content, 
    //         posts.id as post_id,
    //         posts.created_at AS post_created_at,  -- Đặt alias cho created_at
    //         (SELECT COUNT(*) FROM post_like WHERE post_like.post_id = posts.id) AS like_count,
    //         (SELECT COUNT(*) FROM post_share WHERE post_share.post_id = posts.id) AS share_count,
    //         'original' AS post_type,
    //          NULL AS sharer_name
    //         FROM posts 
    //         JOIN users ON posts.user_id = users.id 
    //         LEFT JOIN media AS avatar_media ON avatar_media.user_id = users.id AND avatar_media.is_avatar = 1 
    //         LEFT JOIN media AS avatar_post ON avatar_post.post_id = posts.id AND avatar_post.is_avatar = 0 
    //         WHERE (posts.content LIKE :keyword 
    //             OR MATCH(posts.content) AGAINST (:keyword IN BOOLEAN MODE))";

    //         // Thêm các điều kiện lọc theo `postFrom`
    //         if ($postFrom == 1) {
    //             // Lọc bài viết từ bạn bè
    //             $sql .= " AND posts.user_id IN (
    //             SELECT friend_id FROM friendships 
    //             WHERE user_id = :currentUserId AND status = 'accepted'
    //         )";
    //         } elseif ($postFrom == 2) {
    //             // Lọc bài viết từ người khác, không bao gồm bài viết của chính user
    //             $sql .= " AND posts.user_id NOT IN (
    //             SELECT friend_id FROM friendships 
    //             WHERE user_id = :currentUserId AND status = 'accepted'
    //         ) 
    //         AND posts.user_id != :currentUserId";
    //         }

    //         $sql .= " GROUP BY posts.id, avatar_media.url, avatar_post.url, users.name, posts.content, posts.created_at";

    //         // UNION ALL với truy vấn cho bài viết đã được chia sẻ
    //         $sql .= " UNION ALL 
    //         SELECT 
    //         avatar_post.url AS post_image_url,
    //         avatar.url AS post_image_url,
    //             users.name,
    //             posts.content , 
    //             posts.id as post_id,
    //             post_share.created_at ,

    //             (SELECT COUNT(*) FROM post_like WHERE post_like.post_id = posts.id) AS like_count,
    //             (SELECT COUNT(*) FROM post_share WHERE post_share.post_id = posts.id) AS share_count,
    //             'shared' AS post_type,
    //              sharer.name AS sharer_name
    //         FROM 
    //             post_share 
    //             INNER JOIN 
    //             users AS sharer ON post_share.user_share_id = sharer.id 
    //         INNER JOIN 
    //             posts ON post_share.post_id = posts.id 
    //         INNER JOIN 
    //             users ON users.id = posts.user_id 
    //         LEFT JOIN 
    //             media as avatar_post ON avatar_post.post_id = posts.id AND avatar_post.is_avatar = 0
    //          LEFT JOIN 
    //             media as avatar ON avatar.post_id = posts.id AND avatar.is_avatar = 1  

    //         WHERE posts.content LIKE :keyword OR MATCH(posts.content) AGAINST (:keyword IN BOOLEAN MODE)";

    //         // Thay đổi ORDER BY để sử dụng alias 'post_created_at'
    //         if ($selectedOrder == 1) {
    //             $sql .= " ORDER BY post_created_at DESC"; // Sắp xếp theo thời gian mới nhất
    //         } elseif ($selectedOrder == 2) {
    //             $sql .= " ORDER BY post_created_at ASC"; // Sắp xếp theo thời gian cũ nhất
    //         }

    //         $sql .= " LIMIT :offset, :limit";

    //         $stmt = $this->conn->prepare($sql);
    //         $stmt->bindValue(':keyword', '%' . $keyword . '%', PDO::PARAM_STR);

    //         if ($postFrom == 1 || $postFrom == 2) {
    //             $stmt->bindValue(':currentUserId', $currentUserId, PDO::PARAM_INT);
    //         }

    //         $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    //         $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    //         $stmt->execute();

    //         return $stmt->fetchAll(PDO::FETCH_ASSOC);
    //     } catch (PDOException $e) {
    //         echo "Error: " . $e->getMessage();
    //         return [];
    //     }
    // }

    // public function searchPost($keyword, $offset, $limit, $currentUserId, $postFrom, $selectedOrder)
    // {
    //     try {
    //         $sql = "(SELECT 
    //         posts.id AS post_id, 
    //         posts.content AS post_content, 
    //         posts.created_at AS post_created_at,
    //         users.name AS user_name, 
    //         'original' AS post_type,
    //         media.url AS post_image_url,
    //         (SELECT COUNT(*) FROM post_like WHERE post_like.post_id = posts.id) AS like_count,
    //         (SELECT COUNT(*) FROM post_share WHERE post_share.post_id = posts.id) AS share_count
    //     FROM 
    //         posts 
    //     INNER JOIN 
    //         users ON users.id = posts.user_id 
    //     LEFT JOIN 
    //         media ON media.post_id = posts.id AND media.is_avatar = 0
    //     WHERE 
    //         (posts.content LIKE :keyword OR MATCH(posts.content) AGAINST (:keyword IN BOOLEAN MODE))";

    //         // Áp dụng điều kiện lọc theo nguồn bài viết
    //         if ($postFrom == 1) {
    //             $sql .= " AND posts.user_id IN (
    //             SELECT friend_id FROM friendships 
    //             WHERE user_id = :currentUserId AND status = 'accepted'
    //           ) ";
    //         } elseif ($postFrom == 2) {
    //             $sql .= " AND posts.user_id NOT IN (
    //             SELECT friend_id FROM friendships 
    //             WHERE user_id = :currentUserId AND status = 'accepted'
    //           ) 
    //           AND posts.user_id != :currentUserId ";
    //         }

    //         $sql .= ") UNION ALL (SELECT 
    //         posts.id AS post_id, 
    //         posts.content AS post_content, 
    //         post_share.created_at AS post_created_at,
    //         users.name AS user_name, 
    //         'shared' AS post_type,
    //         media.url AS post_image_url,
    //         (SELECT COUNT(*) FROM post_like WHERE post_like.post_id = posts.id) AS like_count,
    //         (SELECT COUNT(*) FROM post_share WHERE post_share.post_id = posts.id) AS share_count
    //     FROM 
    //         post_share 
    //     INNER JOIN 
    //         posts ON post_share.post_id = posts.id 
    //     INNER JOIN 
    //         users ON users.id = posts.user_id 
    //     LEFT JOIN 
    //         media ON media.post_id = posts.id AND media.is_avatar = 0
    //     WHERE 
    //         (posts.content LIKE :keyword OR MATCH(posts.content) AGAINST (:keyword IN BOOLEAN MODE)))";

    //         // Sắp xếp kết quả sau khi kết hợp
    //         $sql .= " ORDER BY post_created_at " . ($selectedOrder == 1 ? "DESC" : "ASC");

    //         // Giới hạn số lượng bài viết
    //         $sql .= " LIMIT :offset, :limit";

    //         // Chuẩn bị câu truy vấn với PDO
    //         $stmt = $this->conn->prepare($sql);

    //         // Ràng buộc các tham số
    //         $stmt->bindValue(':keyword', '%' . $keyword . '%', PDO::PARAM_STR);
    //         if ($postFrom == 1 || $postFrom == 2) {
    //             $stmt->bindValue(':currentUserId', $currentUserId, PDO::PARAM_INT);
    //         }
    //         $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    //         $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

    //         // Thực thi câu truy vấn và lấy kết quả
    //         $stmt->execute();
    //         return $stmt->fetchAll(PDO::FETCH_ASSOC);
    //     } catch (PDOException $e) {
    //         return ['error' => $e->getMessage()];
    //     }
    // }

    public function getPostsByUserIdInfo($userId)
    {
        // $sql = "SELECT * FROM `posts` INNER JOIN users ON users.id = posts.user_id   WHERE user_id = :id";
        $sql = "SELECT posts.id, 
           posts.content, 
           users.name, 
           posts.created_at, 
           media.url
    FROM posts 
    INNER JOIN users ON users.id = posts.user_id
    LEFT JOIN post_like ON post_like.post_id = posts.id
    LEFT JOIN post_comments ON post_comments.post_id = posts.id
    LEFT JOIN post_share ON post_share.post_id = posts.id
    LEFT JOIN media ON media.post_id = posts.id AND media.is_avatar = 0
    WHERE posts.user_id = :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getPaginatedPosts($lastPostId, $limit)
    {
        $limit = max(1, (int)$limit);
        $lastPostId = (int)$lastPostId; // ID bài viết cuối cùng
        try {
            $stmt = $this->conn->prepare(
                "SELECT p.*,u.name AS name,               
    u.email AS email,               
    m.url AS media_url,         
    m.media_type,               
    COUNT(DISTINCT pc.id) AS total_comments,  
    COUNT(DISTINCT pl.id) AS total_likes,      
    COUNT(DISTINCT ps.id) AS total_shares       
FROM 
    posts p                   
JOIN 
    users u ON p.user_id = u.id   
LEFT JOIN 
    media m ON p.id = m.post_id  
LEFT JOIN 
    post_comments pc ON p.id = pc.post_id   
LEFT JOIN 
    post_like pl ON p.id = pl.post_id       
LEFT JOIN 
    post_share ps ON p.id = ps.post_id      
WHERE 
    p.id > :lastPostId            
GROUP BY 
    p.id                             
ORDER BY 
    p.id ASC                       
LIMIT 
    :limit                        
                      
"
            );
            $stmt->bindParam(':lastPostId', $lastPostId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    public function getPostById($id)
    {
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $this->conn->prepare($sql); // Sử dụng kết nối đã lưu trữ
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createPost($user_id, $content, $media_files = []) {
        // SQL tạo bài post mới
        $sql = "INSERT INTO " . $this->table . " (user_id, content, created_at, updated_at) 
                VALUES (:user_id, :content, NOW(), NOW())";

        // Chuẩn bị câu lệnh SQL
        $stmt = $this->conn->prepare($sql);
        
        // Ràng buộc tham số
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':content', $content);

        // Kiểm tra nếu câu lệnh SQL thực thi thành công
        if ($stmt->execute()) {
            $postId = $this->conn->lastInsertId(); // Lấy ID của bài viết vừa tạo

            // Nếu có media_files, thêm vào bảng media
            if (!empty($media_files)) {
                foreach ($media_files as $media) {
                    // Thêm thông tin media vào cơ sở dữ liệu (có thể cần điều chỉnh theo cách lưu media của bạn)
                    $media_sql = "INSERT INTO media (post_id, url, media_type) VALUES (:post_id, :url, :media_type)";
                    $media_stmt = $this->conn->prepare($media_sql);
                    $media_stmt->bindParam(':post_id', $postId);
                    $media_stmt->bindParam(':url', $media['url']);
                    $media_stmt->bindParam(':media_type', $media['media_type']);
                    $media_stmt->execute();
                }
            }

            return [
                'post_id' => $postId,
                'user_id' => $user_id,
                'content' => $content,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        return false;
    }
    public function deletePostById($id) {
        $sql = "DELETE FROM posts WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute(); // Trả về true nếu xóa thành công
        } catch (PDOException $e) {
            // Ghi log lỗi nếu cần
            error_log("Error deleting post: " . $e->getMessage());
            return false; // Trả về false nếu có lỗi
        }
    }
}
