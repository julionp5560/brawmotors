<?php
// auth/login.php
declare(strict_types=1);
session_start();

if (!empty($_SESSION['user'])) {
  header("Location: ../dashboard.php");
  exit;
}

if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>BRAW MOTORS | Login</title>
  <link rel="stylesheet" href="../css/main.css?v=2" />
  <link rel="stylesheet" href="../css/login.css?v=2" />
</head>
<body>
  <main class="login-wrap">
    <section class="login-card">
      <div class="brand">
        <img class="brand__logo" src="../assets/logo.png" alt="BRAW MOTORS" onerror="this.style.display='none'">
        <div class="brand__text">
          <h1>BRAW<span>MOTORS</span></h1>
          <p>Acceso al sistema</p>
        </div>
      </div>

      <?php if ($error): ?>
        <div class="alert" role="alert">
          <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <form class="form" method="POST" action="proces_login.php" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">

        <label class="field">
          <span>Usuario o correo</span>
          <input name="identity" type="text" required minlength="3" placeholder="admin o correo@..." />
        </label>

        <label class="field">
          <span>Contraseña</span>
          <div class="password">
            <input id="password" name="password" type="password" required minlength="4" placeholder="••••••••" />
            <button class="password__toggle" type="button" id="togglePass">Ver</button>
          </div>
        </label>

        <button class="btn" type="submit">Entrar</button>

        <p class="hint">Tip: si ya tienes el usuario <b>admin</b>, entra con ese.</p>
      </form>
    </section>
  </main>

  <script src="../js/auth.js"></script>
</body>
</html>
