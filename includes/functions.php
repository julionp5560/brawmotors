<?php
declare(strict_types=1);

function e(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function current_page(): string {
  $p = $_GET['p'] ?? 'home';
  $p = preg_replace('/[^a-z0-9_-]/i', '', (string)$p);
  return $p !== '' ? $p : 'home';
}

function is_active(string $key): string {
  return current_page() === $key ? 'is-active' : '';
}

/**
 * Rol 1 = Administrador. El resto de roles son operativos (ventas/taller/
 * autolavado/clientes) y no tienen acceso a Ajustes, Servicios ni Reportes.
 */
function is_admin(): bool {
  return (int)($_SESSION['user']['rol_id'] ?? 0) === 1;
}

/**
 * Roles "Mecánico" (taller) y "Lavador" (autolavado): personal operativo
 * que usa la app móvil. Reciben y trabajan pedidos, pero NUNCA deben ver
 * ni manejar dinero (precios, cobros, corte de caja). Solo quien inicia
 * sesión con otro rol (cajera/admin/gerente) puede cobrar.
 * Se identifica por nombre de rol (no por id) para no depender del orden
 * en que se sembraron los roles en la base de datos.
 */
function is_operario(): bool {
  $rol = (string)($_SESSION['user']['rol_nombre'] ?? '');
  return in_array($rol, ['Mecánico', 'Lavador'], true);
}

/**
 * Módulo de la app móvil al que tiene acceso el usuario operativo actual:
 * 'taller' para Mecánico, 'autolavado' para Lavador, null si no aplica.
 */
function operario_modulo(): ?string {
  return match ((string)($_SESSION['user']['rol_nombre'] ?? '')) {
    'Mecánico'  => 'taller',
    'Lavador'   => 'autolavado',
    default     => null,
  };
}

/**
 * Preferencia de tema del usuario actual ('oscuro' | 'alto_contraste'),
 * guardada en usuarios.tema y reflejada en $_SESSION['user']['tema'] desde
 * el login. Se usa para pintar data-tema en <html> del lado del servidor
 * y evitar un "flash" del tema equivocado al cargar la página.
 */
function current_tema(): string {
  $t = (string)($_SESSION['user']['tema'] ?? 'oscuro');
  return $t === 'alto_contraste' ? 'alto_contraste' : 'oscuro';
}

function page_title(string $p): string {
  return match ($p) {
    'home'       => 'Inicio',
    'ventas'     => 'Ventas',
    'inventario' => 'Inventario',
    'taller'     => 'Taller',
    'autolavado' => 'Autolavado',
    'ordenes'    => 'Órdenes de Trabajo',
    'clientes'   => 'Clientes',
    'agenda'     => 'Agenda',
    'servicios'  => 'Servicios',
    'corte'      => 'Corte de Caja',
    'reporte'    => 'Reporte del Día',
    'reportes'   => 'Reportes',
    'ajustes'    => 'Ajustes',
    default      => 'Inicio',
  };
}
