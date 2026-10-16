<?php
function renderEmail(?array $college, string $studentName, string $contentHtml, string $logoSrc = ''): string
{
    // Plain default design if the college was deleted
    $color   = $college['brand_color']     ?? '#475569';
    $name    = $college['name']            ?? 'Exam Platform';
    $sender  = $college['sender_name']     ?? 'Exam Platform';
    $contact = $college['contact_details'] ?? '';

    $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

    $logo = $logoSrc ? "<img src='{$e($logoSrc)}' height='40' alt='' style='vertical-align:middle;margin-right:10px;'>" : '';

    return "
    <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;border:1px solid #E2E8F0;background:#ffffff;'>
      <div style='background:{$e($color)};color:#fff;padding:16px 20px;font-size:18px;font-weight:bold;'>
        $logo{$e($name)}
      </div>
      <div style='padding:24px 20px;color:#0F172A;font-size:14px;line-height:1.6;'>
        <p style='margin-top:0;'>Hi {$e($studentName)},</p>
        $contentHtml
        <p style='margin-bottom:0;'>&ndash; {$e($sender)}</p>
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

        // ---------- WELCOME: celebratory ----------
        case 'welcome':
            return "
            <div style='text-align:center;padding:10px 0 18px;'>
              <div style='font-size:42px;line-height:1;'>&#127881;</div>
              <h2 style='margin:10px 0 4px;font-size:24px;color:#0F172A;'>Welcome aboard!</h2>
              <p style='margin:0;color:#64748B;'>Your account is ready. You can start taking exams now.</p>
            </div>
            <div style='background:#FFF7ED;border:1px dashed #F58220;border-radius:10px;padding:14px 16px;margin:8px 0 16px;text-align:center;'>
              <div style='font-size:12px;color:#64748B;text-transform:uppercase;letter-spacing:1px;'>Your login</div>
              <div style='font-size:18px;font-weight:bold;color:#0F172A;margin-top:4px;'>{$e($d['email'] ?? '')}</div>
            </div>
            <p>We wish you the very best for your exams.</p>";

        // ---------- RESULT: score card, green for pass, red for fail ----------
        case 'result':
            $score  = (float)($d['score'] ?? 0);
            $total  = (float)($d['total'] ?? 0);
            $pct    = $total > 0 ? round($score / $total * 100) : 0;
            $passed = $total > 0 && ($score / $total) >= 0.4;   // 40% pass mark
            $main   = $passed ? '#15803D' : '#B91C1C';
            $pale   = $passed ? '#F0FDF4' : '#FEE2E2';
            $label  = $passed ? 'PASSED' : 'NOT PASSED';
            $msg    = $passed ? 'Congratulations, well done!' : 'Do not lose heart. Keep practising and try again.';
            return "
            <p>Your result for <strong>{$e($d['exam'] ?? 'Exam')}</strong> is here.</p>
            <div style='background:{$pale};border:2px solid {$main};border-radius:12px;padding:20px;text-align:center;margin:14px 0;'>
              <div style='font-size:46px;font-weight:bold;color:{$main};line-height:1.1;'>{$e($d['score'] ?? 0)}<span style='font-size:22px;color:#64748B;'> / {$e($d['total'] ?? 0)}</span></div>
              <div style='font-size:15px;color:#0F172A;margin-top:4px;'>{$pct}%</div>
              <div style='display:inline-block;background:{$main};color:#ffffff;font-weight:bold;font-size:13px;padding:6px 16px;border-radius:999px;margin-top:10px;'>{$label}</div>
            </div>
            <p>{$msg}</p>";

        // ---------- DISQUALIFICATION: serious notice ----------
        case 'disqualification':
            $reason = $d['reason'] ?? 'Rule violation detected';
            return "
            <div style='background:#B91C1C;color:#ffffff;padding:10px 16px;border-radius:8px 8px 0 0;font-weight:bold;letter-spacing:1px;font-size:13px;'>
              OFFICIAL NOTICE: EXAM TERMINATED
            </div>
            <div style='border:1px solid #FECACA;border-top:none;background:#FEF2F2;padding:16px;border-radius:0 0 8px 8px;'>
              <p style='margin:0 0 10px;'>Your <strong>{$e($d['exam'] ?? 'Exam')}</strong> was ended because of rule violations, and you have been disqualified.</p>
              <div style='background:#ffffff;border-left:4px solid #B91C1C;padding:10px 12px;'>
                <div style='font-size:12px;color:#64748B;text-transform:uppercase;'>Reason</div>
                <div style='font-weight:bold;color:#0F172A;'>{$e($reason)}</div>
              </div>
            </div>
            <p style='margin-bottom:4px;'><strong>Next steps</strong></p>
            <ol style='margin:0;padding-left:20px;'>
              <li>Your attempt will not be scored.</li>
              <li>If you believe this is a mistake, contact your college using the details below.</li>
              <li>Your college will inform you about any re-attempt.</li>
            </ol>";
    }
    return '';
}