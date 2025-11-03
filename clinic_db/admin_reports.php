<?php
// This file is loaded via AJAX
require_once 'db_connect.php';

// Helper function
function safeCount($conn, $sql) {
    $result = $conn->query($sql);
    if (!$result) return 0;
    $row = $result->fetch_assoc();
    return $row ? intval($row['c']) : 0;
}

// Overall Statistics
$totalDoctors = safeCount($conn, "SELECT COUNT(*) AS c FROM users WHERE role='doctor'");
$totalPatients = safeCount($conn, "SELECT COUNT(*) AS c FROM users WHERE role='patient'");
$totalAppointments = safeCount($conn, "SELECT COUNT(*) AS c FROM appointments");
$totalPrescriptions = safeCount($conn, "SELECT COUNT(*) AS c FROM prescriptions");

// This Month Stats
$thisMonth = date('Y-m');
$appointmentsThisMonth = safeCount($conn, "SELECT COUNT(*) AS c FROM appointments WHERE DATE_FORMAT(date, '%Y-%m') = '$thisMonth'");
$prescriptionsThisMonth = safeCount($conn, "SELECT COUNT(*) AS c FROM prescriptions WHERE DATE_FORMAT(created_at, '%Y-%m') = '$thisMonth'");

// Status breakdown
$pendingAppts = safeCount($conn, "SELECT COUNT(*) AS c FROM appointments WHERE status='pending'");
$scheduledAppts = safeCount($conn, "SELECT COUNT(*) AS c FROM appointments WHERE status='scheduled'");
$completedAppts = safeCount($conn, "SELECT COUNT(*) AS c FROM appointments WHERE status='completed'");
$cancelledAppts = safeCount($conn, "SELECT COUNT(*) AS c FROM appointments WHERE status='cancelled'");

// Top doctors by appointments
$topDoctorsQuery = "SELECT u.fullname, u.specialty, COUNT(a.id) as appt_count 
                    FROM users u 
                    LEFT JOIN appointments a ON u.id = a.doctor_id 
                    WHERE u.role='doctor' 
                    GROUP BY u.id 
                    ORDER BY appt_count DESC LIMIT 5";
$topDoctors = $conn->query($topDoctorsQuery);

// Monthly trend (last 6 months)
$monthlyTrend = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('M', strtotime("-$i months"));
    $monthNum = date('m', strtotime("-$i months"));
    $yearNum = date('Y', strtotime("-$i months"));
    $count = safeCount($conn, "SELECT COUNT(*) AS c FROM appointments WHERE MONTH(date) = $monthNum AND YEAR(date) = $yearNum");
    $monthlyTrend[$month] = $count;
}
?>

<div class="reports-management">
    <div class="mb-3">
        <h5><i class="bi bi-graph-up me-2"></i>Reports & Analytics</h5>
    </div>

    <!-- Overall Statistics Cards -->
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <div class="text-success" style="font-size:2rem;"><i class="bi bi-people"></i></div>
                <h3 class="mb-0" style="color:#1a7c4a;"><?= $totalPatients ?></h3>
                <small class="text-muted">Total Patients</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <div class="text-success" style="font-size:2rem;"><i class="bi bi-person-badge"></i></div>
                <h3 class="mb-0" style="color:#1a7c4a;"><?= $totalDoctors ?></h3>
                <small class="text-muted">Total Doctors</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <div class="text-success" style="font-size:2rem;"><i class="bi bi-calendar-check"></i></div>
                <h3 class="mb-0" style="color:#1a7c4a;"><?= $totalAppointments ?></h3>
                <small class="text-muted">Total Appointments</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <div class="text-success" style="font-size:2rem;"><i class="bi bi-prescription2"></i></div>
                <h3 class="mb-0" style="color:#1a7c4a;"><?= $totalPrescriptions ?></h3>
                <small class="text-muted">Total Prescriptions</small>
            </div>
        </div>
    </div>

    <!-- This Month Stats -->
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card p-3">
                <h6 class="text-muted mb-3"><i class="bi bi-calendar-month me-2"></i>This Month Performance</h6>
                <div class="row">
                    <div class="col-6">
                        <div class="text-center p-3" style="background:#f8f9fa; border-radius:8px;">
                            <h4 class="mb-0" style="color:#1a7c4a;"><?= $appointmentsThisMonth ?></h4>
                            <small class="text-muted">Appointments</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center p-3" style="background:#f8f9fa; border-radius:8px;">
                            <h4 class="mb-0" style="color:#1a7c4a;"><?= $prescriptionsThisMonth ?></h4>
                            <small class="text-muted">Prescriptions</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card p-3">
                <h6 class="text-muted mb-3"><i class="bi bi-pie-chart me-2"></i>Appointment Status Breakdown</h6>
                <div class="row g-2">
                    <div class="col-6">
                        <div class="d-flex justify-content-between align-items-center p-2" style="background:#c8e6c9; border-radius:6px;">
                            <span class="small">Completed</span>
                            <strong><?= $completedAppts ?></strong>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex justify-content-between align-items-center p-2" style="background:#fff9c4; border-radius:6px;">
                            <span class="small">Pending</span>
                            <strong><?= $pendingAppts ?></strong>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex justify-content-between align-items-center p-2" style="background:#bbdefb; border-radius:6px;">
                            <span class="small">Scheduled</span>
                            <strong><?= $scheduledAppts ?></strong>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex justify-content-between align-items-center p-2" style="background:#ffccbc; border-radius:6px;">
                            <span class="small">Cancelled</span>
                            <strong><?= $cancelledAppts ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Trend Chart -->
    <div class="row g-3 mb-3">
        <div class="col-md-8">
            <div class="card p-3">
                <h6 class="text-muted mb-3"><i class="bi bi-graph-up me-2"></i>Appointment Trend (Last 6 Months)</h6>
                <canvas id="trendChart" height="100"></canvas>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3">
                <h6 class="text-muted mb-3"><i class="bi bi-trophy me-2"></i>Top Doctors by Appointments</h6>
                <div style="max-height:250px; overflow-y:auto;">
                    <?php if($topDoctors && $topDoctors->num_rows > 0): ?>
                        <?php $rank = 1; ?>
                        <?php while($doctor = $topDoctors->fetch_assoc()): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2 p-2" style="background:#f8f9fa; border-radius:6px;">
                                <div>
                                    <span class="badge bg-success me-2">#<?= $rank++ ?></span>
                                    <strong><?= htmlspecialchars($doctor['fullname']) ?></strong>
                                    <div class="small text-muted"><?= htmlspecialchars($doctor['specialty']) ?></div>
                                </div>
                                <span class="badge bg-primary"><?= $doctor['appt_count'] ?></span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-center text-muted">No data available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Options -->
    <div class="card p-3">
        <h6 class="text-muted mb-3"><i class="bi bi-download me-2"></i>Export Reports</h6>
        <div class="row g-2">
            <div class="col-auto">
                <button class="btn btn-outline-success" onclick="alert('PDF export feature coming soon!')">
                    <i class="bi bi-file-pdf me-1"></i> Export as PDF
                </button>
            </div>
            <div class="col-auto">
                <button class="btn btn-outline-success" onclick="alert('Excel export feature coming soon!')">
                    <i class="bi bi-file-excel me-1"></i> Export as Excel
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Monthly Trend Chart
const trendCtx = document.getElementById('trendChart');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_keys($monthlyTrend)) ?>,
        datasets: [{
            label: 'Appointments',
            data: <?= json_encode(array_values($monthlyTrend)) ?>,
            borderColor: '#1a7c4a',
            backgroundColor: 'rgba(26, 124, 74, 0.1)',
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointBackgroundColor: '#1a7c4a',
            pointBorderColor: '#fff',
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: { y: { beginAtZero: true, ticks: { stepSize: 2 } } },
        plugins: { legend: { display: false } }
    }
});
</script>