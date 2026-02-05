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

$stmtV = $pdo->prepare
("SELECT * FROM ventas WHERE id=? LIMIT 1");
$stmtV->execute([$id]);
$venta = $stmtV->fetch();
$venta_id = (int)$venta["id"];

if (!$venta) die("Venta no encontrada");

$stmtI = $pdo->prepare("
  SELECT vi.*, p.nombre AS producto_nombre
  FROM venta_items vi
  JOIN productos p ON p.id = vi.producto_id
  WHERE vi.venta_id = ?
  ORDER BY vi.id ASC
");
$stmtI->execute([$id]);
$items = $stmtI->fetchAll();
/*
$stmtA = $pdo->prepare("SELECT COALESCE(SUM(monto),0) AS total_abonos FROM abonos WHERE venta_id=?");
$stmtA->execute([$id]);
$total_abonos = (float)$stmtA->fetch()["total_abonos"];
*/

$total = 0.0;
foreach ($items as $it) {
  $total += (float)$it["precio_unitario"] * (int)$it["cantidad"];
}
$stmt = $pdo->prepare("SELECT COALESCE(SUM(monto),0) FROM abonos WHERE venta_id = ?");
$stmt->execute([$venta_id]);
$pagado = (float)$stmt->fetchColumn();

$saldo = $total - $pagado;
if ($saldo < 0) $saldo = 0;


$saldo = $total - $pagado;

$stmt = $pdo->prepare("SELECT COALESCE(SUM(monto),0) AS pagado FROM abonos WHERE venta_id = ?");
$stmt->execute([$venta_id]);
$pagado = (float)$stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT id, monto, nota, created_at
                       FROM abonos
                       WHERE venta_id = ?
                       ORDER BY created_at DESC, id DESC");
$stmt->execute([$venta_id]);
$abonos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$title = "Admin - Ver venta";
require __DIR__ . "/../../../app/includes/header.php";
require __DIR__ . "/../../../app/includes/navbar.php";
?>

<main class="container">
  <section class="section">
    <div class="section__actions" style="justify-content:space-between;">
      <h1 class="section__title" style="margin:0;">Venta #<?= (int)$venta["id"] ?></h1>
      <a class="btn btn--ghost" href="<?= $config["base_url"] ?>/admin/">Dashboard</a>
    </div>

    <div class="box">
      <div class="box__row"><strong>Cliente:</strong> <?= htmlspecialchars($venta["cliente_nombre"]) ?> <?= $venta["cliente_apodo"] ? "(".htmlspecialchars($venta["cliente_apodo"]).")" : "" ?></div>
      <div class="box__row"><strong>Tel:</strong> <?= htmlspecialchars($venta["cliente_tel"]) ?></div>
      <div class="box__row"><strong>Estado:</strong> <?= htmlspecialchars($venta["estado"]) ?></div>
      <?php if ($venta["nota"]): ?>
        <div class="box__row"><strong>Nota:</strong> <?= htmlspecialchars($venta["nota"]) ?></div>
      <?php endif; ?>
    </div>

    <h2 class="section__title">Productos</h2>
    <div class="box">
      <?php foreach ($items as $it): ?>
        <div class="box__row" style="display:flex;justify-content:space-between;gap:10px;">
          <div><?= htmlspecialchars($it["producto_nombre"]) ?> × <?= (int)$it["cantidad"] ?></div>
          <div>$<?= number_format((float)$it["precio_unitario"] * (int)$it["cantidad"], 2) ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="box">
      <div class="box__row" style="display:flex;justify-content:space-between;">
        <strong>Total:</strong> <strong>$<?= number_format($total, 2) ?></strong>
      </div>
      <div class="box__row" style="display:flex;justify-content:space-between;">
        <strong>Abonos:</strong> <strong>$<?= number_format($pagado, 2) ?></strong>
      </div>
      <div class="box__row" style="display:flex;justify-content:space-between;">
        <strong>Saldo:</strong> <strong>$<?= number_format($saldo, 2) ?></strong>
      </div>
    </div>

    <p class="muted">Siguiente paso: aquí agregaremos el formulario de abonos y el botón de WhatsApp del recibo.</p>
  </section>
        <form method="post" action="/admin/abonos/crear.php" style="margin-top:12px;">
  <input type="hidden" name="venta_id" value="<?= (int)$venta["id"] ?>">

  <label>Monto</label>
  <input name="monto" type="number" step="0.01" min="0.01" required>

  <label>Nota (opcional)</label>
  <input name="nota" type="text" maxlength="255">

  <button type="submit">Agregar abono</button>
                <h2 class="section__title">Abonos</h2>

<div class="box">
  <?php if (empty($abonos)): ?>
    <p style="margin:0;">Aún no hay abonos.</p>
  <?php else: ?>
    <?php foreach ($abonos as $a): ?>
      <div class="box__row" style="display:flex;justify-content:space-between;gap:12px;">
        <span>
          <?= htmlspecialchars($a["created_at"]) ?>
          <?php if (!empty($a["nota"])): ?>
            — <?= htmlspecialchars($a["nota"]) ?>
          <?php endif; ?>
        </span>
        <strong>$<?= number_format((float)$a["monto"], 2) ?></strong>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
</form>




</main>

<?php require __DIR__ . "/../../../app/includes/footer.php"; ?>
