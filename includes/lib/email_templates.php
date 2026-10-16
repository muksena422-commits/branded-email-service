<?php
/**
 * email_templates.php
 * One distinct, email-safe design per email type:
 *   welcome | result | disqualification
 * Tables and inline styles only, so it renders in Gmail, Outlook and phones.
 * Branding (logo, colour, name, sender, contact) comes from the college row.
 * If the college is missing (null) a neutral default design is used.
 */

function tplE($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function tplFont(): string
{
    return 'font-family:Segoe UI,Helvetica,Arial,sans-serif;';
}

/** First non-empty value among $keys in $row, else $default. */
function tplPick($row, array $keys, string $default = ''): string
{
    if (is_array($row)) {
        foreach ($keys as $k) {
            if (isset($row[$k]) && trim((string)$row[$k]) !== '') {
                return trim((string)$row[$k]);
            }
        }
    }
    return $default;
}

function tplNum($n): string
{
    $s = number_format((float)$n, 2, '.', '');
    return rtrim(rtrim($s, '0'), '.');
}

function tplLines(string $text): array
{
    $out = [];
    foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
        $line = trim($line);
        if ($line !== '') {
            $out[] = $line;
        }
    }
    return $out;
}

/* ---------- college branding helpers ---------- */

function tplColour($college): string
{
    $c = tplPick($college, ['brand_colour', 'brand_color', 'colour', 'color', 'primary_colour', 'primary_color']);
    return preg_match('/^#[0-9a-fA-F]{6}$/', $c) ? $c : '#475569';
}

/** amount from -1 (darker) to 1 (lighter) */
function tplShade(string $hex, float $amount): string
{
    $hex = ltrim($hex, '#');
    $rgb = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    foreach ($rgb as $i => $c) {
        if ($amount >= 0) {
            $c = (int)round($c + (255 - $c) * $amount);
        } else {
            $c = (int)round($c * (1 + $amount));
        }
        $rgb[$i] = max(0, min(255, $c));
    }
    return sprintf('#%02X%02X%02X', $rgb[0], $rgb[1], $rgb[2]);
}

function tplCollegeName($college): string
{
    return tplPick($college, ['name'], 'Exam Platform');
}

function tplSenderName($college): string
{
    return tplPick($college, ['sender_name'], tplCollegeName($college));
}

function tplContact($college): string
{
    $combined = tplPick($college, ['contact_details', 'contact']);
    if ($combined !== '') {
        return $combined;
    }
    $parts = [];
    $email = tplPick($college, ['contact_email', 'sender_email', 'email']);
    $phone = tplPick($college, ['contact_phone', 'phone']);
    if ($email !== '') {
        $parts[] = $email;
    }
    if ($phone !== '') {
        $parts[] = $phone;
    }
    return implode('  |  ', $parts);
}

/* ---------- small building blocks ---------- */

function tplGreeting(string $name): string
{
    return '<p style="margin:0 0 8px;font-size:16px;font-weight:700;color:#0F172A;' . tplFont() . '">Hi ' . tplE($name) . ',</p>';
}

function tplParagraph(string $text): string
{
    if (trim($text) === '') {
        return '';
    }
    return '<p style="margin:0 0 14px;font-size:15px;line-height:1.6;color:#334155;' . tplFont() . '">'
        . nl2br(tplE($text)) . '</p>';
}

function tplButton(string $text, string $url, string $colour): string
{
    if (trim($text) === '') {
        return '';
    }
    $u = trim($url) !== '' ? tplE($url) : '#';
    return '<table cellpadding="0" cellspacing="0" border="0" style="margin:22px 0 4px;"><tr>'
        . '<td bgcolor="' . $colour . '" style="background:' . $colour . ';border-radius:10px;">'
        . '<a href="' . $u . '" style="display:inline-block;padding:13px 30px;color:#FFFFFF;font-weight:700;font-size:15px;text-decoration:none;' . tplFont() . '">'
        . tplE($text) . '</a></td></tr></table>';
}

function tplHeader($college, string $logoSrc, string $badge): string
{
    $colour = tplColour($college);
    $dark = tplShade($colour, -0.28);
    $name = tplE(tplCollegeName($college));

    $logo = '';
    if ($logoSrc !== '') {
        $logo = '<td width="66" valign="middle" style="padding-right:14px;">'
            . '<table cellpadding="0" cellspacing="0" border="0"><tr>'
            . '<td bgcolor="#FFFFFF" style="background:#FFFFFF;border-radius:12px;padding:6px;">'
            . '<img src="' . tplE($logoSrc) . '" width="48" height="48" alt="" style="display:block;width:48px;height:48px;border:0;">'
            . '</td></tr></table></td>';
    }

    return '<tr><td bgcolor="' . $colour . '" style="background:' . $colour . ';background-image:linear-gradient(135deg,' . $colour . ',' . $dark . ');padding:22px 28px;">'
        . '<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'
        . $logo
        . '<td valign="middle" style="color:#FFFFFF;' . tplFont() . '">'
        . '<div style="font-size:20px;font-weight:800;line-height:1.2;">' . $name . '</div>'
        . '<div style="margin-top:4px;font-size:11px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:#FFFFFF;">' . tplE($badge) . '</div>'
        . '</td></tr></table></td></tr>';
}

function tplFooter($college): string
{
    $sender = tplE(tplSenderName($college));
    $contact = tplContact($college);
    $contactHtml = $contact !== ''
        ? '<div style="margin-top:6px;font-weight:600;color:#475569;">' . tplE($contact) . '</div>'
        : '';
    return '<tr><td bgcolor="#F8FAFC" align="center" style="background:#F8FAFC;border-top:1px solid #E2E8F0;padding:20px 28px;font-size:12px;color:#64748B;' . tplFont() . '">'
        . '<div style="font-weight:700;color:#334155;">' . $sender . '</div>'
        . $contactHtml
        . '<div style="margin-top:10px;">This is an automated message from ' . tplE(tplCollegeName($college)) . '.</div>'
        . '</td></tr>';
}

/* ---------- design 1: WELCOME ---------- */

function tplWelcomeBody($college, string $name, array $d): string
{
    $colour = tplColour($college);
    $pale = tplShade($colour, 0.9);
    $dark = tplShade($colour, -0.35);

    $heading = tplPick($d, ['heading'], 'Welcome aboard, ' . $name . '!');
    $message = tplPick($d, ['message'], 'Your account is ready. We are excited to have you with ' . tplCollegeName($college) . '.');
    $email = tplPick($d, ['email']);
    $steps = tplPick($d, ['steps'], "Sign in with the login shown above.\nComplete your profile.\nGet ready for your first exam.");
    $btnText = array_key_exists('button_text', $d) ? trim((string)$d['button_text']) : 'Get Started';
    $btnUrl = tplPick($d, ['button_url'], '#');

    $hero = '<tr><td align="center" bgcolor="' . $pale . '" style="background:' . $pale . ';padding:30px 28px 24px;">'
        . '<div style="font-size:46px;line-height:1;">&#127881;</div>'
        . '<div style="margin-top:10px;font-size:26px;font-weight:800;line-height:1.25;color:' . $dark . ';' . tplFont() . '">' . tplE($heading) . '</div>'
        . '</td></tr>';

    $loginCard = '';
    if ($email !== '') {
        $loginCard = '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:18px 0;border:1px dashed ' . $colour . ';border-radius:12px;">'
            . '<tr><td style="padding:14px 18px;' . tplFont() . '">'
            . '<div style="font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#64748B;">Your login</div>'
            . '<div style="margin-top:4px;font-size:17px;font-weight:700;color:#0F172A;">' . tplE($email) . '</div>'
            . '</td></tr></table>';
    }

    $stepRows = '';
    foreach (tplLines($steps) as $i => $line) {
        $stepRows .= '<tr>'
            . '<td width="34" valign="top" style="padding:6px 0;">'
            . '<div style="width:26px;height:26px;line-height:26px;border-radius:13px;background:' . $colour . ';color:#FFFFFF;text-align:center;font-size:13px;font-weight:700;' . tplFont() . '">' . ($i + 1) . '</div></td>'
            . '<td valign="top" style="padding:6px 0 6px 8px;font-size:14px;line-height:1.5;color:#334155;' . tplFont() . '">' . tplE($line) . '</td>'
            . '</tr>';
    }
    $stepsHtml = $stepRows !== ''
        ? '<div style="margin:6px 0 4px;font-size:13px;font-weight:800;letter-spacing:.8px;text-transform:uppercase;color:#0F172A;' . tplFont() . '">Getting started</div>'
          . '<table width="100%" cellpadding="0" cellspacing="0" border="0">' . $stepRows . '</table>'
        : '';

    return $hero
        . '<tr><td style="padding:26px 28px 28px;">'
        . tplGreeting($name)
        . tplParagraph($message)
        . $loginCard
        . $stepsHtml
        . tplButton($btnText, $btnUrl, $colour)
        . '</td></tr>';
}

/* ---------- design 2: EXAM RESULT ---------- */

function tplResultBody($college, string $name, array $d): string
{
    $exam = tplPick($d, ['exam'], 'Exam');
    $score = (float)($d['score'] ?? 0);
    $total = (float)($d['total'] ?? 0);
    $pct = $total > 0 ? (int)max(0, min(100, round($score / $total * 100))) : 0;
    $passMark = (isset($d['pass_mark']) && is_numeric($d['pass_mark'])) ? (float)$d['pass_mark'] : 40;
    $passed = $pct >= $passMark;

    $accent = $passed ? '#15803D' : '#C2410C';
    $pale = $passed ? '#F0FDF4' : '#FFF7ED';
    $label = $passed ? 'PASSED' : 'NEEDS IMPROVEMENT';
    $emoji = $passed ? '&#127942;' : '&#128170;';

    $heading = tplPick($d, ['heading'], 'Your ' . $exam . ' result');
    $defaultMsg = $passed
        ? 'Congratulations on completing your exam. Here is how you did.'
        : 'Thank you for taking the exam. Keep practising, you can do even better next time.';
    $message = tplPick($d, ['message'], $defaultMsg);
    $btnText = array_key_exists('button_text', $d) ? trim((string)$d['button_text']) : '';
    $btnUrl = tplPick($d, ['button_url'], '#');

    $hero = '<tr><td align="center" bgcolor="' . $pale . '" style="background:' . $pale . ';padding:28px 28px 22px;">'
        . '<div style="font-size:44px;line-height:1;">' . $emoji . '</div>'
        . '<div style="margin-top:10px;font-size:24px;font-weight:800;line-height:1.25;color:#0F172A;' . tplFont() . '">' . tplE($heading) . '</div>'
        . '</td></tr>';

    $bar = '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:14px;"><tr>';
    if ($pct > 0) {
        $bar .= '<td width="' . $pct . '%" height="12" bgcolor="' . $accent . '" style="background:' . $accent . ';height:12px;line-height:12px;font-size:1px;border-radius:6px;">&nbsp;</td>';
    }
    if ($pct < 100) {
        $bar .= '<td height="12" bgcolor="#E2E8F0" style="background:#E2E8F0;height:12px;line-height:12px;font-size:1px;border-radius:6px;">&nbsp;</td>';
    }
    $bar .= '</tr></table>';

    $card = '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:18px 0 6px;border:2px solid ' . $accent . ';border-radius:16px;">'
        . '<tr><td align="center" style="padding:22px 20px;' . tplFont() . '">'
        . '<div style="font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#64748B;">' . tplE($exam) . '</div>'
        . '<div style="margin-top:10px;font-size:44px;font-weight:800;line-height:1;color:' . $accent . ';">' . tplNum($score) . ' <span style="font-size:24px;color:#94A3B8;">/ ' . tplNum($total) . '</span></div>'
        . '<div style="margin-top:8px;font-size:15px;font-weight:700;color:#334155;">' . $pct . '% score</div>'
        . '<div style="margin-top:12px;"><span style="display:inline-block;padding:6px 16px;border-radius:999px;background:' . $accent . ';color:#FFFFFF;font-size:12px;font-weight:800;letter-spacing:1px;">' . $label . '</span></div>'
        . $bar
        . '</td></tr></table>';

    return $hero
        . '<tr><td style="padding:26px 28px 28px;">'
        . tplGreeting($name)
        . tplParagraph($message)
        . $card
        . tplButton($btnText, $btnUrl, $accent)
        . '</td></tr>';
}

/* ---------- design 3: DISQUALIFICATION ---------- */

function tplDetailRow(string $label, string $value): string
{
    if (trim($value) === '') {
        return '';
    }
    return '<tr>'
        . '<td width="110" valign="top" style="padding:8px 0;font-size:12px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:#64748B;border-bottom:1px solid #FECACA;' . tplFont() . '">' . tplE($label) . '</td>'
        . '<td valign="top" style="padding:8px 0;font-size:14px;font-weight:600;color:#0F172A;border-bottom:1px solid #FECACA;' . tplFont() . '">' . tplE($value) . '</td>'
        . '</tr>';
}

function tplDisqualificationBody($college, string $name, array $d): string
{
    $red = '#B91C1C';
    $exam = tplPick($d, ['exam'], 'your exam');
    $heading = tplPick($d, ['heading'], 'Your exam has been ended');
    $reason = tplPick($d, ['reason'], 'Violation of exam rules');
    $date = tplPick($d, ['date']);
    $message = tplPick($d, ['message'], 'Your ' . $exam . ' was ended because the exam rules were violated. The attempt cannot be continued.');
    $next = tplPick($d, ['steps'], "Your attempt has been recorded and reported to the college.\nIf you believe this is a mistake, contact the team using the details below.\nYou will be informed if a re-attempt is allowed.");
    $btnText = array_key_exists('button_text', $d) ? trim((string)$d['button_text']) : '';
    $btnUrl = tplPick($d, ['button_url'], '#');

    $alert = '<tr><td bgcolor="#FEF2F2" style="background:#FEF2F2;border-bottom:3px solid ' . $red . ';padding:22px 28px;">'
        . '<table cellpadding="0" cellspacing="0" border="0"><tr>'
        . '<td valign="middle" style="font-size:36px;padding-right:14px;">&#9888;&#65039;</td>'
        . '<td valign="middle" style="' . tplFont() . '">'
        . '<div style="font-size:11px;font-weight:800;letter-spacing:1.2px;text-transform:uppercase;color:' . $red . ';">Exam ended</div>'
        . '<div style="margin-top:3px;font-size:22px;font-weight:800;line-height:1.25;color:#7F1D1D;">' . tplE($heading) . '</div>'
        . '</td></tr></table></td></tr>';

    $details = '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:14px 0 18px;background:#FEF2F2;border-radius:12px;"><tr><td style="padding:6px 18px 10px;">'
        . '<table width="100%" cellpadding="0" cellspacing="0" border="0">'
        . tplDetailRow('Exam', $exam)
        . tplDetailRow('Date', $date)
        . tplDetailRow('Reason', $reason)
        . '</table></td></tr></table>';

    $nextRows = '';
    foreach (tplLines($next) as $line) {
        $nextRows .= '<tr>'
            . '<td width="20" valign="top" style="padding:5px 0;color:' . $red . ';font-size:16px;font-weight:800;' . tplFont() . '">&#8226;</td>'
            . '<td valign="top" style="padding:5px 0;font-size:14px;line-height:1.5;color:#334155;' . tplFont() . '">' . tplE($line) . '</td>'
            . '</tr>';
    }
    $nextHtml = $nextRows !== ''
        ? '<div style="margin:4px 0 4px;font-size:13px;font-weight:800;letter-spacing:.8px;text-transform:uppercase;color:#0F172A;' . tplFont() . '">What happens next</div>'
          . '<table width="100%" cellpadding="0" cellspacing="0" border="0">' . $nextRows . '</table>'
        : '';

    return $alert
        . '<tr><td style="padding:26px 28px 28px;">'
        . tplGreeting($name)
        . tplParagraph($message)
        . $details
        . $nextHtml
        . tplButton($btnText, $btnUrl, $red)
        . '</td></tr>';
}

/* ---------- public API ---------- */

/** Subject line for a type. A custom 'subject' in $d wins. */
function tplDefaultSubject(string $type, $college, array $d): string
{
    $custom = tplPick($d, ['subject']);
    if ($custom !== '') {
        return $custom;
    }
    $c = tplCollegeName($college);
    $exam = tplPick($d, ['exam'], 'your exam');
    if ($type === 'welcome') {
        return 'Welcome to ' . $c;
    }
    if ($type === 'result') {
        return 'Your result: ' . $exam;
    }
    if ($type === 'disqualification') {
        return 'Disqualification notice: ' . $exam;
    }
    return 'Notification from ' . $c;
}

/**
 * The email design (no <html> wrapper). Use for previews inside a page.
 * $type: welcome | result | disqualification
 * $d keys: email, exam, score, total, pass_mark, heading, message, steps,
 *          reason, date, button_text, button_url, subject
 */
function renderTypedEmail(string $type, $college, string $studentName, array $d, string $logoSrc = ''): string
{
    $badges = ['welcome' => 'Welcome', 'result' => 'Exam Result', 'disqualification' => 'Important Notice'];
    $badge = $badges[$type] ?? 'Notification';

    if ($type === 'welcome') {
        $body = tplWelcomeBody($college, $studentName, $d);
    } elseif ($type === 'result') {
        $body = tplResultBody($college, $studentName, $d);
    } elseif ($type === 'disqualification') {
        $body = tplDisqualificationBody($college, $studentName, $d);
    } else {
        $body = '<tr><td style="padding:28px;' . tplFont() . '">' . tplGreeting($studentName) . '</td></tr>';
    }

    return '<table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#F1F5F9" style="background:#F1F5F9;"><tr>'
        . '<td align="center" style="padding:24px 12px;">'
        . '<table width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background:#FFFFFF;border-radius:16px;overflow:hidden;">'
        . tplHeader($college, $logoSrc, $badge)
        . $body
        . tplFooter($college)
        . '</table></td></tr></table>';
}

/** Full HTML document, for sending and for iframe previews. */
function wrapEmailDocument(string $inner, string $title = ''): string
{
    return '<!DOCTYPE html><html><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>' . tplE($title) . '</title></head>'
        . '<body style="margin:0;padding:0;background:#F1F5F9;">' . $inner . '</body></html>';
}