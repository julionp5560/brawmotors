<?php
declare(strict_types=1);

/**
 * Migraciones automáticas e idempotentes. Se ejecutan una vez por request
 * (desde config/db.php, justo después de abrir la conexión) y solo crean
 * lo que falte: tablas de "extras" (recargo sujeto a aprobación) para
 * autolavado y taller, y las columnas pagado/venta_id en las órdenes para
 * poder cobrar antes de iniciar el trabajo.
 *
 * Cada paso va en su propio try/catch para que, si uno falla, los demás
 * de todos modos se apliquen (y no se caiga el sitio completo).
 */
function run_migrations(PDO $pdo): void {
  static $ya_corrio = false;
  if ($ya_corrio) return;
  $ya_corrio = true;

  $tableExists = function (string $tabla) use ($pdo): bool {
    $st = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1");
    $st->execute([$tabla]);
    return (bool)$st->fetchColumn();
  };

  $columnExists = function (string $tabla, string $columna) use ($pdo): bool {
    $st = $pdo->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1");
    $st->execute([$tabla, $columna]);
    return (bool)$st->fetchColumn();
  };

  // ── Tablas de extras (recargo por dificultad, sujeto a aprobación) ──
  try {
    if (!$tableExists('autolavado_extras')) {
      $pdo->exec("
        CREATE TABLE autolavado_extras (
          id INT UNSIGNED NOT NULL AUTO_INCREMENT,
          orden_id INT UNSIGNED NOT NULL,
          monto_sugerido DECIMAL(10,2) NOT NULL,
          monto_final DECIMAL(10,2) NULL,
          justificacion VARCHAR(255) NOT NULL,
          estado ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
          creado_por INT UNSIGNED NULL,
          revisado_por INT UNSIGNED NULL,
          revisado_en DATETIME NULL,
          creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          KEY idx_orden (orden_id),
          KEY idx_estado (estado)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
      ");
    }
  } catch (Throwable $e) { error_log('migrate autolavado_extras: ' . $e->getMessage()); }

  try {
    if (!$tableExists('orden_extras')) {
      $pdo->exec("
        CREATE TABLE orden_extras (
          id INT UNSIGNED NOT NULL AUTO_INCREMENT,
          orden_id INT UNSIGNED NOT NULL,
          monto_sugerido DECIMAL(10,2) NOT NULL,
          monto_final DECIMAL(10,2) NULL,
          justificacion VARCHAR(255) NOT NULL,
          estado ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
          creado_por INT UNSIGNED NULL,
          revisado_por INT UNSIGNED NULL,
          revisado_en DATETIME NULL,
          creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          KEY idx_orden (orden_id),
          KEY idx_estado (estado)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
      ");
    }
  } catch (Throwable $e) { error_log('migrate orden_extras: ' . $e->getMessage()); }

  // ── Columnas pagado/venta_id para cobrar antes de iniciar ──
  try {
    if (!$columnExists('autolavado_ordenes', 'pagado')) {
      $pdo->exec("ALTER TABLE autolavado_ordenes ADD COLUMN pagado TINYINT(1) NOT NULL DEFAULT 0 AFTER estado");
    }
  } catch (Throwable $e) { error_log('migrate autolavado_ordenes.pagado: ' . $e->getMessage()); }

  try {
    if (!$columnExists('autolavado_ordenes', 'venta_id')) {
      $pdo->exec("ALTER TABLE autolavado_ordenes ADD COLUMN venta_id INT NULL AFTER pagado");
    }
  } catch (Throwable $e) { error_log('migrate autolavado_ordenes.venta_id: ' . $e->getMessage()); }

  try {
    if (!$columnExists('ordenes_trabajo', 'pagado')) {
      $pdo->exec("ALTER TABLE ordenes_trabajo ADD COLUMN pagado TINYINT(1) NOT NULL DEFAULT 0 AFTER estado");
    }
  } catch (Throwable $e) { error_log('migrate ordenes_trabajo.pagado: ' . $e->getMessage()); }

  try {
    if (!$columnExists('ordenes_trabajo', 'venta_id')) {
      $pdo->exec("ALTER TABLE ordenes_trabajo ADD COLUMN venta_id INT NULL AFTER pagado");
    }
  } catch (Throwable $e) { error_log('migrate ordenes_trabajo.venta_id: ' . $e->getMessage()); }

  // ── Receta de insumos por servicio (qué productos de inventario usa un
  // servicio y cuánto de cada uno), para poder calcular un costo de
  // referencia en vivo y avisar si el precio de venta ya no lo cubre. ──
  try {
    if (!$tableExists('servicio_insumos')) {
      $pdo->exec("
        CREATE TABLE servicio_insumos (
          id INT UNSIGNED NOT NULL AUTO_INCREMENT,
          servicio_id INT UNSIGNED NOT NULL,
          producto_id INT UNSIGNED NOT NULL,
          cantidad DECIMAL(10,3) NOT NULL DEFAULT 1,
          creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          KEY idx_servicio (servicio_id),
          KEY idx_producto (producto_id),
          UNIQUE KEY uniq_servicio_producto (servicio_id, producto_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
      ");
    }
  } catch (Throwable $e) { error_log('migrate servicio_insumos: ' . $e->getMessage()); }

  // ── Preferencia de tema por usuario (oscuro / alto_contraste), para
  // que se lea bien bajo sol directo en las tablets del taller/autolavado. ──
  try {
    if (!$columnExists('usuarios', 'tema')) {
      $pdo->exec("ALTER TABLE usuarios ADD COLUMN tema VARCHAR(20) NOT NULL DEFAULT 'oscuro' AFTER activo");
    }
  } catch (Throwable $e) { error_log('migrate usuarios.tema: ' . $e->getMessage()); }

  // ── Rango de precio sugerido por servicio (precio_min/precio_max). Se usa
  // para que el lavador (app móvil, no ve dinero en general) pueda elegir
  // un monto dentro de un rango permitido al crear el pedido — el precio
  // final de todos modos se cobra en caja, esto solo evita que el cajero
  // tenga que adivinar el precio según el vehículo. NULL = precio fijo,
  // sin opción a elegir. ──
  try {
    if (!$columnExists('servicios', 'precio_min')) {
      $pdo->exec("ALTER TABLE servicios ADD COLUMN precio_min DECIMAL(10,2) NULL AFTER precio");
    }
    if (!$columnExists('servicios', 'precio_max')) {
      $pdo->exec("ALTER TABLE servicios ADD COLUMN precio_max DECIMAL(10,2) NULL AFTER precio_min");
    }
  } catch (Throwable $e) { error_log('migrate servicios.precio_min/max: ' . $e->getMessage()); }

  // ── Cargo personalizado (autolavado) ─────────────────────────────
  // Feature aparte de "Pedir extra por dificultad" (esa pasa por
  // aprobación de admin). Este es un cargo que caja/admin agrega directo
  // al armar el pedido, con descripción libre y monto entre $50 y $2000
  // (p.ej. "trajo casco extra", "manchas de pintura"). Se implementa como
  // un servicio especial marcado con es_personalizado=1: así reutiliza
  // todo el flujo existente de crear orden (servicio_id + precio), y
  // list.php sustituye el nombre genérico por la descripción capturada.
  try {
    if (!$columnExists('servicios', 'es_personalizado')) {
      $pdo->exec("ALTER TABLE servicios ADD COLUMN es_personalizado TINYINT(1) NOT NULL DEFAULT 0 AFTER precio_max");
    }
    $st = $pdo->prepare("SELECT id FROM servicios WHERE es_personalizado = 1 LIMIT 1");
    $st->execute();
    if (!$st->fetchColumn()) {
      $pdo->prepare("
        INSERT INTO servicios (nombre, descripcion, tipo, precio, precio_min, precio_max, es_personalizado, activo)
        VALUES ('Cargo personalizado', 'Cargo con descripción y monto libres, agregado directo por caja/admin.', 'autolavado', 0, 50, 2000, 1, 1)
      ")->execute();
    }
  } catch (Throwable $e) { error_log('migrate servicios.es_personalizado: ' . $e->getMessage()); }

  // ── Agenda de citas ──────────────────────────────────────────────
  // Ya existía una tabla `citas` en el esquema original (cliente_id FK
  // obligatorio, fecha_cita datetime, duracion, estado con 5 valores) que
  // nunca se había usado desde la UI. En vez de crear una tabla paralela,
  // se reutiliza y solo se le agregan las columnas que faltan: `titulo`
  // (motivo libre de la cita) y `area` (taller/autolavado/general, para
  // filtrar/agrupar sin depender de elegir un servicio exacto).
  try {
    if (!$tableExists('citas')) {
      $pdo->exec("
        CREATE TABLE citas (
          id INT UNSIGNED NOT NULL AUTO_INCREMENT,
          cliente_id INT UNSIGNED NOT NULL,
          vehiculo_id INT UNSIGNED NULL,
          servicio_id INT UNSIGNED NULL,
          titulo VARCHAR(150) NULL,
          area ENUM('taller','autolavado','general') NOT NULL DEFAULT 'general',
          fecha_cita DATETIME NOT NULL,
          duracion INT UNSIGNED NULL DEFAULT 60,
          estado ENUM('pendiente','confirmada','en_proceso','completada','cancelada') NOT NULL DEFAULT 'pendiente',
          notas TEXT NULL,
          creado_por INT UNSIGNED NULL,
          fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          KEY idx_fecha_cita (fecha_cita),
          KEY idx_estado (estado)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
      ");
    } else {
      if (!$columnExists('citas', 'titulo')) {
        $pdo->exec("ALTER TABLE citas ADD COLUMN titulo VARCHAR(150) NULL AFTER servicio_id");
      }
      if (!$columnExists('citas', 'area')) {
        $pdo->exec("ALTER TABLE citas ADD COLUMN area ENUM('taller','autolavado','general') NOT NULL DEFAULT 'general' AFTER titulo");
      }
      if (!$columnExists('citas', 'creado_por')) {
        $pdo->exec("ALTER TABLE citas ADD COLUMN creado_por INT UNSIGNED NULL AFTER notas");
      }
    }
  } catch (Throwable $e) { error_log('migrate citas: ' . $e->getMessage()); }

  // ── Activación automática de citas ──────────────────────────────────
  // Una cita agendada no debe llenar el listado de órdenes activas desde
  // el día en que se agenda — solo debe aparecer como orden real (taller
  // o autolavado) el día en que efectivamente toca hacerse. Estas dos
  // columnas guardan el id de la orden que se generó a partir de la cita,
  // para no duplicarla si se activa más de una vez (ver
  // includes/citas_activacion.php).
  try {
    if (!$columnExists('citas', 'orden_trabajo_id')) {
      $pdo->exec("ALTER TABLE citas ADD COLUMN orden_trabajo_id INT UNSIGNED NULL");
    }
    if (!$columnExists('citas', 'autolavado_orden_id')) {
      $pdo->exec("ALTER TABLE citas ADD COLUMN autolavado_orden_id INT UNSIGNED NULL");
    }
  } catch (Throwable $e) { error_log('migrate citas activacion: ' . $e->getMessage()); }
}
