<?php
declare(strict_types=1);

// ─────────────────────────────────────────────────────────────────────────
// Activación automática de citas de la Agenda.
//
// Una cita se agenda con anticipación (p.ej. lunes para el viernes), pero
// no debe aparecer como una orden activa (taller/autolavado) desde que se
// agenda — eso llenaría el listado de órdenes con trabajo que todavía no
// toca hacer. En cambio, la cita se vuelve una orden real automáticamente
// el día en que efectivamente toca (fecha_cita <= hoy), la primera vez que
// alguien abre el listado de órdenes de ese día.
//
// Las columnas citas.orden_trabajo_id / citas.autolavado_orden_id (ver
// includes/migrate.php) guardan qué orden se generó a partir de cada cita,
// para no duplicarla si activar_citas_del_dia() corre más de una vez.
// ─────────────────────────────────────────────────────────────────────────

function citas_activacion_log(array $info): void {
  $linea = date('Y-m-d H:i:s') . ' ' . json_encode($info, JSON_UNESCAPED_UNICODE) . PHP_EOL;
  @file_put_contents(__DIR__ . '/../respaldos/citas_activadas.log', $linea, FILE_APPEND | LOCK_EX);
}

/**
 * Revisa las citas de un área (taller|autolavado) cuya fecha ya llegó y que
 * aún no tienen orden generada, y crea la orden correspondiente para cada
 * una. Pensada para llamarse al inicio de los endpoints de listado
 * (ordenes/listar.php y autolavado/list.php), así que debe ser rápida y
 * nunca debe tronar el listado si algo falla: cualquier error se registra
 * y se sigue con la siguiente cita.
 */
function activar_citas_del_dia(PDO $pdo, string $area): void {
  if ($area !== 'taller' && $area !== 'autolavado') return;

  $colOrden = $area === 'taller' ? 'orden_trabajo_id' : 'autolavado_orden_id';

  try {
    $st = $pdo->prepare("
      SELECT id, cliente_id, vehiculo_id, servicio_id, titulo, notas, creado_por, fecha_cita
      FROM citas
      WHERE area = :area
        AND estado NOT IN ('cancelada', 'completada')
        AND {$colOrden} IS NULL
        AND DATE(fecha_cita) <= CURDATE()
    ");
    $st->execute([':area' => $area]);
    $citas = $st->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e) {
    error_log('activar_citas_del_dia (' . $area . '): ' . $e->getMessage());
    return;
  }

  foreach ($citas as $cita) {
    try {
      if ($area === 'taller') {
        activar_cita_taller($pdo, $cita);
      } else {
        activar_cita_autolavado($pdo, $cita);
      }
    } catch (Throwable $e) {
      citas_activacion_log([
        'error' => $e->getMessage(),
        'cita_id' => $cita['id'] ?? null,
        'area' => $area,
      ]);
      error_log('activar_cita (' . $area . ') cita #' . ($cita['id'] ?? '?') . ': ' . $e->getMessage());
    }
  }
}

/**
 * Convierte una cita de taller en una orden de trabajo real (ordenes_trabajo),
 * usando el mismo esquema de folio que php/api/ordenes/crear.php, y enlaza
 * la cita a la orden generada.
 */
function activar_cita_taller(PDO $pdo, array $cita): void {
  $citaId = (int)$cita['id'];

  $descripcion = trim((string)($cita['titulo'] ?? '')) ?: 'Cita agendada';
  $notasCita = trim((string)($cita['notas'] ?? ''));
  $notas = 'Generada automáticamente desde la Agenda (cita #' . $citaId . ').' .
    ($notasCita !== '' ? ' ' . $notasCita : '');

  $usuarioId = (int)($cita['creado_por'] ?? 0) ?: 1;
  $vehiculoId = !empty($cita['vehiculo_id']) ? (int)$cita['vehiculo_id'] : null;

  $pdo->beginTransaction();
  try {
    $folioTmp = 'TMP-' . date('YmdHis') . '-' . random_int(1000, 9999);

    $ins = $pdo->prepare("
      INSERT INTO ordenes_trabajo
        (folio, cliente_id, vehiculo_id, usuario_id, descripcion_problema, diagnostico, fecha_entrada,
         fecha_estimada_entrega, fecha_entrega, kilometraje_entrada, estado, total, notas)
      VALUES
        (:folio, :cliente_id, :vehiculo_id, :usuario_id, :descripcion, NULL, NOW(),
         NULL, NULL, NULL, 'recibido', 0.00, :notas)
    ");
    $ins->execute([
      ':folio' => $folioTmp,
      ':cliente_id' => (int)$cita['cliente_id'],
      ':vehiculo_id' => $vehiculoId,
      ':usuario_id' => $usuarioId,
      ':descripcion' => $descripcion,
      ':notas' => $notas,
    ]);

    $ordenId = (int)$pdo->lastInsertId();

    $folioFinal = 'OT-' . date('Ymd') . '-' . str_pad((string)$ordenId, 6, '0', STR_PAD_LEFT);
    $pdo->prepare("UPDATE ordenes_trabajo SET folio = :folio WHERE id = :id")
        ->execute([':folio' => $folioFinal, ':id' => $ordenId]);

    // Si la cita ya tenía un servicio elegido, se agrega como renglón de la
    // orden (mismo criterio que php/api/ordenes/agregar_servicio.php), pero
    // solo si sigue activo — si no, la orden se crea igual, sin renglón, y
    // el servicio se agrega manualmente después.
    if (!empty($cita['servicio_id'])) {
      $stSvc = $pdo->prepare("SELECT id, precio FROM servicios WHERE id = ? AND activo = 1 LIMIT 1");
      $stSvc->execute([(int)$cita['servicio_id']]);
      $svc = $stSvc->fetch(PDO::FETCH_ASSOC);
      if ($svc) {
        $precio = (float)$svc['precio'];
        $pdo->prepare("
          INSERT INTO orden_servicios (orden_id, servicio_id, cantidad, precio_unitario, subtotal)
          VALUES (?, ?, 1, ?, ?)
        ")->execute([$ordenId, (int)$svc['id'], $precio, $precio]);

        $pdo->prepare("UPDATE ordenes_trabajo SET total = :total WHERE id = :id")
            ->execute([':total' => $precio, ':id' => $ordenId]);
      }
    }

    $pdo->prepare("UPDATE citas SET orden_trabajo_id = :orden_id WHERE id = :id")
        ->execute([':orden_id' => $ordenId, ':id' => $citaId]);

    $pdo->commit();

    citas_activacion_log([
      'cita_id' => $citaId,
      'orden_id' => $ordenId,
      'folio' => $folioFinal,
      'area' => 'taller',
    ]);
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $e;
  }
}

/**
 * Convierte una cita de autolavado en una orden real (autolavado_ordenes) y
 * enlaza la cita a la orden generada. autolavado_ordenes exige servicio_id
 * y precio (NOT NULL), así que solo se activa si la cita tiene un servicio
 * elegido y ese servicio sigue activo y no es de precio personalizado (ese
 * tipo de cargo requiere que caja capture el monto a mano). Si la cita no
 * cumple eso, se deja pendiente de activar manualmente — no se pierde, solo
 * no se puede crear la orden sin esos datos.
 */
function activar_cita_autolavado(PDO $pdo, array $cita): void {
  $citaId = (int)$cita['id'];

  if (empty($cita['servicio_id'])) {
    citas_activacion_log([
      'cita_id' => $citaId,
      'area' => 'autolavado',
      'omitida' => 'sin servicio_id, no se puede crear la orden automáticamente',
    ]);
    return;
  }

  $stSvc = $pdo->prepare("
    SELECT id, precio, precio_min, precio_max, es_personalizado
    FROM servicios WHERE id = ? AND tipo = 'autolavado' AND activo = 1 LIMIT 1
  ");
  $stSvc->execute([(int)$cita['servicio_id']]);
  $svc = $stSvc->fetch(PDO::FETCH_ASSOC);

  if (!$svc || (int)$svc['es_personalizado'] === 1) {
    citas_activacion_log([
      'cita_id' => $citaId,
      'area' => 'autolavado',
      'omitida' => !$svc ? 'servicio inválido/inactivo' : 'servicio de precio personalizado, requiere captura manual',
    ]);
    return;
  }

  $vehiculoId = !empty($cita['vehiculo_id']) ? (int)$cita['vehiculo_id'] : null;
  $notasCita = trim((string)($cita['notas'] ?? ''));
  $notas = 'Generada automáticamente desde la Agenda (cita #' . $citaId . ').' .
    ($notasCita !== '' ? ' ' . $notasCita : '');

  $pdo->beginTransaction();
  try {
    $ins = $pdo->prepare("
      INSERT INTO autolavado_ordenes (cliente_id, vehiculo_id, servicio_id, precio, notas, estado, fecha_creacion)
      VALUES (?, ?, ?, ?, ?, 'pendiente', NOW())
    ");
    $ins->execute([
      (int)$cita['cliente_id'],
      $vehiculoId,
      (int)$svc['id'],
      (float)$svc['precio'],
      $notas,
    ]);

    $ordenId = (int)$pdo->lastInsertId();

    $pdo->prepare("UPDATE citas SET autolavado_orden_id = :orden_id WHERE id = :id")
        ->execute([':orden_id' => $ordenId, ':id' => $citaId]);

    $pdo->commit();

    citas_activacion_log([
      'cita_id' => $citaId,
      'orden_id' => $ordenId,
      'area' => 'autolavado',
    ]);
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $e;
  }
}
