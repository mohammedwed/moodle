#!/usr/bin/env php
<?php
/**
 * Sendmail wrapper — forwards email to SendGrid's Web API over HTTPS (port 443).
 * Handles multipart/alternative MIME (text + HTML parts) as sent by PHPMailer.
 */

$apiKey = getenv('SENDGRID_API_KEY');
if (!$apiKey) {
    fwrite(STDERR, "sendgrid-sendmail: SENDGRID_API_KEY not set\n");
    exit(1);
}

$raw = stream_get_contents(STDIN);

function splitHeadersBody(string $raw): array {
    $crlfPos = strpos($raw, "\r\n\r\n");
    $lfPos   = strpos($raw, "\n\n");
    if ($crlfPos !== false && ($lfPos === false || $crlfPos <= $lfPos)) {
        return [substr($raw, 0, $crlfPos), substr($raw, $crlfPos + 4)];
    }
    if ($lfPos !== false) {
        return [substr($raw, 0, $lfPos), substr($raw, $lfPos + 2)];
    }
    return [$raw, ''];
}

function parseHeaders(string $headerStr): array {
    $headerStr = preg_replace('/\r?\n[ \t]+/', ' ', $headerStr);
    $headers   = [];
    foreach (preg_split('/\r?\n/', $headerStr) as $line) {
        if (strpos($line, ':') !== false) {
            [$key, $val]                        = explode(':', $line, 2);
            $headers[strtolower(trim($key))] = trim($val);
        }
    }
    return $headers;
}

function decodeBody(string $body, string $encoding): string {
    $enc = strtolower(trim($encoding));
    if ($enc === 'quoted-printable') return quoted_printable_decode($body);
    if ($enc === 'base64')           return base64_decode(preg_replace('/\s+/', '', $body));
    return $body;
}

function extractBoundary(string $contentType): ?string {
    if (preg_match('/boundary=["\']?([^"\';\s]+)["\']?/i', $contentType, $m)) {
        return trim($m[1], '"\'');
    }
    return null;
}

function parseMimeParts(string $body, string $boundary): array {
    $parts     = [];
    $delimiter = '--' . $boundary;
    $sections  = explode($delimiter, $body);
    foreach ($sections as $section) {
        $section = ltrim($section, "\r\n");
        if ($section === '' || rtrim($section) === '--') {
            continue;
        }
        [$hStr, $bdy] = splitHeadersBody($section);
        if ($hStr === '') {
            continue;
        }
        $hdrs    = parseHeaders($hStr);
        $ct      = $hdrs['content-type']              ?? 'text/plain';
        $enc     = $hdrs['content-transfer-encoding'] ?? '7bit';
        $parts[] = ['content-type' => $ct, 'body' => decodeBody($bdy, $enc)];
    }
    return $parts;
}

function parseAddress(string $raw): array {
    $raw = trim($raw);
    if (preg_match('/^(.*?)\s*<([^>]+)>/', $raw, $m)) {
        return ['name' => trim($m[1], ' "\''), 'email' => trim($m[2])];
    }
    return ['email' => $raw];
}

// ── Parse top-level message ──────────────────────────────────────────────────
[$headerStr, $body] = splitHeadersBody($raw);
$headers = parseHeaders($headerStr);

$to          = parseAddress($headers['to']   ?? '');
$from        = parseAddress($headers['from'] ?? (getenv('MOODLE_NOREPLY') ?: 'noreply@localhost'));
$subject     = $headers['subject']      ?? '(no subject)';
$contentType = $headers['content-type'] ?? 'text/plain';

$textContent = null;
$htmlContent = null;

$boundary = extractBoundary($contentType);
if ($boundary !== null && stripos($contentType, 'multipart/') === 0) {
    foreach (parseMimeParts($body, $boundary) as $part) {
        $pct = $part['content-type'];
        if ($htmlContent === null && stripos($pct, 'text/html') !== false) {
            $htmlContent = $part['body'];
        } elseif ($textContent === null && stripos($pct, 'text/plain') !== false) {
            $textContent = $part['body'];
        }
    }
} else {
    $enc     = $headers['content-transfer-encoding'] ?? '7bit';
    $decoded = decodeBody($body, $enc);
    if (stripos($contentType, 'text/html') !== false) {
        $htmlContent = $decoded;
    } else {
        $textContent = $decoded;
    }
}

// SendGrid requires text/plain before text/html.
$content = [];
if ($textContent !== null) {
    $content[] = ['type' => 'text/plain', 'value' => $textContent ?: ' '];
}
if ($htmlContent !== null) {
    $content[] = ['type' => 'text/html',  'value' => $htmlContent ?: '&nbsp;'];
}
if (empty($content)) {
    $content[] = ['type' => 'text/plain', 'value' => '(empty)'];
}

// ── POST to SendGrid ─────────────────────────────────────────────────────────
$payload = json_encode([
    'personalizations' => [['to' => [$to]]],
    'from'             => $from,
    'subject'          => $subject,
    'content'          => $content,
], JSON_UNESCAPED_UNICODE);

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
