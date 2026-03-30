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
    <meta name="description" content="Track broiler batches, manage feed usage, and report farm sales and expenses with BroilerTrack.">
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="https://broilertrack.42web.io/">
    <meta property="og:type" content="website">
    <meta property="og:title" content="BroilerTrack | Poultry Farm Management & Reporting">
    <meta property="og:description" content="BroilerTrack helps poultry farms track batches, sales, and expenses in one place.">
    <meta property="og:url" content="https://broilertrack.42web.io/">
    <meta property="og:site_name" content="BroilerTrack">
    <meta property="og:image" content="https://broilertrack.42web.io/assets/img/logo.png">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="BroilerTrack | Poultry Farm Management & Reporting">
    <meta name="twitter:description" content="Poultry farm management platform for batches, feed, sales, and expense reporting.">
    <meta name="twitter:image" content="https://broilertrack.42web.io/assets/img/logo.png">
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <link rel="apple-touch-icon" href="assets/img/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@600;700&display=swap" rel="stylesheet">
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
        "description": "BroilerTrack is a poultry farm management system for tracking batches, feed usage, sales, and expenses.",
        "publisher": {
            "@type": "Organization",
            "name": "BroilerTrack",
            "url": "https://broilertrack.42web.io/"
        }
    }
    </script>
</head>
<body class="landing-page d-flex flex-column min-vh-100">
    <div class="landing-glow landing-glow-a" aria-hidden="true"></div>
    <div class="landing-glow landing-glow-b" aria-hidden="true"></div>
    <header class="landing-header container">
        <a href="index.php" class="landing-brand">
            <img src="assets/img/logo.png" alt="BroilerTrack logo" loading="eager" width="44" height="44">
            <span>BroilerTrack</span>
        </a>
        <a href="login.php" class="landing-login-link">Login</a>
    </header>
    <main class="landing-main container flex-grow-1">
        <section class="landing-hero">
            <div class="landing-copy">
                <p class="landing-kicker">BROILER OPERATIONS DASHBOARD</p>
                <h1>Track sales, costs, and flock status with less paperwork.</h1>
                <p class="landing-subtext">BroilerTrack gives your team one place to manage batches, feed usage, collections, and reporting.</p>
                <div class="landing-actions">
                    <a href="login.php" class="landing-cta-primary">Open System</a>
                </div>
            </div>
            <aside class="landing-metrics">
                <article class="landing-metric-card">
                    <p class="landing-metric-label">Sales Collection</p>
                    <p class="landing-metric-value">Paid + Balance</p>
                    <p class="landing-metric-note">Track settled and outstanding amounts on each sale.</p>
                </article>
                <article class="landing-metric-card">
                    <p class="landing-metric-label">Batch Overview</p>
                    <p class="landing-metric-value">Live Inventory</p>
                    <p class="landing-metric-note">Monitor current alive birds and sales movement by batch.</p>
                </article>
                <article class="landing-metric-card">
                    <p class="landing-metric-label">Reporting</p>
                    <p class="landing-metric-value">CSV, Excel, PDF</p>
                    <p class="landing-metric-note">Generate financial and sales reports in a few clicks.</p>
                </article>
            </aside>
        </section>
    </main>
    <?php require __DIR__ . '/views/footer_content.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
