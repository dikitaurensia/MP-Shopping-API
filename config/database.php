<?php
class Database
{
    //mp
    // private $host = "127.0.0.1";
    private $host = '193.203.168.35';
    // private $database_name = 'u104359481_tracking_acc';
    // private $username = 'u104359481_admin_acc';
    // private $password = 'AileenTech13!';

    private $database_name = 'u104359481_tracking';
    private $username = 'u104359481_admin';
    private $password = 'A1b2c3d4!';

    public $conn;

    public function getConnection()
    {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                'mysql:host=' . $this->host . ';dbname=' . $this->database_name,
                $this->username,
                $this->password
            );
            $this->conn->exec('set names utf8');
        } catch (PDOException $exception) {
            echo 'Database could not be connected: ' . $exception->getMessage();
        }
        return $this->conn;
    }
}
