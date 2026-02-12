<?php
declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);


$title = "Producto";

require __DIR__ . "/../app/config/db.php";
$config = require __DIR__ . "/../app/config/app.php";

$id = $_GET["id"] ?? null;
if (!$id || !ctype_digit($id)) {
  die("Producto no válido");
}

$stmt = $pdo->prepare("
  SELECT p.id, p.nombre, p.descripcion, p.precio, p.estado,
   p.imagen,
   c.nombre AS categoria
  FROM productos p
  JOIN categorias c ON c.id = p.categoria_id
  WHERE p.id = ?
  LIMIT 1
");
$stmt->execute([$id]);
$producto = $stmt->fetch();
$img = trim((string)($producto["imagen"] ?? ""));
$imgUrl = $img !== "" ? ($config["base_url"] . "/assets/img/products/" . $img) : null;


if (!$producto) {
  die("Producto no encontrado");
}

$waPhone = $config["whatsapp_phone"] ?? "";
$productoNombre = (string)$producto["nombre"];
$productoPrecio = number_format((float)$producto["precio"], 2);

$mensaje = "Hola, me interesan estos: {$productoNombre} ($" . $productoPrecio . "). ¿Está disponible?";
$waLink = "https://wa.me/" . urlencode($waPhone) . "?text=" . urlencode($mensaje);

require __DIR__ . "/../app/includes/header.php";
require __DIR__ . "/../app/includes/navbar.php";

?>

<main class="container">
  <section class="section">
    <?php if ($imgUrl): ?>
  <img class="product-detail__img" src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($producto["nombre"]) ?>">
<?php else: ?>
  <div class="product-detail__img product-detail__img--placeholder">Sin foto</div>
<?php endif; ?>

  <h1 class="section__title"><?= htmlspecialchars($producto["nombre"]) ?></h1>
    <p class="muted">Aquí irá la galería, variantes, stock, etc.</p>

<div class="box">
  <div class="box__row"><strong>Precio:</strong> $<?= number_format((float)$producto["precio"], 2) ?></div>
  <div class="box__row"><strong>Disponibilidad:</strong> <?= $producto["estado"] === "activo" ? "Disponible" : htmlspecialchars($producto["estado"]) ?></div>
</div>

  <div class="section__actions">
  <a class="btn btn--primary" target="_blank" rel="noopener"
     href="<?= htmlspecialchars($waLink) ?>">
    Quiero este (WhatsApp)
  </a>

  <a class="btn btn--ghost" href="<?= $config["base_url"] ?>/productos.php">Volver</a>

</div>



  </section>
</main>

<?php require __DIR__ . "/../app/includes/footer.php"; ?>
