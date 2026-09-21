<?php
declare(strict_types=1);

/**
 * SimplePdf — generador de PDF mínimo, sin dependencias externas.
 *
 * No hay Composer configurado en este XAMPP, así que en vez de instalar una
 * librería (FPDF, TCPDF, dompdf...) esta clase escribe directamente el
 * formato PDF a mano: un catálogo, un árbol de páginas, y por cada página un
 * content stream con texto (operadores BT/Tf/Td/Tj) y líneas (m/l/S).
 *
 * Usa únicamente las fuentes estándar Courier y Courier-Bold (no requieren
 * embeber nada) con /Encoding /WinAnsiEncoding, que sí soporta acentos y
 * eñes en español.
 *
 * Pensada para dos casos de uso:
 *  - Recibos/tickets de una sola "página" de alto variable (se calcula solo
 *    según cuánto contenido se agregó).
 *  - Reportes de tamaño fijo (Carta/A4) con salto de página automático.
 *
 * Coordenadas: todos los métodos públicos reciben X/Y en milímetros,
 * medidos desde la esquina SUPERIOR IZQUIERDA de la página (más intuitivo
 * para ir "escribiendo hacia abajo"). Internamente se convierte al sistema
 * de PDF (puntos, origen inferior izquierdo).
 */
class SimplePdf {
    private const MM2PT = 2.834645669; // 1mm en puntos PDF (1pt = 1/72in)

    /** @var array<int,string> objetos PDF ya serializados (índice = id-1) */
    private array $objects = [];

    /** @var array<int,array{contentObjId:int,w:float,h:float}> */
    private array $pages = [];

    private string $buf = '';           // content stream de la página actual
    private float $pageW = 0;           // ancho de la página actual (pt)
    private float $pageH = 0;           // alto de la página actual (pt)
    private bool $hasOpenPage = false;

    private string $font = 'C';         // 'C' = Courier, 'CB' = Courier-Bold
    private float $fontSize = 9;

    // Cursor para el modo "escritura en flujo" (writeLine/hr/etc.)
    public float $x = 0;
    public float $y = 0;
    public float $marginLeft = 0;
    public float $marginRight = 0;
    public float $marginTop = 0;
    public float $marginBottom = 0;
    public float $pageWmm = 0;
    public float $pageHmm = 0;
    public float $lineHeight = 4.6; // mm, a tamaño de fuente ~9pt

    private int $fontCourierId = 0;
    private int $fontCourierBoldId = 0;

    public function __construct() {
        // Objeto 1 = Catalog, 2 = Pages (se completan al final en output()).
        $this->objects[0] = ''; // placeholder Catalog
        $this->objects[1] = ''; // placeholder Pages
        $this->fontCourierId = $this->addObject(
            "<< /Type /Font /Subtype /Type1 /BaseFont /Courier /Encoding /WinAnsiEncoding >>"
        );
        $this->fontCourierBoldId = $this->addObject(
            "<< /Type /Font /Subtype /Type1 /BaseFont /Courier-Bold /Encoding /WinAnsiEncoding >>"
        );
    }

    private function addObject(string $body): int {
        $this->objects[] = $body;
        return count($this->objects); // 1-based id
    }

    /** Inicia una página nueva de tamaño fijo (mm). Cierra la anterior si había una abierta. */
    public function addPage(float $widthMm, float $heightMm, float $marginMm = 10): void {
        $this->flushPage();
        $this->pageW = $widthMm * self::MM2PT;
        $this->pageH = $heightMm * self::MM2PT;
        $this->pageWmm = $widthMm;
        $this->pageHmm = $heightMm;
        $this->marginLeft = $marginMm;
        $this->marginRight = $marginMm;
        $this->marginTop = $marginMm;
        $this->marginBottom = $marginMm;
        $this->x = $this->marginLeft;
        $this->y = $this->marginTop;
        $this->buf = '';
        $this->hasOpenPage = true;
    }

    private function flushPage(): void {
        if (!$this->hasOpenPage) return;
        $contentObjId = $this->addObject($this->streamObject($this->buf));
        $this->pages[] = ['contentObjId' => $contentObjId, 'w' => $this->pageW, 'h' => $this->pageH];
        $this->hasOpenPage = false;
        $this->buf = '';
    }

    private function streamObject(string $raw): string {
        $len = strlen($raw);
        return "<< /Length {$len} >>\nstream\n{$raw}\nendstream";
    }

    public function setFont(string $font = 'C', ?float $size = null): void {
        $this->font = $font === 'CB' ? 'CB' : 'C';
        if ($size !== null) $this->fontSize = $size;
    }

    // ── Conversión de coordenadas (mm desde arriba-izquierda -> pt PDF) ──
    private function px(float $xMm): float { return $xMm * self::MM2PT; }
    private function py(float $yMm): float { return $this->pageH - ($yMm * self::MM2PT); }

    private static function esc(string $s): string {
        // WinAnsiEncoding (~= CP1252) sí soporta acentos/eñes en español.
        $conv = @iconv('UTF-8', 'CP1252//TRANSLIT', $s);
        if ($conv === false) $conv = $s;
        $conv = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $conv);
        return $conv;
    }

    private function fontRes(string $font): string { return $font === 'CB' ? '/F2' : '/F1'; }

    /** Ancho aproximado del texto en mm (Courier es monoespaciada: 0.6em por char). */
    public function textWidthMm(string $s, ?float $size = null): float {
        $size = $size ?? $this->fontSize;
        $ptWidth = mb_strlen($s) * 0.6 * $size;
        return $ptWidth / self::MM2PT;
    }

    /**
     * Parte un texto largo en varias líneas para que quepa en $maxWidthMm
     * (word-wrap por espacios; si una sola palabra ya es más larga que el
     * ancho disponible, la corta a la fuerza). Devuelve un array de líneas.
     */
    public function wrapText(string $s, float $maxWidthMm, ?float $size = null): array {
        $size = $size ?? $this->fontSize;
        $charW = (0.6 * $size) / self::MM2PT;
        $maxChars = max(1, (int)floor($maxWidthMm / $charW));

        $lines = [];
        foreach (explode("\n", $s) as $paragraph) {
            $words = preg_split('/\s+/', trim($paragraph)) ?: [];
            $cur = '';
            foreach ($words as $word) {
                if ($word === '') continue;
                while (mb_strlen($word) > $maxChars) {
                    // Palabra más larga que una línea completa: se corta a la fuerza.
                    if ($cur !== '') { $lines[] = $cur; $cur = ''; }
                    $lines[] = mb_substr($word, 0, $maxChars);
                    $word = mb_substr($word, $maxChars);
                }
                $tentative = $cur === '' ? $word : ($cur . ' ' . $word);
                if (mb_strlen($tentative) > $maxChars) {
                    if ($cur !== '') $lines[] = $cur;
                    $cur = $word;
                } else {
                    $cur = $tentative;
                }
            }
            if ($cur !== '') $lines[] = $cur;
            if ($paragraph === '') $lines[] = '';
        }
        return $lines ?: [''];
    }

    /**
     * Dibuja una línea de texto en (xMm, yMm) — yMm es la línea BASE del
     * texto (como Td en PDF), no la esquina superior.
     */
    public function text(float $xMm, float $yMm, string $s, ?string $font = null, ?float $size = null): void {
        if (!$this->hasOpenPage) return;
        $font = $font ?? $this->font;
        $size = $size ?? $this->fontSize;
        $x = $this->px($xMm);
        $y = $this->py($yMm);
        $t = self::esc($s);
        $this->buf .= sprintf("BT %s %.2F Tf %.2F %.2F Td (%s) Tj ET\n", $this->fontRes($font), $size, $x, $y, $t);
    }

    public function line(float $x1Mm, float $y1Mm, float $x2Mm, float $y2Mm, bool $dashed = false, float $widthPt = 0.6): void {
        if (!$this->hasOpenPage) return;
        $x1 = $this->px($x1Mm); $y1 = $this->py($y1Mm);
        $x2 = $this->px($x2Mm); $y2 = $this->py($y2Mm);
        $dash = $dashed ? '[2 1.5] 0 d' : '[] 0 d';
        $this->buf .= sprintf("q %s %.2F w %.2F %.2F m %.2F %.2F l S Q\n", $dash, $widthPt, $x1, $y1, $x2, $y2);
    }

    // ── API de "flujo" (cursor que avanza hacia abajo, con salto de página) ──

    /** Asegura que quepa una línea más; si no, abre una página nueva igual a la actual. */
    private function ensureSpace(float $needMm): void {
        if ($this->y + $needMm > $this->pageHmm - $this->marginBottom) {
            $this->addPage($this->pageWmm, $this->pageHmm, $this->marginLeft);
        }
    }

    /**
     * Escribe una línea en el cursor actual y avanza. align: left|center|right.
     * Devuelve el número de líneas usadas (soporta saltos de línea internos \n).
     */
    public function writeLine(string $s, string $align = 'left', ?string $font = null, ?float $size = null): void {
        $font = $font ?? $this->font;
        $size = $size ?? $this->fontSize;
        $lines = explode("\n", $s);
        foreach ($lines as $line) {
            $this->ensureSpace($this->lineHeight);
            $usableW = $this->pageWmm - $this->marginLeft - $this->marginRight;
            $tw = $this->textWidthMm($line, $size);
            $x = $this->marginLeft;
            if ($align === 'center') $x = $this->marginLeft + max(0, ($usableW - $tw) / 2);
            elseif ($align === 'right') $x = $this->marginLeft + max(0, $usableW - $tw);
            $this->text($x, $this->y + $this->lineHeight * 0.72, $line, $font, $size);
            $this->y += $this->lineHeight;
        }
    }

    /** Línea horizontal (separador) a todo el ancho útil de la página. */
    public function hr(bool $dashed = true): void {
        $this->ensureSpace(2);
        $this->y += 1.5;
        $this->line($this->marginLeft, $this->y, $this->pageWmm - $this->marginRight, $this->y, $dashed);
        $this->y += 1.8;
    }

    public function skip(float $mm): void {
        $this->ensureSpace($mm);
        $this->y += $mm;
    }

    /**
     * Fila de dos columnas (etiqueta a la izquierda, valor a la derecha) —
     * útil para "Folio: TAL-0001", "Total: $450.00", etc.
     */
    public function row(string $left, string $right, ?float $size = null): void {
        $size = $size ?? $this->fontSize;
        $this->ensureSpace($this->lineHeight);
        $usableW = $this->pageWmm - $this->marginLeft - $this->marginRight;
        $rw = $this->textWidthMm($right, $size);
        $ySeg = $this->y + $this->lineHeight * 0.72;
        $this->text($this->marginLeft, $ySeg, $left, $this->font, $size);
        $this->text($this->marginLeft + max(0, $usableW - $rw), $ySeg, $right, $this->font, $size);
        $this->y += $this->lineHeight;
    }

    /**
     * Fila de tabla con columnas en posiciones X fijas (mm desde el margen
     * izquierdo). $cols = [[texto, xOffsetMm, align], ...]
     */
    public function cols(array $cols, ?float $size = null, ?string $font = null): void {
        $size = $size ?? $this->fontSize;
        $font = $font ?? $this->font;
        $this->ensureSpace($this->lineHeight);
        $ySeg = $this->y + $this->lineHeight * 0.72;
        foreach ($cols as $c) {
            [$txt, $xOff, $align] = [$c[0], $c[1], $c[2] ?? 'left'];
            $x = $this->marginLeft + $xOff;
            if ($align !== 'left') {
                $tw = $this->textWidthMm((string)$txt, $size);
                if ($align === 'right') $x = $this->marginLeft + $xOff - $tw;
                elseif ($align === 'center') $x = $this->marginLeft + $xOff - $tw / 2;
            }
            $this->text($x, $ySeg, (string)$txt, $font, $size);
        }
        $this->y += $this->lineHeight;
    }

    // ── Salida ──

    /** Devuelve los bytes del PDF ya armado (cierra la página abierta). */
    public function output(): string {
        $this->flushPage();

        // Página(s)
        $kids = [];
        foreach ($this->pages as $p) {
            $pageObjId = $this->addObject(
                "<< /Type /Page /Parent 2 0 R" .
                " /MediaBox [0 0 " . sprintf('%.2F', $p['w']) . " " . sprintf('%.2F', $p['h']) . "]" .
                " /Resources << /Font << /F1 {$this->fontCourierId} 0 R /F2 {$this->fontCourierBoldId} 0 R >> >>" .
                " /Contents {$p['contentObjId']} 0 R >>"
            );
            $kids[] = "{$pageObjId} 0 R";
        }

        $this->objects[0] = "<< /Type /Catalog /Pages 2 0 R >>";
        $this->objects[1] = "<< /Type /Pages /Kids [" . implode(' ', $kids) . "] /Count " . count($kids) . " >>";

        $out = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($this->objects as $i => $body) {
            $id = $i + 1;
            $offsets[$id] = strlen($out);
            $out .= "{$id} 0 obj\n{$body}\nendobj\n";
        }

        $xrefStart = strlen($out);
        $n = count($this->objects) + 1;
        $out .= "xref\n0 {$n}\n";
        $out .= "0000000000 65535 f \n";
        for ($id = 1; $id < $n; $id++) {
            $out .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }
        $out .= "trailer\n<< /Size {$n} /Root 1 0 R >>\nstartxref\n{$xrefStart}\n%%EOF";

        return $out;
    }

    /** Guarda el PDF en disco, creando carpetas intermedias si hace falta. */
    public function save(string $path): bool {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0775, true) && !is_dir($dir)) return false;
        }
        return @file_put_contents($path, $this->output()) !== false;
    }

    /** Envía el PDF como descarga HTTP y termina la ejecución. */
    public function download(string $filename): void {
        $bytes = $this->output();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
        header('Content-Length: ' . strlen($bytes));
        echo $bytes;
        exit;
    }
}
