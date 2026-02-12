<?php
declare(strict_types=1);

require_once __DIR__ . "/../../../app/config/db.php";
require_once __DIR__ . "/../../../app/config/app.php";
require_once __DIR__ . "/../../../app/includes/auth.php";

start_session();
require_admin();

$q = trim($_GET["q"] ?? "");
$ventas = [];

$sql = "
  SELECT
    id,
    cliente_nombre,
    cliente_apodo,
    cliente_tel,
    created_at
  FROM ventas
  WHERE estado = 'abierta'
";

$params = [];

if ($q !== "") {
  $sql .= " AND (
    cliente_nombre LIKE ?
    OR cliente_apodo LIKE ?
    OR cliente_tel LIKE ?
  )";
  $like = "%" . $q . "%";
  $params[] = $like;
  $params[] = $like;
  $params[] = $like;
}

$sql .= " ORDER BY id DESC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totales = [];

if (!empty($ventas)) {
  $ids = array_column($ventas, 'id');
  $placeholders = implode(',', array_fill(0, count($ids), '?'));

  $stmt = $pdo->prepare("
    SELECT venta_id, SUM(cantidad * precio_unitario) AS total
    FROM venta_items
    WHERE venta_id IN ($placeholders)
    GROUP BY venta_id
  ");
  $stmt->execute($ids);

  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $totales[$row['venta_id']] = (float)$row['total'];
  }
}
$abonos = [];
$ultimo_abono = [];

if (!empty($ventas)) {
  $stmt = $pdo->prepare("
    SELECT
      venta_id,
      SUM(monto) AS abonado,
      MAX(created_at) AS ultima_fecha
    FROM abonos
    WHERE venta_id IN ($placeholders)
    GROUP BY venta_id
  ");
  $stmt->execute($ids);

  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $abonos[$row['venta_id']] = (float)$row['abonado'];
    $ultimo_abono[$row['venta_id']] = $row['ultima_fecha'];
  }
}




$title = "Abonos";
require __DIR__ . "/../../../app/includes/header.php";
require __DIR__ . "/../../../app/includes/navbar.php";
?>

<main class="container">
  <section class="section">
    <h1 class="section__title">Abonos / Cobranzas</h1>

    <p class="muted">
      Aquí se mostrarán las ventas con saldo pendiente.
    </p>

            <form method="get" class="search-bar">
            <input
                type="text"
                name="q"
                value="<?= htmlspecialchars($_GET["q"] ?? "") ?>"
                placeholder="Buscar por nombre o teléfono…"
                class="search-bar__input"
            >
            <button class="search-bar__btn" type="submit">
                Buscar
            </button>
            <a class="btn btn--ghost" href="<?= $config["base_url"] ?>/admin/">Dashboard</a>
            </form>

<div class ="abonos-table">
    <div class="box" style="overflow:auto;">
    <table style="width:100%; border-collapse:collapse; min-width:760px;">
        <thead>
        <tr>
            <th style="text-align:left; padding:10px 8px;">Venta</th>
            <th style="text-align:left; padding:10px 8px;">Cliente</th>
            <th style="text-align:left; padding:10px 8px;">Tel</th>
            <th style="text-align:right; padding:10px 8px;">Total</th>
            <th style="text-align:right; padding:10px 8px;">Abonado</th>
            <th style="text-align:right; padding:10px 8px;">Saldo</th>
            <th style="text-align:left; padding:10px 8px;">Último abono</th>
            <th style="text-align:right; padding:10px 8px;">Acciones</th>
        </tr>
        </thead>

        <tbody>
            <?php if (empty($ventas)): ?>
                <tr>
                <td colspan="8" style="padding:12px 8px;" class="muted">
                     No hay resultados
                    <?php if ($q !== ""): ?>
                    para: <strong><?= htmlspecialchars($q) ?></strong>
                    <?php endif; ?>.

                </td>
                </tr>
            <?php else: ?>
                <?php foreach ($ventas as $v): ?>
                  <?php
                      $total   = $totales[$v['id']] ?? 0;
                      $abonado = $abonos[$v['id']] ?? 0;
                      $saldo   = max(0, $total - $abonado);
                      ?>

                        <tr>
                            <td style="padding:10px 8px;">#<?= (int)$v["id"] ?></td>

                            <td style="padding:10px 8px;">
                                <?= htmlspecialchars($v["cliente_nombre"]) ?>
                                <?php if (!empty($v["cliente_apodo"])): ?>
                                <span class="muted">(<?= htmlspecialchars($v["cliente_apodo"]) ?>)</span>
                                <?php endif; ?>
                            </td>

                            <td style="padding:10px 8px;">
                                <?= htmlspecialchars($v["cliente_tel"]) ?>
                            </td>
                            <td style="padding:10px 8px; text-align:right;">$<?= number_format($total,2) ?></td>
                            <td style="padding:10px 8px; text-align:right;">$<?= number_format($abonado,2) ?></td>
                            <td style="padding:10px 8px; text-align:right;">$<?= number_format($saldo,2) ?></td>
                            <td style="padding:10px 8px;">—</td>

                            <td style="padding:10px 8px; text-align:right;">
                                <a class="btn btn--ghost" href="/admin/ventas/ver.php?id=<?= (int)$v["id"] ?>">Ver</a>
                                <a class="btn btn--ghost" href="/admin/abonos/crear.php?venta_id=<?= (int)$v["id"] ?>">Abonar</a>
                            </td>
                         </tr>

                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>

    </table>
    </div>
</div>
<div class="abonos-cards">
  <?php if (empty($ventas)): ?>
    <div class="abono-card">
      <p class="muted" style="margin:0;">
        No hay resultados<?= $q !== "" ? " para: " . htmlspecialchars($q) : "" ?>.
      </p>
    </div>
  <?php else: ?>
    <?php foreach ($ventas as $v): ?>
      <div class="abono-card">
        <?php
            $total   = $totales[$v['id']] ?? 0;
            $abonado = $abonos[$v['id']] ?? 0;
            $saldo   = max(0, $total - $abonado);
            $ultimo  = $ultimo_abono[$v['id']] ?? null;
            ?>
        <div class="abono-card__top">
          <div>
            <div style="font-weight:700;">#<?= (int)$v["id"] ?></div>
            <div>
              <?= htmlspecialchars($v["cliente_nombre"]) ?>
              <?php if (!empty($v["cliente_apodo"])): ?>
                <span class="muted">(<?= htmlspecialchars($v["cliente_apodo"]) ?>)</span>
              <?php endif; ?>
            </div>
            <div class="muted"><?= htmlspecialchars($v["cliente_tel"]) ?></div>
          </div>

          <div style="text-align:right;">
            <div class="muted">Saldo</div>
            <div style="font-weight:700;">$<?= number_format($saldo, 2) ?></div>

          </div>
        </div>

        <div class="abono-kv">
          <span><?= $ultimo ? date('d/m/Y', strtotime($ultimo)) : "—" ?></span>
          <span>—</span>
        </div>

        <div class="abono-card__actions">
          <a class="btn btn--ghost" href="/admin/ventas/ver.php?id=<?= (int)$v["id"] ?>">Ver</a>
          <a class="btn btn--ghost" href="/admin/abonos/crear.php?venta_id=<?= (int)$v["id"] ?>">Abonar</a>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

  </section>
</main>

<?php require __DIR__ . "/../../../app/includes/footer.php"; ?>
