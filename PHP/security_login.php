<?php
session_start();
include '../PHP/dbcon.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $security_number = trim($_POST['Security_Number']);
    $password = $_POST['Password'];

    $query = "SELECT security_id, Firstname, Password FROM security_info_tbl WHERE Security_Number=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $security_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $security = $result->fetch_assoc();

        if (password_verify($password, $security['Password'])) {
            $_SESSION['security_id'] = $security['security_id'];
            $_SESSION['security_name'] = $security['Firstname'];

            echo "<script>window.location.href='/HTML/security_homepage.html';</script>";
            exit;
        } else {
            echo "<script>alert('Incorrect security number or password!'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Incorrect security number or password!'); window.history.back();</script>";
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
                <input type="text" name="Security_Number" placeholder="Security Number" required> <!-- Match DB column -->
                <input type="password" name="Password" placeholder="Password" required> <!-- Match DB column -->
                <p class="signup-link">Don't have an account? <a href="/PHP/security_signup.php">Sign up here</a></p>
                <button type="submit">Sign In</button>
            </form>
        </div>
    </div>

</body>
</html>
