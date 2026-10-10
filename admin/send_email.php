<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/lib/template.php';
require_once __DIR__ . '/../includes/lib/mailer.php';
requireLogin();

$students = $pdo->query("SELECT id, name, email, college_id FROM students ORDER BY name")->fetchAll();
$types = [
    'welcome'          => 'Welcome',
    'result'           => 'Exam Result',
    'disqualification' => 'Disqualification',
];

$errors = [];
$previewHtml = null;
$flash = null;
$in = ['student_id' => '', 'type' => 'result', 'exam' => '', 'score' => '', 'total' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    foreach ($in as $k => $v) {
        $in[$k] = trim($_POST[$k] ?? '');
    }

    // find the student
    $student = null;
    foreach ($students as $s) {
        if ((string)$s['id'] === $in['student_id']) { $student = $s; break; }
    }
    if (!$student) $errors[] = 'Please choose a student.';
    if (!isset($types[$in['type']])) $errors[] = 'Please choose an email type.';

    if ($in['type'] === 'result' || $in['type'] === 'disqualification') {
        if ($in['exam'] === '') $errors[] = 'Exam name is required.';
    }
    if ($in['type'] === 'result') {
        if (!is_numeric($in['score']) || !is_numeric($in['total']) || (float)$in['total'] <= 0) {
            $errors[] = 'Enter a valid score and total.';
        } elseif ((float)$in['score'] > (float)$in['total']) {
            $errors[] = 'Score cannot be greater than the total.';
        }
    }

    if (!$errors) {
        // load the student's college (null = plain default design)
        $college = null;
        if (!empty($student['college_id'])) {
            $stmt = $pdo->prepare("SELECT * FROM colleges WHERE id = ?");
            $stmt->execute([$student['college_id']]);
            $college = $stmt->fetch() ?: null;
        }

        $data = [
            'email' => $student['email'],
            'exam'  => $in['exam'],
            'score' => $in['score'],
            'total' => $in['total'],
        ];

        $logoSrc = ($college && !empty($college['logo_path'])) ? '../' . $college['logo_path'] : '';
        $previewHtml = renderEmail($college, $student['name'], emailContent($in['type'], $data), $logoSrc);
        if (($_POST['action'] ?? '') === 'send') {
            $emailLogo = !empty($college['logo_path']) ? 'http://localhost/branded-email/' . $college['logo_path'] : '';
            $body = renderEmail($college, $student['name'], emailContent($in['type'], $data), $emailLogo);
            $collegeName = $college['name'] ?? 'Exam Platform';
            $subjects = [
                'welcome'          => 'Welcome to ' . $collegeName,
                'result'           => 'Your result: ' . $in['exam'],
                'disqualification' => 'Disqualification notice: ' . $in['exam'],
            ];
            $subject = $subjects[$in['type']];

            $stmt = $pdo->prepare("INSERT INTO email_log (student_id, college_id, email_type, recipient, subject, body, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$student['id'], (int)($student['college_id'] ?? 0), $in['type'], $student['email'], $subject, $body]);
            $logId = (int)$pdo->lastInsertId();

            $sent = sendAndLog($pdo, $logId);
            $flash = $sent
                ? ['ok', 'Email sent to ' . $student['email']]
                : ['error', 'Sending failed after 3 attempts. Check the email log.'];
        }
    }
}

$pageTitle = 'Send Email';
$activePage = 'send';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <h1 class="page-title">Send Email</h1>
</div>

<?php if ($errors): ?>
  <div class="alert alert-error">
    <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="form-layout">
  <div class="card">
    <form method="POST">
      <?= csrfField() ?>
      <div class="form-group">
        <label for="student_id">Student</label>
        <select id="student_id" name="student_id" required>
          <option value="">-- Choose a student --</option>
          <?php foreach ($students as $s): ?>
            <option value="<?= (int)$s['id'] ?>" <?= (string)$s['id'] === $in['student_id'] ? 'selected' : '' ?>>
              <?= h($s['name']) ?> (<?= h($s['email']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="type">Email type</label>
        <select id="type" name="type">
          <?php foreach ($types as $k => $label): ?>
            <option value="<?= h($k) ?>" <?= $k === $in['type'] ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" id="examGroup">
        <label for="exam">Exam name</label>
        <input type="text" id="exam" name="exam" value="<?= h($in['exam']) ?>" placeholder="Java Test">
      </div>
      <div class="form-group" id="scoreGroup">
        <label for="score">Score</label>
        <input type="number" step="any" id="score" name="score" value="<?= h($in['score']) ?>" placeholder="42">
      </div>
      <div class="form-group" id="totalGroup">
        <label for="total">Total marks</label>
        <input type="number" step="any" id="total" name="total" value="<?= h($in['total']) ?>" placeholder="50">
      </div>
            <button class="btn" type="submit" name="action" value="preview">Preview</button>
      <button class="btn" type="submit" name="action" value="send">Send email</button>
    </form>
  </div>

  <div class="card">
    <h3 style="margin-top:0">Preview</h3>
    <?php if ($previewHtml): ?>
      <?= $previewHtml ?>
    <?php else: ?>
      <p style="color:#64748B">Choose a student and email type, then click Preview.</p>
    <?php endif; ?><?php if ($flash): ?>
  <div class="alert alert-<?= $flash[0] === 'ok' ? 'success' : 'error' ?>"><?= h($flash[1]) ?></div>
<?php endif; ?>
  </div>
</div>

<script>
const typeSel = document.getElementById('type');
function toggleFields() {
  const t = typeSel.value;
  document.getElementById('examGroup').style.display  = (t === 'welcome') ? 'none' : '';
  document.getElementById('scoreGroup').style.display = (t === 'result') ? '' : 'none';
  document.getElementById('totalGroup').style.display = (t === 'result') ? '' : 'none';
}
typeSel.addEventListener('change', toggleFields);
toggleFields();
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>

   