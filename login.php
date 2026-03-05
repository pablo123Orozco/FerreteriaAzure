<?php
require_once __DIR__ . "/config.php";

if (!empty($_SESSION['user'])) {
  header("Location: index.php");
  exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  $stmt = $mysqli->prepare("SELECT id, username, password_hash, role FROM users WHERE username=? LIMIT 1");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $user = $stmt->get_result()->fetch_assoc();

  if ($user && password_verify($password, $user['password_hash'])) {
    $_SESSION['user'] = [
      'id' => $user['id'],
      'username' => $user['username'],
      'role' => $user['role']
    ];
    header("Location: index.php");
    exit;
  }

  $error = "Usuario o contraseña incorrectos.";
}

include __DIR__ . "/layouts/header.php";
?>

<div class="card shadow-lg login-card">
  <div class="card-body p-4">

    <div class="text-center mb-4">
      <div class="logo-icon">🔧</div>
      <h4 class="mt-2">Sistema Ferretería</h4>
      <small class="text-muted">Iniciar Sesión</small>
    </div>

    <?php if($error): ?>
      <div class="alert alert-danger text-center">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="post">
      <div class="mb-3">
        <label class="form-label">Usuario</label>
        <input type="text" name="username" class="form-control" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Contraseña</label>
        <input type="password" name="password" class="form-control" required>
      </div>

      <div class="d-grid">
        <button type="submit" class="btn btn-primary">
          Ingresar
        </button>
      </div>
    </form>

    <div class="text-center mt-3">
      <small class="text-muted">
        
      </small>
    </div>

  </div>
</div>

<?php include __DIR__ . "/layouts/footer.php"; ?>