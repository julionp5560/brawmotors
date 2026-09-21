<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (empty($_SESSION['user'])) {
  // Construir la URL base real de la app comparando la ruta física de este
  // archivo con el DOCUMENT_ROOT, para que funcione sin importar si el
  // proyecto vive en la raíz del servidor o en una subcarpeta (p.ej. /rawmotos/)
  // y sin importar la profundidad del script que disparó el guard.
  $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
  $appRoot = str_replace('\\', '/', dirname(__DIR__)); // carpeta raíz del proyecto
  $baseUrl = '/';
  if ($docRoot !== '' && str_starts_with($appRoot, $docRoot)) {
    $rel = trim(substr($appRoot, strlen($docRoot)), '/');
    $baseUrl = $rel === '' ? '/' : "/{$rel}/";
  }
  header("Location: {$baseUrl}auth/login.php");
  exit;
}
