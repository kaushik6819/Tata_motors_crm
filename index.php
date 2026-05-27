<?php
require 'config/db.php';
include 'templates/header.php';

// ── Counts ──────────────────────────────────────────────────
$totalContacts = $pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn();
$totalLeads    = $pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn();
$wonLeads      = $pdo->query("SELECT COUNT(*) FROM leads WHERE status = 'won'")->fetchColumn();
$lostLeads     = $pdo->query("SELECT COUNT(*) FROM leads WHERE status = 'lost'")->fetchColumn();

// ── Leads by status (for the bar chart) ─────────────────────
$statusRows = $pdo->query(
    "SELECT status, COUNT(*) as total FROM leads GROUP BY status"
)->fetchAll(PDO::FETCH_KEY_PAIR);

// ── Recent leads (last 5) ────────────────────────────────────
$recentLeads = $pdo->query(
    "SELECT * FROM leads ORDER BY created_at DESC LIMIT 5"
)->fetchAll();

// ── Recent contacts (last 5) ─────────────────────────────────
$recentContacts = $pdo->query(
    "SELECT * FROM contacts ORDER BY created_at DESC LIMIT 5"
)->fetchAll();
?>

<div class="container">
  <h1>Dashboard</h1>
  <p style="color:#666; margin-top:-10px;">Welcome to your Mini CRM — here's what's happening.</p>

  <!-- ── Stat Cards ── -->
  <div class="stat-grid">
    <div class="stat-card" style="border-left: 4px solid #3498db;">
      <div class="stat-number"><?= $totalContacts ?></div>
      <div class="stat-label">Total Contacts</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid #9b59b6;">
      <div class="stat-number"><?= $totalLeads ?></div>
      <div class="stat-label">Total Leads</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid #27ae60;">
      <div class="stat-number"><?= $wonLeads ?></div>
      <div class="stat-label">Won Leads</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid #e74c3c;">
      <div class="stat-number"><?= $lostLeads ?></div>
      <div class="stat-label">Lost Leads</div>
    </div>
  </div>

  <!-- ── Leads by Status ── -->
  <h2>Leads by Status</h2>
  <div class="status-bars">
  <?php
  $config = [
    'new'       => ['#3498db', '🔵 New'],
    'contacted' => ['#f39c12', '🟡 Contacted'],
    'qualified' => ['#1abc9c', '🔷 Qualified'],
    'won'       => ['#27ae60', '🟢 Won'],
    'lost'      => ['#e74c3c', '🔴 Lost'],
  ];
  $max = max(array_values($statusRows) ?: [1]);
  foreach ($config as $key => [$color, $label]):
    $count = $statusRows[$key] ?? 0;
    $pct   = $max > 0 ? round(($count / $max) * 100) : 0;
  ?>
  <div class="bar-row">
    <a href="pages/leads.php?filter=<?= $key ?>" class="bar-label"><?= $label ?></a>
    <div class="bar-track">
      <div class="bar-fill" style="width:<?= $pct ?>%; background:<?= $color ?>;"></div>
    </div>
    <span class="bar-count"><?= $count ?></span>
  </div>
  <?php endforeach; ?>
  </div>

  <!-- ── Two columns: Recent Leads + Recent Contacts ── -->
  <div class="two-col">

    <!-- Recent Leads -->
    <div>
      <h2>Recent Leads</h2>
      <?php if (empty($recentLeads)): ?>
        <p style="color:#999;">No leads yet. <a href="pages/leads.php">Add one</a>.</p>
      <?php else: ?>
        <table>
          <tr><th>Name</th><th>Status</th><th>Added</th></tr>
          <?php foreach ($recentLeads as $l): ?>
          <tr>
            <td><?= htmlspecialchars($l['name']) ?></td>
            <td><span class="badge badge-<?= $l['status'] ?>"><?= ucfirst($l['status']) ?></span></td>
            <td style="font-size:13px;color:#888"><?= date('d M', strtotime($l['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
        <a href="pages/leads.php" style="font-size:13px;">View all leads →</a>
      <?php endif; ?>
    </div>

    <!-- Recent Contacts -->
    <div>
      <h2>Recent Contacts</h2>
      <?php if (empty($recentContacts)): ?>
        <p style="color:#999;">No contacts yet. <a href="pages/contacts.php">Add one</a>.</p>
      <?php else: ?>
        <table>
          <tr><th>Name</th><th>Email</th><th>Added</th></tr>
          <?php foreach ($recentContacts as $c): ?>
          <tr>
            <td><?= htmlspecialchars($c['name']) ?></td>
            <td style="font-size:13px"><?= htmlspecialchars($c['email']) ?></td>
            <td style="font-size:13px;color:#888"><?= date('d M', strtotime($c['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
        <a href="pages/contacts.php" style="font-size:13px;">View all contacts →</a>
      <?php endif; ?>
    </div>

  </div><!-- end two-col -->

</div><!-- end container -->

<?php include 'templates/footer.php'; ?>