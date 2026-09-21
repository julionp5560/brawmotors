<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 3) . '/includes/guard_api.php';
require_once dirname(__DIR__, 3) . '/config/db.php';
require_once dirname(__DIR__, 3) . '/includes/functions.php';
require_once dirname(__DIR__, 3) . '/includes/whatsapp.php';

function bad(string $msg, int $code = 400): void {
  http_response_code($code);
  echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}

$data   = json_decode(file_get_contents('php://input') ?: '{}', true);
$id     = (int)($data['id']     ?? 0);
$estado = (string)($data['estado'] ?? '');

$permitidos = ['pendiente', 'en_proceso', 'terminado', 'entregado'];
if ($id <= 0)                          bad('ID requerido.');
if (!in_array($estado, $permitidos, true)) bad('Estado inválido.');

// El rol Mecánico/Lavador solo trabaja el pedido (en_proceso/terminado);
// marcar "entregado" es exclusivo de caja/admin.
if ($estado === 'entregado' && is_operario()) {
  bad('No autorizado: solo caja puede marcar una orden como entregada.', 403);
}

try {
  $pdo = db();

  // Se lee el estado/cliente actual una sola vez: sirve para la
  // validación de cobro (abajo) y para saber si el pedido ACABA de
  // pasar a "terminado" (y así no duplicar el aviso de WhatsApp).
  $stPrev = $pdo->prepare("SELECT estado, pagado, cliente_id, vehiculo_id FROM autolavado_ordenes WHERE id = ? LIMIT 1");
  $stPrev->execute([$id]);
  $prev = $stPrev->fetch(PDO::FETCH_ASSOC);
  if (!$prev) bad('Orden no encontrada.');
  $estadoAnterior = (string)$prev['estado'];

  // El cobro ya no bloquea el inicio del trabajo: los clientes suelen
  // pagar al entregar, no al recibir. El botón "Cobrar" sigue disponible
  // en cualquier momento mientras la orden no esté pagada.

  // Actualizar fecha según el nuevo estado
  $fechaCol = match($estado) {
    'en_proceso' => ', fecha_inicio = NOW()',
    'terminado', 'entregado' => ', fecha_fin = NOW()',
    default => '',
  };

  $upd = $pdo->prepare("UPDATE autolavado_ordenes SET estado = ? {$fechaCol} WHERE id = ?");
  $upd->execute([$estado, $id]);

  if ($upd->rowCount() === 0) bad('Orden no encontrada.');

  // Aviso por WhatsApp: solo cuando el pedido ACABA de quedar
  // "terminado" y tiene un cliente registrado con teléfono. En su
  // propio try/catch: si Meta falla, la orden ya quedó actualizada.
  if ($estado === 'terminado' && $estadoAnterior !== 'terminado' && !empty($prev['cliente_id'])) {
    try {
      $stCli = $pdo->prepare("SELECT nombre_completo, telefono FROM clientes WHERE id = ? LIMIT 1");
      $stCli->execute([(int)$prev['cliente_id']]);
      $cliente = $stCli->fetch(PDO::FETCH_ASSOC);
      if ($cliente) {
        // El template dice "tu vehículo con placas {{2}}": mandamos la
        // placa real del vehículo, no un texto fijo.
        $placas = '-';
        if (!empty($prev['vehiculo_id'])) {
          $stVeh = $pdo->prepare("SELECT placas FROM vehiculos WHERE id = ? LIMIT 1");
          $stVeh->execute([(int)$prev['vehiculo_id']]);
          $placasDb = $stVeh->fetchColumn();
          if (!empty($placasDb)) $placas = (string)$placasDb;
        }
        enviar_whatsapp_pedido_listo($cliente['telefono'], $cliente['nombre_completo'], $placas);
      }
    } catch (Throwable $e) {
      error_log('whatsapp pedido_listo (autolavado #' . $id . '): ' . $e->getMessage());
    }
  }

  echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
