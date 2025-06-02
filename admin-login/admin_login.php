<?php
session_start();
include '../PHP/dbcon.php';

$errors = [];
$email_display = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    if (isset($_POST['email'])) {
        $email_display = htmlspecialchars($_POST['email']);
    }

    $email_for_query = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $password_posted = $_POST['password'];

    if (empty($email_for_query)) {
        $errors['email_input'] = "Email is required!";
    }
    if (empty($password_posted)) {
        $errors['password_input'] = "Password is required!";
    }

    if (empty($errors['email_input']) && empty($errors['password_input'])) {
        $query = "SELECT id, email, password FROM pup_trackersys.admins WHERE email='$email_for_query'";
        $result = mysqli_query($conn, $query);

        if (!$result) {
            die("Database Query Error: " . mysqli_error($conn));
        }

        if (mysqli_num_rows($result) == 1) {
            $admin_account = mysqli_fetch_assoc($result);

            if (password_verify($password_posted, $admin_account['password'])) {
                $_SESSION['admin_id'] = $admin_account['id'];
                $_SESSION['admin_email'] = $admin_account['email'];

                header("Location: /HTML/admin_homepage.html");
                exit;
            } else {
                $errors['login_error'] = "Incorrect email or password! Please try again.";
            }
        } else {
            $errors['login_error'] = "Incorrect email or password! Please try again.";
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
    <link rel="stylesheet" href="./admin_login_style.css"> <style>
        .main-login-error {
            color: red;
            text-align: center;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .error {
            color: red;
            font-size: 0.9em;
            display: block;
            margin-top: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <h2>Welcome PUPTians!</h2>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
        </div>
        <div class="right-panel">
            <img src="/assets/PUP_logo.png" alt="PUP Logo" class="logo"> <h3>Admin Account</h3>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <?php if (!empty($errors['login_error'])): ?>
                    <p class="main-login-error"><?php echo $errors['login_error']; ?></p>
                <?php endif; ?>

                <div class="input-group">
                    <input type="email" name="email" placeholder="Email"
                           value="<?= $email_display ?>" required>
                    <?php if (!empty($errors['email_input'])): ?>
                        <span class="error"><?= $errors['email_input'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="input-group">
                    <input type="password" name="password" placeholder="Password" required>
                    <?php if (!empty($errors['password_input'])): ?>
                        <span class="error"><?= $errors['password_input'] ?></span>
                    <?php endif; ?>
                </div>

                <p class="forget"><a>Forget password</a></p>
                <button type="submit" name="login" class="login-btn">Log in</button>
                <p class="signup-text" style="margin-top: 15px; text-align: center;">
                    Admin credentials are provided. Contact the super administrator for account concerns.
                </p>
            </form>
        </div>
    </div>
</body>
</html>