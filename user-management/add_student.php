<?php
include '../PHP/dbcon.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_number = mysqli_real_escape_string($conn, $_POST['student_number']);
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $middle_name = mysqli_real_escape_string($conn, $_POST['middle_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $course_id = mysqli_real_escape_string($conn, $_POST['course']);
    $year_id = mysqli_real_escape_string($conn, $_POST['year']);
    $section_id = mysqli_real_escape_string($conn, $_POST['section']);
    $status_id = mysqli_real_escape_string($conn, $_POST['status']);

    $password = 'default_password';
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    if (isset($student_number, $first_name, $last_name, $email, $course_id, $year_id, $section_id, $status_id)) {
        $query = "INSERT INTO users_tbl (student_number, first_name, middle_name, last_name, email, course_id, year_id, section_id, status_id, password_hash) 
                  VALUES ('$student_number', '$first_name', '$middle_name', '$last_name', '$email', '$course_id', '$year_id', '$section_id', '$status_id', '$password_hash')";

        if (mysqli_query($conn, $query)) {
            echo 'Student added successfully';
        } else {
            $error_message = mysqli_error($conn);
            echo 'Failed to add student. Error: ' . $error_message;
        }
    } else {
        echo 'Missing required fields';
    }
} else {
    echo 'Invalid request method';
}
?>
