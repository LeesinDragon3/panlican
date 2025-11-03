<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once 'db_connect.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$patient_id = $_SESSION['user']['id'];
$patient_fullname = $_SESSION['user']['fullname'] ?? '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = intval($_POST['doctor_id']);
    $date = $_POST['date'];
    $time = $_POST['time'];
    $emergency = isset($_POST['emergency']) ? 1 : 0;
    $notes = trim($_POST['notes']);

    // Basic validation
    if ($doctor_id && $date && $time) {
        // Check if appointments table has patient_username column or not
        // First, let's try with the columns that definitely exist
        $stmt = $conn->prepare("INSERT INTO appointments 
            (patient_id, doctor_id, date, time, status, notes)
            VALUES (?, ?, ?, ?, 'pending', ?)");

        if (!$stmt) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . $conn->error]);
            exit;
        }

        $stmt->bind_param("iisss",
            $patient_id,
            $doctor_id,
            $date,
            $time,
            $notes
        );

        if ($stmt->execute()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Appointment added successfully!']);
            exit;
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Database insert failed: ' . $stmt->error]);
            exit;
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
        exit;
    }
}

// Fetch doctors - Using 'specialty' column (not 'specialization')
$doctorsQuery = "SELECT id, fullname, specialty FROM users WHERE role='doctor' ORDER BY fullname ASC";
$doctors = $conn->query($doctorsQuery);

if (!$doctors) {
    die("Query failed: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Appointment - E-Clinic</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
  body { background: linear-gradient(135deg, #1a7c4a 0%, #2ea169 100%); min-height: 100vh; padding: 20px; }
  .card { border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
  .btn-success { background: linear-gradient(135deg, #1a7c4a 0%, #2ea169 100%); border: none; }
  .btn-success:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(26, 124, 74, 0.4); }
  h4 { color: #1a7c4a; font-weight: 700; }
</style>
</head>
<body>
<div class="container">
  <div class="card p-4 shadow" style="max-width:600px;margin:auto;">
    <h4 class="mb-3 text-center">
      <i class="bi bi-calendar-plus"></i> Add Appointment
    </h4>
    
    <div id="responseMessage" class="alert d-none"></div>
    
    <form method="POST" action="patient_add_appointment.php" id="addAppointmentForm">
      <div class="mb-3">
        <label class="form-label fw-bold">
          <i class="bi bi-person-badge"></i> Select Doctor
        </label>
        <select name="doctor_id" class="form-select" required>
          <option value="">-- Choose a Doctor --</option>
          <?php 
          if ($doctors && $doctors->num_rows > 0) {
              while($row = $doctors->fetch_assoc()): 
          ?>
            <option value="<?= $row['id'] ?>">
              Dr. <?= htmlspecialchars($row['fullname']) ?>
              <?php if (!empty($row['specialty'])): ?>
                - <?= htmlspecialchars($row['specialty']) ?>
              <?php endif; ?>
            </option>
          <?php 
              endwhile; 
          } else {
              echo '<option value="">No doctors available</option>';
          }
          ?>
        </select>
      </div>
      
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label fw-bold">
            <i class="bi bi-calendar-event"></i> Date
          </label>
          <input type="date" name="date" class="form-control" required min="<?= date('Y-m-d') ?>">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label fw-bold">
            <i class="bi bi-clock"></i> Time
          </label>
          <input type="time" name="time" class="form-control" required>
        </div>
      </div>
      
      <div class="form-check mb-3">
        <input type="checkbox" name="emergency" id="emergency" class="form-check-input">
        <label for="emergency" class="form-check-label">
          <i class="bi bi-exclamation-triangle text-danger"></i> Mark as emergency
        </label>
      </div>
      
      <div class="mb-3">
        <label class="form-label fw-bold">
          <i class="bi bi-chat-left-text"></i> Notes / Symptoms
        </label>
        <textarea name="notes" class="form-control" rows="4" placeholder="Describe your symptoms or reason for appointment..."></textarea>
      </div>
      
      <button type="submit" class="btn btn-success w-100 py-2">
        <i class="bi bi-check-circle"></i> Submit Appointment
      </button>
      
      <a href="patient_dashboard.php" class="btn btn-outline-secondary w-100 mt-2">
        <i class="bi bi-arrow-left"></i> Back to Dashboard
      </a>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('addAppointmentForm').addEventListener('submit', function(e) {
  e.preventDefault();
  
  const formData = new FormData(this);
  const responseDiv = document.getElementById('responseMessage');
  
  fetch('patient_add_appointment.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    responseDiv.classList.remove('d-none', 'alert-success', 'alert-danger');
    
    if (data.success) {
      responseDiv.classList.add('alert-success');
      responseDiv.innerHTML = '<i class="bi bi-check-circle"></i> ' + data.message;
      this.reset();
      
      // Redirect after 2 seconds
      setTimeout(() => {
        window.location.href = 'patient_dashboard.php';
      }, 2000);
    } else {
      responseDiv.classList.add('alert-danger');
      responseDiv.innerHTML = '<i class="bi bi-exclamation-triangle"></i> ' + data.message;
    }
  })
  .catch(error => {
    responseDiv.classList.remove('d-none');
    responseDiv.classList.add('alert-danger');
    responseDiv.innerHTML = '<i class="bi bi-x-circle"></i> An error occurred. Please try again.';
  });
});
</script>
</body>
</html>