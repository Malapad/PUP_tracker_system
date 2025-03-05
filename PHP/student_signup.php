<?php
require_once "../PHP/dbcon.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName = trim($_POST['first_name']);
    $middleName = trim($_POST['middle_name']);
    $lastName = trim($_POST['last_name']);
    $birthdate = $_POST['birthdate'];
    $studentNumber = trim($_POST['student_number']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Check if passwords match
    if ($password !== $confirmPassword) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Check if student number or email already exists
    $checkQuery = "SELECT * FROM student_info_tbl WHERE Stud_number = ? OR Email = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ss", $studentNumber, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Student Number or Email already exists!'); window.history.back();</script>";
    } else {
        $sql = "INSERT INTO student_info_tbl (Firstname, Middlename, Lastname, Birthday, Stud_number, Email, Password) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssssss", $firstName, $middleName, $lastName, $birthdate, $studentNumber, $email, $hashedPassword);
            
            if ($stmt->execute()) {
                echo "<script>alert('Signup Successful!'); window.location.href='student_login.php';</script>";
            } else {
                echo "<script>alert('Error: " . addslashes($stmt->error) . "');</script>";
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>
