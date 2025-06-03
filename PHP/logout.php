<?php
session_start();
session_destroy();

if (isset($_GET['role'])) {
    if ($_GET['role'] == 'admin') {
        header("Location: admin_login.php");
    } elseif ($_GET['role'] == 'student') {
        header("Location: ../student-page/student_login.php");
    } elseif ($_GET['role'] == 'security') {
        header("Location: security_login.php");
    } else {
        header("Location: ../index.html");
    }
} else {
    header("Location: ../index.html");
}
exit();
?>