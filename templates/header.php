<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Mini CRM</title>
  <link rel="stylesheet" href="/mini-crm/style.css">
</head>
<body>
<nav>
  <div class="nav-left">
    <a href="/mini-crm/index.php">🏠 Home</a>
    <a href="/mini-crm/pages/contacts.php">👤 Contacts</a>
    <a href="/mini-crm/pages/leads.php">🎯 Leads</a>
    <a href="/mini-crm/pages/cars.php">🚗 Cars</a>
    <a href="/mini-crm/pages/test_drives.php">🔑 Test Drives</a>
    <a href="/mini-crm/pages/after_sales.php">🔧 After-Sales</a>
  </div>
  <div class="nav-right">
    <?php if (isset($_SESSION['username'])): ?>
      <span class="nav-user">👋 <?= htmlspecialchars($_SESSION['username']) ?></span>
      <a href="/mini-crm/logout.php" class="nav-logout">Logout</a>
    <?php endif; ?>
  </div>
</nav>