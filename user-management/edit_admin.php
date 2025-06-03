<?php
@include '../PHP/dbcon.php';
header('Content-Type: application/json');

$response = ['success' => false, 'error' => 'An unknown error occurred.'];

if (!isset($conn) || !$conn || (is_object($conn) && $conn->connect_error)) {
    $response['error'] = 'Database connection failed.';
    if (isset($conn) && is_object($conn) && $conn->connect_error) {
        $response['error'] .= ' DB Error: ' . $conn->connect_error;
    }
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $admin_id = $_POST['admin_id'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $position = $_POST['position'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $status_id = $_POST['status_id'] ?? '';

    if (empty($admin_id) || empty($first_name) || empty($last_name) || empty($position) || empty($email) || empty($status_id)) {
        $response['error'] = 'Required fields are missing for updating admin.';
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
    
    $checkEmailStmt = $conn->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
    if (!$checkEmailStmt) {
        $response['error'] = "Database prepare statement failed (email check): " . $conn->error;
        echo json_encode($response);
        if (is_object($conn)) $conn->close();
        exit;
    }
    $checkEmailStmt->bind_param("si", $email, $admin_id);
    $checkEmailStmt->execute();
    $checkEmailResult = $checkEmailStmt->get_result();
    if ($checkEmailResult->num_rows > 0) {
        $response['error'] = 'This email address is already in use by another admin.';
        $checkEmailStmt->close();
        echo json_encode($response);
        if (is_object($conn)) $conn->close();
        exit;
    }
    $checkEmailStmt->close();

    if ($conn->begin_transaction()) {
        try {
            $stmt_info = $conn->prepare("UPDATE admin_info_tbl SET firstname = ?, middlename = ?, lastname = ?, Position = ?, status_id = ?, updated_at = NOW() WHERE admin_id = ?");
            if (!$stmt_info) throw new Exception("Database prepare statement failed (update admin_info_tbl): " . $conn->error);
            $stmt_info->bind_param("ssssii", $first_name, $middle_name, $last_name, $position, $status_id, $admin_id);
            if (!$stmt_info->execute()) throw new Exception("Database execute failed (update admin_info_tbl): " . $stmt_info->error);
            $stmt_info->close();

            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_admins = $conn->prepare("UPDATE admins SET email = ?, password = ? WHERE id = ?");
                if (!$stmt_admins) throw new Exception("Database prepare statement failed (update admins with password): " . $conn->error);
                $stmt_admins->bind_param("ssi", $email, $hashed_password, $admin_id);
            } else {
                $stmt_admins = $conn->prepare("UPDATE admins SET email = ? WHERE id = ?");
                if (!$stmt_admins) throw new Exception("Database prepare statement failed (update admins email only): " . $conn->error);
                $stmt_admins->bind_param("si", $email, $admin_id);
            }
            if (!$stmt_admins->execute()) throw new Exception("Database execute failed (update admins table): " . $stmt_admins->error);
            $stmt_admins->close();

            $conn->commit();
            $response['success'] = true;
            $response['message'] = 'Admin updated successfully.';

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