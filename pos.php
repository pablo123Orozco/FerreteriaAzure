<?php
require_once __DIR__ . "/auth.php";

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

$msg = "";

// Agregar producto por SKU
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $sku = trim($_POST['sku'] ?? '');
  $qty = max(1, (int)($_POST['qty'] ?? 1));

  $stmt = $mysqli->prepare("SELECT id, name, price, stock FROM products WHERE sku=? AND active=1 LIMIT 1");
  $stmt->bind_param("s", $sku);
  $stmt->execute();
  $p = $stmt->get_result()->fetch_assoc();

  if (!$p) {
    $msg = "Producto no encontrado.";
  } else {
    $pid = (int)$p['id'];
    $_SESSION['cart'][$pid] = ($_SESSION['cart'][$pid] ?? 0) + $qty;
    $msg = "Agregado: " . $p['name'];
  }
}

if (isset($_GET['remove'])) {
  unset($_SESSION['cart'][(int)$_GET['remove']]);
  header("Location: pos.php");
  exit;
}

$cart = $_SESSION['cart'];
$items = [];
$total = 0;

if ($cart) {
  $ids = array_keys($cart);
  $placeholders = implode(",", array_fill(0, count($ids), "?"));
  $types = str_repeat("i", count($ids));

  $stmt = $mysqli->prepare("SELECT id, sku, name, price, stock FROM products WHERE id IN ($placeholders)");
  $stmt->bind_param($types, ...$ids);
  $stmt->execute();
  $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

  foreach ($rows as $r) {
    $pid = (int)$r['id'];
    $qty = (int)$cart[$pid];
    $line = $qty * (float)$r['price'];
    $total += $line;

    $items[] = [
      'id'=>$pid,
      'sku'=>$r['sku'],
      'name'=>$r['name'],
      'price'=>$r['price'],
      'stock'=>$r['stock'],
      'qty'=>$qty,
      'line'=>$line
    ];
  }
}

include __DIR__ . "/layouts/header.php";
?>

<div class="pos-container">

  <div class="row g-4">

    <!-- Panel Izquierdo -->
    <div class="col-lg-4">
      <div class="card pos-card p-4">

        <h4 style="font-weight:800;">🛒 Punto de Venta</h4>
        <small class="text-muted">Agregar producto por SKU</small>

        <?php if($msg): ?>
          <div class="alert alert-info mt-3"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <form method="post" class="pos-input mt-3">
          <div class="mb-3">
            <label>SKU</label>
            <input name="sku" class="form-control" autofocus required>
          </div>

          <div class="mb-3">
            <label>Cantidad</label>
            <input type="number" name="qty" value="1" min="1" class="form-control qty-input">
          </div>

          <div class="d-grid">
            <button class="btn btn-primary btn-lg">Agregar</button>
          </div>
        </form>

        <hr>

        <a href="index.php" class="btn btn-outline-dark w-100">← Volver al Inicio</a>

      </div>
    </div>

    <!-- Panel Derecho -->
    <div class="col-lg-8">
      <div class="card pos-card p-4">

        <h5 style="font-weight:800;">Carrito</h5>

        <div class="table-responsive">
          <table class="table cart-table align-middle">
            <thead class="table-light">
              <tr>
                <th>Producto</th>
                <th>Cant.</th>
                <th>Precio</th>
                <th>Total</th>
                <th></th>
              </tr>
            </thead>
            <tbody>

            <?php if(!$items): ?>
              <tr>
                <td colspan="5" class="text-center text-muted py-4">
                  No hay productos en el carrito.
                </td>
              </tr>
            <?php endif; ?>

            <?php foreach($items as $it): ?>
              <tr>
                <td>
                  <strong><?= htmlspecialchars($it['name']) ?></strong><br>
                  <small class="text-muted"><?= htmlspecialchars($it['sku']) ?></small>
                </td>
                <td><?= $it['qty'] ?></td>
                <td>Q <?= number_format($it['price'],2) ?></td>
                <td>Q <?= number_format($it['line'],2) ?></td>
                <td>
                  <a href="pos.php?remove=<?= $it['id'] ?>"
                     class="btn btn-sm btn-outline-danger">X</a>
                </td>
              </tr>
            <?php endforeach; ?>

            </tbody>
          </table>
        </div>

        <!-- Total -->
        <div class="pos-total-box mt-3">
          <div>Total a pagar</div>
          <div class="pos-total">Q <?= number_format($total,2) ?></div>
        </div>

        <?php if($items): ?>
        <form method="post" action="sale_checkout.php" class="mt-3">
          <div class="mb-3">
            <label>Nota (opcional)</label>
            <input name="note" class="form-control">
          </div>

          <div class="d-grid">
            <button class="btn btn-success btn-lg"
                    onclick="return confirm('¿Confirmar venta?')">
              Confirmar Venta
            </button>
          </div>
        </form>
        <?php endif; ?>

      </div>
    </div>

  </div>

</div>

<?php include __DIR__ . "/layouts/footer.php"; ?>