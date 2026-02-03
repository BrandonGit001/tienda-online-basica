<?php
declare(strict_types=1);

require __DIR__ . "/../../../app/config/db.php";
$config = require __DIR__ . "/../../../app/config/app.php";
require __DIR__ . "/../../../app/includes/auth.php";

start_session();
require_admin();

// Productos con su categoría
$stmt = $pdo->prepare("
  SELECT p.id, p.nombre, p.precio, p.stock, p.estado, p.imagen,
         c.nombre AS categoria
  FROM productos p
  JOIN categorias c ON c.id = p.categoria_id
  ORDER BY p.id DESC
");
$stmt->execute();
$productos = $stmt->fetchAll();

$title = "Admin - Productos";
require __DIR__ . "/../../../app/includes/header.php";
require __DIR__ . "/../../../app/includes/navbar.php";
?>

<main class="container">
  <section class="section">
    <div class="section__actions" style="justify-content:space-between;">
      <h1 class="section__title" style="margin:0;">Productos</h1>
      <a class="btn btn--primary" href="<?= $config["base_url"] ?>/admin/productos/crear.php">+ Nuevo</a>
    </div>

    <?php if (empty($productos)): ?>
      <p class="muted">No hay productos aún.</p>
    <?php else: ?>
      <div class="grid grid--products">
        <?php foreach ($productos as $p): ?>
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
              <div class="muted" style="font-size:14px;">
                <?= htmlspecialchars($p["categoria"]) ?> · Stock: <?= (int)$p["stock"] ?> · <?= htmlspecialchars($p["estado"]) ?>
              </div>

              <div class="section__actions">
                <a class="btn btn--small" href="<?= $config["base_url"] ?>/producto.php?id=<?= (int)$p["id"] ?>" target="_blank" rel="noopener">
                  Ver
                </a>
                <a class="btn btn--small" href="<?= $config["base_url"] ?>/admin/productos/editar.php?id=<?= (int)$p["id"] ?>">
                  Editar
                </a>
                <a class="btn btn--small" href="<?= $config["base_url"] ?>/admin/productos/eliminar.php?id=<?= (int)$p["id"] ?>">
                  Eliminar
                </a>
              </div>

            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</main>

<?php require __DIR__ . "/../../../app/includes/footer.php"; ?>
