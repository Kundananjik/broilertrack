<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

app_start_session();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - BroilerTrack</title>
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <link rel="apple-touch-icon" href="assets/img/logo.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page d-flex flex-column min-vh-100">
    <div class="login-wrapper container flex-grow-1">
        <div class="login-card welcome-card">
            <img src="assets/img/logo.png" alt="BroilerTrack logo" class="login-logo">
            <h1>Welcome to BroilerTrack Management System</h1>
            <p class="muted">Track batches, monitor performance, and manage farm sales with confidence.</p>
            <div class="d-grid gap-2 mt-3">
                <a href="login.php" class="btn btn-primary">Login</a>
            </div>
        </div>
    </div>
    <?php require __DIR__ . '/views/footer_content.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
