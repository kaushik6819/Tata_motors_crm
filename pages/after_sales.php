<?php
require '../config/auth.php';
require '../config/db.php';
include '../templates/header.php';

$today = date('Y-m-d');

// ── ADD a record ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $pdo->prepare(
        "INSERT INTO after_sales
           (lead_id, contact_id, service_type, scheduled_date, status, notes)
         VALUES (?, ?, ?, ?, ?, ?)"
    )->execute([
        !empty($_POST['lead_id'])    ? (int)$_POST['lead_id']    : null,
        !empty($_POST['contact_id']) ? (int)$_POST['contact_id'] : null,
        $_POST['service_type'],
        $_POST['scheduled_date'],
        $_POST['status'],
        htmlspecialchars($_POST['notes'])
    ]);
    $success = "After-sales record added!";
}

// ── UPDATE status ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $pdo->prepare("UPDATE after_sales SET status = ? WHERE id = ?")
        ->execute([$_POST['as_status'], (int)$_POST['as_id']]);
    $success = "Status updated!";
}

// ── SAVE notes ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_notes'])) {
    $pdo->prepare("UPDATE after_sales SET notes = ? WHERE id = ?")
        ->execute([
            htmlspecialchars($_POST['notes']),
            (int)$_POST['as_id']
        ]);
    $success = "Notes saved!";
}

// ── DELETE ───────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM after_sales WHERE id = ?")
        ->execute([(int)$_GET['delete']]);
    $success = "Record deleted.";
}

// ── FILTER ───────────────────────────────────────────────────
$filter = $_GET['filter'] ?? 'all';

$baseQuery = "
    SELECT
        a.*,
        l.name      AS lead_name,
        l.car_model AS lead_car,
        l.phone     AS lead_phone,
        c.name      AS contact_name,
        c.phone     AS contact_phone
    FROM after_sales a
    LEFT JOIN leads    l ON a.lead_id    = l.id
    LEFT JOIN contacts c ON a.contact_id = c.id
";

if ($filter === 'overdue') {
    $stmt = $pdo->prepare($baseQuery .
        "WHERE a.scheduled_date < ? AND a.status = 'pending'
         ORDER BY a.scheduled_date ASC");
    $stmt->execute([$today]);
} elseif ($filter === 'upcoming') {
    $stmt = $pdo->prepare($baseQuery .
        "WHERE a.scheduled_date >= ? AND a.status = 'pending'
         ORDER BY a.scheduled_date ASC");
    $stmt->execute([$today]);
} elseif ($filter !== 'all') {
    $stmt = $pdo->prepare($baseQuery .
        "WHERE a.status = ? ORDER BY a.scheduled_date ASC");
    $stmt->execute([$filter]);
} else {
    $stmt = $pdo->query($baseQuery . "ORDER BY a.scheduled_date ASC");
}
$records = $stmt->fetchAll();

// ── Summary counts ───────────────────────────────────────────
$statusCounts = $pdo->query(
    "SELECT status, COUNT(*) as n FROM after_sales GROUP BY status"
)->fetchAll(PDO::FETCH_KEY_PAIR);

$overdueCount = $pdo->prepare(
    "SELECT COUNT(*) FROM after_sales
     WHERE scheduled_date < ? AND status = 'pending'"
);
$overdueCount->execute([$today]);
$overdueCount = $overdueCount->fetchColumn();

$upcomingCount = $pdo->prepare(
    "SELECT COUNT(*) FROM after_sales
     WHERE scheduled_date >= ? AND status = 'pending'"
);
$upcomingCount->execute([$today]);
$upcomingCount = $upcomingCount->fetchColumn();

// ── Dropdowns ────────────────────────────────────────────────
$deliveredLeads = $pdo->query(
    "SELECT id, name, phone, car_model, status FROM leads
     ORDER BY
       CASE status
         WHEN 'delivered' THEN 1
         WHEN 'booked'    THEN 2
         ELSE 3
       END,
       name ASC"
)->fetchAll();

$allContacts = $pdo->query(
    "SELECT id, name, phone FROM contacts ORDER BY name ASC"
)->fetchAll();

$serviceLabels = [
    'first_free_service'  => '🔧 First free service',
    'paid_service'        => '🔩 Paid service',
    'complaint'           => '⚠️ Complaint',
    'feedback'            => '💬 Feedback call',
    'insurance_renewal'   => '📋 Insurance renewal',
    'extended_warranty'   => '🛡️ Extended warranty',
];
?>

<div class="container">
  <h1>After-Sales</h1>

  <?php if (!empty($success)): ?>
    <div class="msg-success"><?= $success ?></div>
  <?php endif; ?>

  <!-- ── Stat Cards ── -->
  <div class="stat-grid" style="margin-bottom:24px;">
    <div class="stat-card" style="border-left:4px solid #e74c3c;">
      <div class="stat-number"><?= $overdueCount ?></div>
      <div class="stat-label">Overdue</div>
    </div>
    <div class="stat-card" style="border-left:4px solid #f39c12;">
      <div class="stat-number"><?= $upcomingCount ?></div>
      <div class="stat-label">Upcoming</div>
    </div>
    <div class="stat-card" style="border-left:4px solid #27ae60;">
      <div class="stat-number"><?= $statusCounts['done'] ?? 0 ?></div>
      <div class="stat-label">Done</div>
    </div>
    <div class="stat-card" style="border-left:4px solid #3498db;">
      <div class="stat-number"><?= array_sum($statusCounts) ?></div>
      <div class="stat-label">Total records</div>
    </div>
  </div>

  <!-- ── Filter pills ── -->
  <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:24px;">
    <?php
    $pills = [
      'all'       => ['All',                      '#eee',    '#555'],
      'overdue'   => ['Overdue ('.$overdueCount.')', '#f8d7da', '#721c24'],
      'upcoming'  => ['Upcoming ('.$upcomingCount.')', '#fff3cd', '#856404'],
      'pending'   => ['Pending',                  '#cce5ff', '#004085'],
      'contacted' => ['Contacted',                '#e2d9f3', '#4a235a'],
      'done'      => ['Done',                     '#d4edda', '#155724'],
      'cancelled' => ['Cancelled',                '#e2e3e5', '#383d41'],
    ];
    foreach ($pills as $val => [$label, $bg, $clr]): ?>
    <a href="?filter=<?= $val ?>" style="
      text-decoration:none; padding:6px 14px; border-radius:20px;
      font-size:13px; font-weight:bold;
      background:<?= $bg ?>; color:<?= $clr ?>;
      border:2px solid <?= ($filter===$val) ? $clr : 'transparent' ?>;
    "><?= $label ?></a>
    <?php endforeach; ?>
  </div>

  <!-- ── Add Form ── -->
  <details style="margin-bottom:28px;">
    <summary style="cursor:pointer; font-size:16px; font-weight:bold;
                    color:#2c3e50; padding:10px 0;">
      + Add After-Sales Record
    </summary>
    <div style="background:#f8f9fa; padding:20px; border-radius:8px; margin-top:10px;">
      <form method="POST">
        <div class="form-row">
          <div class="form-col">
            <label class="form-label">Delivered lead (customer)</label>
            <select name="lead_id">
              <option value="">— Select delivered lead —</option>
            <?php foreach ($deliveredLeads as $l): ?>
  <option value="<?= $l['id'] ?>">
    <?php
      $car   = !empty($l['car_model']) ? ' — ' . $l['car_model'] : '';
      $status = '(' . ucfirst(str_replace('_', ' ', $l['status'])) . ')';
      echo htmlspecialchars($l['name'] . $car . ' ' . $status);
    ?>
  </option>
<?php endforeach; ?>
            </select>
          </div>
          <div class="form-col">
            <label class="form-label">Contact (optional)</label>
            <select name="contact_id">
              <option value="">— Link a contact —</option>
              <?php foreach ($allContacts as $c): ?>
                <option value="<?= $c['id'] ?>">
                  <?= htmlspecialchars($c['name']) ?>
                  <?= $c['phone'] ? '— '.$c['phone'] : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-col">
            <label class="form-label">Service type</label>
            <select name="service_type">
              <?php foreach ($serviceLabels as $val => $label): ?>
                <option value="<?= $val ?>"><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-col">
            <label class="form-label">Scheduled date</label>
            <input type="date" name="scheduled_date"
                   value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-col">
            <label class="form-label">Status</label>
            <select name="status">
              <option value="pending">Pending</option>
              <option value="contacted">Contacted</option>
              <option value="done">Done</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
          <div class="form-col">
            <label class="form-label">Notes</label>
            <input type="text" name="notes" placeholder="Any notes...">
          </div>
        </div>
        <button type="submit" name="add" style="margin-top:8px;">
          Add Record
        </button>
      </form>
    </div>
  </details>

  <!-- ── Records Table ── -->
  <h2>
    <?= ucfirst($filter === 'all' ? 'All Records' : $filter.' records') ?>
    (<?= count($records) ?>)
  </h2>

  <?php if (empty($records)): ?>
    <p style="color:#999; text-align:center; padding:20px 0;">
      No records found.
      <?= $filter !== 'all' ? '<a href="?filter=all">Show all</a>' : '' ?>
    </p>
  <?php else: ?>
  <table>
    <tr>
      <th>#</th>
      <th>Customer</th>
      <th>Car / Phone</th>
      <th>Service type</th>
      <th>Scheduled</th>
      <th>Status</th>
      <th>Notes</th>
      <th>Action</th>
    </tr>
    <?php foreach ($records as $r):
      $isOverdue = $r['scheduled_date'] < $today && $r['status'] === 'pending';
      $isDueToday = $r['scheduled_date'] === $today && $r['status'] === 'pending';
    ?>
    <tr style="<?= $isOverdue ? 'background:#fff5f5;' : ($isDueToday ? 'background:#fffbf0;' : '') ?>">
      <td><?= $r['id'] ?></td>

      <td>
        <strong>
          <?= htmlspecialchars($r['lead_name'] ?? $r['contact_name'] ?? '—') ?>
        </strong>
        <?php if ($r['contact_name'] && $r['lead_name']): ?>
          <span style="font-size:12px; color:#888; display:block;">
            <?= htmlspecialchars($r['contact_name']) ?>
          </span>
        <?php endif; ?>
      </td>

      <td style="font-size:13px;">
        <?php if ($r['lead_car']): ?>
          <strong><?= htmlspecialchars($r['lead_car']) ?></strong><br>
        <?php endif; ?>
        <span style="color:#888;">
          <?= htmlspecialchars($r['lead_phone'] ?? $r['contact_phone'] ?? '—') ?>
        </span>
      </td>

      <td>
        <span class="as-badge as-<?= $r['service_type'] ?>">
          <?= $serviceLabels[$r['service_type']] ?? $r['service_type'] ?>
        </span>
      </td>

      <td>
        <strong style="<?= $isOverdue ? 'color:#e74c3c;' : ($isDueToday ? 'color:#e67e22;' : '') ?>">
          <?= date('d M Y', strtotime($r['scheduled_date'])) ?>
        </strong>
        <?php if ($isOverdue): ?>
          <span style="font-size:11px; color:#e74c3c; display:block;">Overdue</span>
        <?php elseif ($isDueToday): ?>
          <span style="font-size:11px; color:#e67e22; display:block;">Today</span>
        <?php endif; ?>
      </td>

      <!-- Inline status -->
      <td>
        <form method="POST" style="display:inline">
          <input type="hidden" name="as_id" value="<?= $r['id'] ?>">
          <select name="as_status" class="edit-select" onchange="this.form.submit()">
            <option value="pending"   <?= $r['status']==='pending'  ?'selected':'' ?>>Pending</option>
            <option value="contacted" <?= $r['status']==='contacted'?'selected':'' ?>>Contacted</option>
            <option value="done"      <?= $r['status']==='done'     ?'selected':'' ?>>Done</option>
            <option value="cancelled" <?= $r['status']==='cancelled'?'selected':'' ?>>Cancelled</option>
          </select>
          <input type="hidden" name="update_status" value="1">
        </form>
      </td>

      <!-- Inline notes -->
      <td style="min-width:180px;">
        <form method="POST">
          <input type="hidden" name="as_id" value="<?= $r['id'] ?>">
          <input type="text" name="notes"
                 placeholder="Add note..."
                 value="<?= htmlspecialchars($r['notes'] ?? '') ?>"
                 style="font-size:12px; padding:4px 8px; margin:0 0 4px;">
          <button type="submit" name="save_notes" class="save-btn">Save</button>
        </form>
      </td>

      <td>
        <a class="delete-btn"
           href="?delete=<?= $r['id'] ?>&filter=<?= $filter ?>"
           onclick="return confirm('Delete this record?')">Delete</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>

  <!-- ── Overdue alert box ── -->
  <?php if ($overdueCount > 0 && $filter === 'all'): ?>
  <div style="
    margin-top:32px; background:#fff5f5; border:1px solid #f5c6cb;
    border-left:4px solid #e74c3c; border-radius:8px; padding:16px 20px;
  ">
    <strong style="color:#721c24;">
      ⚠️ <?= $overdueCount ?> overdue record<?= $overdueCount > 1 ? 's' : '' ?>
    </strong>
    <p style="margin:6px 0 0; font-size:14px; color:#721c24;">
      These customers have not been contacted yet and their scheduled date has passed.
      <a href="?filter=overdue" style="color:#721c24; font-weight:bold;">
        View overdue →
      </a>
    </p>
  </div>
  <?php endif; ?>

</div>

<?php include '../templates/footer.php'; ?>