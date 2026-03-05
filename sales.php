<?php
require_once __DIR__ . "/auth.php";

$from = $_GET['from'] ?? date('Y-m-d');
$to = $_GET['to'] ?? date('Y-m-d');

$stmt = $mysqli->prepare("
  SELECT s.id, s.sale_date, s.total, s.note, u.username
  FROM sales s
  JOIN users u ON u.id = s.user_id
  WHERE DATE(s.sale_date) BETWEEN ? AND ?
  ORDER BY s.id DESC
");
$stmt->bind_param("ss", $from, $to);
$stmt->execute();
$sales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Total del período
$totalPeriodo = 0;
foreach ($sales as $s) {
  $totalPeriodo += (float)$s['total'];
}

include __DIR__ . "/layouts/header.php";
?>

<div class="sales-container">

  <div class="topbar">
    <div class="brand-badge">
      <div class="brand-icon">📊</div>
      <div>
        <div style="font-weight:800; font-size:18px;">Reporte de Ventas</div>
        <small class="text-muted">Consulta por rango de fechas</small>
      </div>
    </div>

    <a href="index.php" class="btn btn-outline-dark">← Inicio</a>
  </div>

  <!-- Filtro -->
  <div class="filter-box">
    <form method="get" class="row g-3 align-items-end">

      <div class="col-md-4">
        <label>Desde</label>
        <input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="form-control">
      </div>

      <div class="col-md-4">
        <label>Hasta</label>
        <input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="form-control">
      </div>

      <div class="col-md-4 d-grid">
        <button class="btn btn-primary">Filtrar</button>
      </div>

    </form>
  </div>

  <!-- Total -->
  <div class="sales-total-box mb-4">
    <div>Total del período</div>
    <div class="sales-total">Q <?= number_format($totalPeriodo,2) ?></div>
  </div>

  <!-- Tabla -->
  <div class="card card-soft p-3">
    <div class="table-responsive table-sales">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Fecha</th>
            <th>Usuario</th>
            <th>Nota</th>
            <th class="text-end">Total</th>
          </tr>
        </thead>

        <tbody>
          <?php if(!$sales): ?>
            <tr>
              <td colspan="5" class="text-center text-muted py-4">
                No hay ventas en este período.
              </td>
            </tr>
          <?php endif; ?>

          <?php foreach($sales as $s): ?>
            <tr>
              <td><?= (int)$s['id'] ?></td>
              <td><?= htmlspecialchars($s['sale_date']) ?></td>
              <td><?= htmlspecialchars($s['username']) ?></td>
              <td><?= htmlspecialchars($s['note']) ?></td>
              <td class="text-end">
                <strong>Q <?= number_format((float)$s['total'],2) ?></strong>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>

      </table>
    </div>
  </div>

</div>

<?php include __DIR__ . "/layouts/footer.php"; ?>