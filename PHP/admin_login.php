<?php
session_start();
include 'dbcon.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $password = $_POST['password'];

    $query = "SELECT Admin_id, Email, Password FROM admin_info_tbl WHERE Email='$email'";
    $result = mysqli_query($conn, $query);

    if (!$result) {
        echo "<script>alert('Database Error: " . mysqli_error($conn) . "');</script>";
        exit;
    }

    if (mysqli_num_rows($result) == 1) {
        $admin = mysqli_fetch_assoc($result);

        // Verify the password
        if (password_verify($password, $admin['Password'])) {
            $_SESSION['admin_id'] = $admin['Admin_id'];
            $_SESSION['admin_email'] = $admin['Email'];
            
            echo "<script>window.location.href='/HTML/admin_homepage.html';</script>";
            exit;
        } else {
            echo "<script>alert('Incorrect email or password!');</script>";
        }
    } else {
        echo "<script>alert('Incorrect email or password!');</script>";
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
