<?php
declare(strict_types=1);

require __DIR__ . "/../app/config/db.php";

$row = $pdo->query("SELECT DATABASE() AS db, NOW() AS now_time")->fetch();

echo "OK ✅<br>";
echo "DB: " . htmlspecialchars((string)$row["db"]) . "<br>";
echo "NOW: " . htmlspecialchars((string)$row["now_time"]) . "<br>";
