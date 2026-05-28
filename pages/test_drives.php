<?php
require '../config/auth.php';
require '../config/db.php';
include '../templates/header.php';

// ── ADD a test drive ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $stmt = $pdo->prepare(
        "INSERT INTO test_drives
           (lead_id, car_id, scheduled_date, scheduled_time, salesperson, status)
         VALUES (?, ?, ?, ?, ?, 'scheduled')"
    );
    $stmt->execute([
        !empty($_POST['lead_id']) ? (int)$_POST['lead_id'] : null,
        !empty($_POST['car_id'])  ? (int)$_POST['car_id']  : null,
        $_POST['scheduled_date'],
        $_POST['scheduled_time'],
        htmlspecialchars($_POST['salesperson'])
    ]);

    // Update the linked lead status to test_drive_scheduled
    if (!empty($_POST['lead_id'])) {
        $pdo->prepare("UPDATE leads SET status='test_drive_scheduled' WHERE id=?")
            ->execute([(int)$_POST['lead_id']]);
    }

    $success = "Test drive scheduled!";
}

// ── UPDATE status ────────────────────────────────────────────
// ── UPDATE status ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $td_id     = (int)$_POST['td_id'];
    $td_status = $_POST['td_status'];

    // Update test drive status
    $stmt = $pdo->prepare("UPDATE test_drives SET status = ? WHERE id = ?");
    $stmt->execute([$td_status, $td_id]);

    // Fetch the linked lead_id directly from DB — don't trust the hidden field
    $row = $pdo->prepare("SELECT lead_id FROM test_drives WHERE id = ?");
    $row->execute([$td_id]);
    $linked = $row->fetch();
    $linked_lead_id = $linked['lead_id'] ?? null;

    if ($linked_lead_id) {
        if ($td_status === 'completed') {
            $pdo->prepare("UPDATE leads SET status = 'quote_sent' WHERE id = ?")
                ->execute([$linked_lead_id]);
        } elseif ($td_status === 'cancelled' || $td_status === 'no_show') {
            $pdo->prepare("UPDATE leads SET status = 'new' WHERE id = ?")
                ->execute([$linked_lead_id]);
        }
    }

    $success = "Status updated!";
}

// ── SAVE feedback ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_feedback'])) {
    $stmt = $pdo->prepare("UPDATE test_drives SET feedback=? WHERE id=?");
    $stmt->execute([
        htmlspecialchars($_POST['feedback']),
        (int)$_POST['td_id']
    ]);
    $success = "Feedback saved!";
}

// ── DELETE ───────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM test_drives WHERE id=?")
        ->execute([(int)$_GET['delete']]);
    $success = "Test drive removed.";
}

// ── FILTER ───────────────────────────────────────────────────
$filter = $_GET['filter'] ?? 'all';
$today  = date('Y-m-d');

if ($filter === 'today') {
    $stmt = $pdo->prepare(
        "SELECT td.*,
                l.name  AS lead_name,  l.phone AS lead_phone,
                c.model AS car_model,  c.variant AS car_variant, c.color AS car_color
         FROM test_drives td
         LEFT JOIN leads l ON td.lead_id = l.id
         LEFT JOIN cars  c ON td.car_id  = c.id
         WHERE td.scheduled_date = ?
         ORDER BY td.scheduled_time ASC"
    );
    $stmt->execute([$today]);
} elseif ($filter !== 'all') {
    $stmt = $pdo->prepare(
        "SELECT td.*,
                l.name  AS lead_name,  l.phone AS lead_phone,
                c.model AS car_model,  c.variant AS car_variant, c.color AS car_color
         FROM test_drives td
         LEFT JOIN leads l ON td.lead_id = l.id
         LEFT JOIN cars  c ON td.car_id  = c.id
         WHERE td.status = ?
         ORDER BY td.scheduled_date DESC, td.scheduled_time ASC"
    );
    $stmt->execute([$filter]);
} else {
    $stmt = $pdo->query(
        "SELECT td.*,
                l.name  AS lead_name,  l.phone AS lead_phone,
                c.model AS car_model,  c.variant AS car_variant, c.color AS car_color
         FROM test_drives td
         LEFT JOIN leads l ON td.lead_id = l.id
         LEFT JOIN cars  c ON td.car_id  = c.id
         ORDER BY td.scheduled_date DESC, td.scheduled_time ASC"
    );
}
$drives = $stmt->fetchAll();

// ── Counts for summary pills ─────────────────────────────────
$counts = $pdo->query(
    "SELECT status, COUNT(*) as total FROM test_drives GROUP BY status"
)->fetchAll(PDO::FETCH_KEY_PAIR);

$todayCount = $pdo->prepare(
    "SELECT COUNT(*) FROM test_drives WHERE scheduled_date = ?"
);
$todayCount->execute([$today]);
$todayCount = $todayCount->fetchColumn();

// ── Dropdowns for form ───────────────────────────────────────
$allLeads = $pdo->query(
    "SELECT id, name, phone FROM leads
     WHERE status NOT IN ('delivered','lost')
     ORDER BY name ASC"
)->fetchAll();

$availableCars = $pdo->query(
    "SELECT id, model, variant, color FROM cars
     WHERE stock_status = 'available'
     ORDER BY model ASC"
)->fetchAll();
?>

<div class="container">
  <h1>Test Drives</h1>

  <?php if (!empty($success)): ?>
    <div class="msg-success"><?= $success ?></div>
  <?php endif; ?>

  <!-- ── Stat Cards ── -->
  <div class="stat-grid" style="margin-bottom:24px;">
    <div class="stat-card" style="border-left:4px solid #e67e22;">
      <div class="stat-number"><?= $todayCount ?></div>
      <div class="stat-label">Today's drives</div>
    </div>
    <div class="stat-card" style="border-left:4px solid #3498db;">
      <div class="stat-number"><?= $counts['scheduled'] ?? 0 ?></div>
      <div class="stat-label">Scheduled</div>
    </div>
    <div class="stat-card" style="border-left:4px solid #27ae60;">
      <div class="stat-number"><?= $counts['completed'] ?? 0 ?></div>
      <div class="stat-label">Completed</div>
    </div>
    <div class="stat-card" style="border-left:4px solid #e74c3c;">
      <div class="stat-number"><?= ($counts['cancelled'] ?? 0) + ($counts['no_show'] ?? 0) ?></div>
      <div class="stat-label">Cancelled / No-show</div>
    </div>
  </div>

  <!-- ── Filter pills ── -->
  <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:24px;">
    <?php
    $pills = [
      'all'       => ['All drives',  '#eee',    '#555'],
      'today'     => ['Today ('.$todayCount.')', '#fff3cd', '#856404'],
      'scheduled' => ['Scheduled',   '#cce5ff', '#004085'],
      'completed' => ['Completed',   '#d4edda', '#155724'],
      'cancelled' => ['Cancelled',   '#f8d7da', '#721c24'],
      'no_show'   => ['No-show',     '#e2e3e5', '#383d41'],
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

  <!-- ── Schedule Form ── -->
  <details style="margin-bottom:28px;">
    <summary style="cursor:pointer; font-size:16px; font-weight:bold;
                    color:#2c3e50; padding:10px 0;">
      + Schedule a New Test Drive
    </summary>
    <div style="background:#f8f9fa; padding:20px; border-radius:8px; margin-top:10px;">
      <form method="POST">
        <div class="form-row">
          <div class="form-col">
            <label class="form-label">Customer (lead)</label>
            <select name="lead_id" required>
              <option value="">— Select customer —</option>
              <?php foreach ($allLeads as $l): ?>
                <option value="<?= $l['id'] ?>">
                  <?= htmlspecialchars($l['name']) ?>
                  <?= $l['phone'] ? '— '.$l['phone'] : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-col">
            <label class="form-label">Car to test drive</label>
            <select name="car_id" required>
              <option value="">— Select car —</option>
              <?php foreach ($availableCars as $c): ?>
                <option value="<?= $c['id'] ?>">
                  <?= htmlspecialchars($c['model']) ?>
                  <?= htmlspecialchars($c['variant']) ?>
                  — <?= htmlspecialchars($c['color']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-col">
            <label class="form-label">Date</label>
            <input type="date" name="scheduled_date"
                   value="<?= $today ?>" min="<?= $today ?>" required>
          </div>
          <div class="form-col">
            <label class="form-label">Time</label>
            <input type="time" name="scheduled_time" value="10:00" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-col">
            <label class="form-label">Salesperson</label>
            <input type="text" name="salesperson"
                   placeholder="e.g. Rahul Sharma"
                   value="<?= htmlspecialchars($_SESSION['username']) ?>">
          </div>
        </div>
        <button type="submit" name="add" style="margin-top:8px;">
          Schedule Test Drive
        </button>
      </form>
    </div>
  </details>

  <!-- ── Test Drives Table ── -->
  <h2>
    <?= $filter === 'all' ? 'All Test Drives' : ucfirst(str_replace('_',' ',$filter)).' Test Drives' ?>
    (<?= count($drives) ?>)
  </h2>

  <?php if (empty($drives)): ?>
    <p style="color:#999; text-align:center; padding:20px 0;">
      No test drives found.
      <?= $filter !== 'all' ? '<a href="?filter=all">Show all</a>' : '' ?>
    </p>
  <?php else: ?>
  <table>
    <tr>
      <th>#</th>
      <th>Customer</th>
      <th>Phone</th>
      <th>Car</th>
      <th>Date & Time</th>
      <th>Salesperson</th>
      <th>Status</th>
      <th>Feedback</th>
      <th>Action</th>
    </tr>
    <?php foreach ($drives as $td):
      $isPast = $td['scheduled_date'] < $today;
      $isToday = $td['scheduled_date'] === $today;
    ?>
    <tr style="<?= $isToday ? 'background:#fffbf0;' : '' ?>">
      <td><?= $td['id'] ?></td>

      <td>
        <?php if ($td['lead_name']): ?>
          <a href="contact_view.php?id=<?= $td['lead_id'] ?>"
             style="color:#2c3e50; text-decoration:none; font-weight:bold;">
            <?= htmlspecialchars($td['lead_name']) ?>
          </a>
        <?php else: ?>
          <span style="color:#bbb;">—</span>
        <?php endif; ?>
      </td>

      <td style="font-size:13px; color:#666;">
        <?= htmlspecialchars($td['lead_phone'] ?? '—') ?>
      </td>

      <td>
        <?php if ($td['car_model']): ?>
          <strong><?= htmlspecialchars($td['car_model']) ?></strong>
          <span style="font-size:12px; color:#888; display:block;">
            <?= htmlspecialchars($td['car_variant'] ?? '') ?>
            <?= $td['car_color'] ? '· '.$td['car_color'] : '' ?>
          </span>
        <?php else: ?>
          <span style="color:#bbb;">—</span>
        <?php endif; ?>
      </td>

      <td>
        <strong style="<?= $isToday ? 'color:#e67e22;' : '' ?>">
          <?= date('d M Y', strtotime($td['scheduled_date'])) ?>
        </strong>
        <span style="font-size:12px; color:#888; display:block;">
          <?= date('h:i A', strtotime($td['scheduled_time'])) ?>
          <?= $isToday ? ' — Today' : '' ?>
        </span>
      </td>

      <td style="font-size:13px;">
        <?= htmlspecialchars($td['salesperson'] ?? '—') ?>
      </td>

      <!-- Inline status update -->
      <td>
        <form method="POST" style="display:inline">
          <input type="hidden" name="td_id" value="<?= $td['id'] ?>">
          <input type="hidden" name="linked_lead_id" value="<?= $td['lead_id'] ?>">
          <select name="td_status" class="edit-select" onchange="this.form.submit()">
            <option value="scheduled"  <?= $td['status']==='scheduled' ?'selected':'' ?>>Scheduled</option>
            <option value="completed"  <?= $td['status']==='completed' ?'selected':'' ?>>Completed</option>
            <option value="cancelled"  <?= $td['status']==='cancelled' ?'selected':'' ?>>Cancelled</option>
            <option value="no_show"    <?= $td['status']==='no_show'   ?'selected':'' ?>>No-show</option>
          </select>
          <input type="hidden" name="update_status" value="1">
        </form>
      </td>

      <!-- Feedback inline form -->
      <td style="min-width:180px;">
        <form method="POST">
          <input type="hidden" name="td_id" value="<?= $td['id'] ?>">
          <input type="text" name="feedback"
                 placeholder="Customer reaction..."
                 value="<?= htmlspecialchars($td['feedback'] ?? '') ?>"
                 style="font-size:12px; padding:4px 8px; margin:0 0 4px;">
          <button type="submit" name="save_feedback" class="save-btn">Save</button>
        </form>
      </td>

      <td>
        <a class="delete-btn"
           href="?delete=<?= $td['id'] ?>&filter=<?= $filter ?>"
           onclick="return confirm('Remove this test drive?')">Delete</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>

  <!-- ── Today's schedule card ── -->
  <?php
  $todayDrives = $pdo->prepare(
      "SELECT td.*, l.name AS lead_name, l.phone AS lead_phone,
              c.model AS car_model, c.variant AS car_variant
       FROM test_drives td
       LEFT JOIN leads l ON td.lead_id = l.id
       LEFT JOIN cars  c ON td.car_id  = c.id
       WHERE td.scheduled_date = ? AND td.status = 'scheduled'
       ORDER BY td.scheduled_time ASC"
  );
  $todayDrives->execute([$today]);
  $todayList = $todayDrives->fetchAll();
  ?>

  <?php if (!empty($todayList)): ?>
  <h2 style="margin-top:36px;">Today's Schedule</h2>
  <div style="display:flex; flex-direction:column; gap:12px;">
    <?php foreach ($todayList as $td): ?>
    <div style="
      background:#fff; border:1px solid #e0e0e0; border-radius:8px;
      padding:16px 20px; border-left:4px solid #e67e22;
      display:flex; justify-content:space-between; align-items:center;
      flex-wrap:wrap; gap:10px;
    ">
      <div>
        <strong style="font-size:15px;">
          <?= htmlspecialchars($td['lead_name'] ?? 'Unknown') ?>
        </strong>
        <span style="color:#888; font-size:13px; margin-left:8px;">
          <?= htmlspecialchars($td['lead_phone'] ?? '') ?>
        </span>
        <div style="font-size:13px; color:#555; margin-top:4px;">
          Driving: <strong><?= htmlspecialchars($td['car_model'].' '.($td['car_variant']??'')) ?></strong>
        </div>
      </div>
      <div style="text-align:right;">
        <div style="font-size:20px; font-weight:bold; color:#e67e22;">
          <?= date('h:i A', strtotime($td['scheduled_time'])) ?>
        </div>
        <div style="font-size:12px; color:#999;">
          <?= htmlspecialchars($td['salesperson'] ?? '') ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>

<?php include '../templates/footer.php'; ?>