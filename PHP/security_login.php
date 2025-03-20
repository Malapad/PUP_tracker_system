<?php
session_start();
include '../PHP/dbcon.php';

$errors = []; 
$Security_Number = $Password = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $Security_Number = trim($_POST['Security_Number']);
    $Password = $_POST['Password'];

    if (empty($Security_Number)) {
        $errors['Security_Number'] = "Security number is required!";
    }

    if (empty($Password)) {
        $errors['Password'] = "Password is required!";
    }

    if (empty($errors)) {
        $query = "SELECT security_id, Firstname, Password FROM security_info_tbl WHERE Security_Number=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $Security_Number);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $security = $result->fetch_assoc();

            if (password_verify($Password, $security['Password'])) {
                $_SESSION['security_id'] = $security['security_id'];
                $_SESSION['security_name'] = $security['Firstname'];

                header("Location: ../HTML/security_homepage.html");
                exit;
            } else {
                $errors['Password'] = "Incorrect password! Please try again.";
            }
        } else {
            $errors['Security_Number'] = "Security number not found! Please sign up.";
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
    <title>PUP Security Login</title>
    <link rel="stylesheet" href="../CSS/security_login_style.css">
</head>
<body>

    <div class="container">
        <div class="left-panel">
            <h2>Welcome PUPTians!</h2>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
        </div>

        <div class="right-panel">
            <img src="../assets/PUP_logo.png" alt="PUP Logo" class="logo">
            <h3>Security Personnel Account</h3>

            <form action="" method="POST">
                <div class="input-group">
                    <input type="text" name="Security_Number" placeholder="Security Number" value="<?= htmlspecialchars($Security_Number) ?>" required>
                    <span class="error"><?= $errors['Security_Number'] ?? '' ?></span>
                </div>

                <div class="input-group">
                    <input type="password" name="Password" placeholder="Password" required>
                    <span class="error"><?= $errors['Password'] ?? '' ?></span>
                </div>

                <p class="signup-text">Don't have an account? <a href="../PHP/security_signup.php">Sign up here</a></p>

                <button type="submit" class="login-btn">Sign In</button>
            </form>
        </div>
    </div>

</body>
</html>
