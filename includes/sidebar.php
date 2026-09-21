<?php
declare(strict_types=1);
require_once __DIR__ . "/functions.php";
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar__brand">
    <a class="brand" href="dashboard.php?p=home">
      <img class="brand__logo" src="assets/logo.png" alt="BRAW MOTORS" onerror="this.style.display='none'">
      <div class="brand__text">
        <div class="brand__name">BRAW<span>MOTORS</span></div>
        <div class="brand__sub">Dashboard</div>
      </div>
    </a>
    <button class="icon-btn icon-btn--close" id="btnSidebarClose" type="button" aria-label="Cerrar menú">✕</button>
  </div>

  <nav class="nav">
    <a class="nav__item <?= is_active('home') ?>" href="dashboard.php?p=home">
      <span class="nav__icon">🏠</span>
      <span class="nav__text">Inicio</span>
    </a>
    
    <a class="nav__item <?= is_active('corte') ?>" href="dashboard.php?p=corte">
      <span class="nav__icon">💵</span>
      <span class="nav__text">Corte</span>
    </a>

    <?php if (is_admin()): ?>
    <a class="nav__item <?= is_active('servicios') ?>" href="dashboard.php?p=servicios">
        <span class="nav__icon">🧩</span>
        <span class="nav__text">Servicios</span>
    </a>
    <?php endif; ?>

    <a class="nav__item <?= is_active('ventas') ?>" href="dashboard.php?p=ventas">
      <span class="nav__icon">🧾</span>
      <span class="nav__text">Ventas</span>
    </a>

    <?php if (is_admin()): ?>
    <a class="nav__item <?= is_active('inventario') ?>" href="dashboard.php?p=inventario">
      <span class="nav__icon">📦</span>
      <span class="nav__text">Inventario</span>
    </a>
    <?php endif; ?>

    <a class="nav__item <?= is_active('taller') ?>" href="dashboard.php?p=taller">
      <span class="nav__icon">🛠️</span>
      <span class="nav__text">Taller</span>
    </a>

    <a class="nav__item <?= is_active('autolavado') ?>" href="dashboard.php?p=autolavado">
      <span class="nav__icon">🚿</span>
      <span class="nav__text">Autolavado</span>
    </a>

    <a class="nav__item <?= is_active('clientes') ?>" href="dashboard.php?p=clientes">
      <span class="nav__icon">👥</span>
      <span class="nav__text">Clientes</span>
    </a>

    <a class="nav__item <?= is_active('agenda') ?>" href="dashboard.php?p=agenda">
      <span class="nav__icon">📅</span>
      <span class="nav__text">Agenda</span>
    </a>

    <?php if (is_admin()): ?>
    <a class="nav__item <?= is_active('reportes') ?>" href="dashboard.php?p=reportes">
      <span class="nav__icon">📊</span>
      <span class="nav__text">Reportes</span>
    </a>

    <div class="nav__sep"></div>

    <a class="nav__item <?= is_active('ajustes') ?>" href="dashboard.php?p=ajustes">
      <span class="nav__icon">⚙️</span>
      <span class="nav__text">Ajustes</span>
    </a>
    <?php endif; ?>
  </nav>

  <div class="sidebar__footer">
    <div class="sidebar__hint">
      <div class="dot"></div>
      <span>Sistema activo</span>
    </div>
    <small>© <?= date('Y') ?> BRAW MOTORS</small>
  </div>
</aside>

<div class="overlay" id="overlay"></div>
