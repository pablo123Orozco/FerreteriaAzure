<?php
require_once __DIR__ . "/auth.php";

$cart = $_SESSION['cart'] ?? [];
if (!$cart) {
  header("Location: pos.php");
  exit;
}

$note = trim((string)($_POST['note'] ?? ''));
$userId = (int)$_SESSION['user']['id'];

$mysqli->begin_transaction();

try {
  // Obtener productos del carrito con lock para stock
  $ids = array_keys($cart);
  $placeholders = implode(",", array_fill(0, count($ids), "?"));
  $types = str_repeat("i", count($ids));

  $stmt = $mysqli->prepare("SELECT id, price, stock FROM products WHERE id IN ($placeholders) AND active=1 FOR UPDATE");
  $stmt->bind_param($types, ...$ids);
  $stmt->execute();
  $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

  // Validar stock
  $byId = [];
  foreach ($rows as $r) $byId[(int)$r['id']] = $r;

  foreach ($cart as $pid => $qty) {
    $pid = (int)$pid; $qty = (int)$qty;
    if (!isset($byId[$pid])) throw new Exception("Producto inválido en carrito.");
    if ((int)$byId[$pid]['stock'] < $qty) throw new Exception("Stock insuficiente para producto ID $pid.");
  }

  // Crear venta
  $stmt = $mysqli->prepare("INSERT INTO sales (user_id, total, note) VALUES (?, 0, ?)");
  $stmt->bind_param("is", $userId, $note);
  $stmt->execute();
  $saleId = (int)$mysqli->insert_id;

  $total = 0.0;

  // Insert items y descontar stock
  foreach ($cart as $pid => $qty) {
    $pid = (int)$pid; $qty = (int)$qty;
    $price = (float)$byId[$pid]['price'];
    $line = $price * $qty;
    $total += $line;

    $stmt = $mysqli->prepare("INSERT INTO sale_items (sale_id, product_id, qty, unit_price, line_total) VALUES (?,?,?,?,?)");
    $stmt->bind_param("iiidd", $saleId, $pid, $qty, $price, $line);
    $stmt->execute();

    $stmt = $mysqli->prepare("UPDATE products SET stock = stock - ? WHERE id=?");
    $stmt->bind_param("ii", $qty, $pid);
    $stmt->execute();
  }

  // Update total
  $stmt = $mysqli->prepare("UPDATE sales SET total=? WHERE id=?");
  $stmt->bind_param("di", $total, $saleId);
  $stmt->execute();

  $mysqli->commit();
  $_SESSION['cart'] = [];
  header("Location: sales.php?ok=1");
  exit;

} catch (Throwable $e) {
  $mysqli->rollback();
  http_response_code(400);
  echo "Error al confirmar venta: " . h($e->getMessage());
}