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
    <title>BroilerTrack | Poultry Farm Management & Reporting</title>
    <meta name="description" content="Track broiler batches, monitor flock growth, manage feed usage, and report farm sales and expenses with BroilerTrack.">
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="https://broilertrack.42web.io/">
    <meta property="og:type" content="website">
    <meta property="og:title" content="BroilerTrack | Poultry Farm Management & Reporting">
    <meta property="og:description" content="BroilerTrack helps poultry farms track batches, performance, sales, and expenses in one place.">
    <meta property="og:url" content="https://broilertrack.42web.io/">
    <meta property="og:site_name" content="BroilerTrack">
    <meta property="og:image" content="https://broilertrack.42web.io/assets/img/logo.png">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="BroilerTrack | Poultry Farm Management & Reporting">
    <meta name="twitter:description" content="Poultry farm management platform for batches, growth, feed, sales, and expense reporting.">
    <meta name="twitter:image" content="https://broilertrack.42web.io/assets/img/logo.png">
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <link rel="apple-touch-icon" href="assets/img/logo.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/style.css">
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": "BroilerTrack",
        "applicationCategory": "BusinessApplication",
        "operatingSystem": "Web",
        "url": "https://broilertrack.42web.io/",
        "description": "BroilerTrack is a poultry farm management system for tracking batches, growth, feed usage, sales, and expenses.",
        "publisher": {
            "@type": "Organization",
            "name": "BroilerTrack",
            "url": "https://broilertrack.42web.io/"
        }
    }
    </script>
</head>
<body class="login-page d-flex flex-column min-vh-100">
    <div class="login-wrapper container flex-grow-1">
        <div class="login-card welcome-card">
            <img src="assets/img/logo.png" alt="BroilerTrack logo" class="login-logo" loading="eager" width="96" height="96">
            <h1>Welcome to BroilerTrack Management System</h1>
            <p class="muted">Track batches, monitor performance, and manage farm sales with confidence.</p>
            <section class="mt-3 text-start">
                <h2 class="h5">Manage Poultry Operations in One Place</h2>
                <p class="muted mb-2">Monitor growth records, feed usage, and cost trends for every production cycle.</p>
            </section>
            <section class="mt-2 text-start">
                <h2 class="h5">Farm Reporting and Insights</h2>
                <p class="muted mb-0">Generate clear reports on sales, expenses, and batch performance to support better farm decisions.</p>
            </section>
            <div class="d-grid gap-2 mt-3">
                <a href="login.php" class="btn btn-primary">Login</a>
            </div>
        </div>
    </div>
    <?php require __DIR__ . '/views/footer_content.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
