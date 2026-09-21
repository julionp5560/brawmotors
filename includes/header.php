<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . "/functions.php";

$p = current_page();
$tema = current_tema();
?>
<!doctype html>
<html lang="es" data-tema="<?= e($tema) ?>">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>BRAW MOTORS | <?= e(page_title($p)) ?></title>
  <link rel="stylesheet" href="css/main.css?v=4" />
  <link rel="stylesheet" href="css/dashboard-layout.css?v=5" />
</head>
<body>
<div class="app" id="app">
