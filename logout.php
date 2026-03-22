<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

app_start_session();
session_unset();
session_destroy();
header('Location: login.php');
exit;
