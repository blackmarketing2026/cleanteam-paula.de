<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/contract_template.php';

final class SimplePdfDocument
{
    private const PAGE_WIDTH = 595.28;
    private const PAGE_HEIGHT = 841.89;
    private const MARGIN_LEFT = 52.0;
    private const MARGIN_RIGHT = 52.0;
    private const MARGIN_TOP = 52.0;
    private const MARGIN_BOTTOM = 54.0;

    private array $pages = [];
    private int $pageIndex = -1;
    private array $images = [];
    private float $y = 0.0;

    public function __construct()
    {
        $this->addPage();
    }

    public function addPage(): void
    {
        $this->pages[] = ['content' => '', 'images' => []];
        $this->pageIndex = count($this->pages) - 1;
        $this->y = self::PAGE_HEIGHT - self::MARGIN_TOP;
    }

    public function spacer(float $height = 8.0): void
    {
        $this->ensureSpace($height);
        $this->y -= $height;
    }

    public function title(string $text): void
    {
        $this->ensureSpace(34.0);
        $this->line($text, self::MARGIN_LEFT, $this->y, 18.0, 'F2');
        $this->y -= 25.0;
    }

    public function label(string $text): void
    {
        $this->ensureSpace(18.0);
        $this->line($text, self::MARGIN_LEFT, $this->y, 9.0, 'F2');
        $this->y -= 16.0;
    }

    public function meta(string $text): void
    {
        $this->ensureSpace(16.0);
        $this->line($text, self::MARGIN_LEFT, $this->y, 9.0, 'F1');
        $this->y -= 18.0;
    }

    public function heading(string $text): void
    {
        $this->ensureSpace(28.0);
        $this->y -= 8.0;
        $this->line($text, self::MARGIN_LEFT, $this->y, 13.0, 'F2');
        $this->drawLine(self::MARGIN_LEFT, $this->y - 5.0, self::PAGE_WIDTH - self::MARGIN_RIGHT, $this->y - 5.0);
        $this->y -= 18.0;
    }

    public function subheading(string $text): void
    {
        $this->ensureSpace(22.0);
        $this->y -= 4.0;
        $this->line($text, self::MARGIN_LEFT, $this->y, 11.0, 'F2');
        $this->y -= 14.0;
    }

    public function paragraph(string $text, float $fontSize = 10.5, float $indent = 0.0): void
    {
        $availableWidth = self::PAGE_WIDTH - self::MARGIN_LEFT - self::MARGIN_RIGHT - $indent;
        $lines = $this->wrap($this->normalizeWhitespace($text), $fontSize, $availableWidth);
        foreach ($lines as $line) {
            $this->ensureSpace($fontSize + 4.5);
            $this->line($line, self::MARGIN_LEFT + $indent, $this->y, $fontSize, 'F1');
            $this->y -= $fontSize + 4.5;
        }
        $this->y -= 4.0;
    }

    public function bulletList(array $items): void
    {
        foreach ($items as $item) {
            $this->listItem((string) $item, '-');
        }
        $this->y -= 3.0;
    }

    public function numberedList(array $items): void
    {
        $number = 1;
        foreach ($items as $item) {
            $this->listItem((string) $item, (string) $number . '.');
            $number++;
        }
        $this->y -= 3.0;
    }

    public function keyValue(string $key, string $value): void
    {
        $this->ensureSpace(18.0);
        $this->line($key, self::MARGIN_LEFT, $this->y, 9.5, 'F2');
        $lines = $this->wrap($this->normalizeWhitespace($value), 9.5, 320.0);
        if ($lines === []) {
            $lines = ['-'];
        }

        $first = true;
        foreach ($lines as $line) {
            if (!$first) {
                $this->ensureSpace(13.5);
            }
            $this->line($line, self::MARGIN_LEFT + 170.0, $this->y, 9.5, 'F1');
            $this->y -= 13.5;
            $first = false;
        }
    }

    public function checklistItem(string $text): void
    {
        $fontSize = 9.2;
        $lineHeight = 12.5;
        $boxSize = 8.0;
        $employeeBoxX = self::PAGE_WIDTH - self::MARGIN_RIGHT - 76.0;
        $customerBoxX = self::PAGE_WIDTH - self::MARGIN_RIGHT - 30.0;
        $textWidth = $employeeBoxX - self::MARGIN_LEFT - 12.0;
        $lines = $this->wrap($this->normalizeWhitespace($text), $fontSize, $textWidth);
        if ($lines === []) {
            return;
        }

        $height = max(count($lines) * $lineHeight, 16.0);
        $this->ensureSpace($height + 4.0);
        $startY = $this->y;
        foreach ($lines as $index => $line) {
            $this->line($line, self::MARGIN_LEFT, $startY - ($index * $lineHeight), $fontSize, 'F1');
        }

        $boxY = $startY - 8.0;
        $this->drawBox($employeeBoxX, $boxY, $boxSize);
        $this->drawBox($customerBoxX, $boxY, $boxSize);
        $this->y -= $height + 4.0;
    }

    public function checklistColumns(string $left = 'Mitarbeiter', string $right = 'Endkunde'): void
    {
        $this->ensureSpace(18.0);
        $this->line($left, self::PAGE_WIDTH - self::MARGIN_RIGHT - 92.0, $this->y, 8.0, 'F2');
        $this->line($right, self::PAGE_WIDTH - self::MARGIN_RIGHT - 44.0, $this->y, 8.0, 'F2');
        $this->y -= 12.0;
    }

    public function protocolKeyValue(string $key, string $value): void
    {
        $fontSize = 9.5;
        $lineHeight = 15.0;
        $keyWidth = 205.0;
        $valueX = self::MARGIN_LEFT + 220.0;
        $valueWidth = self::PAGE_WIDTH - self::MARGIN_RIGHT - $valueX;
        $keyLines = $this->wrap($this->normalizeWhitespace($key), $fontSize, $keyWidth);
        $valueLines = $this->wrap($this->normalizeWhitespace($value), $fontSize, $valueWidth);

        if ($keyLines === []) {
            $keyLines = ['-'];
        }
        if ($valueLines === []) {
            $valueLines = ['-'];
        }

        $lineCount = max(count($keyLines), count($valueLines));
        $this->ensureSpace(($lineCount * $lineHeight) + 5.0);

        for ($index = 0; $index < $lineCount; $index++) {
            $lineY = $this->y - ($index * $lineHeight);
            if (isset($keyLines[$index])) {
                $this->line($keyLines[$index], self::MARGIN_LEFT, $lineY, $fontSize, 'F2');
            }
            if (isset($valueLines[$index])) {
                $this->line($valueLines[$index], $valueX, $lineY, $fontSize, 'F1');
            }
        }

        $this->y -= ($lineCount * $lineHeight) + 3.0;
    }

    public function signatureImage(?string $dataUrl): bool
    {
        $image = $this->parsePngDataUrl($dataUrl);
        if ($image === null) {
            return false;
        }

        $maxWidth = 180.0;
        $maxHeight = 62.0;
        $ratio = min($maxWidth / $image['width'], $maxHeight / $image['height'], 1.0);
        $width = max(1.0, $image['width'] * $ratio);
        $height = max(1.0, $image['height'] * $ratio);

        $this->ensureSpace($height + 8.0);
        $name = 'Im' . (count($this->images) + 1);
        $this->images[$name] = $image;
        $this->pages[$this->pageIndex]['images'][$name] = true;

        $x = self::MARGIN_LEFT + 246.0;
        $y = $this->y - $height;
        $this->write(sprintf(
            "q %.2F 0 0 %.2F %.2F %.2F cm /%s Do Q\n",
            $width,
            $height,
            $x,
            $y,
            $name
        ));
        $this->y -= $height + 8.0;

        return true;
    }

    public function output(): string
    {
        $objectCount = 4;
        $imageObjects = [];
        foreach (array_keys($this->images) as $name) {
            $objectCount++;
            $imageObjects[$name] = $objectCount;
        }

        $pageObjects = [];
        $contentObjects = [];
        foreach ($this->pages as $index => $_page) {
            $objectCount++;
            $contentObjects[$index] = $objectCount;
            $objectCount++;
            $pageObjects[$index] = $objectCount;
        }

        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        foreach ($this->images as $name => $image) {
            $compressed = gzcompress($image['rgb']);
            $objects[$imageObjects[$name]] = "<< /Type /XObject /Subtype /Image /Width {$image['width']} /Height {$image['height']} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /FlateDecode /Length " . strlen($compressed) . " >>\nstream\n" . $compressed . "\nendstream";
        }

        $kids = [];
        $pageTotal = count($this->pages);
        foreach ($this->pages as $index => $page) {
            $content = $page['content'] . $this->footer($index + 1, $pageTotal);
            $objects[$contentObjects[$index]] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "endstream";

            $xObjects = '';
            foreach (array_keys($page['images']) as $imageName) {
                $xObjects .= '/' . $imageName . ' ' . $imageObjects[$imageName] . ' 0 R ';
            }
            $xObjectResource = $xObjects !== '' ? ' /XObject << ' . $xObjects . '>>' : '';
            $objects[$pageObjects[$index]] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] /Resources << /Font << /F1 3 0 R /F2 4 0 R >>' . $xObjectResource . ' >> /Contents ' . $contentObjects[$index] . ' 0 R >>';
            $kids[] = $pageObjects[$index] . ' 0 R';
        }
        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';

        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($id = 1; $id <= count($objects); $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id] ?? 0);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    private function listItem(string $text, string $marker): void
    {
        $fontSize = 9.5;
        $lines = $this->wrap($this->normalizeWhitespace($text), $fontSize, self::PAGE_WIDTH - self::MARGIN_LEFT - self::MARGIN_RIGHT - 26.0);
        $first = true;
        foreach ($lines as $line) {
            $this->ensureSpace(13.5);
            if ($first) {
                $this->line($marker, self::MARGIN_LEFT, $this->y, $fontSize, 'F1');
            }
            $this->line($line, self::MARGIN_LEFT + 18.0, $this->y, $fontSize, 'F1');
            $this->y -= 13.5;
            $first = false;
        }
    }

    private function ensureSpace(float $height): void
    {
        if ($this->y - $height < self::MARGIN_BOTTOM) {
            $this->addPage();
        }
    }

    private function line(string $text, float $x, float $y, float $fontSize, string $font): void
    {
        $this->write(sprintf(
            "BT /%s %.2F Tf %.2F %.2F Td (%s) Tj ET\n",
            $font,
            $fontSize,
            $x,
            $y,
            $this->escapeText($text)
        ));
    }

    private function drawLine(float $x1, float $y1, float $x2, float $y2): void
    {
        $this->write(sprintf("%.2F %.2F m %.2F %.2F l S\n", $x1, $y1, $x2, $y2));
    }

    private function drawBox(float $x, float $y, float $size): void
    {
        $this->drawLine($x, $y, $x + $size, $y);
        $this->drawLine($x + $size, $y, $x + $size, $y - $size);
        $this->drawLine($x + $size, $y - $size, $x, $y - $size);
        $this->drawLine($x, $y - $size, $x, $y);
    }

    private function write(string $content): void
    {
        $this->pages[$this->pageIndex]['content'] .= $content;
    }

    private function footer(int $page, int $total): string
    {
        $text = 'Seite ' . $page . ' von ' . $total;
        return sprintf(
            "BT /F1 8 Tf %.2F 26.00 Td (%s) Tj ET\n",
            self::PAGE_WIDTH / 2 - 24.0,
            $this->escapeText($text)
        );
    }

    private function wrap(string $text, float $fontSize, float $width): array
    {
        $maxChars = max(24, (int) floor($width / ($fontSize * 0.48)));
        $words = preg_split('/\s+/u', $text) ?: [];
        $lines = [];
        $line = '';

        foreach ($words as $word) {
            if ($word === '') {
                continue;
            }

            $candidate = $line === '' ? $word : $line . ' ' . $word;
            if ($this->textLength($candidate) <= $maxChars) {
                $line = $candidate;
                continue;
            }

            if ($line !== '') {
                $lines[] = $line;
            }
            $line = $word;
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines;
    }

    private function textLength(string $text): int
    {
        return function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
    }

    private function normalizeWhitespace(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    private function escapeText(string $text): string
    {
        $text = str_replace(["\r", "\n"], ' ', $text);
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $text);
            if ($converted !== false) {
                $text = $converted;
            }
        }

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function parsePngDataUrl(?string $dataUrl): ?array
    {
        if ($dataUrl === null || strpos($dataUrl, 'data:image/png;base64,') !== 0) {
            return null;
        }

        $binary = base64_decode(substr($dataUrl, 22), true);
        if ($binary === false || substr($binary, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            return null;
        }

        $offset = 8;
        $width = 0;
        $height = 0;
        $bitDepth = 0;
        $colorType = 0;
        $interlace = 0;
        $idat = '';
        $length = strlen($binary);

        while ($offset + 8 <= $length) {
            $chunkLength = unpack('N', substr($binary, $offset, 4))[1];
            $type = substr($binary, $offset + 4, 4);
            $data = substr($binary, $offset + 8, $chunkLength);
            $offset += 12 + $chunkLength;

            if ($type === 'IHDR') {
                $header = unpack('Nwidth/Nheight/CbitDepth/CcolorType/Ccompression/Cfilter/Cinterlace', $data);
                $width = (int) $header['width'];
                $height = (int) $header['height'];
                $bitDepth = (int) $header['bitDepth'];
                $colorType = (int) $header['colorType'];
                $interlace = (int) $header['interlace'];
            } elseif ($type === 'IDAT') {
                $idat .= $data;
            } elseif ($type === 'IEND') {
                break;
            }
        }

        if ($width <= 0 || $height <= 0 || $bitDepth !== 8 || $interlace !== 0 || $idat === '') {
            return null;
        }

        $channels = [0 => 1, 2 => 3, 4 => 2, 6 => 4][$colorType] ?? null;
        if ($channels === null) {
            return null;
        }

        $decoded = @gzuncompress($idat);
        if ($decoded === false) {
            return null;
        }

        $bytesPerPixel = $channels;
        $stride = $width * $channels;
        $sourceOffset = 0;
        $previous = array_fill(0, $stride, 0);
        $rgb = '';

        for ($row = 0; $row < $height; $row++) {
            if ($sourceOffset >= strlen($decoded)) {
                return null;
            }

            $filter = ord($decoded[$sourceOffset]);
            if ($filter > 4) {
                return null;
            }
            $sourceOffset++;
            $scanline = substr($decoded, $sourceOffset, $stride);
            $sourceOffset += $stride;
            if (strlen($scanline) !== $stride) {
                return null;
            }

            $current = [];
            for ($i = 0; $i < $stride; $i++) {
                $raw = ord($scanline[$i]);
                $left = $i >= $bytesPerPixel ? $current[$i - $bytesPerPixel] : 0;
                $up = $previous[$i] ?? 0;
                $upperLeft = $i >= $bytesPerPixel ? ($previous[$i - $bytesPerPixel] ?? 0) : 0;

                if ($filter === 1) {
                    $value = $raw + $left;
                } elseif ($filter === 2) {
                    $value = $raw + $up;
                } elseif ($filter === 3) {
                    $value = $raw + (int) floor(($left + $up) / 2);
                } elseif ($filter === 4) {
                    $value = $raw + $this->paeth($left, $up, $upperLeft);
                } else {
                    $value = $raw;
                }
                $current[$i] = $value & 0xff;
            }

            for ($x = 0; $x < $width; $x++) {
                $base = $x * $channels;
                if ($colorType === 0) {
                    $r = $g = $b = $current[$base];
                    $a = 255;
                } elseif ($colorType === 2) {
                    $r = $current[$base];
                    $g = $current[$base + 1];
                    $b = $current[$base + 2];
                    $a = 255;
                } elseif ($colorType === 4) {
                    $r = $g = $b = $current[$base];
                    $a = $current[$base + 1];
                } else {
                    $r = $current[$base];
                    $g = $current[$base + 1];
                    $b = $current[$base + 2];
                    $a = $current[$base + 3];
                }

                if ($a < 255) {
                    $r = (int) round($r * $a / 255 + 255 * (1 - $a / 255));
                    $g = (int) round($g * $a / 255 + 255 * (1 - $a / 255));
                    $b = (int) round($b * $a / 255 + 255 * (1 - $a / 255));
                }

                $rgb .= chr($r) . chr($g) . chr($b);
            }

            $previous = $current;
        }

        return ['width' => $width, 'height' => $height, 'rgb' => $rgb];
    }

    private function paeth(int $left, int $up, int $upperLeft): int
    {
        $estimate = $left + $up - $upperLeft;
        $distanceLeft = abs($estimate - $left);
        $distanceUp = abs($estimate - $up);
        $distanceUpperLeft = abs($estimate - $upperLeft);

        if ($distanceLeft <= $distanceUp && $distanceLeft <= $distanceUpperLeft) {
            return $left;
        }

        return $distanceUp <= $distanceUpperLeft ? $up : $upperLeft;
    }
}

// Rendert den (bereits mit Platzhaltern befuellten) Mustervertrags-HTML-Text in das
// dependency-freie PDF-Primitiv-API von SimplePdfDocument. Unterstuetzt genau die Tags, die
// der Mustervertrag-Editor erlaubt: h2/h3/h4 (Ueberschrift), p (Absatz, <br>/<strong> werden zu
// Klartext abgeflacht, da die PDF-Engine keine Inline-Formatierung kennt), ul/li, ol/li.
function contract_template_html_to_pdf(SimplePdfDocument $pdf, string $html): void
{
    if (trim($html) === '') {
        return;
    }

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();

    $root = $doc->getElementsByTagName('div')->item(0);
    if ($root === null) {
        return;
    }

    contract_template_walk_pdf_nodes($pdf, $root->childNodes);
}

function contract_template_walk_pdf_nodes(SimplePdfDocument $pdf, DOMNodeList $nodes): void
{
    foreach ($nodes as $node) {
        if ($node->nodeType !== XML_ELEMENT_NODE) {
            continue;
        }

        $tag = strtolower($node->nodeName);
        if ($tag === 'h1' || $tag === 'h2') {
            $text = trim($node->textContent);
            if ($text !== '') {
                $pdf->heading($text);
            }
        } elseif ($tag === 'h3' || $tag === 'h4') {
            $text = trim($node->textContent);
            if ($text !== '') {
                $pdf->subheading($text);
            }
        } elseif ($tag === 'ul') {
            $items = contract_template_pdf_list_items($node);
            if ($items !== []) {
                $pdf->bulletList($items);
            }
        } elseif ($tag === 'ol') {
            $items = contract_template_pdf_list_items($node);
            if ($items !== []) {
                $pdf->numberedList($items);
            }
        } elseif ($tag === 'section' || $tag === 'div') {
            contract_template_walk_pdf_nodes($pdf, $node->childNodes);
        } else {
            $text = contract_template_pdf_node_text($node);
            if ($text !== '') {
                $pdf->paragraph($text);
            }
        }
    }
}

function contract_template_pdf_node_text(DOMNode $node): string
{
    $text = '';
    foreach ($node->childNodes as $child) {
        if ($child->nodeType === XML_TEXT_NODE) {
            $text .= $child->textContent;
        } elseif ($child->nodeType === XML_ELEMENT_NODE) {
            $text .= strtolower($child->nodeName) === 'br' ? ' ' : $child->textContent;
        }
    }

    return trim((string) preg_replace('/\s+/u', ' ', $text));
}

function contract_template_pdf_list_items(DOMNode $listNode): array
{
    $items = [];
    foreach ($listNode->childNodes as $child) {
        if ($child->nodeType === XML_ELEMENT_NODE && strtolower($child->nodeName) === 'li') {
            $text = contract_template_pdf_node_text($child);
            if ($text !== '') {
                $items[] = $text;
            }
        }
    }

    return $items;
}

function ensure_contract_documents_table(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS contract_documents (
            id VARCHAR(64) NOT NULL,
            contract_id VARCHAR(64) NOT NULL,
            audience VARCHAR(20) NOT NULL,
            filename VARCHAR(160) NOT NULL,
            mime_type VARCHAR(80) NOT NULL DEFAULT \'application/pdf\',
            content LONGBLOB NOT NULL,
            sha256 CHAR(64) NOT NULL,
            generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_contract_documents_contract_audience (contract_id, audience),
            KEY idx_contract_documents_contract (contract_id),
            CONSTRAINT fk_contract_documents_contract FOREIGN KEY (contract_id) REFERENCES contracts (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
}

function contract_pdf_context(PDO $pdo, string $contractId): ?array
{
    $contractStmt = $pdo->prepare('SELECT * FROM contracts WHERE id = :id');
    $contractStmt->execute(['id' => $contractId]);
    $contract = $contractStmt->fetch();
    if (!$contract) {
        return null;
    }

    $offerStmt = $pdo->prepare('SELECT * FROM offers WHERE id = :id');
    $offerStmt->execute(['id' => $contract['offer_id']]);
    $offer = $offerStmt->fetch();
    if (!$offer) {
        return null;
    }

    $customerStmt = $pdo->prepare('SELECT * FROM customers WHERE id = :id');
    $customerStmt->execute(['id' => $offer['customer_id']]);
    $customer = $customerStmt->fetch();
    if (!$customer) {
        return null;
    }

    $siteVisit = null;
    $siteVisitId = trim((string) ($offer['site_visit_id'] ?? ''));
    if ($siteVisitId !== '') {
        $visitStmt = $pdo->prepare('SELECT * FROM site_visits WHERE id = :id');
        $visitStmt->execute(['id' => $siteVisitId]);
        $siteVisit = $visitStmt->fetch() ?: null;
    }

    return ['contract' => $contract, 'offer' => $offer, 'customer' => $customer, 'siteVisit' => $siteVisit];
}

function contract_pdf_filename(array $contract, string $audience): string
{
    $number = (string) ($contract['number'] ?? 'Vertrag');
    $safeNumber = preg_replace('/[^A-Za-z0-9\-_]+/', '-', $number) ?: 'Vertrag';
    if ($audience === 'site_visit') {
        return 'Begehung-' . trim($safeNumber, '-') . '.pdf';
    }
    if ($audience === 'authorization') {
        return 'Vollmacht-' . trim($safeNumber, '-') . '.pdf';
    }
    if ($audience === 'checklist') {
        return 'Checkliste-' . trim($safeNumber, '-') . '.pdf';
    }

    $suffix = $audience === 'cleanteam' ? 'CleanTeam' : 'Kunde';
    return 'Vertrag-' . trim($safeNumber, '-') . '-' . $suffix . '.pdf';
}

function normalize_contract_pdf_audience(string $audience): string
{
    return in_array($audience, ['cleanteam', 'customer', 'site_visit', 'authorization', 'checklist'], true) ? $audience : 'customer';
}

function contract_authorization_grantor_name(array $contract): string
{
    return trim((string) ($contract['authorization_grantor_name'] ?? ''));
}

function contract_authorization_company_address(array $contract): string
{
    return trim((string) ($contract['authorization_company_address'] ?? ''));
}

function contract_has_authorization_details(array $contract): bool
{
    return isset($contract['authorized'])
        && (int) $contract['authorized'] === 0
        && contract_authorization_grantor_name($contract) !== ''
        && contract_authorization_company_address($contract) !== '';
}

function site_visit_floors_from_row(array $siteVisit): array
{
    $floors = json_decode((string) ($siteVisit['floors_json'] ?? '[]'), true);
    return is_array($floors) ? $floors : [];
}

function site_visit_pdf_int($value): int
{
    return max(0, (int) $value);
}

function site_visit_pdf_cleaning_type(?string $value): string
{
    if ($value === 'Gesaugt') {
        return 'Nur gesaugt';
    }
    if ($value === 'Gewischt') {
        return 'Nur gewischt';
    }

    return $value !== null && $value !== '' ? $value : 'Gesaugt und gewischt';
}

function site_visit_pdf_cleaning_frequency(?string $frequency): string
{
    $allowedFrequencies = ['Täglich', 'Alle 2 Tage', 'Wöchentlich', '14-täglich', '30-täglich', 'Individuell'];
    return $frequency !== null && in_array($frequency, $allowedFrequencies, true) ? $frequency : 'Täglich';
}

function site_visit_pdf_floor_cleaning_method(?string $method): string
{
    if ($method === 'Nur gesaugt') {
        return 'Gesaugt';
    }
    if ($method === 'Nur gewischt') {
        return 'Gewischt';
    }

    $allowedMethods = ['Gesaugt', 'Gewischt', 'Gesaugt und gewischt'];
    return $method !== null && in_array($method, $allowedMethods, true) ? $method : 'Gesaugt und gewischt';
}

function site_visit_pdf_trash_bag_mode(?string $mode): string
{
    $allowedModes = ['Mit Mülltüte', 'Ohne Mülltüte'];
    return $mode !== null && in_array($mode, $allowedModes, true) ? $mode : 'Mit Mülltüte';
}

function site_visit_pdf_cleaning_task_label(string $key): string
{
    $labels = [
        'washbasin' => 'Waschbecken',
        'toilet' => 'WC',
        'mirror' => 'Spiegel',
        'floor' => 'Boden',
        'door' => 'Tür',
        'desk' => 'Schreibtische',
        'window' => 'Fensterbänke',
        'surface' => 'Oberflächen',
        'trash' => 'Mülleimer-Entleerung',
        'kitchen' => 'Küchenflächen',
        'handrail' => 'Handlauf / Geländer',
        'stairFloor' => 'Etage',
        'stairDoor' => 'Türen',
        'treatmentDesk' => 'Schreibtisch',
        'treatmentChair' => 'Behandlungsstühle',
        'treatmentTable' => 'Behandlungstisch',
        'disinfection' => 'Desinfektion',
    ];

    return $labels[$key] ?? $key;
}

function site_visit_pdf_cleaning_item(array $item): ?array
{
    $key = trim((string) ($item['key'] ?? ($item['type'] ?? '')));
    if ($key === '') {
        return null;
    }

    $frequency = site_visit_pdf_cleaning_frequency(trim((string) ($item['frequency'] ?? '')));
    $method = trim((string) ($item['method'] ?? ($item['cleaningMethod'] ?? '')));

    return [
        'key' => $key,
        'label' => site_visit_pdf_cleaning_task_label($key),
        'frequency' => $frequency,
        'customFrequency' => $frequency === 'Individuell' ? trim((string) ($item['customFrequency'] ?? '')) : '',
        'method' => $key === 'floor' && $method !== '' ? site_visit_pdf_floor_cleaning_method($method) : '',
        'bagMode' => $key === 'trash' ? site_visit_pdf_trash_bag_mode(trim((string) ($item['bagMode'] ?? ($item['trashBagMode'] ?? '')))) : '',
        'quantity' => site_visit_pdf_int($item['quantity'] ?? 0),
    ];
}

function site_visit_pdf_legacy_cleaning_items_from_room(array $room): array
{
    $items = [];
    if (site_visit_pdf_int($room['sinks'] ?? 0) > 0) {
        $items[] = ['key' => 'washbasin', 'label' => 'Waschbecken', 'frequency' => 'Täglich', 'customFrequency' => ''];
    }
    if (site_visit_pdf_int($room['toilets'] ?? 0) > 0) {
        $items[] = ['key' => 'toilet', 'label' => 'WC', 'frequency' => 'Täglich', 'customFrequency' => ''];
    }
    if (site_visit_pdf_int($room['mirrors'] ?? 0) > 0) {
        $items[] = ['key' => 'mirror', 'label' => 'Spiegel', 'frequency' => 'Täglich', 'customFrequency' => ''];
    }
    if (site_visit_pdf_int($room['desks'] ?? 0) > 0) {
        $items[] = ['key' => 'desk', 'label' => 'Schreibtische', 'frequency' => 'Wöchentlich', 'customFrequency' => ''];
    }
    if (site_visit_pdf_int($room['windows'] ?? 0) > 0) {
        $items[] = ['key' => 'window', 'label' => 'Fensterbänke', 'frequency' => '30-täglich', 'customFrequency' => ''];
    }
    if (trim((string) ($room['cleaningType'] ?? '')) !== '') {
        $items[] = ['key' => 'floor', 'label' => 'Boden', 'frequency' => 'Täglich', 'customFrequency' => '', 'method' => site_visit_pdf_floor_cleaning_method(trim((string) ($room['cleaningType'] ?? '')))];
    }

    return $items;
}

function site_visit_pdf_cleaning_items_from_room(array $room): array
{
    $items = [];
    if (isset($room['cleaningItems']) && is_array($room['cleaningItems'])) {
        foreach ($room['cleaningItems'] as $item) {
            if (is_array($item)) {
                $normalizedItem = site_visit_pdf_cleaning_item($item);
                if ($normalizedItem !== null) {
                    $items[] = $normalizedItem;
                }
            }
        }
    }

    return $items !== [] ? $items : site_visit_pdf_legacy_cleaning_items_from_room($room);
}

function site_visit_pdf_cleaning_item_text(array $item, array $room = []): string
{
    $frequency = $item['frequency'] === 'Individuell'
        ? (trim((string) ($item['customFrequency'] ?? '')) ?: 'Individuell')
        : $item['frequency'];

    $details = [$frequency];
    if (($item['key'] ?? '') === 'floor') {
        if (trim((string) ($item['method'] ?? '')) !== '') {
            $details[] = (string) $item['method'];
        }
    }
    if (($item['key'] ?? '') === 'trash' && trim((string) ($item['bagMode'] ?? '')) !== '') {
        $details[] = (string) $item['bagMode'];
    }
    if (site_visit_pdf_int($item['quantity'] ?? 0) > 0) {
        $details[] = 'Anzahl: ' . site_visit_pdf_int($item['quantity']);
    }

    return $item['label'] . ': ' . implode(', ', $details);
}

function site_visit_pdf_normalize_room(array $room, int $index = 0): array
{
    return [
        'name' => trim((string) ($room['name'] ?? '')) ?: 'Raum ' . ($index + 1),
        'roomType' => trim((string) ($room['roomType'] ?? '')) ?: 'Büro',
        'quantity' => max(1, site_visit_pdf_int($room['quantity'] ?? 1)),
        'squareMeters' => site_visit_pdf_int($room['squareMeters'] ?? 0),
        'cleaningItems' => site_visit_pdf_cleaning_items_from_room($room),
        'sinks' => site_visit_pdf_int($room['sinks'] ?? 0),
        'mirrors' => site_visit_pdf_int($room['mirrors'] ?? 0),
        'toilets' => site_visit_pdf_int($room['toilets'] ?? 0),
        'desks' => site_visit_pdf_int($room['desks'] ?? 0),
        'windows' => site_visit_pdf_int($room['windows'] ?? 0),
        'cleaningType' => site_visit_pdf_cleaning_type(trim((string) ($room['cleaningType'] ?? ''))),
        'floorCondition' => trim((string) ($room['floorCondition'] ?? '')) ?: 'Teppich',
        'extraAgreements' => trim((string) ($room['extraAgreements'] ?? '')),
        'notes' => trim((string) ($room['notes'] ?? ($room['areaNotes'] ?? ''))),
    ];
}

function site_visit_pdf_legacy_rooms_from_floor(array $floor): array
{
    $rooms = [];
    $areaName = trim((string) ($floor['areaName'] ?? ''));
    $areaNotes = trim((string) ($floor['areaNotes'] ?? ($floor['notes'] ?? '')));
    $extraAgreements = trim((string) ($floor['extraAgreements'] ?? ''));
    $cleaningType = site_visit_pdf_cleaning_type(trim((string) ($floor['cleaningType'] ?? '')));
    $floorCondition = trim((string) ($floor['floorCondition'] ?? '')) ?: 'Teppich';
    $sanitaryRooms = site_visit_pdf_int($floor['sanitaryRooms'] ?? 0);
    $officeRooms = site_visit_pdf_int($floor['officeRooms'] ?? 0);

    if ($sanitaryRooms > 0) {
        $rooms[] = site_visit_pdf_normalize_room([
            'name' => $areaName !== '' && $officeRooms === 0 ? $areaName : 'Sanitärbereich',
            'roomType' => 'Sanitär',
            'quantity' => $sanitaryRooms,
            'sinks' => $floor['sinks'] ?? 0,
            'mirrors' => $floor['mirrors'] ?? 0,
            'toilets' => $floor['toilets'] ?? 0,
            'cleaningType' => $cleaningType,
            'floorCondition' => $floorCondition,
            'extraAgreements' => $officeRooms === 0 ? $extraAgreements : '',
            'notes' => $officeRooms === 0 ? $areaNotes : '',
        ]);
    }

    if ($officeRooms > 0) {
        $rooms[] = site_visit_pdf_normalize_room([
            'name' => $areaName !== '' && $sanitaryRooms === 0 ? $areaName : 'Bürobereich',
            'roomType' => 'Büro',
            'quantity' => $officeRooms,
            'desks' => $floor['desks'] ?? 0,
            'windows' => $floor['windows'] ?? 0,
            'cleaningType' => $cleaningType,
            'floorCondition' => $floorCondition,
            'extraAgreements' => $extraAgreements,
            'notes' => $areaNotes,
        ]);
    }

    if ($rooms === [] && ($areaName !== '' || $areaNotes !== '' || $extraAgreements !== '')) {
        $rooms[] = site_visit_pdf_normalize_room([
            'name' => $areaName !== '' ? $areaName : 'Bereich',
            'roomType' => 'Sonstiger Raum',
            'cleaningType' => $cleaningType,
            'floorCondition' => $floorCondition,
            'extraAgreements' => $extraAgreements,
            'notes' => $areaNotes,
        ]);
    }

    return $rooms;
}

function site_visit_pdf_rooms_from_floor(array $floor): array
{
    $rooms = [];
    if (isset($floor['rooms']) && is_array($floor['rooms']) && count($floor['rooms']) > 0) {
        foreach ($floor['rooms'] as $index => $room) {
            if (is_array($room)) {
                $rooms[] = site_visit_pdf_normalize_room($room, (int) $index);
            }
        }
    }

    return $rooms !== [] ? $rooms : site_visit_pdf_legacy_rooms_from_floor($floor);
}

function site_visit_pdf_room_details(array $room): string
{
    return '';
}

function site_visit_pdf_cleaning_items_text(array $room): string
{
    $items = site_visit_pdf_cleaning_items_from_room($room);
    return implode(' | ', array_map(fn(array $item): string => site_visit_pdf_cleaning_item_text($item, $room), $items));
}

function render_site_visit_pdf(array $siteVisit, array $offer, array $customer, ?array $contract): string
{
    $pdf = new SimplePdfDocument();
    $contractNumber = (string) ($contract['number'] ?? 'Entwurf');
    $customerName = (string) ($siteVisit['customer_name'] ?? contract_customer_display_name($customer));
    $createdAt = contract_format_datetime($siteVisit['created_at'] ?? null);
    $floors = site_visit_floors_from_row($siteVisit);

    $pdf->title('Begehungsprotokoll');
    $pdf->label('Begehung zum Vertrag ' . $contractNumber);
    $pdf->meta('Erfasst am: ' . $createdAt . ' | Kostenvoranschlag: ' . contract_format_date($offer['created_at'] ?? null));

    $pdf->heading('Firmendaten');
    $pdf->keyValue('Firma', $customerName);
    $pdf->keyValue('Telefon', (string) ($siteVisit['phone'] ?? ''));
    $pdf->keyValue('E-Mail', (string) ($siteVisit['email'] ?? ''));
    $pdf->keyValue('Adresse', (string) ($siteVisit['address'] ?? ''));
    $pdf->keyValue('Ansprechpartner vor Ort', (string) ($siteVisit['onsite_contact'] ?? ''));
    $pdf->keyValue('Objektgröße', (int) ($siteVisit['square_meters'] ?? 0) . ' m²');

    $pdf->heading('Etagen und Räume');
    if ($floors === []) {
        $pdf->paragraph('Keine Etagenangaben vorhanden.');
    }

    foreach ($floors as $index => $floor) {
        if (!is_array($floor)) {
            continue;
        }

        $name = trim((string) ($floor['name'] ?? '')) ?: 'Etage ' . ($index + 1);
        $pdf->subheading($name);

        $rooms = site_visit_pdf_rooms_from_floor($floor);
        if ($rooms === []) {
            $pdf->paragraph('Keine Räume hinterlegt.');
        }

        foreach ($rooms as $room) {
            $quantity = site_visit_pdf_int($room['quantity'] ?? 1);
            $roomTitle = ($quantity > 1 ? $quantity . 'x ' : '') . (string) $room['name'];
            $pdf->keyValue($roomTitle, (string) $room['roomType']);
            $details = site_visit_pdf_room_details($room);
            if ($details !== '') {
                $pdf->keyValue('Details', $details);
            }
            $cleaning = site_visit_pdf_cleaning_items_text($room);
            if ($cleaning !== '') {
                $pdf->keyValue('Reinigung', $cleaning);
            }
            if (trim((string) ($room['extraAgreements'] ?? '')) !== '') {
                $pdf->keyValue('Extra Vereinbarungen', (string) $room['extraAgreements']);
            }
            if (trim((string) ($room['notes'] ?? '')) !== '') {
                $pdf->keyValue('Notiz zum Raum', (string) $room['notes']);
            }
            $pdf->spacer(2.0);
        }
        $pdf->spacer(4.0);
    }

    $notes = trim((string) ($siteVisit['notes'] ?? ''));
    if ($notes !== '') {
        $pdf->heading('Allgemeine Notizen');
        $pdf->paragraph($notes);
    }

    return $pdf->output();
}

function cleaning_checklist_items_from_offer_notes(string $notes): array
{
    $items = [];
    $currentRoom = '';
    $skipPrefixes = [
        'Leistungsbeschreibung / Dienstleistung',
        'Firma:',
        'Ansprechpartner vor Ort:',
        'Adresse:',
        'Objektgröße:',
        'Begehung erfasst am:',
        'Etagen und Räume:',
        'Allgemeine Notizen:',
    ];

    foreach (preg_split('/\R/u', $notes) ?: [] as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        $shouldSkip = false;
        foreach ($skipPrefixes as $prefix) {
            if ($line === $prefix || strpos($line, $prefix) === 0) {
                $shouldSkip = true;
                break;
            }
        }
        if ($shouldSkip) {
            continue;
        }

        if (preg_match('/^\d+\.\s+(.+)$/u', $line, $matches) === 1) {
            $currentRoom = trim($matches[1]);
            continue;
        }

        if (strpos($line, '- ') === 0) {
            $currentRoom = trim(substr($line, 2));
            continue;
        }

        $line = preg_replace('/^[•*-]\s*/u', '', $line) ?? $line;
        if ($line === '') {
            continue;
        }

        $items[] = $currentRoom !== '' ? $currentRoom . ': ' . $line : $line;
    }

    return array_values(array_unique($items));
}

function render_cleaning_checklist_pdf(array $offer, array $customer, array $contract, ?array $siteVisit = null): string
{
    $pdf = new SimplePdfDocument();
    $contractNumber = (string) ($contract['number'] ?? 'Entwurf');
    $customerName = contract_customer_display_name($customer);
    $customerAddress = trim((string) $customer['address'] . ' ' . (string) $customer['house_number']);
    $customerZipCity = trim((string) $customer['zip'] . ' ' . (string) $customer['city']);
    $startDate = !empty($offer['start_date']) ? contract_format_date($offer['start_date']) : 'Nach Absprache';
    $createdAt = contract_format_date($contract['created_at'] ?? null);

    $pdf->title('Reinigungs-Checkliste');
    $pdf->label('Mitarbeiter / Endkunde');
    $pdf->meta('Vertrag: ' . $contractNumber . ' | Erstellt: ' . $createdAt . ' | Start: ' . $startDate);
    $pdf->heading('Objekt');
    $pdf->keyValue('Kunde', $customerName);
    $pdf->keyValue('Adresse', trim($customerAddress . ', ' . $customerZipCity, ' ,'));
    $pdf->keyValue('Ansprechpartner', contract_signatory_display($customer));
    $pdf->keyValue('Fläche', (int) ($offer['square_meters'] ?? 0) . ' m²');
    $pdf->paragraph('Je Position abhaken: linkes Kästchen = vom Mitarbeiter erledigt, rechtes Kästchen = vom Endkunden geprüft.');

    $hasStructuredItems = false;
    if ($siteVisit !== null) {
        $floors = site_visit_floors_from_row($siteVisit);
        foreach ($floors as $floorIndex => $floor) {
            if (!is_array($floor)) {
                continue;
            }

            $rooms = site_visit_pdf_rooms_from_floor($floor);
            if ($rooms === []) {
                continue;
            }

            $floorName = trim((string) ($floor['name'] ?? '')) ?: 'Etage ' . ($floorIndex + 1);
            $pdf->heading($floorName);
            $pdf->checklistColumns();

            foreach ($rooms as $roomIndex => $room) {
                $quantity = site_visit_pdf_int($room['quantity'] ?? 1);
                $roomTitle = ($quantity > 1 ? $quantity . 'x ' : '') . (trim((string) ($room['name'] ?? '')) ?: 'Raum ' . ($roomIndex + 1));
                $roomType = trim((string) ($room['roomType'] ?? ''));
                $details = site_visit_pdf_room_details($room);
                $roomLabel = $roomTitle . ($roomType !== '' ? ' (' . $roomType . ')' : '');
                if ($details !== '') {
                    $roomLabel .= ' - ' . $details;
                }

                $items = site_visit_pdf_cleaning_items_from_room($room);
                if ($items === []) {
                    continue;
                }

                $hasStructuredItems = true;
                $pdf->subheading($roomLabel);
                foreach ($items as $item) {
                    $pdf->checklistItem(site_visit_pdf_cleaning_item_text($item, $room));
                }

                if (trim((string) ($room['extraAgreements'] ?? '')) !== '') {
                    $pdf->paragraph('Extra Vereinbarungen: ' . (string) $room['extraAgreements'], 9.2, 12.0);
                }
                if (trim((string) ($room['notes'] ?? '')) !== '') {
                    $pdf->paragraph('Notiz: ' . (string) $room['notes'], 9.2, 12.0);
                }
            }
        }
    }

    if (!$hasStructuredItems) {
        $fallbackItems = cleaning_checklist_items_from_offer_notes(trim((string) ($offer['notes'] ?? '')));
        $pdf->heading('Leistungsbeschreibung');
        if ($fallbackItems === []) {
            $pdf->paragraph('Keine Checklistenpositionen im Vertrag hinterlegt.');
        } else {
            $pdf->checklistColumns();
            foreach ($fallbackItems as $item) {
                $pdf->checklistItem($item);
            }
        }
    }

    $pdf->heading('Abschluss');
    $pdf->keyValue('Mitarbeiter', 'Name: ________________________________  Datum: ________________');
    $pdf->keyValue('Endkunde', 'Name: ________________________________  Datum: ________________');

    return $pdf->output();
}

function render_authorization_pdf(array $offer, array $customer, array $contract): string
{
    if (!contract_has_authorization_details($contract)) {
        throw new RuntimeException('Zu diesem Vertrag ist keine Vollmacht hinterlegt.');
    }

    $pdf = new SimplePdfDocument();
    $contractNumber = (string) ($contract['number'] ?? 'Entwurf');
    $createdAt = contract_format_date($contract['created_at'] ?? ($offer['created_at'] ?? null));
    $customerName = contract_customer_display_name($customer);
    $signatoryName = contract_signatory_display($customer);
    $customerAddress = trim((string) $customer['address'] . ' ' . (string) $customer['house_number']);
    $customerZipCity = trim((string) $customer['zip'] . ' ' . (string) $customer['city']);
    $grantorName = contract_authorization_grantor_name($contract);
    $companyAddress = contract_authorization_company_address($contract);

    $pdf->title('Vollmacht zum Gebäudereinigungsvertrag');
    $pdf->label('Vollmacht zum Vertrag ' . $contractNumber);
    $pdf->meta('Erstellt am: ' . $createdAt . ' | Auftraggeber: ' . $customerName);

    $pdf->heading('Vertragsdaten');
    $pdf->keyValue('Vertragsnummer', $contractNumber);
    $pdf->keyValue('Auftragnehmer', CONTRACTOR['legal_name']);
    $pdf->keyValue('Auftraggeber', $customerName);
    $pdf->keyValue('Auftraggeber-Adresse', $customerAddress . ', ' . $customerZipCity);
    $pdf->keyValue('Leistung', (string) $offer['service']);

    $pdf->heading('Vollmachtgeber');
    $pdf->keyValue('Name', $grantorName);
    $pdf->keyValue('Firmenadresse', $companyAddress);

    $pdf->heading('Bevollmächtigte Person');
    $pdf->keyValue('Name', $signatoryName);
    $pdf->keyValue('Firma', $customerName);

    $pdf->heading('Erklärung');
    $pdf->paragraph(
        'Hiermit bevollmächtigt die oben genannte Person die bevollmächtigte Person, den Gebäudereinigungsvertrag '
        . $contractNumber . ' mit ' . CONTRACTOR['legal_name'] . ' für den Auftraggeber rechtsverbindlich zu unterzeichnen.'
    );
    $pdf->paragraph(
        'Dieses Dokument wurde aus den Angaben im digitalen Vertragsassistenten vorbereitet und dient als Vollmachtsdokument '
        . 'zum oben genannten Vertrag.'
    );

    $pdf->heading('Unterschrift Vollmachtgeber');
    $pdf->keyValue('Ort, Datum', '________________________________________');
    $pdf->keyValue('Name', $grantorName);
    $pdf->keyValue('Unterschrift', '________________________________________');

    return $pdf->output();
}

function render_contract_pdf(array $offer, array $customer, ?array $contract, array $options = []): string
{
    $audience = normalize_contract_pdf_audience((string) ($options['audience'] ?? 'customer'));
    if ($audience === 'site_visit') {
        $audience = 'customer';
    }
    $isCleanTeamCopy = $audience === 'cleanteam';
    $pdf = new SimplePdfDocument();

    $contractNumber = (string) ($contract['number'] ?? 'Entwurf');
    $createdAt = contract_format_date($offer['created_at']);
    $isSigned = $contract !== null && $contract['status'] === 'signiert';
    $signedAt = $isSigned ? contract_format_date($contract['signed_at']) : '-';
    $statusLabel = $contract === null ? 'Entwurf' : ucfirst(str_replace('_', ' ', (string) $contract['status']));
    $documentLabel = $isCleanTeamCopy ? 'CleanTeam-Ausfertigung' : 'Kundenausfertigung';

    $authorized = isset($contract['authorized']) && $contract['authorized'] !== null ? (bool) $contract['authorized'] : null;
    $representationNote = $contract['representation_note'] ?? null;
    $authorityText = $authorized === false && $representationNote
        ? (string) $representationNote
        : 'ohne gesonderte Vertretungsangabe';

    $customerName = contract_customer_display_name($customer);
    $signatoryName = contract_signatory_display($customer);
    $customerAddress = trim((string) $customer['address'] . ' ' . (string) $customer['house_number']);
    $customerZipCity = trim((string) $customer['zip'] . ' ' . (string) $customer['city']);
    $managingDirectors = implode(', ', CONTRACTOR['managing_directors']);
    $contractorZipCity = CONTRACTOR['postal_code'] . ' ' . CONTRACTOR['city'] . ', ' . CONTRACTOR['country'];
    $contractorServicePoint = CONTRACTOR['service_point_street'] . ', ' . CONTRACTOR['service_point_postal_code'] . ' ' . CONTRACTOR['service_point_city'];
    $contractorSignatureName = contract_contractor_signature_name();
    $contractorSignatureDataUrl = get_contract_template_contractor_signature_data(db());

    $pdf->title('Gebäudereinigungsvertrag');
    $pdf->label($documentLabel);
    $pdf->meta('Vertragsnummer: ' . $contractNumber . ' | Status: ' . $statusLabel . ' | Erstellt: ' . $createdAt);

    $pdf->heading('Vertragsparteien');
    $pdf->keyValue('Auftragnehmer', CONTRACTOR['legal_name']);
    $pdf->keyValue('Beschreibung', CONTRACTOR['trade_description']);
    $pdf->keyValue('Geschäftsführung', $managingDirectors);
    $pdf->keyValue('Sitz', CONTRACTOR['street'] . ', ' . $contractorZipCity);
    $pdf->keyValue('Service Point', $contractorServicePoint);
    $pdf->spacer(5.0);
    $pdf->keyValue('Auftraggeber', $customerName);
    $pdf->keyValue('Adresse', $customerAddress . ', ' . $customerZipCity);
    $pdf->keyValue('Unterzeichner', $signatoryName . ' (' . $authorityText . ')');

    $templateHtml = get_contract_template_html(db());
    $pdfPlaceholders = contract_template_placeholder_map($offer, $customer, $contract, true);
    $templateBodyHtml = render_contract_template_body($templateHtml, $pdfPlaceholders);
    contract_template_html_to_pdf($pdf, $templateBodyHtml);

    $pdf->heading('Unterschriften');
    $pdf->keyValue('CleanTeam', CONTRACTOR['service_point_city'] . ', ' . $createdAt . ' | Im Namen von CleanTeam: ' . $contractorSignatureName);
    if ($contractorSignatureDataUrl !== null) {
        if (!$pdf->signatureImage($contractorSignatureDataUrl)) {
            $pdf->paragraph('Die CleanTeam-Unterschrift konnte nicht eingebettet werden.', 9.5, 170.0);
        }
    } else {
        $pdf->keyValue('CleanTeam-Unterschrift', 'Noch nicht in den Einstellungen hinterlegt.');
    }
    $pdf->keyValue('Kunde', (string) $customer['city'] . ', ' . $signedAt . ' | Vertragsunterzeichnung durch: ' . $signatoryName);
    if ($isSigned) {
        $pdf->keyValue('Elektronische Signatur', 'Signaturdaten wurden elektronisch erfasst.');
        if (!$pdf->signatureImage($contract['signature_data'] ?? null)) {
            $pdf->paragraph('Das Signaturbild konnte nicht eingebettet werden; der Signaturzeitpunkt ist im Dokument protokolliert.', 9.5, 170.0);
        }
    } else {
        $pdf->keyValue('Elektronische Signatur', 'Noch nicht unterschrieben.');
    }

    if ($isCleanTeamCopy) {
        $pdf->addPage();
        $pdf->title('Signaturprotokoll / Nachweis für CleanTeam');
        $pdf->paragraph('Dieses Protokoll dokumentiert die elektronische Unterzeichnung der CleanTeam-Ausfertigung sowie die Zustimmung zu den Vertragsbedingungen und Allgemeinen Geschäftsbedingungen.');

        $termsAcceptedAt = $contract['terms_accepted_at'] ?? null;
        $termsAccepted = $termsAcceptedAt !== null || $isSigned;
        $termsAcceptedTime = contract_format_datetime($termsAcceptedAt ?: ($isSigned ? ($contract['signed_at'] ?? null) : null));
        $signedAtDisplay = contract_format_datetime($contract['signed_at'] ?? null);
        $pdf->protocolKeyValue('Vertragsnummer', $contractNumber);
        $pdf->protocolKeyValue('Kunde', $customerName);
        $pdf->protocolKeyValue('Unterzeichner', $signatoryName);
        $pdf->protocolKeyValue('Kostenvoranschlag erstellt', contract_format_datetime($offer['created_at'] ?? null));
        $pdf->protocolKeyValue('Vertrag erstellt', contract_format_datetime($contract['created_at'] ?? null));
        $pdf->protocolKeyValue('Vertrag elektronisch signiert', $signedAtDisplay);
        $pdf->protocolKeyValue('AGB / Vertragsbedingungen zugestimmt', $termsAccepted ? 'Ja, Zustimmung erteilt' : 'Noch nicht bestätigt');
        $pdf->protocolKeyValue('Zeitpunkt der Zustimmung', $termsAcceptedTime);
        $pdf->protocolKeyValue('AGB-Fassung', LEGAL['agb_version']);
        $pdf->protocolKeyValue('AGB-Quelle', LEGAL['agb_url']);
        if ($contract !== null && contract_has_authorization_details($contract)) {
            $pdf->protocolKeyValue('Vollmachtgeber', contract_authorization_grantor_name($contract));
            $pdf->protocolKeyValue('Vollmacht-Adresse', contract_authorization_company_address($contract));
        }
    }

    return $pdf->output();
}

function load_contract_pdf(PDO $pdo, string $contractId, string $audience): ?array
{
    ensure_contract_documents_table($pdo);
    $stmt = $pdo->prepare('SELECT * FROM contract_documents WHERE contract_id = :contract_id AND audience = :audience');
    $stmt->execute(['contract_id' => $contractId, 'audience' => $audience]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function save_contract_pdf(PDO $pdo, string $contractId, string $audience, bool $force = false): array
{
    $audience = normalize_contract_pdf_audience($audience);
    ensure_contract_documents_table($pdo);

    if (!$force) {
        $existing = load_contract_pdf($pdo, $contractId, $audience);
        if ($existing !== null) {
            return $existing;
        }
    }

    $context = contract_pdf_context($pdo, $contractId);
    if ($context === null) {
        throw new RuntimeException('Vertrag konnte nicht geladen werden.');
    }

    if ($audience === 'site_visit') {
        if (($context['siteVisit'] ?? null) === null) {
            throw new RuntimeException('Zu diesem Vertrag ist keine Begehung verknüpft.');
        }
        $content = render_site_visit_pdf($context['siteVisit'], $context['offer'], $context['customer'], $context['contract']);
    } elseif ($audience === 'authorization') {
        $content = render_authorization_pdf($context['offer'], $context['customer'], $context['contract']);
    } elseif ($audience === 'checklist') {
        $content = render_cleaning_checklist_pdf($context['offer'], $context['customer'], $context['contract'], $context['siteVisit'] ?? null);
    } else {
        $content = render_contract_pdf($context['offer'], $context['customer'], $context['contract'], ['audience' => $audience]);
    }
    $filename = contract_pdf_filename($context['contract'], $audience);
    $sha256 = hash('sha256', $content);
    $id = generate_id('contract-document');

    $stmt = $pdo->prepare(
        'INSERT INTO contract_documents (id, contract_id, audience, filename, mime_type, content, sha256, generated_at)
         VALUES (:id, :contract_id, :audience, :filename, :mime_type, :content, :sha256, UTC_TIMESTAMP())
         ON DUPLICATE KEY UPDATE
            filename = VALUES(filename),
            mime_type = VALUES(mime_type),
            content = VALUES(content),
            sha256 = VALUES(sha256),
            generated_at = UTC_TIMESTAMP()'
    );
    $stmt->execute([
        'id' => $id,
        'contract_id' => $contractId,
        'audience' => $audience,
        'filename' => $filename,
        'mime_type' => 'application/pdf',
        'content' => $content,
        'sha256' => $sha256,
    ]);

    $document = load_contract_pdf($pdo, $contractId, $audience);
    if ($document === null) {
        throw new RuntimeException('PDF konnte nicht gespeichert werden.');
    }

    return $document;
}

function save_contract_pdfs(PDO $pdo, string $contractId, bool $force = true): void
{
    save_contract_pdf($pdo, $contractId, 'cleanteam', $force);
    save_contract_pdf($pdo, $contractId, 'customer', $force);

    $context = contract_pdf_context($pdo, $contractId);
    if ($context !== null && ($context['siteVisit'] ?? null) !== null) {
        save_contract_pdf($pdo, $contractId, 'site_visit', $force);
    }
    if ($context !== null) {
        save_contract_pdf($pdo, $contractId, 'checklist', $force);
    }
    if ($context !== null && contract_has_authorization_details($context['contract'])) {
        save_contract_pdf($pdo, $contractId, 'authorization', $force);
    }
}

function output_contract_pdf(array $document, bool $download = false): void
{
    $filename = (string) ($document['filename'] ?? 'Vertrag.pdf');
    $content = (string) $document['content'];
    header('Content-Type: application/pdf');
    header('Content-Length: ' . strlen($content));
    header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . str_replace('"', '', $filename) . '"');
    header('X-Content-Type-Options: nosniff');
    echo $content;
    exit;
}
