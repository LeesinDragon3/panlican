<?php
session_start();
require_once 'db_connect.php';

// ✅ Only allow doctors
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    header('Location: login.php');
    exit();
}

// ✅ Require appointment ID
if (!isset($_GET['id'])) {
    echo "<h4>❌ Invalid appointment ID.</h4>";
    exit();
}

$appointmentId = intval($_GET['id']);
$doctorId = $_SESSION['user']['id'];

// ✅ Get appointment info
$stmt = $conn->prepare("
    SELECT a.*, u.fullname AS patient_name 
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.id = ? AND a.doctor_id = ?
");
$stmt->bind_param("ii", $appointmentId, $doctorId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<h4>❌ Appointment not found or unauthorized access.</h4>";
    exit();
}

$appointment = $result->fetch_assoc();

// ✅ If doctor submits the form, update appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $time = $_POST['time'];
    $status = $_POST['status'];
    $emergency = intval($_POST['emergency']);
    $notes = $_POST['notes'];

    $update = $conn->prepare("
        UPDATE appointments 
        SET date=?, time=?, status=?, emergency=?, notes=?
        WHERE id=? AND doctor_id=?
    ");
    $update->bind_param("sssisii", $date, $time, $status, $emergency, $notes, $appointmentId, $doctorId);

    if ($update->execute()) {
        echo "<script>
                alert('✅ Appointment updated successfully!');
                window.location='doctor_appointments.php';
              </script>";
        exit();
    } else {
        echo "<script>alert('❌ Failed to update appointment.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Appointment</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #f8fafc; font-family: 'Segoe UI', sans-serif; }
.card { border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
.btn-save { background: #1a7c4a; color: #fff; border: none; }
.btn-save:hover { background: #16663b; }
.btn-delete { background: #c0392b; color: #fff; border: none; }
.btn-delete:hover { background: #962d22; }
</style>
</head>
<body>
<div class="container mt-5">
  <div class="card p-4">
    <h4 class="text-success mb-4">✏️ Edit Appointment #<?= $appointment['id'] ?></h4>

    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Patient Name</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($appointment['patient_name']) ?>" disabled>
      </div>

      <div class="mb-3">
        <label class="form-label">Date</label>
        <input type="date" name="date" class="form-control" value="<?= $appointment['date'] ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Time</label>
        <input type="time" name="time" class="form-control" value="<?= $appointment['time'] ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="Pending" <?= $appointment['status']=='Pending'?'selected':'' ?>>Pending</option>
          <option value="Completed" <?= $appointment['status']=='Completed'?'selected':'' ?>>Completed</option>
          <option value="Cancelled" <?= $appointment['status']=='Cancelled'?'selected':'' ?>>Cancelled</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Emergency</label>
        <select name="emergency" class="form-select">
          <option value="1" <?= $appointment['emergency']?'selected':'' ?>>Yes</option>
          <option value="0" <?= !$appointment['emergency']?'selected':'' ?>>No</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($appointment['notes']) ?></textarea>
      </div>

      <div class="d-flex justify-content-between align-items-center">
        <div>
          <button type="submit" class="btn btn-save">💾 Save Changes</button>
          <a href="doctor_appointments.php" class="btn btn-secondary">⬅ Back</a>
        </div>
        <button type="button" class="btn btn-delete" onclick="confirmDelete(<?= $appointment['id'] ?>)">🗑 Delete</button>
      </div>
    </form>

    <hr class="my-4">
    <div class="text-center">
      <a href="doctor_dashboard.php" class="btn btn-primary me-2">🏠 Back to Dashboard</a>
      <a href="doctor_patients.php" class="btn btn-info">👥 My Patients</a>
    </div>
  </div>
</div>


<script>
function confirmDelete(id) {
  if (confirm("⚠️ Are you sure you want to delete this appointment? This action cannot be undone.")) {
    fetch('delete_appointment.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'id=' + encodeURIComponent(id)
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert('✅ Appointment deleted successfully.');
        window.location.href = 'doctor_appointments.php';
      } else {
        alert('❌ ' + (data.error || 'Failed to delete appointment.'));
      }
    })
    .catch(err => {
      alert('❌ Network error: ' + err);
    });
  }
}
</>


</body>
</html>
