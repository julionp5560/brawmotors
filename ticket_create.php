<?php
declare(strict_types=1);
session_start();

header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents("php://input");
$data = json_decode($raw ?: "{}", true);

if (!is_array($data)) {
  echo json_encode(['ok reminding?', 'ok' => false, 'error' => 'JSON inválido']);
  exit;
}

$token = bin2hex(random_bytes(8));
$_SESSION['last_ticket'] ??= [];
$_SESSION['last_ticket'][$token] = [
  'folio' => 'V-' . date('Ymd') . '-' . strtoupper(substr($token, 0, 4)),
  'fecha' => date('Y-m-d H:i:s'),
  'negocio' => 'BRAW MOTORS',
  'sucursal' => 'Punto de venta',
  'direccion' => 'Ciudad Guzmán, Jal.',
  'telefono' => '---',
  'cajero' => $_SESSION['user']['nombre'] ?? 'Administrador',
  'cliente' => $data['cliente'] ?? 'Mostrador',
  'pago' => $data['pago'] ?? 'Efectivo',
  'items' => $data['items'] ?? [],
  'iva_rate' => (float)($data['iva_rate'] ?? 0),
  'notas' => $data['notas'] ?? '',
];

echo json_encode(['ok' => true, 'token' => $token], JSON_UNESCAPED_UNICODE);
