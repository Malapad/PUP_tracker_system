<?php
session_start();
include '../PHP/dbcon.php';

$errors = [];
$security_number_display = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    if (isset($_POST['security_number'])) {
        $security_number_display = htmlspecialchars($_POST['security_number']);
    }

    $security_number_for_query = trim($_POST['security_number']);
    $password_posted = $_POST['password'];

    if (empty($security_number_for_query)) {
        $errors['security_number_input'] = "Security Number is required!";
    }
    if (empty($password_posted)) {
        $errors['password_input'] = "Password is required!";
    }

    if (empty($errors)) {
        if (!$conn) {
            $errors['db_error'] = "Database connection failed.";
        } else {
            $query = "SELECT s.id, s.security_number, s.password, si.firstname 
                      FROM security s 
                      LEFT JOIN security_info si ON s.id = si.security_id 
                      WHERE s.security_number = ?";
            
            if ($stmt = $conn->prepare($query)) {
                $stmt->bind_param("s", $security_number_for_query);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows == 1) {
                    $security_account = $result->fetch_assoc();
                    
                    if (password_verify($password_posted, $security_account['password'])) {
                        $_SESSION['security_id'] = $security_account['id'];
                        $_SESSION['security_number'] = $security_account['security_number'];
                        $_SESSION['security_first_name'] = $security_account['firstname'];
                        
                        $stmt->close();
                        $conn->close();
                        header("Location: ../security/security_dashboard.php");
                        exit;
                    } else {
                        $errors['login_error'] = "Incorrect password! Please try again.";
                    }
                } else {
                    $errors['login_error'] = "Security Number not found.";
                }
                $stmt->close();
            } else {
                $errors['db_error'] = "Database query preparation failed.";
            }
        }
    }
}
if ($conn) {
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PUP Security Login</title>
    <link rel="stylesheet" href="./security_login_style.css">
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <h2>Welcome PUPT Security!</h2>
            <p>Ensuring a safe and secure campus environment.</p>
        </div>
        <div class="right-panel">
            <img src="../assets/PUP_logo.png" alt="PUP Logo" class="logo">
            <h3>Security Account</h3>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <?php if (!empty($errors['login_error'])): ?>
                    <p class="error-message main-error"><?php echo $errors['login_error']; ?></p>
                <?php endif; ?>
                <?php if (!empty($errors['db_error'])): ?>
                    <p class="error-message main-error"><?php echo $errors['db_error']; ?></p>
                <?php endif; ?>
                
                <div class="input-group">
                    <input type="text" name="security_number" placeholder="Security Number" value="<?php echo htmlspecialchars($security_number_display); ?>" required>
                    <?php if (!empty($errors['security_number_input'])): ?>
                        <span class="error-message"><?php echo $errors['security_number_input']; ?></span>
                    <?php endif; ?>
                </div>
                <div class="input-group">
                    <input type="password" name="password" placeholder="Password" required>
                    <?php if (!empty($errors['password_input'])): ?>
                        <span class="error-message"><?php echo $errors['password_input']; ?></span>
                    <?php endif; ?>
                </div>
                <button type="submit" name="login" class="login-btn">Log In</button>
                <p class="signup-text" style="margin-top: 15px; text-align: center;">
                    For account concerns, please contact the administrator.
                </p>
            </form>
        </div>
    </div>
</body>
</html>
