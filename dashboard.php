<?php
declare(strict_types=1);

// Mostrar errores en pantalla solo en desarrollo local (127.0.0.1/::1).
$esLocalhost = in_array($_SERVER['SERVER_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
    || in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);

error_reporting(E_ALL);
ini_set('display_errors', $esLocalhost ? '1' : '0');
ini_set('log_errors', '1');

require_once __DIR__ . "/includes/guard.php";
require_once __DIR__ . "/includes/functions.php";


$user = $_SESSION['user'];
$p = current_page();

$allowed = [
  'home','ventas','inventario','taller','autolavado','ordenes','clientes','agenda','servicios','corte','ajustes','reporte','reportes'
];



if (!in_array($p, $allowed, true)) {
  $p = 'home';
}

// Páginas solo para Administrador (rol_id = 1). El resto de roles se
// quedan en la operación diaria (ventas/taller/autolavado/clientes).
$adminOnly = ['ajustes', 'servicios', 'reportes', 'inventario'];
if (in_array($p, $adminOnly, true) && !is_admin()) {
  $p = 'home';
}

$page_file = __DIR__ . "/pages/{$p}.php";
$page_name = page_title($p);

require __DIR__ . "/includes/header.php";
require __DIR__ . "/includes/sidebar.php";
?>

<main class="app__main">

  <!-- HEADER -->
  <div class="main__header">
    <button class="icon-btn" id="btnSidebar" type="button" aria-label="Abrir menú">☰</button>

    <div class="main__title">
      <h2><?= e($page_name) ?></h2>
      <p>BRAW MOTORS • Punto de venta</p>
    </div>

    <!-- BUSCADOR (PC) -->
    <form class="search" action="dashboard.php" method="get">
      <input type="hidden" name="p" value="<?= e($p) ?>">
      <input
        class="search__input"
        type="search"
        name="q"
        placeholder="Buscar…"
        autocomplete="off"
      />
      <button class="search__btn" type="submit" aria-label="Buscar">⌕</button>
    </form>

    <!-- USUARIO -->
    <div class="main__user">
      <button class="tema-switch" type="button" id="btnTemaSwitch" title="Cambiar a modo alto contraste (para exteriores)">
        <span class="tema-switch__icon" id="temaSwitchIcon"><?= current_tema() === 'alto_contraste' ? '☀️' : '🌙' ?></span>
        <span id="temaSwitchLabel"><?= current_tema() === 'alto_contraste' ? 'Alto contraste' : 'Oscuro' ?></span>
      </button>
      <div class="chip">
        <div class="chip__name"><?= e((string)$user['nombre']) ?></div>
        <div class="chip__meta">
          @<?= e((string)$user['usuario']) ?> • Rol <?= (int)$user['rol_id'] ?>
        </div>
      </div>
      <a class="btn btn--ghost" href="auth/logout.php">Salir</a>
    </div>
  </div>

  <!-- CONTENIDO -->
  <section class="main__content">
    <?php
      if (is_file($page_file)) {
        require $page_file;
      } else {
        require __DIR__ . "/pages/home.php";
      }
    ?>
  </section>

</main>

<?php require __DIR__ . "/includes/footer.php"; ?>
