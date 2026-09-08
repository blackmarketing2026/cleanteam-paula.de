"""Run the real PHP SMTP transport against a loopback-only capture server."""
import email
import email.policy
import os
from pathlib import Path
import socket
import subprocess
import threading

captured = []
server = socket.socket()
server.bind(('127.0.0.1', 0))
server.listen(2)
server.settimeout(15)


def capture():
    for _ in range(2):
        connection, _ = server.accept()
        connection.settimeout(10)
        with connection, connection.makefile('rwb') as stream:
            stream.write(b'220 local-test\r\n')
            stream.flush()
            recipient = None
            while True:
                line = stream.readline()
                if not line or line.startswith(b'QUIT'):
                    break
                if line.startswith(b'RCPT TO:'):
                    recipient = line.strip()
                if line.startswith(b'DATA'):
                    stream.write(b'354 continue\r\n')
                    stream.flush()
                    body = []
                    while (line := stream.readline()) != b'.\r\n':
                        if not line:
                            raise RuntimeError('Unexpected SMTP disconnect')
                        body.append(line)
                    captured.append((recipient, email.message_from_bytes(b''.join(body), policy=email.policy.default)))
                stream.write(b'250 OK\r\n')
                stream.flush()


worker = threading.Thread(target=capture, daemon=True)
worker.start()
script = r'''<?php
require 'includes/recurring_email.php';
require 'includes/recurring_email_sender.php';
$mailer = new SmtpMailer('127.0.0.1', (int) getenv('SMTP_TEST_PORT'), 'none', '', '');
$smtp = ['username'=>'sender@example.invalid', 'from_name'=>'Test Sender'];
$customer = ['name'=>'Fixture', 'email'=>'contract@example.invalid'];
$series = ['subject'=>'Checklist', 'body'=>'Draft content', 'recipient_mode'=>'manual',
    'recipient_email'=>'manual@example.invalid', 'attachment_name'=>'checklist.txt',
    'attachment_content'=>'Checklist attachment bytes', 'attachment_mime'=>'text/plain'];
$message = ['html'=>'<p>Draft content</p>', 'inlineImages'=>[]];
recurring_email_deliver($mailer, $smtp, $customer, $series, $message, recurring_email_recipient($series, $customer), true);
$series['recipient_mode'] = 'contract';
$series['attachment_name'] = null;
recurring_email_deliver($mailer, $smtp, $customer, $series, $message, recurring_email_recipient($series, $customer));
'''
environment = dict(os.environ, SMTP_TEST_PORT=str(server.getsockname()[1]))
result = subprocess.run(['php'], input=script, text=True, capture_output=True, timeout=20,
                        cwd=Path(__file__).resolve().parents[1], env=environment)
worker.join(5)
server.close()
assert result.returncode == 0, result.stdout + result.stderr
assert len(captured) == 2, captured
recipient, message = captured[0]
assert recipient == b'RCPT TO:<manual@example.invalid>'
assert str(message['Subject']) == '[Test] Checklist'
attachment = list(message.iter_attachments())[0]
assert attachment.get_filename() == 'checklist.txt'
assert attachment.get_payload(decode=True) == b'Checklist attachment bytes'
recipient, message = captured[1]
assert recipient == b'RCPT TO:<contract@example.invalid>'
assert str(message['Subject']) == 'Checklist'
assert not list(message.iter_attachments())
print('PASS: local SMTP test/manual recipient/attachment and regular contract recipient without attachment')
