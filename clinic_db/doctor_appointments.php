<?php
session_start();
require_once 'db_connect.php';

// ✅ Only allow logged-in doctors
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    echo "<p class='text-danger text-center mt-4'>Access denied.</p>";
    exit();
}

$doctorId = $_SESSION['user']['id'];
?>

<style>
/* ✅ Smooth card fade-in */
.slide-in {
  animation: fadeIn 0.5s ease-in-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

/* ✅ Appointment cards style */
.appointment-card {
  border-left: 5px solid var(--bs-success);
  transition: all 0.25s ease-in-out;
}
.appointment-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

/* ✅ Status badge look */
.badge {
  font-size: 0.8rem;
  padding: 0.4em 0.6em;
}

/* ✅ Sticky filter bar */
#filterBar {
  position: sticky;
  top: 0;
  z-index: 10;
  background: #fff;
  border-bottom: 1px solid #dee2e6;
  padding-bottom: .75rem;
  margin-bottom: 1rem;
}
</style>

<div class="card p-3 slide-in">
  <!-- 🧭 Filter bar -->
  <div id="filterBar" class="d-flex flex-wrap justify-content-between align-items-center">
    <h5 class="mb-2 mb-md-0">
      <i class="bi bi-calendar-event me-2 text-success"></i>
      <strong>My Appointments</strong>
    </h5>
    <div class="d-flex flex-wrap gap-2">
      <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search by name/contact" style="max-width:200px;">
      <input type="date" id="dateFilter" class="form-control form-control-sm">
      <button id="clearFilters" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-x-circle"></i> Clear
      </button>
    </div>
  </div>

  <!-- 📋 Appointment list -->
  <div id="appointmentsTable" class="mt-2">
    <div class="text-center text-muted py-4">Loading appointments...</div>
  </div>
</div>

<script>
// ✅ Fetch appointments dynamically
function fetchAppointments() {
  const search = document.getElementById('searchInput').value;
  const date = document.getElementById('dateFilter').value;

  fetch(`doctor_fetch_appointments.php?search=${encodeURIComponent(search)}&date=${encodeURIComponent(date)}`)
    .then(res => res.text())
    .then(html => {
      document.getElementById('appointmentsTable').innerHTML = html;
    });
}

// ✅ Event listeners
document.getElementById('searchInput').addEventListener('input', fetchAppointments);
document.getElementById('dateFilter').addEventListener('change', fetchAppointments);
document.getElementById('clearFilters').addEventListener('click', () => {
  document.getElementById('searchInput').value = '';
  document.getElementById('dateFilter').value = '';
  fetchAppointments();
});

// ✅ Initial load
fetchAppointments();
</script>
