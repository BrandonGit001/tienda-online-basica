<?php
declare(strict_types=1);

require __DIR__ . "/../../app/includes/auth.php";
start_session();

$_SESSION = [];
session_destroy();

header("Location: /admin/login.php");
exit;
