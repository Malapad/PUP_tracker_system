<?php
@include '../PHP/dbcon.php'; 

header('Content-Type: application/json');
$response = ['success' => false, 'error' => 'An unknown error occurred.'];

if (!isset($conn) || !$conn || (is_object($conn) && $conn->connect_error)) {
    $response['error'] = 'Database connection failed. Please check server logs and the dbcon.php file.';
    if (isset($conn) && is_object($conn) && $conn->connect_error) {
        $response['error'] .= ' Specific DB Error: ' . $conn->connect_error;
    }
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $position = $_POST['position'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $username = $email;

    if (empty($first_name) || empty($last_name) || empty($position) || empty($email) || empty($password)) {
        $response['error'] = 'First Name, Last Name, Position, Email, and Password are required for adding an admin.';
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
        $response['error'] = "Database prepare statement failed (username/email check): " . $conn->error;
        echo json_encode($response);
        if (is_object($conn)) $conn->close();
        exit;
    }
    $checkStmt->bind_param("ss", $username, $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        $response['error'] = 'The provided email (acting as username) already exists in the system.';
        $checkStmt->close();
        echo json_encode($response);
        if (is_object($conn)) $conn->close();
        exit;
    }
    $checkStmt->close();

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $default_status_id = 1; 
    $default_role_id = 1;   

    if ($conn->begin_transaction()) {
        try {
            $stmt_admins = $conn->prepare("INSERT INTO admins (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
            if (!$stmt_admins) throw new Exception("Database prepare statement failed (insert into admins table): " . $conn->error);
            
            $stmt_admins->bind_param("sss", $username, $email, $hashed_password);
            if (!$stmt_admins->execute()) throw new Exception("Database execute failed (insert into admins table): " . $stmt_admins->error);
            
            $admin_primary_id = $conn->insert_id;
            if (!$admin_primary_id) {
                throw new Exception("Failed to retrieve last insert ID for new admin.");
            }
            $stmt_admins->close();

            $stmt_admin_info = $conn->prepare("INSERT INTO admin_info_tbl (admin_id, firstname, middlename, lastname, Position, status_id, role_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            if (!$stmt_admin_info) throw new Exception("Database prepare statement failed (insert into admin_info_tbl): " . $conn->error);
            
            $stmt_admin_info->bind_param("issssii", $admin_primary_id, $first_name, $middle_name, $last_name, $position, $default_status_id, $default_role_id);
            if (!$stmt_admin_info->execute()) throw new Exception("Database execute failed (insert into admin_info_tbl): " . $stmt_admin_info->error);

            if ($stmt_admin_info->affected_rows <= 0) {
                 throw new Exception("No rows were affected when inserting into admin_info_tbl, indicating a possible issue.");
            }
            $stmt_admin_info->close();

            $conn->commit();
            $response['success'] = true;
            $response['message'] = 'Admin successfully added.'; 

        } catch (Exception $e) {
            $conn->rollback();
            $response['error'] = "Transaction failed: " . $e->getMessage();
        }
    } else {
        $response['error'] = "Failed to start database transaction: " . $conn->error;
    }

} else {
    $response['error'] = 'Invalid request method. Only POST is accepted.';
}

echo json_encode($response);
if (is_object($conn) && !$conn->connect_error) { 
    $conn->close();
}
?>