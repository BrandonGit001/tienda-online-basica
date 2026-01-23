<?php
declare(strict_types=1);
$title = "Productos";
require __DIR__ . "/../app/includes/header.php";
require __DIR__ . "/../app/includes/navbar.php";
?>

<main class="container">
  <section class="section">
    <h1 class="section__title">Productos</h1>
    <p class="muted">Luego aquí van filtros por categoría y búsqueda.</p>

    <div class="grid grid--products">
      <article class="product">
        <div class="product__img" aria-hidden="true">IMG</div>
        <div class="product__body">
          <div class="product__name">Producto demo</div>
          <div class="product__price">$999</div>
          <a class="btn btn--small" href="/producto.php?id=1">Ver</a>
        </div>
      </article>
    </div>
  </section>
</main>

<?php require __DIR__ . "/../app/includes/footer.php"; ?>
