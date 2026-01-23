<?php
declare(strict_types=1);

$title = "Inicio";

require __DIR__ . "/../app/config/db.php";
$config = require __DIR__ . "/../app/config/app.php";

$stmt = $pdo->prepare("SELECT nombre, slug FROM categorias WHERE activa = 1 ORDER BY orden, nombre");
$stmt->execute();
$categorias = $stmt->fetchAll();

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
    <p class="muted">Ahorita es maqueta. En el siguiente paso lo conectamos a la base de datos.</p>
  </section>
</main>

<?php require __DIR__ . "/../app/includes/footer.php"; ?>
