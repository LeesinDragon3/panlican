<?php
session_start();
require_once 'db_connect.php';

// ✅ Allow only patients
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
    header('Location: login.php');
    exit();
}

$patientId = $_SESSION['user']['id'];
$fullname = $_SESSION['user']['fullname'] ?? 'Patient';
$profilePic = $_SESSION['user']['profile_pic'] ?? 'default.png'; // default picture
$today = date('Y-m-d');

// ✅ Helper for safe count
function safeCount($conn, $sql) {
    $result = $conn->query($sql);
    if (!$result) return 0;
    $row = $result->fetch_assoc();
    return $row ? intval($row['c']) : 0;
}

// Dashboard stats
$totalAppointments = safeCount($conn, "SELECT COUNT(*) AS c FROM appointments WHERE patient_id=$patientId");
$todayAppointments = safeCount($conn, "SELECT COUNT(*) AS c FROM appointments WHERE patient_id=$patientId AND date='$today'");
$completedAppointments = safeCount($conn, "SELECT COUNT(*) AS c FROM appointments WHERE patient_id=$patientId AND status='completed'");

// Example chart data
$chartData = ["Jan"=>2,"Feb"=>3,"Mar"=>4,"Apr"=>3,"May"=>5,"Jun"=>4,"Jul"=>5,"Aug"=>6,"Sep"=>4,"Oct"=>5,"Nov"=>3,"Dec"=>4];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Patient Dashboard - E-Clinic</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body { background-color:#f4f7f9; font-family:'Segoe UI',sans-serif; }
.sidebar { height:100vh; background:#eaf7ef; padding:20px 10px; border-right:1px solid #ddd; }
.sidebar h4 { font-weight:700; color:#1a7c4a; text-align:center; }
.sidebar .nav-link { color:#333; border-radius:8px; margin:5px 0; font-weight:500; transition:background 0.3s,color 0.3s; }
.sidebar .nav-link:hover, .sidebar .nav-link.active { background-color:#1a7c4a; color:#fff; }
.navbar { background:#fff; box-shadow:0 2px 5px rgba(0,0,0,0.05); }
.card { border:none; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.08); }

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
      </div>
      <nav class="nav flex-column mt-2" id="sidebarMenu">
        <a class="nav-link active" href="#" onclick="showDashboard(this)">
          <i class="bi bi-speedometer2 me-2"></i> Dashboard
        </a>

        <!-- ✅ FIXED: Opens as a separate page now -->
        <a class="nav-link" href="#" onclick="loadSection('patient_add_appointment.php', this)">
          <i class="bi bi-plus-circle me-2"></i> Add Appointment
        </a>

        <a class="nav-link" href="#" onclick="loadSection('patient_appointments.php', this)">
          <i class="bi bi-calendar-event me-2"></i> Appointments
        </a>

        <a class="nav-link" href="#" onclick="loadSection('patient_prescriptions.php', this)">
          <i class="bi bi-capsule me-2"></i> Prescriptions
        </a>

        <a class="nav-link" href="#" onclick="loadSection('patient_profile.php', this)">
          <i class="bi bi-person-circle me-2"></i> Profile
        </a>

        <a class="nav-link" href="#" id="notificationsLink" onclick="openNotifications(this)">
          <i class="bi bi-bell me-2"></i> Notifications
          <span id="notifDot" class="badge rounded-pill bg-success ms-1" style="display:none;">&nbsp;</span>
        </a>
      </nav>
    </div>

    <!-- Main Content -->
    <div class="col-md-10 p-0">
      <nav class="navbar navbar-light px-4">
        <span class="navbar-brand mb-0 h5">Welcome back, <?= htmlspecialchars($fullname) ?> 👋</span>
        <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
      </nav>

      <div class="container mt-4">
        <!-- Dashboard Section -->
        <div id="dashboard-content" class="slide-in">
          <div class="row g-3">
            <div class="col-md-4">
              <div class="card text-center p-3">
                <h6 class="text-muted">Appointments Today</h6>
                <h3><?= $todayAppointments ?></h3>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card text-center p-3">
                <h6 class="text-muted">Total Appointments</h6>
                <h3 class="text-success"><?= $totalAppointments ?></h3>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card text-center p-3">
                <h6 class="text-muted">Completed</h6>
                <h3 class="text-primary"><?= $completedAppointments ?></h3>
              </div>
            </div>
          </div>

          <div class="row mt-4 g-3">
            <div class="col-md-8">
              <div class="card p-3">
                <h6 class="text-muted">Monthly Appointments</h6>
                <canvas id="appointmentsChart" height="100"></canvas>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card p-3">
                <h6 class="text-muted">Appointment Status</h6>
                <canvas id="statusChart" height="180"></canvas>
              </div>
            </div>
          </div>
        </div>

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

  content.innerHTML = '<div class="text-center mt-5"><div class="spinner-border text-success"></div><p>Loading...</p></div>';
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

// Notifications
function openNotifications(el){
  setActiveLink(el);
  document.getElementById('notifDot').style.display='none';
  loadSection('patient_notifications.php', el);
}

// Charts
const ctx = document.getElementById('appointmentsChart');
new Chart(ctx,{type:'line',data:{labels:<?= json_encode(array_keys($chartData)) ?>,datasets:[{label:'Appointments',data:<?= json_encode(array_values($chartData)) ?>,borderColor:'#1a7c4a',fill:false,tension:0.3}]},options:{responsive:true,scales:{y:{beginAtZero:true}}}});

const ctx2=document.getElementById('statusChart');
new Chart(ctx2,{type:'doughnut',data:{labels:['Completed','Pending','Cancelled'],datasets:[{data:[<?= $completedAppointments ?>,<?= $totalAppointments-$completedAppointments ?>,0],backgroundColor:['#2ecc71','#f1c40f','#e74c3c']}]}});

// Notification dot refresh
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

// --- Prevent forms inside dynamic sections from refreshing page ---
document.addEventListener('submit', function(e) {
  const form = e.target;

  // Only intercept forms loaded dynamically inside #content
  if (form.closest('#content')) {
    e.preventDefault(); // stop full reload

    // If the form has a search input, handle AJAX search
    if (form.classList.contains('ajax-search')) {
      const formData = new FormData(form);
      fetch(form.action, {
        method: 'POST',
        body: formData
      })
      .then(res => res.text())
      .then(html => {
        // Replace just the section with search results
        document.getElementById('content').innerHTML = html;
      })
      .catch(() => {
        document.getElementById('content').innerHTML =
          "<p class='text-danger text-center mt-4'>❌ Search failed. Try again.</p>";
      });
    }
  }
});

// --- Prevent forms inside dynamic sections from refreshing page ---
document.addEventListener('submit', function(e) {
  const form = e.target;

  // Intercept only forms inside dynamically loaded sections
  if (form.closest('#content')) {
    e.preventDefault();

    const formData = new FormData(form);
    const action = form.action || window.location.href;

    // --- Detect if this is a search form ---
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

    // --- Otherwise, normal form (like Add Appointment) ---
    fetch(action, { method: 'POST', body: formData })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          // ✅ Show success alert
          alert('Appointment saved successfully!');
          // ✅ Redirect back to dashboard
          showDashboard(document.querySelector('#sidebarMenu .nav-link:first-child'));
        } else {
          alert(data.message || 'Something went wrong!');
        }
      })
      .catch(() => alert('❌ Failed to submit form. Please try again.'));
  }
});


</script>
</body>
</html>
