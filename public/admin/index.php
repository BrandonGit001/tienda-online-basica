<?php
declare(strict_types=1);

require __DIR__ . "/../../app/includes/auth.php";
start_session();
require_admin();

require __DIR__ . "/../../app/config/app.php";
$title = "Dashboard Admin";

require __DIR__ . "/../../app/includes/header.php";
require __DIR__ . "/../../app/includes/navbar.php";
?>

<main class="container">
  <section class="section">
    <h1 class="section__title">Dashboard</h1>
    <p class="muted">Bienvenido, <?= htmlspecialchars($_SESSION["admin_username"] ?? "") ?></p>

    <div class="dashboard">
      <a class="dashboard__card" href="<?= $config["base_url"] ?>/admin/productos/">
        <strong>Productos</strong>
        
      </a>
        <a class="btn btn--ghost" href="<?= $config["base_url"] ?>/admin/categorias/">
        Categorías
      </a>
      <a class="dashboard__card" href="<?= $config["base_url"] ?>/admin/logout.php">
        <strong>Cerrar sesión</strong>
   
      </a>
    </div>
  </section>
</main>

<?php require __DIR__ . "/../../app/includes/footer.php"; ?>
