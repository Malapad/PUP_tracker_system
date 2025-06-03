<?php
include '../PHP/dbcon.php';

header('Content-Type: application/json');
$response = ['success' => false, 'error' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['student_number'])) {
    $student_number = trim($_POST['student_number']);

    if (empty($student_number)) {
        $response['error'] = 'Student number is required.';
    } else {
        $deleteQuery = "DELETE FROM users_tbl WHERE student_number = ?";
        $stmt = $conn->prepare($deleteQuery);
        if ($stmt) {
            $stmt->bind_param("s", $student_number);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $response['success'] = true;
                    unset($response['error']);
                } else {
                    $response['error'] = 'Student not found or no changes made.';
                }
            } else {
                $response['error'] = 'Failed to delete student: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $response['error'] = 'Failed to prepare delete statement: ' . $conn->error;
        }
    }
} else {
    $response['error'] = 'Invalid request method or missing student number.';
}

mysqli_close($conn);
echo json_encode($response);
?>