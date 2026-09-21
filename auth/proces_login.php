<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/login_throttle.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: login.php");
  exit;
}

$csrf = $_POST['csrf'] ?? '';
if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrf)) {
  $_SESSION['login_error'] = "Sesión inválida. Recarga e intenta de nuevo.";
  header("Location: login.php");
  exit;
}

$identity = trim((string)($_POST['identity'] ?? ''));
$password = (string)($_POST['password'] ?? '');

if ($identity === '' || $password === '') {
  $_SESSION['login_error'] = "Completa usuario y contraseña.";
  header("Location: login.php");
  exit;
}

$segundosBloqueado = login_throttle_bloqueado($identity);
if ($segundosBloqueado > 0) {
  $minutos = (int)ceil($segundosBloqueado / 60);
  $_SESSION['login_error'] = "Demasiados intentos fallidos. Intenta de nuevo en {$minutos} minuto(s).";
  header("Location: login.php");
  exit;
}

try {
  $pdo = db();

 $sql = "SELECT u.id, u.nombre_completo, u.usuario, u.email, u.PASSWORD, u.rol_id, u.activo, u.tema,
               r.nombre AS rol_nombre
        FROM usuarios u
        LEFT JOIN roles r ON r.id = u.rol_id
        WHERE (u.usuario = ? OR u.email = ?)
        LIMIT 1";

$st = $pdo->prepare($sql);
$st->execute([$identity, $identity]);
$user = $st->fetch();


  if (!$user || (int)$user['activo'] !== 1) {
    login_throttle_registrar_fallo($identity);
    $_SESSION['login_error'] = "Credenciales inválidas o usuario inactivo.";
    header("Location: login.php");
    exit;
  }

  if (!password_verify($password, (string)$user['PASSWORD'])) {
    login_throttle_registrar_fallo($identity);
    $_SESSION['login_error'] = "Credenciales inválidas.";
    header("Location: login.php");
    exit;
  }

  login_throttle_limpiar($identity);
  session_regenerate_id(true);

  $_SESSION['user'] = [
    'id' => (int)$user['id'],
    'nombre' => (string)$user['nombre_completo'],
    'usuario' => (string)$user['usuario'],
    'email' => (string)$user['email'],
    'rol_id' => (int)$user['rol_id'],
    'rol_nombre' => (string)($user['rol_nombre'] ?? ''),
    'tema' => ((string)($user['tema'] ?? 'oscuro')) === 'alto_contraste' ? 'alto_contraste' : 'oscuro',
  ];

  $up = $pdo->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id");
  $up->execute(['id' => (int)$user['id']]);

  // Roles "Mecánico" y "Lavador": van directo a la app móvil (sin dinero),
  // cada uno a su propio módulo (taller / autolavado), no al dashboard
  // completo de escritorio.
  if (in_array($_SESSION['user']['rol_nombre'], ['Mecánico', 'Lavador'], true)) {
    header("Location: ../movil/index.php");
    exit;
  }

  header("Location: ../dashboard.php");
  exit;

} catch (Throwable $e) {
  $_SESSION['login_error'] = "Error del servidor. Intenta más tarde.";
  header("Location: login.php");
  exit;
}



