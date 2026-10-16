
<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/events.php';

requireLogin();

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'error' => 'Use POST.'
    ]);
    exit;
}

$event = (string)($_POST['event'] ?? '');

if (!in_array($event, ['exam.completed', 'cheating.detected'], true)) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'Unknown event.'
    ]);
    exit;
}

$studentId = (int)($_POST['student_id'] ?? 0);
$exam = trim((string)($_POST['exam'] ?? 'Exam'));

if ($studentId <= 0 || $exam === '') {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'Valid student and exam name are required.'
    ]);
    exit;
}

try {
    // Save the completed exam result before sending the email.
    if ($event === 'exam.completed') {
        $scoreInput = $_POST['score'] ?? null;
        $totalInput = $_POST['total'] ?? null;

        if (
            !is_numeric($scoreInput) ||
            !is_numeric($totalInput) ||
            (float)$scoreInput < 0 ||
            (float)$totalInput <= 0 ||
            (float)$scoreInput > (float)$totalInput
        ) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Invalid score or total marks.'
            ]);
            exit;
        }

        // Confirm that the student exists.
        $check = $pdo->prepare(
            'SELECT id FROM students WHERE id = ?'
        );
        $check->execute([$studentId]);

        if (!$check->fetch()) {
            http_response_code(404);
            echo json_encode([
                'ok' => false,
                'error' => 'Student not found.'
            ]);
            exit;
        }

        $score = (float)$scoreInput;
        $total = (float)$totalInput;
        $percentage = round(($score / $total) * 100, 2);

        // Keep the same 40% pass threshold used by the email template.
        $status = $percentage >= 40 ? 'Pass' : 'Fail';

        $stmt = $pdo->prepare(
            'INSERT INTO exam_results
                (student_id, exam_name, score, total_marks,
                 percentage, result_status)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $studentId,
            $exam,
            $score,
            $total,
            $percentage,
            $status
        ]);
    }

    // Preserve the existing branded email workflow.
    $res = handleEvent($event, [
        'student_id' => $studentId,
        'exam'       => $exam,
        'reason'     => trim((string)(
            $_POST['reason'] ?? ''
        )),
        'score'      => $_POST['score'] ?? 0,
        'total'      => $_POST['total'] ?? 1,
    ]);

    if ($event === 'exam.completed' && !empty($res['ok'])) {
        $res['result_saved'] = true;
        $res['score'] = $score;
        $res['total'] = $total;
        $res['percentage'] = $percentage;
        $res['result_status'] = $status;
    }

    echo json_encode($res);

} catch (Throwable $e) {
    error_log('Exam event error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Could not process the exam. Check the PHP error log.'
    ]);
}
