<?php
require_once 'db_connect.php';
session_start();

// ✅ Restrict to doctors
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    echo "<p class='text-danger'>Unauthorized access.</p>";
    exit;
}

$doctor_id = $_SESSION['user']['id'];

// ✅ Fetch medical records from appointments (assuming records are stored in appointments table)
$sql = "SELECT a.id, a.date, a.time, a.status, u.fullname, a.notes
        FROM appointments a 
        LEFT JOIN users u ON a.patient_id = u.id
        WHERE a.doctor_id = ?
        ORDER BY a.date DESC, a.time DESC";

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
    <h5 class="mb-0 text-success"><i class="bi bi-file-medical me-2"></i>Medical Records</h5>
    <button class="btn btn-success btn-sm" onclick="openAddRecordModal()">
      <i class="bi bi-plus-circle me-1"></i> New Record
    </button>
  </div>

  <!-- Search & Filter -->
  <div class="mb-3">
    <form class="d-flex ajax-search" method="post" action="doctor_medical_records_search.php">
      <input type="text" name="keyword" class="form-control form-control-sm me-2" placeholder="Search by patient name...">
      <button class="btn btn-outline-success btn-sm">Search</button>
    </form>
  </div>

  <!-- Medical Records Table -->
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Patient Name</th>
          <th>Visit Date</th>
          <th>Time</th>
          <th>Status</th>
          <th>Notes</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows === 0): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">No medical records found</td></tr>
        <?php else: $i=1; while($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($row['fullname'] ?? 'N/A') ?></td>
            <td><?= date('M d, Y', strtotime($row['date'])) ?></td>
            <td><?= htmlspecialchars($row['time']) ?></td>
            <td>
              <?php 
                $status = strtolower($row['status']);
                $badgeClass = $status === 'completed' ? 'bg-success' : ($status === 'pending' ? 'bg-warning' : 'bg-secondary');
              ?>
              <span class="badge <?= $badgeClass ?>"><?= ucfirst($row['status']) ?></span>
            </td>
            <td>
              <small><?= strlen($row['notes']) > 50 ? substr(htmlspecialchars($row['notes']), 0, 50) . '...' : htmlspecialchars($row['notes'] ?? 'N/A') ?></small>
            </td>
            <td>
              <button class="btn btn-outline-primary btn-sm" onclick="viewRecord(<?= $row['id'] ?>)" title="View">
                <i class="bi bi-eye"></i>
              </button>
              <button class="btn btn-outline-warning btn-sm" onclick="editRecord(<?= $row['id'] ?>)" title="Edit">
                <i class="bi bi-pencil"></i>
              </button>
            </td>
          </tr>
        <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add/Edit Medical Record Modal -->
<div class="modal fade" id="recordModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">New Medical Record</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="recordForm" method="post" action="medical_record_save.php">
          <input type="hidden" name="record_id" value="">
          
          <div class="mb-3">
            <label class="form-label">Patient</label>
            <select name="patient_id" class="form-control form-control-sm" required>
              <option value="">-- Select Patient --</option>
              <?php 
              // Fetch patients treated by this doctor
              $patientQuery = "SELECT DISTINCT a.patient_id, u.fullname FROM appointments a 
                              LEFT JOIN users u ON a.patient_id = u.id 
                              WHERE a.doctor_id = ? ORDER BY u.fullname";
              $pStmt = $conn->prepare($patientQuery);
              $pStmt->bind_param("i", $doctor_id);
              $pStmt->execute();
              $pResult = $pStmt->get_result();
              while($p = $pResult->fetch_assoc()) {
                echo "<option value='".$p['patient_id']."'>".htmlspecialchars($p['fullname'])."</option>";
              }
              ?>
            </select>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Visit Date</label>
              <input type="date" name="visit_date" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Time</label>
              <input type="time" name="visit_time" class="form-control form-control-sm" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Chief Complaint</label>
            <input type="text" name="chief_complaint" class="form-control form-control-sm" placeholder="e.g. Headache, Fever" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Diagnosis</label>
            <textarea name="diagnosis" class="form-control form-control-sm" rows="2" placeholder="Medical diagnosis..." required></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Treatment/Notes</label>
            <textarea name="treatment" class="form-control form-control-sm" rows="3" placeholder="Treatment plan and additional notes..." required></textarea>
          </div>

          <button type="submit" class="btn btn-success w-100">Save Record</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- View Record Modal -->
<div class="modal fade" id="viewRecordModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" id="viewRecordContent">
      <!-- Loaded dynamically -->
    </div>
  </div>
</div>

<script>
// Open Add Record Modal
function openAddRecordModal() {
  document.getElementById('recordForm').reset();
  document.getElementById('recordForm').querySelector('input[name="record_id"]').value = '';
  document.getElementById('modalTitle').textContent = 'New Medical Record';
  new bootstrap.Modal(document.getElementById('recordModal')).show();
}

// View Record Details
function viewRecord(id) {
  const modal = new bootstrap.Modal(document.getElementById('viewRecordModal'));
  const content = document.getElementById('viewRecordContent');
  content.innerHTML = "<div class='text-center p-4'><div class='spinner-border text-success'></div></div>";
  
  fetch('medical_record_view.php?id=' + id)
    .then(res => res.text())
    .then(html => { content.innerHTML = html; })
    .catch(() => { content.innerHTML = "<div class='text-danger text-center p-4'>Failed to load record.</div>"; });
  
  modal.show();
}

// Edit Record
function editRecord(id) {
  fetch('medical_record_get.php?id=' + id)
    .then(res => res.json())
    .then(data => {
      if(data.success) {
        document.getElementById('recordForm').querySelector('input[name="record_id"]').value = id;
        document.getElementById('recordForm').querySelector('input[name="visit_date"]').value = data.date;
        document.getElementById('recordForm').querySelector('input[name="visit_time"]').value = data.time;
        document.getElementById('recordForm').querySelector('input[name="chief_complaint"]').value = data.chief_complaint || '';
        document.getElementById('recordForm').querySelector('textarea[name="diagnosis"]').value = data.diagnosis || '';
        document.getElementById('recordForm').querySelector('textarea[name="treatment"]').value = data.notes || '';
        document.getElementById('modalTitle').textContent = 'Edit Medical Record';
        new bootstrap.Modal(document.getElementById('recordModal')).show();
      }
    })
    .catch(err => alert('Failed to load record'));
}

// Form submission
document.addEventListener('submit', function(e) {
  const form = e.target;
  
  if(form.id === 'recordForm') {
    e.preventDefault();
    const formData = new FormData(form);
    
    fetch(form.action, { method: 'POST', body: formData })
      .then(res => res.json())
      .then(data => {
        if(data.success) {
          alert('✅ Medical record saved successfully!');
          bootstrap.Modal.getInstance(document.getElementById('recordModal')).hide();
          location.reload();
        } else {
          alert('❌ ' + (data.message || 'Error saving record'));
        }
      })
      .catch(() => alert('❌ Failed to save record'));
  }
});
</script>