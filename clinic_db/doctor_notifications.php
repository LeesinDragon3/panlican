<?php
require_once 'db_connect.php';
session_start();

// ✅ Restrict to doctors
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    echo "<p class='text-danger'>Unauthorized access.</p>";
    exit;
}

$doctor_id = $_SESSION['user']['id'];

// ✅ Fetch notifications - with error handling
// Try direct query first
$sql = "SELECT id, title, message, type, is_read, created_at 
        FROM notifications 
        WHERE recipient_id = $doctor_id AND recipient_type = 'doctor'
        ORDER BY created_at DESC 
        LIMIT 50";

$result = $conn->query($sql);
$notifCount = 0;

if (!$result) {
    echo "<div class='alert alert-info'><i class='bi bi-inbox'></i> No notifications yet</div>";
    $result = null;
} else {
    $notifCount = $result->num_rows;
}
?>

<div class="card shadow-sm p-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 text-success">
      <i class="bi bi-bell me-2"></i>Notifications
      <span class="badge bg-success"><?= $notifCount ?></span>
    </h5>
    <?php if($notifCount > 0): ?>
      <button class="btn btn-outline-danger btn-sm" onclick="markAllAsRead()">
        <i class="bi bi-check-all me-1"></i>Mark All as Read
      </button>
    <?php endif; ?>
  </div>

  <!-- Notifications List -->
  <div class="list-group">
    <?php $notifCount = $result ? $result->num_rows : 0; ?>
    <?php if ($notifCount === 0): ?>
      <div class="alert alert-info text-center py-4">
        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
        <p class="mt-2 mb-0">No notifications yet</p>
      </div>
    <?php else: ?>
      <?php while($notif = $result->fetch_assoc()): 
        $bgClass = $notif['is_read'] ? '' : 'bg-light border-start border-success border-3';
        $typeIcon = match(strtolower($notif['type'] ?? 'info')) {
          'appointment' => 'calendar-check',
          'prescription' => 'prescription2',
          'medical' => 'file-medical',
          'urgent' => 'exclamation-triangle',
          'warning' => 'exclamation-circle',
          default => 'info-circle'
        };
        $typeBadgeClass = match(strtolower($notif['type'] ?? 'info')) {
          'appointment' => 'bg-info',
          'prescription' => 'bg-primary',
          'medical' => 'bg-success',
          'urgent' => 'bg-danger',
          'warning' => 'bg-warning',
          default => 'bg-secondary'
        };
      ?>
        <div class="list-group-item <?= $bgClass ?> p-3 border">
          <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
              <div class="d-flex align-items-center mb-2">
                <i class="bi bi-<?= $typeIcon ?> me-2" style="color: #1a7c4a;"></i>
                <h6 class="mb-0"><?= htmlspecialchars($notif['title']) ?></h6>
                <span class="badge <?= $typeBadgeClass ?> ms-2"><?= ucfirst($notif['type']) ?></span>
              </div>
              <p class="text-muted mb-2"><?= htmlspecialchars($notif['message']) ?></p>
              <small class="text-muted">
                <i class="bi bi-clock me-1"></i>
                <?php 
                  $date = new DateTime($notif['created_at']);
                  $now = new DateTime();
                  $diff = $date->diff($now);
                  
                  if ($diff->days > 0) {
                    echo $date->format('M d, Y H:i');
                  } elseif ($diff->h > 0) {
                    echo $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
                  } elseif ($diff->i > 0) {
                    echo $diff->i . ' min' . ($diff->i > 1 ? 's' : '') . ' ago';
                  } else {
                    echo 'Just now';
                  }
                ?>
              </small>
            </div>
            <div>
              <?php if(!$notif['is_read']): ?>
                <button class="btn btn-sm btn-outline-success" onclick="markAsRead(<?= $notif['id'] ?>, this)" title="Mark as read">
                  <i class="bi bi-check-circle"></i>
                </button>
              <?php endif; ?>
              <button class="btn btn-sm btn-outline-danger" onclick="deleteNotification(<?= $notif['id'] ?>, this)" title="Delete">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>
</div>

<script>
// Mark single notification as read
function markAsRead(id, button) {
  fetch('notification_mark_read.php?id=' + id, { method: 'POST' })
    .then(res => res.json())
    .then(data => {
      if(data.success) {
        button.closest('.list-group-item').classList.remove('bg-light', 'border-start', 'border-success', 'border-3');
        button.remove();
      }
    })
    .catch(err => alert('Failed to mark as read'));
}

// Mark all as read
function markAllAsRead() {
  if(confirm('Mark all notifications as read?')) {
    fetch('notification_mark_all_read.php', { method: 'POST' })
      .then(res => res.json())
      .then(data => {
        if(data.success) {
          location.reload();
        }
      })
      .catch(err => alert('Failed to mark all as read'));
  }
}

// Delete notification
function deleteNotification(id, button) {
  if(confirm('Delete this notification?')) {
    fetch('notification_delete.php?id=' + id, { method: 'POST' })
      .then(res => res.json())
      .then(data => {
        if(data.success) {
          button.closest('.list-group-item').remove();
        }
      })
      .catch(err => alert('Failed to delete notification'));
  }
}
</script>