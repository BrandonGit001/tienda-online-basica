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

<main class="auth">
  <section class="auth__card">
    <h1 class="auth__title">Acceso admin</h1>
    <p class="auth__subtitle">Ingresa con tu usuario y contraseña</p>

    <?php if ($error): ?>
      <p class="auth__error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form class="form" method="post" autocomplete="off">
      <div class="form__grid">
        <div class="box__row span-2">
          <label class="form__label">Usuario
            <input class="form__input" name="username" required value="<?= htmlspecialchars($_POST["username"] ?? "") ?>">

          </label>
        </div>

        <div class="box__row span-2">
          <label class="form__label">Contraseña
            <input class="form__input" type="password" name="password" required>
          </label>
        </div>
      </div>

      <div class="section__actions auth__actions">
        <button class="btn btn--primary" type="submit">Entrar</button>
        <a class="btn btn--ghost" href="<?= $config["base_url"] ?>/">Volver a la tienda</a>
      </div>
    </form>
  </section>
</main>


<?php require __DIR__ . "/../../app/includes/footer.php"; ?>
