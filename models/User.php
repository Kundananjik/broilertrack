<?php
declare(strict_types=1);

class User
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT id, username, role, is_active, created_at FROM users ORDER BY created_at DESC');
        return $stmt->fetchAll() ?: [];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, username, role, is_active, created_at FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, username, role, is_active FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByUsernameExcludingId(string $username, int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, username, role, is_active FROM users WHERE username = :username AND id <> :id');
        $stmt->execute(['username' => $username, 'id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $username, string $passwordHash, string $role): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (username, password_hash, role, is_active) VALUES (:username, :password_hash, :role, 1)'
        );
        return $stmt->execute([
            'username' => $username,
            'password_hash' => $passwordHash,
            'role' => $role,
        ]);
    }

    public function update(int $id, string $username, string $role, ?string $passwordHash = null): bool
    {
        if ($passwordHash !== null) {
            $stmt = $this->pdo->prepare(
                'UPDATE users SET username = :username, role = :role, password_hash = :password_hash WHERE id = :id'
            );
            return $stmt->execute([
                'id' => $id,
                'username' => $username,
                'role' => $role,
                'password_hash' => $passwordHash,
            ]);
        }

        $stmt = $this->pdo->prepare('UPDATE users SET username = :username, role = :role WHERE id = :id');
        return $stmt->execute([
            'id' => $id,
            'username' => $username,
            'role' => $role,
        ]);
    }

    public function setActive(int $id, bool $isActive): bool
    {
        $stmt = $this->pdo->prepare('UPDATE users SET is_active = :is_active WHERE id = :id');
        return $stmt->execute([
            'id' => $id,
            'is_active' => $isActive ? 1 : 0,
        ]);
    }
}
