<?php
declare(strict_types=1);

$title = "Productos";

require __DIR__ . "/../app/config/db.php";
$config = require __DIR__ . "/../app/config/app.php";

$slug = $_GET["cat"] ?? null;
if (!$slug) {
  die("Categoría no especificada.");
}

$stmtCat = $pdo->prepare("SELECT id, nombre FROM categorias WHERE slug = ? AND activa = 1 LIMIT 1");
$stmtCat->execute([$slug]);
$categoria = $stmtCat->fetch();

if (!$categoria) {
  die("Categoría no encontrada.");
}

$stmtProd = $pdo->prepare("
  SELECT id, nombre, precio, estado, imagen

  FROM productos
  WHERE categoria_id = ?
    AND estado = 'activo'
  ORDER BY created_at DESC
");
$stmtProd->execute([$categoria["id"]]);
$productos = $stmtProd->fetchAll();

require __DIR__ . "/../app/includes/header.php";
require __DIR__ . "/../app/includes/navbar.php";
?>


<main class="container">
  <section class="section">
    <h1 class="section__title"><?= htmlspecialchars($categoria["nombre"]) ?></h1>

    <?php if (empty($productos)): ?>
      <p class="muted">No hay productos disponibles en esta categoría.</p>
    <?php else: ?>
      <div class="grid grid--products">
        <?php foreach ($productos as $p): ?>
          <article class="product">
            <?php
  $img = $p["imagen"] ?? null;
  $imgUrl = $img ? ($config["base_url"] . "/assets/img/products/" . $img) : null;
?>

<?php if ($imgUrl): ?>
  <img class="product__img" src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($p["nombre"]) ?>">
<?php else: ?>
  <div class="product__img product__img--placeholder">Sin foto</div>
<?php endif; ?>

            <div class="product__body">
              <div class="product__name"><?= htmlspecialchars($p["nombre"]) ?></div>
              <div class="product__price">$<?= number_format((float)$p["precio"], 2) ?></div>
              <a class="btn btn--small" href="<?= $config["base_url"] ?>/producto.php?id=<?= (int)$p["id"] ?>">Ver</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</main>

<?php require __DIR__ . "/../app/includes/footer.php"; ?>

