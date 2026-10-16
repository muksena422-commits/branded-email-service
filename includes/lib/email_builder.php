<?php
/**
 * email_builder.php
 * One function that turns form input into a ready-to-send branded email.
 * Used by: admin/send_email.php (send), admin/preview.php (live preview),
 * and later api/event.php (automatic sending).
 */
require_once __DIR__ . '/email_templates.php';

function emailTypes(): array
{
    return [
        'welcome'          => 'Welcome',
        'result'           => 'Exam Result',
        'disqualification' => 'Disqualification',
    ];
}

/**
 * $in keys: type, student_id, exam, score, total, reason, subject, heading, message
 * $forEmail = false -> logo path works inside the admin page (preview)
 * $forEmail = true  -> logo URL used inside the email that is sent
 *
 * Returns: errors (array), student, college, type, subject, html (full document)
 */
function buildEmail(PDO $pdo, array $in, bool $forEmail = false): array
{
    $out = ['errors' => [], 'student' => null, 'college' => null, 'type' => '', 'subject' => '', 'html' => ''];

    $types = emailTypes();
    $type = trim((string)($in['type'] ?? ''));
    if (!isset($types[$type])) {
        $out['errors'][] = 'Please choose an email type.';
    }

    $student = null;
    $sid = (int)($in['student_id'] ?? 0);
    if ($sid > 0) {
        $st = $pdo->prepare('SELECT id, name, email, college_id FROM students WHERE id = ?');
        $st->execute([$sid]);
        $student = $st->fetch() ?: null;
    }
    if (!$student) {
        $out['errors'][] = 'Choose a student to see the live preview.';
    }

    $exam  = trim((string)($in['exam'] ?? ''));
    $score = trim((string)($in['score'] ?? ''));
    $total = trim((string)($in['total'] ?? ''));

    if ($type === 'result' || $type === 'disqualification') {
        if ($exam === '') {
            $out['errors'][] = 'Enter the exam name.';
        }
    }
    if ($type === 'result') {
        if (!is_numeric($score) || !is_numeric($total) || (float)$total <= 0) {
            $out['errors'][] = 'Enter a valid score and total marks.';
        } elseif ((float)$score > (float)$total) {
            $out['errors'][] = 'Score cannot be greater than the total.';
        }
    }
    if ($out['errors']) {
        return $out;
    }

    // the student's college (null = plain default design)
    $college = null;
    if (!empty($student['college_id'])) {
        $cs = $pdo->prepare('SELECT * FROM colleges WHERE id = ?');
        $cs->execute([$student['college_id']]);
        $college = $cs->fetch() ?: null;
    }

    $data = [
        'email'  => $student['email'],
        'exam'   => $exam,
        'score'  => $score,
        'total'  => $total,
        'reason' => trim((string)($in['reason'] ?? '')),
        'date'   => date('d M Y, h:i A'),
    ];
    foreach (['subject', 'heading', 'message'] as $k) {
        $v = trim((string)($in[$k] ?? ''));
        if ($v !== '') {
            $data[$k] = mb_substr($v, 0, 600);
        }
    }

    $logo = '';
    if (!empty($college['logo_path'])) {
        $logo = $forEmail
            ? 'http://localhost/branded-email/' . $college['logo_path']
            : '../' . $college['logo_path'];
    }

    $subject = tplDefaultSubject($type, $college, $data);
    $html = wrapEmailDocument(renderTypedEmail($type, $college, $student['name'], $data, $logo), $subject);

    $out['student'] = $student;
    $out['college'] = $college;
    $out['type'] = $type;
    $out['subject'] = $subject;
    $out['html'] = $html;
    return $out;
}
