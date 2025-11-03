<?php
require_once 'db_connect.php';
session_start();

// ✅ Restrict to doctors
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    header('Location: login.php');
    exit();
}

// ✅ Validate patient ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p class='text-danger m-3'>Invalid patient ID.</p>";
    exit;
}

$patient_id = intval($_GET['id']);
$doctor_id = intval($_SESSION['user']['id']);
$doctor_name = $_SESSION['user']['fullname'] ?? 'Doctor';
$doctor_spec = $_SESSION['user']['specialization'] ?? 'General Practitioner';
$export_date = date('F d, Y');

// ✅ Fetch patient info
$stmt = $conn->prepare("SELECT fullname, username, email, contact FROM user WHERE id=? AND role='patient' LIMIT 1");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

if (!$patient) {
    echo "<p class='text-danger m-3'>Patient not found.</p>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($patient['fullname']) ?> - History</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.3/html2pdf.bundle.min.js"></script>
<style>
body {
  background-color: #f8f9fa;
}
.card {
  border-radius: 15px;
}
.badge {
  font-size: 0.8rem;
}
@media print {
  .no-print {
    display: none !important;
  }
  body {
    background: white;
  }
  #pdf-header {
    border-bottom: 2px solid #198754;
    padding-bottom: 10px;
    margin-bottom: 20px;
  }
}
#pdf-header {
  border-bottom: 2px solid #198754;
  padding-bottom: 10px;
  margin-bottom: 25px;
}
</style>
</head>
<body>
<div class="container mt-4 mb-5" id="report-content">
 <!-- ✅ EClinic Logo Header -->
<div class="d-flex align-items-center mb-3 ps-3 pt-3">
    <img src="C:\xampp23\htdocs\public_eclinic\ChatGPT Image Nov 1, 2025, 06_56_32 PM.png" alt="EClinic Logo" 
         style="height: 45px; width: 45px; border-radius: 12px; object-fit: cover; margin-right: 10px;">
    <h4 class="fw-bold text-success mb-0">EClinic</h4>
</div>
<hr class="text-muted my-2">

  <!-- Toolbar -->
  <div class="no-print d-flex justify-content-between mb-3">
    <a href="doctor_dashboard.php" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Back
    </a>
    <div>
      <button class="btn btn-outline-primary me-2" onclick="window.print()">
        <i class="bi bi-printer"></i> Print
      </button>
      <button class="btn btn-success" id="downloadPdf">
        <i class="bi bi-filetype-pdf"></i> Export PDF
      </button>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header bg-success text-white">
      <h4 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i>Patient History: <?= htmlspecialchars($patient['fullname']) ?></h4>
    </div>
    <div class="card-body">
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <p><strong>Username:</strong> <?= htmlspecialchars($patient['username']) ?></p>
          <p><strong>Email:</strong> <?= htmlspecialchars($patient['email']) ?></p>
        </div>
        <div class="col-md-6">
          <p><strong>Contact:</strong> <?= htmlspecialchars($patient['contact']) ?></p>
          <p><strong>Doctor ID:</strong> <?= $doctor_id ?></p>
        </div>
      </div>

      <!-- Tabs -->
      <ul class="nav nav-tabs" id="historyTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="appt-tab" data-bs-toggle="tab" data-bs-target="#appt" type="button" role="tab">
            <i class="bi bi-calendar-check me-1"></i> Appointments
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="pres-tab" data-bs-toggle="tab" data-bs-target="#pres" type="button" role="tab">
            <i class="bi bi-capsule me-1"></i> Prescriptions
          </button>
        </li>
      </ul>

      <div class="tab-content mt-3">
        <!-- 🗓 Appointments -->
        <div class="tab-pane fade show active" id="appt" role="tabpanel">
          <div class="table-responsive">
            <table class="table table-striped align-middle">
              <thead class="table-success">
                <tr>
                  <th>Date</th>
                  <th>Time</th>
                  <th>Status</th>
                  <th>Emergency</th>
                  <th>Notes</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $appt = $conn->prepare("SELECT date, time, status, emergency, notes 
                                        FROM appointments 
                                        WHERE patient_id=? AND doctor_id=? 
                                        ORDER BY date DESC, time DESC");
                $appt->bind_param("ii", $patient_id, $doctor_id);
                $appt->execute();
                $resA = $appt->get_result();
                if ($resA->num_rows === 0): ?>
                  <tr><td colspan="5" class="text-muted text-center py-3">No appointments found.</td></tr>
                <?php else:
                  while ($a = $resA->fetch_assoc()):
                    $color = match($a['status']) {
                      'completed' => 'success',
                      'pending' => 'warning',
                      'cancelled' => 'danger',
                      default => 'secondary'
                    };
                ?>
                  <tr>
                    <td><?= htmlspecialchars($a['date']) ?></td>
                    <td><?= htmlspecialchars(date('h:i A', strtotime($a['time']))) ?></td>
                    <td><span class="badge bg-<?= $color ?>"><?= ucfirst($a['status']) ?></span></td>
                    <td><?= $a['emergency'] ? '<span class="badge bg-danger">Yes</span>' : 'No' ?></td>
                    <td><?= htmlspecialchars($a['notes'] ?: '-') ?></td>
                  </tr>
                <?php endwhile; endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- 💊 Prescriptions -->
        <div class="tab-pane fade" id="pres" role="tabpanel">
          <div class="table-responsive">
            <table class="table table-striped align-middle">
              <thead class="table-primary">
                <tr>
                  <th>Medicine</th>
                  <th>Dosage</th>
                  <th>Instructions</th>
                  <th>Date Issued</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $pres = $conn->prepare("SELECT medicine, dosage, instructions, date_issued 
                                        FROM prescriptions 
                                        WHERE patient_id=? AND doctor_id=? 
                                        ORDER BY date_issued DESC");
                $pres->bind_param("ii", $patient_id, $doctor_id);
                $pres->execute();
                $resP = $pres->get_result();
                if ($resP->num_rows === 0): ?>
                  <tr><td colspan="4" class="text-muted text-center py-3">No prescriptions found.</td></tr>
                <?php else:
                  while ($p = $resP->fetch_assoc()): ?>
                  <tr>
                    <td><?= htmlspecialchars($p['medicine']) ?></td>
                    <td><?= htmlspecialchars($p['dosage']) ?></td>
                    <td><?= htmlspecialchars($p['instructions']) ?></td>
                    <td><?= htmlspecialchars($p['date_issued']) ?></td>
                  </tr>
                <?php endwhile; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById("downloadPdf").addEventListener("click", function () {
  const content = document.getElementById("report-content");
  const opt = {
    margin: 0.5,
    filename: '<?= preg_replace("/[^a-zA-Z0-9_-]/", "_", $patient['fullname']) ?>_history.pdf',
    image: { type: 'jpeg', quality: 0.98 },
    html2canvas: { scale: 2 },
    jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
  };
  html2pdf().from(content).set(opt).save();
});
</script>
</body>
</html>
