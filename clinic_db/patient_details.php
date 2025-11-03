<?php
require_once 'db_connect.php';
session_start();

// ✅ Restrict to doctors
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    echo "<p class='text-danger'>Unauthorized access.</p>";
    exit;
}

// ✅ Validate patient ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p class='text-danger'>Invalid patient ID.</p>";
    exit;
}

$patient_id = intval($_GET['id']);
$doctor_id = intval($_SESSION['user']['id']);

// ✅ Fetch patient info
$stmt = $conn->prepare("SELECT id, username, fullname, email, contact, specialization 
                        FROM user 
                        WHERE id = ? AND role = 'patient' LIMIT 1");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p class='text-danger'>Patient not found.</p>";
    exit;
}

$patient = $result->fetch_assoc();

// ✅ Fetch appointment count
$countAppointments = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE patient_id=$patient_id AND doctor_id=$doctor_id")->fetch_assoc()['c'] ?? 0;

// ✅ Fetch prescription count
$countPrescriptions = $conn->query("SELECT COUNT(*) AS c FROM prescriptions WHERE patient_id=$patient_id AND doctor_id=$doctor_id")->fetch_assoc()['c'] ?? 0;

// ✅ Fetch recent appointments
$sqlA = "SELECT id, date, time, status, emergency, notes 
         FROM appointments 
         WHERE patient_id = ? AND doctor_id = ?
         ORDER BY date DESC, time DESC LIMIT 5";
$stmtA = $conn->prepare($sqlA);
$stmtA->bind_param('ii', $patient_id, $doctor_id);
$stmtA->execute();
$resA = $stmtA->get_result();

// ✅ Fetch recent prescriptions
$sqlP = "SELECT medicine, dosage, instructions, date_issued
         FROM prescriptions
         WHERE patient_id = ? AND doctor_id = ?
         ORDER BY date_issued DESC LIMIT 5";
$stmtP = $conn->prepare($sqlP);
$stmtP->bind_param('ii', $patient_id, $doctor_id);
$stmtP->execute();
$resP = $stmtP->get_result();
?>

<div class="modal-header">
  <h5 class="modal-title text-success">
    <i class="bi bi-person-circle me-2"></i> <?= htmlspecialchars($patient['fullname']) ?>
  </h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
  <!-- Tabs -->
  <ul class="nav nav-tabs" id="patientTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab">
        <i class="bi bi-info-circle me-1"></i> Profile Info
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">
        <i class="bi bi-clock-history me-1"></i> History
        <span class="badge bg-success ms-1"><?= $countAppointments ?></span> /
        <span class="badge bg-primary"><?= $countPrescriptions ?></span>
      </button>
    </li>
  </ul>

  <div class="tab-content mt-3" id="patientTabsContent">

    <!-- Profile Info -->
    <div class="tab-pane fade show active" id="info" role="tabpanel">
      <table class="table table-bordered align-middle">
        <tr><th>Username</th><td><?= htmlspecialchars($patient['username']) ?></td></tr>
        <tr><th>Full Name</th><td><?= htmlspecialchars($patient['fullname']) ?></td></tr>
        <tr><th>Email</th><td><?= htmlspecialchars($patient['email']) ?></td></tr>
        <tr><th>Contact</th><td><?= htmlspecialchars($patient['contact']) ?></td></tr>
        <tr><th>Specialization</th><td><?= htmlspecialchars($patient['specialization'] ?: 'N/A') ?></td></tr>
      </table>
    </div>

    <!-- History -->
    <div class="tab-pane fade" id="history" role="tabpanel">
      <h6 class="text-success mb-2"><i class="bi bi-calendar-event me-1"></i> Recent Appointments</h6>
      <?php if ($resA->num_rows === 0): ?>
        <p class="text-muted small">No appointment history available.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle">
            <thead class="table-light">
              <tr><th>Date</th><th>Time</th><th>Status</th><th>Emergency</th><th>Notes</th></tr>
            </thead>
            <tbody>
            <?php while($a = $resA->fetch_assoc()): ?>
              <?php
                $statusColor = match($a['status']) {
                    'completed' => 'success',
                    'pending' => 'warning',
                    'cancelled' => 'danger',
                    default => 'secondary'
                };
              ?>
              <tr>
                <td><?= htmlspecialchars($a['date']) ?></td>
                <td><?= htmlspecialchars(date('h:i A', strtotime($a['time']))) ?></td>
                <td><span class="badge bg-<?= $statusColor ?>"><?= ucfirst($a['status']) ?></span></td>
                <td><?= $a['emergency'] ? '<span class="badge bg-danger">Yes</span>' : 'No' ?></td>
                <td><?= htmlspecialchars($a['notes'] ?: '-') ?></td>
              </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <h6 class="text-success mt-4 mb-2"><i class="bi bi-capsule me-1"></i> Recent Prescriptions</h6>
      <?php if ($resP->num_rows === 0): ?>
        <p class="text-muted small">No prescriptions found.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle">
            <thead class="table-light">
              <tr><th>Medicine</th><th>Dosage</th><th>Instructions</th><th>Date Issued</th></tr>
            </thead>
            <tbody>
            <?php while($p = $resP->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($p['medicine']) ?></td>
                <td><?= htmlspecialchars($p['dosage']) ?></td>
                <td><?= htmlspecialchars($p['instructions']) ?></td>
                <td><?= htmlspecialchars($p['date_issued']) ?></td>
              </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <div class="text-end mt-3">
        <a href="patient_history.php?id=<?= $patient_id ?>" class="btn btn-outline-success btn-sm">
          <i class="bi bi-journal-text"></i> View Full History
        </a>
      </div>
    </div>
  </div>
</div>

<div class="modal-footer">
  <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
</div>
