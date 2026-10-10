<?php
function renderEmail(array $b, string $heading, string $bodyHtml): string {
    $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

    $color   = $e($b['color']);
    $name    = $e($b['name']);
    $contact = $e($b['contact']);
    $sender  = $e($b['sender_name']);
    $heading = $e($heading);

    $logo = '';
    if (!empty($b['logo_src'])) {
        $logo = '<td style="padding-right:14px;width:1%;">'
              . '<img src="' . $e($b['logo_src']) . '" alt="" height="44" '
              . 'style="display:block;height:44px;max-width:120px;background:#ffffff;border-radius:6px;padding:3px;">'
              . '</td>';
    }

    return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head>'
    . '<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;">'
    . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 0;"><tr><td align="center">'
    . '<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:8px;overflow:hidden;">'
    . '<tr><td style="background:' . $color . ';padding:18px 24px;"><table cellpadding="0" cellspacing="0"><tr>'
    . $logo
    . '<td style="color:#ffffff;font-size:18px;font-weight:bold;text-transform:uppercase;">' . $name . '</td>'
    . '</tr></table></td></tr>'
    . '<tr><td style="padding:28px 24px;color:#111827;font-size:15px;line-height:1.6;">'
    . '<h2 style="margin:0 0 16px;font-size:20px;color:' . $color . ';">' . $heading . '</h2>'
    . $bodyHtml
    . '<p style="margin:24px 0 0;color:#374151;">&ndash; ' . $sender . '</p>'
    . '</td></tr>'
    . '<tr><td style="background:#f9fafb;padding:14px 24px;color:#6b7280;font-size:12px;border-top:1px solid #e5e7eb;">' . $contact . '</td></tr>'
    . '</table></td></tr></table></body></html>';
}