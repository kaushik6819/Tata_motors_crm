<?php
session_start();
require 'config/db.php';

// Already logged in? Go to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: /mini-crm/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Login success
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: /mini-crm/index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login — Mini CRM</title>
  <link rel="stylesheet" href="/mini-crm/style.css">
  <style>
    body { display:flex; justify-content:center; align-items:center; min-height:100vh; background:#f0f2f5; }
    .login-box {
      background: white;
      padding: 40px;
      border-radius: 10px;
      width: 100%;
      max-width: 380px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    .login-box h1 { text-align:center; margin-bottom:6px; font-size:22px; color:#2c3e50; }
    .login-box p  { text-align:center; color:#888; font-size:14px; margin-bottom:24px; }
    .error-msg { background:#f8d7da; color:#721c24; padding:10px; border-radius:4px; margin-bottom:16px; font-size:14px; }
    .login-box label { font-size:13px; font-weight:bold; color:#555; display:block; margin-bottom:4px; }
    .login-box input { margin-bottom:16px; }
    .login-box button { width:100%; padding:12px; font-size:15px; }
  </style>
</head>
<body>
  <div class="login-box">
    <h1>🔐 Mini CRM</h1>
    <p>Sign in to your account</p>

    <?php if ($error): ?>
      <div class="error-msg"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <label>Username</label>
      <input type="text" name="username" placeholder="Enter username"
             value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>

      <label>Password</label>
      <input type="password" name="password" placeholder="Enter password" required>

      <button type="submit">Sign In</button>
    </form>
  </div>
</body>
</html>