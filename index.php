<?php
require_once __DIR__ . "/auth.php";

// KPIs rápidos
// total productos
$res = $mysqli->query("SELECT COUNT(*) AS c FROM products WHERE active=1");
$totalProducts = (int)($res->fetch_assoc()['c'] ?? 0);

// bajo stock
$res = $mysqli->query("SELECT COUNT(*) AS c FROM products WHERE active=1 AND stock <= min_stock");
$lowStock = (int)($res->fetch_assoc()['c'] ?? 0);

// ventas hoy
$res = $mysqli->query("SELECT COALESCE(SUM(total),0) AS t FROM sales WHERE DATE(sale_date)=CURDATE()");
$salesToday = (float)($res->fetch_assoc()['t'] ?? 0.0);

// últimas 5 ventas
$stmt = $mysqli->prepare("
  SELECT s.id, s.sale_date, s.total, u.username
  FROM sales s
  JOIN users u ON u.id = s.user_id
  ORDER BY s.id DESC
  LIMIT 5
");
$stmt->execute();
$lastSales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include __DIR__ . "/layouts/header.php";
?>

<div class="page-wrap">

  <!-- Topbar -->
  <div class="topbar">
    <div class="brand-badge">
      <div class="brand-icon">🔧</div>
      <div>
        <div style="font-weight:800; font-size:18px; line-height:1;">Sistema Ferretería</div>
        <small class="text-muted">Panel principal</small>
      </div>
    </div>

    <div class="text-end">
      <div style="font-weight:700;">
        <?= htmlspecialchars($_SESSION['user']['username']) ?>
        <span class="badge bg-secondary"><?= htmlspecialchars($_SESSION['user']['role']) ?></span>
      </div>
      <small class="text-muted">Bienvenido(a)</small>
    </div>
  </div>

  <!-- KPIs -->
  <div class="row g-3 mb-3">
    <div class="col-md-4">
      <div class="card card-soft p-3">
        <div class="stat">
          <div>
            <small class="text-muted">Productos activos</small>
            <p class="num mb-0"><?= $totalProducts ?></p>
          </div>
          <div class="kpi-icon">📦</div>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card card-soft p-3">
        <div class="stat">
          <div>
            <small class="text-muted">Productos bajo stock</small>
            <p class="num mb-0"><?= $lowStock ?></p>
          </div>
          <div class="kpi-icon">⚠️</div>
        </div>
        <small class="text-muted">Stock ≤ mínimo</small>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card card-soft p-3">
        <div class="stat">
          <div>
            <small class="text-muted">Ventas de hoy</small>
            <p class="num mb-0">Q <?= number_format($salesToday, 2) ?></p>
          </div>
          <div class="kpi-icon">💰</div>
        </div>
        <small class="text-muted"><?= date('Y-m-d') ?></small>
      </div>
    </div>
  </div>

  <!-- Accesos rápidos + últimas ventas -->
  <div class="row g-3">
    <div class="col-lg-5">
      <div class="card card-soft p-3 quick">
        <h5 class="mb-3" style="font-weight:800;">Accesos rápidos</h5>

        <div class="d-grid gap-2">
          <a class="btn btn-primary" href="products.php">📦 Productos</a>
          <a class="btn btn-success" href="pos.php">🛒 Ventas (POS)</a>
          <a class="btn btn-dark" href="sales.php">📊 Reporte de ventas</a>

          <?php if(($_SESSION['user']['role'] ?? '') === 'admin'): ?>
            <a class="btn btn-outline-secondary" href="products.php?q=">⚙️ Gestión avanzada</a>
          <?php endif; ?>

          <a class="btn btn-outline-danger" href="logout.php">🚪 Cerrar sesión</a>
        </div>

        <hr class="my-3">
        <small class="text-muted">
          Consejo: Mantén actualizado el stock mínimo para recibir alertas de bajo inventario.
        </small>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card card-soft p-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <h5 class="mb-0" style="font-weight:800;">Últimas ventas</h5>
          <a href="sales.php" class="text-decoration-none">Ver todas →</a>
        </div>

        <div class="table-rounded">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Fecha</th>
                <th>Usuario</th>
                <th class="text-end">Total</th>
              </tr>
            </thead>
            <tbody>
              <?php if(!$lastSales): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">Aún no hay ventas registradas.</td></tr>
              <?php endif; ?>

              <?php foreach($lastSales as $s): ?>
                <tr>
                  <td><?= (int)$s['id'] ?></td>
                  <td><?= htmlspecialchars($s['sale_date']) ?></td>
                  <td><?= htmlspecialchars($s['username']) ?></td>
                  <td class="text-end">Q <?= number_format((float)$s['total'], 2) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . "/layouts/footer.php"; ?>