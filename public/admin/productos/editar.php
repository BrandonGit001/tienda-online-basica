<?php
declare(strict_types=1);

require __DIR__ . "/../../../app/config/db.php";
$config = require __DIR__ . "/../../../app/config/app.php";
require __DIR__ . "/../../../app/includes/auth.php";

start_session();
require_admin();

// Validar ID
$id = $_GET["id"] ?? null;
if (!$id || !ctype_digit($id)) {
  die("ID inválido");
}
$id = (int)$id;

// Categorías para select
$stmtCat = $pdo->prepare("SELECT id, nombre FROM categorias WHERE activa = 1 ORDER BY orden, nombre");
$stmtCat->execute();
$categorias = $stmtCat->fetchAll();

// Traer producto
$stmt = $pdo->prepare("
  SELECT id, categoria_id, nombre, slug, descripcion, precio, stock, estado, imagen
  FROM productos
  WHERE id = ?
  LIMIT 1
");
$stmt->execute([$id]);
$producto = $stmt->fetch();

if (!$producto) {
  die("Producto no encontrado");
}

$error = "";
$ok = "";

/**
 * Guardar cambios (sin imagen)
 */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "save") {
  $categoria_id = $_POST["categoria_id"] ?? "";
  $nombre = trim($_POST["nombre"] ?? "");
  $slug = trim($_POST["slug"] ?? "");
  $descripcion = trim($_POST["descripcion"] ?? "");
  $precio = $_POST["precio"] ?? "0";
  $stock = $_POST["stock"] ?? "0";
  $estado = $_POST["estado"] ?? "activo";

  if (!ctype_digit($categoria_id)) {
    $error = "Categoría inválida.";
  } elseif ($nombre === "" || mb_strlen($nombre) > 120) {
    $error = "Nombre inválido.";
  } elseif ($slug === "" || mb_strlen($slug) > 160) {
    $error = "Slug inválido.";
  } elseif (!is_numeric($precio) || (float)$precio < 0) {
    $error = "Precio inválido.";
  } elseif (!ctype_digit((string)$stock)) {
    $error = "Stock inválido.";
  } elseif (!in_array($estado, ["activo","oculto","agotado","reservado"], true)) {
    $error = "Estado inválido.";
  } else {
    try {
      $stmtUp = $pdo->prepare("
        UPDATE productos
        SET categoria_id=?, nombre=?, slug=?, descripcion=?, precio=?, stock=?, estado=?
        WHERE id=?
      ");
      $stmtUp->execute([
        (int)$categoria_id,
        $nombre,
        $slug,
        $descripcion !== "" ? $descripcion : null,
        (float)$precio,
        (int)$stock,
        $estado,
        $id
      ]);

      // refrescar producto
      $stmt->execute([$id]);
      $producto = $stmt->fetch();

      $ok = "Guardado ✅";
    } catch (PDOException $e) {
      $error = "No se pudo guardar. ¿Slug repetido?";
    }
  }
}

/**
 * Subir imagen principal
 */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "upload_image") {
  if (!isset($_FILES["img"]) || $_FILES["img"]["error"] !== UPLOAD_ERR_OK) {
    $error = "No se subió imagen.";
  } else {
    $file = $_FILES["img"];

    if ($file["size"] > 2 * 1024 * 1024) {
      $error = "Máximo 2MB.";
    } else {
      $finfo = new finfo(FILEINFO_MIME_TYPE);
      $mime = $finfo->file($file["tmp_name"]);

      $allowed = [
        "image/jpeg" => "jpg",
        "image/png"  => "png",
        "image/webp" => "webp",
      ];

      if (!isset($allowed[$mime])) {
        $error = "Formato no permitido (solo JPG/PNG/WEBP).";
      } else {
        $ext = $allowed[$mime];
        $newName = "p{$id}_" . bin2hex(random_bytes(8)) . "." . $ext;

        $destDir = __DIR__ . "/../../assets/img/products";
        // OJO: __DIR__ está en /public/admin/productos, entonces ../../assets/... apunta a /public/assets/...
        if (!is_dir($destDir)) {
          mkdir($destDir, 0755, true);
        }

        $destPath = $destDir . "/" . $newName;

        if (!move_uploaded_file($file["tmp_name"], $destPath)) {
          $error = "Error al guardar imagen.";
        } else {
          // (Opcional) borrar imagen anterior si existía
          $old = $producto["imagen"] ?? null;
          if ($old) {
            $oldPath = $destDir . "/" . $old;
            if (is_file($oldPath)) {
              @unlink($oldPath);
            }
          }

          $stmtImg = $pdo->prepare("UPDATE productos SET imagen=? WHERE id=?");
          $stmtImg->execute([$newName, $id]);

          // refrescar producto
          $stmt->execute([$id]);
          $producto = $stmt->fetch();

          $ok = "Imagen actualizada ✅";
        }
      }
    }
  }
}

$img = $producto["imagen"] ?? null;
$imgUrl = $img ? ($config["base_url"] . "/assets/img/products/" . $img) : null;

$title = "Admin - Editar producto";
require __DIR__ . "/../../../app/includes/header.php";
require __DIR__ . "/../../../app/includes/navbar.php";
?>

<main class="container">
  <section class="section">
    <div class="section__actions">
      <a class="btn btn--ghost" href="<?= $config["base_url"] ?>/admin/productos/">← Volver</a>
      <a class="btn btn--small" target="_blank" rel="noopener"
         href="<?= $config["base_url"] ?>/producto.php?id=<?= (int)$producto["id"] ?>">Ver público</a>
    </div>

    <h1 class="section__title">Editar producto</h1>

    <?php if ($error): ?>
      <p class="muted"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if ($ok): ?>
      <p class="muted"><?= htmlspecialchars($ok) ?></p>
    <?php endif; ?>

    <div class="grid" style="grid-template-columns: 1fr; gap:16px;">
      <!-- Imagen -->
      <div class="box">
        <div class="box__row"><strong>Imagen principal</strong></div>

        <?php if ($imgUrl): ?>
          <img class="product-detail__img" src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($producto["nombre"]) ?>">
        <?php else: ?>
          <div class="product-detail__img product-detail__img--placeholder">Sin foto</div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="action" value="upload_image">
          <input type="file" name="img" accept="image/jpeg,image/png,image/webp" required>
          <div class="section__actions" style="margin-top:12px;">
            <button class="btn btn--primary" type="submit">Subir imagen</button>
          </div>
        </form>
      </div>

      <!-- Form producto -->
      <form class="box" method="post">
        <input type="hidden" name="action" value="save">

        <div class="box__row">
          <label>Categoría<br>
            <select name="categoria_id" required>
              <?php foreach ($categorias as $c): ?>
                <option value="<?= (int)$c["id"] ?>" <?= ((int)$producto["categoria_id"] === (int)$c["id"]) ? "selected" : "" ?>>
                  <?= htmlspecialchars($c["nombre"]) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>
        </div>

        <div class="box__row">
          <label>Nombre<br>
            <input name="nombre" required maxlength="120" value="<?= htmlspecialchars($producto["nombre"]) ?>">
          </label>
        </div>

        <div class="box__row">
          <label>Slug (único)<br>
            <input name="slug" required maxlength="160" value="<?= htmlspecialchars($producto["slug"]) ?>">
          </label>
        </div>

        <div class="box__row">
          <label>Descripción<br>
            <textarea name="descripcion" rows="4"><?= htmlspecialchars((string)($producto["descripcion"] ?? "")) ?></textarea>
          </label>
        </div>

        <div class="box__row">
          <label>Precio<br>
            <input name="precio" type="number" step="0.01" min="0" value="<?= htmlspecialchars((string)$producto["precio"]) ?>">
          </label>
        </div>

        <div class="box__row">
          <label>Stock<br>
            <input name="stock" type="number" step="1" min="0" value="<?= (int)$producto["stock"] ?>">
          </label>
        </div>

        <div class="box__row">
          <label>Estado<br>
            <select name="estado">
              <?php
                $estados = ["activo","agotado","reservado","oculto"];
                foreach ($estados as $e):
              ?>
                <option value="<?= $e ?>" <?= ($producto["estado"] === $e) ? "selected" : "" ?>><?= $e ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        </div>

        <div class="section__actions">
          <button class="btn btn--primary" type="submit">Guardar cambios</button>
        </div>
      </form>
    </div>

  </section>
</main>

<?php require __DIR__ . "/../../../app/includes/footer.php"; ?>
