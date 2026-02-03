<?php
declare(strict_types=1);

function start_session(): void {
  if (session_status() === PHP_SESSION_NONE) {
    // En Docker/localhost esto está bien
    session_start();
  }
}

function is_admin_logged_in(): bool {
  start_session();
  return isset($_SESSION["admin_id"]);
}

function require_admin(): void {
  if (!is_admin_logged_in()) {
    header("Location: /admin/login.php");
    exit;
  }
}
