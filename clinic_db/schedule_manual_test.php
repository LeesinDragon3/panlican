<?php
require_once 'db_connect.php';
session_start();

$doctor_id = $_SESSION['user']['id'] ?? 49;

if ($_POST) {
    $day = $_POST['day'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    
    echo "<h3>Processing your input:</h3>";
    echo "<p>Day: <strong>$day</strong></p>";
    echo "<p>Start: <strong>$start_time</strong></p>";
    echo "<p>End: <strong>$end_time</strong></p>";
    echo "<p>Available: <strong>" . ($is_available ? 'Yes' : 'No') . "</strong></p>";
    
    // Check if exists
    $check = $conn->query("SELECT id FROM doctor_schedules WHERE doctor_id = $doctor_id AND day_of_week = '$day'");
    
    if ($check->num_rows > 0) {
        $sql = "UPDATE doctor_schedules SET start_time = '$start_time', end_time = '$end_time', is_available = $is_available WHERE doctor_id = $doctor_id AND day_of_week = '$day'";
        echo "<p>Running UPDATE...</p>";
    } else {
        $sql = "INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, is_available) VALUES ($doctor_id, '$day', '$start_time', '$end_time', $is_available)";
        echo "<p>Running INSERT...</p>";
    }
    
    if ($conn->query($sql)) {
        echo "<p style='color:green; font-size:18px;'><strong>✅ SUCCESS! Data saved!</strong></p>";
    } else {
        echo "<p style='color:red; font-size:18px;'><strong>❌ ERROR: " . $conn->error . "</strong></p>";
    }
}
?>

<h2>Simple Schedule Save Test</h2>
<p>Doctor ID: <strong><?= $doctor_id ?></strong></p>

<form method="POST" style="background: #f0f0f0; padding: 20px; border-radius: 5px; max-width: 400px;">
    <div style="margin: 15px 0;">
        <label style="display: block; font-weight: bold; margin-bottom: 5px;">Day of Week:</label>
        <select name="day" required style="width: 100%; padding: 8px; font-size: 14px;">
            <option value="">-- Select Day --</option>
            <option value="Monday">Monday</option>
            <option value="Tuesday">Tuesday</option>
            <option value="Wednesday">Wednesday</option>
            <option value="Thursday">Thursday</option>
            <option value="Friday">Friday</option>
            <option value="Saturday">Saturday</option>
            <option value="Sunday">Sunday</option>
        </select>
    </div>
    
    <div style="margin: 15px 0;">
        <label style="display: block; font-weight: bold; margin-bottom: 5px;">Start Time:</label>
        <input type="time" name="start_time" required style="width: 100%; padding: 8px; font-size: 14px;">
    </div>
    
    <div style="margin: 15px 0;">
        <label style="display: block; font-weight: bold; margin-bottom: 5px;">End Time:</label>
        <input type="time" name="end_time" required style="width: 100%; padding: 8px; font-size: 14px;">
    </div>
    
    <div style="margin: 15px 0;">
        <label style="font-weight: bold;">
            <input type="checkbox" name="is_available"> Available for appointments
        </label>
    </div>
    
    <button type="submit" style="width: 100%; padding: 12px; background: #1a7c4a; color: white; border: none; border-radius: 5px; font-size: 16px; font-weight: bold; cursor: pointer;">
        SAVE SCHEDULE
    </button>
</form>

<hr>

<h3>Current Schedule for Doctor ID <?= $doctor_id ?>:</h3>
<?php
$result = $conn->query("SELECT * FROM doctor_schedules WHERE doctor_id = $doctor_id ORDER BY CASE WHEN day_of_week = 'Monday' THEN 1 WHEN day_of_week = 'Tuesday' THEN 2 WHEN day_of_week = 'Wednesday' THEN 3 WHEN day_of_week = 'Thursday' THEN 4 WHEN day_of_week = 'Friday' THEN 5 WHEN day_of_week = 'Saturday' THEN 6 WHEN day_of_week = 'Sunday' THEN 7 ELSE 8 END");

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #1a7c4a; color: white;'><th>Day</th><th>Start</th><th>End</th><th>Available</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $available = $row['is_available'] ? '✅ Yes' : '❌ No';
        echo "<tr><td>" . $row['day_of_week'] . "</td><td>" . $row['start_time'] . "</td><td>" . $row['end_time'] . "</td><td>" . $available . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>No schedules found.</p>";
}
?>

<style>
body { font-family: Arial; margin: 20px; background: #f9f9f9; }
h2 { color: #1a7c4a; }
h3 { color: #333; margin-top: 30px; }
</style>