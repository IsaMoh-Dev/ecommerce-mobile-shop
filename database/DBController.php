<?php
// Load config if not already loaded
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../config.php';
}

class DBController
{
    protected $host     = DB_HOST;
    protected $user     = DB_USER;
    protected $password = DB_PASSWORD;
    protected $database = DB_NAME;

    public $con = null;

    public function __construct() {
        $this->con = mysqli_connect($this->host, $this->user, $this->password, $this->database);
        if (!$this->con) {
            die("Database connection failed: " . mysqli_connect_error());
        }
        mysqli_set_charset($this->con, 'utf8mb4');
    }

    public function __destruct() {
        $this->closeConnection();
    }

    protected function closeConnection() {
        if ($this->con != null) {
            $this->con->close();
            $this->con = null;
        }
    }
}
