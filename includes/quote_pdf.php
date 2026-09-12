<?php
require_once __DIR__ . '/contract_pdf.php';

const QUOTE_FIXED_NUMBER = '8680';
const QUOTE_FIXED_CUSTOMER_NUMBER = 'K2130';
const QUOTE_VALIDITY_DAYS = 14;

function ensure_quote_documents_table(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS quote_documents (
        id VARCHAR(64) NOT NULL, offer_id VARCHAR(64) NOT NULL, filename VARCHAR(190) NOT NULL,
        mime_type VARCHAR(80) NOT NULL DEFAULT \'application/pdf\', content LONGBLOB NOT NULL,
        sha256 CHAR(64) NOT NULL, generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id), UNIQUE KEY uniq_quote_documents_offer (offer_id),
        CONSTRAINT fk_quote_documents_offer FOREIGN KEY (offer_id) REFERENCES offers (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}

function quote_pdf_filename(string $customerName): string
{
    $name = trim(preg_replace('/[\/\\\\:*?"<>|]+/', '-', $customerName) ?: '') ?: 'Kunde';
    return 'CleanTeam Kostenvoranschlag - ' . $name . '.pdf';
}

function quote_pdf_logo_path(): ?string
{
    $path = __DIR__ . '/../assets/cleanteam-logo.jpg';
    return is_file($path) ? $path : null;
}

function render_quote_pdf(array $offer, array $customer): string
{
    $pdf = new SimplePdfDocument();
    $pdf->quoteLetterhead($customer, contract_current_date(), quote_pdf_logo_path(), [
        'locations' => [
            ['Clean Team Gebäudereinigung', 'Meisterbetrieb', 'Ober der Mühle 30', '42699 Solingen'],
            ['Clean Team Wuppertal', 'Kleine Lagerstraße 5', '42119 Wuppertal'],
            ['CleanTeam Haan', 'Bergische Straße 8', '42781 Haan'],
        ],
        'email' => 'info@cleanteam-group.com',
        'website' => 'www.cleanteam-group.com',
        'certificate' => 'ISO 14001:2015',
        'contact' => 'Fabio Donato',
        'senderLine' => 'Clean Team Group - Meisterbetrieb für Gebäudereinigung - Ober der Mühle 30 - 42699 Solingen',
        'quoteNumber' => QUOTE_FIXED_NUMBER,
        'customerNumber' => QUOTE_FIXED_CUSTOMER_NUMBER,
    ]);
    $pdf->quoteIntroduction();

    $net = (float) $offer['price'];
    $pdf->quoteServiceTable((string) ($offer['notes'] ?? ''), (string) ($offer['interval_label'] ?? ''), contract_format_money($net));
    $pdf->quoteNetTotal(contract_format_money($net));
    $pdf->quoteRemarkAndManagement(
        'Alle genannten Preise verstehen sich als Nettobeträge zuzüglich Mehrwertsteuer. Die Bereitstellung von Reinigungsmitteln sowie die Anfahrtskosten sind im Preis enthalten. Toilettenpapier, Seife und spezielle Müllsäcke werden vom Auftraggeber zur Verfügung gestellt.',
        'Thomas Mündlein'
    );
    return $pdf->output();
}

function save_quote_pdf(PDO $pdo, array $offer, array $customer): array
{
    ensure_quote_documents_table($pdo);
    $content = render_quote_pdf($offer, $customer);
    $filename = quote_pdf_filename(contract_customer_display_name($customer));
    $stmt = $pdo->prepare(
        'INSERT INTO quote_documents (id, offer_id, filename, mime_type, content, sha256, generated_at)
         VALUES (:id,:offer_id,:filename,\'application/pdf\',:content,:sha256,UTC_TIMESTAMP())
         ON DUPLICATE KEY UPDATE filename = :updated_filename, mime_type = \'application/pdf\', content = :updated_content,
            sha256 = :updated_sha256, generated_at = UTC_TIMESTAMP()'
    );
    $stmt->execute([
        'id' => generate_id('quote-document'),
        'offer_id' => $offer['id'],
        'filename' => $filename,
        'content' => $content,
        'sha256' => hash('sha256', $content),
        'updated_filename' => $filename,
        'updated_content' => $content,
        'updated_sha256' => hash('sha256', $content),
    ]);
    $stmt = $pdo->prepare('SELECT * FROM quote_documents WHERE offer_id = :id'); $stmt->execute(['id' => $offer['id']]);
    return $stmt->fetch();
}
