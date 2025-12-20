<?php
class Database {
    private $host;
    private $user;
    private $pass;
    private $db;
    private $conn;

    public function __construct($host, $user, $pass, $db) {
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
        $this->db   = $db;
        $this->connect();
    }

    private function connect() {
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->db);
        if ($this->conn->connect_error) {
            throw new Exception('Connection failed: ' . $this->conn->connect_error);
        }
        // Keep charset consistent
        $this->conn->set_charset('utf8mb4');
    }

    public function getConnection() {
        return $this->conn;
    }

    public function close() {
        if ($this->conn) $this->conn->close();
    }
}
?>