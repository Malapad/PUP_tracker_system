<?php
require_once "../PHP/dbcon.php";
session_start();

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email_or_student_number = trim($_POST['email_or_student_number']);

    if (empty($email_or_student_number)) {
        $message = "Please enter your email address or student number.";
        $message_type = "error";
    } else {
        if (!$conn) {
            $message = "Database connection failed. Please try again later.";
            $message_type = "error";
        } else {
            if (filter_var($email_or_student_number, FILTER_VALIDATE_EMAIL)) {
                $sql = "SELECT user_id, first_name, email FROM users_tbl WHERE email = ?";
            } else {
                $sql = "SELECT user_id, first_name, email FROM users_tbl WHERE student_number = ?";
            }

            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("s", $email_or_student_number);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows == 1) {
                    $user = $result->fetch_assoc();
                    $user_email = $user['email'];
                    $user_id = $user['user_id'];
                    $user_first_name = $user['first_name'];

                    $token = bin2hex(random_bytes(50));
                    $token_hash = password_hash($token, PASSWORD_DEFAULT);
                    $expires_at = date("Y-m-d H:i:s", time() + 3600);

                    $update_sql = "UPDATE users_tbl SET reset_token_hash = ?, reset_token_expires_at = ? WHERE user_id = ?";
                    if ($update_stmt = $conn->prepare($update_sql)) {
                        $update_stmt->bind_param("ssi", $token_hash, $expires_at, $user_id);
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
                                $mail->addAddress($user_email, $user_first_name);

                                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                                $host = $_SERVER['HTTP_HOST'];
                                $path = dirname($_SERVER['PHP_SELF']);
                                $reset_link = "{$protocol}://{$host}{$path}/reset_password_form.php?token=" . $token;

                                $mail->isHTML(true);
                                $mail->Subject = 'Password Reset Request - PUPT Tracker System';
                                $mail->Body    = "Hello " . htmlspecialchars($user_first_name) . ",<br><br>" .
                                                 "You requested a password reset for your PUPT Tracker System account.<br>" .
                                                 "Please click the link below to reset your password:<br>" .
                                                 "<a href='" . $reset_link . "'>" . $reset_link . "</a><br><br>" .
                                                 "This link will expire in 1 hour.<br><br>" .
                                                 "If you did not request this, please ignore this email.<br><br>" .
                                                 "Regards,<br>" .
                                                 "PUPT Tracker System Administration";
                                $mail->AltBody = "Hello " . htmlspecialchars($user_first_name) . ",\n\n" .
                                                 "You requested a password reset for your PUPT Tracker System account.\n" .
                                                 "Please copy and paste the following link into your browser to reset your password:\n" .
                                                 $reset_link . "\n\n" .
                                                 "This link will expire in 1 hour.\n\n" .
                                                 "If you did not request this, please ignore this email.\n\n" .
                                                 "Regards,\n" .
                                                 "PUPT Tracker System Administration";
                                
                                $mail->send();
                                $message = "If an account is associated with " . htmlspecialchars($email_or_student_number) . ", a password reset link has been sent to the registered email address.";
                                $message_type = "success";

                            } catch (Exception $e) {
                                error_log("Mailer Error for password reset {$user_email}: " . $mail->ErrorInfo);
                                $message = "We found your account, but could not send the reset email. Please try again later or contact support.";
                                $message_type = "error";
                            }
                        } else {
                            $message = "Error updating your account for password reset. Please try again.";
                            $message_type = "error";
                        }
                        $update_stmt->close();
                    } else {
                        $message = "Database error preparing for password reset. Please try again.";
                        $message_type = "error";
                    }
                } else {
                    $message = "If an account is associated with " . htmlspecialchars($email_or_student_number) . ", a password reset link has been sent to the registered email address.";
                    $message_type = "success";
                }
                $stmt->close();
            } else {
                $message = "Database query failed. Please try again later.";
                $message_type = "error";
            }
            if ($conn) {
                $conn->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Password Reset - PUPT</title>
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
            <h2>Password Reset</h2>
            <p>Enter your student number or email address to receive a password reset link.</p>
        </div>
        <div class="right-panel">
            <img src="../assets/PUP_logo.png" alt="PUP Logo" class="logo">
            <h3>Reset Your Password</h3>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <?php if (!empty($message)): ?>
                    <p class="message <?php echo $message_type; ?>"><?php echo $message; ?></p>
                <?php endif; ?>

                <div class="input-group">
                    <input type="text" name="email_or_student_number" placeholder="Student Number or Email" required value="<?php echo isset($email_or_student_number) ? htmlspecialchars($email_or_student_number) : ''; ?>">
                </div>
                
                <button type="submit" class="login-btn">Send Reset Link</button>
                <p style="text-align: center; margin-top: 20px;">
                    <a href="student_login.php" style="color: #8a1c1c; text-decoration: none;">Back to Login</a>
                </p>
            </form>
        </div>
    </div>
</body>
</html>