<?php
require_once 'db_connect.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') exit();

$doctorId = $_SESSION['user']['id'];

function getCount($conn, $doctorId, $status = null) {
    if ($status) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM appointments WHERE doctor_id=? AND status=?");
        $stmt->bind_param("is", $doctorId, $status);
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM appointments WHERE doctor_id=?");
        $stmt->bind_param("i", $doctorId);
    }
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return $res['c'] ?? 0;
}

$total = getCount($conn, $doctorId);
$completed = getCount($conn, $doctorId, 'completed');
$pending = getCount($conn, $doctorId, 'pending');
$cancelled = getCount($conn, $doctorId, 'cancelled');
?>

<div class="row g-3 mb-4" 
     data-completed="<?= $completed ?>" 
     data-pending="<?= $pending ?>" 
     data-cancelled="<?= $cancelled ?>">
  <div class="col-md-3">
    <div class="widget bg-blue">
      <div><h4><?= $total ?></h4><p>Total Appointments</p></div>
      <i class="bi bi-calendar2-check"></i>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget bg-green">
      <div><h4><?= $completed ?></h4><p>Completed</p></div>
      <i class="bi bi-check2-circle"></i>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget bg-yellow">
      <div><h4><?= $pending ?></h4><p>Pending</p></div>
      <i class="bi bi-hourglass-split"></i>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget bg-red">
      <div><h4><?= $cancelled ?></h4><p>Cancelled</p></div>
      <i class="bi bi-x-circle"></i>
    </div>
  </div>
</div>
