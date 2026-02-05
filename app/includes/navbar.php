<?php
declare(strict_types=1);
require_once __DIR__ . "/auth.php";
$config = require __DIR__ . "/../config/app.php";
$appName = $config["app_name"];
$waPhone = $config["whatsapp_phone"];
?>
<header class="site-header">
  <nav class="nav">
    <a class="nav__brand" href="/">
      <span class="nav__logo" aria-hidden="true">👟</span>
      <span class="nav__name"><?= htmlspecialchars($appName) ?></span>
    </a>

        <button class="nav__toggle" type="button" aria-controls="navMenu" aria-expanded="false">
          ☰
        </button>

      <div class="nav__menu" id="navMenu">


      <a class="nav__link" href="...">Productos</a>

      <a class="nav__link" href="/productos.php?cat=ofertas">Ofertas</a>

      <a class="nav__cta" target="_blank" rel="noopener"
         href="https://wa.me/<?= urlencode($waPhone) ?>?text=<?= urlencode('Hola, me gustaría información 🙂') ?>">
        WhatsApp
      </a>
      <a class="nav__icon" href="<?= $config["base_url"] ?>/admin/login.php" aria-label="Admin">
  ⚙️
</a>
    </div>
  </nav>
</header>
