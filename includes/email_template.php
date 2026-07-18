<?php

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/contract_template.php';

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
    $genericLines = ['--', 'mit freundlichen grüßen', 'ihr cleanteam', 'ihr cleanteam-team'];
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

    return base_url() . '/uploads/' . rawurlencode($filename);
}

function email_logo_html(PDO $pdo): string
{
    $logoUrl = email_brand_logo_url($pdo);
    if ($logoUrl !== null) {
        return '<img src="' . email_h($logoUrl) . '" alt="CleanTeam" width="168" style="display:block;max-width:168px;max-height:68px;width:auto;height:auto;border:0;">';
    }

    return '<div style="display:inline-block;padding:10px 14px;border-radius:8px;background:#0a4f91;color:#ffffff;font-size:18px;font-weight:800;letter-spacing:0;">CleanTeam</div>';
}

function email_signature_html(PDO $pdo, array $options = []): string
{
    $fromName = trim((string) ($options['fromName'] ?? ''));
    $signatureText = email_normalize_signature_extra((string) ($options['signatureText'] ?? ''));
    $teamLine = $fromName !== '' ? $fromName : 'Ihr CleanTeam-Team';
    $servicePoint = CONTRACTOR['service_point_street'] . ', ' . CONTRACTOR['service_point_postal_code'] . ' ' . CONTRACTOR['service_point_city'];
    $registeredSeat = CONTRACTOR['street'] . ', ' . CONTRACTOR['postal_code'] . ' ' . CONTRACTOR['city'] . ', ' . CONTRACTOR['country'];
    $website = (string) CONTRACTOR['website'];
    $customText = $signatureText !== ''
        ? '<div style="margin-top:12px;color:#51657d;font-size:13px;line-height:1.55;">' . email_plain_text_html($signatureText) . '</div>'
        : '';

    return '
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:28px;border-top:1px solid #d7e4f2;">
        <tr>
          <td style="padding-top:18px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td style="width:190px;padding:0 18px 12px 0;vertical-align:top;">' . email_logo_html($pdo) . '</td>
                <td style="padding:0 0 12px 0;vertical-align:top;color:#26384d;font-family:Arial,sans-serif;font-size:13px;line-height:1.5;">
                  <p style="margin:0 0 6px 0;color:#08325f;font-size:15px;font-weight:800;">Mit freundlichen Grüßen</p>
                  <p style="margin:0 0 10px 0;color:#0a4f91;font-size:14px;font-weight:800;">' . email_h($teamLine) . '</p>
                  <p style="margin:0;color:#26384d;"><strong>' . email_h(CONTRACTOR['legal_name']) . '</strong><br>' . email_h(CONTRACTOR['trade_description']) . '</p>
                  <p style="margin:8px 0 0 0;color:#51657d;">Service Point: ' . email_h($servicePoint) . '<br>Sitz: ' . email_h($registeredSeat) . '</p>
                  <p style="margin:8px 0 0 0;"><a href="' . email_h($website) . '" style="color:#0a4f91;text-decoration:none;font-weight:700;">' . email_h($website) . '</a></p>
                  ' . $customText . '
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>';
}

function render_email_template(PDO $pdo, string $contentHtml, array $options = []): string
{
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

    return '<!doctype html>
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
                ' . email_logo_html($pdo) . '
                ' . $titleHtml . '
                <div style="color:#26384d;font-size:15px;line-height:1.65;">' . $contentHtml . '</div>
                ' . email_signature_html($pdo, ['fromName' => $fromName, 'signatureText' => $signatureText]) . '
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>';
}
