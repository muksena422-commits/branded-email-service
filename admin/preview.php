<?php
// Returns the email design for the current form values (used by the live preview).
// Read-only: it sends nothing and saves nothing.
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/lib/email_builder.php';
requireLogin();

header('Content-Type: text/html; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit;
}

$r = buildEmail($pdo, $_POST, false);

if ($r['errors']) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"></head>'
        . '<body style="margin:0;font-family:Segoe UI,Arial,sans-serif;color:#64748B;background:#F8FAFC;">'
        . '<div style="padding:70px 24px;text-align:center;">'
        . '<div style="font-size:44px;line-height:1;">&#128231;</div>'
        . '<p style="margin:14px 0 0;font-size:15px;">' . h(implode(' ', $r['errors'])) . '</p>'
        . '</div></body></html>';
    exit;
}

echo $r['html'];