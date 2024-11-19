<?php
require_once 'vendor/autoload.php'; // Dùng Composer để tự động load thư viện

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Server\IoServer;

class WebSocketServer implements MessageComponentInterface
{
    protected $clients;
    public function __construct()
    {
        $this->clients = new SplObjectStorage;
        echo "Server started...\n"; // Thông báo khi server bắt đầu
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New connection: ({$conn->resourceId})\n"; // Thông báo khi có kết nối mới
    }
    public function onMessage(ConnectionInterface $from, $msg)
    {
        // Gửi thông điệp cho tất cả các client
        $this->sendMessageToAllClients($msg);
        // Chuyển tin nhắn thành một array
        $data = json_decode($msg, true);

        // Kiểm tra xem người nhận có kết nối hay không
        foreach ($this->clients as $client) {
            if ($client !== $from) { // Không gửi lại tin nhắn cho người gửi
                // $client->send(json_encode([
                //     'from' => $data['fromUserId'],
                //     'content' => $data['content'],
                // ]));
                 $client->send(json_encode(['data'=>$data]));
            }
        }
    }
    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} closed\n"; // Thông báo khi kết nối đóng
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "Error: " . $e->getMessage() . "\n"; // Thông báo lỗi khi có lỗi xảy ra
        $conn->close();
    }
    // Hàm gửi thông điệp tới tất cả các client đang kết nối
    public function sendMessageToAllClients($message)
    {
        foreach ($this->clients as $client) {
            $client->send($message); // Gửi thông điệp tới từng client
        }
    }
}

// Khởi động WebSocket server trên cổng 8081
echo "Starting WebSocket server...\n"; // Thêm thông báo khi bắt đầu khởi động server
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new WebSocketServer()
        )
    ),
    8081
);

echo "Server listening on port 8081...\n"; // Thông báo khi server đang lắng nghe
$server->run();
