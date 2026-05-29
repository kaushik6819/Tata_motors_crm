<?php
require 'config/auth.php';
require 'config/db.php';
include 'templates/header.php';

// ── All backend queries unchanged ────────────────────────────
$totalContacts = $pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn();
$totalLeads    = $pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn();
$wonLeads      = $pdo->query("SELECT COUNT(*) FROM leads WHERE status IN ('booked','delivered')")->fetchColumn();
$lostLeads     = $pdo->query("SELECT COUNT(*) FROM leads WHERE status = 'lost'")->fetchColumn();

$statusRows = $pdo->query(
    "SELECT status, COUNT(*) as total FROM leads GROUP BY status"
)->fetchAll(PDO::FETCH_KEY_PAIR);

$recentLeads = $pdo->query(
    "SELECT * FROM leads ORDER BY created_at DESC LIMIT 5"
)->fetchAll();

$recentContacts = $pdo->query(
    "SELECT * FROM contacts ORDER BY created_at DESC LIMIT 5"
)->fetchAll();
?>

<div class="container">

  <!-- Page header -->
  <div class="page-header">
    <div>
      <h1>Dashboard</h1>
      <div class="page-subtitle">
        <?= date('l, d F Y') ?> — Dealer operations overview
      </div>
    </div>
  </div>

  <!-- Stat cards -->
  <div class="stat-grid">
    <div class="stat-card" style="border-top: 2px solid #3B82F6;">
      <div class="stat-number"><?= $totalContacts ?></div>
      <div class="stat-label">Total contacts</div>
    </div>
    <div class="stat-card" style="border-top: 2px solid #8B5CF6;">
      <div class="stat-number"><?= $totalLeads ?></div>
      <div class="stat-label">Total leads</div>
    </div>
    <div class="stat-card" style="border-top: 2px solid #22C55E;">
      <div class="stat-number"><?= $wonLeads ?></div>
      <div class="stat-label">Booked + delivered</div>
    </div>
    <div class="stat-card" style="border-top: 2px solid #EF4444;">
      <div class="stat-number"><?= $lostLeads ?></div>
      <div class="stat-label">Lost leads</div>
    </div>
  </div>

  <!-- Leads by status -->
  <h2>Pipeline by status</h2>
  <div class="table-card" style="padding: 20px 24px;">
    <div class="status-bars">
    <?php
    $config = [
      'new'                  => ['#3B82F6', 'New'],
      'test_drive_scheduled' => ['#F59E0B', 'Test drive scheduled'],
      'quote_sent'           => ['#14B8A6', 'Quote sent'],
      'negotiating'          => ['#A855F7', 'Negotiating'],
      'booked'               => ['#22C55E', 'Booked'],
      'delivered'            => ['#86EFAC', 'Delivered'],
      'lost'                 => ['#EF4444', 'Lost'],
    ];
    $max = max(array_merge(array_values($statusRows), [1]));
    foreach ($config as $key => [$color, $label]):
      $count = $statusRows[$key] ?? 0;
      $pct   = round(($count / $max) * 100);
    ?>
    <div class="bar-row">
      <a href="pages/leads.php?filter=<?= $key ?>" class="bar-label">
        <?= $label ?>
      </a>
      <div class="bar-track">
        <div class="bar-fill"
             style="width:<?= max($pct, ($count > 0 ? 2 : 0)) ?>%;
                    background:<?= $color ?>;"></div>
      </div>
      <span class="bar-count"><?= $count ?></span>
    </div>
    <?php endforeach; ?>
    </div>
  </div>

  <!-- Recent activity -->
  <div class="two-col" style="margin-top: 28px;">

    <!-- Recent leads -->
    <div>
      <h2>Recent leads</h2>
      <div class="table-card">
        <?php if (empty($recentLeads)): ?>
          <div style="padding: 24px; color: var(--steel-500); font-size: 13px; text-align: center;">
            No leads yet. <a href="pages/leads.php" style="color:var(--accent);">Add one →</a>
          </div>
        <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Car</th>
              <th>Status</th>
              <th>Added</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($recentLeads as $l):
            $statusColors = [
              'new'                  => ['rgba(59,130,246,0.1)',  '#60A5FA'],
              'test_drive_scheduled' => ['rgba(245,158,11,0.1)',  '#FBB040'],
              'quote_sent'           => ['rgba(20,184,166,0.1)',  '#2DD4BF'],
              'negotiating'          => ['rgba(168,85,247,0.1)',  '#C084FC'],
              'booked'               => ['rgba(34,197,94,0.1)',   '#4ADE80'],
              'delivered'            => ['rgba(34,197,94,0.15)',  '#86EFAC'],
              'lost'                 => ['rgba(239,68,68,0.1)',   '#F87171'],
            ];
            [$bg,$clr] = $statusColors[$l['status']] ?? ['rgba(90,110,138,0.1)','#8FA3BD'];
            $label = ucwords(str_replace('_',' ',$l['status']));
          ?>
          <tr>
            <td style="font-weight: 500; color: var(--steel-50);">
              <?= htmlspecialchars($l['name']) ?>
            </td>
            <td style="color: var(--steel-400); font-size: 12px;">
              <?= htmlspecialchars($l['car_model'] ?? '—') ?>
            </td>
            <td>
              <span style="
                background:<?= $bg ?>; color:<?= $clr ?>;
                font-family: var(--font-head);
                font-size: 10px; font-weight: 700;
                letter-spacing: 0.08em; text-transform: uppercase;
                padding: 2px 8px; border-radius: 2px;
                border: 1px solid <?= str_replace('0.1)', '0.2)', str_replace('0.15)', '0.25)', $bg)) ?>;
                white-space: nowrap;
              "><?= $label ?></span>
            </td>
            <td style="color: var(--steel-500); font-size: 12px;">
              <?= date('d M', strtotime($l['created_at'])) ?>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <div style="padding: 12px 16px; border-top: 1px solid var(--steel-700);">
          <a href="pages/leads.php" style="
            font-family: var(--font-head); font-size: 10px; font-weight: 700;
            letter-spacing: 0.1em; text-transform: uppercase;
            color: var(--accent); text-decoration: none;
          ">View all leads →</a>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Recent contacts -->
    <div>
      <h2>Recent contacts</h2>
      <div class="table-card">
        <?php if (empty($recentContacts)): ?>
          <div style="padding: 24px; color: var(--steel-500); font-size: 13px; text-align: center;">
            No contacts yet. <a href="pages/contacts.php" style="color:var(--accent);">Add one →</a>
          </div>
        <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Added</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($recentContacts as $c): ?>
          <tr>
            <td style="font-weight: 500; color: var(--steel-50);">
              <a href="pages/contact_view.php?id=<?= $c['id'] ?>"
                 style="color: var(--steel-50); text-decoration: none;">
                <?= htmlspecialchars($c['name']) ?>
              </a>
            </td>
            <td style="color: var(--steel-400); font-size: 12px;">
              <?= htmlspecialchars($c['email'] ?? '—') ?>
            </td>
            <td style="color: var(--steel-500); font-size: 12px;">
              <?= date('d M', strtotime($c['created_at'])) ?>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <div style="padding: 12px 16px; border-top: 1px solid var(--steel-700);">
          <a href="pages/contacts.php" style="
            font-family: var(--font-head); font-size: 10px; font-weight: 700;
            letter-spacing: 0.1em; text-transform: uppercase;
            color: var(--accent); text-decoration: none;
          ">View all contacts →</a>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?php include 'templates/footer.php'; ?>