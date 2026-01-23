<?php
declare(strict_types=1);
$title = "Producto";
require __DIR__ . "/../app/includes/header.php";
require __DIR__ . "/../app/includes/navbar.php";
?>

<main class="container">
  <section class="section">
    <h1 class="section__title">Producto demo</h1>
    <p class="muted">Aquí irá la galería, variantes, stock, etc.</p>

    <div class="box">
      <div class="box__row"><strong>Precio:</strong> $999</div>
      <div class="box__row"><strong>Disponibilidad:</strong> En stock</div>
    </div>

    <div class="section__actions">
      <a class="btn btn--primary" href="#">Quiero este (WhatsApp)</a>
      <a class="btn btn--ghost" href="/productos.php">Volver</a>
    </div>
  </section>
</main>

<?php require __DIR__ . "/../app/includes/footer.php"; ?>
