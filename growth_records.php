<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

require_admin();
http_response_code(410);
$pageTitle = 'Feature Removed';
include __DIR__ . '/views/layout_header.php';
?>
<section class="empty-state">
    <p>Growth and weighing records have been removed from this system.</p>
    <p><a href="dashboard.php">Return to Dashboard</a></p>
</section>
<?php include __DIR__ . '/views/layout_footer.php'; ?>
