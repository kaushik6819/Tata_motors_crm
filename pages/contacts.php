<?php
session_start();
require '../config/db.php';
include '../templates/header.php';

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $stmt = $pdo->prepare("INSERT INTO contacts (name, email, phone) VALUES (?, ?, ?)");
    $stmt->execute([
        htmlspecialchars($_POST['name']),
        htmlspecialchars($_POST['email']),
        htmlspecialchars($_POST['phone'])
    ]);
    $success = "Contact added!";
}

// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = ?");
    $stmt->execute([(int)$_GET['delete']]);
    $success = "Contact deleted.";
}

// Fetch all contacts
$contacts = $pdo->query("SELECT * FROM contacts ORDER BY created_at DESC")->fetchAll();
?>

<div class="container">
  <h1>Contacts</h1>

  <?php if (!empty($success)): ?>
    <div class="msg-success"><?= $success ?></div>
  <?php endif; ?>

  <!-- Add Contact Form -->
  <h2>Add New Contact</h2>
  <form method="POST">
    <input type="text"  name="name"  placeholder="Full Name"    required>
    <input type="email" name="email" placeholder="Email address">
    <input type="text"  name="phone" placeholder="Phone number">
    <button type="submit" name="add">Add Contact</button>
  </form>

  <!-- Contacts Table -->
  <h2>All Contacts (<?= count($contacts) ?>)</h2>
  <table>
    <tr>
      <th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Added</th><th>Action</th>
    </tr>
    <?php if (empty($contacts)): ?>
      <tr><td colspan="6" style="color:#999">No contacts yet. Add one above!</td></tr>
    <?php else: ?>
      <?php foreach ($contacts as $c): ?>
      <tr>
        <td><?= $c['id'] ?></td>
        <td><?= htmlspecialchars($c['name']) ?></td>
        <td><?= htmlspecialchars($c['email']) ?></td>
        <td><?= htmlspecialchars($c['phone']) ?></td>
        <td><?= date('d M Y', strtotime($c['created_at'])) ?></td>
        <td><a class="delete-btn" href="?delete=<?= $c['id'] ?>" onclick="return confirm('Delete this contact?')">Delete</a></td>
      </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </table>
</div>

<?php include '../templates/footer.php'; ?>