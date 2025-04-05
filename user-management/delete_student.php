<?php
include '../PHP/dbcon.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['student_number'])) {
    $student_number = $_POST['student_number'];
    $deleteQuery = "DELETE FROM users_tbl WHERE student_number = '$student_number'";
    mysqli_query($conn, $deleteQuery);
}

header("Location: user_management.php");
exit();
