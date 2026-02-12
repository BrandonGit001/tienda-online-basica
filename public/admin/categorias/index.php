<?php
declare(strict_types=1);

require __DIR__ . "/../../../app/config/db.php";
$config = require __DIR__ . "/../../../app/config/app.php";
require __DIR__ . "/../../../app/includes/auth.php";

start_session();
require_admin();

$stmt = $pdo->prepare("
  SELECT id, nombre, slug, activa, orden
  FROM categorias
  ORDER BY orden ASC, nombre ASC
");
$stmt->execute();
$cats = $stmt->fetchAll();

$title = "Admin - Categorías";
require __DIR__ . "/../../../app/includes/header.php";
require __DIR__ . "/../../../app/includes/navbar.php";
?>

<main class="container">
  <section class="section">
    <div class="section__actions" style="justify-content:space-between;">
      <h1 class="section__title" style="margin:0;">Categorías</h1>
      <a class="btn btn--primary" href="<?= $config["base_url"] ?>/admin/categorias/crear.php">+ Nueva</a>
      <a class="btn btn--primary" href="<?= $config["base_url"] ?>/admin/">Dashboard</a>
    </div>

    <?php if (empty($cats)): ?>
      <p class="muted">No hay categorías aún.</p>
    <?php else: ?>
      <div class="box">
        <?php foreach ($cats as $c): ?>
          <div class="box__row" style="display:flex; gap:12px; align-items:center; justify-content:space-between;">
            <div>
              <strong><?= htmlspecialchars($c["nombre"]) ?></strong>
              <div class="muted" style="font-size:13px;">
                slug: <?= htmlspecialchars($c["slug"]) ?> · orden: <?= (int)$c["orden"] ?> ·
                <?= ((int)$c["activa"] === 1) ? "activa" : "inactiva" ?>
              </div>
            </div>

            <div class="section__actions">
              <a class="btn btn--small" href="<?= $config["base_url"] ?>/admin/categorias/editar.php?id=<?= (int)$c["id"] ?>">Editar</a>
              <a class="btn btn--small" href="<?= $config["base_url"] ?>/admin/categorias/eliminar.php?id=<?= (int)$c["id"] ?>">Eliminar</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</main>

<?php require __DIR__ . "/../../../app/includes/footer.php"; ?>
