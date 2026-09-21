<?php
declare(strict_types=1);

/**
 * Rate limiting simple para el login, basado en archivo (sin tocar la BD).
 * Bloquea temporalmente después de varios intentos fallidos seguidos para
 * el mismo usuario/IP, para dificultar ataques de fuerza bruta.
 */

const LOGIN_THROTTLE_MAX_INTENTOS = 5;      // intentos fallidos permitidos
const LOGIN_THROTTLE_VENTANA_SEG  = 900;    // ventana para contar intentos (15 min)
const LOGIN_THROTTLE_BLOQUEO_SEG  = 900;    // cuánto dura el bloqueo (15 min)

function login_throttle_path(): string {
  return __DIR__ . '/../config/.login_attempts.json';
}

function login_throttle_key(string $identity): string {
  $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
  return strtolower(trim($identity)) . '|' . $ip;
}

function login_throttle_load(): array {
  $path = login_throttle_path();
  if (!is_file($path)) return [];
  $raw = @file_get_contents($path);
  if ($raw === false || $raw === '') return [];
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

function login_throttle_save(array $data): void {
  $path = login_throttle_path();
  @file_put_contents($path, json_encode($data), LOCK_EX);
}

/**
 * Devuelve segundos restantes de bloqueo (0 si no está bloqueado).
 */
function login_throttle_bloqueado(string $identity): int {
  $data = login_throttle_load();
  $key = login_throttle_key($identity);
  $now = time();

  if (!isset($data[$key])) return 0;

  $entry = $data[$key];
  $intentos = (int)($entry['intentos'] ?? 0);
  $ultimo   = (int)($entry['ultimo'] ?? 0);
  $bloqueadoHasta = (int)($entry['bloqueado_hasta'] ?? 0);

  if ($bloqueadoHasta > $now) {
    return $bloqueadoHasta - $now;
  }

  // Si ya pasó la ventana de conteo, el registro ya no aplica.
  if ($ultimo > 0 && ($now - $ultimo) > LOGIN_THROTTLE_VENTANA_SEG) {
    return 0;
  }

  return 0;
}

/**
 * Registra un intento fallido. Si se llega al máximo, activa el bloqueo.
 */
function login_throttle_registrar_fallo(string $identity): void {
  $data = login_throttle_load();
  $key = login_throttle_key($identity);
  $now = time();

  $entry = $data[$key] ?? ['intentos' => 0, 'ultimo' => 0, 'bloqueado_hasta' => 0];

  // Si ya pasó la ventana desde el último intento, reiniciamos el conteo.
  if ($entry['ultimo'] > 0 && ($now - (int)$entry['ultimo']) > LOGIN_THROTTLE_VENTANA_SEG) {
    $entry['intentos'] = 0;
  }

  $entry['intentos'] = (int)$entry['intentos'] + 1;
  $entry['ultimo'] = $now;

  if ($entry['intentos'] >= LOGIN_THROTTLE_MAX_INTENTOS) {
    $entry['bloqueado_hasta'] = $now + LOGIN_THROTTLE_BLOQUEO_SEG;
  }

  $data[$key] = $entry;
  login_throttle_save($data);
}

/**
 * Limpia el contador tras un login exitoso.
 */
function login_throttle_limpiar(string $identity): void {
  $data = login_throttle_load();
  $key = login_throttle_key($identity);
  if (isset($data[$key])) {
    unset($data[$key]);
    login_throttle_save($data);
  }
}
