<?php
declare(strict_types=1);

$config = require __DIR__ . "/../config/app.php";
$title = $title ?? $config["app_name"];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title) ?></title>

  <link rel="stylesheet" href="<?= $config["base_url"] ?>/assets/css/app.css">


</head>
<script src="<?= $config["base_url"] ?>/assets/js/app.js" defer></script>

<body>
