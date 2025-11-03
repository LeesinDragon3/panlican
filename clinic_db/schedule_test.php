<?php
require_once 'db_connect.php';
session_start();

echo "<h2>Doctor Schedule Debug Test</h2>";

// Test 1: Check connection
echo "<h3>1. Database Connection:</h3>";
if ($conn) {
    echo "<p style='color:green;'>✅ Connected</p>";
} else {
    echo "<p style='color:red;'>❌ Connection failed</p>";
    exit;
}

// Test 2: Check table exists
echo "<h3>2. Check doctor_schedules table:</h3>";
$result = $conn->query("SHOW TABLES LIKE 'doctor_schedules'");
if ($result->num_rows > 0) {
    echo "<p style='color:green;'>✅ Table exists</p>";
} else {
    echo "<p style='color:red;'>❌ Table does not exist</p>";
}

// Test 3: Show table structure
echo "<h3>3. Table Columns:</h3>";
$result = $conn->query("SHOW COLUMNS FROM doctor_schedules");
echo "<pre>";
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
echo "</pre>";

// Test 4: Check doctor data
echo "<h3>4. Check Session Doctor ID:</h3>";
$doctor_id = $_SESSION['user']['id'] ?? 'NOT SET';
echo "<p>Doctor ID: <strong>$doctor_id</strong></p>";

// Test 5: Try a test INSERT
if ($doctor_id && $doctor_id !== 'NOT SET') {
    echo "<h3>5. Test INSERT:</h3>";
    $test_sql = "INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, is_available) VALUES ($doctor_id, 'TestDay', '09:00', '17:00', 1)";
    if ($conn->query($test_sql)) {
        echo "<p style='color:green;'>✅ INSERT works</p>";
        echo "<p>Affected rows: " . $conn->affected_rows . "</p>";
    } else {
        echo "<p style='color:red;'>❌ INSERT failed: " . $conn->error . "</p>";
    }
}

// Test 6: Count existing records
echo "<h3>6. Existing Records:</h3>";
$result = $conn->query("SELECT * FROM doctor_schedules LIMIT 5");
echo "<p>Found: " . $result->num_rows . " records</p>";
?>

<style>
body { font-family: Arial; margin: 20px; }
h2 { color: #333; }
h3 { color: #666; margin-top: 20px; }
pre { background: #f0f0f0; padding: 10px; border-radius: 5px; }
</style>