<?php
declare(strict_types=1);

class Database
{
    private string $host;
    private string $dbName;
    private string $username;
    private string $password;
    private ?PDO $connection = null;

    public function __construct()
    {
        $this->host = getenv('BROILERTRACK_DB_HOST') ?: 'localhost';
        $this->dbName = getenv('BROILERTRACK_DB_NAME') ?: 'broilertrack';
        $this->username = getenv('BROILERTRACK_DB_USER') ?: 'root';
        $this->password = getenv('BROILERTRACK_DB_PASS') ?: '';
    }

    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $this->host, $this->dbName);
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        }

        return $this->connection;
    }
}
