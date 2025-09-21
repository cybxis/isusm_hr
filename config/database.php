<?php
/**
 * Database Configuration
 * HR Management System
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'db_hr';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    public $conn;

    /**
     * Database Connection
     * @return PDO connection object
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Set timezone to Asia/Manila
            $this->conn->exec("SET time_zone = '+08:00'");
            
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
            die();
        }
        
        return $this->conn;
    }
}
?>