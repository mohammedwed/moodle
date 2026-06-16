#!/usr/bin/env php
<?php
/**
 * Sendmail wrapper — forwards email to SendGrid's Web API over HTTPS (port 443).
 * Set as PHP's sendmail_path so Moodle sends mail without outbound SMTP.
 */

$apiKey = getenv('SENDGRID_API_KEY');
if (!$apiKey) {
    fwrite(STDERR, "sendgrid-sendmail: SENDGRID_API_KEY not set\n");
    exit(1);
}

// Read raw email from stdin
$raw = stream_get_contents(STDIN);

// Parse headers and body
$parts     = preg_split('/\r?\n\r?\n/', $raw, 2);
$headerStr = $parts[0] ?? '';
$body      = $parts[1] ?? '';

// Unfold multi-line headers (RFC 2822)
$headerStr = preg_replace('/\r?\n[ \t]+/', ' ', $headerStr);

$headers = [];
foreach (explode("\n", $headerStr) as $line) {
    if (strpos($line, ':') !== false) {
        [$key, $val] = explode(':', $line, 2);
        $headers[strtolower(trim($key))] = trim($val);
    }
}

function parseAddress(string $raw): array {
    $raw = trim($raw);
    if (preg_match('/^(.*?)\s*<([^>]+)>/', $raw, $m)) {
        return ['name' => trim($m[1], ' "\''), 'email' => trim($m[2])];
    }
    return ['email' => $raw];
}

$to      = parseAddress($headers['to']      ?? '');
$from    = parseAddress($headers['from']    ?? (getenv('MOODLE_NOREPLY') ?: 'noreply@localhost'));
$subject = $headers['subject'] ?? '(no subject)';

// Decode quoted-printable or base64 encoded body if needed
$encoding = strtolower($headers['content-transfer-encoding'] ?? '');
if ($encoding === 'quoted-printable') {
    $body = quoted_printable_decode($body);
} elseif ($encoding === 'base64') {
    $body = base64_decode($body);
}

$contentType = strtolower($headers['content-type'] ?? 'text/plain');
$type = str_contains($contentType, 'html') ? 'text/html' : 'text/plain';

$payload = json_encode([
    'personalizations' => [['to' => [$to]]],
    'from'             => $from,
    'subject'          => $subject,
    'content'          => [['type' => $type, 'value' => $body ?: '(empty)']],
]);

// POST to SendGrid API via curl (HTTPS port 443)
$ch = curl_init('https://api.sendgrid.com/v3/mail/send');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = curl_error($ch);
curl_close($ch);

if ($error) {
    fwrite(STDERR, "sendgrid-sendmail: curl error: $error\n");
    exit(1);
}

if ($httpCode === 202 || $httpCode === 200) {
    exit(0);
}

fwrite(STDERR, "sendgrid-sendmail: API returned HTTP $httpCode — $response\n");
exit(1);
