<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/controllers/UserController.php';

require_admin();

$pdo = app_db();
$userController = new UserController($pdo);
$editUserId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$editingUser = $editUserId > 0 ? $userController->find($editUserId) : null;

$userFeedback = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $userFeedback = ['success' => false, 'message' => 'Invalid form submission. Please try again.'];
    } else {
        $action = $_POST['action'] ?? 'create';
        if ($action === 'update') {
            $userFeedback = $userController->update($_POST);
        } elseif ($action === 'deactivate') {
            $currentUserId = (int)($_SESSION['user_id'] ?? 0);
            $userFeedback = $userController->deactivate($_POST, $currentUserId);
        } elseif ($action === 'reactivate') {
            $userFeedback = $userController->reactivate($_POST);
        } else {
            $userFeedback = $userController->create($_POST);
        }

        if ($userFeedback['success']) {
            header('Location: users.php');
            exit;
        }
    }
}

$users = $userController->list();
$pageTitle = 'User Management';
include __DIR__ . '/views/layout_header.php';
include __DIR__ . '/views/users.php';
include __DIR__ . '/views/layout_footer.php';
