<?php
include '../PHP/dbcon.php'; 
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');
$response = ['success' => false, 'error' => 'An unknown error occurred.', 'email_status' => ''];

ini_set('display_errors', 0); 
error_reporting(E_ALL);    

function generateAdminPassword($length = 12) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()_+-=[]{}|';
    $password = '';
    $characterCount = strlen($characters);
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, $characterCount - 1)];
    }
    return $password;
}

if (!isset($conn) || !$conn || (is_object($conn) && $conn->connect_error)) {
    $response['error'] = 'Database connection failed.';
    error_log('DB Connection Error in add_admin.php: ' . (is_object($conn) ? $conn->connect_error : "Conn not object or not set"));
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $position = $_POST['position'] ?? '';
    $email = $_POST['email'] ?? '';
    
    $username = $email; 
    $plain_text_password = generateAdminPassword(12);
    $hashed_password = password_hash($plain_text_password, PASSWORD_DEFAULT);

    if (empty($first_name) || empty($last_name) || empty($position) || empty($email)) {
        $response['error'] = 'First Name, Last Name, Position, and Email are required.';
        echo json_encode($response);
        if (is_object($conn)) $conn->close();
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['error'] = 'Invalid email format provided.';
        echo json_encode($response);
        if (is_object($conn)) $conn->close();
        exit;
    }

    $checkStmt = $conn->prepare("SELECT id FROM admins WHERE username = ? OR email = ?");
    if (!$checkStmt) {
        $response['error'] = "Prepare failed (check): " . $conn->error;
        error_log($response['error']);
        echo json_encode($response);
        if (is_object($conn)) $conn->close();
        exit;
    }
    $checkStmt->bind_param("ss", $username, $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        $response['error'] = 'Email (username) already exists.';
        $checkStmt->close();
        echo json_encode($response);
        if (is_object($conn)) $conn->close();
        exit;
    }
    $checkStmt->close();
    
    $default_status_id = 1; 
    $default_role_id = 1;   

    if ($conn->begin_transaction()) {
        try {
            $stmt_admins = $conn->prepare("INSERT INTO admins (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
            if (!$stmt_admins) throw new Exception("Prepare failed (admins): " . $conn->error);
            
            $stmt_admins->bind_param("sss", $username, $email, $hashed_password);
            if (!$stmt_admins->execute()) throw new Exception("Execute failed (admins): " . $stmt_admins->error);
            
            $admin_primary_id = $conn->insert_id;
            if (!$admin_primary_id) {
                throw new Exception("Failed to retrieve last insert ID for new admin.");
            }
            $stmt_admins->close();

            $stmt_admin_info = $conn->prepare("INSERT INTO admin_info_tbl (admin_id, firstname, middlename, lastname, Position, status_id, role_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            if (!$stmt_admin_info) throw new Exception("Prepare failed (admin_info): " . $conn->error);
            
            $stmt_admin_info->bind_param("issssii", $admin_primary_id, $first_name, $middle_name, $last_name, $position, $default_status_id, $default_role_id);
            if (!$stmt_admin_info->execute()) throw new Exception("Execute failed (admin_info): " . $stmt_admin_info->error);

            if ($stmt_admin_info->affected_rows <= 0) {
                 throw new Exception("No rows affected in admin_info_tbl.");
            }
            $stmt_admin_info->close();

            $conn->commit();
            $response['success'] = true;
            $response['message'] = 'Admin successfully added.'; 

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
                
                $emailBody = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Admin Account Created</title></head><body style="margin:0; padding:0; background-color:#f4f4f4; font-family: Arial, Helvetica, sans-serif;">';
                $emailBody .= '<div style="max-width:600px; margin:20px auto; background-color:#ffffff; border:1px solid #dddddd; border-radius:8px; overflow:hidden;">';
                $emailBody .= '<div style="background-color:#8a1c1c; color:#ffffff; padding:20px; text-align:center;">';
                $emailBody .= '<img src="https://insync.ojt-ims-bsit.net/assets/PUP_logo.png" alt="PUPT Logo" style="max-width:80px; margin-bottom:10px;">';
                $emailBody .= '<h1 style="margin:0; font-size:24px;">PUPT Tracker System</h1>';
                $emailBody .= '</div>';
                $emailBody .= '<div style="padding:20px 30px; color:#333333; line-height:1.6;">';
                $emailBody .= "<p>Hello " . htmlspecialchars($first_name) . ",</p>";
                $emailBody .= "<p>An administrator account has been created for you on the PUPT Tracker System.</p>";
                $emailBody .= "<p>Your login details are:<br>";
                $emailBody .= "Email / Username: <strong style=\"color:#555555;\">" . htmlspecialchars($email) . "</strong><br>";
                $emailBody .= "Temporary Password: <strong style=\"color:#555555;\">" . htmlspecialchars($plain_text_password) . "</strong></p>";
                $emailBody .= "<p>Please keep these credentials secure. It is recommended to change your password upon first login if the system allows for this.</p>";
                $emailBody .= "<p>Regards,<br>System Super Administration</p>";
                $emailBody .= '</div>';
                $emailBody .= '<div style="background-color:#f0f0f0; padding:15px 30px; text-align:center; font-size:12px; color:#777777;">';
                $emailBody .= '&copy; ' . date("Y") . ' PUPT Tracker System. All rights reserved.';
                $emailBody .= '</div>';
                $emailBody .= '</div></body></html>';

                $mail->Body = $emailBody;
                $mail->AltBody = "Hello " . htmlspecialchars($first_name) . ",\n\n" .
                                 "An administrator account has been created for you on the PUPT Tracker System.\n\n" .
                                 "Your login details are:\n" .
                                 "Email / Username: " . htmlspecialchars($email) . "\n" .
                                 "Temporary Password: " . htmlspecialchars($plain_text_password) . "\n\n" .
                                 "Please keep these credentials secure.\n\n" .
                                 "Regards,\nSystem Super Administration";
                $mail->send();
                $response['email_status'] = 'Admin account created and notification email sent.';
            } catch (Exception $e_mail) {
                error_log("Mailer Error for new admin {$email}: " . $mail->ErrorInfo);
                $response['email_status'] = 'Admin account created, but notification email could not be sent.';
            }

        } catch (Exception $e) {
            $conn->rollback();
            $response['error'] = "Transaction failed: " . $e->getMessage();
            error_log($response['error']);
        }
    } else {
        $response['error'] = "Failed to start database transaction: " . $conn->error;
        error_log($response['error']);
    }
} else {
    $response['error'] = 'Invalid request method. Only POST is accepted.';
}

echo json_encode($response);
if (is_object($conn) && property_exists($conn, 'connect_error') && !$conn->connect_error) { 
    $conn->close();
}
?>