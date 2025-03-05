<?php
require_once "../PHP/dbcon.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = trim($_POST['firstname']);
    $middlename = trim($_POST['middlename']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if passwords match
    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit();
    }

    // Check if email already exists
    $checkEmail = "SELECT * FROM security_info_tbl WHERE Email = ?";
    if ($stmt = $conn->prepare($checkEmail)) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo "<script>alert('Email already registered!'); window.history.back();</script>";
            exit();
        }
        $stmt->close();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO security_info_tbl (Firstname, Middlename, Lastname, Email) VALUES (?, ?, ?, ?)";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssss", $firstname, $middlename, $lastname, $email);
        if ($stmt->execute()) {
            echo "<script>alert('Registration Successful!'); window.location.href='../HTML/security_login.html';</script>";
        } else {
            echo "<script>alert('Something went wrong. Try again!'); window.history.back();</script>";
        }
        $stmt->close();
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Personnel Sign Up</title>
    <link rel="stylesheet" href="/CSS/security_signup_style.css">
</head>
<body>
    <div class="container">
        <div class="signup-box">
            <img src="/assets/PUP_logo.png" alt="Security Logo" class="logo"> 
            <h2>Create an Account</h2>
            <form action="security_signup.php" method="POST">
                <div class="input-group">
                    <input type="text" name="firstname" placeholder="First Name" required>
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                <div class="input-group">
                    <input type="text" name="middlename" placeholder="Middle Name">
                    <input type="password" name="password" placeholder="New Password" required>
                </div>
                <div class="input-group">
                    <input type="text" name="lastname" placeholder="Last Name" required>
                    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                </div>
                <p><a href="security_login.php">Already have an account?</a></p>
                <button type="submit" class="btn">Sign Up</button>
            </form>
        </div>
    </div>
</body>
</html>
