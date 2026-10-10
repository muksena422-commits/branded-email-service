<?php
function renderEmail(?array $college, string $studentName, string $contentHtml, string $logoSrc = ''): string
{
    // Plain default design if the college was deleted
    $color   = $college['brand_color']    ?? '#475569';
    $name    = $college['name']           ?? 'Exam Platform';
    $sender  = $college['sender_name']    ?? 'Exam Platform';
    $contact = $college['contact_details'] ?? '';

    $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

    $logo = $logoSrc ? "<img src='{$e($logoSrc)}' height='40' alt='' style='vertical-align:middle;margin-right:10px;'>" : '';

    return "
    <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;border:1px solid #E2E8F0;'>
      <div style='background:{$e($color)};color:#fff;padding:16px 20px;font-size:18px;font-weight:bold;'>
        $logo{$e($name)}
      </div>
      <div style='padding:20px;color:#0F172A;font-size:14px;line-height:1.6;'>
        <p>Hi {$e($studentName)},</p>
        $contentHtml
        <p>&ndash; {$e($sender)}</p>
      </div>
      <div style='background:#F8FAFC;padding:12px 20px;font-size:12px;color:#64748B;'>
        {$e($contact)}
      </div>
    </div>";
}

function emailContent(string $type, array $d): string
{
    $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    switch ($type) {
        case 'welcome':
            return "<p>Your account is ready. Your login: {$e($d['email'])}</p>";
        case 'result':
            return "<p>Your {$e($d['exam'])} result: {$e($d['score'])}/{$e($d['total'])}</p>";
        case 'disqualification':
            return "<p>Your {$e($d['exam'])} was ended due to rule violations.</p>";
    }
    return '';
}