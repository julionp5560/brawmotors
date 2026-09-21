<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 3) . '/includes/guard_api.php';
require_once dirname(__DIR__, 3) . '/config/db.php';
require_once dirname(__DIR__, 3) . '/includes/functions.php';
require_once dirname(__DIR__, 3) . '/includes/whatsapp.php';

function respond(int $code, array $payload): void {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

function body(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw ?: '', true);
  return is_array($data) ? $data : [];
}

try {
  $pdo = db();
  $data = body();

  // ✅ Acepta orden_id o id (por si tu JS manda distinto)
  $orden_id = 0;
  if (isset($data['orden_id'])) $orden_id = (int)$data['orden_id'];
  if (!$orden_id && isset($data['id'])) $orden_id = (int)$data['id'];

  $estado    = isset($data['estado']) ? trim((string)$data['estado']) : '';
  $notaNueva = isset($data['nota']) ? trim((string)$data['nota']) : '';

  if ($orden_id <= 0) respond(400, ["ok"=>false, "error"=>"id/orden_id requerido"]);
  if ($estado === '') respond(400, ["ok"=>false, "error"=>"estado requerido"]);

  // ✅ IMPORTANTE:
  // Según tu braw_motos.sql, tu BD tiene enum('recibido','en_proceso','terminado','entregado','cancelado')
  // Si intentas guardar 'diagnosticado' o 'en_espera', MySQL NO lo guarda y te rompe.
  $permitidos = ['recibido','en_proceso','terminado','entregado','cancelado'];

  if (!in_array($estado, $permitidos, true)) {
    respond(400, ["ok"=>false, "error"=>"Estado inválido. Permitidos: ".implode(", ", $permitidos)]);
  }

  // El rol Mecánico solo trabaja la orden (en_proceso/terminado); entregar
  // (cierre + cobro) y cancelar son decisiones de caja/admin.
  if (is_operario() && in_array($estado, ['entregado', 'cancelado'], true)) {
    respond(403, ["ok"=>false, "error"=>"No autorizado: solo caja puede entregar o cancelar una orden."]);
  }

  $pdo->beginTransaction();

  // Leer estado y notas actuales
  $st = $pdo->prepare("SELECT id, estado, notas, fecha_entrega, pagado, cliente_id, vehiculo_id, folio FROM ordenes_trabajo WHERE id = ? LIMIT 1");
  $st->execute([$orden_id]);
  $ord = $st->fetch(PDO::FETCH_ASSOC);

  if (!$ord) {
    $pdo->rollBack();
    respond(404, ["ok"=>false, "error"=>"Orden no encontrada"]);
  }

  $estado_actual = (string)$ord['estado'];
  $notasActuales = (string)($ord['notas'] ?? '');

  if ($estado_actual === 'cancelado') {
    $pdo->rollBack();
    respond(400, ["ok"=>false, "error"=>"La orden está cancelada y no puede cambiar de estado"]);
  }

  // El cobro ya no bloquea el inicio del trabajo: los clientes suelen
  // pagar al entregar, no al recibir. El botón "Cobrar" sigue disponible
  // en cualquier momento mientras la orden no esté pagada ni cancelada.

  // Construir notas en PHP
  $notasFinal = $notasActuales;
  if ($notaNueva !== '') {
    $notasFinal = ($notasFinal === '') ? $notaNueva : ($notasFinal . "\n" . $notaNueva);
  }

  if ($estado === 'entregado') {
    $sql = "
      UPDATE ordenes_trabajo
      SET estado = :estado,
          notas = :notas,
          fecha_entrega = COALESCE(fecha_entrega, NOW())
      WHERE id = :id
    ";
  } else {
    $sql = "
      UPDATE ordenes_trabajo
      SET estado = :estado,
          notas = :notas
      WHERE id = :id
    ";
  }

  $upd = $pdo->prepare($sql);
  $upd->execute([
    ":estado" => $estado,
    ":notas"  => $notasFinal,
    ":id"     => $orden_id
  ]);

  // ✅ Si no actualizó nada, lo decimos (esto detecta “no hizo nada”)
  if ($upd->rowCount() === 0) {
    $pdo->rollBack();
    respond(400, ["ok"=>false, "error"=>"No se actualizó la orden (¿id correcto? ¿estado igual al actual?)"]);
  }

  // ✅ Volver a leer para confirmar qué quedó guardado
  $st2 = $pdo->prepare("SELECT estado FROM ordenes_trabajo WHERE id = ? LIMIT 1");
  $st2->execute([$orden_id]);
  $nuevo = $st2->fetchColumn();

  $pdo->commit();

  // Aviso por WhatsApp: solo cuando el pedido ACABA de quedar
  // "terminado" (no en cada guardado repetido). Va después del commit
  // y en su propio try/catch: si Meta falla o no está configurado,
  // la orden ya quedó actualizada de todos modos.
  if ($estado === 'terminado' && $estado_actual !== 'terminado') {
    try {
      $stCli = $pdo->prepare("SELECT nombre_completo, telefono FROM clientes WHERE id = ? LIMIT 1");
      $stCli->execute([(int)$ord['cliente_id']]);
      $cliente = $stCli->fetch(PDO::FETCH_ASSOC);
      if ($cliente) {
        // El template dice "tu vehículo con placas {{2}}": mandamos la
        // placa real del vehículo, no el folio interno de la orden.
        $placas = '-';
        if (!empty($ord['vehiculo_id'])) {
          $stVeh = $pdo->prepare("SELECT placas FROM vehiculos WHERE id = ? LIMIT 1");
          $stVeh->execute([(int)$ord['vehiculo_id']]);
          $placasDb = $stVeh->fetchColumn();
          if (!empty($placasDb)) $placas = (string)$placasDb;
        }
        enviar_whatsapp_pedido_listo($cliente['telefono'], $cliente['nombre_completo'], $placas);
      }
    } catch (Throwable $e) {
      error_log('whatsapp pedido_listo (taller orden #' . $orden_id . '): ' . $e->getMessage());
    }
  }

  respond(200, [
    "ok" => true,
    "data" => [
      "orden_id" => $orden_id,
      "estado_anterior" => $estado_actual,
      "estado_nuevo" => (string)$nuevo
    ]
  ]);

} catch (Throwable $e) {
  if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
  respond(500, ["ok"=>false, "error"=>"Error al cambiar estado", "detail"=>$e->getMessage()]);
}
