<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../PHP/dbcon.php';

header('Content-Type: application/json');

if (mysqli_connect_errno()) {
    echo json_encode(["success" => false, "error" => "Failed to connect to database: " . mysqli_connect_error()]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $student_number = $_POST["student_number"] ?? '';
    $first_name = $_POST["first_name"] ?? '';
    $middle_name = $_POST["middle_name"] ?? '';
    $last_name = $_POST["last_name"] ?? '';
    $email = $_POST["email"] ?? '';
    $course_name = $_POST["course"] ?? '';
    $year_name = $_POST["year"] ?? '';
    $section_name = $_POST["section"] ?? '';
    $gender_name = $_POST["gender"] ?? '';
    $status = "Active";

    if (empty($student_number) || empty($first_name) || empty($last_name) || empty($email) || empty($course_name) || empty($year_name) || empty($section_name) || empty($gender_name)) {
        echo json_encode(["success" => false, "error" => "Missing required fields"]);
        exit;
    }

    $course_id = getIdFromTable($conn, 'course_tbl', 'course_name', 'course_id', $course_name);
    $year_id = getIdFromTable($conn, 'year_tbl', 'year', 'year_id', $year_name);
    $section_id = getIdFromTable($conn, 'section_tbl', 'section_name', 'section_id', $section_name);
    $gender_id = getIdFromTable($conn, 'gender_tbl', 'gender_name', 'gender_id', $gender_name);
    $status_id = getIdFromTable($conn, 'status_tbl', 'status_name', 'status_id', $status);

    if (!$course_id || !$year_id || !$section_id || !$gender_id || !$status_id) {
        echo json_encode([
            "success" => false, 
            "error" => "Invalid data provided",
            "debug" => [
                "course_name" => $course_name,
                "course_id" => $course_id,
                "year_name" => $year_name,
                "year_id" => $year_id,
                "section_name" => $section_name,
                "section_id" => $section_id,
                "gender_name" => $gender_name,
                "gender_id" => $gender_id,
                "status_name" => $status,
                "status_id" => $status_id
            ]
        ]);
        exit;
    }

    $query = "INSERT INTO users_tbl (student_number, last_name, first_name, middle_name, email, course_id, year_id, section, gender, status_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        echo json_encode(["success" => false, "error" => "Error preparing query: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("ssssssssss", $student_number, $last_name, $first_name, $middle_name, $email, $course_id, $year_id, $section_id, $gender_id, $status_id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }

    $stmt->close();
}

function getIdFromTable($conn, $table, $columnName, $idName, $value) {
    $query = "SELECT $idName FROM $table WHERE $columnName = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $value);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row[$idName] ?? null;
}
?>
