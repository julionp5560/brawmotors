<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/guard_movil.php';
require_once __DIR__ . '/../includes/functions.php';

// Cada rol operativo tiene un único módulo: no hay "selector" que muestre
// ambos. Lavador -> autolavado, Mecánico -> taller.
$modulo = operario_modulo();

if ($modulo === 'autolavado') {
  header('Location: autolavado.php');
  exit;
}
if ($modulo === 'taller') {
  header('Location: taller.php');
  exit;
}

// Rol operativo sin módulo asignado todavía (no debería pasar en la
// práctica, pero por seguridad no lo dejamos varado sin salida).
header('Location: ../dashboard.php');
exit;
