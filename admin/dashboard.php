<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

$one = function ($sql) use ($pdo) { return (int)$pdo->query($sql)->fetchColumn(); };
$nColleges = $one("SELECT COUNT(*) FROM colleges");
$nStudents = $one("SELECT COUNT(*) FROM students");
$nSent     = $one("SELECT COUNT(*) FROM email_log WHERE status = 'sent'");
$nFailed   = $one("SELECT COUNT(*) FROM email_log WHERE status = 'failed'");
$rate      = ($nSent + $nFailed) > 0 ? (int)round($nSent / ($nSent + $nFailed) * 100) : 0;

$typeLabels = ['welcome' => 'Welcome', 'result' => 'Exam Result', 'disqualification' => 'Disqualification'];
$typeColors = ['welcome' => '#15803D', 'result' => '#F58220', 'disqualification' => '#B91C1C'];
$byType = ['welcome' => 0, 'result' => 0, 'disqualification' => 0];
foreach ($pdo->query("SELECT email_type, COUNT(*) AS c FROM email_log GROUP BY email_type")->fetchAll() as $r) {
    if (isset($byType[$r['email_type']])) {
        $byType[$r['email_type']] = (int)$r['c'];
    }
}
$maxType = max(1, max($byType));

$byCollege = $pdo->query(
    "SELECT c.name, c.brand_color AS colour, COUNT(l.id) AS c
     FROM colleges c LEFT JOIN email_log l ON l.college_id = c.id
     GROUP BY c.id, c.name, c.brand_color ORDER BY c DESC"
)->fetchAll();
$maxCollege = 1;
foreach ($byCollege as $r) {
    $maxCollege = max($maxCollege, (int)$r['c']);
}

$dayMap = [];
foreach ($pdo->query("SELECT DATE(created_at) AS d, COUNT(*) AS c FROM email_log WHERE created_at >= (CURDATE() - INTERVAL 6 DAY) GROUP BY DATE(created_at)")->fetchAll() as $r) {
    $dayMap[$r['d']] = (int)$r['c'];
}
$days = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $days[] = ['label' => date('D', strtotime($d)), 'c' => $dayMap[$d] ?? 0];
}
$maxDay = max(1, max(array_column($days, 'c')));

$recent = $pdo->query(
    "SELECT l.email_type, l.status, l.created_at, s.name AS student
     FROM email_log l LEFT JOIN students s ON s.id = l.student_id
     ORDER BY l.id DESC LIMIT 6"
)->fetchAll();

$stats = [
    ['Colleges',     $nColleges, '',  'fa-building-columns', '#F58220', '#FFF7ED'],
    ['Students',     $nStudents, '',  'fa-user-graduate',    '#2563EB', '#EFF6FF'],
    ['Emails sent',  $nSent,     '',  'fa-paper-plane',      '#15803D', '#F0FDF4'],
    ['Failed',       $nFailed,   '',  'fa-triangle-exclamation', '#B91C1C', '#FEF2F2'],
    ['Success rate', $rate,      '%', 'fa-circle-check',     '#7C3AED', '#F5F3FF'],
];

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require __DIR__ . '/../includes/header.php';
?>
<style>
  @keyframes dashGrow { from { width: 0; } }
  @keyframes dashRise { from { height: 0; } }
  .dash-hero { background: linear-gradient(135deg, #F58220, #EA580C); color: #FFFFFF; border-radius: 20px; padding: 28px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; }
  .dash-hero h2 { margin: 0 0 6px; font-family: Outfit, sans-serif; font-size: 26px; font-weight: 800; }
  .dash-hero p { margin: 0; opacity: .95; font-size: 14px; }
  .dash-hero .acts { display: flex; gap: 10px; flex-wrap: wrap; }
  .dash-hero .acts a { background: #FFFFFF; color: #EA580C; padding: 10px 16px; border-radius: 10px; font-weight: 700; font-size: 13px; text-decoration: none; }
  .dash-hero .acts a.ghost { background: rgba(255,255,255,.18); color: #FFFFFF; }
  .dash-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 14px; margin-bottom: 20px; }
  .dash-stat { background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; padding: 18px; box-shadow: 0 2px 8px rgba(15,23,42,.05); transition: transform .2s, box-shadow .2s; }
  .dash-stat:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(15,23,42,.10); }
  .dash-ico { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-bottom: 12px; }
  .dash-num { font-family: Outfit, sans-serif; font-size: 32px; font-weight: 800; line-height: 1; color: #0F172A; }
  .dash-lbl { margin-top: 6px; font-size: 12px; font-weight: 600; color: #64748B; text-transform: uppercase; letter-spacing: .6px; }
  .dash-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 320px), 1fr)); gap: 16px; margin-bottom: 16px; }
  .dash-row { margin-bottom: 14px; }
  .dash-row .top { display: flex; justify-content: space-between; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
  .dash-track { background: #F1F5F9; border-radius: 999px; height: 12px; overflow: hidden; }
  .dash-fill { height: 12px; border-radius: 999px; animation: dashGrow .9s ease-out; }
  .dash-cols { display: flex; align-items: flex-end; gap: 10px; height: 150px; margin-top: 10px; }
  .dash-col { flex: 1; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; height: 100%; font-size: 11px; font-weight: 600; color: #64748B; }
  .dash-bar { width: 100%; max-width: 34px; border-radius: 8px 8px 0 0; background: linear-gradient(180deg, #F58220, #EA580C); animation: dashRise .9s ease-out; min-height: 3px; }
  .dash-act { display: flex; align-items: center; gap: 10px; padding: 9px 0; border-bottom: 1px solid #F1F5F9; font-size: 13px; }
  .dash-act:last-child { border-bottom: 0; }
  .dash-dot { width: 10px; height: 10px; border-radius: 50%; flex: none; }
  .dash-pill { margin-left: auto; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; }
</style>

<div class="dash-hero">
  <div>
    <h2>Branded Email Control Center</h2>
    <p>Every email carries the student's own college logo, colour and name.</p>
  </div>
  <div class="acts">
    <a href="send_email.php"><i class="fa-solid fa-paper-plane"></i> Send email</a>
    <a class="ghost" href="exam_demo.php"><i class="fa-solid fa-user-graduate"></i> Exam demo</a>
  </div>
</div>

<div class="dash-stats">
  <?php foreach ($stats as $s): ?>
    <div class="dash-stat">
      <div class="dash-ico" style="background:<?= $s[5] ?>;color:<?= $s[4] ?>;"><i class="fa-solid <?= $s[3] ?>"></i></div>
      <div class="dash-num" data-count="<?= (int)$s[1] ?>" data-suffix="<?= h($s[2]) ?>"><?= (int)$s[1] . h($s[2]) ?></div>
      <div class="dash-lbl"><?= h($s[0]) ?></div>
    </div>
  <?php endforeach; ?>
</div>

<div class="dash-grid">
  <div class="card">
    <h3 style="margin-top:0">Emails by type</h3>
    <?php foreach ($byType as $k => $c): ?>
      <div class="dash-row">
        <div class="top"><span><?= h($typeLabels[$k]) ?></span><span><?= (int)$c ?></span></div>
        <div class="dash-track"><div class="dash-fill" style="width:<?= (int)round($c / $maxType * 100) ?>%;background:<?= $typeColors[$k] ?>;"></div></div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <h3 style="margin-top:0">Emails by college</h3>
    <?php if (!$byCollege): ?><p style="color:#64748B">No colleges yet.</p><?php endif; ?>
    <?php foreach ($byCollege as $r): $col = preg_match('/^#[0-9a-fA-F]{6}$/', (string)$r['colour']) ? $r['colour'] : '#475569'; ?>
      <div class="dash-row">
        <div class="top"><span><?= h($r['name']) ?></span><span><?= (int)$r['c'] ?></span></div>
        <div class="dash-track"><div class="dash-fill" style="width:<?= (int)round($r['c'] / $maxCollege * 100) ?>%;background:<?= h($col) ?>;"></div></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="dash-grid">
  <div class="card">
    <h3 style="margin-top:0">Last 7 days</h3>
    <div class="dash-cols">
      <?php foreach ($days as $d): ?>
        <div class="dash-col">
          <span><?= (int)$d['c'] ?></span>
          <div class="dash-bar" style="height:<?= (int)round($d['c'] / $maxDay * 100) ?>%;"></div>
          <span style="margin-top:6px"><?= h($d['label']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card">
    <h3 style="margin-top:0">Recent activity</h3>
    <?php if (!$recent): ?><p style="color:#64748B">No emails sent yet.</p><?php endif; ?>
    <?php foreach ($recent as $r):
        $ok = $r['status'] === 'sent';
        $tc = $typeColors[$r['email_type']] ?? '#64748B';
    ?>
      <div class="dash-act">
        <span class="dash-dot" style="background:<?= $tc ?>"></span>
        <span><strong><?= h($r['student'] ?? 'Unknown') ?></strong> <span style="color:#64748B">&middot; <?= h($typeLabels[$r['email_type']] ?? $r['email_type']) ?></span></span>
        <span class="dash-pill" style="background:<?= $ok ? '#F0FDF4' : '#FEF2F2' ?>;color:<?= $ok ? '#15803D' : '#B91C1C' ?>;"><?= h($r['status']) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
document.querySelectorAll('[data-count]').forEach(function (el) {
  var to = Number(el.dataset.count), suffix = el.dataset.suffix || '', n = 0;
  var step = Math.max(1, Math.ceil(to / 30));
  if (to === 0) return;
  var t = setInterval(function () {
    n = Math.min(to, n + step);
    el.textContent = n + suffix;
    if (n >= to) clearInterval(t);
  }, 25);
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
