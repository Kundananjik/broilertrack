<?php
if (!isset($_SESSION)) {
    session_start();
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'BroilerTrack', ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="robots" content="noindex,nofollow,noarchive">
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <link rel="apple-touch-icon" href="assets/img/logo.png">
    <script>
        (function () {
            try {
                var savedTheme = localStorage.getItem('bt_theme');
                if (savedTheme === 'dark' || savedTheme === 'light') {
                    document.documentElement.setAttribute('data-theme', savedTheme);
                }
            } catch (e) {}
        })();
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="mobile-topbar d-lg-none">
    <div class="mobile-topbar-brand">
        <img src="assets/img/logo.png" alt="BroilerTrack logo" class="mobile-logo">
    </div>
    <button class="btn btn-outline-light" type="button"
            data-bs-toggle="collapse" data-bs-target="#sidebarNav"
            aria-controls="sidebarNav" aria-expanded="false" aria-label="Toggle navigation">
        Menu
    </button>
</div>
<div class="app-shell d-flex flex-column flex-lg-row min-vh-100">
    <aside class="sidebar d-flex flex-column flex-shrink-0">
        <div class="d-flex justify-content-between align-items-center w-100 mb-3">
            <div class="brand mb-0">
                <img src="assets/img/logo.png" alt="BroilerTrack logo" class="sidebar-logo">
            </div>
        </div>
        <div class="user-meta mb-3">
            <span><?= htmlspecialchars($_SESSION['username'] ?? 'Administrator', ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="collapse d-lg-block w-100" id="sidebarNav">
            <nav class="nav flex-lg-column flex-row flex-wrap gap-2">
                <a class="nav-link text-white" href="dashboard.php">Dashboard</a>
                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                <a class="nav-link text-white" href="batches.php">Batches</a>
                <a class="nav-link text-white" href="add_batch.php">Add Batch</a>
                <a class="nav-link text-white" href="expenses.php">Expenses</a>
                <a class="nav-link text-white" href="feed_usage.php">Feed Usage</a>
                <a class="nav-link text-white" href="users.php">Users</a>
                <a class="nav-link text-white" href="reports.php">Reports</a>
                <a class="nav-link text-white" href="audit_logs.php">Audit Logs</a>
                <?php endif; ?>
                <a class="nav-link text-white" href="sales.php">Sales</a>
                <a class="nav-link text-white logout" href="logout.php">Logout</a>
            </nav>
        </div>
    </aside>
    <main class="content flex-grow-1">
        <header class="page-header">
            <h1><?= htmlspecialchars($pageTitle ?? 'BroilerTrack', ENT_QUOTES, 'UTF-8'); ?></h1>
            <button type="button" class="theme-toggle" id="themeToggle" aria-pressed="false">Dark Mode</button>
        </header>
