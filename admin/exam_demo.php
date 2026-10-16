<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

$students = $pdo->query(
    "SELECT s.id, s.name, s.email, c.name AS college
     FROM students s LEFT JOIN colleges c ON c.id = s.college_id
     ORDER BY s.name"
)->fetchAll();

$questions = [
    ['q' => 'Which language runs on the server in this project?', 'o' => ['PHP', 'Python', 'C#'], 'a' => 0],
    ['q' => 'Which database stores the colleges and students?', 'o' => ['MongoDB', 'MySQL', 'Redis'], 'a' => 1],
    ['q' => 'Which library sends the emails?', 'o' => ['jQuery', 'Bootstrap', 'PHPMailer'], 'a' => 2],
];

$pageTitle = 'Exam Demo';
$activePage = 'exam';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <h1 class="page-title">Exam Demo</h1>
</div>

<div class="card" id="setupCard">
  <h3 style="margin-top:0">Online exam simulator</h3>
  <p style="color:#64748B;margin-top:0">Anti-cheat is on: leaving the exam tab twice ends the exam and the disqualification email is sent automatically. Submitting sends the result email automatically.</p>
  <div class="form-group">
    <label for="student">Student taking the exam</label>
    <select id="student">
      <option value="">-- Choose a student --</option>
      <?php foreach ($students as $s): ?>
        <option value="<?= (int)$s['id'] ?>" data-email="<?= h($s['email']) ?>"><?= h($s['name']) ?> (<?= h($s['college'] ?? 'No college') ?>)</option>
      <?php endforeach; ?>
    </select>
  </div>
  <button class="btn" type="button" id="startBtn"><i class="fa-solid fa-play"></i> Start exam</button>
</div>

<div class="card" id="examCard" style="display:none;">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
    <h3 style="margin:0">Java Test (Demo)</h3>
    <div id="timer" style="font-weight:800;font-size:20px;color:#EA580C;">03:00</div>
  </div>
  <div id="warn" style="display:none;margin:14px 0;padding:12px 14px;border-radius:10px;background:#FEF2F2;color:#B91C1C;font-weight:600;font-size:13px;"></div>
  <div id="qBox"></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;">
    <button class="btn" type="button" id="submitBtn"><i class="fa-solid fa-check"></i> Submit exam</button>
    <button class="btn btn-danger" type="button" id="cheatBtn"><i class="fa-solid fa-user-secret"></i> Simulate cheating</button>
  </div>
</div>

<div class="card" id="resultCard" style="display:none;"></div>

<script>
const QUESTIONS = <?= json_encode($questions, JSON_UNESCAPED_UNICODE) ?>;
const EXAM = 'Java Test (Demo)';
const $ = id => document.getElementById(id);
let running = false, ended = false, warnings = 0, left = 180, tick = null, studentId = 0, studentEmail = '';

function fmt(n) { return String(Math.floor(n / 60)).padStart(2, '0') + ':' + String(n % 60).padStart(2, '0'); }

async function send(fields) {
  const fd = new FormData();
  Object.keys(fields).forEach(k => fd.append(k, fields[k]));
  try {
    const r = await fetch('exam_event.php', { method: 'POST', body: fd });
    return await r.json();
  } catch (e) { return { ok: false, error: 'Could not reach the server.' }; }
}

function showResult(color, title, text) {
  $('examCard').style.display = 'none';
  const c = $('resultCard');
  c.style.display = '';
  c.style.borderTop = '6px solid ' + color;
  c.innerHTML = '<h3 style="margin-top:0;color:' + color + '">' + title + '</h3><p style="margin:0 0 14px">' + text + '</p>' +
    '<a class="btn" href="email_log.php"><i class="fa-solid fa-clock-rotate-left"></i> Open Email Log</a> ' +
    '<button class="btn" type="button" onclick="location.reload()">Run again</button>';
}

function mailNote(res) {
  if (res.ok && res.status === 'duplicate') return 'The same email was already sent in the last 10 minutes, so it was not sent again.';
  if (res.ok) return 'The email was sent to ' + studentEmail + ' automatically. Open Mailtrap to see it.';
  return 'The email could not be sent: ' + (res.error || 'unknown error') + '. Check the Email Log.';
}

function stopExam() { running = false; ended = true; clearInterval(tick); }

async function terminate(reason) {
  if (ended) return;
  stopExam();
  showResult('#B91C1C', 'Exam terminated', 'Reason: ' + reason + '. Sending the disqualification notice...');
  const res = await send({ event: 'cheating.detected', student_id: studentId, exam: EXAM, reason: reason });
  showResult('#B91C1C', 'Exam terminated', 'Reason: ' + reason + '.<br>' + mailNote(res));
}

async function submitExam() {
  if (ended) return;
  stopExam();
  let score = 0;
  QUESTIONS.forEach((q, i) => {
    const pick = document.querySelector('input[name="q' + i + '"]:checked');
    if (pick && Number(pick.value) === q.a) score += 10;
  });
  const total = QUESTIONS.length * 10;
  showResult('#15803D', 'Exam submitted', 'Score: ' + score + ' / ' + total + '. Sending the result email...');
  const res = await send({ event: 'exam.completed', student_id: studentId, exam: EXAM, score: score, total: total });
  showResult('#15803D', 'Exam submitted', 'Score: ' + score + ' / ' + total + '.<br>' + mailNote(res));
}

$('startBtn').addEventListener('click', () => {
  const sel = $('student');
  if (!sel.value) { alert('Choose a student first.'); return; }
  studentId = Number(sel.value);
  studentEmail = sel.options[sel.selectedIndex].dataset.email || '';
  $('qBox').innerHTML = QUESTIONS.map((q, i) =>
    '<div style="margin:14px 0"><div style="font-weight:700;margin-bottom:6px">' + (i + 1) + '. ' + q.q + '</div>' +
    q.o.map((o, j) => '<label style="display:block;padding:6px 0;cursor:pointer"><input type="radio" name="q' + i + '" value="' + j + '"> ' + o + '</label>').join('') + '</div>'
  ).join('');
  $('setupCard').style.display = 'none';
  $('examCard').style.display = '';
  running = true;
  tick = setInterval(() => {
    left--;
    $('timer').textContent = fmt(left);
    if (left <= 0) submitExam();
  }, 1000);
});

document.addEventListener('visibilitychange', () => {
  if (!running || ended) return;
  if (document.hidden) {
    warnings++;
    if (warnings >= 2) { terminate('Left the exam tab 2 times'); return; }
    const w = $('warn');
    w.style.display = '';
    w.textContent = 'Warning 1 of 2: you left the exam tab. Leaving again will end the exam.';
  }
});

$('submitBtn').addEventListener('click', submitExam);
$('cheatBtn').addEventListener('click', () => terminate('Suspicious activity detected (simulated)'));
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
