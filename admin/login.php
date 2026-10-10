<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/db.php';

if (isLoggedIn()) {
    header('Location: colleges.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT id, password_hash FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        header('Location: colleges.php');
        exit;
    }
    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body { font-family: Inter, sans-serif; font-size: 13px; background: #f3f4f6;
           display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
    .card { background: #fff; padding: 32px; border-radius: 16px; width: 100%; max-width: 360px;
            box-shadow: 0 4px 20px rgba(0,0,0,.08); margin: 16px; }
    h1 { font-family: Outfit, sans-serif; font-size: 24px; font-weight: 800; margin: 0 0 20px; }
    label { display: block; margin-bottom: 6px; font-weight: 500; }
    input { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 10px;
            margin-bottom: 16px; box-sizing: border-box; font-family: Inter, sans-serif; }
    button { width: 100%; padding: 11px; background: #2563eb; color: #fff; border: 0;
             border-radius: 10px; font-weight: 600; cursor: pointer; }
    .error { background: #fee2e2; color: #b91c1c; padding: 10px; border-radius: 10px; margin-bottom: 16px; }
  </style>
</head>
<body>
  <div class="card">
    <h1><i class="fa-solid fa-envelope"></i> Admin Login</h1>
    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <?= csrfField() ?>
      <label>Username</label>
      <input type="text" name="username" required>
      <label>Password</label>
      <input type="password" name="password" required>
      <button type="submit">Login</button>
    </form>
  </div>
</body>
</html>