<?php
declare(strict_types=1);

// Guard para la app móvil (movil/*.php). Reutiliza la misma sesión que el
// sistema de escritorio, pero:
//  - si no hay sesión, manda al login normal.
//  - si hay sesión pero el rol NO es Mecánico (4), lo manda de vuelta al
//    dashboard de escritorio: la app móvil es exclusiva del personal
//    operativo que no debe ver dinero.

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once __DIR__ . '/functions.php';

if (empty($_SESSION['user'])) {
  header('Location: ../auth/login.php');
  exit;
}

if (!is_operario()) {
  header('Location: ../dashboard.php');
  exit;
}
