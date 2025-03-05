<?php
session_start();
include '../PHP/dbcon.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $security_number = $_POST['security_number'];
    $password = $_POST['password'];

    // Check if security personnel exists
    $query = "SELECT * FROM security WHERE security_number=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $security_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $security = $result->fetch_assoc();

        if (password_verify($password, $security['password'])) {
            $_SESSION['security_id'] = $security['id'];
            $_SESSION['security_name'] = $security['firstname'];
            echo "<script>alert('Login successful!'); window.location.href='security_dashboard.php';</script>";
        } else {
            echo "<script>alert('Incorrect password!'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Security personnel not found!'); window.history.back();</script>";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Personnel Login</title>
    <link rel="stylesheet" href="/CSS/security_login.css">
</head>
<body>

    <div class="login-container">
        <div class="welcome-box">
            <h2>Welcome PUPTians!</h2>
        </div>

        <div class="login-box">
            <img src="/assets/PUP_logo.png" alt="PUP Logo">
            <h3>Security Personnel Login</h3>
            <form method="POST" action="security_login.php">
                <input type="text" name="security_number" placeholder="Security Number" required>
                <input type="password" name="password" placeholder="Password" required>
                <p class="signup-link">Don't have an account? <a href="/PHP/security_signup.php">Sign up here</a></p>
                <button type="submit">Sign In</button>
            </form>
        </div>
    </div>

</body>
</html>
