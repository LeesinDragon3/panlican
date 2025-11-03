<?php
require_once 'db_connect.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    echo "<p class='text-danger'>Unauthorized access.</p>";
    exit;
}

$doctor_id = $_SESSION['user']['id'];

// Fetch all schedules
$sql = "SELECT * FROM doctor_schedules WHERE doctor_id = $doctor_id";
$result = $conn->query($sql);

$schedule = [];
while($row = $result->fetch_assoc()) {
    $schedule[$row['day_of_week']] = $row;
}

// Default days
$daysOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
?>

<div class="card shadow-sm p-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 text-success"><i class="bi bi-clock me-2"></i>My Schedule</h5>
  </div>

  <div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    Edit the times and click Save to update your schedule.
  </div>

  <div class="table-responsive mb-4">
    <table class="table table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th>Day</th>
          <th>Start Time</th>
          <th>End Time</th>
          <th>Available</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($daysOrder as $day): ?>
          <?php $s = $schedule[$day] ?? ['start_time' => '09:00', 'end_time' => '17:00', 'is_available' => 1]; ?>
          <tr>
            <td><strong><?= $day ?></strong></td>
            <td>
              <input type="time" id="start_<?= $day ?>" value="<?= htmlspecialchars($s['start_time']) ?>" class="form-control form-control-sm" style="max-width:120px;">
            </td>
            <td>
              <input type="time" id="end_<?= $day ?>" value="<?= htmlspecialchars($s['end_time']) ?>" class="form-control form-control-sm" style="max-width:120px;">
            </td>
            <td>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="available_<?= $day ?>" <?= ($s['is_available'] ?? 0) ? 'checked' : '' ?>>
              </div>
            </td>
            <td>
              <button class="btn btn-success btn-sm" onclick="saveDay('<?= $day ?>')">
                <i class="bi bi-check-circle"></i> Save
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <hr>
  <h6 class="text-success mb-3"><i class="bi bi-calendar-event me-2"></i>Upcoming Appointments</h6>
  
  <div class="table-responsive">
    <table class="table table-sm table-hover">
      <thead class="table-light">
        <tr>
          <th>Date</th>
          <th>Time</th>
          <th>Patient</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $apptQuery = "SELECT a.id, a.date, a.time, a.status, u.fullname
                      FROM appointments a
                      LEFT JOIN users u ON a.patient_id = u.id
                      WHERE a.doctor_id = ? AND a.date >= CURDATE()
                      ORDER BY a.date ASC, a.time ASC LIMIT 10";
        
        $apptStmt = $conn->prepare($apptQuery);
        $apptStmt->bind_param("i", $doctor_id);
        $apptStmt->execute();
        $apptResult = $apptStmt->get_result();
        ?>
        <?php if ($apptResult->num_rows === 0): ?>
          <tr><td colspan="4" class="text-center text-muted py-3">No upcoming appointments</td></tr>
        <?php else: ?>
          <?php while($appt = $apptResult->fetch_assoc()): ?>
            <tr>
              <td><?= date('M d, Y', strtotime($appt['date'])) ?></td>
              <td><?= htmlspecialchars($appt['time']) ?></td>
              <td><?= htmlspecialchars($appt['fullname'] ?? 'N/A') ?></td>
              <td>
                <?php 
                  $status = strtolower($appt['status']);
                  $badgeClass = ($status === 'scheduled') ? 'bg-info' : (($status === 'completed') ? 'bg-success' : (($status === 'cancelled') ? 'bg-danger' : 'bg-warning'));
                ?>
                <span class="badge <?= $badgeClass ?>"><?= ucfirst($appt['status']) ?></span>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function saveDay(day) {
  var startTime = document.getElementById('start_' + day).value;
  var endTime = document.getElementById('end_' + day).value;
  var isAvailable = document.getElementById('available_' + day).checked ? 1 : 0;
  
  if (!startTime || !endTime) {
    alert('Please set both times');
    return;
  }
  
  var formData = new FormData();
  formData.append('day', day);
  formData.append('start_time', startTime);
  formData.append('end_time', endTime);
  if (isAvailable) {
    formData.append('is_available', 1);
  }
  
  fetch('schedule_save.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert('✅ ' + day + ' schedule saved!');
        location.reload();
      } else {
        alert('Error: ' + (data.message || 'Failed'));
      }
    })
    .catch(err => alert('Failed to save'));
}
</script>