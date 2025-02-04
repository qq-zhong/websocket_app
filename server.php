<?php
require __DIR__ . '/vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\App;

class WebSocketServer implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        // The server keeps track of connected clients using SplObjectStorage().
        $this->clients = new \SplObjectStorage();
        $this->db = new mysqli("localhost", "root", "", "chat_app");

        if ($this->db->connect_error) {
            die("Database connection failed: " . $this->db->connect_error);
        }
    }
    

    public function onOpen(ConnectionInterface $conn) {
        // When a user opens the page, their WebSocket connection is 
        // attached to the clients list.
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";

        // Fetch last 20 messages from the database
        $result = $this->db->query("SELECT username, message, timestamp FROM messages ORDER BY timestamp DESC LIMIT 20");

        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        $history = array_reverse($history); // Show oldest messages first

        // Send history to the new user
        $conn->send(json_encode(["history" => $history]));
    }
    

    public function onMessage(ConnectionInterface $from, $msg) {
        // Every time a client sends a message, 
        // the server broadcasts it to all connected users.
        echo "Received: $msg\n";
        $data = json_decode($msg, true);
        $username = $this->db->real_escape_string($data['username']);
        $message = $this->db->real_escape_string($data['message']);

        // Save message to database
        $this->db->query("INSERT INTO messages (username, message) VALUES ('$username', '$message')");

        // Prepare message to broadcast
        $response = json_encode(["username" => $username, "message" => $message, "timestamp" => date("Y-m-d H:i:s")]);

        // Send to all clients
        foreach ($this->clients as $client) {
            $client->send($response);
        }
    }
    

    public function onClose(ConnectionInterface $conn) {
        // Removes the disconnected client from the clients list.
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} closed\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }
}

// Start the WebSocket server
$server = new Ratchet\App('localhost', 8080, '0.0.0.0');
$server->route('/chat', new WebSocketServer(), ['*']);
// client connects to ws://localhost:8080/chat.
$server->run();
