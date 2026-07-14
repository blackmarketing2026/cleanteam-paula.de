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

    $suffix = $audience === 'cleanteam' ? 'CleanTeam' : 'Kunde';
    return 'Vertrag-' . trim($safeNumber, '-') . '-' . $suffix . '.pdf';
}

function normalize_contract_pdf_audience(string $audience): string
{
    return in_array($audience, ['cleanteam', 'customer', 'site_visit'], true) ? $audience : 'customer';
}

function site_visit_floors_from_row(array $siteVisit): array
{
    $floors = json_decode((string) ($siteVisit['floors_json'] ?? '[]'), true);
    return is_array($floors) ? $floors : [];
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

    $pdf->heading('Kundendaten');
    $pdf->keyValue('Kunde', $customerName);
    $pdf->keyValue('Telefon', (string) ($siteVisit['phone'] ?? ''));
    $pdf->keyValue('E-Mail', (string) ($siteVisit['email'] ?? ''));
    $pdf->keyValue('Adresse', (string) ($siteVisit['address'] ?? ''));
    $pdf->keyValue('Ansprechpartner vor Ort', (string) ($siteVisit['onsite_contact'] ?? ''));
    $pdf->keyValue('Objektgröße', (int) ($siteVisit['square_meters'] ?? 0) . ' m²');

    $pdf->heading('Etagen und Bereiche');
    if ($floors === []) {
        $pdf->paragraph('Keine Etagenangaben vorhanden.');
    }

    foreach ($floors as $index => $floor) {
        if (!is_array($floor)) {
            continue;
        }

        $name = trim((string) ($floor['name'] ?? '')) ?: 'Etage ' . ($index + 1);
        $areaNotes = trim((string) ($floor['areaNotes'] ?? ($floor['notes'] ?? '')));
        $pdf->subheading($name);
        if (trim((string) ($floor['areaName'] ?? '')) !== '') {
            $pdf->keyValue('Bereich', (string) $floor['areaName']);
        }
        $pdf->keyValue('Sanitärräume', (string) ((int) ($floor['sanitaryRooms'] ?? 0)));
        $pdf->keyValue('Waschbecken', (string) ((int) ($floor['sinks'] ?? 0)));
        $pdf->keyValue('Spiegel', (string) ((int) ($floor['mirrors'] ?? 0)));
        $pdf->keyValue('Toiletten', (string) ((int) ($floor['toilets'] ?? 0)));
        $pdf->keyValue('Büroräume', (string) ((int) ($floor['officeRooms'] ?? 0)));
        $pdf->keyValue('Schreibtische', (string) ((int) ($floor['desks'] ?? 0)));
        $pdf->keyValue('Fenster', (string) ((int) ($floor['windows'] ?? 0)));
        $pdf->keyValue('Bodenart', (string) ($floor['floorCondition'] ?? ''));
        $pdf->keyValue('Bodenbehandlung', (string) ($floor['cleaningType'] ?? ''));
        if (trim((string) ($floor['extraAgreements'] ?? '')) !== '') {
            $pdf->keyValue('Extra Vereinbarungen', (string) $floor['extraAgreements']);
        }
        if ($areaNotes !== '') {
            $pdf->keyValue('Notiz zum Bereich', $areaNotes);
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

function render_contract_pdf(array $offer, array $customer, ?array $contract, array $options = []): string
{
    $audience = normalize_contract_pdf_audience((string) ($options['audience'] ?? 'customer'));
    if ($audience === 'site_visit') {
        $audience = 'customer';
    }
    $isCleanTeamCopy = $audience === 'cleanteam';
    $pdf = new SimplePdfDocument();

    $netPrice = (float) $offer['price'];
    $vatAmount = round($netPrice * VAT_RATE / 100, 2);
    $grossPrice = round($netPrice + $vatAmount, 2);

    $contractNumber = (string) ($contract['number'] ?? 'Entwurf');
    $effectiveDate = contract_format_date($offer['start_date'] ?? $offer['created_at']);
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

    $pdf->heading('§ 1 Beginn, Rechtswahl und Vertragssprache');
    $pdf->numberedList([
        'Der Vertrag zur Gebäudereinigung tritt am ' . $effectiveDate . ' in Kraft.',
        'Für sämtliche Rechtsbeziehungen der Parteien gilt ausschließlich das Recht der Bundesrepublik Deutschland unter Ausschluss aller kollisionsrechtlichen Bestimmungen, die in eine andere Rechtsordnung verweisen. Die Vertragssprache ist Deutsch.',
    ]);

    $pdf->heading('§ 2 Vertragsgegenstand und Objekt');
    $pdf->paragraph('Vertragsgegenstand sind die in § 3 genannten Reinigungsarbeiten für das nachfolgend näher bezeichnete Objekt.');
    $pdf->keyValue('Leistungsort', $customerAddress . ', ' . $customerZipCity);

    $pdf->heading('§ 3 Art und Umfang der Reinigung');
    $pdf->paragraph('Die Reinigung findet ' . (string) $offer['interval_label'] . ' statt.');
    $pdf->paragraph('Gebuchte Leistung: ' . (string) $offer['service'] . ' (' . (int) $offer['square_meters'] . ' m² Reinigungsfläche).');
    $offerNotes = trim((string) ($offer['notes'] ?? ''));
    if ($offerNotes !== '') {
        $pdf->paragraph('Zusatzhinweis: ' . $offerNotes);
    }
    foreach (STANDARD_SERVICE_CATALOG as $category => $items) {
        $pdf->subheading((string) $category);
        $pdf->bulletList($items);
    }
    $pdf->paragraph('Leistungen, die nicht in diesem Vertrag aufgeführt sind, bedürfen einer gesonderten Beauftragung und werden zusätzlich berechnet.');

    $pdf->heading('§ 4 Vergütung');
    $pdf->paragraph('Die monatliche Pauschalvergütung beträgt ' . contract_format_money($netPrice) . ' netto zuzüglich der jeweils geltenden Umsatzsteuer (aktuell ' . rtrim(rtrim(number_format(VAT_RATE, 1, ',', '.'), '0'), ',') . ' %). Der Bruttobetrag beträgt zum Zeitpunkt der Vertragserstellung ' . contract_format_money($grossPrice) . '. Leistungen außerhalb von § 3 sind separat zu beauftragen und werden zusätzlich berechnet. Rechnungen sind binnen ' . PAYMENT_DUE_DAYS . ' Arbeitstagen zu zahlen; nach ' . DEFAULT_AFTER_DAYS . ' Tagen fallen automatisch Verzugszinsen an.');
    $pdf->paragraph('Bei einer Tariferhöhung in der Gebäudereinigung geben wir die entsprechenden Lohnsteigerungen an unser Personal und unsere Kunden weiter. Die Lohnkosten werden voraussichtlich um ' . WAGE_INCREASE_MIN_PERCENT . ' bis ' . WAGE_INCREASE_MAX_PERCENT . ' Prozent steigen. Unsere Kunden informieren wir mindestens ' . PRICE_ADJUSTMENT_NOTICE_MONTHS . ' Monat(e) vorher und teilen den exakten Differenzbetrag mit, der aus Lohn-, Betriebs- und Materialkosten berechnet wird.');
    $pdf->paragraph('Zahlungsverzug / Zurückbehaltungsrecht: Die vereinbarte Vergütung ist innerhalb von ' . PAYMENT_DUE_DAYS . ' Arbeitstagen nach Zugang der Rechnung ohne Abzug auf das von uns benannte Konto zu überweisen. Geht innerhalb dieser Frist kein vollständiger Zahlungseingang ein und erfolgt keine vorherige Mitteilung oder anderweitige Vereinbarung durch den Auftraggeber, sind wir berechtigt, von unserem Zurückbehaltungsrecht gemäß § 320 BGB Gebrauch zu machen und die vertraglich geschuldeten Reinigungsleistungen bis zum vollständigen Ausgleich der offenen Forderung auszusetzen. Die während der Ausübung des Zurückbehaltungsrechts ausfallenden Reinigungstermine gelten als vertraglich geschuldet und werden dem Auftraggeber vergütungsrechtlich berechnet. Ein Anspruch auf Nachholung besteht nicht.');

    $pdf->heading('§ 5 Pflichten des Auftraggebers');
    $pdf->numberedList(CUSTOMER_OBLIGATIONS);

    $pdf->heading('§ 6 Vertraulichkeit');
    $pdf->paragraph('Die Parteien vereinbaren, während der Laufzeit dieses Vertrags über die Geschäfts- und Betriebsgeheimnisse der jeweils anderen Partei, einschließlich dieses Vertrags, über jedes Know-how, jegliches von einer Partei ausgehändigte Material und sämtliche Informationen, die eine Partei über das Geschäft der jeweils anderen erhält ("vertrauliche Informationen"), vertraulich zu behandeln und nicht an Dritte weiterzugeben.');

    $pdf->heading('§ 7 Erfüllungsort, Gerichtsstand und Schlussbestimmungen');
    $pdf->numberedList([
        'Der Erfüllungsort richtet sich nach dem Sitz des Auftraggebers.',
        'Ausschließlicher Gerichtsstand für Streitigkeiten im Zusammenhang mit dem Vertragsverhältnis ist ' . LEGAL['jurisdiction_city'] . '.',
        'Für sämtliche Rechtsbeziehungen der Parteien gilt ausschließlich das Recht der Bundesrepublik Deutschland unter Ausschluss aller kollisionsrechtlichen Bestimmungen, die in eine andere Rechtsordnung verweisen.',
        'Sollte eine Bestimmung dieses Vertrags unwirksam sein, wird die Wirksamkeit der übrigen Bestimmungen hiervon nicht berührt. Die Parteien werden über eine die unwirksame Bestimmung ersetzende Regelung verhandeln, die dem Inhalt der ursprünglichen Bestimmung möglichst nahekommt. Gleiches gilt für mögliche Vertragslücken.',
    ]);

    $pdf->heading('§ 8 Einbeziehung der Allgemeinen Geschäftsbedingungen');
    $pdf->paragraph('Es gelten zusätzlich die Allgemeinen Geschäftsbedingungen des Auftragnehmers. Die gültige Fassung mit ' . LEGAL['agb_version'] . ' ist auf der Website ' . LEGAL['agb_url'] . ' einsehbar. Auf Wunsch kann dem Auftraggeber eine Ausfertigung der Allgemeinen Geschäftsbedingungen auch postalisch zugesendet werden.');

    $pdf->heading('§ 9 Ausfertigungen und elektronische Dokumentation');
    $pdf->paragraph('Beide Parteien erhalten eine Ausfertigung dieses Gebäudereinigungsvertrags. Bei elektronischer Unterzeichnung wird jeder Partei nach Abschluss des Signaturvorgangs eine Ausfertigung zur Verfügung gestellt.');

    $pdf->heading('Unterschriften');
    $pdf->keyValue('CleanTeam', CONTRACTOR['service_point_city'] . ', ' . $createdAt . ' | Geschäftsführer: ' . $managingDirectors);
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
        $pdf->keyValue('Vertragsnummer', $contractNumber);
        $pdf->keyValue('Kunde', $customerName);
        $pdf->keyValue('Unterzeichner', $signatoryName);
        $pdf->keyValue('Kostenvoranschlag erstellt', contract_format_datetime($offer['created_at'] ?? null));
        $pdf->keyValue('Vertrag erstellt', contract_format_datetime($contract['created_at'] ?? null));
        $pdf->keyValue('Vertrag elektronisch signiert', $signedAtDisplay);
        $pdf->keyValue('AGB / Vertragsbedingungen zugestimmt', $termsAccepted ? 'Ja, Zustimmung erteilt' : 'Noch nicht bestätigt');
        $pdf->keyValue('Zeitpunkt der Zustimmung', $termsAcceptedTime);
        $pdf->keyValue('AGB-Fassung', LEGAL['agb_version']);
        $pdf->keyValue('AGB-Quelle', LEGAL['agb_url']);
        if (!empty($contract['signature_data'])) {
            $pdf->keyValue('Signatur-Prüfsumme', hash('sha256', (string) $contract['signature_data']));
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
