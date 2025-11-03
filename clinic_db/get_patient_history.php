<?php
session_start();
require_once 'db_connect.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') { echo '<div class="text-danger">Unauthorized</div>'; exit; }
$doctorId = intval($_SESSION['user']['id']);
$patientId = intval($_GET['patient_id'] ?? 0);
if (!$patientId) { echo '<div class="text-muted">Invalid patient.</div>'; exit; }

// fetch patient basic info
$stmt = $conn->prepare("SELECT id, fullname, username, contact FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i',$patientId); $stmt->execute(); $u = $stmt->get_result()->fetch_assoc();
?>
<div class="mb-3">
  <h6><?= htmlspecialchars($u['fullname'] ?? 'Patient') ?> <small class="text-muted">(<?= htmlspecialchars($u['username'] ?? '') ?>)</small></h6>
  <p class="small-muted">Contact: <?= htmlspecialchars($u['contact'] ?? '-') ?></p>
</div>

<h6 class="mt-3">Recent Appointments</h6>
<?php
$stmtA = $conn->prepare("SELECT date, time, status, notes FROM appointments WHERE doctor_id = ? AND patient_id = ? ORDER BY date DESC, time DESC LIMIT 10");
$stmtA->bind_param('ii', $doctorId, $patientId); $stmtA->execute(); $ra = $stmtA->get_result();
if ($ra->num_rows > 0):
?>
  <ul class="list-group mb-3">
    <?php while($a = $ra->fetch_assoc()): ?>
      <li class="list-group-item">
        <strong><?= htmlspecialchars($a['date']) ?> <?= htmlspecialchars($a['time']) ?></strong>
        <div class="small-muted"><?= htmlspecialchars($a['status']) ?> — <?= htmlspecialchars($a['notes'] ?: '-') ?></div>
      </li>
    <?php endwhile; ?>
  </ul>
<?php else: ?>
  <p class="text-muted small">No appointments found.</p>
<?php endif; ?>

<h6 class="mt-3">Recent Prescriptions</h6>
<?php
$stmtP = $conn->prepare("SELECT date_issued, medicine, dosage, instructions FROM prescriptions WHERE doctor_id = ? AND patient_id = ? ORDER BY date_issued DESC LIMIT 10");
$stmtP->bind_param('ii', $doctorId, $patientId); $stmtP->execute(); $rp = $stmtP->get_result();
if ($rp->num_rows > 0):
?>
  <ul class="list-group">
    <?php while($p = $rp->fetch_assoc()): ?>
      <li class="list-group-item">
        <strong><?= htmlspecialchars($p['date_issued']) ?></strong>
        <div><?= htmlspecialchars($p['medicine']) ?> — <?= htmlspecialchars($p['dosage'] ?: '-') ?></div>
        <div class="small-muted"><?= htmlspecialchars($p['instructions'] ?: '-') ?></div>
      </li>
    <?php endwhile; ?>
  </ul>
<?php else: ?>
  <p class="text-muted small">No prescriptions found.</p>
<?php endif; ?>
