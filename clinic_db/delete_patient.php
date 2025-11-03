<?php
// =====================================
// delete_patient.php — Delete Patient
// =====================================

include 'db_connect.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "DELETE FROM patients WHERE id = $id";

    if (mysqli_query($conn, $query)) {
        echo "success";
    } else {
        echo "error: " . mysqli_error($conn);
    }
} else {
    echo "invalid";
}

mysqli_close($conn);
?>
