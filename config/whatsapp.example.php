<?php
declare(strict_types=1);

// ============================================================
// Configuración de WhatsApp Cloud API (Meta) para el aviso automático
// de "pedido terminado" (taller y autolavado).
//
// Copia este archivo como config/whatsapp.php y pon tus propios datos.
// config/whatsapp.php NUNCA debe subirse a git (ya está en .gitignore).
//
// Estos datos se obtienen en https://developers.facebook.com
// -> tu App -> WhatsApp -> Configuración de la API (API Setup).
// Guía paso a paso en GUIA_WHATSAPP.md.
// ============================================================

$WHATSAPP_TOKEN = "tu-token-permanente-aqui";
$WHATSAPP_PHONE_ID = "tu-phone-number-id-aqui";
$WHATSAPP_API_VERSION = "v25.0";

// Nombre exacto del template aprobado en WhatsApp Manager.
$WHATSAPP_TEMPLATE_PEDIDO_LISTO = "vehiculo_listo";

// Interruptor general: déjalo en false mientras configuras todo.
$WHATSAPP_ACTIVO = false;
