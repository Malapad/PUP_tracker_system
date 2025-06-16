<?php
include '../PHP/dbcon.php'; // Your database connection file

header('Content-Type: application/json'); // Set header for JSON response

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];

    // Basic file validation
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'File upload error: ' . $file['error'];
        echo json_encode($response);
        exit;
    }
    if ($file['type'] !== 'text/csv' && $file['type'] !== 'application/vnd.ms-excel') {
        $response['message'] = 'Invalid file type. Please upload a CSV file.';
        echo json_encode($response);
        exit;
    }
    if ($file['size'] == 0) {
        $response['message'] = 'Uploaded file is empty.';
        echo json_encode($response);
        exit;
    }

    $filePath = $file['tmp_name'];
    $importedCount = 0;
    $skippedCount = 0;
    $errors = [];

    // Fetch the 'Active' status_id once
    $activeStatusId = null;
    $statusQuery = mysqli_query($conn, "SELECT status_id FROM status_tbl WHERE status_name = 'Active'");
    if ($statusQuery && mysqli_num_rows($statusQuery) > 0) {
        $row = mysqli_fetch_assoc($statusQuery);
        $activeStatusId = $row['status_id'];
    } else {
        $response['message'] = 'Error: "Active" status not found in status_tbl. Please ensure it exists.';
        echo json_encode($response);
        exit;
    }

    // Open the CSV file
    if (($handle = fopen($filePath, "r")) !== FALSE) {
        // Get headers from the first row
        $headers = fgetcsv($handle);
        if ($headers === FALSE) {
            $response['message'] = 'Could not read CSV headers.';
            echo json_encode($response);
            fclose($handle);
            exit;
        }

        // Trim whitespace and convert headers to lowercase for flexible matching
        $headers = array_map('trim', $headers);
        $headerMap = array_flip(array_map('strtolower', $headers));

        // Define required columns and their corresponding database fields
        // Note: 'status name' is still listed for user instruction but its value will be overridden
        $requiredColumns = [
            'student number' => 'student_number',
            'first name' => 'first_name',
            'last name' => 'last_name',
            'email' => 'email',
            'course name' => 'course_id', // Will be mapped to ID
            'year' => 'year_id',         // Will be mapped to ID
            'section name' => 'section_id', // Will be mapped to ID
            // 'status name' => 'status_id',   // No longer directly used from CSV
            'gender name' => 'gender_id'    // Will be mapped to ID
        ];

        // Check if all *essential* required headers are present (excluding status name since it's auto-set)
        $essentialHeaders = array_keys($requiredColumns);
        foreach ($essentialHeaders as $col) {
            if (!isset($headerMap[$col])) {
                $response['message'] = "Missing required column in CSV: '{$col}'. Please ensure your CSV has all essential headers.";
                echo json_encode($response);
                fclose($handle);
                exit;
            }
        }

        // Fetch lookup data once for efficiency
        $courses = [];
        $courseResult = mysqli_query($conn, "SELECT course_id, course_name FROM course_tbl");
        while ($row = mysqli_fetch_assoc($courseResult)) { $courses[strtolower($row['course_name'])] = $row['course_id']; }

        $years = [];
        $yearResult = mysqli_query($conn, "SELECT year_id, year FROM year_tbl");
        while ($row = mysqli_fetch_assoc($yearResult)) { $years[strtolower($row['year'])] = $row['year_id']; }

        $sections = [];
        $sectionResult = mysqli_query($conn, "SELECT section_id, section_name FROM section_tbl");
        while ($row = mysqli_fetch_assoc($sectionResult)) { $sections[strtolower($row['section_name'])] = $row['section_id']; }

        $genders = [];
        $genderResult = mysqli_query($conn, "SELECT gender_id, gender_name FROM gender_tbl");
        while ($row = mysqli_fetch_assoc($genderResult)) { $genders[strtolower($row['gender_name'])] = $row['gender_id']; }

        // Start transaction for atomicity
        mysqli_begin_transaction($conn);

        $rowNum = 1; // Start from 1 for header row, then increment for data rows
        while (($rowData = fgetcsv($handle)) !== FALSE) {
            $rowNum++;
            // Skip empty rows
            if (empty(array_filter($rowData))) {
                $skippedCount++;
                continue;
            }

            if (count($rowData) !== count($headers)) {
                $errors[] = "Row $rowNum skipped: Column count mismatch. Expected " . count($headers) . " but got " . count($rowData) . ".";
                $skippedCount++;
                continue;
            }

            $studentData = [];
            foreach ($requiredColumns as $csvCol => $dbCol) {
                // Skip processing 'status name' from CSV as it's now fixed
                // The `status_id` will be set after this loop.
                if ($csvCol === 'status name') continue;

                $index = $headerMap[$csvCol];
                $value = trim($rowData[$index]);

                // Perform lookup for IDs
                switch ($dbCol) {
                    case 'course_id':
                        $studentData[$dbCol] = $courses[strtolower($value)] ?? null;
                        if (is_null($studentData[$dbCol])) $errors[] = "Row $rowNum: Invalid or missing Course Name '{$value}'.";
                        break;
                    case 'year_id':
                        $studentData[$dbCol] = $years[strtolower($value)] ?? null;
                        if (is_null($studentData[$dbCol])) $errors[] = "Row $rowNum: Invalid or missing Year '{$value}'.";
                        break;
                    case 'section_id':
                        $studentData[$dbCol] = $sections[strtolower($value)] ?? null;
                        if (is_null($studentData[$dbCol])) $errors[] = "Row $rowNum: Invalid or missing Section Name '{$value}'.";
                        break;
                    case 'gender_id':
                        $studentData[$dbCol] = $genders[strtolower($value)] ?? null;
                        if (is_null($studentData[$dbCol])) $errors[] = "Row $rowNum: Invalid or missing Gender Name '{$value}'.";
                        break;
                    default:
                        $studentData[$dbCol] = $value;
                        break;
                }
            }

            // Set status_id to 'Active' (1) automatically
            $studentData['status_id'] = $activeStatusId;

            // Get optional middle name
            $middleNameIndex = isset($headerMap['middle name']) ? $headerMap['middle name'] : null;
            $studentData['middle_name'] = ($middleNameIndex !== null && isset($rowData[$middleNameIndex])) ? trim($rowData[$middleNameIndex]) : null;

            // Generate a default password hash (e.g., "password123" hashed)
            $defaultPassword = password_hash("password123", PASSWORD_DEFAULT);

            // Check for critical missing data or lookup failures (excluding status_id now)
            if (is_null($studentData['course_id']) || is_null($studentData['year_id']) || is_null($studentData['section_id']) || is_null($studentData['gender_id']) || empty($studentData['student_number']) || empty($studentData['first_name']) || empty($studentData['last_name']) || empty($studentData['email'])) {
                $errors[] = "Row $rowNum skipped: Critical data missing or invalid lookup value. Student Number: '{$studentData['student_number']}', Email: '{$studentData['email']}'.";
                $skippedCount++;
                continue;
            }

            // Check if student_number or email already exists to prevent duplicates
            $checkStmt = $conn->prepare("SELECT student_number, email FROM users_tbl WHERE student_number = ? OR email = ?");
            $checkStmt->bind_param("ss", $studentData['student_number'], $studentData['email']);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            if ($checkResult->num_rows > 0) {
                $existing = $checkResult->fetch_assoc();
                if ($existing['student_number'] === $studentData['student_number']) {
                    $errors[] = "Row $rowNum skipped: Student Number '{$studentData['student_number']}' already exists.";
                }
                if ($existing['email'] === $studentData['email']) {
                    $errors[] = "Row $rowNum skipped: Email '{$studentData['email']}' already exists.";
                }
                $skippedCount++;
                $checkStmt->close();
                continue;
            }
            $checkStmt->close();

            // Insert into users_tbl
            $stmt = $conn->prepare("INSERT INTO users_tbl (student_number, first_name, middle_name, last_name, email, course_id, year_id, section_id, gender_id, password_hash, status_id, roles_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 2)"); // roles_id 2 for Student
            $stmt->bind_param("sssssiiiisi",
                $studentData['student_number'],
                $studentData['first_name'],
                $studentData['middle_name'],
                $studentData['last_name'],
                $studentData['email'],
                $studentData['course_id'],
                $studentData['year_id'],
                $studentData['section_id'],
                $studentData['gender_id'],
                $defaultPassword,
                $studentData['status_id'] // This is now always the 'Active' ID
            );

            if ($stmt->execute()) {
                $importedCount++;
            } else {
                $errors[] = "Row $rowNum failed to import (Database error): " . $stmt->error . " for Student Number: '{$studentData['student_number']}'.";
                $skippedCount++;
            }
            $stmt->close();
        }
        fclose($handle);

        if (empty($errors)) {
            mysqli_commit($conn);
            $response['success'] = true;
            $response['message'] = "Successfully imported $importedCount students.";
        } else {
            mysqli_rollback($conn); // Rollback if any errors occurred
            $response['message'] = "Import completed with errors. Imported: $importedCount, Skipped: $skippedCount. Errors: " . implode('; ', $errors);
        }

    } else {
        $response['message'] = 'Could not open the uploaded CSV file.';
    }
} else {
    $response['message'] = 'Invalid request. No file uploaded or incorrect method.';
}

echo json_encode($response);
mysqli_close($conn);
?>