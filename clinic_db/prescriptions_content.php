<?php
// prescriptions_content.php - sample
$pres = [
  ['date'=>'2025-10-26','meds'=>'Amoxicillin 500mg','doctor'=>'Dr. Kiera Knight','status'=>'Active'],
  ['date'=>'2025-09-20','meds'=>'Ibuprofen 200mg','doctor'=>'Dr. Bruce Williams','status'=>'Completed']
];
?>
<div class="container-fluid">
  <h5>Prescriptions</h5>
  <table class="table mt-3 table-striped">
    <thead><tr><th>Date</th><th>Medications</th><th>Doctor</th><th>Status</th></tr></thead>
    <tbody>
      <?php foreach($pres as $p): ?>
        <tr>
          <td><?= htmlspecialchars($p['date']); ?></td>
          <td><?= htmlspecialchars($p['meds']); ?></td>
          <td><?= htmlspecialchars($p['doctor']); ?></td>
          <td><?= htmlspecialchars($p['status']); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
