<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

// Turns <img src="http://localhost/.../logo.png"> into an image
// attached inside the email, so it shows in any inbox.
function embedLocalImages(PHPMailer $mail, string $html): string {
    $root = realpath(__DIR__ . '/../../');
    return preg_replace_callback(
        '/<img([^>]*?)src=["\']([^"\']+)["\']/i',
        function ($m) use ($mail, $root) {
            $src = $m[2];
            if (strpos($src, 'data:') === 0 || strpos($src, 'cid:') === 0) {
                return $m[0];
            }
            $path = parse_url($src, PHP_URL_PATH) ?: '';
            $pos = strpos($path, '/branded-email/');
            $rel = ($pos !== false)
                ? substr($path, $pos + strlen('/branded-email/'))
                : ltrim($path, '/');
            $file = realpath($root . '/' . $rel);
            if (!$file || strpos($file, $root) !== 0 || !is_file($file)) {
                return $m[0]; // file not found, leave as it is
            }
            $cid = 'img' . md5($file);
            $mail->addEmbeddedImage($file, $cid);
            return '<img' . $m[1] . 'src="cid:' . $cid . '"';
        },
        $html
    );
}

function sendMail(string $to, string $subject, string $html): array {
    $cfg = require __DIR__ . '/../../config/mail.php';
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host     = $cfg['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $cfg['username'];
        $mail->Password = $cfg['password'];
        $mail->Port     = $cfg['port'];
        $mail->setFrom($cfg['default_from_email'], $cfg['default_from_name']);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = embedLocalImages($mail, $html);
        $mail->send();
        return ['ok' => true, 'error' => null];
    } catch (Exception $e) {
        return ['ok' => false, 'error' => $mail->ErrorInfo];
    }
}

function sendAndLog(PDO $pdo, int $logId, int $maxAttempts = 3): bool {
    $stmt = $pdo->prepare("SELECT * FROM email_log WHERE id = ?");
    $stmt->execute([$logId]);
    $log = $stmt->fetch();
    if (!$log) return false;

    $attempts = (int)$log['attempts'];
    $ok = false;
    $error = null;

    for ($i = 1; $i <= $maxAttempts; $i++) {
        $attempts++;
        $result = sendMail($log['recipient'], $log['subject'], $log['body']);
        $ok = $result['ok'];
        $error = $result['error'];
        if ($ok) break;
        sleep(1);
    }

    $upd = $pdo->prepare("UPDATE email_log SET attempts = ?, status = ?, error_message = ?, last_attempt_at = NOW() WHERE id = ?");
    $upd->execute([$attempts, $ok ? 'sent' : 'failed', $ok ? null : $error, $logId]);
    return $ok;
}