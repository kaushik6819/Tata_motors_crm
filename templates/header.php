<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$currentPage = basename($_SERVER['PHP_SELF']);
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
  <link rel="stylesheet" href="/mini-crm/style.css">
  <!-- Apply saved theme BEFORE paint to avoid flash -->
  <script>
    (function() {
      if (localStorage.getItem('crm-theme') === 'light') {
        document.documentElement.classList.add('light-mode-pre');
      }
    })();
  </script>
  <style>
    /* Pre-apply on html to avoid FOUC before body renders */
    html.light-mode-pre body { background: #F0F4F8 !important; }
  </style>
</head>
<body>

<script>
  // Apply theme to body as soon as body exists
  (function() {
    if (localStorage.getItem('crm-theme') === 'light') {
      document.body.classList.add('light-mode');
    }
  })();
</script>

<nav>
  <div class="nav-inner">
    <div class="nav-left-group">
      <a href="/mini-crm/index.php" class="nav-brand">
        <div class="nav-logo-box">
          <img src="/mini-crm/assets/tata-logo.png" alt="Tata" class="nav-logo-img">
        </div>
        <div class="nav-brand-words">
          <span class="nav-brand-title">Dealer CRM</span>
          <span class="nav-brand-sub">Tata Motors</span>
        </div>
      </a>
      <div class="nav-divider"></div>
      <div class="nav-links">
        <a href="/mini-crm/index.php"             class="nav-link <?= $currentPage==='index.php'        ?'is-active':'' ?>">Home</a>
        <a href="/mini-crm/pages/contacts.php"    class="nav-link <?= $currentPage==='contacts.php'     ?'is-active':'' ?>">Contacts</a>
        <a href="/mini-crm/pages/leads.php"       class="nav-link <?= $currentPage==='leads.php'        ?'is-active':'' ?>">Leads</a>
        <a href="/mini-crm/pages/cars.php"        class="nav-link <?= $currentPage==='cars.php'         ?'is-active':'' ?>">Cars</a>
        <a href="/mini-crm/pages/test_drives.php" class="nav-link <?= $currentPage==='test_drives.php'  ?'is-active':'' ?>">Test Drives</a>
        <a href="/mini-crm/pages/after_sales.php" class="nav-link <?= $currentPage==='after_sales.php'  ?'is-active':'' ?>">After-Sales</a>
      </div>
    </div>

    <div class="nav-right-group">
      <?php if (isset($_SESSION['username'])): ?>
        <span class="nav-user">
          <span class="nav-online-dot"></span>
          <?= htmlspecialchars($_SESSION['username']) ?>
        </span>
      <?php endif; ?>

      <!-- Theme toggle button -->
      <button class="theme-toggle" id="themeToggle" title="Toggle light/dark mode" type="button">
        <!-- Moon icon (shown in dark mode) -->
        <svg class="icon-moon" viewBox="0 0 24 24">
          <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
        </svg>
        <!-- Sun icon (shown in light mode) -->
        <svg class="icon-sun" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="5"/>
          <line x1="12" y1="1"  x2="12" y2="3"/>
          <line x1="12" y1="21" x2="12" y2="23"/>
          <line x1="4.22" y1="4.22"  x2="5.64" y2="5.64"/>
          <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
          <line x1="1"  y1="12" x2="3"  y2="12"/>
          <line x1="21" y1="12" x2="23" y2="12"/>
          <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
          <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
        </svg>
      </button>

      <?php if (isset($_SESSION['username'])): ?>
        <a href="/mini-crm/logout.php" class="nav-logout-btn">Logout</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<script>
  // Theme toggle logic
  document.getElementById('themeToggle').addEventListener('click', function() {
    var isLight = document.body.classList.toggle('light-mode');
    localStorage.setItem('crm-theme', isLight ? 'light' : 'dark');
  });
</script>