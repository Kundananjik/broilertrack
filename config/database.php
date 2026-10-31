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
        $environment = strtolower(trim((string)(getenv('BROILERTRACK_ENV') ?: 'development')));
        if (!in_array($environment, ['development', 'production'], true)) {
            throw new RuntimeException('BROILERTRACK_ENV must be development or production.');
        }

        $settings = [
            'host' => getenv('BROILERTRACK_DB_HOST'),
            'name' => getenv('BROILERTRACK_DB_NAME'),
            'user' => getenv('BROILERTRACK_DB_USER'),
            'pass' => getenv('BROILERTRACK_DB_PASS'),
        ];
        if ($environment === 'production') {
            foreach ($settings as $key => $value) {
                if ($value === false || trim($value) === '') {
                    throw new RuntimeException('All BROILERTRACK_DB_* settings must be configured in production.');
                }
            }
        }

        $this->host = $settings['host'] ?: 'localhost';
        $this->dbName = $settings['name'] ?: 'broilertrack';
        $this->username = $settings['user'] ?: 'root';
        $this->password = $settings['pass'] ?: '';
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
