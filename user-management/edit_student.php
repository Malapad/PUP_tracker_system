<?php
include '../PHP/dbcon.php';

$response = ["success" => false];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $required_fields = ['student_number', 'first_name', 'middle_name', 'last_name', 'email', 'course_id', 'year_id', 'section_id'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $response['error'] = "Missing required field: $field";
            echo json_encode($response);
            exit;
        }
    }

    $student_number = mysqli_real_escape_string($conn, $_POST['student_number']);
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $middle_name = mysqli_real_escape_string($conn, $_POST['middle_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $course_id = mysqli_real_escape_string($conn, $_POST['course_id']);
    $year_id = mysqli_real_escape_string($conn, $_POST['year_id']);
    $section_id = mysqli_real_escape_string($conn, $_POST['section_id']);

    $updateQuery = "UPDATE users_tbl SET
        first_name = ?,
        middle_name = ?,
        last_name = ?,
        email = ?,
        course_id = ?,
        year_id = ?,
        section_id = ?
        WHERE student_number = ?";

    if ($stmt = $conn->prepare($updateQuery)) {
        $stmt->bind_param("ssssiiis", $first_name, $middle_name, $last_name, $email, $course_id, $year_id, $section_id, $student_number);

        if ($stmt->execute()) {
            $response["success"] = true;
        } else {
            $response["error"] = "Failed to update student: " . $stmt->error;
        }

        $stmt->close();
    } else {
        $response["error"] = "Failed to prepare the update query: " . $conn->error;
    }
}

echo json_encode($response);
?>
