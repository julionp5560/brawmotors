# Aviso de "pedido listo" por WhatsApp — guía de configuración

Esto usa la **WhatsApp Cloud API oficial de Meta** (gratis hasta cierto volumen
de conversaciones al mes). El código ya está integrado en el sistema; falta
la parte que solo tú puedes hacer en el sitio de Meta: crear la app, verificar
un número y que aprueben la plantilla del mensaje.

## Qué ya quedó hecho en el código

- `config/whatsapp.php` — aquí van tus credenciales (token, Phone Number ID,
  nombre del template). Ahora mismo está vacío y con `$WHATSAPP_ACTIVO = false`,
  así que el sistema sigue funcionando exactamente igual que antes; no manda
  nada hasta que tú lo actives.
- `includes/whatsapp.php` — la función `enviar_whatsapp_pedido_listo()` que
  llama a la API de Meta. Nunca rompe el flujo: si el envío falla (sin
  internet, credenciales mal, lo que sea) solo se registra en
  `respaldos/whatsapp.log` y la orden se actualiza normal.
- **Taller** (`php/api/ordenes/cambiar_estado.php`): cuando una orden pasa a
  `terminado` (y no venía ya de `terminado`), busca el cliente y le manda el
  aviso con su nombre y el folio (ej. `OT-20260909-000010`).
- **Autolavado** (`php/api/autolavado/update_estado.php`): igual, cuando el
  pedido pasa a `terminado`. Como en autolavado el cliente es opcional
  (ventas de mostrador sin registrar), si no hay `cliente_id` simplemente no
  manda nada — no truena.

## Paso 1 — Crear la app en Meta for Developers

1. Entra a https://developers.facebook.com/ e inicia sesión con tu cuenta
   de Facebook (o crea una para el negocio si prefieres separarlo de tu
   cuenta personal).
2. "Mis apps" → "Crear app" → elige el tipo **"Empresa" / "Business"**.
3. Ponle un nombre, por ejemplo `Brawmotors Avisos`.
4. Dentro del panel de la app, busca el producto **WhatsApp** y dale
   "Configurar" / "Set up".

## Paso 2 — Número de prueba y token temporal (para probar ya)

Al entrar a WhatsApp → "Configuración de la API" ("API Setup") vas a ver:

- Un **número de prueba** que Meta te presta gratis (no es el número real
  del negocio todavía).
- Un **Phone Number ID** (un ID numérico, no el teléfono).
- Un **token de acceso temporal** (dura 24 horas, sirve para probar hoy
  mismo sin configurar nada más).
- Una sección para agregar "números destinatarios de prueba" — tienes que
  agregar tu propio WhatsApp ahí y confirmar con el código que te llega,
  para poder recibir mensajes de prueba.

Copia el Phone Number ID y el token a `config/whatsapp.php`:

```php
$WHATSAPP_TOKEN = "el-token-que-copiaste";
$WHATSAPP_PHONE_ID = "el-phone-number-id";
```

## Paso 3 — Crear la plantilla (template) "pedido_listo"

Los mensajes que el negocio manda sin que el cliente haya escrito antes
**tienen que usar una plantilla aprobada por Meta** — no puedes mandar texto
libre para avisar "tu pedido está listo" a menos que el cliente te haya
escrito en las últimas 24h. Por eso hay que crear la plantilla una vez:

1. Ve a **Meta Business Manager → WhatsApp Manager → Administrar plantillas
   de mensaje** (o desde el mismo panel de la app, "Message Templates").
2. "Crear plantilla":
   - **Nombre**: `pedido_listo` (tiene que coincidir EXACTO con
     `$WHATSAPP_TEMPLATE_PEDIDO_LISTO` en `config/whatsapp.php`).
   - **Categoría**: `Utilidad` / `Utility` (es un aviso de servicio, no
     marketing — aprueban más rápido y no cuenta como plantilla de
     marketing).
   - **Idioma**: Español (MX).
   - **Cuerpo del mensaje**, con 2 variables:

     ```
     Hola {{1}}, tu pedido {{2}} en Brawmotors ya está listo. Puedes pasar
     a recogerlo. ¡Gracias por tu confianza!
     ```

   - `{{1}}` = nombre del cliente, `{{2}}` = folio (taller) o
     "tu servicio de autolavado" (autolavado). Así los manda el código.
3. Envíala a revisión. Las de categoría "Utilidad" con texto simple suelen
   aprobarse en minutos u horas, casi nunca más de 24h.

Cuando la plantilla aparezca como **Aprobada**, ya puedes poner
`$WHATSAPP_ACTIVO = true;` en `config/whatsapp.php` y probar cambiando una
orden a "terminado".

## Paso 4 — Pasar a producción (número real del negocio)

El número de prueba solo puede mandarle mensajes a los números que agregaste
como destinatarios de prueba — no le sirve a un cliente cualquiera. Para que
funcione con cualquier cliente real:

1. En "API Setup", cambia el número de prueba por el número real de
   WhatsApp del negocio (un número que NO esté ya usándose en la app normal
   de WhatsApp/WhatsApp Business en un celular, porque al conectarlo a la
   API se desconecta de ahí).
2. Verifica ese número (te llega un código por SMS o llamada).
3. Verifica el negocio en Meta Business Manager (sube algún documento del
   negocio si te lo pide — puede tardar de horas a un par de días).
4. Genera un **token permanente**: Business Settings → Usuarios del
   sistema ("System Users") → crea uno → asígnale la app de WhatsApp →
   genera un token sin fecha de expiración. Reemplaza el token temporal en
   `config/whatsapp.php` con este.

## Revisar si algo no llegó

Cada intento de envío (éxito o error) se guarda en
`respaldos/whatsapp.log`, con el error exacto que regresó Meta si algo
falló (token vencido, plantilla no aprobada, número mal formado, etc.).

## Notas sobre el teléfono guardado en Clientes

`clientes.telefono` se guarda a 10 dígitos (ej. `3411212704`, sin el 52).
El código ya lo normaliza solo, agregando el `52` de México antes de
mandarlo a la API — no necesitas cambiar cómo se captura el teléfono en el
formulario de clientes.
