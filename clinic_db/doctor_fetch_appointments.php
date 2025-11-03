<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    exit('<p class="text-danger text-center mt-4">Access denied.</p>');
}

$doctorId = $_SESSION['user']['id'];
$search = '%' . ($_GET['search'] ?? '') . '%';
$date = $_GET['date'] ?? '';

$sql = "
    SELECT a.id, a.date, a.time, a.status, u.fullname, u.contact
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.doctor_id = ?
";
$params = [$doctorId];
$types = 'i';

if (!empty($_GET['search'])) {
    $sql .= " AND (u.fullname LIKE ? OR u.contact LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $types .= 'ss';
}
if (!empty($_GET['date'])) {
    $sql .= " AND a.date = ?";
    $params[] = $date;
    $types .= 's';
}
$sql .= " ORDER BY a.date DESC, a.time ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0): ?>
  <p class="text-center text-muted py-4">No appointments found.</p>
<?php else: ?>
  <style>
  .appointment-card {
    border-radius: 12px;
    color: #333;
    transition: all 0.3s ease-in-out;
  }
  .appointment-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.08);
  }

  /* 🟢 Status-based colors */
  .status-pending {
    background: #fffbea;
    border-left: 6px solid #f1c40f;
  }
  .status-completed {
    background: #e9f9ef;
    border-left: 6px solid #2ecc71;
  }
  .status-cancelled {
    background: #fdecea;
    border-left: 6px solid #e74c3c;
  }

  .badge {
    font-size: 0.8rem;
    padding: 0.4em 0.6em;
  }
  </style>

  <?php
  $currentDate = '';
  while ($row = $result->fetch_assoc()):
      if ($row['date'] !== $currentDate):
          if ($currentDate !== '') echo '</div>';
          $currentDate = $row['date'];
          echo "<h6 class='mt-3 mb-2 text-success fw-bold'><i class='bi bi-calendar3'></i> " . htmlspecialchars(date("F j, Y", strtotime($currentDate))) . "</h6>";
          echo "<div class='row g-3'>";
      endif;

      $statusClass = match($row['status']) {
          'completed' => 'status-completed',
          'cancelled' => 'status-cancelled',
          'pending' => 'status-pending',
          default => ''
      };

      $badge = match($row['status']) {
          'completed' => 'success',
          'cancelled' => 'danger',
          'pending' => 'warning',
          default => 'secondary'
      };
  ?>
  <div class="col-md-6 col-lg-4">
    <div class="card appointment-card <?= $statusClass ?> p-3 h-100">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="fw-semibold mb-0">
          <i class="bi bi-person-circle text-primary me-1"></i>
          <?= htmlspecialchars($row['fullname']) ?>
        </h6>
        <span class="badge bg-<?= $badge ?>"><?= ucfirst($row['status']) ?></span>
      </div>

      <p class="mb-1"><i class="bi bi-telephone me-2 text-muted"></i><?= htmlspecialchars($row['contact']) ?></p>
      <p class="mb-1"><i class="bi bi-clock me-2 text-muted"></i><?= htmlspecialchars($row['time']) ?></p>

      <div class="mt-3">
        <?php if ($row['status'] === 'pending'): ?>
          <form method="POST" action="doctor_mark_completed.php" class="d-inline markForm">
            <input type="hidden" name="appointment_id" value="<?= $row['id'] ?>">
            <button type="submit" class="btn btn-sm btn-success w-100">
              <i class="bi bi-check2-circle me-1"></i> Mark as Done
            </button>
          </form>
        <?php elseif ($row['status'] === 'completed'): ?>
          <button class="btn btn-sm btn-outline-success w-100" disabled>
            <i class="bi bi-check2-all"></i> Completed
          </button>
        <?php else: ?>
          <button class="btn btn-sm btn-outline-danger w-100" disabled>
            <i class="bi bi-x-circle"></i> Cancelled
          </button>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endwhile; ?>
  </div>
<?php endif; ?>
