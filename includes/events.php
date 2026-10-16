<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/lib/mailer.php';
require_once __DIR__ . '/lib/email_builder.php';

// Builds the branded email for one student (new designs) and sends it.
function sendBrandedEmail($studentId, $type, array $data) {
    global $pdo;

    $r = buildEmail($pdo, [
        'type'       => $type,
        'student_id' => (int)$studentId,
        'exam'       => $data['exam']   ?? 'Exam',
        'score'      => $data['score']  ?? '',
        'total'      => $data['total']  ?? '',
        'reason'     => $data['reason'] ?? '',
    ], true);

    if ($r['errors']) {
        return ['ok' => false, 'error' => implode(' ', $r['errors'])];
    }
    $student = $r['student'];

    // Same email to the same student in the last 10 minutes? Do not send it twice.
    $dup = $pdo->prepare("SELECT id FROM email_log WHERE student_id = ? AND email_type = ? AND subject = ? AND status = 'sent' AND created_at > (NOW() - INTERVAL 10 MINUTE) LIMIT 1");
    $dup->execute([$student['id'], $r['type'], $r['subject']]);
    if ($dup->fetch()) {
        return ['ok' => true, 'status' => 'duplicate', 'message' => 'Same email already sent in the last 10 minutes.'];
    }

    // Log first, then send (so a failure can be retried).
    $stmt = $pdo->prepare("INSERT INTO email_log (student_id, college_id, email_type, recipient, subject, body, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$student['id'], (int)($student['college_id'] ?? 0), $r['type'], $student['email'], $r['subject'], $r['html']]);
    $logId = (int)$pdo->lastInsertId();

    $sent = sendAndLog($pdo, $logId);
    return $sent
        ? ['ok' => true, 'log_id' => $logId, 'status' => 'sent']
        : ['ok' => false, 'log_id' => $logId, 'error' => 'Sending failed. See the Email Log.'];
}

// Maps an event name to an email type, then sends it.
function handleEvent($event, array $p) {
    $map = [
        'student.created'   => 'welcome',
        'exam.completed'    => 'result',
        'cheating.detected' => 'disqualification',
    ];
    if (!isset($map[$event])) {
        return ['ok' => false, 'error' => 'Unknown event'];
    }

    $studentId = (int)($p['student_id'] ?? 0);
    if ($studentId <= 0) {
        return ['ok' => false, 'error' => 'student_id required'];
    }

    $data = [];
    if ($event === 'exam.completed') {
        $data['exam']  = $p['exam'] ?? 'Exam';
        $data['score'] = is_numeric($p['score'] ?? null) ? $p['score'] : 0;
        $data['total'] = (is_numeric($p['total'] ?? null) && $p['total'] > 0) ? $p['total'] : 1;
    }
    if ($event === 'cheating.detected') {
        $data['exam']   = $p['exam'] ?? 'Exam';
        $data['reason'] = $p['reason'] ?? 'Rule violation detected';
    }

    return sendBrandedEmail($studentId, $map[$event], $data);
}