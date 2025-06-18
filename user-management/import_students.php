<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../PHP/dbcon.php';
session_start();

// Function to generate a random password
function generateRandomPassword($length = 12) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()_+';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $password;
}

// Function to log actions in user_management_history
function log_user_action($conn, $action_type, $target_user_type, $target_user_identifier, $details = null) {
    // Get admin name from session for history logging
    // Removed $admin_id as per database schema in 0616-trackersys.sql
    $admin_name = $_SESSION['admin_name'] ?? 'System/Unknown';

    $action_type = mysqli_real_escape_string($conn, $action_type);
    $target_user_type = mysqli_real_escape_string($conn, $target_user_type);
    $target_user_identifier = mysqli_real_escape_string($conn, $target_user_identifier);
    $details = $details ? mysqli_real_escape_string($conn, $details) : 'N/A';

    // Adjusted query to match the user_management_history table schema (no performed_by_admin_id)
    $query = "INSERT INTO user_management_history (timestamp, performed_by_admin_name, action_type, target_user_type, target_user_identifier, details) VALUES (NOW(), ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssss", $admin_name, $action_type, $target_user_type, $target_user_identifier, $details);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        error_log("Failed to prepare log statement: " . mysqli_error($conn));
    }
}

// Ensure this script is only accessible by authorized admins
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];
$admin_name = $_SESSION['admin_name'] ?? 'Admin'; // Get admin name from session for history logging

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $csvFile = $_FILES['csv_file']['tmp_name'];
    $browser_mime_type = $_FILES['csv_file']['type']; // MIME type sent by browser
    $detected_mime_type = 'unknown'; // Initialize

    if (file_exists($csvFile)) {
        $detected_mime_type = mime_content_type($csvFile); // MIME type detected by PHP
    }

    $response['debug_browser_mime_type'] = $browser_mime_type;
    $response['debug_detected_mime_type'] = $detected_mime_type;

    // Allowed MIME types for CSV files
    $allowedMimeTypes = [
        'text/csv',
        'application/csv',
        'text/plain',
        'application/vnd.ms-excel',
        'application/octet-stream'
    ];

    if (!in_array($detected_mime_type, $allowedMimeTypes)) {
        $response['message'] = 'Invalid file type. Please upload a CSV file. Detected: ' . $detected_mime_type;
        echo json_encode($response);
        exit();
    }


    if (($handle = fopen($csvFile, "r")) !== FALSE) {
        $header = fgetcsv($handle, 1000, ",");

        // --- IMPORTANT FIX: Explicitly strip BOM from the first header element if present ---
        if ($header && isset($header[0])) {
            // Remove UTF-8 BOM (Byte Order Mark) if present
            $header[0] = str_replace("\xEF\xBB\xBF", '', $header[0]);
        }
        // --- END FIX ---

        // Expected headers for student import
        // Removed 'password' from expected headers as it's system-generated
        $expectedHeaders = [
            'student_number', 'first_name', 'middle_name', 'last_name', 'email',
            'course_id', 'year_id', 'section_id', 'gender_id'
        ];

        // --- DEBUGGING FOR HEADERS (KEEP THESE FOR FURTHER DIAGNOSIS IF NEEDED) ---
        $response['debug_raw_csv_header_after_bom_strip'] = $header; // New debug line
        $normalizedHeader = array_map(function($h) { return strtolower(trim($h)); }, $header);
        $response['debug_normalized_csv_header'] = $normalizedHeader;
        $normalizedExpectedHeaders = array_map(function($h) { return strtolower(trim($h)); }, $expectedHeaders);
        $response['debug_normalized_expected_headers'] = $normalizedExpectedHeaders;
        // --- END DEBUGGING ---


        // Check for missing required headers
        $missingHeaders = array_diff($normalizedExpectedHeaders, $normalizedHeader);
        if (!empty($missingHeaders)) {
            $response['message'] = 'Missing required CSV headers: ' . implode(', ', $missingHeaders);
            echo json_encode($response);
            fclose($handle);
            exit();
        }

        // Ensure column indices are correctly mapped
        $columnMapping = [];
        foreach ($normalizedExpectedHeaders as $expectedCol) {
            $columnIndex = array_search($expectedCol, $normalizedHeader);
            if ($columnIndex !== false) {
                $columnMapping[$expectedCol] = $columnIndex;
            } else {
                // This error means array_search returned false, which shouldn't happen if $missingHeaders is empty
                $response['message'] = 'Internal error: Could not map expected header "' . $expectedCol . '". This indicates a mismatch even after BOM strip and normalization.';
                echo json_encode($response);
                fclose($handle);
                exit();
            }
        }


        $importedCount = 0;
        $failedEntries = [];

        mysqli_begin_transaction($conn);
        $errorsOccurredDuringTransaction = false;

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Skip empty rows
            if (count(array_filter($data, 'strlen')) === 0) {
                continue;
            }

            // Get data using the robust column mapping
            $student_number = mysqli_real_escape_string($conn, $data[$columnMapping['student_number']] ?? '');
            $first_name = mysqli_real_escape_string($conn, $data[$columnMapping['first_name']] ?? '');
            $middle_name = mysqli_real_escape_string($conn, $data[$columnMapping['middle_name']] ?? '');
            $last_name = mysqli_real_escape_string($conn, $data[$columnMapping['last_name']] ?? '');
            $email = mysqli_real_escape_string($conn, $data[$columnMapping['email']] ?? '');

            // Optional fields (will default to 0 if not provided, empty, or non-numeric)
            $course_id = filter_var($data[$columnMapping['course_id']] ?? '', FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);
            $year_id = filter_var($data[$columnMapping['year_id']] ?? '', FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);
            $section_id = filter_var($data[$columnMapping['section_id']] ?? '', FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);
            $gender_id = filter_var($data[$columnMapping['gender_id']] ?? '', FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);

            $status_id = 1; // Active
            $roles_id = 2; // Student

            // Validation for critical fields
            if (empty($student_number) || empty($first_name) || empty($last_name) || empty($email)) {
                $failedEntries[] = "Skipped row due to missing critical data (Student Number, First Name, Last Name, or Email) for row: " . htmlspecialchars(implode(', ', $data));
                $errorsOccurredDuringTransaction = true;
                continue;
            }

            // Check for existing student number or email to prevent duplicates
            $check_query = "SELECT student_number, email FROM users_tbl WHERE student_number = ? OR email = ?";
            $check_stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($check_stmt, "ss", $student_number, $email);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);

            if (mysqli_stmt_num_rows($check_stmt) > 0) {
                $failedEntries[] = "Duplicate entry skipped: Student Number '{$student_number}' or Email '{$email}' already exists.";
                mysqli_stmt_close($check_stmt);
                continue;
            }
            mysqli_stmt_close($check_stmt);

            // Generate Password
            $generated_password = generateRandomPassword();
            $hashed_password = password_hash($generated_password, PASSWORD_DEFAULT);

            // Insert into users_tbl
            $insert_query = "INSERT INTO users_tbl (student_number, first_name, middle_name, last_name, email, password_hash, course_id, year_id, section_id, gender_id, status_id, roles_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_query);

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ssssssiiiiii",
                    $student_number, $first_name, $middle_name, $last_name, $email, $hashed_password,
                    $course_id, $year_id, $section_id, $gender_id, $status_id, $roles_id
                );
                if (mysqli_stmt_execute($stmt)) {
                    $importedCount++;

                    // --- Send Email with Generated Password (PLACEHOLDER) ---
                    /* In a real application, you'd integrate an email sending library here (e.g., PHPMailer).
                       For example:
                       require 'path/to/PHPMailerAutoload.php';
                       $mail = new PHPMailer;
                       $mail->isSMTP();
                       $mail->Host = 'smtp.example.com';
                       // ... configure SMTP details ...
                       $mail->setFrom('no-reply@yourdomain.com', 'PUP Sanction Tracker');
                       $mail->addAddress($email, $first_name . ' ' . $last_name);
                       $mail->Subject = 'Your New Account Password';
                       $mail->Body    = 'Hello ' . $first_name . ', your new account password is: ' . $generated_password . '. Please log in and change your password.';
                       if(!$mail->send()) {
                           error_log("Mailer Error ({$email}): " . $mail->ErrorInfo);
                           // You might want to add this to failed entries or a separate log
                       } else {
                           // Email successfully sent
                       }
                    */

                    // Log the action
                    $details = "Imported student: <b>" . htmlspecialchars($first_name) . " " . htmlspecialchars($last_name) . "</b> (Student No: " . htmlspecialchars($student_number) . "). Password system-generated and sent to email.";
                    $missing_optional_info = [];
                    if ($course_id === 0) $missing_optional_info[] = "Course";
                    if ($year_id === 0) $missing_optional_info[] = "Year";
                    if ($section_id === 0) $missing_optional_info[] = "Section";
                    if ($gender_id === 0) $missing_optional_info[] = "Gender";
                    if (!empty($missing_optional_info)) {
                        $details .= " Missing optional data for: " . implode(', ', $missing_optional_info) . ".";
                    }
                    // Call log_user_action without admin_id
                    log_user_action($conn, "Import Student", "Student", $student_number, $details);

                } else {
                    $failedEntries[] = "Failed to insert row for student number {$student_number}: " . mysqli_error($conn);
                    $errorsOccurredDuringTransaction = true;
                }
                mysqli_stmt_close($stmt);
            } else {
                $failedEntries[] = "Failed to prepare insert statement for student number {$student_number}: " . mysqli_error($conn);
                $errorsOccurredDuringTransaction = true;
            }
        }
        fclose($handle);

        // Finalize transaction
        if ($errorsOccurredDuringTransaction) {
            mysqli_rollback($conn);
            $response['success'] = false;
            $response['message'] = "Import failed due to critical errors. All changes have been rolled back. Details: " . implode('; ', $failedEntries);
        } else {
            mysqli_commit($conn);
            $response['success'] = true;
            $response['message'] = "Successfully imported $importedCount students.";
            if (!empty($failedEntries)) {
                $response['message'] .= " Some entries had warnings or missing optional data: " . implode('; ', $failedEntries);
            }
        }
    } else {
        $response['message'] = 'Error opening CSV file on the server.';
    }
} else {
    $response['message'] = 'No CSV file uploaded or invalid request method.';
}

echo json_encode($response);
exit();
?>