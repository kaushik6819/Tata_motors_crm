<?php
session_start();
require 'config/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: /mini-crm/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    if (empty($username) || empty($password)) {
        $error = 'Both fields are required.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: /mini-crm/index.php');
            exit;
        } else {
            $error = 'Invalid credentials. Try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tata Motors — Dealer CRM</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --tata-blue:   #2B6CB0;
      --tata-deep:   #1A3A5C;
      --steel-900:   #0D1117;
      --steel-800:   #161B22;
      --steel-700:   #1C2433;
      --steel-600:   #232D3F;
      --steel-500:   #2E3A50;
      --steel-400:   #3D4F68;
      --steel-300:   #5A6E8A;
      --steel-200:   #8FA3BD;
      --steel-100:   #C4D0DF;
      --steel-50:    #E8EDF3;
      --accent:      #3B82F6;
      --accent-glow: rgba(59,130,246,0.18);
      --danger:      #E53E3E;
      --font-head:   'Syne', sans-serif;
      --font-body:   'DM Sans', sans-serif;
    }

    html, body {
      height: 100%;
      background-color: var(--steel-900);
      color: var(--steel-50);
      font-family: var(--font-body);
      font-size: 15px;
      line-height: 1.6;
      -webkit-font-smoothing: antialiased;
    }

    /* ── Structural grid ── */
    .page {
      min-height: 100vh;
      display: grid;
      grid-template-columns: 1fr 520px;
    }

    /* ── Left panel — industrial backdrop ── */
    .panel-left {
      position: relative;
      background: var(--steel-800);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      padding: 56px;
    }

    /* Diagonal grid lines */
    .panel-left::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image:
        linear-gradient(rgba(59,130,246,0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(59,130,246,0.04) 1px, transparent 1px);
      background-size: 48px 48px;
    }

    /* Thick accent bar top */
    .panel-left::after {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 3px;
      background: var(--tata-blue);
    }

    /* Blueprint circle decorations */
    .blueprint-ring {
      position: absolute;
      border-radius: 50%;
      border: 1px solid rgba(59,130,246,0.1);
    }
    .ring-1 { width: 480px; height: 480px; top: -120px; right: -160px; }
    .ring-2 { width: 320px; height: 320px; top: -40px;  right: -60px;  border-color: rgba(59,130,246,0.07); }
    .ring-3 { width: 640px; height: 640px; bottom: -200px; left: -200px; }

    /* Crosshair marker */
    .crosshair {
      position: absolute;
      top: 60px; right: 80px;
      width: 24px; height: 24px;
    }
    .crosshair::before, .crosshair::after {
      content: '';
      position: absolute;
      background: var(--accent);
      opacity: 0.5;
    }
    .crosshair::before { left: 11px; top: 0; width: 2px; height: 100%; }
    .crosshair::after  { top: 11px; left: 0; width: 100%; height: 2px; }

    /* Metric tags scattered */
    .metric-tag {
      position: absolute;
      font-family: var(--font-head);
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--steel-300);
      border: 1px solid var(--steel-600);
      padding: 4px 10px;
      border-radius: 2px;
    }
    .metric-tag.accent { color: var(--accent); border-color: rgba(59,130,246,0.35); }
    .mt-1 { top: 120px; right: 56px; }
    .mt-2 { top: 160px; right: 56px; }
    .mt-3 { top: 200px; right: 56px; }

    .left-content { position: relative; z-index: 2; }

    .division-label {
      font-family: var(--font-head);
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: var(--tata-blue);
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .division-label::before {
      content: '';
      display: inline-block;
      width: 24px;
      height: 2px;
      background: var(--tata-blue);
    }

    .left-heading {
      font-family: var(--font-head);
      font-size: clamp(32px, 4vw, 52px);
      font-weight: 800;
      line-height: 1.05;
      letter-spacing: -0.02em;
      color: var(--steel-50);
      margin-bottom: 20px;
    }
    .left-heading em {
      font-style: normal;
      color: var(--accent);
    }

    .left-subtext {
      font-family: var(--font-body);
      font-size: 14px;
      font-weight: 300;
      color: var(--steel-300);
      max-width: 360px;
      line-height: 1.7;
      margin-bottom: 36px;
    }

    /* Stats row */
    .stats-row {
      display: flex;
      gap: 32px;
      border-top: 1px solid var(--steel-600);
      padding-top: 28px;
    }
    .stat-item { }
    .stat-val {
      font-family: var(--font-head);
      font-size: 22px;
      font-weight: 700;
      color: var(--steel-50);
    }
    .stat-lbl {
      font-size: 11px;
      font-weight: 400;
      color: var(--steel-400);
      letter-spacing: 0.06em;
      text-transform: uppercase;
      margin-top: 2px;
    }

    /* ── Right panel — login form ── */
    .panel-right {
      background: var(--steel-900);
      border-left: 1px solid var(--steel-700);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 56px 48px;
      position: relative;
    }

    /* Corner bracket top-right */
    .bracket-tr {
      position: absolute;
      top: 24px; right: 24px;
      width: 28px; height: 28px;
      border-top: 2px solid var(--steel-600);
      border-right: 2px solid var(--steel-600);
    }
    .bracket-bl {
      position: absolute;
      bottom: 24px; left: 24px;
      width: 28px; height: 28px;
      border-bottom: 2px solid var(--steel-600);
      border-left: 2px solid var(--steel-600);
    }

    .form-wrap {
      width: 100%;
      max-width: 360px;
      animation: fadeUp 0.5s ease both;
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(18px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* Logo block */
    .logo-block {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin-bottom: 40px;
    }

    .logo-svg-wrap {
  width: 80px;
  height: 80px;
  background: #ffffff;
  border: 1px solid var(--steel-700);
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
  position: relative;
  padding: 10px;
}
    /* Tiny accent corners on logo box */
    .logo-svg-wrap::before, .logo-svg-wrap::after {
      content: '';
      position: absolute;
      width: 8px; height: 8px;
    }
    .logo-svg-wrap::before {
      top: -1px; left: -1px;
      border-top: 2px solid var(--accent);
      border-left: 2px solid var(--accent);
    }
    .logo-svg-wrap::after {
      bottom: -1px; right: -1px;
      border-bottom: 2px solid var(--accent);
      border-right: 2px solid var(--accent);
    }

    /* Tata logo inline SVG */
    .tata-logo-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

    .logo-name {
      font-family: var(--font-head);
      font-size: 13px;
      font-weight: 700;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      color: var(--steel-200);
    }
    .logo-tagline {
      font-size: 11px;
      font-weight: 300;
      color: var(--steel-400);
      letter-spacing: 0.1em;
      text-transform: uppercase;
      margin-top: 3px;
    }

    /* Form heading */
    .form-heading {
      font-family: var(--font-head);
      font-size: 22px;
      font-weight: 700;
      color: var(--steel-50);
      margin-bottom: 6px;
      letter-spacing: -0.01em;
    }
    .form-subheading {
      font-size: 13px;
      font-weight: 300;
      color: var(--steel-400);
      margin-bottom: 32px;
    }

    /* Error box */
    .error-box {
      background: rgba(229,62,62,0.08);
      border: 1px solid rgba(229,62,62,0.35);
      border-left: 3px solid var(--danger);
      color: #FC8181;
      font-size: 13px;
      padding: 10px 14px;
      margin-bottom: 20px;
      border-radius: 2px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .error-box::before {
      content: '⚠';
      font-size: 14px;
      flex-shrink: 0;
    }

    /* Field */
    .field {
      margin-bottom: 18px;
    }
    .field label {
      display: block;
      font-family: var(--font-head);
      font-size: 10px;
      font-weight: 700;
      letter-spacing: 0.16em;
      text-transform: uppercase;
      color: var(--steel-300);
      margin-bottom: 8px;
    }

    .input-wrap {
      position: relative;
    }
    .input-wrap svg {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      width: 16px;
      height: 16px;
      stroke: var(--steel-400);
      fill: none;
      stroke-width: 1.5;
      stroke-linecap: round;
      stroke-linejoin: round;
      pointer-events: none;
      transition: stroke 0.2s;
    }

    .field input {
      width: 100%;
      background: var(--steel-800);
      border: 1px solid var(--steel-600);
      border-radius: 3px;
      color: var(--steel-50);
      font-family: var(--font-body);
      font-size: 14px;
      font-weight: 400;
      padding: 12px 14px 12px 42px;
      outline: none;
      transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
      -webkit-appearance: none;
    }
    .field input::placeholder { color: var(--steel-500); }
    .field input:focus {
      border-color: var(--accent);
      background: var(--steel-700);
      box-shadow: 0 0 0 3px var(--accent-glow);
    }
    .field input:focus + svg,
    .input-wrap:focus-within svg { stroke: var(--accent); }

    /* Flip icon/input order for correct DOM — icon after input via absolute */
    .input-wrap input { order: 2; }

    /* Submit button */
    .btn-login {
      width: 100%;
      background: var(--tata-blue);
      color: #fff;
      border: none;
      border-radius: 3px;
      font-family: var(--font-head);
      font-size: 13px;
      font-weight: 700;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      padding: 14px;
      cursor: pointer;
      margin-top: 8px;
      position: relative;
      overflow: hidden;
      transition: background 0.2s, transform 0.1s;
    }
    .btn-login::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.06) 50%, transparent 100%);
      transform: translateX(-100%);
      transition: transform 0.4s ease;
    }
    .btn-login:hover { background: #3B82F6; }
    .btn-login:hover::before { transform: translateX(100%); }
    .btn-login:active { transform: scale(0.99); }

    /* Bottom meta */
    .form-footer {
      margin-top: 32px;
      border-top: 1px solid var(--steel-800);
      padding-top: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .form-footer-left {
      font-size: 11px;
      color: var(--steel-500);
      letter-spacing: 0.06em;
    }
    .form-footer-right {
      font-size: 11px;
      color: var(--steel-500);
      text-align: right;
    }
    .version-tag {
      font-family: var(--font-head);
      font-size: 10px;
      font-weight: 600;
      letter-spacing: 0.1em;
      color: var(--steel-600);
      background: var(--steel-800);
      border: 1px solid var(--steel-700);
      padding: 3px 8px;
      border-radius: 2px;
    }

    /* Mobile */
    @media (max-width: 840px) {
      .page { grid-template-columns: 1fr; }
      .panel-left { display: none; }
      .panel-right { padding: 40px 24px; }
    }
  </style>
</head>
<body>
<div class="page">

  <!-- ── LEFT PANEL ── -->
  <div class="panel-left">
    <div class="blueprint-ring ring-1"></div>
    <div class="blueprint-ring ring-2"></div>
    <div class="blueprint-ring ring-3"></div>
    <div class="crosshair"></div>

    <span class="metric-tag accent mt-1">Pipeline active</span>
    <span class="metric-tag mt-2">Dealer network</span>
    <span class="metric-tag mt-3">Real-time sync</span>

    <div class="left-content">
      <div class="division-label">Tata Motors — Dealer Division</div>
      <h1 class="left-heading">
        Drive every<br>
        deal to <em>closure.</em>
      </h1>
      <p class="left-subtext">
        Unified CRM for managing leads, test drives, inventory,
        and after-sales — built for the Tata dealership floor.
      </p>
      <div class="stats-row">
        <div class="stat-item">
          <div class="stat-val">7</div>
          <div class="stat-lbl">Pipeline stages</div>
        </div>
        <div class="stat-item">
          <div class="stat-val">360°</div>
          <div class="stat-lbl">Customer view</div>
        </div>
        <div class="stat-item">
          <div class="stat-val">Live</div>
          <div class="stat-lbl">Inventory sync</div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── RIGHT PANEL ── -->
  <div class="panel-right">
    <div class="bracket-tr"></div>
    <div class="bracket-bl"></div>

    <div class="form-wrap">

      <!-- Logo -->
      <div class="logo-block">
        <div class="logo-svg-wrap">
          <img src="/mini-crm/assets/tata-logo.png" class="tata-logo-img" alt="Tata Motors">
        </div>
        <div class="logo-name">Dealer CRM</div>
        <div class="logo-tagline">Tata Motors — Authorized Dealer Portal</div>
      </div>

      <h2 class="form-heading">Sign in</h2>
      <p class="form-subheading">Enter your dealer credentials to continue.</p>

      <?php if ($error): ?>
      <div class="error-box"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" autocomplete="off">
        <div class="field">
          <label for="username">Username</label>
          <div class="input-wrap">
            <input
              type="text"
              id="username"
              name="username"
              placeholder="dealer_username"
              value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
              required
              autofocus>
            <!-- Person icon -->
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <circle cx="12" cy="8" r="4"/>
              <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
            </svg>
          </div>
        </div>

        <div class="field">
          <label for="password">Password</label>
          <div class="input-wrap">
            <input
              type="password"
              id="password"
              name="password"
              placeholder="••••••••"
              required>
            <!-- Lock icon -->
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <rect x="5" y="11" width="14" height="10" rx="2"/>
              <path d="M8 11V7a4 4 0 0 1 8 0v4"/>
            </svg>
          </div>
        </div>

        <button type="submit" class="btn-login">Authenticate →</button>
      </form>

      <div class="form-footer">
        <div class="form-footer-left">
          © <?= date('Y') ?> Tata Motors Ltd.<br>
          Authorized use only.
        </div>
        <div class="form-footer-right">
          <span class="version-tag">v1.0</span>
        </div>
      </div>

    </div>
  </div>

</div>
</body>
</html>