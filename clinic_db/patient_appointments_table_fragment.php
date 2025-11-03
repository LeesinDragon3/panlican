<table class="table table-striped align-middle text-center">
  <thead>
    <tr>
      <th>Date</th>
      <th>Time</th>
      <th>Doctor</th>
      <th>Status</th>
      <th>Emergency</th>
      <th>Notes</th>
    </tr>
  </thead>
  <tbody>
    <?php if ($result->num_rows > 0): ?>
      <?php while($row = $result->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($row['date']) ?></td>
        <td><?= htmlspecialchars(date('h:i A', strtotime($row['time']))) ?></td>
        <td><?= htmlspecialchars($row['doctor_name']) ?></td>
        <td>
          <?php
            $statusColor = match($row['status']) {
              'Pending' => 'warning',
              'Completed' => 'success',
              'Cancelled' => 'danger',
              default => 'secondary'
            };
          ?>
          <span class="badge bg-<?= $statusColor ?>"><?= htmlspecialchars($row['status']) ?></span>
        </td>
        <td><?= $row['emergency'] ? '🚨 Yes' : 'No' ?></td>
        <td><?= htmlspecialchars($row['notes'] ?: '-') ?></td>
      </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr><td colspan="6" class="text-muted">No appointments found.</td></tr>
    <?php endif; ?>
  </tbody>
</table>
        