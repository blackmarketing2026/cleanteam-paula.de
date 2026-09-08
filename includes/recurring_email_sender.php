<?php

require_once __DIR__ . '/crypto.php';
require_once __DIR__ . '/SmtpMailer.php';
require_once __DIR__ . '/email_template.php';

function recurring_email_message(PDO $pdo, array $smtp, array $series): array
{
    return render_email_template_message($pdo, '<div>' . nl2br(email_h($series['body'])) . '</div>', [
        'title' => $series['subject'], 'fromName' => $smtp['from_name'] ?? 'CleanTeam',
        'signatureText' => $smtp['signature'] ?? '', 'signatureContext' => 'contract_customer',
    ]);
}

function recurring_email_deliver(SmtpMailer $mailer, array $smtp, array $customer, array $series, array $message, string $recipient, bool $test = false): void
{
    $subject = ($test ? '[Test] ' : '') . $series['subject'];
    if (!empty($series['attachment_name'])) {
        $mailer->sendWithAttachment($smtp['username'], $smtp['from_name'], $recipient, $customer['name'],
            $subject, $message['html'], $series['attachment_name'], $series['attachment_content'],
            $series['attachment_mime'], $message['inlineImages']);
    } else {
        $mailer->send($smtp['username'], $smtp['from_name'], $recipient, $customer['name'],
            $subject, $message['html'], true, $message['inlineImages']);
    }
}
