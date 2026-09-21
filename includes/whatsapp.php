<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/whatsapp.php';

/**
 * Envía el aviso de "pedido listo" por WhatsApp usando un template
 * aprobado (WhatsApp Cloud API de Meta).
 *
 * Los mensajes que el negocio inicia (el cliente no escribió primero)
 * SIEMPRE deben usar un template aprobado — un mensaje de texto libre
 * solo se puede mandar dentro de las 24h después de que el cliente
 * escribió al negocio.
 *
 * Nunca lanza excepciones hacia afuera: si algo falla regresa
 * ['ok' => false, 'error' => ...] para que jamás rompa el flujo de
 * cambiar el estado de una orden (la orden se actualiza aunque el
 * WhatsApp falle).
 */
function enviar_whatsapp_pedido_listo(?string $telefono, string $nombreCliente, string $referencia): array {
  global $WHATSAPP_TOKEN, $WHATSAPP_PHONE_ID, $WHATSAPP_API_VERSION, $WHATSAPP_TEMPLATE_PEDIDO_LISTO, $WHATSAPP_ACTIVO;

  if (empty($WHATSAPP_ACTIVO)) {
    return ['ok' => false, 'error' => 'WhatsApp desactivado (WHATSAPP_ACTIVO = false en config/whatsapp.php)'];
  }
  if (empty($WHATSAPP_TOKEN) || empty($WHATSAPP_PHONE_ID)) {
    return ['ok' => false, 'error' => 'Faltan credenciales de WhatsApp en config/whatsapp.php'];
  }

  $telefonoApi = normalizar_telefono_mx($telefono);
  if ($telefonoApi === null) {
    whatsapp_log(['error' => 'Teléfono inválido o vacío, no se envió', 'telefono_original' => $telefono]);
    return ['ok' => false, 'error' => 'Teléfono inválido o vacío'];
  }

  $payload = [
    'messaging_product' => 'whatsapp',
    'to' => $telefonoApi,
    'type' => 'template',
    'template' => [
      'name' => $WHATSAPP_TEMPLATE_PEDIDO_LISTO,
      'language' => ['code' => 'es_MX'],
      'components' => [[
        'type' => 'body',
        'parameters' => [
          ['type' => 'text', 'text' => $nombreCliente !== '' ? $nombreCliente : 'cliente'],
          ['type' => 'text', 'text' => $referencia],
        ],
      ]],
    ],
  ];

  $url = "https://graph.facebook.com/{$WHATSAPP_API_VERSION}/{$WHATSAPP_PHONE_ID}/messages";

  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    CURLOPT_HTTPHEADER => [
      'Authorization: Bearer ' . $WHATSAPP_TOKEN,
      'Content-Type: application/json',
    ],
    CURLOPT_TIMEOUT => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
  ]);

  $respuestaRaw = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $curlError = curl_error($ch);
  curl_close($ch);

  $respuesta = json_decode((string)$respuestaRaw, true);

  whatsapp_log([
    'telefono' => $telefonoApi,
    'http_code' => $httpCode,
    'curl_error' => $curlError,
    'respuesta' => $respuesta ?? $respuestaRaw,
  ]);

  if ($curlError !== '') {
    return ['ok' => false, 'error' => 'cURL: ' . $curlError];
  }
  if ($httpCode >= 200 && $httpCode < 300 && isset($respuesta['messages'])) {
    return ['ok' => true, 'data' => $respuesta];
  }

  return ['ok' => false, 'error' => $respuesta['error']['message'] ?? ("HTTP {$httpCode}"), 'raw' => $respuesta];
}

/**
 * Normaliza un teléfono guardado en clientes.telefono (10 dígitos MX,
 * sin código de país, tal como se captura en el formulario de clientes)
 * al formato que espera la API: 52 + 10 dígitos, sin espacios ni signos.
 */
function normalizar_telefono_mx(?string $telefono): ?string {
  if ($telefono === null) return null;
  $digitos = preg_replace('/\D+/', '', $telefono) ?? '';
  if ($digitos === '') return null;

  if (strlen($digitos) === 10) {
    return '52' . $digitos;
  }
  if (strlen($digitos) === 12 && substr($digitos, 0, 2) === '52') {
    return $digitos;
  }
  // Formato viejo con el '1' de móvil (521 + 10 dígitos). Meta ya no lo
  // pide desde 2022 — se normaliza quitándolo.
  if (strlen($digitos) === 13 && substr($digitos, 0, 3) === '521') {
    return '52' . substr($digitos, 3);
  }

  return null;
}

/**
 * Deja un renglón por intento de envío en respaldos/whatsapp.log —
 * útil para revisar por qué no llegó un mensaje sin tener que andar
 * adivinando.
 */
function whatsapp_log(array $info): void {
  $linea = date('Y-m-d H:i:s') . ' ' . json_encode($info, JSON_UNESCAPED_UNICODE) . PHP_EOL;
  @file_put_contents(__DIR__ . '/../respaldos/whatsapp.log', $linea, FILE_APPEND | LOCK_EX);
}
