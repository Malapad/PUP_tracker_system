<?php
include '../PHP/dbcon.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
$response = ['success' => false, 'error' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_number = isset($_POST['student_number']) ? mysqli_real_escape_string($conn, trim($_POST['student_number'])) : '';
    $first_name = isset($_POST['first_name']) ? mysqli_real_escape_string($conn, trim($_POST['first_name'])) : '';
    $middle_name = isset($_POST['middle_name']) ? mysqli_real_escape_string($conn, trim($_POST['middle_name'])) : '';
    $last_name = isset($_POST['last_name']) ? mysqli_real_escape_string($conn, trim($_POST['last_name'])) : '';
    $email = isset($_POST['email']) ? mysqli_real_escape_string($conn, trim($_POST['email'])) : '';
    $course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
    $year_id = isset($_POST['year_id']) ? (int)$_POST['year_id'] : 0;
    $section_id = isset($_POST['section_id']) ? (int)$_POST['section_id'] : 0;
    $status_id = isset($_POST['status_id']) ? (int)$_POST['status_id'] : 0;

    $password = 'default_password';
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    if (!empty($student_number) && !empty($first_name) && !empty($last_name) && !empty($email) && $course_id > 0 && $year_id > 0 && $section_id > 0 && $status_id > 0) {
        
        $checkQuery = "SELECT student_number FROM users_tbl WHERE student_number = ?";
        $stmt_check = $conn->prepare($checkQuery);
        $stmt_check->bind_param("s", $student_number);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $response['error'] = 'Student number already exists.';
            $stmt_check->close();
        } else {
            $stmt_check->close();
            $query = "INSERT INTO users_tbl (student_number, first_name, middle_name, last_name, email, course_id, year_id, section_id, status_id, password_hash) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt_insert = $conn->prepare($query);
            if ($stmt_insert) {
                $stmt_insert->bind_param("sssssiiiis", $student_number, $first_name, $middle_name, $last_name, $email, $course_id, $year_id, $section_id, $status_id, $password_hash);
                if ($stmt_insert->execute()) {
                    $response['success'] = true;
                    unset($response['error']);
                } else {
                    $response['error'] = 'Failed to add student: ' . $stmt_insert->error;
                }
                $stmt_insert->close();
            } else {
                 $response['error'] = 'Failed to prepare insert statement: ' . $conn->error;
            }
        }
    } else {
        $response['error'] = 'Missing or invalid required fields. Please check all inputs.';
    }
} else {
    $response['error'] = 'Invalid request method.';
}

mysqli_close($conn);
echo json_encode($response);
?>