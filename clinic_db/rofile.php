<?php
session_start();
if (!isset($_SESSION['user'])) {
  echo "<p class='text-danger'>Please log in first.</p>";
  exit();
}
$user = $_SESSION['user'];
?>
<div class="container py-4">
  <h3 class="mb-3">My Profile</h3>
  <div class="card p-4 shadow-sm">
    <p><strong>Full Name:</strong> <?= htmlspecialchars($user['fullname'] ?? 'N/A') ?></p>
    <p><strong>Username:</strong> <?= htmlspecialchars($user['username'] ?? 'N/A') ?></p>
    <p><strong>Role:</strong> <?= htmlspecialchars($user['role'] ?? 'Patient') ?></p>
    <hr>
    <p class="text-muted small">Profile info displayed here (you can edit this page later).</p>
  </div>
</div>
