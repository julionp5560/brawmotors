# Brawmotors — Sistema de gestión para taller, autolavado y refaccionaria

Aplicación web hecha a medida para un negocio real (taller de motos,
autolavado y venta de refacciones), construida con PHP + MySQL + JS puro.
Corre en escritorio y en una vista móvil optimizada para el piso de trabajo.

## Funcionalidad principal

- **Órdenes de taller**: recepción de vehículo, diagnóstico, servicios y
  refacciones por orden, seguimiento de estado (recibido → en proceso →
  terminado → entregado), folio automático, cobro independiente del flujo
  de trabajo.
- **Autolavado**: módulo POS para pantalla táctil, servicios con precio
  fijo, rango o personalizado (a cotizar), impresión de tickets en
  impresora térmica (ESC/POS).
- **Agenda de citas con activación automática**: las citas se agendan con
  anticipación pero permanecen ocultas del listado de órdenes activas
  hasta el día en que realmente toca atenderlas — ese día se convierten
  solas en una orden real ("Recibido"/"Pendiente"), sin intervención
  manual y sin duplicarse aunque el listado se recargue varias veces.
- **Clientes y vehículos**: registro y consulta rápida por placas/teléfono.
- **Notificaciones automáticas por WhatsApp**: al marcar un pedido como
  terminado, el cliente recibe un mensaje de WhatsApp real (WhatsApp Cloud
  API de Meta, con plantilla aprobada) avisando que su vehículo está listo.
- **Roles de usuario**: Admin, Caja, Mecánico y Lavador, cada uno con
  permisos distintos (por ejemplo, un operario nunca ve ni maneja precios).
- **Migraciones automáticas**: el esquema de base de datos se crea y
  actualiza solo la primera vez que corre la app — no hace falta importar
  ningún `.sql` a mano.

## Stack

PHP 8 (vanilla, sin framework) · MySQL/MariaDB · JavaScript vanilla ·
PDO con prepared statements · XAMPP para desarrollo local.

## Cómo correrlo localmente

1. Clona el repo dentro de `htdocs` de tu XAMPP (o cualquier servidor con
   PHP 8+ y MySQL).
2. Crea una base de datos vacía en MySQL.
3. Copia `config/db.example.php` → `config/db.php` y pon los datos de tu
   base de datos. Al abrir la app por primera vez, las tablas se crean
   solas.
4. (Opcional, para notificaciones de WhatsApp) Copia
   `config/whatsapp.example.php` → `config/whatsapp.php` y sigue la guía
   en `GUIA_WHATSAPP.md` para conectarlo a WhatsApp Cloud API.
5. Abre `index.php` en el navegador.

## Nota

Este proyecto se desarrolló para un cliente real; este repo es una versión
limpia para portafolio — sin credenciales, sin respaldos ni datos de
clientes reales.
