<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

require __DIR__ . "/../../app/config/db.php";
$config = require __DIR__ . "/../../app/config/app.php";
require __DIR__ . "/../../app/includes/auth.php";

start_session();

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $username = trim($_POST["username"] ?? "");
  $password = (string)($_POST["password"] ?? "");

  $stmt = $pdo->prepare("SELECT id, password_hash, activo FROM admins WHERE username = ? LIMIT 1");
  $stmt->execute([$username]);
  $admin = $stmt->fetch();

  if (!$admin || (int)$admin["activo"] !== 1 || !password_verify($password, $admin["password_hash"])) {
    $error = "Credenciales incorrectas.";
  } else {
    session_regenerate_id(true);
    $_SESSION["admin_id"] = (int)$admin["id"];
    $_SESSION["admin_username"] = $username;

    header("Location: /admin/");
    exit;
  }
}

$title = "Admin - Login";
require __DIR__ . "/../../app/includes/header.php";
require __DIR__ . "/../../app/includes/navbar.php";
?>

<main class="container">
  <section class="section">
    <h1 class="section__title">Acceso admin</h1>

    <?php if ($error): ?>
      <p class="muted"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form class="box" method="post">
      <div class="box__row">
        <label class="form__label" >Usuario<br>
          <input name="username" required>
        </label>
      </div>

      <div class="box__row">
        <label class="form__label" >Contraseña<br>
          <input type="password" name="password" required>
        </label>
      </div>

      <div class="section__actions">
        <button class="btn btn--primary" type="submit">Entrar</button>
      </div>
    </form>
  </section>
</main>

<?php require __DIR__ . "/../../app/includes/footer.php"; ?>
