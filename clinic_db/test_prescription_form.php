<?php
require_once 'db_connect.php';
session_start();

$doctor_id = $_SESSION['user']['id'] ?? 49;

if ($_POST) {
    $patient_id = $_POST['patient_id'] ?? 0;
    $medicine = $_POST['medicine'] ?? '';
    $dosage = $_POST['dosage'] ?? '';
    $instructions = $_POST['instructions'] ?? '';
    
    echo "<h3>Processing:</h3>";
    echo "<p>Patient ID: $patient_id</p>";
    echo "<p>Medicine: $medicine</p>";
    echo "<p>Dosage: $dosage</p>";
    echo "<p>Instructions: $instructions</p>";
    
    // Check if patient exists
    $check = $conn->query("SELECT id FROM appointments WHERE doctor_id = $doctor_id AND patient_id = $patient_id LIMIT 1");
    echo "<p>Patient found: " . ($check->num_rows > 0 ? 'YES' : 'NO') . "</p>";
    
    if ($check->num_rows > 0) {
        // Get appointment
        $apptResult = $conn->query("SELECT id FROM appointments WHERE doctor_id = $doctor_id AND patient_id = $patient_id ORDER BY date DESC LIMIT 1");
        $appt = $apptResult->fetch_assoc();
        $appointment_id = $appt['id'];
        
        echo "<p>Appointment ID: $appointment_id</p>";
        
        // INSERT
        $sql = "INSERT INTO prescriptions (doctor_id, appointment_id, patient_id, medicine, dosage, instructions, date_issued) 
                VALUES ($doctor_id, $appointment_id, $patient_id, '$medicine', '$dosage', '$instructions', NOW())";
        
        if ($conn->query($sql)) {
            echo "<p style='color:green; font-size:18px;'><strong>✅ SUCCESS!</strong></p>";
        } else {
            echo "<p style='color:red;'><strong>Error: " . $conn->error . "</strong></p>";
        }
    }
}
?>

<h2>Test Prescription Form</h2>
<p>Doctor ID: <strong><?= $doctor_id ?></strong></p>

<form method="POST" style="background: #f0f0f0; padding: 20px; max-width: 400px; border-radius: 5px;">
    <div style="margin: 15px 0;">
        <label style="display: block; font-weight: bold; margin-bottom: 5px;">Patient:</label>
        <select name="patient_id" required style="width: 100%; padding: 8px;">
            <option value="">-- Select Patient --</option>
            <?php
            $patients = $conn->query("SELECT DISTINCT a.patient_id, u.fullname FROM appointments a LEFT JOIN users u ON a.patient_id = u.id WHERE a.doctor_id = $doctor_id ORDER BY u.fullname");
            while ($p = $patients->fetch_assoc()) {
                echo "<option value='" . $p['patient_id'] . "'>" . $p['fullname'] . "</option>";
            }
            ?>
        </select>
    </div>
    
    <div style="margin: 15px 0;">
        <label style="display: block; font-weight: bold; margin-bottom: 5px;">Medicine:</label>
        <input type="text" name="medicine" required style="width: 100%; padding: 8px;" placeholder="e.g. Paracetamol">
    </div>
    
    <div style="margin: 15px 0;">
        <label style="display: block; font-weight: bold; margin-bottom: 5px;">Dosage:</label>
        <input type="text" name="dosage" required style="width: 100%; padding: 8px;" placeholder="e.g. 500mg">
    </div>
    
    <div style="margin: 15px 0;">
        <label style="display: block; font-weight: bold; margin-bottom: 5px;">Instructions:</label>
        <textarea name="instructions" required style="width: 100%; padding: 8px; height: 100px;" placeholder="e.g. Take 3 times daily after meals"></textarea>
    </div>
    
    <button type="submit" style="width: 100%; padding: 12px; background: #1a7c4a; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; font-size: 16px;">
        SAVE PRESCRIPTION
    </button>
</form>

<hr>

<h3>Your Prescriptions:</h3>
<?php
$result = $conn->query("SELECT p.*, u.fullname FROM prescriptions p LEFT JOIN users u ON p.patient_id = u.id WHERE p.doctor_id = $doctor_id ORDER BY p.date_issued DESC LIMIT 10");

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #1a7c4a; color: white;'><th>Patient</th><th>Medicine</th><th>Dosage</th><th>Instructions</th><th>Date</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['fullname'] . "</td>";
        echo "<td><strong>" . $row['medicine'] . "</strong></td>";
        echo "<td>" . $row['dosage'] . "</td>";
        echo "<td>" . substr($row['instructions'], 0, 50) . "...</td>";
        echo "<td>" . $row['date_issued'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No prescriptions yet</p>";
}
?>

<style>
body { font-family: Arial; margin: 20px; background: #f9f9f9; }
h2 { color: #1a7c4a; }
h3 { color: #333; margin-top: 30px; }
</style>