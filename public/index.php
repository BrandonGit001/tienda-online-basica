<?php
declare(strict_types=1);

$title = "Inicio";
require __DIR__ . "/../app/config/db.php";

$stmt = $pdo->prepare("SELECT nombre, slug FROM categorias WHERE activa = 1 ORDER BY orden, nombre");
$stmt->execute();
$categorias = $stmt->fetchAll();
require __DIR__ . "/../app/config/db.php";
$config = require __DIR__ . "/../app/config/app.php";

$stmt = $pdo->prepare("SELECT nombre, slug FROM categorias WHERE activa = 1 ORDER BY orden, nombre");
$stmt->execute();
$categorias = $stmt->fetchAll();
$stmtUlt = $pdo->prepare("
  SELECT id, nombre, precio, imagen
  FROM productos
  WHERE estado = 'activo'
  ORDER BY created_at DESC
  LIMIT 6
");
$stmtUlt->execute();
$ultimos = $stmtUlt->fetchAll();

require __DIR__ . "/../app/includes/header.php";
require __DIR__ . "/../app/includes/navbar.php";
require __DIR__ . "/../app/includes/banner.php";
?>

<main class="container">
  <section class="section">
    <h2 class="section__title">Categorías</h2>
      <div class="grid">
          <?php foreach ($categorias as $cat): ?>
            <a class="card" href="<?= $config["base_url"] ?>/productos.php?cat=<?= urlencode($cat["slug"]) ?>">
              <?= htmlspecialchars($cat["nombre"]) ?>
            </a>
          <?php endforeach; ?>
       </div>


  </section>

 <section class="section">
  <h2 class="section__title">Últimos productos</h2>

  <?php if (empty($ultimos)): ?>
    <p class="muted">Aún no hay productos para mostrar.</p>
  <?php else: ?>
    <div class="grid grid--products">
      <?php foreach ($ultimos as $p): ?>
        <?php
          $img = $p["imagen"] ?? null;
          $imgUrl = $img ? ($config["base_url"] . "/assets/img/products/" . $img) : null;
        ?>

        <article class="product">
          <?php if ($imgUrl): ?>
            <img class="product__img" src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($p["nombre"]) ?>">
          <?php else: ?>
            <div class="product__img product__img--placeholder">Sin foto</div>
          <?php endif; ?>

          <div class="product__body">
            <div class="product__name"><?= htmlspecialchars($p["nombre"]) ?></div>
            <div class="product__price">$<?= number_format((float)$p["precio"], 2) ?></div>

            <a class="btn btn--small"
               href="<?= $config["base_url"] ?>/producto.php?id=<?= (int)$p["id"] ?>">
              Ver
            </a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

</main>

<?php require __DIR__ . "/../app/includes/footer.php"; ?>
