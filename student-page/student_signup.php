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

    if ($password !== $confirmPassword) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Sign-Up</title>
    <link rel="stylesheet" href="./student_signup_style.css">
</head>
<body>

    <div class="signup-container">
        <div class="signup-box">
            <div class="head-container"> 
                <img src="../assets/PUP_logo.png" alt="Security Logo" class="logo"> 
                <h2>Create an Account</h2>
            </div>  
            
            <form action="student_signup.php" method="POST">
                <div class="row">
                    <input type="text" name="first_name" placeholder="First Name" required>
                    <input type="date" name="birthdate" required>
                </div>
                <div class="row">
                    <input type="text" name="middle_name" placeholder="Middle Name">
                    <input type="text" name="student_number" placeholder="Student Number" required>
                </div>
                <div class="row">
                    <input type="text" name="last_name" placeholder="Last Name" required>
                    <input type="password" name="password" placeholder="New Password" required>
                </div>
                <div class="row">
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                </div>

                <p class="login-text">Already have an account? <a href="/PHP/student_login.php">Log in</a></p>
                
                <button type="submit" class="signup-btn">Sign Up</button>
            </form>
        </div>
    </div>

</body>
</html>
