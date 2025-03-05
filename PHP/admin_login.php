<?php
session_start();
include 'dbcon.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    // Get email and password input
    $email = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $password = $_POST['password'];

    // Debug: Check email before query
    echo "<script>console.log('Email entered: $email');</script>";

    // Check if email exists in the database
    $query = "SELECT Admin_id, Email, Password FROM admin_info_tbl WHERE Email='$email'";
    $result = mysqli_query($conn, $query);

    if (!$result) {
        die("<script>alert('Database Error: " . mysqli_error($conn) . "');</script>");
    }

    if (mysqli_num_rows($result) == 1) {
        $admin = mysqli_fetch_assoc($result);

        // Debug: Check hashed password
        echo "<script>console.log('Hashed password from DB: " . addslashes($admin['Password']) . "');</script>";

        // Verify the password
        if (password_verify($password, $admin['Password'])) {
            $_SESSION['admin_id'] = $admin['Admin_id'];
            $_SESSION['admin_email'] = $admin['Email'];
            echo "<script>alert('Login successful!'); window.location.href='admin_dashboard.php';</script>";
        } else {
            echo "<script>alert('Incorrect password!');</script>";
        }
    } else {
        echo "<script>alert('No admin found with this email!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PUP Admin Login</title>
    <link rel="stylesheet" href="/CSS/admin_login_style.css">
</head>
<body>
    <div class="container">
        <div class="welcome-section">
            <h1>Welcome PUPTians!</h1>
        </div>
        <div class="login-section">
            <img src="/assets/PUP_logo.png" alt="PUP Logo" class="logo">
            <h2>Admin Account</h2>
            
            <form action="" method="POST">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <p>Don’t have an account? <a href="admin_signup.php">Sign up here</a></p>
                <button type="submit" name="login">Sign In</button>
            </form>
        </div>
    </div>
</body>
</html>
