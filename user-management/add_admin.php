<?php
ob_start();
session_start();
require '../PHP/dbcon.php';
require_once './history_logger.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$response = ['success' => false, 'error' => 'An unknown error occurred.'];

function generateAdminPassword($length = 12) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()_+-=[]{}|';
    $password = '';
    $characterCount = strlen($characters);
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, $characterCount - 1)];
    }
    return $password;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (empty($first_name) || empty($last_name) || empty($position) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['error'] = 'Required fields are missing or email is invalid.';
    } else {
        $username = $email; 
        $checkStmt = $conn->prepare("SELECT id FROM admins WHERE username = ? OR email = ?");
        $checkStmt->bind_param("ss", $username, $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $response['error'] = 'Email (username) already exists.';
        } else {
            if ($conn->begin_transaction()) {
                try {
                    $plain_text_password = generateAdminPassword(12);
                    $hashed_password = password_hash($plain_text_password, PASSWORD_DEFAULT);
                    $default_status_id = 1; 
                    $default_role_id = 1;

                    $stmt_admins = $conn->prepare("INSERT INTO admins (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt_admins->bind_param("sss", $username, $email, $hashed_password);
                    $stmt_admins->execute();
                    $admin_primary_id = $conn->insert_id;
                    $stmt_admins->close();
                    if (!$admin_primary_id) throw new Exception("Failed to create admin in primary table.");

                    $stmt_admin_info = $conn->prepare("INSERT INTO admin_info_tbl (admin_id, firstname, middlename, lastname, Position, status_id, role_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt_admin_info->bind_param("issssii", $admin_primary_id, $first_name, $middle_name, $last_name, $position, $default_status_id, $default_role_id);
                    $stmt_admin_info->execute();
                    $stmt_admin_info->close();

                    $conn->commit();
                    $response['success'] = true;
                    $response['message'] = 'Admin added successfully.'; 
                    log_user_action($conn, 'Add Admin', 'Admin', $email, 'Created new admin: ' . $first_name . ' ' . $last_name . '.');
                    
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
                        $mail->setFrom('pupinsync@gmail.com', 'PUPT Tracker System Admin');
                        $mail->addAddress($email, $first_name . ' ' . $last_name);
                        $mail->isHTML(true);
                        $mail->Subject = 'Your Admin Account for PUPT Tracker System';
                        $emailBody = '<!DOCTYPE html><html><body>An admin account has been created for you. Your username is ' . htmlspecialchars($email) . ' and your temporary password is ' . htmlspecialchars($plain_text_password) . '</body></html>';
                        $mail->Body = $emailBody;
                        $mail->send();
                        $response['email_status'] = 'Admin account created and notification email sent.';
                    } catch (Exception $e_mail) {
                        error_log("Mailer Error for new admin {$email}: " . $mail->ErrorInfo);
                        $response['email_status'] = 'Admin account created, but notification email could not be sent.';
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    $response['error'] = "Transaction failed: " . $e->getMessage();
                }
            } else {
                $response['error'] = "Failed to start transaction.";
            }
        }
        $checkStmt->close();
    }
} else {
    $response['error'] = 'Invalid request method.';
}

if (is_object($conn)) $conn->close();
ob_end_clean();
header('Content-Type: application/json');
echo json_encode($response);
?>