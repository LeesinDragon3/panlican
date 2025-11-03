<?php
require_once 'db_connect.php';
session_start();

$doctor_id = $_SESSION['user']['id'] ?? 0;

echo "<h2>Doctor Schedule Data - Doctor ID: $doctor_id</h2>";

$sql = "SELECT * FROM doctor_schedules WHERE doctor_id = $doctor_id";
$result = $conn->query($sql);

echo "<p>Total records: " . $result->num_rows . "</p>";

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Day</th><th>Start Time</th><th>End Time</th><th>Available</th><th>Created</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['day_of_week'] . "</td>";
    echo "<td>" . $row['start_time'] . "</td>";
    echo "<td>" . $row['end_time'] . "</td>";
    echo "<td>" . ($row['is_available'] ? 'Yes' : 'No') . "</td>";
    echo "<td>" . $row['created_at'] . "</td>";
    echo "</tr>";
}

echo "</table>";

// Also show the TestDay record
echo "<h3>All TestDay records:</h3>";
$result2 = $conn->query("SELECT * FROM doctor_schedules WHERE day_of_week = 'TestDay'");
echo "<p>TestDay records: " . $result2->num_rows . "</p>";
?>

<style>
body { font-family: Arial; margin: 20px; }
table { background: white; border-collapse: collapse; }
th { background: #1a7c4a; color: white; }
td { padding: 10px; }
tr:nth-child(even) { background: #f0f0f0; }
</style>