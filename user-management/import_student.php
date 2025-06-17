<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../PHP/dbcon.php'; // Adjust path as necessary for your db connection
session_start();

// Ensure this script is only accessible by authorized admins
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$response = ['success' => false, 'message' => ''];
$admin_name = $_SESSION['admin_name'] ?? 'Admin'; // Get admin name from session for history logging

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $csvFile = $_FILES['csv_file']['tmp_name'];
    $fileType = mime_content_type($csvFile);

    // Basic validation for CSV file type
    if ($fileType !== 'text/csv' && $fileType !== 'application/vnd.ms-excel') { // Common MIME types for CSV
        $response['message'] = 'Invalid file type. Please upload a CSV file.';
        echo json_encode($response);
        exit();
    }

    if (($handle = fopen($csvFile, "r")) !== FALSE) {
        $header = fgetcsv($handle, 1000, ","); // Read the header row

        // Expected headers based on the CSV format provided earlier
        $expectedHeaders = [
            'student_number', 'first_name', 'middle_name', 'last_name', 'email',
            'course_id', 'year_id', 'section_id', 'status_id', 'password', 'gender_id', 'roles_id'
        ];

        // Check if all required headers are present
        $missingHeaders = array_diff($expectedHeaders, $header);
        if (!empty($missingHeaders)) {
            $response['message'] = 'Missing required CSV headers: ' . implode(', ', $missingHeaders);
            fclose($handle);
            echo json_encode($response);
            exit();
        }

        $importedCount = 0;
        $failedEntries = [];

        // Map CSV headers to database column names (adjust if your CSV headers differ from DB columns)
        $columnMapping = array_flip($header);

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Create an associative array for easier access using column names
            $rowData = [];
            foreach ($columnMapping as $csvHeader => $index) {
                $rowData[$csvHeader] = $data[$index];
            }

            $student_number = mysqli_real_escape_string($conn, $rowData['student_number'] ?? '');
            $first_name = mysqli_real_escape_string($conn, $rowData['first_name'] ?? '');
            $middle_name = mysqli_real_escape_string($conn, $rowData['middle_name'] ?? '');
            $last_name = mysqli_real_escape_string($conn, $rowData['last_name'] ?? '');
            $email = mysqli_real_escape_string($conn, $rowData['email'] ?? '');
            $course_id = (int)($rowData['course_id'] ?? 0);
            $year_id = (int)($rowData['year_id'] ?? 0);
            $section_id = (int)($rowData['section_id'] ?? 0);
            $gender_id = (int)($rowData['gender_id'] ?? 0);
            $password = $rowData['password'] ?? '';

            // Auto-fill status_id to 'Active' (ID 1) and roles_id to 'Student' (ID 2)
            $status_id = 1; // Assuming 1 is 'Active' in status_tbl
            $roles_id = 2; // Assuming 2 is 'Student' in roles_tbl

            // Validate required fields
            if (empty($student_number) || empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
                $failedEntries[] = "Missing required data for student: " . implode(', ', $rowData);
                continue;
            }

            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Check for existing student number or email to prevent duplicates
            $check_query = "SELECT student_number, email FROM users_tbl WHERE student_number = '$student_number' OR email = '$email'";
            $check_result = mysqli_query($conn, $check_query);

            if (mysqli_num_rows($check_result) > 0) {
                $existing_user = mysqli_fetch_assoc($check_result);
                $failedEntries[] = "Duplicate entry: Student Number '{$existing_user['student_number']}' or Email '{$existing_user['email']}' already exists.";
                continue;
            }

            // Insert into users_tbl
            $insert_query = "INSERT INTO users_tbl (student_number, first_name, middle_name, last_name, email, course_id, year_id, section_id, gender_id, password_hash, status_id, roles_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($stmt, "sssssiiiisii", $student_number, $first_name, $middle_name, $last_name, $email, $course_id, $year_id, $section_id, $gender_id, $hashed_password, $status_id, $roles_id);

            if (mysqli_stmt_execute($stmt)) {
                $importedCount++;
                // Log the action in user_management_history
                $details = "Imported student: <b>" . htmlspecialchars($first_name) . " " . htmlspecialchars($last_name) . "</b> (Student No: " . htmlspecialchars($student_number) . ")";
                $log_query = "INSERT INTO user_management_history (performed_by_admin_name, action_type, target_user_type, target_user_identifier, details) VALUES (?, ?, ?, ?, ?)";
                $log_stmt = mysqli_prepare($conn, $log_query);
                $action_type = "Import Student";
                $target_user_type = "Student";
                $target_user_identifier = $student_number;
                mysqli_stmt_bind_param($log_stmt, "sssss", $admin_name, $action_type, $target_user_type, $target_user_identifier, $details);
                mysqli_stmt_execute($log_stmt);
                mysqli_stmt_close($log_stmt);

            } else {
                $failedEntries[] = "Failed to insert row for student number {$student_number}: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
        fclose($handle);

        if ($importedCount > 0) {
            $response['success'] = true;
            $response['message'] = "Successfully imported $importedCount students.";
            if (!empty($failedEntries)) {
                $response['message'] .= " Some entries failed: " . implode('; ', $failedEntries);
                $response['success'] = false; // Indicate partial success/errors
            }
        } else {
            $response['message'] = "No students were imported. " . (!empty($failedEntries) ? "Errors: " . implode('; ', $failedEntries) : "Please check your CSV file and ensure it contains valid data and headers.");
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