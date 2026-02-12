<?php
declare(strict_types=1);

require __DIR__ . "/../../../app/config/db.php";
$config = require __DIR__ . "/../../../app/config/app.php";
require __DIR__ . "/../../../app/includes/auth.php";

start_session();
require_admin();

/**
 * Genera un slug simple sin librerías.
 */
function make_slug(string $text): string {
  $text = trim(mb_strtolower($text, "UTF-8"));

  // Quitar acentos (lo básico)
  $replace = [
    "á"=>"a","é"=>"e","í"=>"i","ó"=>"o","ú"=>"u","ü"=>"u","ñ"=>"n",
    "Á"=>"a","É"=>"e","Í"=>"i","Ó"=>"o","Ú"=>"u","Ü"=>"u","Ñ"=>"n",
  ];
  $text = strtr($text, $replace);

  // Todo lo que no sea letra/número -> guion
  $text = preg_replace("/[^a-z0-9]+/i", "-", $text) ?? "";
  $text = trim($text, "-");

  // Evitar vacío
  return $text !== "" ? $text : "producto";
}

// Traer categorías activas para el select
$stmtCat = $pdo->prepare("SELECT id, nombre FROM categorias WHERE activa = 1 ORDER BY orden, nombre");
$stmtCat->execute();
$categorias = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

$error = "";
$ok = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $categoria_id = (string)($_POST["categoria_id"] ?? "");
  $nombre = trim((string)($_POST["nombre"] ?? ""));
  $slug = trim((string)($_POST["slug"] ?? ""));
  $descripcion = trim((string)($_POST["descripcion"] ?? ""));
  $precio = (string)($_POST["precio"] ?? "0");
  $stock = (string)($_POST["stock"] ?? "0");
  $estado = (string)($_POST["estado"] ?? "activo");

  // Si no mandan slug, lo generamos del nombre
  if ($slug === "" && $nombre !== "") {
    $slug = make_slug($nombre);
  } else {
    $slug = make_slug($slug); // normalizar aunque lo escriban a mano
  }

  // Validaciones básicas
  if (!ctype_digit($categoria_id)) {
    $error = "Categoría inválida.";
  } elseif ($nombre === "" || mb_strlen($nombre) > 120) {
    $error = "Nombre inválido.";
  } elseif ($slug === "" || mb_strlen($slug) > 160) {
    $error = "Slug inválido.";
  } elseif (!is_numeric($precio) || (float)$precio < 0) {
    $error = "Precio inválido.";
  } elseif (!ctype_digit($stock)) {
    $error = "Stock inválido.";
  } elseif (!in_array($estado, ["activo","oculto","agotado","reservado"], true)) {
    $error = "Estado inválido.";
  } else {
    // Validar que la categoría exista y esté activa (evita inserts “fantasma”)
    $stmt = $pdo->prepare("SELECT id FROM categorias WHERE id = ? AND activa = 1 LIMIT 1");
    $stmt->execute([(int)$categoria_id]);
    $catOk = $stmt->fetchColumn();

    if (!$catOk) {
      $error = "La categoría seleccionada no existe o está desactivada.";
    } else {
      // Insert
      try {
        $stmt = $pdo->prepare("
          INSERT INTO productos (categoria_id, nombre, slug, descripcion, precio, stock, estado)
          VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
          (int)$categoria_id,
          $nombre,
          $slug,
          $descripcion !== "" ? $descripcion : null,
          (float)$precio,
          (int)$stock,
          $estado
        ]);

        $nuevoId = (int)$pdo->lastInsertId();
        header("Location: " . $config["base_url"] . "/admin/productos/editar.php?id=" . $nuevoId);
        exit;

      } catch (PDOException $e) {
        // Si el slug es único, esto suele ser duplicate entry
        // Mostramos mensaje claro (y si quieres debug, lo activamos)
        $msg = $e->getMessage();

        if (stripos($msg, "Duplicate") !== false || stripos($msg, "duplicate") !== false) {
          $error = "No se pudo guardar: el slug ya existe. Prueba otro (ej: {$slug}-2).";
        } else {
          // En desarrollo, es mejor ver el error real
          $error = "No se pudo guardar (DB). " . $msg;
        }
      }
    }
  }
}

$title = "Admin - Crear producto";
require __DIR__ . "/../../../app/includes/header.php";
require __DIR__ . "/../../../app/includes/navbar.php";
?>


<main class="container">
  <section class="section">
    <div class="section__actions" style="justify-content:space-between;">
      <h1 class="section__title" style="margin:0;">Crear producto</h1>
      <a class="btn btn--ghost" href="<?= $config["base_url"] ?>/admin/productos/">Volver</a>
    </div>

    <?php if ($error): ?>
      <p class="muted"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form class="box form" method="post">
      <div class="box__row">
        <label>Categoría<br>
          <select class="form__input" name="categoria_id" required>
            <?php foreach ($categorias as $c): ?>
              <option value="<?= (int)$c["id"] ?>"><?= htmlspecialchars($c["nombre"]) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>

      <div class="box__row">
        <label>Nombre<br>
          <input  class="form__input" name="nombre" required maxlength="120" placeholder="Ej. Nike Air Max">
        </label>
      </div>

      <div class="box__row">
        <label class="form__label">Slug (único)<br>
         <input class="form__input" name="nombre" required maxlength="120" placeholder="Ej. Nike Air Max">
        </label>
        <div class="muted" style="font-size:13px;">Tip: minúsculas, sin acentos, con guiones.</div>
      </div>

      <div class="box__row">
        <label class="form__label">Descripción<br>
          <textarea class="form__input" name="descripcion" rows="4" placeholder="Detalles del producto..."></textarea>
        </label>
      </div>

      <div class="box__row">
        <label class="form__label">Precio<br>
          <input  class="form__input" name="precio" type="number" step="0.01" min="0" value="0">
        </label>
      </div>

      <div class="box__row">
        <label class="form__label">Stock<br>
          <input class="form__input" name="stock" type="number" step="1" min="0" value="0">
        </label>
      </div>

      <div class="box__row">
        <label class="form__label">Estado<br>
          <select name="estado" class="form__input">
            <option value="activo">activo</option>
            <option value="agotado">agotado</option>
            <option value="reservado">reservado</option>
            <option value="oculto">oculto</option>
          </select>
        </label>
      </div>

      <div class="section__actions">
        <button class="btn btn--primary" type="submit">Guardar y seguir</button>
      </div>
    </form>
  </section>
</main>

<?php require __DIR__ . "/../../../app/includes/footer.php"; ?>
