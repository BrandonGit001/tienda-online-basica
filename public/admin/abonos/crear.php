<?php
declare(strict_types=1);

require_once __DIR__ . "/../../../app/config/db.php";
require_once __DIR__ . "/../../../app/config/app.php";
require_once __DIR__ . "/../../../app/includes/auth.php";

start_session();
require_admin();

// -------- Helpers mínimos
function redirect(string $to): void {
  header("Location: " . $to);
  exit;
}

// -------- 1) GET: mostrar formulario con contexto
if ($_SERVER["REQUEST_METHOD"] === "GET") {
  $venta_id = (int)($_GET["venta_id"] ?? 0);
  if ($venta_id <= 0) {
    redirect("/admin/abonos/index.php");
  }

  // Venta (contexto)
  $stmt = $pdo->prepare("
    SELECT id, cliente_nombre, cliente_apodo, cliente_tel, estado
    FROM ventas
    WHERE id = ?
    LIMIT 1
  ");
  $stmt->execute([$venta_id]);
  $venta = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$venta) {
    redirect("/admin/abonos/index.php");
  }

  // Total
  $stmt = $pdo->prepare("SELECT COALESCE(SUM(cantidad * precio_unitario),0) FROM venta_items WHERE venta_id = ?");
  $stmt->execute([$venta_id]);
  $total = (float)$stmt->fetchColumn();

  // Abonado
  $stmt = $pdo->prepare("SELECT COALESCE(SUM(monto),0) FROM abonos WHERE venta_id = ?");
  $stmt->execute([$venta_id]);
  $abonado = (float)$stmt->fetchColumn();

  $saldo = max(0, $total - $abonado);

  // Layout (para que no se vea feo)
  $title = "Agregar abono";
  require __DIR__ . "/../../../app/includes/header.php";
  require __DIR__ . "/../../../app/includes/navbar.php";
  ?>

  <main class="container">
    <section class="section">
      <div class="section__actions" style="justify-content:space-between;">
        <h1 class="section__title" style="margin:0;">Agregar abono</h1>
        <a class="btn btn--ghost" href="/admin/abonos/index.php">Volver</a>
      </div>

      <div class="box" style="margin-top:14px;">
        <div class="box__row"><strong>Venta:</strong> #<?= (int)$venta["id"] ?></div>
        <div class="box__row"><strong>Cliente:</strong>
          <?= htmlspecialchars($venta["cliente_nombre"] ?? "") ?>
          <?php if (!empty($venta["cliente_apodo"])): ?>
            <span class="muted">(<?= htmlspecialchars($venta["cliente_apodo"]) ?>)</span>
          <?php endif; ?>
        </div>
        <div class="box__row"><strong>Tel:</strong> <?= htmlspecialchars($venta["cliente_tel"] ?? "") ?></div>
        <div class="box__row"><strong>Estado:</strong> <?= htmlspecialchars($venta["estado"] ?? "") ?></div>

        <hr style="border:0; border-top:1px solid rgba(255,255,255,.08); margin:12px 0;">

        <div class="box__row" style="display:flex; justify-content:space-between;">
          <span><strong>Total:</strong></span>
          <span>$<?= number_format($total, 2) ?></span>
        </div>
        <div class="box__row" style="display:flex; justify-content:space-between;">
          <span><strong>Abonado:</strong></span>
          <span>$<?= number_format($abonado, 2) ?></span>
        </div>
        <div class="box__row" style="display:flex; justify-content:space-between;">
          <span><strong>Saldo:</strong></span>
          <span>$<?= number_format($saldo, 2) ?></span>
        </div>
      </div>

      <?php if ($saldo <= 0): ?>
        <p class="muted" style="margin-top:14px;">Esta venta ya no tiene saldo pendiente.</p>
      <?php else: ?>
        <form method="post" action="/admin/abonos/crear.php" class="box" style="margin-top:14px;">
          <input type="hidden" name="venta_id" value="<?= (int)$venta_id ?>">

          <div class="box__row">
            <label>Pagó (opcional)</label>
            <input name="pagador" maxlength="100" placeholder="Ej: Juan / Esposa / Cliente">
          </div>

          <div class="box__row">
            <label>Monto</label>
            <input name="monto" type="number" step="0.01" min="0.01" required>
          </div>

          <div class="box__row">
            <label>Nota (opcional)</label>
            <input name="nota" maxlength="255" placeholder="Ej: pago sábado">
          </div>

          <div class="box__row" style="display:flex; gap:10px;">
            <button class="btn btn--ghost" type="submit">Guardar abono</button>
            <a class="btn btn--ghost" href="/admin/ventas/ver.php?id=<?= (int)$venta_id ?>">Ver venta</a>
          </div>
        </form>
      <?php endif; ?>
    </section>
  </main>

  <?php
  require __DIR__ . "/../../../app/includes/footer.php";
  exit;
}

// -------- 2) POST: guardar abono
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  redirect("/admin/abonos/index.php");
}

$venta_id = (int)($_POST["venta_id"] ?? 0);
$monto    = (float)($_POST["monto"] ?? 0);
$pagador  = trim($_POST["pagador"] ?? "");
$nota     = trim($_POST["nota"] ?? "");

if ($venta_id <= 0 || $monto <= 0) {
  redirect("/admin/abonos/index.php");
}

// Total
$stmt = $pdo->prepare("SELECT COALESCE(SUM(cantidad * precio_unitario),0) FROM venta_items WHERE venta_id = ?");
$stmt->execute([$venta_id]);
$total = (float)$stmt->fetchColumn();

// Abonado actual
$stmt = $pdo->prepare("SELECT COALESCE(SUM(monto),0) FROM abonos WHERE venta_id = ?");
$stmt->execute([$venta_id]);
$abonado = (float)$stmt->fetchColumn();

$saldo = max(0, $total - $abonado);

// Validaciones de negocio
if ($total <= 0 || $saldo <= 0 || $monto > $saldo) {
  redirect("/admin/ventas/ver.php?id=" . $venta_id);
}

// Insert
$stmt = $pdo->prepare("INSERT INTO abonos (venta_id, monto, pagador, nota) VALUES (?, ?, ?, ?)");
$stmt->execute([
  $venta_id,
  $monto,
  $pagador !== "" ? $pagador : null,
  $nota !== "" ? $nota : null,
]);

redirect("/admin/ventas/ver.php?id=" . $venta_id);

header("Location: ...");
exit;

