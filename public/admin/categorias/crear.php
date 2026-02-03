<?php
declare(strict_types=1);

require __DIR__ . "/../../../app/config/db.php";
$config = require __DIR__ . "/../../../app/config/app.php";
require __DIR__ . "/../../../app/includes/auth.php";

start_session();
require_admin();

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $nombre = trim($_POST["nombre"] ?? "");
  $slug = trim($_POST["slug"] ?? "");
  $orden = $_POST["orden"] ?? "0";
  $activa = isset($_POST["activa"]) ? 1 : 0;

  if ($nombre === "" || mb_strlen($nombre) > 80) {
    $error = "Nombre inválido.";
  } elseif ($slug === "" || mb_strlen($slug) > 160) {
    $error = "Slug inválido.";
  } elseif (!is_numeric($orden)) {
    $error = "Orden inválido.";
  } else {
    try {
      $stmt = $pdo->prepare("
        INSERT INTO categorias (nombre, slug, orden, activa)
        VALUES (?, ?, ?, ?)
      ");
      $stmt->execute([$nombre, $slug, (int)$orden, $activa]);

      header("Location: " . $config["base_url"] . "/admin/categorias/");
      exit;
    } catch (PDOException $e) {
      $error = "No se pudo guardar. ¿Slug repetido?";
    }
  }
}

$title = "Admin - Crear categoría";
require __DIR__ . "/../../../app/includes/header.php";
require __DIR__ . "/../../../app/includes/navbar.php";
?>

<main class="container">
  <section class="section">
    <div class="section__actions" style="justify-content:space-between;">
      <h1 class="section__title" style="margin:0;">Crear categoría</h1>
      <a class="btn btn--ghost" href="<?= $config["base_url"] ?>/admin/categorias/">Volver</a>
    </div>

    <?php if ($error): ?>
      <p class="muted"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form class="box form" method="post">
      <div class="form__grid">
        <div class="box__row">
          <label class="form__label">Nombre
            <input class="form__input" name="nombre" required maxlength="80" placeholder="Ej. Tenis">
          </label>
        </div>

        <div class="box__row">
          <label class="form__label">Slug (único)
            <input class="form__input" name="slug" required maxlength="160" placeholder="ej. tenis">
          </label>
          <div class="muted" style="font-size:13px;">Minúsculas, sin acentos, con guiones.</div>
        </div>

        <div class="box__row">
          <label class="form__label">Orden
            <input class="form__input" name="orden" type="number" step="1" value="0">
          </label>
        </div>

        <div class="box__row">
          <label class="form__label">
            <input type="checkbox" name="activa" checked>
            Activa
          </label>
        </div>
      </div>

      <div class="section__actions">
        <button class="btn btn--primary" type="submit">Guardar</button>
      </div>
    </form>
  </section>
</main>

<?php require __DIR__ . "/../../../app/includes/footer.php"; ?>
