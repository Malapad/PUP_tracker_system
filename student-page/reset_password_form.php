<?php
require_once "../PHP/dbcon.php";
session_start();

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$token_valid = false;
$message = "";
$message_type = "";
$user_id_to_reset = null;
$user_email_for_notification = null;
$user_first_name_for_notification = null;

if (isset($_GET['token'])) {
    $received_token = $_GET['token'];

    if (!$conn) {
        $message = "Database connection failed.";
        $message_type = "error";
    } else {
        $sql_find_token = "SELECT user_id, email, first_name, reset_token_hash, reset_token_expires_at FROM users_tbl WHERE reset_token_hash IS NOT NULL AND reset_token_expires_at > NOW()";
        $result_tokens = $conn->query($sql_find_token);

        if ($result_tokens && $result_tokens->num_rows > 0) {
            while ($user_row = $result_tokens->fetch_assoc()) {
                if (password_verify($received_token, $user_row['reset_token_hash'])) {
                    $token_valid = true;
                    $user_id_to_reset = $user_row['user_id'];
                    $user_email_for_notification = $user_row['email'];
                    $user_first_name_for_notification = $user_row['first_name'];
                    break; 
                }
            }
        }
        
        if (!$token_valid && empty($message)) {
            $message = "Invalid or expired password reset link. Please request a new one.";
            $message_type = "error";
        }
    }
} else {
    $message = "No reset token provided. Please use the link from your email.";
    $message_type = "error";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $token_valid && isset($_POST['password'], $_POST['confirm_password'])) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($password) || empty($confirm_password)) {
        $message = "Both password fields are required.";
        $message_type = "error";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $message_type = "error";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters long.";
        $message_type = "error";
    } else {
        if (!$conn) {
             $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
             if ($conn->connect_error) {
                $message = "Database connection failed. Cannot reset password.";
                $message_type = "error";
             }
        }
        
        if ($conn && empty($message)) {
            $new_password_hash = password_hash($password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users_tbl SET password_hash = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE user_id = ?";
            
            if ($update_stmt = $conn->prepare($update_sql)) {
                $update_stmt->bind_param("si", $new_password_hash, $user_id_to_reset);
                if ($update_stmt->execute()) {
                    $mail = new PHPMailer(true);
                    try {
                        $mail->SMTPDebug = SMTP::DEBUG_OFF;
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'pupinsync@gmail.com';
                        $mail->Password   = 'rnjrnircjdbuqhqm';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;

                        $mail->setFrom('pupinsync@gmail.com', 'PUPT Tracker System');
                        $mail->addAddress($user_email_for_notification, $user_first_name_for_notification);

                        $mail->isHTML(true);
                        $mail->Subject = 'Your Password Has Been Changed - PUPT Tracker System';
                        $mail->Body    = "Hello " . htmlspecialchars($user_first_name_for_notification) . ",<br><br>" .
                                         "This email confirms that the password for your PUPT Tracker System account has been successfully changed.<br><br>" .
                                         "If you did not make this change, please contact support immediately.<br><br>" .
                                         "Regards,<br>" .
                                         "PUPT Tracker System Administration";
                        $mail->AltBody = "Hello " . htmlspecialchars($user_first_name_for_notification) . ",\n\n" .
                                         "This email confirms that the password for your PUPT Tracker System account has been successfully changed.\n\n" .
                                         "If you did not make this change, please contact support immediately.\n\n" .
                                         "Regards,\n" .
                                         "PUPT Tracker System Administration";
                        $mail->send();
                    } catch (Exception $e) {
                        error_log("Mailer Error for password change confirmation {$user_email_for_notification}: " . $mail->ErrorInfo);
                    }

                    $_SESSION['password_reset_success'] = "Your password has been successfully reset! You can now log in with your new password.";
                    if ($conn) $conn->close();
                    header("Location: student_login.php");
                    exit();

                } else {
                    $message = "Error resetting password. Please try again.";
                    $message_type = "error";
                }
                $update_stmt->close();
            } else {
                $message = "Database error preparing to reset password. Please try again.";
                $message_type = "error";
            }
        }
    }
}

if ($conn && ($_SERVER["REQUEST_METHOD"] != "POST" || !empty($message)) ) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password - PUPT</title>
    <link rel="stylesheet" href="./student_login_style.css">
    <style>
        .right-panel form p.message {
            padding: 10px; margin-bottom: 15px; border-radius: 5px; text-align: center;
        }
        .right-panel form p.message.success {
            background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;
        }
        .right-panel form p.message.error {
            background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <h2>Set New Password</h2>
            <?php if ($token_valid && $_SERVER["REQUEST_METHOD"] != "POST"):?>
            <p>Please enter your new password below.</p>
            <?php elseif (!$token_valid && !isset($_POST['password'])):?>
            <p>Follow the instructions sent to your email or request a new reset link.</p>
            <?php endif; ?>
        </div>
        <div class="right-panel">
            <img src="../assets/PUP_logo.png" alt="PUP Logo" class="logo">
            <h3>Create New Password</h3>
            
            <?php if (!empty($message)): ?>
                <p class="message <?php echo $message_type; ?>"><?php echo $message; ?></p>
            <?php endif; ?>

            <?php if ($token_valid): ?>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?token=<?php echo htmlspecialchars(isset($_GET['token']) ? $_GET['token'] : ''); ?>" method="POST">
                <div class="input-group">
                    <input type="password" name="password" placeholder="New Password" required>
                </div>
                <div class="input-group">
                    <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
                </div>
                <button type="submit" class="login-btn">Reset Password</button>
            </form>
            <?php endif; ?>
            <p style="text-align: center; margin-top: 20px;">
                <a href="student_login.php" style="color: #8a1c1c; text-decoration: none;">Back to Login</a>
            </p>
        </div>
    </div>
</body>
</html>