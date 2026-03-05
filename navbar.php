<?php require_once __DIR__ . "/auth.php"; ?>
<div style="padding:10px;border-bottom:1px solid #ddd;margin-bottom:15px">
  <b>Ferretería</b> |
  <a href="index.php">Inicio</a> |
  <a href="products.php">Productos</a> |
  <a href="pos.php">Ventas (POS)</a> |
  <a href="sales.php">Reporte Ventas</a> |
  <span style="float:right">
    <?=h($_SESSION['user']['username'])?> (<?=h($_SESSION['user']['role'])?>)
    - <a href="logout.php">Salir</a>
  </span>
</div>