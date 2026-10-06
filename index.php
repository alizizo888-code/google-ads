<?php
declare(strict_types=1);

/*
 * PHP front controller for environments where the domain is served by PHP
 * while the Google Ads application runs as a Node.js HTTP service.
 *
 * Node.js server: 127.0.0.1:3000 by default.
 * Override with GOOGLE_ADS_NODE_URL when the hosting provider uses another
 * internal URL/port.
 */

$nodeUrl = rtrim((string) (getenv('GOOGLE_ADS_NODE_URL') ?: 'http://127.0.0.1:3000'), '/');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$query = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== ''
    ? '?' . $_SERVER['QUERY_STRING']
    : '';

$target = $nodeUrl . $path . $query;

$ch = curl_init($target);
if ($ch === false) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'proxy_init_failed']);
    exit;
}

$headers = [];
foreach (getallheaders() ?: [] as $name => $value) {
    $lower = strtolower($name);
    if (in_array($lower, ['host', 'content-length', 'connection'], true)) {
        continue;
    }
    $headers[] = $name . ': ' . $value;
}

$body = file_get_contents('php://input');

curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST => $_SERVER['REQUEST_METHOD'] ?? 'GET',
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_POSTFIELDS => $body !== '' ? $body : null,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 60,
]);

$response = curl_exec($ch);

if ($response === false) {
    $error = curl_error($ch);
    curl_close($ch);

    http_response_code(502);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'node_service_unavailable',
        'message' => 'PHP reached the front controller, but the Node.js service is not reachable on ' . $nodeUrl,
    ]);
    exit;
}

$status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
curl_close($ch);

$rawHeaders = substr($response, 0, $headerSize);
$responseBody = substr($response, $headerSize);

http_response_code($status ?: 502);

foreach (preg_split('/\r\n|\n|\r/', $rawHeaders) ?: [] as $headerLine) {
    if ($headerLine === '' || stripos($headerLine, 'HTTP/') === 0) {
        continue;
    }
    [$name, $value] = array_pad(explode(':', $headerLine, 2), 2, '');
    $name = trim($name);
    $value = trim($value);

    if ($name === '' || in_array(strtolower($name), [
        'transfer-encoding',
        'content-length',
        'connection',
    ], true)) {
        continue;
    }

    header($name . ': ' . $value, false);
}

echo $responseBody;
