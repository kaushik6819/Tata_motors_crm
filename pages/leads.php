<?php
require '../config/auth.php';
require '../config/db.php';
include '../templates/header.php';

// ── ADD a new lead ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $contact_id = !empty($_POST['contact_id']) ? (int)$_POST['contact_id'] : null;
    $stmt = $pdo->prepare(
        "INSERT INTO leads (name, email, phone, status, notes, contact_id, source, car_model)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        htmlspecialchars($_POST['name']),
        htmlspecialchars($_POST['email']),
        htmlspecialchars($_POST['phone']),
        $_POST['status'],
        htmlspecialchars($_POST['notes']),
        $contact_id,
        $_POST['source'] ?? 'walk_in',
        htmlspecialchars($_POST['car_model'] ?? '')
    ]);
    $success = "Lead added successfully!";
}

// ── UPDATE status ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $lead_id    = (int)$_POST['lead_id'];
    $new_status = $_POST['status'];

    $pdo->prepare("UPDATE leads SET status = ? WHERE id = ?")
        ->execute([$new_status, $lead_id]);

    // When marked delivered → auto-create first free service reminder
    if ($new_status === 'delivered') {
        $exists = $pdo->prepare(
            "SELECT COUNT(*) FROM after_sales
             WHERE lead_id = ? AND service_type = 'first_free_service'"
        );
        $exists->execute([$lead_id]);

        if ($exists->fetchColumn() == 0) {
            $lead_row = $pdo->prepare("SELECT contact_id FROM leads WHERE id = ?");
            $lead_row->execute([$lead_id]);
            $lead_data = $lead_row->fetch();

            $pdo->prepare(
                "INSERT INTO after_sales
                   (lead_id, contact_id, service_type, scheduled_date, status, notes)
                 VALUES (?, ?, 'first_free_service', ?, 'pending', ?)"
            )->execute([
                $lead_id,
                $lead_data['contact_id'] ?? null,
                date('Y-m-d', strtotime('+30 days')),
                'Auto-created: first free service due 30 days after delivery'
            ]);
        }
    }
    $success = "Status updated!";
}

// ── DELETE a lead ───────────────────────────────────────────
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM leads WHERE id = ?")
        ->execute([(int)$_GET['delete']]);
    $success = "Lead deleted.";
}

// ── FILTER by status ────────────────────────────────────────
$filter = $_GET['filter'] ?? 'all';

if ($filter !== 'all') {
    $stmt = $pdo->prepare(
        "SELECT leads.*, contacts.name AS contact_name
         FROM leads
         LEFT JOIN contacts ON leads.contact_id = contacts.id
         WHERE leads.status = ?
         ORDER BY leads.created_at DESC"
    );
    $stmt->execute([$filter]);
    $leads = $stmt->fetchAll();
} else {
    $leads = $pdo->query(
        "SELECT leads.*, contacts.name AS contact_name
         FROM leads
         LEFT JOIN contacts ON leads.contact_id = contacts.id
         ORDER BY leads.created_at DESC"
    )->fetchAll();
}

// ── STATUS counts ───────────────────────────────────────────
$counts = $pdo->query(
    "SELECT status, COUNT(*) as total FROM leads GROUP BY status"
)->fetchAll(PDO::FETCH_KEY_PAIR);

// ── All contacts for dropdown ───────────────────────────────
$allContacts = $pdo->query(
    "SELECT id, name FROM contacts ORDER BY name ASC"
)->fetchAll();

// ALL valid statuses matching your ENUM exactly
$statuses = [
    'new'                   => '🔵 New',
    'test_drive_scheduled'  => '🚗 Test Drive Scheduled',
    'quote_sent'            => '📄 Quote Sent',
    'negotiating'           => '🤝 Negotiating',
    'booked'                => '✅ Booked',
    'delivered'             => '🎉 Delivered',
    'lost'                  => '❌ Lost',
];
?>

<div class="container">
  <h1>Leads</h1>

  <?php if (!empty($success)): ?>
    <div class="msg-success"><?= $success ?></div>
  <?php endif; ?>

  <!-- ── Summary filter pills ── -->
  <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:24px;">
    <?php
    $pillColors = [
      'all'                  => ['#eee',    '#555'],
      'new'                  => ['#cce5ff', '#004085'],
      'test_drive_scheduled' => ['#fff3cd', '#856404'],
      'quote_sent'           => ['#d1ecf1', '#0c5460'],
      'negotiating'          => ['#e2d9f3', '#4a235a'],
      'booked'               => ['#d4edda', '#155724'],
      'delivered'            => ['#d4edda', '#155724'],
      'lost'                 => ['#f8d7da', '#721c24'],
    ];

    // All pill
    [$bg,$clr] = $pillColors['all'];
    $total = array_sum($counts);
    echo "<a href='?' style='text-decoration:none; padding:6px 14px; border-radius:20px;
      font-size:13px; font-weight:bold; background:$bg; color:$clr;
      border:2px solid " . ($filter==='all' ? $clr : 'transparent') . ";'>
      All ($total)</a>";

    foreach ($statuses as $val => $label):
      [$bg,$clr] = $pillColors[$val] ?? ['#eee','#555'];
      $count = $counts[$val] ?? 0;
    ?>
    <a href="?filter=<?= $val ?>" style="
      text-decoration:none; padding:6px 14px; border-radius:20px;
      font-size:13px; font-weight:bold;
      background:<?= $bg ?>; color:<?= $clr ?>;
      border:2px solid <?= ($filter===$val) ? $clr : 'transparent' ?>;
    "><?= $label ?> (<?= $count ?>)</a>
    <?php endforeach; ?>
  </div>

  <!-- ── Add Lead Form ── -->
  <details style="margin-bottom:28px;">
    <summary style="cursor:pointer; font-size:16px; font-weight:bold;
                    color:#2c3e50; padding:10px 0;">
      + Add New Lead
    </summary>
    <div style="background:#f8f9fa; padding:20px; border-radius:8px; margin-top:10px;">
      <form method="POST">
        <div class="form-row">
          <div class="form-col">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" placeholder="Customer full name" required>
          </div>
          <div class="form-col">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" placeholder="Phone number">
          </div>
        </div>
        <div class="form-row">
          <div class="form-col">
            <label class="form-label">Email</label>
            <input type="email" name="email" placeholder="Email address">
          </div>
          <div class="form-col">
            <label class="form-label">Car model interested in</label>
            <input type="text" name="car_model" placeholder="e.g. Nexon, Punch, Harrier">
          </div>
        </div>
        <div class="form-row">
          <div class="form-col">
            <label class="form-label">Source</label>
            <select name="source">
              <option value="walk_in">🚶 Walk-in</option>
              <option value="phone">📞 Phone</option>
              <option value="website">🌐 Website</option>
              <option value="whatsapp">💬 WhatsApp</option>
              <option value="referral">🤝 Referral</option>
            </select>
          </div>
          <div class="form-col">
            <label class="form-label">Link to contact</label>
            <select name="contact_id">
              <option value="">— Link a contact (optional) —</option>
              <?php foreach ($allContacts as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-col">
            <label class="form-label">Initial status</label>
            <select name="status">
              <?php foreach ($statuses as $val => $label): ?>
                <option value="<?= $val ?>"><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-col">
            <label class="form-label">Notes</label>
            <input type="text" name="notes" placeholder="Any notes...">
          </div>
        </div>
        <button type="submit" name="add" style="margin-top:8px;">Add Lead</button>
      </form>
    </div>
  </details>

  <!-- ── Leads Table ── -->
  <h2>
    <?= $filter !== 'all' ? ($statuses[$filter] ?? ucfirst($filter)).' Leads' : 'All Leads' ?>
    (<?= count($leads) ?>)
  </h2>

  <table>
    <tr>
      <th>#</th>
      <th>Name</th>
      <th>Linked Contact</th>
      <th>Phone</th>
      <th>Car</th>
      <th>Source</th>
      <th>Status</th>
      <th>Notes</th>
      <th>Added</th>
      <th>Action</th>
    </tr>

    <?php if (empty($leads)): ?>
      <tr><td colspan="10" style="color:#999; text-align:center; padding:20px;">
        No leads found. <?= $filter !== 'all' ? '<a href="?">Show all</a>' : '' ?>
      </td></tr>
    <?php else: ?>
      <?php foreach ($leads as $lead): ?>
      <tr>
        <td><?= $lead['id'] ?></td>
        <td><strong><?= htmlspecialchars($lead['name']) ?></strong></td>

        <td>
          <?php if ($lead['contact_name']): ?>
            <a href="contact_view.php?id=<?= $lead['contact_id'] ?>"
               style="color:#2c3e50; font-size:13px; text-decoration:none;">
              👤 <?= htmlspecialchars($lead['contact_name']) ?>
            </a>
          <?php else: ?>
            <span style="color:#bbb; font-size:12px;">None</span>
          <?php endif; ?>
        </td>

        <td><?= htmlspecialchars($lead['phone'] ?? '') ?></td>

        <td style="font-size:13px;">
          <?= htmlspecialchars($lead['car_model'] ?? '—') ?>
        </td>

        <td style="font-size:12px; color:#666;">
          <?php
          $sourceIcons = [
            'walk_in'  => '🚶 Walk-in',
            'phone'    => '📞 Phone',
            'website'  => '🌐 Website',
            'whatsapp' => '💬 WhatsApp',
            'referral' => '🤝 Referral',
          ];
          echo $sourceIcons[$lead['source']] ?? ucfirst($lead['source'] ?? '—');
          ?>
        </td>

        <!-- Inline status update — uses exact ENUM values -->
        <td>
          <form method="POST" style="display:inline">
            <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
            <select name="status" class="edit-select" onchange="this.form.submit()">
              <?php foreach ($statuses as $val => $label): ?>
                <option value="<?= $val ?>"
                  <?= $lead['status'] === $val ? 'selected' : '' ?>>
                  <?= $label ?>
                </option>
              <?php endforeach; ?>
            </select>
            <input type="hidden" name="update_status" value="1">
          </form>
        </td>

        <td style="font-size:13px; color:#666; max-width:140px;">
          <?= htmlspecialchars($lead['notes'] ?? '') ?>
        </td>

        <td><?= date('d M Y', strtotime($lead['created_at'])) ?></td>

        <td>
          <a class="delete-btn"
             href="?delete=<?= $lead['id'] ?><?= $filter !== 'all' ? '&filter='.$filter : '' ?>"
             onclick="return confirm('Delete this lead?')">Delete</a>
        </td>
      </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </table>
</div>

<?php include '../templates/footer.php'; ?>