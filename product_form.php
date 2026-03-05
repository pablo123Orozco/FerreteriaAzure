<?php
require_once __DIR__ . "/auth.php";

$id = (int)($_GET['id'] ?? 0);
$editing = $id > 0;

$sku = $name = $category = "";
$price = 0; $stock = 0; $min_stock = 0;

if ($editing) {
  $stmt = $mysqli->prepare("SELECT * FROM products WHERE id=?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $p = $stmt->get_result()->fetch_assoc();
  if (!$p) exit("Producto no encontrado.");
  $sku = $p['sku']; $name = $p['name']; $category = (string)$p['category'];
  $price = (float)$p['price']; $stock = (int)$p['stock']; $min_stock = (int)$p['min_stock'];
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $sku = trim((string)($_POST['sku'] ?? ''));
  $name = trim((string)($_POST['name'] ?? ''));
  $category = trim((string)($_POST['category'] ?? ''));
  $price = (float)($_POST['price'] ?? 0);
  $stock = (int)($_POST['stock'] ?? 0);
  $min_stock = (int)($_POST['min_stock'] ?? 0);

  if ($sku === "" || $name === "") {
    $error = "SKU y Nombre son obligatorios.";
  } else {
    if ($editing) {
      $stmt = $mysqli->prepare("UPDATE products SET sku=?, name=?, category=?, price=?, stock=?, min_stock=? WHERE id=?");
      $stmt->bind_param("sssdiii", $sku, $name, $category, $price, $stock, $min_stock, $id);
      $stmt->execute();
    } else {
      $stmt = $mysqli->prepare("INSERT INTO products (sku, name, category, price, stock, min_stock) VALUES (?,?,?,?,?,?)");
      $stmt->bind_param("sssdi i", $sku, $name, $category, $price, $stock, $min_stock);
    }
    // Fix bind params for insert (php no permite espacio en types):
    if (!$editing) {
      $stmt = $mysqli->prepare("INSERT INTO products (sku, name, category, price, stock, min_stock) VALUES (?,?,?,?,?,?)");
      $stmt->bind_param("sssdii", $sku, $name, $category, $price, $stock, $min_stock);
      $stmt->execute();
    }
    header("Location: products.php");
    exit;
  }
}
?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><title><?= $editing ? "Editar" : "Nuevo" ?> Producto</title></head>
<body>
<?php include __DIR__ . "/navbar.php"; ?>
<h2><?= $editing ? "Editar" : "Nuevo" ?> Producto</h2>
<?php if($error): ?><p style="color:#b00"><?=h($error)?></p><?php endif; ?>

<form method="post">
  <label>SKU</label><br>
  <input name="sku" value="<?=h($sku)?>" required><br><br>

  <label>Nombre</label><br>
  <input name="name" value="<?=h($name)?>" required style="width:420px"><br><br>

  <label>Categoría</label><br>
  <input name="category" value="<?=h($category)?>"><br><br>

  <label>Precio (Q)</label><br>
  <input name="price" type="number" step="0.01" value="<?=h((string)$price)?>" required><br><br>

  <label>Stock</label><br>
  <input name="stock" type="number" value="<?=h((string)$stock)?>" required><br><br>

  <label>Stock mínimo</label><br>
  <input name="min_stock" type="number" value="<?=h((string)$min_stock)?>" required><br><br>

  <button type="submit">Guardar</button>
  <a href="products.php">Cancelar</a>
</form>
</body>
</html>