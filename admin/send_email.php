<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/lib/mailer.php';
require_once __DIR__ . '/../includes/lib/email_builder.php';
requireLogin();

$students = $pdo->query("SELECT id, name, email, college_id FROM students ORDER BY name")->fetchAll();
$types = emailTypes();

$errors = [];
$flash = null;
$in = [
    'student_id' => '',
    'type'       => 'result',
    'exam'       => '',
    'score'      => '',
    'total'      => '',
    'reason'     => '',
    'subject'    => '',
    'heading'    => '',
    'message'    => '',
];

// Pre-fill from the link, e.g. send_email.php?type=welcome&student_id=3
if (isset($_GET['type']) && isset($types[$_GET['type']])) {
    $in['type'] = $_GET['type'];
}
if (isset($_GET['student_id'])) {
    $in['student_id'] = (string)(int)$_GET['student_id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    foreach ($in as $k => $v) {
        $in[$k] = trim((string)($_POST[$k] ?? ''));
    }

    $r = buildEmail($pdo, $in, true);
    if ($r['errors']) {
        $errors = $r['errors'];
    } else {
        $student = $r['student'];

        // log first, then send (so a failed email can be retried)
        $stmt = $pdo->prepare("INSERT INTO email_log (student_id, college_id, email_type, recipient, subject, body, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$student['id'], (int)($student['college_id'] ?? 0), $r['type'], $student['email'], $r['subject'], $r['html']]);
        $logId = (int)$pdo->lastInsertId();

        $sent = sendAndLog($pdo, $logId);
        $flash = $sent
            ? ['ok', 'Email sent to ' . $student['email']]
            : ['error', 'Sending failed after 3 attempts. Check the email log.'];
    }
}

$pageTitle = 'Send ' . ($types[$in['type']] ?? '') . ' Email';
$activePage = 'send_' . $in['type'];
require __DIR__ . '/../includes/header.php';

$areaStyle = 'width:100%;min-height:84px;padding:10px 12px;border:1px solid #E2E8F0;border-radius:10px;font:inherit;font-size:13px;resize:vertical;';
?>
<div class="page-head">
  <h1 class="page-title"><?= h($pageTitle) ?></h1>
</div>

<?php if ($errors): ?>
  <div class="alert alert-error">
    <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if ($flash): ?>
  <div class="alert alert-<?= $flash[0] === 'ok' ? 'success' : 'error' ?>"><?= h($flash[1]) ?></div>
<?php endif; ?>

<div class="form-layout">
  <div class="card">
    <form method="POST" id="sendForm">
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

      <div class="form-group" id="reasonGroup">
        <label for="reason">Reason for disqualification</label>
        <input type="text" id="reason" name="reason" value="<?= h($in['reason']) ?>" placeholder="Switched browser tabs 3 times">
      </div>

      <details style="margin:4px 0 18px;">
        <summary style="cursor:pointer;font-weight:600;font-size:13px;color:#EA580C;">Customize the email (optional)</summary>
        <div style="margin-top:12px;">
          <div class="form-group">
            <label for="subject">Subject</label>
            <input type="text" id="subject" name="subject" value="<?= h($in['subject']) ?>" placeholder="Leave blank for the default subject">
          </div>
          <div class="form-group">
            <label for="heading">Heading</label>
            <input type="text" id="heading" name="heading" value="<?= h($in['heading']) ?>" placeholder="Leave blank for the default heading">
          </div>
          <div class="form-group">
            <label for="message">Message</label>
            <textarea id="message" name="message" style="<?= $areaStyle ?>" placeholder="Leave blank for the default message"><?= h($in['message']) ?></textarea>
          </div>
        </div>
      </details>

      <button class="btn" type="submit"><i class="fa-solid fa-paper-plane"></i> Send email</button>
    </form>
  </div>

  <div class="card">
    <h3 style="margin-top:0">Live preview</h3>
    <iframe id="previewFrame" title="Email preview" style="width:100%;min-height:520px;border:1px solid #E2E8F0;border-radius:12px;background:#F8FAFC;"></iframe>
  </div>
</div>

<script>
const form = document.getElementById('sendForm');
const frame = document.getElementById('previewFrame');
const typeSel = document.getElementById('type');
let timer = null;
let controller = null;

function toggleFields() {
  const t = typeSel.value;
  document.getElementById('examGroup').style.display   = (t === 'welcome') ? 'none' : '';
  document.getElementById('scoreGroup').style.display  = (t === 'result') ? '' : 'none';
  document.getElementById('totalGroup').style.display  = (t === 'result') ? '' : 'none';
  document.getElementById('reasonGroup').style.display = (t === 'disqualification') ? '' : 'none';
}

async function refreshPreview() {
  if (controller) controller.abort();
  controller = new AbortController();
  try {
    const res = await fetch('preview.php', { method: 'POST', body: new FormData(form), signal: controller.signal });
    frame.srcdoc = await res.text();
  } catch (err) {
    if (err.name !== 'AbortError') {
      frame.srcdoc = '<p style="font-family:Arial;padding:24px;color:#B91C1C">Preview failed. Check that Apache and MySQL are running.</p>';
    }
  }
}

function schedulePreview() {
  clearTimeout(timer);
  timer = setTimeout(refreshPreview, 250);
}

// fit the preview box to the email height
frame.addEventListener('load', function () {
  try {
    const h = frame.contentDocument.documentElement.scrollHeight;
    frame.style.height = Math.max(520, h + 8) + 'px';
  } catch (e) {}
});

form.addEventListener('input', schedulePreview);
form.addEventListener('change', function () { toggleFields(); schedulePreview(); });

toggleFields();
refreshPreview();
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>