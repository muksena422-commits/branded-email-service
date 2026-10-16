<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/events.php';
$cfg = require __DIR__ . '/../config/api.php';

if (($_SERVER['HTTP_X_API_KEY'] ?? '') !== $cfg['api_key']) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Invalid API key']);
    exit;
}

$body   = json_decode(file_get_contents('php://input'), true) ?: [];
$result = handleEvent($body['event'] ?? '', $body);

http_response_code($result['ok'] ? 200 : 400);
echo json_encode($result);