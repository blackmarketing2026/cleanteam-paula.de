<?php

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/contract_template.php';
require_once __DIR__ . '/email_signature.php';

function email_h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function email_plain_text_html(string $text): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }

    return nl2br(email_h($text), false);
}

function email_normalize_signature_extra(string $text): string
{
    $lines = preg_split('/\R/', $text) ?: [];
    $genericLines = ['--', 'mit freundlichen gruessen', 'mit freundlichen grüßen', 'ihr cleanteam', 'ihr cleanteam-team'];
    $filtered = [];

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '') {
            continue;
        }
        $normalized = function_exists('mb_strtolower') ? mb_strtolower($trimmed, 'UTF-8') : strtolower($trimmed);
        if (in_array($normalized, $genericLines, true)) {
            continue;
        }
        $filtered[] = $trimmed;
    }

    return trim(implode("\n", $filtered));
}

function email_brand_logo_url(PDO $pdo): ?string
{
    try {
        $row = $pdo->query('SELECT logo_filename FROM branding_settings WHERE id = 1')->fetch();
    } catch (Throwable $exception) {
        return null;
    }

    if (!is_array($row)) {
        return null;
    }

    $filename = trim((string) ($row['logo_filename'] ?? ''));
    if ($filename === '') {
        return null;
    }

    return email_asset_base_url() . '/uploads/' . rawurlencode($filename);
}

function email_upload_image_mime_type(string $path): string
{
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            return 'image/jpeg';
        case 'png':
            return 'image/png';
        case 'webp':
            return 'image/webp';
        default:
            return 'application/octet-stream';
    }
}

function email_inline_upload_image_html(
    ?string $filename,
    string $alt,
    int $width,
    string $style,
    array &$inlineImages,
    string $fallbackUrl = ''
): string {
    $filename = trim((string) $filename);
    if ($filename === '') {
        return '';
    }

    $path = __DIR__ . '/../uploads/' . basename($filename);
    if (is_file($path)) {
        $cid = 'ct-' . sha1($filename . '|' . filesize($path) . '|' . filemtime($path)) . '@cleanteam';
        $inlineImages[$cid] = [
            'cid' => $cid,
            'filename' => basename($filename),
            'mimeType' => email_upload_image_mime_type($path),
            'content' => (string) file_get_contents($path),
        ];

        return '<img src="cid:' . email_h($cid) . '" alt="' . email_h($alt) . '" width="' . $width . '" style="' . email_h($style) . '">';
    }

    if ($fallbackUrl !== '') {
        return '<img src="' . email_h($fallbackUrl) . '" alt="' . email_h($alt) . '" width="' . $width . '" style="' . email_h($style) . '">';
    }

    return '';
}

function email_brand_logo_filename(PDO $pdo): ?string
{
    try {
        $row = $pdo->query('SELECT logo_filename FROM branding_settings WHERE id = 1')->fetch();
    } catch (Throwable $exception) {
        return null;
    }

    if (!is_array($row)) {
        return null;
    }

    $filename = trim((string) ($row['logo_filename'] ?? ''));
    return $filename !== '' ? $filename : null;
}

function email_logo_html(PDO $pdo, array &$inlineImages = []): string
{
    $filename = email_brand_logo_filename($pdo);
    if ($filename !== null) {
        $imageHtml = email_inline_upload_image_html(
            $filename,
            'CleanTeam',
            168,
            'display:block;max-width:168px;max-height:68px;width:auto;height:auto;border:0;',
            $inlineImages,
            email_asset_base_url() . '/uploads/' . rawurlencode($filename)
        );
        if ($imageHtml !== '') {
            return $imageHtml;
        }
    }

    return '<div style="display:inline-block;padding:10px 14px;border-radius:8px;background:#0a4f91;color:#ffffff;font-size:18px;font-weight:800;letter-spacing:0;">CleanTeam</div>';
}

function email_signature_html(PDO $pdo, array $options = [], array &$inlineImages = []): string
{
    $settings = load_email_signature_settings($pdo);
    $context = trim((string) ($options['signatureContext'] ?? ''));
    if ($context !== '' && !email_signature_should_apply($settings, $context)) {
        return '';
    }

    $fromName = trim((string) ($options['fromName'] ?? ''));
    $signatureText = email_normalize_signature_extra((string) ($options['signatureText'] ?? ''));
    $senderName = trim($settings['senderName']) !== '' ? $settings['senderName'] : ($fromName !== '' ? $fromName : 'Ihr CleanTeam-Team');
    $senderRole = trim($settings['senderRole']);
    $companyName = trim($settings['companyName']) !== '' ? $settings['companyName'] : CONTRACTOR['legal_name'];
    $website = trim($settings['website']) !== '' ? $settings['website'] : CONTRACTOR['website'];
    $contactLines = [];

    if (trim($settings['phone']) !== '') {
        $contactLines[] = 'Tel.: ' . trim($settings['phone']);
    }
    if (trim($settings['mobile']) !== '') {
        $contactLines[] = 'Mobil: ' . trim($settings['mobile']);
    }
    if (trim($settings['email']) !== '') {
        $contactLines[] = 'E-Mail: ' . trim($settings['email']);
    }

    $addressLines = array_values(array_filter([
        trim($settings['addressLine1']),
        trim($settings['addressLine2']),
    ]));
    $extraText = email_normalize_signature_extra(trim($settings['extraText'] . "\n" . $signatureText));
    $extraHtml = $extraText !== ''
        ? '<div style="margin-top:10px;color:#51657d;font-size:12.5px;line-height:1.5;">' . email_plain_text_html($extraText) . '</div>'
        : '';
    $signatureFilename = trim((string) ($settings['imageFilename'] ?? ''));
    $imageHtml = $signatureFilename !== ''
        ? email_inline_upload_image_html(
            $signatureFilename,
            $companyName,
            156,
            'display:block;max-width:156px;max-height:86px;width:auto;height:auto;border:0;',
            $inlineImages,
            (string) ($settings['imageUrl'] ?? '')
        )
        : '';
    $imageHtml = $imageHtml !== '' ? $imageHtml : email_logo_html($pdo, $inlineImages);
    $roleHtml = $senderRole !== ''
        ? '<p style="margin:0 0 10px 0;color:#51657d;font-size:13px;">' . email_h($senderRole) . '</p>'
        : '';
    $contactHtml = $contactLines !== []
        ? '<p style="margin:8px 0 0 0;color:#51657d;">' . email_h(implode(' | ', $contactLines)) . '</p>'
        : '';
    $addressHtml = $addressLines !== []
        ? '<p style="margin:8px 0 0 0;color:#51657d;">' . implode('<br>', array_map('email_h', $addressLines)) . '</p>'
        : '';
    $websiteHtml = $website !== ''
        ? '<p style="margin:8px 0 0 0;"><a href="' . email_h($website) . '" style="color:#0a4f91;text-decoration:none;font-weight:700;">' . email_h($website) . '</a></p>'
        : '';

    return '
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:28px;border-top:1px solid #d7e4f2;">
        <tr>
          <td style="padding-top:18px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td style="padding:0 0 12px 0;vertical-align:top;color:#26384d;font-family:Arial,sans-serif;font-size:13px;line-height:1.5;">
                  <div style="margin:0 0 12px 0;">' . $imageHtml . '</div>
                  <p style="margin:0 0 6px 0;color:#08325f;font-size:15px;font-weight:800;">Mit freundlichen Gr&uuml;&szlig;en</p>
                  <p style="margin:0;color:#0a4f91;font-size:14px;font-weight:800;">' . email_h($senderName) . '</p>
                  ' . $roleHtml . '
                  <p style="margin:0;color:#26384d;"><strong>' . email_h($companyName) . '</strong></p>
                  ' . $contactHtml . '
                  ' . $addressHtml . '
                  ' . $websiteHtml . '
                  ' . $extraHtml . '
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>';
}

function render_email_template(PDO $pdo, string $contentHtml, array $options = []): string
{
    $message = render_email_template_message($pdo, $contentHtml, $options);
    return $message['html'];
}

function render_email_template_message(PDO $pdo, string $contentHtml, array $options = []): array
{
    $inlineImages = [];
    $title = trim((string) ($options['title'] ?? ''));
    $preheader = trim((string) ($options['preheader'] ?? $title));
    $signatureText = (string) ($options['signatureText'] ?? '');
    $fromName = (string) ($options['fromName'] ?? '');
    $titleHtml = $title !== ''
        ? '<h1 style="margin:18px 0 14px 0;color:#08325f;font-size:24px;line-height:1.2;font-weight:800;">' . email_h($title) . '</h1>'
        : '';
    $preheaderHtml = $preheader !== ''
        ? '<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">' . email_h($preheader) . '</div>'
        : '';

    $html = '<!doctype html>
<html lang="de">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>' . email_h($title !== '' ? $title : CONTRACTOR['legal_name']) . '</title>
  </head>
  <body style="margin:0;padding:0;background:#eef5fb;color:#1c2733;font-family:Arial,sans-serif;">
    ' . $preheaderHtml . '
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef5fb;">
      <tr>
        <td align="center" style="padding:28px 14px;">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:680px;background:#ffffff;border:1px solid #d7e4f2;border-radius:12px;box-shadow:0 12px 30px rgba(8,50,95,0.08);overflow:hidden;">
            <tr>
              <td style="padding:26px 28px 22px 28px;border-top:5px solid #0a4f91;">
                ' . email_logo_html($pdo, $inlineImages) . '
                ' . $titleHtml . '
                <div style="color:#26384d;font-size:15px;line-height:1.65;">' . $contentHtml . '</div>
                ' . email_signature_html($pdo, [
                    'fromName' => $fromName,
                    'signatureText' => $signatureText,
                    'signatureContext' => (string) ($options['signatureContext'] ?? ''),
                ], $inlineImages) . '
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>';

    return [
        'html' => $html,
        'inlineImages' => array_values($inlineImages),
    ];
}
