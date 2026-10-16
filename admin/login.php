
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

    $stmt = $pdo->prepare(
        "SELECT id, password_hash FROM admins WHERE username = ?"
    );
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

    <title>Admin Login | Branded Email Service</title>

    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body.login-page {
            min-height: 100vh;
            margin: 0;
            padding: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            color: #172554;
            background:
                radial-gradient(circle at 15% 20%, rgba(96, 165, 250, 0.20), transparent 32%),
                radial-gradient(circle at 85% 80%, rgba(167, 139, 250, 0.20), transparent 32%),
                linear-gradient(135deg, #f0f6ff 0%, #f7f5ff 50%, #edf4ff 100%);
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            padding: 38px;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(255, 255, 255, 0.9);
            border-radius: 24px;
            box-shadow: 0 24px 70px rgba(30, 64, 175, 0.12);
        }

        .login-brand {
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
            border-radius: 17px;
            color: #2563eb;
            background: #eaf1ff;
            font-size: 26px;
        }

        .login-eyebrow {
            margin: 0 0 8px;
            color: #2563eb;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 2px;
        }

        .login-card h1 {
            margin: 0 0 10px;
            color: #172554;
            font-family: 'Outfit', sans-serif;
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.8px;
        }

        .login-subtitle {
            margin: 0 0 28px;
            color: #64748b;
            font-size: 14px;
            line-height: 1.7;
        }

        .error {
            margin-bottom: 18px;
            padding: 12px 14px;
            border: 1px solid #fecaca;
            border-radius: 12px;
            background: #fef2f2;
            color: #b91c1c;
            font-size: 13px;
            line-height: 1.5;
        }

        .login-card label {
            display: block;
            margin: 18px 0 8px;
            color: #334155;
            font-size: 13px;
            font-weight: 700;
        }

        .login-card input[type="text"],
        .login-card input[type="password"] {
            width: 100%;
            min-height: 49px;
            padding: 12px 14px;
            border: 1px solid #dbe4f0;
            border-radius: 12px;
            outline: none;
            background: #f8faff;
            color: #172554;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }

        .login-card input:focus {
            border-color: #3b82f6;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.13);
        }

        .login-card button[type="submit"] {
            width: 100%;
            min-height: 51px;
            margin-top: 25px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, #3b82f6, #4f46e5);
            color: #fff;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 8px 18px rgba(59, 130, 246, 0.22);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .login-card button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(59, 130, 246, 0.30);
        }

        .login-card button[type="submit"]:focus-visible {
            outline: 3px solid #93c5fd;
            outline-offset: 3px;
        }

        .login-security {
            margin: 24px 0 0;
            text-align: center;
            color: #64748b;
            font-size: 12px;
        }

        .login-security i {
            margin-right: 5px;
            color: #2563eb;
        }

        .login-footer {
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid #eef2f7;
            text-align: center;
            color: #94a3b8;
            font-size: 11px;
            line-height: 1.8;
        }

        @media (max-width: 480px) {
            body.login-page {
                padding: 16px;
            }

            .login-card {
                padding: 28px 22px;
                border-radius: 20px;
            }

            .login-card h1 {
                font-size: 28px;
            }
        }
    </style>
</head>

<body class="login-page">

    <main class="login-card">

        <div class="login-brand">
            <i class="fa-solid fa-envelope"></i>
        </div>

        <p class="login-eyebrow">ADMIN PORTAL</p>

        <h1>Welcome back</h1>

        <p class="login-subtitle">
            Sign in to manage branded exam notifications.
        </p>

        <?php if ($error): ?>
            <div class="error" role="alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <?= csrfField() ?>

            <label for="username">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                placeholder="Enter your username"
                autocomplete="username"
                required
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                placeholder="Enter your password"
                autocomplete="current-password"
                required
            >

            <button type="submit">
                Sign in to dashboard
                <i class="fa-solid fa-arrow-right"></i>
            </button>
        </form>

        <p class="login-security">
            <i class="fa-solid fa-shield-halved"></i>
            Secure admin access
        </p>

        <div class="login-footer">
            Branded Email Notification Service<br>
            Admin Portal
        </div>

    </main>

</body>
</html>
