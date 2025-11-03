<?php
session_start();
require_once 'db_connect.php';

// ✅ Allow only doctors
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    header('Location: login.php');
    exit();
}

$doctorId = $_SESSION['user']['id'];
$fullname = $_SESSION['user']['fullname'] ?? 'Doctor';
$profilePic = $_SESSION['user']['profile_pic'] ?? 'default.png';
$specialty = $_SESSION['user']['specialty'] ?? 'General Practice';
$today = date('Y-m-d');

// ✅ Helper for safe count
function safeCount($conn, $sql) {
    $result = $conn->query($sql);
    if (!$result) return 0;
    $row = $result->fetch_assoc();
    return $row ? intval($row['c']) : 0;
}

// Dashboard stats
$totalAppointments = safeCount($conn, "SELECT COUNT(*) AS c FROM appointments WHERE doctor_id=$doctorId");
$todayAppointments = safeCount($conn, "SELECT COUNT(*) AS c FROM appointments WHERE doctor_id=$doctorId AND date='$today' AND status='scheduled'");
$completedAppointments = safeCount($conn, "SELECT COUNT(*) AS c FROM appointments WHERE doctor_id=$doctorId AND status='completed'");
$pendingAppointments = safeCount($conn, "SELECT COUNT(*) AS c FROM appointments WHERE doctor_id=$doctorId AND status='pending'");
$totalPatients = safeCount($conn, "SELECT COUNT(DISTINCT patient_id) AS c FROM appointments WHERE doctor_id=$doctorId");

// Example chart data for monthly consultations
$chartData = ["Jan"=>5,"Feb"=>7,"Mar"=>8,"Apr"=>6,"May"=>10,"Jun"=>9,"Jul"=>11,"Aug"=>12,"Sep"=>8,"Oct"=>10,"Nov"=>9,"Dec"=>7];

// Fetch today's appointments
$todayAppointmentsQuery = "SELECT a.id, a.date, a.time, a.status, u.fullname FROM appointments a 
                           JOIN users u ON a.patient_id = u.id 
                           WHERE a.doctor_id=$doctorId AND a.date='$today' 
                           ORDER BY a.time ASC LIMIT 5";
$todayAppts = $conn->query($todayAppointmentsQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Doctor Dashboard - E-Clinic</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body { background-color:#f4f7f9; font-family:'Segoe UI',sans-serif; }
.sidebar { height:100vh; background:#eaf7ef; padding:20px 10px; border-right:1px solid #ddd; overflow-y:auto; }
.sidebar h4 { font-weight:700; color:#1a7c4a; text-align:center; }
.sidebar .nav-link { color:#333; border-radius:8px; margin:5px 0; font-weight:500; transition:background 0.3s,color 0.3s; }
.sidebar .nav-link:hover, .sidebar .nav-link.active { background-color:#1a7c4a; color:#fff; }
.navbar { background:#fff; box-shadow:0 2px 5px rgba(0,0,0,0.05); }
.card { border:none; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.08); transition:transform 0.3s,box-shadow 0.3s; }
.card:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,0.12); }

/* Stat cards */
.stat-card { text-align:center; padding:20px; }
.stat-card h6 { font-size:0.85rem; color:#666; font-weight:500; }
.stat-card h3 { font-size:2.5rem; font-weight:700; margin:10px 0; }
.stat-card .icon { font-size:2.5rem; margin-bottom:10px; }
.stat-card.blue { color:#1a7c4a; }
.stat-card.green { color:#1a7c4a; }
.stat-card.orange { color:#1a7c4a; }
.stat-card.red { color:#1a7c4a; }

/* Slide animations */
#content { position:relative; min-height:300px; overflow:hidden; }
.slide-in { animation:slideIn 0.4s ease-in-out forwards; }
.slide-out { animation:slideOut 0.4s ease-in-out forwards; }
@keyframes slideIn { from {opacity:0; transform:translateX(50px);} to {opacity:1; transform:translateX(0);} }
@keyframes slideOut { from {opacity:1; transform:translateX(0);} to {opacity:0; transform:translateX(-50px);} }

/* Notification dot */
#notifDot { width:10px; height:10px; display:inline-block; border-radius:50%; }

/* Profile pic */
#profilePic { width:50px; height:50px; border-radius:50%; object-fit:cover; }

/* Appointment table */
.appt-table { font-size:0.9rem; }
.appt-table td { vertical-align:middle; padding:10px; }
.status-badge { padding:5px 10px; border-radius:6px; font-size:0.8rem; font-weight:600; }
.status-scheduled { background:#bbdefb; color:#1565c0; }
.status-pending { background:#fff9c4; color:#f57f17; }
.status-completed { background:#c8e6c9; color:#2e7d32; }
.status-cancelled { background:#ffccbc; color:#d84315; }
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
        <p class="small text-muted mt-2"><?= htmlspecialchars($specialty) ?></p>
      </div>
      <nav class="nav flex-column mt-3" id="sidebarMenu">
        <a class="nav-link active" href="#" onclick="showDashboard(this)">
          <i class="bi bi-speedometer2 me-2"></i> Dashboard
        </a>

        <a class="nav-link" href="#" onclick="loadSection('doctor_appointments.php', this)">
          <i class="bi bi-calendar-check me-2"></i> My Appointments
        </a>

        <a class="nav-link" href="#" onclick="loadSection('doctor_patients.php', this)">
          <i class="bi bi-people me-2"></i> My Patients
        </a>

        <a class="nav-link" href="#" onclick="loadSection('doctor_prescriptions.php', this)">
          <i class="bi bi-prescription2 me-2"></i> Prescriptions
        </a>

        <a class="nav-link" href="#" onclick="loadSection('doctor_medical_records.php', this)">
          <i class="bi bi-file-medical me-2"></i> Medical Records
        </a>

        <a class="nav-link" href="#" onclick="loadSection('doctor_schedule.php', this)">
          <i class="bi bi-clock me-2"></i> Schedule
        </a>

        <a class="nav-link" href="#" id="notificationsLink" onclick="openNotifications(this)">
          <i class="bi bi-bell me-2"></i> Notifications
          <span id="notifDot" class="badge rounded-pill bg-danger ms-1" style="display:none;">&nbsp;</span>
        </a>

        <a class="nav-link" href="#" onclick="loadSection('doctor_profile.php', this)">
          <i class="bi bi-person-circle me-2"></i> Profile
        </a>
      </nav>
    </div>

    <!-- Main Content -->
    <div class="col-md-10 p-0">
      <nav class="navbar navbar-light px-4">
        <span class="navbar-brand mb-0 h5">
          <i class="bi bi-stethoscope me-2" style="color:#1a7c4a;"></i>
          Welcome back, Dr. <?= htmlspecialchars($fullname) ?> 👋
        </span>
        <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
      </nav>

      <div class="container-fluid mt-4">
        <!-- Dashboard Section -->
        <div id="dashboard-content" class="slide-in">
          <!-- Stat Cards Row -->
          <div class="row g-3 mb-4">
            <div class="col-md-3">
              <div class="card stat-card blue">
                <div class="icon"><i class="bi bi-calendar3"></i></div>
                <h6>Today's Appointments</h6>
                <h3><?= $todayAppointments ?></h3>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card stat-card green">
                <div class="icon"><i class="bi bi-people"></i></div>
                <h6>Total Patients</h6>
                <h3><?= $totalPatients ?></h3>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card stat-card orange">
                <div class="icon"><i class="bi bi-hourglass-split"></i></div>
                <h6>Pending</h6>
                <h3><?= $pendingAppointments ?></h3>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card stat-card red">
                <div class="icon"><i class="bi bi-check-circle"></i></div>
                <h6>Completed</h6>
                <h3><?= $completedAppointments ?></h3>
              </div>
            </div>
          </div>

          <!-- Charts & Today's Schedule Row -->
          <div class="row g-3 mb-4">
            <div class="col-md-8">
              <div class="card p-4">
                <h6 class="text-muted mb-3"><i class="bi bi-graph-up me-2"></i>Monthly Consultations</h6>
                <canvas id="appointmentsChart" height="80"></canvas>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card p-4">
                <h6 class="text-muted mb-3"><i class="bi bi-pie-chart me-2"></i>Appointment Status</h6>
                <canvas id="statusChart" height="180"></canvas>
              </div>
            </div>
          </div>

          <!-- Today's Appointments Table -->
          <div class="row g-3">
            <div class="col-12">
              <div class="card p-4">
                <h6 class="text-muted mb-3"><i class="bi bi-list-check me-2"></i>Today's Schedule</h6>
                <div class="table-responsive">
                  <table class="table table-hover appt-table">
                    <thead class="table-light">
                      <tr>
                        <th>Time</th>
                        <th>Patient Name</th>
                        <th>Status</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if($todayAppts && $todayAppts->num_rows > 0): ?>
                        <?php while($appt = $todayAppts->fetch_assoc()): ?>
                          <tr>
                            <td><strong><?= htmlspecialchars($appt['time']) ?></strong></td>
                            <td><?= htmlspecialchars($appt['fullname']) ?></td>
                            <td>
                              <span class="status-badge status-<?= strtolower($appt['status']) ?>">
                                <?= ucfirst($appt['status']) ?>
                              </span>
                            </td>
                            <td>
                              <a href="#" class="btn btn-sm btn-primary" title="View Details">
                                <i class="bi bi-eye"></i>
                              </a>
                              <a href="#" class="btn btn-sm btn-success" title="Complete">
                                <i class="bi bi-check"></i>
                              </a>
                            </td>
                          </tr>
                        <?php endwhile; ?>
                      <?php else: ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">No appointments scheduled for today</td></tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Dynamic Content Area -->
        <div id="content" class="mt-4"></div>
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

// Load section with animation
function loadSection(url, el) {
  const content = document.getElementById('content');
  const dashboard = document.getElementById('dashboard-content');
  setActiveLink(el);

  if (dashboard.style.display !== 'none') {
    dashboard.classList.remove('slide-in');
    dashboard.classList.add('slide-out');
  }

  content.innerHTML = '<div class="text-center mt-5"><div class="spinner-border text-primary"></div><p>Loading...</p></div>';
  content.style.display = 'block';
  content.classList.remove('slide-in','slide-out');

  fetch(url)
    .then(res=>res.text())
    .then(html=>{
      content.innerHTML = html;
      content.classList.add('slide-in');
      dashboard.style.display='none';
    })
    .catch(()=>content.innerHTML="<p class='text-danger text-center mt-4'>❌ Failed to load content.</p>");
}

// Show dashboard
function showDashboard(element){
  setActiveLink(element);
  const dashboard=document.getElementById('dashboard-content');
  const content=document.getElementById('content');
  content.style.display='none';
  content.innerHTML='';
  dashboard.style.display='block';
  dashboard.classList.remove('slide-out');
  dashboard.classList.add('slide-in');
}

// Open notifications
function openNotifications(el){
  setActiveLink(el);
  document.getElementById('notifDot').style.display='none';
  loadSection('doctor_notifications.php', el);
}

// Monthly Consultations Chart
const ctx = document.getElementById('appointmentsChart');
new Chart(ctx,{
  type:'line',
  data:{
    labels:<?= json_encode(array_keys($chartData)) ?>,
    datasets:[{
      label:'Consultations',
      data:<?= json_encode(array_values($chartData)) ?>,
      borderColor:'#1a7c4a',
      backgroundColor:'rgba(26, 124, 74, 0.1)',
      fill:true,
      tension:0.4,
      pointRadius:5,
      pointBackgroundColor:'#1a7c4a',
      pointBorderColor:'#fff',
      pointBorderWidth:2
    }]
  },
  options:{
    responsive:true,
    maintainAspectRatio:true,
    scales:{y:{beginAtZero:true,ticks:{stepSize:2}}},
    plugins:{legend:{display:false}}
  }
});

// Appointment Status Chart
const ctx2=document.getElementById('statusChart');
new Chart(ctx2,{
  type:'doughnut',
  data:{
    labels:['Completed','Pending','Scheduled','Cancelled'],
    datasets:[{
      data:[<?= $completedAppointments ?>,<?= $pendingAppointments ?>,<?= $todayAppointments ?>,0],
      backgroundColor:['#4caf50','#ff9800','#2196f3','#f44336'],
      borderColor:'#fff',
      borderWidth:2
    }]
  },
  options:{responsive:true,maintainAspectRatio:true,plugins:{legend:{position:'bottom'}}}
});

// Update notification dot
function updateNotifDot(){
  fetch('get_unread_notifications.php')
    .then(res=>res.text())
    .then(count=>{
      const dot=document.getElementById('notifDot');
      if(dot) dot.style.display=parseInt(count)>0?'inline-block':'none';
    })
    .catch(err=>console.error(err));
}
setInterval(updateNotifDot,10000);
updateNotifDot();

// Form submission handler
document.addEventListener('submit', function(e) {
  const form = e.target;

  if (form.closest('#content')) {
    e.preventDefault();

    const formData = new FormData(form);
    const action = form.action || window.location.href;

    if (form.classList.contains('ajax-search')) {
      fetch(action, { method: 'POST', body: formData })
        .then(res => res.text())
        .then(html => {
          document.getElementById('content').innerHTML = html;
        })
        .catch(() => {
          document.getElementById('content').innerHTML =
            "<p class='text-danger text-center mt-4'>❌ Search failed. Try again.</p>";
        });
      return;
    }

    fetch(action, { method: 'POST', body: formData })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert('✅ ' + (data.message || 'Operation completed successfully!'));
          showDashboard(document.querySelector('#sidebarMenu .nav-link:first-child'));
        } else {
          alert('❌ ' + (data.message || 'Something went wrong!'));
        }
      })
      .catch(() => alert('❌ Failed to submit form. Please try again.'));
  }
});
</script>
</body>
</html>