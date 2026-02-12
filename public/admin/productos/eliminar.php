<?php
declare(strict_types=1);

require __DIR__ . "/../../../app/config/db.php";
$config = require __DIR__ . "/../../../app/config/app.php";
require __DIR__ . "/../../../app/includes/auth.php";

start_session();
require_admin();

$id = $_GET["id"] ?? null;
if (!$id || !ctype_digit($id)) {
  die("ID inválido");
}
$id = (int)$id;

// Traer producto (para mostrar nombre e imagen)
$stmt = $pdo->prepare("SELECT id, nombre, imagen FROM productos WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$producto = $stmt->fetch();

if (!$producto) {
  die("Producto no encontrado");
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

  try {
    // 1) borrar registro
    $stmtDel = $pdo->prepare("DELETE FROM productos WHERE id = ? LIMIT 1");
    $stmtDel->execute([$id]);

    // 2) borrar imagen del disco (si existía)
    $img = $producto["imagen"] ?? null;
    if ($img) {
      $dir  = __DIR__ . "/../../assets/img/products";
      $path = $dir . "/" . $img;
      if (is_file($path)) {
        @unlink($path);
      }
    }

    header("Location: " . $config["base_url"] . "/admin/productos/?ok=eliminado");
    exit;

  } catch (PDOException $e) {
    // 1451 / 23000: está referenciado en venta_items -> no se puede borrar
    if ($e->getCode() === "23000") {
      $stmtHide = $pdo->prepare("UPDATE productos SET estado = 'oculto' WHERE id = ? LIMIT 1");
      $stmtHide->execute([$id]);

      header("Location: " . $config["base_url"] . "/admin/productos/?ok=oculto");
      exit;
    }

    // otro error (dev)
    die("DB ERROR: " . $e->getMessage());
  }
}

$title = "Admin - Eliminar producto";
require __DIR__ . "/../../../app/includes/header.php";
require __DIR__ . "/../../../app/includes/navbar.php";
?>

<main class="container">
  <section class="section">
    <h1 class="section__title">Eliminar producto</h1>
    <p class="muted">Esta acción no se puede deshacer.</p>

    <div class="box">
      <div class="box__row"><strong>Producto:</strong> <?= htmlspecialchars($producto["nombre"]) ?></div>
      <div class="box__row">
        <form method="post">
          <div class="section__actions">
            <button class="btn btn--primary" type="submit">Sí, eliminar</button>
            <a class="btn btn--ghost" href="<?= $config["base_url"] ?>/admin/productos/">Cancelar</a>
          </div>
        </form>
      </div>
    </div>
  </section>
</main>

<?php require __DIR__ . "/../../../app/includes/footer.php"; ?>
