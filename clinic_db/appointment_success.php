<?php
session_start();

// Get message from query
$message = $_GET['msg'] ?? 'Appointment updated successfully!';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Appointment Success</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
  background: #f0fdf4;
  font-family: 'Segoe UI', sans-serif;
}
.card {
  max-width: 500px;
  margin: 80px auto;
  padding: 30px;
  border-radius: 15px;
  box-shadow: 0 5px 18px rgba(0,0,0,0.1);
  text-align: center;
  background: #fff;
}
h3 {
  color: #198754;
  font-weight: 600;
}
.btn {
  border-radius: 10px;
  font-weight: 500;
}
</style>
</head>
<body>
<div class="card">
  <h3>✅ <?= htmlspecialchars($message) ?></h3>
  <p class="text-muted mt-2 mb-4">Your changes have been saved successfully.</p>
  <div class="d-flex justify-content-center gap-3">
    <a href="doctor_dashboard.php#appointments" class="btn btn-success px-4">📅 My Appointments</a>
    <a href="doctor_dashboard.php" class="btn btn-outline-secondary px-4">🏠 Dashboard</a>
  </div>
</div>
</body>
</html>
