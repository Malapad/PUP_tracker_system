<?php
require_once "../PHP/dbcon.php";
session_start();

$errors = [];
$studentNumber = $password = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $studentNumber = trim($_POST['student_number']);
    $password = $_POST['password'];

    if (empty($studentNumber)) {
        $errors['student_number'] = "Student number is required!";
    }

    if (empty($password)) {
        $errors['password'] = "Password is required!";
    }

    if (empty($errors)) {
        if (!$conn) {
            die("Database connection failed: " . mysqli_connect_error());
        }

        $sql = "SELECT user_id, first_name, student_number, password_hash FROM users_tbl WHERE student_number = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $studentNumber);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user["password_hash"])) {
                    $_SESSION["current_user_id"] = $user["user_id"];
                    $_SESSION["user_first_name"] = $user["first_name"];
                    $_SESSION["user_student_number"] = $user["student_number"];
                    header("Location: ./student_dashboard.php");
                    exit();
                } else {
                    $errors['login_error'] = "Incorrect student number or password! Please try again.";
                }
            } else {
                $errors['login_error'] = "Incorrect student number or password! Please try again.";
            }
            $stmt->close();
        } else {
            $errors['db_error'] = "Database query failed. Please try again later.";
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PUP Student Login</title>
    <link rel="stylesheet" href="./student_login_style.css">
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <h2>Welcome PUPTians!</h2>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
        </div>
        <div class="right-panel">
            <img src="../assets/PUP_logo.png" alt="PUP Logo" class="logo">
            <h3>Student Account</h3>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <?php if (!empty($errors['login_error'])): ?>
                    <p class="error-message main-error"><?php echo $errors['login_error']; ?></p>
                <?php endif; ?>
                <?php if (!empty($errors['db_error'])): ?>
                    <p class="error-message main-error"><?php echo $errors['db_error']; ?></p>
                <?php endif; ?>

                <div class="input-group">
                    <input type="text" name="student_number" placeholder="Student Number" value="<?php echo htmlspecialchars($studentNumber); ?>" required>
                    <?php if (!empty($errors['student_number'])): ?>
                        <span class="error-message"><?php echo $errors['student_number']; ?></span>
                    <?php endif; ?>
                </div>

                <div class="input-group">
                    <input type="password" name="password" placeholder="Password" required>
                    <?php if (!empty($errors['password'])): ?>
                        <span class="error-message"><?php echo $errors['password']; ?></span>
                    <?php endif; ?>
                </div>

                <!--<p class="forget"><a>Forget password? Contact Admin.</a></p>-->
                
                <button type="submit" class="login-btn">Log in</button>

                <!--<p class="signup-text">Credentials are provided by the admin. Please contact admin for account concerns.</p>-->
            </form>
        </div>
    </div>
</body>
</html>