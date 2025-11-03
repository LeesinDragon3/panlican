<?php
session_start();
require_once 'db_connect.php';

// ✅ Allow only admins
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$adminId = $_SESSION['user']['id'];
$fullname = $_SESSION['user']['fullname'] ?? 'Admin';
$profilePic = $_SESSION['user']['profile_pic'] ?? 'default.png';
$today = date('Y-m-d');
$currentTime = date('H:i:s');

// ✅ Helper for safe count
function safeCount($conn, $sql) {
    $result = $conn->query($sql);
    if (!$result) return 0;
    $row = $result->fetch_assoc();
    return $row ? intval($row['c']) : 0;
}

// Dashboard stats
$totalDoctors = safeCount($conn, "SELECT COUNT(*) AS c FROM users WHERE role='doctor'");
$totalPatients = safeCount($conn, "SELECT COUNT(*) AS c FROM users WHERE role='patient'");
$totalAppointments = safeCount($conn, "SELECT COUNT(*) AS c FROM appointments");
$todayAppointments = safeCount($conn, "SELECT COUNT(*) AS c FROM appointments WHERE date='$today'");
$pendingAppointments = safeCount($conn, "SELECT COUNT(*) AS c FROM appointments WHERE status='pending'");
$completedAppointments = safeCount($conn, "SELECT COUNT(*) AS c FROM appointments WHERE status='completed'");
$cancelledAppointments = safeCount($conn, "SELECT COUNT(*) AS c FROM appointments WHERE status='cancelled'");
$totalPrescriptions = safeCount($conn, "SELECT COUNT(*) AS c FROM prescriptions");

// Fetch recent appointments (last 5)
$recentAppointmentsQuery = "SELECT a.id, a.date, a.time, a.status, 
                            p.fullname as patient_name, 
                            d.fullname as doctor_name,
                            d.specialty
                            FROM appointments a 
                            JOIN users p ON a.patient_id = p.id 
                            JOIN users d ON a.doctor_id = d.id 
                            ORDER BY a.date DESC, a.time DESC LIMIT 5";
$recentAppts = $conn->query($recentAppointmentsQuery);

// Fetch upcoming appointments (next 5 future appointments)
$upcomingAppointmentsQuery = "SELECT a.id, a.date, a.time, a.status, 
                               p.fullname as patient_name, 
                               d.fullname as doctor_name,
                               d.specialty
                               FROM appointments a 
                               JOIN users p ON a.patient_id = p.id 
                               JOIN users d ON a.doctor_id = d.id 
                               WHERE (a.date > '$today' OR (a.date = '$today' AND a.time > '$currentTime'))
                               AND a.status != 'cancelled'
                               ORDER BY a.date ASC, a.time ASC LIMIT 5";
$upcomingAppts = $conn->query($upcomingAppointmentsQuery);

// Weekly activity data (appointments per day for current week)
$weekStart = date('Y-m-d', strtotime('monday this week'));
$weekEnd = date('Y-m-d', strtotime('sunday this week'));
$weeklyData = ["Mon"=>0,"Tue"=>0,"Wed"=>0,"Thu"=>0,"Fri"=>0,"Sat"=>0,"Sun"=>0];
$weeklyQuery = "SELECT DATE_FORMAT(date, '%a') as day, COUNT(*) as count 
                FROM appointments 
                WHERE date BETWEEN '$weekStart' AND '$weekEnd'
                GROUP BY date ORDER BY date";
$weeklyResult = $conn->query($weeklyQuery);
if ($weeklyResult) {
    while ($row = $weeklyResult->fetch_assoc()) {
        $weeklyData[$row['day']] = $row['count'];
    }
}

// Prescription statistics
$prescriptionsThisMonth = safeCount($conn, "SELECT COUNT(*) AS c FROM prescriptions WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
$prescriptionsLastMonth = safeCount($conn, "SELECT COUNT(*) AS c FROM prescriptions WHERE MONTH(created_at) = MONTH(CURDATE() - INTERVAL 1 MONTH) AND YEAR(created_at) = YEAR(CURDATE() - INTERVAL 1 MONTH)");

// Top prescribing doctors
$topDoctorsQuery = "SELECT d.fullname, d.specialty, COUNT(*) as prescription_count 
                    FROM prescriptions p 
                    JOIN users d ON p.doctor_id = d.id 
                    GROUP BY p.doctor_id 
                    ORDER BY prescription_count DESC LIMIT 5";
$topDoctors = $conn->query($topDoctorsQuery);

// Recent prescriptions
$recentPrescriptionsQuery = "SELECT p.id, p.created_at, p.medication,
                             pat.fullname as patient_name,
                             doc.fullname as doctor_name
                             FROM prescriptions p
                             JOIN users pat ON p.patient_id = pat.id
                             JOIN users doc ON p.doctor_id = doc.id
                             ORDER BY p.created_at DESC LIMIT 5";
$recentPrescriptions = $conn->query($recentPrescriptionsQuery);

// Monthly appointments data for chart (last 6 months)
$monthlyData = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('M', strtotime("-$i months"));
    $monthNum = date('m', strtotime("-$i months"));
    $yearNum = date('Y', strtotime("-$i months"));
    $count = safeCount($conn, "SELECT COUNT(*) AS c FROM appointments WHERE MONTH(date) = $monthNum AND YEAR(date) = $yearNum");
    $monthlyData[$month] = $count;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard - E-Clinic</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body { background-color:#f4f7f9; font-family:'Segoe UI',sans-serif; }
.sidebar { height:100vh; background:#eaf7ef; padding:20px 10px; border-right:1px solid #ddd; overflow-y:auto; position:fixed; width:16.666667%; }
.sidebar h4 { font-weight:700; color:#1a7c4a; text-align:center; }
.sidebar .nav-link { color:#333; border-radius:8px; margin:5px 0; font-weight:500; transition:background 0.3s,color 0.3s; }
.sidebar .nav-link:hover, .sidebar .nav-link.active { background-color:#1a7c4a; color:#fff; }
.main-content { margin-left:16.666667%; }
.navbar { background:#fff; box-shadow:0 2px 5px rgba(0,0,0,0.05); }
.card { border:none; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.08); transition:transform 0.3s,box-shadow 0.3s; }
.card:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,0.12); }

/* Stat cards */
.stat-card { text-align:center; padding:15px; }
.stat-card h6 { font-size:0.8rem; color:#666; font-weight:500; margin-bottom:8px; }
.stat-card h3 { font-size:1.8rem; font-weight:700; margin:8px 0; color:#1a7c4a; }
.stat-card .icon { font-size:1.8rem; margin-bottom:8px; color:#1a7c4a; }

/* Quick action buttons */
.quick-action-btn { border-radius:10px; padding:15px; text-align:center; transition:all 0.3s; border:2px solid #e0e0e0; background:#fff; cursor:pointer; }
.quick-action-btn:hover { transform:translateY(-3px); box-shadow:0 5px 15px rgba(0,0,0,0.1); border-color:#1a7c4a; }
.quick-action-btn i { font-size:2rem; color:#1a7c4a; display:block; margin-bottom:10px; }
.quick-action-btn span { font-size:0.9rem; font-weight:600; color:#333; }

/* Appointment list */
.appt-item { padding:12px; border-bottom:1px solid #f0f0f0; transition:background 0.2s; }
.appt-item:hover { background:#f8f9fa; }
.appt-item:last-child { border-bottom:none; }
.appt-time { font-weight:600; color:#1a7c4a; font-size:0.9rem; }
.appt-patient { font-weight:500; color:#333; }
.appt-doctor { font-size:0.85rem; color:#666; }

/* Status badges */
.status-badge { padding:4px 10px; border-radius:6px; font-size:0.75rem; font-weight:600; }
.status-scheduled { background:#bbdefb; color:#1565c0; }
.status-pending { background:#fff9c4; color:#f57f17; }
.status-completed { background:#c8e6c9; color:#2e7d32; }
.status-cancelled { background:#ffccbc; color:#d84315; }

/* Profile pic */
#profilePic { width:50px; height:50px; border-radius:50%; object-fit:cover; }

/* Doctor ranking item */
.doctor-item { display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid #f0f0f0; }
.doctor-item:last-child { border-bottom:none; }

/* Weekly calendar mini boxes */
.week-day { text-align:center; padding:10px; border-radius:8px; background:#f8f9fa; margin:5px; }
.week-day.active { background:#1a7c4a; color:#fff; font-weight:600; }
.week-day .day-name { font-size:0.75rem; font-weight:600; margin-bottom:5px; }
.week-day .day-count { font-size:1.5rem; font-weight:700; }

/* Scrollable containers */
.scrollable { max-height:300px; overflow-y:auto; }
.scrollable::-webkit-scrollbar { width:6px; }
.scrollable::-webkit-scrollbar-thumb { background:#1a7c4a; border-radius:10px; }

/* Prescription summary boxes */
.summary-box { padding:15px; border-left:4px solid #1a7c4a; background:#f8f9fa; border-radius:8px; margin-bottom:10px; }
.summary-box h5 { font-size:1.8rem; font-weight:700; color:#1a7c4a; margin:0; }
.summary-box p { font-size:0.85rem; color:#666; margin:0; }

/* Prescription item */
.prescription-item { padding:10px; border-bottom:1px solid #f0f0f0; }
.prescription-item:last-child { border-bottom:none; }
</style>
</head>
<body>

<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <div class="col-md-2 sidebar">
      <h4>MED<span class="text-success">+</span> Clinic</h4>
      <div class="text-center mt-3 mb-2">
        <img id="profilePic" src="uploads/<?= htmlspecialchars($profilePic) ?>" alt="Profile Picture">
        <p class="small text-muted mt-2">Administrator</p>
      </div>
      <nav class="nav flex-column mt-3" id="sidebarMenu">
        <a class="nav-link active" href="#" onclick="showDashboard(this)">
          <i class="bi bi-speedometer2 me-2"></i> Dashboard
        </a>
        <a class="nav-link" href="#" onclick="loadSection('admin_doctors.php', this)">
          <i class="bi bi-person-badge me-2"></i> Manage Doctors
        </a>
        <a class="nav-link" href="#" onclick="loadSection('admin_patients.php', this)">
          <i class="bi bi-people me-2"></i> Manage Patients
        </a>
        <a class="nav-link" href="#" onclick="loadSection('admin_appointments.php', this)">
          <i class="bi bi-calendar-check me-2"></i> Appointments
        </a>
        <a class="nav-link" href="#" onclick="loadSection('admin_prescriptions.php', this)">
          <i class="bi bi-prescription2 me-2"></i> Prescriptions
        </a>
        <a class="nav-link" href="#" onclick="loadSection('admin_reports.php', this)">
          <i class="bi bi-graph-up me-2"></i> Reports
        </a>
        <a class="nav-link" href="#" onclick="loadSection('admin_settings.php', this)">
          <i class="bi bi-gear me-2"></i> Settings
        </a>
        <a class="nav-link" href="#" onclick="loadSection('admin_profile.php', this)">
          <i class="bi bi-person-circle me-2"></i> Profile
        </a>
      </nav>
    </div>

    <!-- Main Content -->
    <div class="col-md-10 main-content p-0">
      <nav class="navbar navbar-light px-4">
        <span class="navbar-brand mb-0 h5">
          <i class="bi bi-shield-check me-2" style="color:#1a7c4a;"></i>
          Welcome back, <?= htmlspecialchars($fullname) ?> 👋
        </span>
        <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
      </nav>

      <div class="container-fluid mt-4 p-4">
        <!-- Dashboard Section -->
        <div id="dashboard-content">
          
          <!-- Stat Cards Row -->
          <div class="row g-3 mb-4">
            <div class="col-md-3">
              <div class="card stat-card">
                <div class="icon"><i class="bi bi-person-badge"></i></div>
                <h6>Total Doctors</h6>
                <h3><?= $totalDoctors ?></h3>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card stat-card">
                <div class="icon"><i class="bi bi-people"></i></div>
                <h6>Total Patients</h6>
                <h3><?= $totalPatients ?></h3>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card stat-card">
                <div class="icon"><i class="bi bi-calendar3"></i></div>
                <h6>Total Appointments</h6>
                <h3><?= $totalAppointments ?></h3>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card stat-card">
                <div class="icon"><i class="bi bi-prescription2"></i></div>
                <h6>Total Prescriptions</h6>
                <h3><?= $totalPrescriptions ?></h3>
              </div>
            </div>
          </div>

          <!-- Quick Actions Panel -->
          <div class="row g-3 mb-4">
            <div class="col-12">
              <div class="card p-4">
                <h6 class="text-muted mb-3"><i class="bi bi-lightning me-2"></i>Quick Actions</h6>
                <div class="row g-3">
                  <div class="col-md-2">
                    <a href="#" onclick="loadSection('admin_add_doctor.php', this)" class="quick-action-btn d-block text-decoration-none">
                      <i class="bi bi-person-plus"></i>
                      <span>Add Doctor</span>
                    </a>
                  </div>
                  <div class="col-md-2">
                    <a href="#" onclick="loadSection('admin_add_patient.php', this)" class="quick-action-btn d-block text-decoration-none">
                      <i class="bi bi-person-plus-fill"></i>
                      <span>Add Patient</span>
                    </a>
                  </div>
                  <div class="col-md-2">
                    <a href="#" onclick="loadSection('admin_create_appointment.php', this)" class="quick-action-btn d-block text-decoration-none">
                      <i class="bi bi-calendar-plus"></i>
                      <span>New Appointment</span>
                    </a>
                  </div>
                  <div class="col-md-2">
                    <a href="#" onclick="loadSection('admin_appointments.php', this)" class="quick-action-btn d-block text-decoration-none">
                      <i class="bi bi-list-check"></i>
                      <span>View All</span>
                    </a>
                  </div>
                  <div class="col-md-2">
                    <a href="#" onclick="loadSection('admin_reports.php', this)" class="quick-action-btn d-block text-decoration-none">
                      <i class="bi bi-file-earmark-bar-graph"></i>
                      <span>Reports</span>
                    </a>
                  </div>
                  <div class="col-md-2">
                    <a href="#" onclick="loadSection('admin_settings.php', this)" class="quick-action-btn d-block text-decoration-none">
                      <i class="bi bi-gear-fill"></i>
                      <span>Settings</span>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Recent & Upcoming Appointments Row -->
          <div class="row g-3 mb-4">
            <!-- Recent Appointments -->
            <div class="col-md-6">
              <div class="card p-4">
                <h6 class="text-muted mb-3"><i class="bi bi-clock-history me-2"></i>Recent Appointments</h6>
                <div class="scrollable">
                  <?php if($recentAppts && $recentAppts->num_rows > 0): ?>
                    <?php while($appt = $recentAppts->fetch_assoc()): ?>
                      <div class="appt-item">
                        <div class="d-flex justify-content-between align-items-center">
                          <div>
                            <div class="appt-time"><?= date('M d, Y', strtotime($appt['date'])) ?> • <?= date('g:i A', strtotime($appt['time'])) ?></div>
                            <div class="appt-patient"><?= htmlspecialchars($appt['patient_name']) ?></div>
                            <div class="appt-doctor">Dr. <?= htmlspecialchars($appt['doctor_name']) ?> - <?= htmlspecialchars($appt['specialty']) ?></div>
                          </div>
                          <span class="status-badge status-<?= strtolower($appt['status']) ?>">
                            <?= ucfirst($appt['status']) ?>
                          </span>
                        </div>
                      </div>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <p class="text-center text-muted py-4">No recent appointments</p>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <!-- Upcoming Appointments -->
            <div class="col-md-6">
              <div class="card p-4">
                <h6 class="text-muted mb-3"><i class="bi bi-calendar-event me-2"></i>Upcoming Appointments Schedule</h6>
                <div class="scrollable">
                  <?php if($upcomingAppts && $upcomingAppts->num_rows > 0): ?>
                    <?php while($appt = $upcomingAppts->fetch_assoc()): ?>
                      <div class="appt-item">
                        <div class="d-flex justify-content-between align-items-center">
                          <div>
                            <div class="appt-time"><?= date('M d, Y', strtotime($appt['date'])) ?> • <?= date('g:i A', strtotime($appt['time'])) ?></div>
                            <div class="appt-patient"><?= htmlspecialchars($appt['patient_name']) ?></div>
                            <div class="appt-doctor">Dr. <?= htmlspecialchars($appt['doctor_name']) ?> - <?= htmlspecialchars($appt['specialty']) ?></div>
                          </div>
                          <span class="status-badge status-<?= strtolower($appt['status']) ?>">
                            <?= ucfirst($appt['status']) ?>
                          </span>
                        </div>
                      </div>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <p class="text-center text-muted py-4">No upcoming appointments</p>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Appointments Overview Chart -->
          <div class="row g-3 mb-4">
            <div class="col-md-8">
              <div class="card p-4">
                <h6 class="text-muted mb-3"><i class="bi bi-graph-up me-2"></i>Appointments Overview (Last 6 Months)</h6>
                <canvas id="appointmentsChart" height="100"></canvas>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card p-4">
                <h6 class="text-muted mb-3"><i class="bi bi-pie-chart me-2"></i>Appointment Status</h6>
                <canvas id="statusChart" height="200"></canvas>
                <div class="mt-3">
                  <div class="d-flex justify-content-between mb-2">
                    <span class="small">Completed:</span>
                    <strong class="text-success"><?= $completedAppointments ?></strong>
                  </div>
                  <div class="d-flex justify-content-between mb-2">
                    <span class="small">Pending:</span>
                    <strong class="text-warning"><?= $pendingAppointments ?></strong>
                  </div>
                  <div class="d-flex justify-content-between mb-2">
                    <span class="small">Today:</span>
                    <strong class="text-primary"><?= $todayAppointments ?></strong>
                  </div>
                  <div class="d-flex justify-content-between">
                    <span class="small">Cancelled:</span>
                    <strong class="text-danger"><?= $cancelledAppointments ?></strong>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Prescription Statistics & Top Doctors -->
          <div class="row g-3 mb-4">
            <div class="col-md-4">
              <div class="card p-4">
                <h6 class="text-muted mb-3"><i class="bi bi-prescription2 me-2"></i>Prescription Statistics</h6>
                <div class="summary-box">
                  <h5><?= $totalPrescriptions ?></h5>
                  <p>Total Prescriptions</p>
                </div>
                <div class="summary-box">
                  <h5><?= $prescriptionsThisMonth ?></h5>
                  <p>This Month</p>
                </div>
                <div class="summary-box">
                  <h5><?= $prescriptionsLastMonth ?></h5>
                  <p>Last Month</p>
                </div>
              </div>
            </div>

            <div class="col-md-4">
              <div class="card p-4">
                <h6 class="text-muted mb-3"><i class="bi bi-award me-2"></i>Top Prescribing Doctors</h6>
                <div class="scrollable">
                  <?php if($topDoctors && $topDoctors->num_rows > 0): ?>
                    <?php $rank = 1; ?>
                    <?php while($doctor = $topDoctors->fetch_assoc()): ?>
                      <div class="doctor-item">
                        <div>
                          <span class="badge bg-success me-2">#<?= $rank++ ?></span>
                          <strong>Dr. <?= htmlspecialchars($doctor['fullname']) ?></strong>
                          <div class="small text-muted"><?= htmlspecialchars($doctor['specialty']) ?></div>
                        </div>
                        <span class="badge bg-primary"><?= $doctor['prescription_count'] ?></span>
                      </div>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <p class="text-center text-muted py-4">No data available</p>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <div class="col-md-4">
              <div class="card p-4">
                <h6 class="text-muted mb-3"><i class="bi bi-clock-history me-2"></i>Recent Prescriptions</h6>
                <div class="scrollable">
                  <?php if($recentPrescriptions && $recentPrescriptions->num_rows > 0): ?>
                    <?php while($rx = $recentPrescriptions->fetch_assoc()): ?>
                      <div class="prescription-item">
                        <div class="small text-muted"><?= date('M d, Y', strtotime($rx['created_at'])) ?></div>
                        <div class="fw-bold"><?= htmlspecialchars($rx['medication'] ?? 'N/A') ?></div>
                        <div class="small">Patient: <?= htmlspecialchars($rx['patient_name']) ?></div>
                        <div class="small">By: Dr. <?= htmlspecialchars($rx['doctor_name']) ?></div>
                      </div>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <p class="text-center text-muted py-4">No prescriptions yet</p>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Weekly Activity Calendar -->
          <div class="row g-3 mb-4">
            <div class="col-12">
              <div class="card p-4">
                <h6 class="text-muted mb-3"><i class="bi bi-calendar-week me-2"></i>Weekly Activity Calendar</h6>
                <div class="row g-2 mb-3">
                  <?php foreach($weeklyData as $day => $count): ?>
                    <div class="col">
                      <div class="week-day <?= $count > 0 ? 'active' : '' ?>">
                        <div class="day-name"><?= $day ?></div>
                        <div class="day-count"><?= $count ?></div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
                <canvas id="weeklyChart" height="80"></canvas>
              </div>
            </div>
          </div>

        </div>

        <!-- Dynamic Content Area -->
        <div id="content" class="mt-4" style="display:none;"></div>
      </div>
    </div>
  </div>
</div>

<script>
// Active link highlight
function setActiveLink(element) {
  document.querySelectorAll('#sidebarMenu .nav-link').forEach(link => link.classList.remove('active'));
  element.classList.add('active');
}

// Load section
function loadSection(url, el) {
  const content = document.getElementById('content');
  const dashboard = document.getElementById('dashboard-content');
  setActiveLink(el);

  dashboard.style.display = 'none';
  content.style.display = 'block';
  content.innerHTML = '<div class="text-center mt-5"><div class="spinner-border text-success"></div><p class="mt-2">Loading...</p></div>';

  fetch(url)
    .then(res=>res.text())
    .then(html=>{
      content.innerHTML = html;
    })
    .catch(()=>content.innerHTML="<p class='text-danger text-center mt-4'>❌ Failed to load content.</p>");
}

// Show dashboard
function showDashboard(element){
  setActiveLink(element);
  const dashboard=document.getElementById('dashboard-content');
  const content=document.getElementById('content');
  content.style.display='none';
  dashboard.style.display='block';
}

// Appointments Overview Chart (Last 6 Months)
const appointmentsCtx = document.getElementById('appointmentsChart');
new Chart(appointmentsCtx, {
  type: 'line',
  data: {
    labels: <?= json_encode(array_keys($monthlyData)) ?>,
    datasets: [{
      label: 'Appointments',
      data: <?= json_encode(array_values($monthlyData)) ?>,
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

// Appointment Status Chart (Doughnut)
const statusCtx = document.getElementById('statusChart');
new Chart(statusCtx, {
  type: 'doughnut',
  data: {
    labels: ['Completed', 'Pending', 'Today', 'Cancelled'],
    datasets: [{
      data: [<?= $completedAppointments ?>, <?= $pendingAppointments ?>, <?= $todayAppointments ?>, <?= $cancelledAppointments ?>],
      backgroundColor: ['#4caf50', '#ff9800', '#2196f3', '#f44336'],
      borderColor: '#fff',
      borderWidth: 2
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    plugins: { 
      legend: { 
        display: false
      }
    }
  }
});

// Weekly Activity Chart
const weeklyCtx = document.getElementById('weeklyChart');
new Chart(weeklyCtx, {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_keys($weeklyData)) ?>,
    datasets: [{
      label: 'Appointments',
      data: <?= json_encode(array_values($weeklyData)) ?>,
      backgroundColor: '#1a7c4a',
      borderRadius: 8
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
    plugins: { legend: { display: false } }
  }
});
</script>
</body>
</html>