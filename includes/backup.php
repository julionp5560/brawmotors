<?php
declare(strict_types=1);

require_once __DIR__ . '/SimplePdf.php';

/**
 * Carpeta base donde se guardan los respaldos en PDF (ventas y reportes).
 * Configurable en config/db.php ($RESPALDO_CARPETA). Si esa ruta apunta a
 * una carpeta sincronizada con Google Drive Desktop, el respaldo sube solo
 * a la nube — no hace falta ninguna integración con la API de Google.
 */
function respaldo_carpeta_base(): string {
    global $RESPALDO_CARPETA;
    $base = (string)($RESPALDO_CARPETA ?? '');
    if ($base === '') $base = __DIR__ . '/../respaldos';
    return rtrim($base, '/\\');
}

function respaldo_money(float $n): string {
    return '$' . number_format($n, 2, '.', ',');
}

/**
 * Genera un PDF tipo ticket de la venta $ventaId y lo guarda como respaldo
 * en {carpeta}/ventas/{AAAA-MM}/{folio}.pdf
 *
 * Pensada para llamarse justo después de crear una venta (cobrar.php de
 * taller, autolavado y mostrador). Nunca lanza una excepción hacia afuera:
 * si algo falla, solo se registra en el log de PHP — un respaldo caído
 * jamás debe tumbar un cobro real.
 */
function guardar_venta_pdf(PDO $pdo, int $ventaId): void {
    try {
        global $NEGOCIO_NOMBRE, $NEGOCIO_SUCURSAL, $NEGOCIO_DIRECCION, $NEGOCIO_TELEFONO;
        $negocio = (string)($NEGOCIO_NOMBRE ?? 'BRAW MOTORS');
        $sucursal = (string)($NEGOCIO_SUCURSAL ?? 'Punto de venta');
        $direccion = (string)($NEGOCIO_DIRECCION ?? '');
        $telefono = (string)($NEGOCIO_TELEFONO ?? '');

        $st = $pdo->prepare("SELECT v.*, u.nombre_completo AS cajero_nombre
                             FROM ventas v
                             LEFT JOIN usuarios u ON u.id = v.usuario_id
                             WHERE v.id = ? LIMIT 1");
        $st->execute([$ventaId]);
        $venta = $st->fetch(PDO::FETCH_ASSOC);
        if (!$venta) return;

        $dt = $pdo->prepare("SELECT descripcion, cantidad, precio_unitario, importe
                             FROM venta_detalle WHERE venta_id = ? ORDER BY id ASC");
        $dt->execute([$ventaId]);
        $items = $dt->fetchAll(PDO::FETCH_ASSOC);

        $pdf = new SimplePdf();
        $pdf->addPage(80, 200, 8);
        $pdf->lineHeight = 5;

        $pdf->setFont('CB', 13);
        $pdf->writeLine($negocio, 'center');
        $pdf->setFont('C', 9);
        if ($sucursal !== '') $pdf->writeLine($sucursal, 'center');
        if ($direccion !== '') $pdf->writeLine($direccion, 'center');
        if ($telefono !== '' && $telefono !== '---') $pdf->writeLine($telefono, 'center');
        $pdf->hr();

        $pdf->setFont('C', 8.5);
        $pdf->row('Folio:', (string)$venta['folio']);
        $pdf->row('Fecha:', (string)$venta['fecha']);
        $pdf->row('Cajero:', (string)($venta['cajero_nombre'] ?? ''));
        $pdf->row('Cliente:', (string)$venta['cliente_nombre']);
        $pdf->row('Pago:', (string)$venta['metodo_pago']);
        $pdf->row('Estado:', (string)$venta['estado']);
        $pdf->hr();

        $pdf->setFont('CB', 8.5);
        $pdf->cols([['Descripción', 0, 'left'], ['Importe', 64, 'right']]);
        $pdf->hr(false);

        $pdf->setFont('C', 8.5);
        // La columna "Importe" arranca en xOffset=64 (todo el ancho útil) y
        // está alineada a la derecha, así que su ancho real cambia según el
        // monto ("$50.00" no es igual de ancho que "$1,250.00"). Hay que
        // calcular cuánto le queda disponible a la descripción en cada fila
        // — un ancho fijo optimista es justo lo que causaba el traslape.
        foreach ($items as $it) {
            $desc = (string)$it['descripcion'];
            $qty = (float)$it['cantidad'];
            $price = (float)$it['precio_unitario'];
            $lineTotal = (float)$it['importe'];

            $importeTxt = respaldo_money($lineTotal);
            $anchoImporte = $pdf->textWidthMm($importeTxt, 8.5);
            $anchoDesc = max(15, 64 - $anchoImporte - 3); // 3mm de respiro

            $descLineas = $pdf->wrapText($desc, $anchoDesc, 8.5);
            $pdf->cols([[$descLineas[0], 0, 'left'], [$importeTxt, 64, 'right']]);
            for ($i = 1; $i < count($descLineas); $i++) {
                $pdf->cols([[$descLineas[$i], 0, 'left']]);
            }

            $pdf->setFont('C', 7.5);
            $pdf->cols([[number_format($qty, 2) . ' x ' . respaldo_money($price), 0, 'left']]);
            $pdf->setFont('C', 8.5);
        }
        $pdf->hr();

        $pdf->row('Subtotal:', respaldo_money((float)$venta['subtotal']));
        $pdf->row('IVA (' . number_format((float)$venta['iva_rate'], 2) . '%):', respaldo_money((float)$venta['iva']));
        $pdf->setFont('CB', 10);
        $pdf->row('TOTAL:', respaldo_money((float)$venta['total']));

        if (!empty($venta['notas'])) {
            $pdf->setFont('C', 8);
            $pdf->hr();
            $pdf->writeLine('Notas: ' . (string)$venta['notas']);
        }

        $mes = date('Y-m', strtotime((string)$venta['fecha']) ?: time());
        $folio = preg_replace('/[^A-Za-z0-9\-_]/', '_', (string)$venta['folio']);
        $dir = respaldo_carpeta_base() . '/ventas/' . $mes;
        $pdf->save($dir . '/' . $folio . '.pdf');
    } catch (Throwable $e) {
        error_log('guardar_venta_pdf: ' . $e->getMessage());
    }
}
