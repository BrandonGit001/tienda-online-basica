<?php
declare(strict_types=1);

require __DIR__ . "/../../../app/config/db.php";
$config = require __DIR__ . "/../../../app/config/app.php";
require __DIR__ . "/../../../app/includes/auth.php";

start_session();
require_admin();

$id = $_GET["id"] ?? null;
if (!$id || !ctype_digit($id)) die("ID inválido");
$id = (int)$id;

$stmt = $pdo->prepare("SELECT id, nombre, activa FROM categorias WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$cat = $stmt->fetch();
if (!$cat) die("Categoría no encontrada");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $up = $pdo->prepare("UPDATE categorias SET activa = 0 WHERE id = ?");
  $up->execute([$id]);

  header("Location: " . $config["base_url"] . "/admin/categorias/");
  exit;
}

$title = "Admin - Desactivar categoría";
require __DIR__ . "/../../../app/includes/header.php";
require __DIR__ . "/../../../app/includes/navbar.php";
?>

<main class="container">
  <section class="section">
    <h1 class="section__title">Desactivar categoría</h1>
    <p class="muted">No se borra, solo se oculta del sitio.</p>

    <div class="box">
      <div class="box__row"><strong><?= htmlspecialchars($cat["nombre"]) ?></strong></div>
      <div class="box__row">
        <form method="post">
          <div class="section__actions">
            <button class="btn btn--primary" type="submit">Sí, desactivar</button>
            <a class="btn btn--ghost" href="<?= $config["base_url"] ?>/admin/categorias/">Cancelar</a>
          </div>
        </form>
      </div>
    </div>
  </section>
</main>

<?php require __DIR__ . "/../../../app/includes/footer.php"; ?>
