<?php
require_once 'db_connect.php';
session_start();

echo "<h2>Test Schedule Save</h2>";

$doctor_id = $_SESSION['user']['id'] ?? 49;
echo "<p>Doctor ID: <strong>$doctor_id</strong></p>";

// Simulate POST data
$_POST['day'] = 'Monday';
$_POST['start_time'] = '08:00';
$_POST['end_time'] = '18:00';
$_POST['is_available'] = 1;

// Run the same logic as schedule_save.php
$day = trim($_POST['day']);
$start_time = trim($_POST['start_time']);
$end_time = trim($_POST['end_time']);
$is_available = 1;

echo "<p>Saving: Day=$day, Start=$start_time, End=$end_time, Available=$is_available</p>";

// Check if exists
$checkQuery = "SELECT id FROM doctor_schedules WHERE doctor_id = ? AND day_of_week = ? LIMIT 1";
$checkStmt = $conn->prepare($checkQuery);
$checkStmt->bind_param('is', $doctor_id, $day);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
$recordExists = ($checkResult->num_rows > 0);
$checkStmt->close();

echo "<p>Record exists: " . ($recordExists ? 'YES' : 'NO') . "</p>";

if ($recordExists) {
    echo "<h3>Attempting UPDATE...</h3>";
    $updateQuery = "UPDATE doctor_schedules SET start_time = ?, end_time = ?, is_available = ? WHERE doctor_id = ? AND day_of_week = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param('sssii', $start_time, $end_time, $is_available, $doctor_id, $day);
    
    if ($updateStmt->execute()) {
        echo "<p style='color:green;'>✅ UPDATE successful! Affected rows: " . $updateStmt->affected_rows . "</p>";
    } else {
        echo "<p style='color:red;'>❌ UPDATE failed: " . $updateStmt->error . "</p>";
    }
    $updateStmt->close();
} else {
    echo "<h3>Attempting INSERT...</h3>";
    $insertQuery = "INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, is_available) VALUES (?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bind_param('isssi', $doctor_id, $day, $start_time, $end_time, $is_available);
    
    if ($insertStmt->execute()) {
        echo "<p style='color:green;'>✅ INSERT successful! New ID: " . $conn->insert_id . "</p>";
    } else {
        echo "<p style='color:red;'>❌ INSERT failed: " . $insertStmt->error . "</p>";
    }
    $insertStmt->close();
}

// Verify
echo "<h3>Verification - All records for Doctor ID $doctor_id:</h3>";
$result = $conn->query("SELECT * FROM doctor_schedules WHERE doctor_id = $doctor_id ORDER BY day_of_week");
echo "<p>Found: " . $result->num_rows . " records</p>";

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Day</th><th>Start</th><th>End</th><th>Available</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>" . $row['day_of_week'] . "</td><td>" . $row['start_time'] . "</td><td>" . $row['end_time'] . "</td><td>" . $row['is_available'] . "</td></tr>";
}
echo "</table>";
?>

<style>
body { font-family: Arial; margin: 20px; }
table { border-collapse: collapse; margin-top: 10px; }
th, td { padding: 10px; text-align: left; }
th { background: #1a7c4a; color: white; }
tr:nth-child(even) { background: #f0f0f0; }
</style>