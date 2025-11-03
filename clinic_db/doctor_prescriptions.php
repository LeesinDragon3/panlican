<?php
require_once 'db_connect.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    echo "<p class='text-danger'>Unauthorized access.</p>";
    exit;
}

$doctor_id = $_SESSION['user']['id'];

// ✅ FIXED: Using correct column names from your prescriptions table
$sql = "SELECT p.id, p.medication, p.dosage, p.instructions, p.created_at as date_issued, u.fullname 
        FROM prescriptions p 
        LEFT JOIN appointments a ON p.appointment_id = a.id
        LEFT JOIN users u ON a.patient_id = u.id
        WHERE p.doctor_id = ?
        ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="card shadow-sm p-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 text-success"><i class="bi bi-prescription2 me-2"></i>My Prescriptions</h5>
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#prescriptionModal">
      <i class="bi bi-plus-circle me-1"></i> New Prescription
    </button>
  </div>

  <div class="mb-3">
    <form class="d-flex ajax-search" method="post" action="doctor_prescriptions_search.php">
      <input type="text" name="keyword" class="form-control form-control-sm me-2" placeholder="Search by patient name or medication...">
      <button class="btn btn-outline-success btn-sm">Search</button>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Patient Name</th>
          <th>Medication</th>
          <th>Dosage</th>
          <th>Instructions</th>
          <th>Duration</th>
          <th>Date Issued</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows === 0): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">No prescriptions yet</td></tr>
        <?php else: $i=1; while($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($row['fullname'] ?? 'N/A') ?></td>
            <td><strong><?= htmlspecialchars($row['medication'] ?? 'N/A') ?></strong></td>
            <td><?= htmlspecialchars($row['dosage'] ?? 'N/A') ?></td>
            <td><small><?= htmlspecialchars(substr($row['instructions'] ?? '', 0, 40)) ?></small></td>
            <td><?= htmlspecialchars($row['duration'] ?? 'N/A') ?></td>
            <td><?= date('M d, Y', strtotime($row['date_issued'])) ?></td>
            <td>
              <button class="btn btn-outline-primary btn-sm" title="View">
                <i class="bi bi-eye"></i>
              </button>
              <button class="btn btn-outline-danger btn-sm" title="Delete">
                <i class="bi bi-trash"></i>
              </button>
            </td>
          </tr>
        <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- New Prescription Modal -->
<div class="modal fade" id="prescriptionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">New Prescription</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="prescriptionForm" method="POST" action="prescription_save.php">
          <div class="mb-3">
            <label class="form-label">Patient</label>
            <select name="patient_id" class="form-control" required>
              <option value="">-- Select Patient --</option>
              <?php 
              $patientQuery = "SELECT DISTINCT a.patient_id, u.fullname 
                              FROM appointments a 
                              LEFT JOIN users u ON a.patient_id = u.id 
                              WHERE a.doctor_id = ? 
                              ORDER BY u.fullname";
              $pStmt = $conn->prepare($patientQuery);
              $pStmt->bind_param("i", $doctor_id);
              $pStmt->execute();
              $pResult = $pStmt->get_result();
              
              while($p = $pResult->fetch_assoc()) {
                echo "<option value='".htmlspecialchars($p['patient_id'])."'>".htmlspecialchars($p['fullname'])."</option>";
              }
              ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Medication</label>
            <input type="text" name="medication" class="form-control" placeholder="e.g. Paracetamol" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Dosage</label>
            <input type="text" name="dosage" class="form-control" placeholder="e.g. 500mg" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Instructions</label>
            <textarea name="instructions" class="form-control" rows="3" placeholder="e.g. Take 3 times daily after meals" required></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Duration</label>
            <input type="text" name="duration" class="form-control" placeholder="e.g. 7 days" required>
          </div>

          <button type="submit" class="btn btn-success w-100">Save Prescription</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('submit', function(e) {
  if (e.target.id === 'prescriptionForm') {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    fetch('prescription_save.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert('✅ Prescription saved!');
        bootstrap.Modal.getInstance(document.getElementById('prescriptionModal')).hide();
        location.reload();
      } else {
        alert('Error: ' + (data.message || 'Failed to save'));
      }
    })
    .catch(err => alert('Failed to save prescription'));
  }
});
</script>