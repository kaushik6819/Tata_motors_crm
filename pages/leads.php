<?php
require '../config/auth.php';
require '../config/db.php';
include '../templates/header.php';

// ── ADD a new lead ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $stmt = $pdo->prepare(
        "INSERT INTO leads (name, email, phone, status, notes) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        htmlspecialchars($_POST['name']),
        htmlspecialchars($_POST['email']),
        htmlspecialchars($_POST['phone']),
        $_POST['status'],
        htmlspecialchars($_POST['notes'])
    ]);
    $success = "Lead added successfully!";
}

// ── UPDATE status ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $stmt = $pdo->prepare("UPDATE leads SET status = ? WHERE id = ?");
    $stmt->execute([$_POST['status'], (int)$_POST['lead_id']]);
    $success = "Status updated!";
}

// ── DELETE a lead ───────────────────────────────────────────
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM leads WHERE id = ?");
    $stmt->execute([(int)$_GET['delete']]);
    $success = "Lead deleted.";
}

// ── FILTER by status ────────────────────────────────────────
$filter = $_GET['filter'] ?? 'all';
if ($filter !== 'all') {
    $stmt = $pdo->prepare("SELECT * FROM leads WHERE status = ? ORDER BY created_at DESC");
    $stmt->execute([$filter]);
    $leads = $stmt->fetchAll();
} else {
    $leads = $pdo->query("SELECT * FROM leads ORDER BY created_at DESC")->fetchAll();
}

// ── STATUS counts for summary bar ──────────────────────────
$counts = $pdo->query(
    "SELECT status, COUNT(*) as total FROM leads GROUP BY status"
)->fetchAll(PDO::FETCH_KEY_PAIR);

$statuses = ['new', 'contacted', 'qualified', 'won', 'lost'];
?>

<div class="container">
  <h1>Leads</h1>

  <?php if (!empty($success)): ?>
    <div class="msg-success"><?= $success ?></div>
  <?php endif; ?>

  <!-- ── Summary bar ── -->
  <div style="display:flex; gap:10px; margin-bottom:24px; flex-wrap:wrap;">
    <?php
    $labels = [
      'new'       => ['🔵', '#cce5ff', '#004085'],
      'contacted' => ['🟡', '#fff3cd', '#856404'],
      'qualified' => ['🔷', '#d1ecf1', '#0c5460'],
      'won'       => ['🟢', '#d4edda', '#155724'],
      'lost'      => ['🔴', '#f8d7da', '#721c24'],
    ];
    foreach ($labels as $s => [$icon, $bg, $clr]):
      $n = $counts[$s] ?? 0;
    ?>
    <a href="?filter=<?= $s ?>" style="
      text-decoration:none;
      background:<?= $bg ?>;
      color:<?= $clr ?>;
      padding:8px 16px;
      border-radius:20px;
      font-size:13px;
      font-weight:bold;
      border: 2px solid <?= ($filter===$s) ? $clr : 'transparent' ?>;
    "><?= $icon ?> <?= ucfirst($s) ?> (<?= $n ?>)</a>
    <?php endforeach; ?>
    <?php if ($filter !== 'all'): ?>
      <a href="?" style="text-decoration:none; color:#666; padding:8px 16px; font-size:13px;">✖ Clear filter</a>
    <?php endif; ?>
  </div>

  <!-- ── Add Lead Form ── -->
  <h2>Add New Lead</h2>
  <form method="POST">
    <input type="text"  name="name"  placeholder="Full Name" required>
    <input type="email" name="email" placeholder="Email address">
    <input type="text"  name="phone" placeholder="Phone number">
    <select name="status">
      <option value="new">🔵 New</option>
      <option value="contacted">🟡 Contacted</option>
      <option value="qualified">🔷 Qualified</option>
      <option value="won">🟢 Won</option>
      <option value="lost">🔴 Lost</option>
    </select>
    <textarea name="notes" placeholder="Notes (optional)" rows="2"></textarea>
    <button type="submit" name="add">Add Lead</button>
  </form>

  <!-- ── Leads Table ── -->
  <h2>
    <?php if ($filter !== 'all'): ?>
      <?= ucfirst($filter) ?> Leads (<?= count($leads) ?>)
    <?php else: ?>
      All Leads (<?= count($leads) ?>)
    <?php endif; ?>
  </h2>

  <table>
    <tr>
      <th>#</th>
      <th>Name</th>
      <th>Email</th>
      <th>Phone</th>
      <th>Status</th>
      <th>Notes</th>
      <th>Added</th>
      <th>Action</th>
    </tr>

    <?php if (empty($leads)): ?>
      <tr><td colspan="8" style="color:#999; text-align:center; padding:20px;">
        No leads found. <?= $filter !== 'all' ? '<a href="?">Show all</a>' : 'Add one above!' ?>
      </td></tr>
    <?php else: ?>
      <?php foreach ($leads as $lead): ?>
      <tr>
        <td><?= $lead['id'] ?></td>
        <td><strong><?= htmlspecialchars($lead['name']) ?></strong></td>
        <td><?= htmlspecialchars($lead['email']) ?></td>
        <td><?= htmlspecialchars($lead['phone']) ?></td>

        <!-- Inline status update -->
        <td>
          <form method="POST" style="display:inline">
            <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
            <select name="status" class="edit-select" onchange="this.form.submit()">
              <?php foreach ($statuses as $s): ?>
                <option value="<?= $s ?>" <?= $lead['status']===$s ? 'selected' : '' ?>>
                  <?= ucfirst($s) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <input type="hidden" name="update_status" value="1">
          </form>
        </td>

        <td style="font-size:13px; color:#666; max-width:160px;">
          <?= htmlspecialchars($lead['notes']) ?>
        </td>
        <td><?= date('d M Y', strtotime($lead['created_at'])) ?></td>
        <td>
          <a class="delete-btn"
             href="?delete=<?= $lead['id'] ?><?= $filter!=='all'?'&filter='.$filter:'' ?>"
             onclick="return confirm('Delete this lead?')">Delete</a>
        </td>
      </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </table>
</div>

<?php include '../templates/footer.php'; ?>