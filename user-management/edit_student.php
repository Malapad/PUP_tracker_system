<?php
include '../PHP/dbcon.php';

header('Content-Type: application/json');

$response = ["success" => false, "error" => "An unknown error occurred."];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $required_fields = [
        'original_student_number',
        'student_number',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'course_id',
        'year_id',
        'section_id',
        'status_id'
    ];

    foreach ($required_fields as $field) {
        if ($field === 'middle_name' && isset($_POST[$field])) {
        } elseif (empty($_POST[$field])) {
            $response['error'] = "Missing or empty required field: $field";
            echo json_encode($response);
            mysqli_close($conn);
            exit;
        }
    }

    $original_student_number = mysqli_real_escape_string($conn, trim($_POST['original_student_number']));
    $student_number = mysqli_real_escape_string($conn, trim($_POST['student_number']));
    $first_name = mysqli_real_escape_string($conn, trim($_POST['first_name']));
    $middle_name = mysqli_real_escape_string($conn, trim($_POST['middle_name']));
    $last_name = mysqli_real_escape_string($conn, trim($_POST['last_name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));

    $course_id = (int)$_POST['course_id'];
    $year_id = (int)$_POST['year_id'];
    $section_id = (int)$_POST['section_id'];
    $status_id = (int)$_POST['status_id'];

    if ($course_id <= 0 || $year_id <= 0 || $section_id <= 0 || $status_id <= 0) {
        $response['error'] = "Invalid ID provided for course, year, section, or status. IDs must be positive integers.";
        echo json_encode($response);
        mysqli_close($conn);
        exit;
    }

    $updateQuery = "UPDATE users_tbl SET
                       student_number = ?,
                       first_name = ?,
                       middle_name = ?,
                       last_name = ?,
                       email = ?,
                       course_id = ?,
                       year_id = ?,
                       section_id = ?,
                       status_id = ?
                     WHERE student_number = ?";

    if ($stmt = $conn->prepare($updateQuery)) {
        $stmt->bind_param(
            "sssssiiiis",
            $student_number,
            $first_name,
            $middle_name,
            $last_name,
            $email,
            $course_id,
            $year_id,
            $section_id,
            $status_id,
            $original_student_number
        );

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $response["success"] = true;
                unset($response["error"]);
            } else {
                $response["success"] = true;
                $response["message"] = "Query executed, but no rows were updated. Data might be the same, or the original student number was not found.";
            }
        } else {
            $response["error"] = "Failed to execute student update: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $response["error"] = "Failed to prepare the update query: " . $conn->error;
    }
} else {
    $response["error"] = "Invalid request method.";
}

mysqli_close($conn);
echo json_encode($response);
?>