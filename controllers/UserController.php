<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/User.php';

class UserController
{
    private User $userModel;

    public function __construct(PDO $pdo)
    {
        $this->userModel = new User($pdo);
    }

    public function list(): array
    {
        return $this->userModel->all();
    }

    public function find(int $id): ?array
    {
        return $this->userModel->findById($id);
    }

    public function create(array $input): array
    {
        $username = trim($input['username'] ?? '');
        $password = (string)($input['password'] ?? '');
        $confirmPassword = (string)($input['confirm_password'] ?? '');
        $role = trim($input['role'] ?? '');
        $allowedRoles = ['admin', 'salesperson'];

        if ($username === '' || $password === '' || $confirmPassword === '') {
            return ['success' => false, 'message' => 'Username and password fields are required.'];
        }

        if (!preg_match('/^[a-zA-Z0-9_.-]{3,60}$/', $username)) {
            return ['success' => false, 'message' => 'Username must be 3-60 chars and use letters, numbers, dot, underscore or hyphen.'];
        }

        if (!$this->passwordMeetsPolicy($password)) {
            return ['success' => false, 'message' => 'Password must be at least 12 characters with uppercase and lowercase letters, a number, and a symbol, or a passphrase of at least 4 words and 20 characters.'];
        }

        if ($password !== $confirmPassword) {
            return ['success' => false, 'message' => 'Password confirmation does not match.'];
        }

        if (!in_array($role, $allowedRoles, true)) {
            return ['success' => false, 'message' => 'Select a valid role.'];
        }

        if ($this->userModel->findByUsername($username)) {
            return ['success' => false, 'message' => 'Username already exists.'];
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        if ($passwordHash === false) {
            return ['success' => false, 'message' => 'Unable to create password hash.'];
        }

        $created = $this->userModel->create($username, $passwordHash, $role);
        if ($created && function_exists('audit_log')) {
            audit_log('users', 'create', 'user', null, [
                'username' => $username,
                'role' => $role,
            ]);
        }
        return $created
            ? ['success' => true, 'message' => 'User created successfully.']
            : ['success' => false, 'message' => 'Unable to create user.'];
    }

    public function update(array $input): array
    {
        $userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
        if ($userId <= 0) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        $existing = $this->userModel->findById($userId);
        if (!$existing) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        $username = trim($input['username'] ?? '');
        $role = trim($input['role'] ?? '');
        $password = (string)($input['password'] ?? '');
        $confirmPassword = (string)($input['confirm_password'] ?? '');
        $allowedRoles = ['admin', 'salesperson'];

        if ($username === '') {
            return ['success' => false, 'message' => 'Username is required.'];
        }

        if (!preg_match('/^[a-zA-Z0-9_.-]{3,60}$/', $username)) {
            return ['success' => false, 'message' => 'Username must be 3-60 chars and use letters, numbers, dot, underscore or hyphen.'];
        }

        if (!in_array($role, $allowedRoles, true)) {
            return ['success' => false, 'message' => 'Select a valid role.'];
        }

        if ($this->userModel->findByUsernameExcludingId($username, $userId)) {
            return ['success' => false, 'message' => 'Username already exists.'];
        }

        $passwordHash = null;
        if ($password !== '' || $confirmPassword !== '') {
            if (!$this->passwordMeetsPolicy($password)) {
                return ['success' => false, 'message' => 'Password must be at least 12 characters with uppercase and lowercase letters, a number, and a symbol, or a passphrase of at least 4 words and 20 characters.'];
            }
            if ($password !== $confirmPassword) {
                return ['success' => false, 'message' => 'Password confirmation does not match.'];
            }
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            if ($passwordHash === false) {
                return ['success' => false, 'message' => 'Unable to create password hash.'];
            }
        }

        $updated = $this->userModel->update($userId, $username, $role, $passwordHash);
        if ($updated && function_exists('audit_log')) {
            audit_log('users', 'update', 'user', $userId, [
                'username' => $username,
                'role' => $role,
                'password_changed' => $passwordHash !== null,
            ]);
        }
        return $updated
            ? ['success' => true, 'message' => 'User updated successfully.']
            : ['success' => false, 'message' => 'Unable to update user.'];
    }

    public function changeOwnPassword(array $input, int $userId): array
    {
        $password = (string)($input['password'] ?? '');
        $confirmPassword = (string)($input['confirm_password'] ?? '');
        if (!$this->userModel->findById($userId)) {
            return ['success' => false, 'message' => 'User not found.'];
        }
        if (!$this->passwordMeetsPolicy($password)) {
            return ['success' => false, 'message' => 'Password must be at least 12 characters with uppercase and lowercase letters, a number, and a symbol, or a passphrase of at least 4 words and 20 characters.'];
        }
        if ($password !== $confirmPassword) {
            return ['success' => false, 'message' => 'Password confirmation does not match.'];
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        if ($passwordHash === false) {
            return ['success' => false, 'message' => 'Unable to create password hash.'];
        }
        $updated = $this->userModel->changePassword($userId, $passwordHash);
        if ($updated && function_exists('audit_log')) {
            audit_log('auth', 'password_changed', 'user', $userId, []);
        }

        return $updated
            ? ['success' => true, 'message' => 'Password changed successfully.']
            : ['success' => false, 'message' => 'Unable to change password.'];
    }

    public function deactivate(array $input, int $currentUserId): array
    {
        $userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
        if ($userId <= 0) {
            return ['success' => false, 'message' => 'User not found.'];
        }
        if ($userId === $currentUserId) {
            return ['success' => false, 'message' => 'You cannot deactivate your own account.'];
        }

        $existing = $this->userModel->findById($userId);
        if (!$existing) {
            return ['success' => false, 'message' => 'User not found.'];
        }
        if ((int)$existing['is_active'] === 0) {
            return ['success' => false, 'message' => 'User is already inactive.'];
        }

        $saved = $this->userModel->setActive($userId, false);
        if ($saved && function_exists('audit_log')) {
            audit_log('users', 'deactivate', 'user', $userId, [
                'username' => (string)$existing['username'],
            ]);
        }
        return $saved
            ? ['success' => true, 'message' => 'User deactivated successfully.']
            : ['success' => false, 'message' => 'Unable to deactivate user.'];
    }

    public function reactivate(array $input): array
    {
        $userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
        if ($userId <= 0) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        $existing = $this->userModel->findById($userId);
        if (!$existing) {
            return ['success' => false, 'message' => 'User not found.'];
        }
        if ((int)$existing['is_active'] === 1) {
            return ['success' => false, 'message' => 'User is already active.'];
        }

        $saved = $this->userModel->setActive($userId, true);
        if ($saved && function_exists('audit_log')) {
            audit_log('users', 'reactivate', 'user', $userId, [
                'username' => (string)$existing['username'],
            ]);
        }
        return $saved
            ? ['success' => true, 'message' => 'User reactivated successfully.']
            : ['success' => false, 'message' => 'Unable to reactivate user.'];
    }

    private function passwordMeetsPolicy(string $password): bool
    {
        $mixedPassword = strlen($password) >= 12
            && preg_match('/[A-Z]/', $password) === 1
            && preg_match('/[a-z]/', $password) === 1
            && preg_match('/[0-9]/', $password) === 1
            && preg_match('/[^A-Za-z0-9\s]/', $password) === 1;
        $passphrase = strlen($password) >= 20
            && preg_match('/^\S+(?:\s+\S+){3,}$/u', $password) === 1;

        return $mixedPassword || $passphrase;
    }
}
