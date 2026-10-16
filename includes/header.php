<?php
$pageTitle  = $pageTitle  ?? 'Admin';
$activePage = $activePage ?? '';
$flash = getFlash();


$nav = [
    'dashboard' => ['dashboard.php', 'fa-chart-pie', 'Dashboard'],
    'colleges'  => ['colleges.php', 'fa-building-columns', 'Colleges'],
    'students'  => ['students.php', 'fa-user-graduate', 'Students'],
    'exam'      => ['exam_demo.php', 'fa-graduation-cap', 'Exam Demo'],
    'results'   => ['exam_results.php', 'fa-square-poll-vertical', 'Exam Results'],
];

$navLog = ['log', 'email_log.php', 'fa-clock-rotate-left', 'Email Log'];

$sendMenu = [
    'welcome'          => ['send_email.php?type=welcome',          'fa-hand-sparkles',      'Welcome email'],
    'result'           => ['send_email.php?type=result',           'fa-trophy',             'Exam result'],
    'disqualification' => ['send_email.php?type=disqualification', 'fa-triangle-exclamation', 'Disqualification'],
];
$sendActive = strpos($activePage, 'send') === 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($pageTitle) ?> - Branded Email</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    .nav-drop { position: relative; display: inline-flex; align-items: center; }
    .nav-drop-menu { display: none; position: absolute; top: 100%; left: 0; min-width: 230px; background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 14px; box-shadow: 0 12px 30px rgba(15,23,42,.12); padding: 8px; z-index: 50; }
    .nav-drop:hover .nav-drop-menu, .nav-drop:focus-within .nav-drop-menu { display: block; }
    .nav-drop-menu a { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 10px; font-size: 13px; font-weight: 600; color: #0F172A; text-decoration: none; white-space: nowrap; background: transparent; }
    .nav-drop-menu a:hover, .nav-drop-menu a.active { background: #FFF7ED; color: #EA580C; }
  </style>
</head>
<body>
<nav class="topbar">
  <div class="brand"><i class="fa-solid fa-envelope"></i> Branded Email</div>
  <div class="nav-links">
    <?php foreach ($nav as $key => [$href, $icon, $label]): ?>
      <a href="<?= $href ?>" class="<?= $activePage === $key ? 'active' : '' ?>">
        <i class="fa-solid <?= $icon ?>"></i> <?= $label ?>
      </a>
    <?php endforeach; ?>

    <div class="nav-drop">
      <a href="send_email.php" class="<?= $sendActive ? 'active' : '' ?>">
        <i class="fa-solid fa-paper-plane"></i> Send Email <i class="fa-solid fa-chevron-down" style="font-size:10px;"></i>
      </a>
      <div class="nav-drop-menu">
        <?php foreach ($sendMenu as $key => [$href, $icon, $label]): ?>
          <a href="<?= $href ?>" class="<?= $activePage === 'send_' . $key ? 'active' : '' ?>">
            <i class="fa-solid <?= $icon ?>"></i> <?= $label ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <a href="<?= $navLog[1] ?>" class="<?= $activePage === $navLog[0] ? 'active' : '' ?>">
      <i class="fa-solid <?= $navLog[2] ?>"></i> <?= $navLog[3] ?>
    </a>
  </div>
  <a href="logout.php" class="btn btn-ghost btn-sm"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</nav>
<main class="container">
<?php if ($flash): ?>
  <div class="alert alert-<?= $flash['type'] === 'error' ? 'error' : 'success' ?>"><?= h($flash['message']) ?></div>
<?php endif; ?>