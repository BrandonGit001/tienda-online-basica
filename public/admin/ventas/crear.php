<?php
declare(strict_types=1);

require __DIR__ . "/../../../app/config/db.php";
$config = require __DIR__ . "/../../../app/config/app.php";
require __DIR__ . "/../../../app/includes/auth.php";

start_session();
require_admin();

// Productos activos para elegir
$stmtP = $pdo->prepare("SELECT id, nombre, precio FROM productos WHERE estado='activo' ORDER BY nombre");
$stmtP->execute();
$productos = $stmtP->fetchAll();

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $cliente_nombre = trim($_POST["cliente_nombre"] ?? "");
  $cliente_apodo  = trim($_POST["cliente_apodo"] ?? "");
  $cliente_tel    = trim($_POST["cliente_tel"] ?? "");
  $nota           = trim($_POST["nota"] ?? "");

  $item_producto = $_POST["producto_id"] ?? [];
  $item_cantidad = $_POST["cantidad"] ?? [];
  $item_precio   = $_POST["precio_unitario"] ?? [];

  if ($cliente_nombre === "" || $cliente_tel === "") {
    $error = "Nombre y teléfono son obligatorios.";
  } elseif (!is_array($item_producto) || count($item_producto) < 1) {
    $error = "Agrega al menos 1 producto.";
  } else {
    // Normalizar items: validar y filtrar vacíos
    $items = [];
    for ($i=0; $i<count($item_producto); $i++) {
      $pid = (string)($item_producto[$i] ?? "");
      $qty = (string)($item_cantidad[$i] ?? "1");
      $pr  = (string)($item_precio[$i] ?? "0");

      if ($pid === "" || !ctype_digit($pid)) continue;
      if (!ctype_digit($qty) || (int)$qty <= 0) { $error = "Cantidad inválida."; break; }
      if (!is_numeric($pr) || (float)$pr < 0) { $error = "Precio inválido."; break; }

      $items[] = [
        "producto_id" => (int)$pid,
        "cantidad" => (int)$qty,
        "precio_unitario" => (float)$pr
      ];
    }

    if (!$error && count($items) === 0) {
      $error = "Agrega al menos 1 producto válido.";
    }

    if (!$error) {
      try {
        $pdo->beginTransaction();

        $stmtV = $pdo->prepare("
          INSERT INTO ventas (cliente_nombre, cliente_apodo, cliente_tel, estado, nota)
          VALUES (?, ?, ?, 'abierta', ?)
        ");
        $stmtV->execute([
          $cliente_nombre,
          $cliente_apodo !== "" ? $cliente_apodo : null,
          $cliente_tel,
          $nota !== "" ? $nota : null
        ]);

        $ventaId = (int)$pdo->lastInsertId();

        $stmtI = $pdo->prepare("
          INSERT INTO venta_items (venta_id, producto_id, cantidad, precio_unitario)
          VALUES (?, ?, ?, ?)
        ");
        foreach ($items as $it) {
          $stmtI->execute([$ventaId, $it["producto_id"], $it["cantidad"], $it["precio_unitario"]]);
        }

        $pdo->commit();

        header("Location: " . $config["base_url"] . "/admin/ventas/ver.php?id=" . $ventaId);
        exit;
      } catch (Throwable $e) {
        $pdo->rollBack();
        $error = "No se pudo crear la venta.";
      }
    }
  }
}

$title = "Admin - Crear venta";
require __DIR__ . "/../../../app/includes/header.php";
require __DIR__ . "/../../../app/includes/navbar.php";
?>

<main class="container">
  <section class="section">
    <div class="section__actions" style="justify-content:space-between;">
      <h1 class="section__title" style="margin:0;">Nueva venta</h1>
      <a class="btn btn--ghost" href="<?= $config["base_url"] ?>/admin/">Volver</a>
    </div>

    <?php if ($error): ?>
      <p class="muted"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form class="box form" method="post">
      <div class="form__grid">
        <div class="box__row span-2">
          <label class="form__label">Nombre cliente
            <input class="form__input" name="cliente_nombre" required>
          </label>
        </div>

        <div class="box__row">
          <label class="form__label">Apodo (opcional)
            <input class="form__input" name="cliente_apodo">
          </label>
        </div>

        <div class="box__row">
          <label class="form__label">Teléfono (WhatsApp)
            <input class="form__input" name="cliente_tel" required placeholder="ej. 6141234567">
          </label>
        </div>

        <div class="box__row span-2">
          <label class="form__label">Nota (opcional)
            <input class="form__input" name="nota" placeholder="ej. entrega el viernes">
          </label>
        </div>

        <div class="box__row span-2">
          <strong>Productos</strong>
          <div class="muted" style="font-size:13px;">Puedes agregar varios. El precio queda “congelado” en la venta.</div>
        </div>

        <div class="box__row span-2">
          <div id="items"></div>

          <button class="btn btn--ghost" type="button" id="addRow">+ Agregar producto</button>
        </div>
      </div>

      <div class="section__actions">
        <button class="btn btn--primary" type="submit">Crear venta</button>
      </div>
    </form>
  </section>
</main>

<script>
(() => {
  const productos = <?= json_encode($productos, JSON_UNESCAPED_UNICODE) ?>;

  const items = document.getElementById("items");
  const add = document.getElementById("addRow");

  function rowHTML() {
    const options = productos.map(p => `<option value="${p.id}" data-precio="${p.precio}">${escapeHtml(p.nombre)} ($${Number(p.precio).toFixed(2)})</option>`).join("");
    return `
      <div class="venta-row" style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:10px;margin:10px 0;">
        <select class="form__select" name="producto_id[]">
          <option value="">-- producto --</option>
          ${options}
        </select>
        <input class="form__input" name="cantidad[]" type="number" min="1" value="1" />
        <input class="form__input" name="precio_unitario[]" type="number" step="0.01" min="0" value="0" />
        <button class="btn btn--ghost" type="button" data-del>Quitar</button>
      </div>
    `;
  }

  function escapeHtml(s){
    return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
  }

  function addRow() {
    const div = document.createElement("div");
    div.innerHTML = rowHTML();
    const row = div.firstElementChild;
    items.appendChild(row);

    const sel = row.querySelector("select");
    const precio = row.querySelector('input[name="precio_unitario[]"]');

    sel.addEventListener("change", () => {
      const opt = sel.options[sel.selectedIndex];
      const p = opt?.getAttribute("data-precio");
      if (p !== null) precio.value = Number(p || 0).toFixed(2);
    });

    row.querySelector("[data-del]").addEventListener("click", () => row.remove());
  }

  add.addEventListener("click", addRow);
  addRow(); // 1 fila por defecto
})();
</script>

<?php require __DIR__ . "/../../../app/includes/footer.php"; ?>
