<?php
declare(strict_types=1);

$config = require __DIR__ . "/../config/app.php";
$appName = $config["app_name"];
$year = (int)date("Y");
?>
<footer class="footer">
  <div class="footer__inner">
    <div class="footer__brand">
      <div class="footer__name"><?= htmlspecialchars($appName) ?></div>
      <div class="footer__small">Catálogo • WhatsApp • Inventario</div>
    </div>

    <div class="footer__links">
      <a class="footer__link" href="/productos.php">Productos</a>
      <a class="footer__link" href="/">Inicio</a>
    </div>

    <div class="footer__copy">© <?= $year ?> <?= htmlspecialchars($appName) ?></div>
  </div>
</footer>

<script src="<?= $config["base_url"] ?>/assets/js/app.js" defer></script>

</body>
</html>
