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

    if (empty($admin_id)) {
        $response['error'] = 'Admin ID is required for deletion.';
        echo json_encode($response);
        if (is_object($conn)) $conn->close();
        exit;
    }
    
    if ($conn->begin_transaction()) {
        try {
            $stmt_info = $conn->prepare("DELETE FROM admin_info_tbl WHERE admin_id = ?");
            if (!$stmt_info) throw new Exception("Database prepare statement failed (delete from admin_info_tbl): " . $conn->error);
            $stmt_info->bind_param("i", $admin_id);
            $stmt_info->execute();
            $stmt_info->close();

            $stmt_admins = $conn->prepare("DELETE FROM admins WHERE id = ?");
            if (!$stmt_admins) throw new Exception("Database prepare statement failed (delete from admins): " . $conn->error);
            $stmt_admins->bind_param("i", $admin_id);
            $stmt_admins->execute();

            if ($stmt_admins->affected_rows > 0) {
                $conn->commit();
                $response['success'] = true;
                $response['message'] = 'Admin deleted successfully.';
            } else {
                throw new Exception("Admin not found in main accounts table or already deleted. Info table entry might have been removed if it existed.");
            }
            $stmt_admins->close();

        } catch (Exception $e) {
            $conn->rollback();
            if ($conn->errno == 1451) { 
                 $response['error'] = "Cannot delete admin. They might be linked to other records in the system.";
            } else {
                 $response['error'] = "Deletion failed: " . $e->getMessage();
            }
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