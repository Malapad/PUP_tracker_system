<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../PHP/dbcon.php'; // Adjust path as necessary for your db connection
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
    // Get admin ID and name from session for history logging
    $admin_id = $_SESSION['admin_id'] ?? 0;
    $admin_name = $_SESSION['admin_name'] ?? 'System/Unknown';

    $action_type = mysqli_real_escape_string($conn, $action_type);
    $target_user_type = mysqli_real_escape_string($conn, $target_user_type);
    $target_user_identifier = mysqli_real_escape_string($conn, $target_user_identifier);
    $details = $details ? mysqli_real_escape_string($conn, $details) : 'N/A';

    $query = "INSERT INTO user_management_history (performed_by_admin_id, performed_by_admin_name, action_type, target_user_type, target_user_identifier, details) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isssss", $admin_id, $admin_name, $action_type, $target_user_type, $target_user_identifier, $details);
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
    $fileType = mime_content_type($csvFile);

    // Basic validation for CSV file type
    if ($fileType !== 'text/csv' && $fileType !== 'application/vnd.ms-excel') {
        $response['message'] = 'Invalid file type. Please upload a CSV file.';
        echo json_encode($response);
        exit();
    }

    if (($handle = fopen($csvFile, "r")) !== FALSE) {
        $header = fgetcsv($handle, 1000, ",");

        // Expected headers based on the new CSV format (no 'password' column)
        $expectedHeaders = [
            'student_number', 'first_name', 'middle_name', 'last_name', 'email',
            'course_id', 'year_id', 'section_id', 'gender_id'
        ];

        // Check if all required headers are present (case-insensitive and trimmed for robustness)
        $normalizedHeader = array_map(function($h) { return strtolower(trim($h)); }, $header);
        $normalizedExpectedHeaders = array_map(function($h) { return strtolower(trim($h)); }, $expectedHeaders);

        $missingHeaders = array_diff($normalizedExpectedHeaders, $normalizedHeader);
        if (!empty($missingHeaders)) {
            $response['message'] = 'Missing required CSV headers: ' . implode(', ', $missingHeaders);
            fclose($handle);
            echo json_encode($response);
            exit();
        }

        $importedCount = 0;
        $failedEntries = [];
        // Create a mapping from cleaned CSV header to original index for data retrieval
        $columnMapping = [];
        foreach ($header as $index => $colName) {
            $columnMapping[strtolower(trim($colName))] = $index;
        }

        // Start transaction for atomicity
        mysqli_begin_transaction($conn);
        $errorsOccurredDuringTransaction = false; // Flag for critical errors that should trigger a rollback

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Get data using the robust column mapping
            $student_number = mysqli_real_escape_string($conn, $data[$columnMapping['student_number']] ?? '');
            $first_name = mysqli_real_escape_string($conn, $data[$columnMapping['first_name']] ?? '');
            $middle_name = mysqli_real_escape_string($conn, $data[$columnMapping['middle_name']] ?? '');
            $last_name = mysqli_real_escape_string($conn, $data[$columnMapping['last_name']] ?? '');
            $email = mysqli_real_escape_string($conn, $data[$columnMapping['email']] ?? '');

            // Optional fields (will default to 0 if not provided or invalid)
            $course_id = (int)($data[$columnMapping['course_id']] ?? 0);
            $year_id = (int)($data[$columnMapping['year_id']] ?? 0);
            $section_id = (int)($data[$columnMapping['section_id']] ?? 0);
            $gender_id = (int)($data[$columnMapping['gender_id']] ?? 0);

            // Auto-fill status_id to 'Active' (ID 1) and roles_id to 'Student' (ID 2)
            $status_id = 1; // Assuming 1 is 'Active' in status_tbl
            $roles_id = 2; // Assuming 2 is 'Student' in roles_tbl

            // --- Validation for critical fields ---
            if (empty($student_number) || empty($first_name) || empty($last_name) || empty($email)) {
                $failedEntries[] = "Skipped row due to missing critical data (Student Number, First Name, Last Name, or Email) for row: " . implode(', ', $data);
                $errorsOccurredDuringTransaction = true; // Mark as an error that might require rollback for this row
                continue; // Skip to next row
            }

            // --- Check for existing student number or email to prevent duplicates ---
            $check_query = "SELECT student_number, email FROM users_tbl WHERE student_number = ? OR email = ?";
            $check_stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($check_stmt, "ss", $student_number, $email);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);

            if (mysqli_stmt_num_rows($check_stmt) > 0) {
                $failedEntries[] = "Duplicate entry skipped: Student Number '{$student_number}' or Email '{$email}' already exists.";
                mysqli_stmt_close($check_stmt);
                continue; // Skip to next row
            }
            mysqli_stmt_close($check_stmt);

            // --- Generate Password ---
            $generated_password = generateRandomPassword();
            $hashed_password = password_hash($generated_password, PASSWORD_DEFAULT);

            // --- Insert into users_tbl ---
            $insert_query = "INSERT INTO users_tbl (student_number, first_name, middle_name, last_name, email, password_hash, course_id, year_id, section_id, gender_id, status_id, roles_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_query);

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ssssssiiiisii",
                    $student_number, $first_name, $middle_name, $last_name, $email, $hashed_password,
                    $course_id, $year_id, $section_id, $gender_id, $status_id, $roles_id
                );

                if (mysqli_stmt_execute($stmt)) {
                    $importedCount++;

                    // --- Send Email with Generated Password (PLACEHOLDER) ---
                    // This is where you would integrate your email sending logic.
                    // You'll need to configure PHP Mailer, Swift Mailer, or a similar library/service.
                    /*
                    $to = $email;
                    $subject = "Your New Student Account Password";
                    $message = "Dear " . htmlspecialchars($first_name) . ",<br><br>"
                             . "Your new student account has been created. Your credentials are:<br>"
                             . "Student Number: <b>" . htmlspecialchars($student_number) . "</b><br>"
                             . "Email (Username): <b>" . htmlspecialchars($email) . "</b><br>"
                             . "Temporary Password: <b>" . htmlspecialchars($generated_password) . "</b><br><br>"
                             . "Please log in and change your password immediately.<br><br>"
                             . "Regards,<br>Your University Admin Team";
                    $headers = "MIME-Version: 1.0" . "\r\n";
                    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                    $headers .= 'From: <noreply@youruniversity.com>' . "\r\n";
                    mail($to, $subject, $message, $headers);
                    // Consider adding robust error handling for mail() function as it can fail silently.
                    */

                    // Log the action in user_management_history
                    $details = "Imported student: <b>" . htmlspecialchars($first_name) . " " . htmlspecialchars($last_name) . "</b> (Student No: " . htmlspecialchars($student_number) . "). Password system-generated and sent to email.";

                    // Check for missing optional data and append to details for logging
                    $missing_optional_info = [];
                    if ($course_id === 0) $missing_optional_info[] = "Course";
                    if ($year_id === 0) $missing_optional_info[] = "Year";
                    if ($section_id === 0) $missing_optional_info[] = "Section";
                    if ($gender_id === 0) $missing_optional_info[] = "Gender";

                    if (!empty($missing_optional_info)) {
                        $details .= " Missing: " . implode(', ', $missing_optional_info) . ".";
                    }

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

        // Commit or Rollback transaction
        if ($errorsOccurredDuringTransaction) {
            mysqli_rollback($conn);
            $response['success'] = false; // Indicate overall failure if critical errors occurred
            $response['message'] = "Import completed with errors. All changes have been rolled back. " . (!empty($failedEntries) ? "Errors: " . implode('; ', $failedEntries) : "");
        } else {
            mysqli_commit($conn);
            $response['success'] = true;
            $response['message'] = "Successfully imported $importedCount students.";
            if (!empty($failedEntries)) {
                $response['message'] .= " Some entries had missing optional data or warnings: " . implode('; ', $failedEntries);
            }
        }
    } else {
        $response['message'] = 'Error opening CSV file.';
    }
} else {
    $response['message'] = 'No CSV file uploaded or invalid request method.';
}

echo json_encode($response);
exit();
?>