<?php
session_start();
include 'dbcon.php';

$errors = []; 
$email = $password = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $password = $_POST['password'];

    if (empty($email)) {
        $errors['email'] = "Email is required!";
    }
    if (empty($password)) {
        $errors['password'] = "Password is required!";
    }

    if (empty($errors)) {
        $query = "SELECT Admin_id, Email, Password FROM admin_info_tbl WHERE Email='$email'";
        $result = mysqli_query($conn, $query);

        if (!$result) {
            die("Database Error: " . mysqli_error($conn));
        }

        if (mysqli_num_rows($result) == 1) {
            $admin = mysqli_fetch_assoc($result);

            if (password_verify($password, $admin['Password'])) {
                $_SESSION['admin_id'] = $admin['Admin_id'];
                $_SESSION['admin_email'] = $admin['Email'];

                header("Location: /HTML/admin_homepage.html");
                exit;
            } else {
                $errors['password'] = "Incorrect password! Please try again.";
            }
        } else {
            $errors['email'] = "Email address not found! Please sign up.";
        }
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
        <div class="left-panel">
            <h2>Welcome PUPTians!</h2>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
        </div>
        <div class="right-panel">
            <img src="/assets/PUP_logo.png" alt="PUP Logo" class="logo">
            <h3>Admin Account</h3>

            <form action="" method="POST">
                <div class="input-group">
                    <input type="email" name="email" placeholder="Email" 
                        value="<?= htmlspecialchars($email) ?>" required>
                    <span class="error"><?= $errors['email'] ?? '' ?></span>
                </div>

                <div class="input-group">
                    <input type="password" name="password" placeholder="Password" required>
                    <span class="error"><?= $errors['password'] ?? '' ?></span>
                </div>

                <p class="signup-text">Don’t have an account? <a href="admin_signup.php">Sign up here</a></p>
                <button type="submit" name="login" class="login-btn">Sign In</button>
            </form>
        </div>
    </div>
</body>
</html>
