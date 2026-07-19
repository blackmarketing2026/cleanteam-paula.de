<?php

/**
 * Schlanker, abhaengigkeitsfreier SMTP-Client. Kein Composer/PHPMailer noetig,
 * damit die Anwendung per einfachem Datei-Upload auf Shared-Hosting laeuft.
 * Unterstuetzt SSL (implizit, z. B. Port 465), STARTTLS (z. B. Port 587),
 * unverschluesselt sowie AUTH LOGIN. Optional ein einzelner Datei-Anhang
 * (multipart/mixed).
 */
final class SmtpMailer
{
    private string $host;
    private int $port;
    private string $encryption;
    private string $username;
    private string $password;
    private int $timeout;

    public function __construct(
        string $host,
        int $port,
        string $encryption,
        string $username,
        string $password,
        int $timeout = 15
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->encryption = $encryption;
        $this->username = $username;
        $this->password = $password;
        $this->timeout = $timeout;
    }

    public function send(
        string $fromEmail,
        string $fromName,
        string $toEmail,
        string $toName,
        string $subject,
        string $body,
        bool $isHtml = true,
        array $inlineImages = []
    ): void {
        if ($isHtml && $inlineImages !== []) {
            $payload = $this->buildRelatedPayload($fromEmail, $fromName, $toEmail, $toName, $subject, $body, $inlineImages);
            $this->transmit($fromEmail, $toEmail, $payload);
            return;
        }

        $contentType = ($isHtml ? 'text/html' : 'text/plain') . '; charset=UTF-8';
        $payload = $this->buildHeaders($fromEmail, $fromName, $toEmail, $toName, $subject, $contentType)
            . "\r\n" . $this->dotStuff($body);

        $this->transmit($fromEmail, $toEmail, $payload);
    }

    public function sendWithAttachment(
        string $fromEmail,
        string $fromName,
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $attachmentFilename,
        string $attachmentContent,
        string $attachmentMimeType = 'text/html',
        array $inlineImages = []
    ): void {
        $boundary = 'ct-' . bin2hex(random_bytes(12));
        $headers = $this->buildHeaders($fromEmail, $fromName, $toEmail, $toName, $subject, 'multipart/mixed; boundary="' . $boundary . '"');

        if ($inlineImages !== []) {
            $bodyPart = "--{$boundary}\r\n"
                . $this->buildRelatedBody($htmlBody, $inlineImages) . "\r\n";
        } else {
            $bodyPart = "--{$boundary}\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: 8bit\r\n\r\n"
                . $htmlBody . "\r\n";
        }

        $safeAttachmentFilename = $this->sanitizeFilename($attachmentFilename);
        $safeAttachmentMimeType = $this->sanitizeMimeType($attachmentMimeType);
        $attachmentPart = "--{$boundary}\r\n"
            . "Content-Type: {$safeAttachmentMimeType}; name=\"{$safeAttachmentFilename}\"\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . "Content-Disposition: attachment; filename=\"{$safeAttachmentFilename}\"\r\n\r\n"
            . chunk_split(base64_encode($attachmentContent)) . "\r\n";

        $payload = $headers . "\r\n" . $this->dotStuff($bodyPart . $attachmentPart . "--{$boundary}--");

        $this->transmit($fromEmail, $toEmail, $payload);
    }

    private function buildRelatedPayload(
        string $fromEmail,
        string $fromName,
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        array $inlineImages
    ): string {
        $boundary = 'ct-rel-' . bin2hex(random_bytes(12));
        $headers = $this->buildHeaders($fromEmail, $fromName, $toEmail, $toName, $subject, 'multipart/related; boundary="' . $boundary . '"');
        $body = $this->buildRelatedBody($htmlBody, $inlineImages, $boundary, false);

        return $headers . "\r\n" . $this->dotStuff($body);
    }

    private function buildRelatedBody(string $htmlBody, array $inlineImages, ?string $boundary = null, bool $includeContentType = true): string
    {
        $boundary = $boundary ?: 'ct-rel-' . bin2hex(random_bytes(12));
        $body = $includeContentType
            ? "Content-Type: multipart/related; boundary=\"{$boundary}\"\r\n\r\n"
            : '';

        $body .= "--{$boundary}\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n\r\n"
            . $htmlBody . "\r\n";

        foreach ($inlineImages as $image) {
            $cid = $this->sanitizeContentId((string) ($image['cid'] ?? ''));
            $mime = $this->sanitizeMimeType((string) ($image['mimeType'] ?? 'application/octet-stream'));
            $filename = $this->sanitizeFilename((string) ($image['filename'] ?? 'image'));
            $content = (string) ($image['content'] ?? '');
            if ($cid === '' || $content === '') {
                continue;
            }

            $body .= "--{$boundary}\r\n"
                . "Content-Type: {$mime}; name=\"{$filename}\"\r\n"
                . "Content-Transfer-Encoding: base64\r\n"
                . "Content-ID: <{$cid}>\r\n"
                . "Content-Disposition: inline; filename=\"{$filename}\"\r\n\r\n"
                . chunk_split(base64_encode($content)) . "\r\n";
        }

        return $body . "--{$boundary}--";
    }

    private function transmit(string $fromEmail, string $toEmail, string $payload): void
    {
        $socket = $this->connect();

        try {
            $this->command($socket, 'MAIL FROM:<' . $this->sanitizeAddress($fromEmail) . '>', 250);
            $this->command($socket, 'RCPT TO:<' . $this->sanitizeAddress($toEmail) . '>', 250);
            $this->command($socket, 'DATA', 354);

            $this->rawWrite($socket, $payload . "\r\n.");
            $this->expect($socket, 250);

            $this->rawWrite($socket, 'QUIT');
        } finally {
            fclose($socket);
        }
    }

    private function connect()
    {
        $transport = $this->encryption === 'ssl' ? 'ssl://' : 'tcp://';
        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client(
            $transport . $this->host . ':' . $this->port,
            $errno,
            $errstr,
            $this->timeout
        );

        if ($socket === false) {
            throw new RuntimeException("Verbindung zum SMTP-Server fehlgeschlagen: {$errstr} ({$errno})");
        }

        stream_set_timeout($socket, $this->timeout);

        $this->expect($socket, 220);
        $this->command($socket, 'EHLO ' . $this->heloName(), 250);

        if ($this->encryption === 'tls') {
            $this->command($socket, 'STARTTLS', 220);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('STARTTLS-Verschluesselung konnte nicht aufgebaut werden.');
            }
            $this->command($socket, 'EHLO ' . $this->heloName(), 250);
        }

        if ($this->username !== '') {
            $this->command($socket, 'AUTH LOGIN', 334);
            $this->command($socket, base64_encode($this->username), 334);
            $this->command($socket, base64_encode($this->password), 235);
        }

        return $socket;
    }

    private function heloName(): string
    {
        return 'localhost';
    }

    private function sanitizeAddress(string $address): string
    {
        return str_replace(["\r", "\n", '<', '>'], '', trim($address));
    }

    private function sanitizeContentId(string $contentId): string
    {
        return preg_replace('/[^A-Za-z0-9._@-]/', '', $contentId) ?? '';
    }

    private function sanitizeMimeType(string $mimeType): string
    {
        return preg_match('/^[A-Za-z0-9.+-]+\/[A-Za-z0-9.+-]+$/', $mimeType) ? $mimeType : 'application/octet-stream';
    }

    private function sanitizeFilename(string $filename): string
    {
        $filename = str_replace(["\r", "\n", '"', '\\'], '', trim($filename));
        return $filename !== '' ? $filename : 'image';
    }

    private function buildHeaders(
        string $fromEmail,
        string $fromName,
        string $toEmail,
        string $toName,
        string $subject,
        string $contentType
    ): string {
        $lines = [
            'Date: ' . date('r'),
            'From: ' . $this->encodeHeader($fromName) . ' <' . $this->sanitizeAddress($fromEmail) . '>',
            'To: ' . $this->encodeHeader($toName) . ' <' . $this->sanitizeAddress($toEmail) . '>',
            'Subject: ' . $this->encodeHeader($subject),
            'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $this->host . '>',
            'MIME-Version: 1.0',
            'Content-Type: ' . $contentType,
        ];

        return implode("\r\n", $lines) . "\r\n";
    }

    private function encodeHeader(string $value): string
    {
        if (preg_match('/[^\x20-\x7E]/', $value) === 1) {
            return '=?UTF-8?B?' . base64_encode($value) . '?=';
        }

        return $value;
    }

    private function dotStuff(string $body): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $body);
        $lines = explode("\n", $normalized);
        foreach ($lines as &$line) {
            if (isset($line[0]) && $line[0] === '.') {
                $line = '.' . $line;
            }
        }

        return implode("\r\n", $lines);
    }

    private function rawWrite($socket, string $line): void
    {
        fwrite($socket, $line . "\r\n");
    }

    private function command($socket, string $line, int $expectedCode): string
    {
        $this->rawWrite($socket, $line);
        return $this->expect($socket, $expectedCode);
    }

    private function expect($socket, int $expectedCode): string
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        if ($response === '') {
            throw new RuntimeException('Keine Antwort vom SMTP-Server erhalten (Zeitueberschreitung?).');
        }

        $code = (int) substr($response, 0, 3);
        if ($code !== $expectedCode) {
            throw new RuntimeException("Unerwartete SMTP-Antwort (erwartet {$expectedCode}): " . trim($response));
        }

        return $response;
    }
}
