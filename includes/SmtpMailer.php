<?php

/**
 * Schlanker, abhaengigkeitsfreier SMTP-Client. Kein Composer/PHPMailer noetig,
 * damit die Anwendung per einfachem Datei-Upload auf Shared-Hosting laeuft.
 * Unterstuetzt SSL (implizit, z. B. Port 465), STARTTLS (z. B. Port 587),
 * unverschluesselt sowie AUTH LOGIN.
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
        bool $isHtml = true
    ): void {
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

        try {
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

            $this->command($socket, 'MAIL FROM:<' . $this->sanitizeAddress($fromEmail) . '>', 250);
            $this->command($socket, 'RCPT TO:<' . $this->sanitizeAddress($toEmail) . '>', 250);
            $this->command($socket, 'DATA', 354);

            $headers = $this->buildHeaders($fromEmail, $fromName, $toEmail, $toName, $subject, $isHtml);
            $payload = $headers . "\r\n" . $this->dotStuff($body) . "\r\n.";
            $this->rawWrite($socket, $payload);
            $this->expect($socket, 250);

            $this->rawWrite($socket, 'QUIT');
        } finally {
            fclose($socket);
        }
    }

    private function heloName(): string
    {
        return 'localhost';
    }

    private function sanitizeAddress(string $address): string
    {
        return str_replace(["\r", "\n", '<', '>'], '', trim($address));
    }

    private function buildHeaders(
        string $fromEmail,
        string $fromName,
        string $toEmail,
        string $toName,
        string $subject,
        bool $isHtml
    ): string {
        $lines = [
            'Date: ' . date('r'),
            'From: ' . $this->encodeHeader($fromName) . ' <' . $this->sanitizeAddress($fromEmail) . '>',
            'To: ' . $this->encodeHeader($toName) . ' <' . $this->sanitizeAddress($toEmail) . '>',
            'Subject: ' . $this->encodeHeader($subject),
            'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $this->host . '>',
            'MIME-Version: 1.0',
            'Content-Type: ' . ($isHtml ? 'text/html' : 'text/plain') . '; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
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
