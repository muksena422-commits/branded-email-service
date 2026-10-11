<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/lib/mailer.php';
requireLogin();

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'retry') {
    verifyCsrf();
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT status FROM email_log WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row && $row['status'] === 'failed') {
        $ok = sendAndLog($pdo, $id);
        $flash = $ok ? ['ok', 'Retry succeeded.'] : ['error', 'Retry failed again. See the error column.'];
    } else {
        $flash = ['error', 'Only failed emails can be retried.'];
    }
}

$logs = $pdo->query(
    "SELECT l.*, s.name AS student_name, c.name AS college_name
     FROM email_log l
     LEFT JOIN students s ON s.id = l.student_id
     LEFT JOIN colleges c ON c.id = l.college_id
     ORDER BY l.id DESC LIMIT 200"
)->fetchAll();

$pageTitle = 'Email Log';
$activePage = 'log';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <h1 class="page-title">Email Log</h1>
</div>

<?php if ($flash): ?>
  <div class="alert alert-<?= $flash[0] === 'ok' ? 'success' : 'error' ?>"><?= h($flash[1]) ?></div>
<?php endif; ?>

<div class="card" style="overflow-x:auto;">
  <table style="width:100%;border-collapse:collapse;font-size:14px;">
    <thead>
      <tr style="text-align:left;border-bottom:1px solid #E2E8F0;">
        <th style="padding:8px;">ID</th>
        <th style="padding:8px;">Student</th>
        <th style="padding:8px;">College</th>
        <th style="padding:8px;">Recipient</th>
        <th style="padding:8px;">Type</th>
        <th style="padding:8px;">Subject</th>
        <th style="padding:8px;">Status</th>
        <th style="padding:8px;">Attempts</th>
        <th style="padding:8px;">Error</th>
        <th style="padding:8px;">Date</th>
        <th style="padding:8px;">Last attempt</th>
        <th style="padding:8px;"></th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$logs): ?>
        <tr><td colspan="12" style="padding:12px;color:#64748B;">No emails sent yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($logs as $l): ?>
        <tr style="border-bottom:1px solid #F1F5F9;">
          <td style="padding:8px;"><?= (int)$l['id'] ?></td>
          <td style="padding:8px;"><?= h($l['student_name'] ?? '(deleted)') ?></td>
          <td style="padding:8px;"><?= h($l['college_name'] ?? '(deleted)') ?></td>
          <td style="padding:8px;"><?= h($l['recipient']) ?></td>
          <td style="padding:8px;"><?= h($l['email_type']) ?></td>
          <td style="padding:8px;"><?= h($l['subject']) ?></td>
          <td style="padding:8px;font-weight:bold;color:<?= $l['status'] === 'sent' ? '#15803D' : ($l['status'] === 'failed' ? '#B91C1C' : '#64748B') ?>;">
            <?= h($l['status']) ?>
          </td>
          <td style="padding:8px;"><?= (int)$l['attempts'] ?></td>
          <td style="padding:8px;"><?= h($l['error_message'] ?? '') ?></td>
          <td style="padding:8px;"><?= h($l['created_at'] ?? '') ?></td>
          <td style="padding:8px;"><?= h($l['last_attempt_at'] ?? '') ?></td>
          <td style="padding:8px;">
            <?php if ($l['status'] === 'failed'): ?>
              <form method="POST" style="margin:0;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="retry">
                <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
                <button class="btn" type="submit">Retry</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>