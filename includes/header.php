<?php
$pageTitle  = $pageTitle  ?? 'Admin';
$activePage = $activePage ?? '';
$flash = getFlash();
$nav = [
    'colleges'   => ['colleges.php',   'fa-building-columns', 'Colleges'],
    'students'   => ['students.php',   'fa-user-graduate',    'Students'],
    'send'       => ['send_email.php', 'fa-paper-plane',      'Send Email'],
    'log'        => ['email_log.php',  'fa-clock-rotate-left','Email Log'],
];
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
  </div>
  <a href="logout.php" class="btn btn-ghost btn-sm"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</nav>
<main class="container">
<?php if ($flash): ?>
  <div class="alert alert-<?= $flash['type'] === 'error' ? 'error' : 'success' ?>"><?= h($flash['message']) ?></div>
<?php endif; ?>