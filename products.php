<?php
require_once __DIR__ . "/auth.php";

$q = trim((string)($_GET['q'] ?? ''));

// Consulta con búsqueda
$sql = "SELECT * FROM products WHERE active=1";
$params = [];
$types = "";

if ($q !== "") {
  $sql .= " AND (sku LIKE ? OR name LIKE ? OR category LIKE ?)";
  $like = "%$q%";
  $params = [$like, $like, $like];
  $types = "sss";
}
$sql .= " ORDER BY id DESC";

$stmt = $mysqli->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// KPIs
$res = $mysqli->query("SELECT COUNT(*) AS c FROM products WHERE active=1");
$totalProducts = (int)($res->fetch_assoc()['c'] ?? 0);

$res = $mysqli->query("SELECT COUNT(*) AS c FROM products WHERE active=1 AND stock <= min_stock");
$lowStock = (int)($res->fetch_assoc()['c'] ?? 0);

include __DIR__ . "/layouts/header.php";
?>

<div class="page-wrap">

  <div class="topbar">
    <div class="brand-badge">
      <div class="brand-icon">📦</div>
      <div>
        <div style="font-weight:800; font-size:18px; line-height:1;">Productos</div>
        <small class="text-muted">Gestión de inventario</small>
      </div>
    </div>

    <div class="text-end">
      <div style="font-weight:700;">
        <?= htmlspecialchars($_SESSION['user']['username']) ?>
        <span class="badge bg-secondary"><?= htmlspecialchars($_SESSION['user']['role']) ?></span>
      </div>
      <small class="text-muted">Listado y control</small>
    </div>
  </div>

  <!-- KPIs -->
  <div class="row g-3 mb-3">
    <div class="col-md-6">
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

    <div class="col-md-6">
      <div class="card card-soft p-3">
        <div class="stat">
          <div>
            <small class="text-muted">Bajo stock</small>
            <p class="num mb-0"><?= $lowStock ?></p>
          </div>
          <div class="kpi-icon">⚠️</div>
        </div>
        <small class="text-muted">Stock ≤ mínimo</small>
      </div>
    </div>
  </div>

  <!-- Barra de herramientas -->
  <div class="card card-soft p-3">
    <div class="toolbar">
      <form class="searchbox" method="get">
        <input class="form-control" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por SKU, nombre o categoría">
        <button class="btn btn-primary" type="submit">Buscar</button>
        <a class="btn btn-outline-secondary" href="products.php">Limpiar</a>
      </form>

      <div class="d-flex gap-2">
        <a class="btn btn-success" href="product_form.php">+ Nuevo producto</a>
        <a class="btn btn-outline-dark" href="index.php">← Inicio</a>
      </div>
    </div>

    <?php if($lowStock > 0): ?>
      <div class="alert alert-warning mb-3">
        <b>Atención:</b> Tienes <b><?= $lowStock ?></b> producto(s) con stock igual o menor al mínimo.
      </div>
    <?php endif; ?>

    <div class="table-responsive table-rounded">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>SKU</th>
            <th>Producto</th>
            <th>Categoría</th>
            <th class="text-end">Precio</th>
            <th class="text-center">Stock</th>
            <th class="text-center">Mín</th>
            <th class="text-end">Acciones</th>
          </tr>
        </thead>

        <tbody>
          <?php if(!$rows): ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-4">
                No hay productos para mostrar.
              </td>
            </tr>
          <?php endif; ?>

          <?php foreach($rows as $r): 
            $stock = (int)$r['stock'];
            $min = (int)$r['min_stock'];

            // colores según stock
            $badgeClass = "bg-success";
            if ($stock <= $min) $badgeClass = "bg-danger";
            else if ($stock <= ($min + 5)) $badgeClass = "bg-warning text-dark";
          ?>
            <tr>
              <td style="font-weight:700;"><?= htmlspecialchars($r['sku']) ?></td>
              <td><?= htmlspecialchars($r['name']) ?></td>
              <td><span class="badge bg-light text-dark"><?= htmlspecialchars((string)$r['category']) ?></span></td>
              <td class="text-end">Q <?= number_format((float)$r['price'], 2) ?></td>

              <td class="text-center">
                <span class="badge <?= $badgeClass ?> badge-stock"><?= $stock ?></span>
              </td>

              <td class="text-center"><?= $min ?></td>

              <td class="text-end row-actions">
                <a class="btn btn-sm btn-outline-primary"
                   href="product_form.php?id=<?= (int)$r['id'] ?>">Editar</a>

                <a class="btn btn-sm btn-outline-danger"
                   href="product_delete.php?id=<?= (int)$r['id'] ?>"
                   onclick="return confirm('¿Eliminar (desactivar) este producto?')">Eliminar</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>

      </table>
    </div>
  </div>

</div>

<?php include __DIR__ . "/layouts/footer.php"; ?>