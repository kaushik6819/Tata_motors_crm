<?php
require '../config/auth.php';
require '../config/db.php';
include '../templates/header.php';

// ── ADD a new car ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $stmt = $pdo->prepare(
        "INSERT INTO cars (model, variant, color, fuel_type, price, stock_status)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        htmlspecialchars($_POST['model']),
        htmlspecialchars($_POST['variant']),
        htmlspecialchars($_POST['color']),
        $_POST['fuel_type'],
        (float)$_POST['price'],
        $_POST['stock_status']
    ]);
    $success = "Car added to inventory!";
}

// ── UPDATE stock status ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $stmt = $pdo->prepare("UPDATE cars SET stock_status = ? WHERE id = ?");
    $stmt->execute([$_POST['stock_status'], (int)$_POST['car_id']]);
    $success = "Status updated!";
}

// ── DELETE a car ─────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM cars WHERE id = ?");
    $stmt->execute([(int)$_GET['delete']]);
    $success = "Car removed from inventory.";
}

// ── FILTER by status or fuel ─────────────────────────────────
$filter_status = $_GET['status'] ?? 'all';
$filter_fuel   = $_GET['fuel']   ?? 'all';

$where = [];
$params = [];

if ($filter_status !== 'all') {
    $where[]  = "stock_status = ?";
    $params[] = $filter_status;
}
if ($filter_fuel !== 'all') {
    $where[]  = "fuel_type = ?";
    $params[] = $filter_fuel;
}

$sql = "SELECT * FROM cars";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY model ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cars = $stmt->fetchAll();

// ── Summary counts ───────────────────────────────────────────
$statusCounts = $pdo->query(
    "SELECT stock_status, COUNT(*) as total FROM cars GROUP BY stock_status"
)->fetchAll(PDO::FETCH_KEY_PAIR);

$fuelCounts = $pdo->query(
    "SELECT fuel_type, COUNT(*) as total FROM cars GROUP BY fuel_type"
)->fetchAll(PDO::FETCH_KEY_PAIR);

$totalValue = $pdo->query(
    "SELECT SUM(price) FROM cars WHERE stock_status = 'available'"
)->fetchColumn();
?>

<div class="container">
  <h1>Car Inventory</h1>

  <?php if (!empty($success)): ?>
    <div class="msg-success"><?= $success ?></div>
  <?php endif; ?>

  <!-- ── Stat Cards ── -->
  <div class="stat-grid" style="margin-bottom:24px;">
    <div class="stat-card" style="border-left:4px solid #27ae60;">
      <div class="stat-number"><?= $statusCounts['available'] ?? 0 ?></div>
      <div class="stat-label">Available</div>
    </div>
    <div class="stat-card" style="border-left:4px solid #f39c12;">
      <div class="stat-number"><?= $statusCounts['booked'] ?? 0 ?></div>
      <div class="stat-label">Booked</div>
    </div>
    <div class="stat-card" style="border-left:4px solid #3498db;">
      <div class="stat-number"><?= $statusCounts['delivered'] ?? 0 ?></div>
      <div class="stat-label">Delivered</div>
    </div>
    <div class="stat-card" style="border-left:4px solid #9b59b6;">
      <div class="stat-number">
        ₹<?= $totalValue >= 100000
            ? round($totalValue/100000, 1).'L'
            : number_format($totalValue) ?>
      </div>
      <div class="stat-label">Available stock value</div>
    </div>
  </div>

  <!-- ── Filters ── -->
  <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px;">

    <!-- Status filter -->
    <?php
    $sLabels = ['all'=>'All cars','available'=>'Available','booked'=>'Booked','delivered'=>'Delivered'];
    $sColors = ['all'=>['#eee','#555'],'available'=>['#d4edda','#155724'],'booked'=>['#fff3cd','#856404'],'delivered'=>['#cce5ff','#004085']];
    foreach ($sLabels as $val => $label):
      [$bg,$clr] = $sColors[$val];
      $active = ($filter_status === $val && $filter_fuel === 'all') || ($val === 'all' && $filter_status === 'all' && $filter_fuel === 'all');
    ?>
    <a href="?status=<?= $val ?>&fuel=<?= $filter_fuel ?>" style="
      text-decoration:none; padding:6px 14px; border-radius:20px; font-size:13px; font-weight:bold;
      background:<?= $bg ?>; color:<?= $clr ?>;
      border:2px solid <?= ($filter_status===$val) ? $clr : 'transparent' ?>;
    "><?= $label ?></a>
    <?php endforeach; ?>

    <span style="color:#ccc; padding:6px 4px;">|</span>

    <!-- Fuel filter -->
    <?php
    $fLabels = ['all'=>'All fuels','petrol'=>'Petrol','diesel'=>'Diesel','ev'=>'EV','cng'=>'CNG'];
    $fColors = ['all'=>['#eee','#555'],'petrol'=>['#fde8d8','#7d3c10'],'diesel'=>['#e8e8e8','#333'],'ev'=>['#d1f2eb','#0e6655'],'cng'=>['#eaf2fb','#1a5276']];
    foreach ($fLabels as $val => $label):
      [$bg,$clr] = $fColors[$val];
    ?>
    <a href="?status=<?= $filter_status ?>&fuel=<?= $val ?>" style="
      text-decoration:none; padding:6px 14px; border-radius:20px; font-size:13px; font-weight:bold;
      background:<?= $bg ?>; color:<?= $clr ?>;
      border:2px solid <?= ($filter_fuel===$val) ? $clr : 'transparent' ?>;
    "><?= $label ?></a>
    <?php endforeach; ?>

  </div>

  <!-- ── Add Car Form ── -->
  <details style="margin-bottom:24px;">
    <summary style="cursor:pointer; font-size:16px; font-weight:bold; color:#2c3e50; padding:10px 0;">
      + Add New Car to Inventory
    </summary>
    <div style="background:#f8f9fa; padding:20px; border-radius:8px; margin-top:10px;">
      <form method="POST">
        <div class="form-row">
          <div class="form-col">
            <label class="form-label">Model</label>
            <input type="text" name="model" placeholder="e.g. Nexon" required>
          </div>
          <div class="form-col">
            <label class="form-label">Variant</label>
            <input type="text" name="variant" placeholder="e.g. XZ+ (S)">
          </div>
        </div>
        <div class="form-row">
          <div class="form-col">
            <label class="form-label">Color</label>
            <input type="text" name="color" placeholder="e.g. Calgary White">
          </div>
          <div class="form-col">
            <label class="form-label">Fuel type</label>
            <select name="fuel_type">
              <option value="petrol">Petrol</option>
              <option value="diesel">Diesel</option>
              <option value="ev">EV</option>
              <option value="cng">CNG</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-col">
            <label class="form-label">Price (₹)</label>
            <input type="number" name="price" placeholder="e.g. 1050000" min="0" step="1000">
          </div>
          <div class="form-col">
            <label class="form-label">Status</label>
            <select name="stock_status">
              <option value="available">Available</option>
              <option value="booked">Booked</option>
              <option value="delivered">Delivered</option>
            </select>
          </div>
        </div>
        <button type="submit" name="add" style="margin-top:8px;">Add Car</button>
      </form>
    </div>
  </details>

  <!-- ── Cars Table ── -->
  <h2>
    <?php
    $label = $filter_status !== 'all' ? ucfirst($filter_status) : 'All';
    $flabel = $filter_fuel !== 'all' ? ' · '.strtoupper($filter_fuel) : '';
    echo $label.$flabel.' Cars ('.count($cars).')';
    ?>
  </h2>

  <table>
    <tr>
      <th>#</th>
      <th>Model</th>
      <th>Variant</th>
      <th>Color</th>
      <th>Fuel</th>
      <th>Price</th>
      <th>Status</th>
      <th>Action</th>
    </tr>

    <?php if (empty($cars)): ?>
      <tr>
        <td colspan="8" style="text-align:center; color:#999; padding:20px;">
          No cars found. <a href="?">Show all</a>
        </td>
      </tr>
    <?php else: ?>
      <?php foreach ($cars as $car): ?>
      <tr>
        <td><?= $car['id'] ?></td>
        <td><strong><?= htmlspecialchars($car['model']) ?></strong></td>
        <td style="font-size:13px;"><?= htmlspecialchars($car['variant']) ?></td>
        <td>
          <span style="display:inline-flex; align-items:center; gap:6px; font-size:13px;">
            <span style="
              width:12px; height:12px; border-radius:50%; display:inline-block;
              background:<?= colorForCar($car['color']) ?>;
              border:1px solid #ccc;
            "></span>
            <?= htmlspecialchars($car['color']) ?>
          </span>
        </td>
        <td>
          <span class="fuel-badge fuel-<?= $car['fuel_type'] ?>">
            <?= strtoupper($car['fuel_type']) ?>
          </span>
        </td>
        <td style="font-weight:bold; color:#2c3e50;">
          ₹<?= number_format($car['price']) ?>
        </td>

        <!-- Inline status update -->
        <td>
          <form method="POST" style="display:inline">
            <input type="hidden" name="car_id" value="<?= $car['id'] ?>">
            <select name="stock_status" class="edit-select" onchange="this.form.submit()">
              <option value="available" <?= $car['stock_status']==='available'?'selected':'' ?>>Available</option>
              <option value="booked"    <?= $car['stock_status']==='booked'   ?'selected':'' ?>>Booked</option>
              <option value="delivered" <?= $car['stock_status']==='delivered'?'selected':'' ?>>Delivered</option>
            </select>
            <input type="hidden" name="update_status" value="1">
          </form>
        </td>

        <td>
          <a class="delete-btn"
             href="?delete=<?= $car['id'] ?>"
             onclick="return confirm('Remove this car from inventory?')">Delete</a>
        </td>
      </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </table>

  <!-- ── Fuel breakdown ── -->
  <h2 style="margin-top:32px;">Stock by fuel type</h2>
  <div style="display:flex; gap:12px; flex-wrap:wrap;">
    <?php
    $allFuels = ['petrol'=>'#f39c12','diesel'=>'#7f8c8d','ev'=>'#27ae60','cng'=>'#3498db'];
    foreach ($allFuels as $fuel => $color):
      $count = $fuelCounts[$fuel] ?? 0;
      if (!$count) continue;
    ?>
    <div style="
      background:#fff; border:1px solid #e0e0e0; border-radius:8px;
      padding:16px 24px; text-align:center; min-width:100px;
      border-top:4px solid <?= $color ?>;
    ">
      <div style="font-size:24px; font-weight:bold; color:#2c3e50;"><?= $count ?></div>
      <div style="font-size:12px; color:#888; margin-top:4px;"><?= strtoupper($fuel) ?></div>
    </div>
    <?php endforeach; ?>
  </div>

</div>

<?php
// Helper: map color name to a rough CSS color
function colorForCar($name) {
    $map = [
        'white'=>'#fff','black'=>'#222','grey'=>'#888','gray'=>'#888',
        'red'=>'#e74c3c','blue'=>'#3498db','green'=>'#27ae60',
        'silver'=>'#bdc3c7','gold'=>'#f1c40f','orange'=>'#e67e22',
        'brown'=>'#a0522d','yellow'=>'#f9ca24','teal'=>'#1abc9c',
    ];
    $lower = strtolower($name);
    foreach ($map as $keyword => $hex) {
        if (strpos($lower, $keyword) !== false) return $hex;
    }
    return '#ccc';
}
include '../templates/footer.php';
?>