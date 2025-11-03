<?php
require_once 'db_connect.php';
session_start();

$doctor_id = $_SESSION['user']['id'] ?? 49;

echo "<h2>Create Test Appointment</h2>";
echo "<p>Doctor ID: <strong>$doctor_id</strong></p>";

if ($_POST) {
    $patient_id = $_POST['patient_id'] ?? 0;
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    
    echo "<h3>Creating appointment:</h3>";
    echo "<p>Patient ID: $patient_id</p>";
    echo "<p>Date: $date</p>";
    echo "<p>Time: $time</p>";
    
    $sql = "INSERT INTO appointments (doctor_id, patient_id, date, time, status) 
            VALUES ($doctor_id, $patient_id, '$date', '$time', 'scheduled')";
    
    if ($conn->query($sql)) {
        echo "<p style='color:green; font-size:18px;'><strong>✅ Appointment created!</strong></p>";
    } else {
        echo "<p style='color:red;'><strong>Error: " . $conn->error . "</strong></p>";
    }
}
?>

<form method="POST" style="background: #f0f0f0; padding: 20px; max-width: 400px; border-radius: 5px;">
    <div style="margin: 15px 0;">
        <label style="display: block; font-weight: bold; margin-bottom: 5px;">Patient:</label>
        <select name="patient_id" required style="width: 100%; padding: 8px;">
            <option value="">-- Select Patient --</option>
            <?php
            $patients = $conn->query("SELECT id, fullname FROM users WHERE role = 'patient' ORDER BY fullname");
            while ($p = $patients->fetch_assoc()) {
                echo "<option value='" . $p['id'] . "'>" . $p['fullname'] . "</option>";
            }
            ?>
        </select>
    </div>
    
    <div style="margin: 15px 0;">
        <label style="display: block; font-weight: bold; margin-bottom: 5px;">Date:</label>
        <input type="date" name="date" required style="width: 100%; padding: 8px;">
    </div>
    
    <div style="margin: 15px 0;">
        <label style="display: block; font-weight: bold; margin-bottom: 5px;">Time:</label>
        <input type="time" name="time" required style="width: 100%; padding: 8px;">
    </div>
    
    <button type="submit" style="width: 100%; padding: 12px; background: #1a7c4a; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; font-size: 16px;">
        CREATE APPOINTMENT
    </button>
</form>

<hr>

<h3>Your Current Appointments:</h3>
<?php
$result = $conn->query("SELECT a.*, u.fullname FROM appointments a LEFT JOIN users u ON a.patient_id = u.id WHERE a.doctor_id = $doctor_id ORDER BY a.date DESC");

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #1a7c4a; color: white;'><th>Patient</th><th>Date</th><th>Time</th><th>Status</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['fullname'] . "</td>";
        echo "<td>" . $row['date'] . "</td>";
        echo "<td>" . $row['time'] . "</td>";
        echo "<td>" . ucfirst($row['status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No appointments yet</p>";
}
?>

<style>
body { font-family: Arial; margin: 20px; background: #f9f9f9; }
h2 { color: #1a7c4a; }
h3 { color: #333; margin-top: 30px; }
</style>