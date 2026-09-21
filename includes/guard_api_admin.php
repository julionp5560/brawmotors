<?php
declare(strict_types=1);

// Igual que guard_api.php (requiere sesión) pero además exige rol_id = 1
// (Administrador). Úsalo en endpoints sensibles: precios de servicios,
// reportes financieros, etc.
require_once __DIR__ . '/guard_api.php';
require_once __DIR__ . '/functions.php';

if (!is_admin()) {
  http_response_code(403);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok' => false, 'error' => 'No autorizado (solo administradores)'], JSON_UNESCAPED_UNICODE);
  exit;
}
