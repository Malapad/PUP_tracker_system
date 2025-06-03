<?php
require_once '../PHP/dbcon.php';
session_start();

if (!isset($_SESSION["current_user_id"]) || !isset($_SESSION["user_student_number"])) {
    header("Location: ./student_login.php");
    exit();
}

$student_stud_number_from_session = $_SESSION["user_student_number"];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($conn)) {
        $sql_mark_all_read = "UPDATE notifications_tbl SET is_read = TRUE
                                WHERE student_number = ? AND is_read = FALSE";
        if ($stmt_mark_all = $conn->prepare($sql_mark_all_read)) {
            $stmt_mark_all->bind_param("s", $student_stud_number_from_session);
            if ($stmt_mark_all->execute()) {
                $affected_rows = $stmt_mark_all->affected_rows;
            } else {
                error_log("Error executing mark all read: " . $stmt_mark_all->error);
            }
            $stmt_mark_all->close();
        } else {
            error_log("Error preparing mark all read query: " . $conn->error);
        }
        $conn->close();
    } else {
        error_log("DB connection error in mark_all_notifications_read.php");
    }
} else {
    // $_SESSION['notification_error_feedback'] = "Invalid request method.";
}

header("Location: ./all_notifications.php");
exit();
?>