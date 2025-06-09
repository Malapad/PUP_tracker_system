<?php
include '../PHP/dbcon.php'; 

// IMPORTANT: Replace with actual admin session ID and name
// For demonstration, using a placeholder. In a real system, this would come from session variables.
$admin_id = 1; 
$admin_name = "System Admin"; // This should come from a session, e.g., $_SESSION['admin_name']

$DEFAULT_TAB = 'sanction-request';
$active_tab = $_GET['tab'] ?? $DEFAULT_TAB;
$active_view = $_GET['view'] ?? 'list'; // 'list' for main table, 'history' for compliance history, 'sanction_config_history' for sanction config history

// --- AJAX Actions for Sanction Request Tab ---
// Handle "Approve Sanction" from modal
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['approve_sanction'])) {
    $response = ['success' => false, 'message' => 'An unexpected error occurred.'];
    header('Content-Type: application/json');

    $student_number = $_POST['student_number'] ?? '';
    $violation_id = $_POST['violation_id'] ?? '';
    $assigned_sanction_id = $_POST['assigned_sanction_id'] ?? '';
    $deadline_date = $_POST['deadline_date'] ?? null;

    if (empty($student_number) || empty($violation_id) || empty($assigned_sanction_id) || empty($deadline_date)) {
        $response['message'] = 'Missing required fields for approval.';
        echo json_encode($response);
        exit;
    }

    $conn->begin_transaction();
    try {
        // Insert into student_sanction_records_tbl
        $stmt = $conn->prepare(
            "INSERT INTO student_sanction_records_tbl (student_number, violation_id, assigned_sanction_id, deadline_date, assigned_by_admin_id, status) VALUES (?, ?, ?, ?, ?, 'Pending')"
        );

        if (!$stmt) {
            throw new mysqli_sql_exception('Database prepare error for insert: ' . htmlspecialchars($conn->error));
        }

        $stmt->bind_param("siisi", $student_number, $violation_id, $assigned_sanction_id, $deadline_date, $admin_id);
        if (!$stmt->execute()) {
            throw new mysqli_sql_exception('Database execution error for insert: ' . htmlspecialchars($stmt->error));
        }

        if ($stmt->affected_rows > 0) {
            // Update the sanction_requests_tbl to mark it as inactive (or delete if preferred)
            // For now, let's mark it inactive, assuming it's linked to a violation that is now sanctioned.
            // If sanction_requests_tbl is specifically for *pending* requests that get moved,
            // you might delete the row from that table instead.
            // Based on the SQL schema, violation_tbl has no status, so we handle it by checking ssr.
            // If the violation_tbl is also a 'request' table, uncomment the below:
            /*
            $stmt_update_request = $conn->prepare("UPDATE sanction_requests_tbl SET is_active = 0 WHERE student_number = ? AND violation_id = ?"); // Assuming violation_id is in sanction_requests_tbl
            if ($stmt_update_request) {
                $stmt_update_request->bind_param("si", $student_number, $violation_id);
                $stmt_update_request->execute();
                $stmt_update_request->close();
            }
            */
            
            $response['success'] = true;
            $response['message'] = 'Sanction approved and assigned successfully!';
        } else {
            $response['message'] = 'Failed to assign sanction. Please try again.';
        }
        $stmt->close();

        $conn->commit();
    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        $response['message'] = 'Database transaction failed: ' . $exception->getMessage();
    }

    echo json_encode($response);
    exit;
}

// --- Handle marking sanction status (Completed/Pending) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_sanction_status'])) {
    $response = ['success' => false, 'message' => 'An error occurred.'];
    header('Content-Type: application/json');

    $record_id = $_POST['record_id'] ?? '';
    $new_status = $_POST['new_status'] ?? '';
    $student_number = $_POST['student_number'] ?? '';

    if (empty($record_id) || empty($new_status) || !in_array($new_status, ['Completed', 'Pending'])) {
        $response['message'] = 'Invalid data provided.';
        echo json_encode($response);
        exit;
    }

    $conn->begin_transaction();
    try {
        $stmt_update = $conn->prepare("UPDATE student_sanction_records_tbl SET status = ?, date_completed = ? WHERE record_id = ?");
        $date_completed = ($new_status == 'Completed') ? date('Y-m-d H:i:s') : NULL;
        $stmt_update->bind_param("ssi", $new_status, $date_completed, $record_id);
        $stmt_update->execute();

        // Log the action to history table
        $stmt_history = $conn->prepare("INSERT INTO sanction_compliance_history (record_id, student_number, performed_by_admin_name, `action`, details) VALUES (?, ?, ?, ?, ?)");
        $action = "Marked as " . $new_status;
        $details = "Admin '$admin_name' updated sanction status for student $student_number.";
        $stmt_history->bind_param("issss", $record_id, $student_number, $admin_name, $action, $details);
        $stmt_history->execute();

        $conn->commit();
        $response['success'] = true;
        $response['message'] = 'Status updated successfully!';

    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        $response['message'] = 'Database transaction failed: ' . $exception->getMessage();
    }

    echo json_encode($response);
    exit;
}


// --- NEW: AJAX Actions for Sanction Configuration Tab ---

// Fetch sanctions for a specific violation type (used by JS when accordion expands)
if (isset($_GET['action']) && $_GET['action'] == 'get_sanctions_for_violation_type' && isset($_GET['violation_type_id'])) {
    $response = ['success' => false, 'message' => 'Sanctions not found.', 'sanctions' => []];
    $violationTypeId = trim($_GET['violation_type_id']);
    if (empty($violationTypeId) || !is_numeric($violationTypeId)) {
        $response['message'] = 'Invalid Violation Type ID.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    $sql_sanctions = "SELECT disciplinary_sanction_id, offense_level, disciplinary_sanction
                      FROM disciplinary_sanctions
                      WHERE violation_type_id = ? ORDER BY offense_level ASC";
    $stmt_sanctions = $conn->prepare($sql_sanctions);
    if ($stmt_sanctions) {
        $stmt_sanctions->bind_param("i", $violationTypeId);
        $stmt_sanctions->execute();
        $result_sanctions = $stmt_sanctions->get_result();
        $sanctions_data = [];
        while ($sanc_row = $result_sanctions->fetch_assoc()) {
            $sanctions_data[] = $sanc_row;
        }
        $response['success'] = true;
        $response['sanctions'] = $sanctions_data;
        $response['message'] = empty($sanctions_data) ? 'No disciplinary sanctions found for this violation type.' : 'Sanctions fetched successfully.';
        $stmt_sanctions->close();
    } else {
        $response['message'] = 'Error preparing sanctions fetch statement: ' . $conn->error;
        error_log('Error preparing sanctions fetch statement: ' . $conn->error);
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Fetch details for a specific disciplinary sanction (for edit/delete modals)
if (isset($_GET['action']) && $_GET['action'] == 'get_disciplinary_sanction_details' && isset($_GET['id'])) {
    $response = ['success' => false, 'message' => 'Details not found.', 'data' => null];
    $disciplinary_sanction_id = $_GET['id'];
    if (empty($disciplinary_sanction_id)) {
        $response['message'] = 'Sanction ID not provided.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    $sql_details = "SELECT ds.disciplinary_sanction_id, ds.violation_type_id, ds.offense_level, ds.disciplinary_sanction, vt.violation_type AS violation_type_name
                    FROM disciplinary_sanctions ds
                    JOIN violation_type_tbl vt ON ds.violation_type_id = vt.violation_type_id
                    WHERE ds.disciplinary_sanction_id = ?";
    $stmt_details = $conn->prepare($sql_details);
    if ($stmt_details) {
        $stmt_details->bind_param("i", $disciplinary_sanction_id);
        $stmt_details->execute();
        $result_details = $stmt_details->get_result();
        if ($row_details = $result_details->fetch_assoc()) {
            $response['success'] = true;
            $response['message'] = 'Details fetched successfully.';
            $response['data'] = $row_details;
        }
        $stmt_details->close();
    } else {
        $response['message'] = 'Error preparing details fetch statement: ' . $conn->error;
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// --- POST Actions for Sanction Configuration (Add, Edit, Delete Disciplinary Sanctions) ---

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_disciplinary_sanction'])) {
    $response = ['success' => false, 'message' => 'An unexpected error occurred.'];
    $violation_type_id = $_POST['violation_type_id_sanction_modal'] ?? '';
    $offense_level = trim($_POST['offense_level_sanction_modal'] ?? '');
    $disciplinary_sanction = trim($_POST['disciplinary_sanction_text'] ?? '');
    $violation_type_name = trim($_POST['violation_type_name_hidden'] ?? ''); // From hidden input

    if (empty($violation_type_id) || empty($offense_level) || empty($disciplinary_sanction)) {
        $response['message'] = 'All fields are required for adding a sanction.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    $conn->begin_transaction();
    try {
        // Check for existing offense level for this violation type
        $check_stmt = $conn->prepare("SELECT disciplinary_sanction_id FROM disciplinary_sanctions WHERE violation_type_id = ? AND offense_level = ?");
        if ($check_stmt) {
            $check_stmt->bind_param("is", $violation_type_id, $offense_level);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            if ($check_result->num_rows > 0) {
                $response['message'] = "Error: An offense level '{$offense_level}' already exists for this violation type.";
                $conn->rollback();
                echo json_encode($response);
                exit;
            }
            $check_stmt->close();
        } else {
            throw new mysqli_sql_exception('Error preparing check statement for sanction: ' . $conn->error);
        }

        $insert_stmt = $conn->prepare("INSERT INTO disciplinary_sanctions (violation_type_id, offense_level, disciplinary_sanction) VALUES (?, ?, ?)");
        if ($insert_stmt) {
            $insert_stmt->bind_param("iss", $violation_type_id, $offense_level, $disciplinary_sanction);
            if ($insert_stmt->execute()) {
                $new_sanction_id = $conn->insert_id;

                // Log the action to history table
                $stmt_history = $conn->prepare("INSERT INTO disciplinary_sanction_history_tbl (performed_by_admin_name, action_type, violation_type_id, violation_type_name, offense_level, sanction_details_snapshot) VALUES (?, ?, ?, ?, ?, ?)");
                $action = "Added Sanction";
                $snapshot = json_encode([
                    'disciplinary_sanction_id' => $new_sanction_id,
                    'offense_level' => $offense_level,
                    'disciplinary_sanction' => $disciplinary_sanction
                ]);
                $stmt_history->bind_param("ssisss", $admin_name, $action, $violation_type_id, $violation_type_name, $offense_level, $snapshot);
                $stmt_history->execute();

                $response['success'] = true;
                $response['message'] = 'Disciplinary sanction added successfully!';
            } else {
                throw new mysqli_sql_exception('Error adding disciplinary sanction: ' . $insert_stmt->error);
            }
            $insert_stmt->close();
        } else {
            throw new mysqli_sql_exception('Error preparing insert statement for sanction: ' . $conn->error);
        }

        $conn->commit();
    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        $response['message'] = 'Database transaction failed: ' . $exception->getMessage();
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_disciplinary_sanction_submit'])) {
    $response = ['success' => false, 'message' => 'An unexpected error occurred.'];
    $disciplinary_sanction_id = $_POST['edit_disciplinary_sanction_id'] ?? '';
    $violation_type_id = $_POST['edit_violation_type_id_sanction_modal'] ?? '';
    $offense_level = trim($_POST['edit_offense_level_sanction_modal'] ?? '');
    $disciplinary_sanction_text = trim($_POST['edit_disciplinary_sanction_text'] ?? '');
    $violation_type_name = trim($_POST['edit_violation_type_name_hidden'] ?? ''); // From hidden input

    if (empty($disciplinary_sanction_id) || empty($violation_type_id) || empty($offense_level) || empty($disciplinary_sanction_text)) {
        $response['message'] = 'All fields are required for editing a sanction.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    $conn->begin_transaction();
    try {
        // Fetch old details for snapshot/logging
        $old_details_stmt = $conn->prepare("SELECT offense_level, disciplinary_sanction FROM disciplinary_sanctions WHERE disciplinary_sanction_id = ?");
        $old_details_stmt->bind_param("i", $disciplinary_sanction_id);
        $old_details_stmt->execute();
        $old_details_result = $old_details_stmt->get_result();
        $old_sanction_data = $old_details_result->fetch_assoc();
        $old_details_stmt->close();

        // Check for duplicate offense level for the same violation type, excluding the current sanction being edited
        $check_stmt = $conn->prepare("SELECT disciplinary_sanction_id FROM disciplinary_sanctions WHERE violation_type_id = ? AND offense_level = ? AND disciplinary_sanction_id != ?");
        if ($check_stmt) {
            $check_stmt->bind_param("isi", $violation_type_id, $offense_level, $disciplinary_sanction_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            if ($check_result->num_rows > 0) {
                $response['message'] = "Error: An offense level '{$offense_level}' already exists for this violation type.";
                $conn->rollback();
                echo json_encode($response);
                exit;
            }
            $check_stmt->close();
        } else {
            throw new mysqli_sql_exception('Error preparing check statement for sanction update: ' . $conn->error);
        }

        $update_stmt = $conn->prepare("UPDATE disciplinary_sanctions SET violation_type_id = ?, offense_level = ?, disciplinary_sanction = ? WHERE disciplinary_sanction_id = ?");
        if ($update_stmt) {
            $update_stmt->bind_param("issi", $violation_type_id, $offense_level, $disciplinary_sanction_text, $disciplinary_sanction_id);
            if ($update_stmt->execute()) {
                // Log the action to history table
                $stmt_history = $conn->prepare("INSERT INTO disciplinary_sanction_history_tbl (performed_by_admin_name, action_type, violation_type_id, violation_type_name, offense_level, sanction_details_snapshot) VALUES (?, ?, ?, ?, ?, ?)");
                $action = "Updated Sanction";
                $snapshot = json_encode([
                    'disciplinary_sanction_id' => $disciplinary_sanction_id,
                    'old_offense_level' => $old_sanction_data['offense_level'],
                    'old_disciplinary_sanction' => $old_sanction_data['disciplinary_sanction'],
                    'new_offense_level' => $offense_level,
                    'new_disciplinary_sanction' => $disciplinary_sanction_text
                ]);
                $stmt_history->bind_param("ssisss", $admin_name, $action, $violation_type_id, $violation_type_name, $offense_level, $snapshot);
                $stmt_history->execute();

                $response['success'] = true;
                $response['message'] = 'Disciplinary sanction updated successfully!';
            } else {
                throw new mysqli_sql_exception('Error updating disciplinary sanction: ' . $update_stmt->error);
            }
            $update_stmt->close();
        } else {
            throw new mysqli_sql_exception('Error preparing update statement for sanction: ' . $conn->error);
        }

        $conn->commit();
    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        $response['message'] = 'Database transaction failed: ' . $exception->getMessage();
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_disciplinary_sanction_id'])) {
    $response = ['success' => false, 'message' => 'An unexpected error occurred.'];
    $disciplinary_sanction_id = $_POST['delete_disciplinary_sanction_id'];
    $violation_type_id = $_POST['violation_type_id_hidden'] ?? null; // From hidden input
    $violation_type_name = $_POST['violation_type_name_hidden'] ?? null; // From hidden input
    $offense_level = $_POST['offense_level_hidden'] ?? null; // From hidden input
    $disciplinary_sanction_text = $_POST['sanction_details_hidden'] ?? null; // From hidden input


    if (empty($disciplinary_sanction_id)) {
        $response['message'] = 'Sanction ID not provided for deletion.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    $conn->begin_transaction();
    try {
        $delete_stmt = $conn->prepare("DELETE FROM disciplinary_sanctions WHERE disciplinary_sanction_id = ?");
        if ($delete_stmt) {
            $delete_stmt->bind_param("i", $disciplinary_sanction_id);
            if ($delete_stmt->execute()) {
                if ($delete_stmt->affected_rows > 0) {
                    // Log the action to history table
                    $stmt_history = $conn->prepare("INSERT INTO disciplinary_sanction_history_tbl (performed_by_admin_name, action_type, violation_type_id, violation_type_name, offense_level, sanction_details_snapshot) VALUES (?, ?, ?, ?, ?, ?)");
                    $action = "Deleted Sanction";
                    $snapshot = json_encode([
                        'disciplinary_sanction_id' => $disciplinary_sanction_id,
                        'offense_level' => $offense_level,
                        'disciplinary_sanction' => $disciplinary_sanction_text
                    ]);
                    $stmt_history->bind_param("ssisss", $admin_name, $action, $violation_type_id, $violation_type_name, $offense_level, $snapshot);
                    $stmt_history->execute();

                    $response['success'] = true;
                    $response['message'] = 'Disciplinary sanction deleted successfully.';
                } else {
                    $response['message'] = 'Disciplinary sanction not found or already deleted.';
                }
            } else {
                throw new mysqli_sql_exception('Error deleting disciplinary sanction: ' . $delete_stmt->error);
            }
            $delete_stmt->close();
        } else {
            throw new mysqli_sql_exception('Error preparing delete statement for sanction: ' . $conn->error);
        }

        $conn->commit();
    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        $response['message'] = 'Database transaction failed: ' . $exception->getMessage();
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sanction</title>
    <link rel="stylesheet" href="./admin_sanction_styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <div id="toast-notification" class="toast"></div>
    <header>
        <div class="header-content-wrapper">
            <div class="logo">
                <a href="../HTML/admin_homepage.html">
                    <img src="../IMAGE/Tracker-logo.png" alt="PUP Logo">
                </a>
            </div>
            <nav>
                <a href="../HTML/admin_homepage.html">Home</a>
                <a href="../updated-admin-violation/admin-violationpage.php">Violations</a>
                <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="active">Student Sanction</a>
                <a href="../user-management/user_management.php">User Management</a>
            </nav>
            <div class="admin-icons">
                <a href="notification.html" class="notification">
                    <img src="https://img.icons8.com/?size=100&id=83193&format=png&color=000000" alt="Notifications"/></a>
                <a href="admin_account.html" class="admin">
                    <img src="https://img.icons8.com/?size=100&id=77883&format=png&color=000000" alt="Admin Account"/></a>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if ($active_view === 'history'): ?>
            <div class="history-header">
                <h1>Sanction Compliance History</h1>
                <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?tab=sanction-compliance" class="back-to-list-btn"><i class="fas fa-arrow-left"></i> Back to Sanction List</a>
            </div>
            <div class="history-table-container">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Performed By</th>
                            <th>Action</th>
                            <th>Target Student</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $history_sql = "SELECT * FROM sanction_compliance_history ORDER BY `timestamp` DESC";
                        $history_result = $conn->query($history_sql);
                        if ($history_result && $history_result->num_rows > 0) {
                            while ($row = $history_result->fetch_assoc()) {
                                $action_class = '';
                                if ($row['action'] == 'Marked as Completed') $action_class = 'action-completed';
                                if ($row['action'] == 'Marked as Pending') $action_class = 'action-pending';
                                
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars(date("M d, Y, h:i A", strtotime($row['timestamp']))) . "</td>";
                                echo "<td>" . htmlspecialchars($row['performed_by_admin_name']) . "</td>";
                                echo "<td><span class='action-badge " . $action_class . "'>" . htmlspecialchars($row['action']) . "</span></td>";
                                echo "<td>" . htmlspecialchars($row['student_number']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['details']) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='no-records-cell'>No compliance history found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($active_view === 'sanction_config_history'): ?>
            <div class="history-header">
                <h1>Disciplinary Sanction Configuration History</h1>
                <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?tab=sanction-config" class="back-to-list-btn"><i class="fas fa-arrow-left"></i> Back to Sanction Configuration</a>
            </div>
            <div class="history-table-container">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Performed By</th>
                            <th>Action Type</th>
                            <th>Violation Type</th>
                            <th>Offense Level</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $history_sql = "SELECT * FROM disciplinary_sanction_history_tbl ORDER BY `timestamp` DESC";
                        $history_result = $conn->query($history_sql);
                        if ($history_result && $history_result->num_rows > 0) {
                            while ($row = $history_result->fetch_assoc()) {
                                $action_class = '';
                                if ($row['action_type'] == 'Added Sanction') $action_class = 'action-added';
                                if ($row['action_type'] == 'Updated Sanction') $action_class = 'action-updated';
                                if ($row['action_type'] == 'Deleted Sanction') $action_class = 'action-deleted';

                                $details_output = 'N/A';
                                if (!empty($row['sanction_details_snapshot'])) {
                                    $snapshot = json_decode($row['sanction_details_snapshot'], true);
                                    if ($snapshot) {
                                        if ($row['action_type'] == 'Added Sanction' || $row['action_type'] == 'Deleted Sanction') {
                                            $details_output = "Offense: " . htmlspecialchars($snapshot['offense_level']) . ", Sanction: " . htmlspecialchars($snapshot['disciplinary_sanction']);
                                        } elseif ($row['action_type'] == 'Updated Sanction') {
                                            $details_output = "Old: " . htmlspecialchars($snapshot['old_offense_level']) . " - " . htmlspecialchars($snapshot['old_disciplinary_sanction']) . "; New: " . htmlspecialchars($snapshot['new_offense_level']) . " - " . htmlspecialchars($snapshot['new_disciplinary_sanction']);
                                        }
                                    }
                                }

                                echo "<tr>";
                                echo "<td>" . htmlspecialchars(date("M d, Y, h:i A", strtotime($row['timestamp']))) . "</td>";
                                echo "<td>" . htmlspecialchars($row['performed_by_admin_name']) . "</td>";
                                echo "<td><span class='action-badge " . $action_class . "'>" . htmlspecialchars($row['action_type']) . "</span></td>";
                                echo "<td>" . htmlspecialchars($row['violation_type_name'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($row['offense_level'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($details_output) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' class='no-records-cell'>No disciplinary sanction configuration history found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <h1>Student Sanction List</h1>

            <div class="tabs">
                <button class="tab <?php echo ($active_tab == 'sanction-request' ? 'active' : ''); ?>" data-tab="sanction-request"><i class="fas fa-user-graduate"></i> Sanction Request</button>
                <button class="tab <?php echo ($active_tab == 'sanction-compliance' ? 'active' : ''); ?>" data-tab="sanction-compliance"><i class="fas fa-tasks"></i> Sanction Compliance</button>
                <button class="tab <?php echo ($active_tab == 'sanction-config' ? 'active' : ''); ?>" data-tab="sanction-config"><i class="fas fa-cogs"></i> Sanction Configuration</button>
            </div>

            <div id="sanction-request" class="tab-content" style="<?php echo ($active_tab == 'sanction-request' ? 'display: block;' : 'display: none;'); ?>">
                <?php
                // Filter parameters for Sanction Request tab
                $filterViolation = $_GET['violation_type'] ?? '';
                $search = trim($_GET['search_student_number'] ?? '');
                $filterCourse = $_GET['filter_course'] ?? '';
                $filterYear = $_GET['filter_year'] ?? '';
                $filterSection = $_GET['filter_section'] ?? '';

                if ($active_tab == 'sanction-request' && (!empty($filterViolation) || !empty($search) || !empty($filterCourse) || !empty($filterYear) || !empty($filterSection))) {
                    $baseUrl = strtok($_SERVER["REQUEST_URI"], '?');
                    echo '<div class="clear-filters-container">';
                    echo '       <a href="' . htmlspecialchars($baseUrl) . '?tab=sanction-request" class="clear-filters-btn"><i class="fas fa-eraser"></i> Clear Filters</a>';
                    echo '</div>';
                }
                ?>
                <div class="table-controls">
                    <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="filter-form-new">
                        <input type="hidden" name="tab" value="sanction-request">
                        <div class="filter-group-and-search">
                            <div class="filters-group">
                                <div class="custom-select-wrapper">
                                    <select class="custom-select" name="filter_course" onchange="this.form.submit()">
                                        <option value="">Select Course</option>
                                        <?php
                                        $courseResult = $conn->query("SELECT course_id, course_name FROM course_tbl ORDER BY course_name ASC");
                                        if ($courseResult) {
                                            while ($row = $courseResult->fetch_assoc()) {
                                                echo "<option value='" . htmlspecialchars($row['course_id']) . "' " . (($filterCourse == $row['course_id']) ? 'selected' : '') . ">" . htmlspecialchars($row['course_name']) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                    <span class="select-arrow"></span>
                                </div>
                                <div class="custom-select-wrapper">
                                    <select class="custom-select" name="filter_year" onchange="this.form.submit()">
                                        <option value="">Select Year</option>
                                        <?php
                                        $yearResult = $conn->query("SELECT year_id, year FROM year_tbl ORDER BY year ASC");
                                        if ($yearResult) {
                                            while ($row = $yearResult->fetch_assoc()) {
                                                echo "<option value='" . htmlspecialchars($row['year_id']) . "' " . (($filterYear == $row['year_id']) ? 'selected' : '') . ">" . htmlspecialchars($row['year']) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                    <span class="select-arrow"></span>
                                </div>
                                <div class="custom-select-wrapper">
                                    <select class="custom-select" name="filter_section" onchange="this.form.submit()">
                                        <option value="">Select Section</option>
                                        <?php
                                        $sectionResult = $conn->query("SELECT section_id, section_name FROM section_tbl ORDER BY section_name ASC");
                                        if ($sectionResult) {
                                            while ($row = $sectionResult->fetch_assoc()) {
                                                echo "<option value='" . htmlspecialchars($row['section_id']) . "' " . (($filterSection == $row['section_id']) ? 'selected' : '') . ">" . htmlspecialchars($row['section_name']) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                    <span class="select-arrow"></span>
                                </div>
                                <div class="custom-select-wrapper">
                                    <select class="custom-select" name="violation_type" onchange="this.form.submit()">
                                        <option value="">Select Violation</option>
                                        <?php
                                        $vtResult = $conn->query("SELECT violation_type_id, violation_type FROM violation_type_tbl ORDER BY violation_type ASC");
                                        if ($vtResult) {
                                            while ($row = $vtResult->fetch_assoc()) {
                                                echo "<option value='" . htmlspecialchars($row['violation_type_id']) . "' " . (($filterViolation == $row['violation_type_id']) ? 'selected' : '') . ">" . htmlspecialchars($row['violation_type']) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                    <span class="select-arrow"></span>
                                </div>
                            </div>

                            <div class="search-group">
                                <div class="search-input-wrapper">
                                    <input type="text" id="searchInputNew" name="search_student_number" placeholder="Search by Student Number" value="<?php echo htmlspecialchars($search); ?>" class="search-input-field" />
                                    <button type="submit" class="search-button-new"><i class="fas fa-search"></i></button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="main-table-scroll-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Student Number</th>
                                <th class="text-wrap-header">Student Name</th>
                                <th>Course</th>
                                <th>Year & Section</th>
                                <th class="text-wrap-header">Violation Type</th>
                                <th class="text-wrap-header">Disciplinary Sanction</th>
                                <th>Offense Level</th>
                                <th>Date Requested</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT
                                        v.violation_id, v.violation_date, v.student_number,
                                        u.first_name, u.middle_name, u.last_name,
                                        c.course_name, y.year, s.section_name,
                                        vt.violation_type_id, vt.violation_type,
                                        (SELECT COUNT(*) FROM violation_tbl WHERE student_number = u.student_number AND violation_type = vt.violation_type_id) as offense_level_count,
                                        ds.disciplinary_sanction_id, ds.disciplinary_sanction, ds.offense_level
                                    FROM violation_tbl v
                                    JOIN users_tbl u ON v.student_number = u.student_number
                                    JOIN violation_type_tbl vt ON v.violation_type = vt.violation_type_id
                                    LEFT JOIN course_tbl c ON u.course_id = c.course_id
                                    LEFT JOIN year_tbl y ON u.year_id = y.year_id
                                    LEFT JOIN section_tbl s ON u.section_id = s.section_id
                                    LEFT JOIN disciplinary_sanctions ds ON vt.violation_type_id = ds.violation_type_id AND ds.offense_level = (SELECT COUNT(*) FROM violation_tbl WHERE student_number = u.student_number AND violation_type = vt.violation_type_id)
                                    WHERE NOT EXISTS (
                                        SELECT 1 FROM student_sanction_records_tbl ssr WHERE ssr.violation_id = v.violation_id
                                    )";

                            $params = [];
                            $paramTypes = "";

                            if (!empty($filterViolation)) { $sql .= " AND vt.violation_type_id = ?"; $params[] = $filterViolation; $paramTypes .= "i"; }
                            if (!empty($filterCourse)) { $sql .= " AND c.course_id = ?"; $params[] = $filterCourse; $paramTypes .= "i"; }
                            if (!empty($filterYear)) { $sql .= " AND y.year_id = ?"; $params[] = $filterYear; $paramTypes .= "i"; }
                            if (!empty($filterSection)) { $sql .= " AND s.section_id = ?"; $params[] = $filterSection; $paramTypes .= "i"; }
                            if (!empty($search)) {
                                $sql .= " AND u.student_number LIKE ?";
                                $params[] = "%{$search}%";
                                $paramTypes .= "s";
                            }
                            
                            $sql .= " ORDER BY v.violation_date DESC";

                            $stmt = $conn->prepare($sql);
                            if ($stmt) {
                                if (!empty($params)) {
                                    $stmt->bind_param($paramTypes, ...$params);
                                }
                                $stmt->execute();
                                $result = $stmt->get_result();

                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $student_full_name = htmlspecialchars($row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'][0] . '. ' : '') . $row['last_name']);
                                        $course_year_section = htmlspecialchars(($row['course_name'] ?? 'N/A') . ' | ' . ($row['year'] ?? 'N/A') . ' - ' . ($row['section_name'] ?? 'N/A'));
                                        
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['student_number']) . "</td>";
                                        echo "<td class='text-wrap-content'>" . $student_full_name . "</td>";
                                        echo "<td>" . htmlspecialchars($row['course_name'] ?? 'N/A') . "</td>";
                                        echo "<td>" . htmlspecialchars(($row['year'] ?? 'N/A') . ' - ' . ($row['section_name'] ?? 'N/A')) . "</td>";
                                        echo "<td class='text-wrap-content'>" . htmlspecialchars($row['violation_type']) . "</td>";
                                        echo "<td class='text-wrap-content'>" . htmlspecialchars($row['disciplinary_sanction'] ?? 'No sanction defined for this offense level.') . "</td>";
                                        echo "<td>" . htmlspecialchars($row['offense_level'] ?? 'N/A') . "</td>";
                                        echo "<td>" . htmlspecialchars(date("F j, Y", strtotime($row['violation_date']))) . "</td>";
                                        echo "<td class='action-buttons-cell'>";
                                        echo "<button class='view-manage-btn'
                                                        data-student-number='" . htmlspecialchars($row['student_number']) . "'
                                                        data-student-name='" . $student_full_name . "'
                                                        data-course-year-section='" . $course_year_section . "'
                                                        data-violation-id='" . htmlspecialchars($row['violation_id']) . "'
                                                        data-violation-type='" . htmlspecialchars($row['violation_type']) . "'
                                                        data-disciplinary-sanction='" . htmlspecialchars($row['disciplinary_sanction'] ?? '') . "'
                                                        data-offense-level='" . htmlspecialchars($row['offense_level'] ?? '') . "'
                                                        data-date-requested='" . htmlspecialchars(date("F j, Y", strtotime($row['violation_date']))) . "'
                                                        data-assigned-sanction-id='" . htmlspecialchars($row['disciplinary_sanction_id'] ?? '') . "'
                                                        ><i class='fas fa-eye'></i> Manage</button>";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='9' class='no-records-cell'>No pending sanction requests found.</td></tr>";
                                }
                                $stmt->close();
                            } else {
                                    echo "<tr><td colspan='9' class='no-records-cell'>Database query error.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="sanction-compliance" class="tab-content" style="<?php echo ($active_tab == 'sanction-compliance' ? 'display: block;' : 'display: none;'); ?>">
                <?php $filterComplianceStatus = $_GET['status_filter'] ?? 'All'; ?>
                <div class="compliance-controls">
                    <div class="compliance-filter-tabs">
                        <a href="?tab=sanction-compliance&status_filter=All" class="filter-tab-btn <?php echo (($filterComplianceStatus == 'All') ? 'active' : ''); ?>">All</a>
                        <a href="?tab=sanction-compliance&status_filter=Pending" class="filter-tab-btn <?php echo (($filterComplianceStatus == 'Pending') ? 'active' : ''); ?>">Pending</a>
                        <a href="?tab=sanction-compliance&status_filter=Completed" class="filter-tab-btn <?php echo (($filterComplianceStatus == 'Completed') ? 'active' : ''); ?>">Completed</a>
                    </div>
                    <a href="?tab=sanction-compliance&view=history" class="view-history-btn">
                        <i class="fas fa-history"></i> View History
                    </a>
                </div>

                <div class="main-table-scroll-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Student Number</th>
                                <th class="text-wrap-header">Student Name</th>
                                <th>Course</th>
                                <th>Year & Section</th>
                                <th class="text-wrap-header">Violation Type</th>
                                <th class="text-wrap-header">Disciplinary Sanction</th>
                                <th>Offense Level</th>
                                <th>Date of Compliance</th>
                                <th>Status</th>
                                <th class="action-column <?php echo ($filterComplianceStatus == 'All' ? 'hidden' : ''); ?>">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql_compliance = "SELECT
                                                    ssr.record_id, ssr.status, ssr.deadline_date, ssr.date_completed,
                                                    u.student_number, u.first_name, u.middle_name, u.last_name,
                                                    c.course_name, y.year, s.section_name,
                                                    vt.violation_type,
                                                    ds.disciplinary_sanction, ds.offense_level
                                                FROM student_sanction_records_tbl ssr
                                                JOIN users_tbl u ON ssr.student_number = u.student_number
                                                JOIN violation_tbl v ON ssr.violation_id = v.violation_id
                                                JOIN violation_type_tbl vt ON v.violation_type = vt.violation_type_id
                                                LEFT JOIN disciplinary_sanctions ds ON ssr.assigned_sanction_id = ds.disciplinary_sanction_id
                                                LEFT JOIN course_tbl c ON u.course_id = c.course_id
                                                LEFT JOIN year_tbl y ON u.year_id = y.year_id
                                                LEFT JOIN section_tbl s ON u.section_id = s.section_id";
                            
                            $compliance_params = [];
                            $compliance_paramTypes = "";

                            if ($filterComplianceStatus != 'All') {
                                $sql_compliance .= " WHERE ssr.status = ?";
                                $compliance_params[] = $filterComplianceStatus;
                                $compliance_paramTypes .= "s";
                            }
                            
                            $sql_compliance .= " ORDER BY ssr.date_assigned DESC";

                            $stmt_compliance = $conn->prepare($sql_compliance);
                            if ($stmt_compliance) {
                                if (!empty($compliance_params)) {
                                    $stmt_compliance->bind_param($compliance_paramTypes, ...$compliance_params);
                                }
                                $stmt_compliance->execute();
                                $result_compliance = $stmt_compliance->get_result();
                                
                                if ($result_compliance && $result_compliance->num_rows > 0) {
                                    while ($row = $result_compliance->fetch_assoc()) {
                                        $status_class = 'status-default';
                                        if ($row['status'] == 'Pending') $status_class = 'status-pending';
                                        if ($row['status'] == 'Completed') $status_class = 'status-completed';

                                        $student_full_name = htmlspecialchars($row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'][0] . '. ' : '') . $row['last_name']);
                                        $course_year_section = htmlspecialchars(($row['course_name'] ?? 'N/A') . ' | ' . ($row['year'] ?? 'N/A') . ' - ' . ($row['section_name'] ?? 'N/A'));

                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['student_number']) . "</td>";
                                        echo "<td class='text-wrap-content'>" . $student_full_name . "</td>";
                                        echo "<td>" . htmlspecialchars($row['course_name'] ?? 'N/A') . "</td>";
                                        echo "<td>" . htmlspecialchars(($row['year'] ?? 'N/A') . ' - ' . ($row['section_name'] ?? 'N/A')) . "</td>";
                                        echo "<td class='text-wrap-content'>" . htmlspecialchars($row['violation_type']) . "</td>";
                                        echo "<td class='text-wrap-content'>" . htmlspecialchars($row['disciplinary_sanction'] ?? 'N/A') . "</td>";
                                        echo "<td>" . htmlspecialchars($row['offense_level'] ?? 'N/A') . "</td>";
                                        echo "<td>" . htmlspecialchars(date("F j, Y", strtotime($row['deadline_date']))) . "</td>";
                                        echo "<td><span class='status-badge " . $status_class . "'>" . htmlspecialchars($row['status']) . "</span></td>";
                                        
                                        echo "<td class='action-buttons-cell action-column " . ($filterComplianceStatus == 'All' ? 'hidden' : '') . "'>";
                                        if ($row['status'] == 'Pending') {
                                            echo "<button class='update-status-btn status-completed-btn' data-record-id='" . htmlspecialchars($row['record_id']) . "' data-student-number='" . htmlspecialchars($row['student_number']) . "' data-new-status='Completed'><i class='fas fa-check-circle'></i> Mark Completed</button>";
                                        } else {
                                            echo "<button class='update-status-btn status-pending-btn' data-record-id='" . htmlspecialchars($row['record_id']) . "' data-student-number='" . htmlspecialchars($row['student_number']) . "' data-new-status='Pending'><i class='fas fa-undo'></i> Mark as Pending</button>";
                                        }
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    $colspan = ($filterComplianceStatus == 'All' ? '9' : '10');
                                    echo "<tr><td colspan='" . $colspan . "' class='no-records-cell'>No " . strtolower(htmlspecialchars($filterComplianceStatus)) . " sanctions found.</td></tr>";
                                }
                                $stmt_compliance->close();
                            } else {
                                $colspan = ($filterComplianceStatus == 'All' ? '9' : '10');
                                echo "<tr><td colspan='" . $colspan . "' class='no-records-cell'>Database query error.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="sanction-config" class="tab-content" style="<?php echo ($active_tab == 'sanction-config' ? 'display: block;' : 'display: none;'); ?>">
                <div class="sanction-config-controls-row">
                    <div class="sanction-config-controls-right-group">
                        <a href="?tab=sanction-config&view=sanction_config_history" class="view-history-btn"><i class="fas fa-history"></i> View History</a>
                        <div class="sanction-config-search-bar">
                            <input type="text" id="violation-type-search" placeholder="Search Violation Type">
                            <button type="button" class="search-button"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                </div>
                <div class="accordion-container-wrapper">
                    <div class="accordion-container">
                        <?php
                        $violationTypesQuery = "SELECT violation_type_id, violation_type, resolution_number FROM violation_type_tbl ORDER BY violation_type ASC";
                        $vtResult = $conn->query($violationTypesQuery);
                        if ($vtResult && $vtResult->num_rows > 0) {
                            while ($vtRow = $vtResult->fetch_assoc()) {
                                $violationTypeId = htmlspecialchars($vtRow['violation_type_id']);
                                $violationTypeName = htmlspecialchars($vtRow['violation_type']);
                                $resolutionNumber = htmlspecialchars($vtRow['resolution_number'] ?? 'N/A');
                        ?>
                        <div class="accordion-item violation-type-item" data-violation-type-name="<?php echo $violationTypeName; ?>">
                            <button class="accordion-header" data-violation-type-id="<?php echo $violationTypeId; ?>" data-violation-type-name="<?php echo $violationTypeName; ?>">
                                <?php echo $violationTypeName; ?>
                                <i class="fas fa-chevron-down accordion-icon"></i>
                            </button>
                            <div class="accordion-content">
                                <div class="sanction-config-header-inside-accordion">
                                    <h4>Disciplinary Sanctions for '<?php echo $violationTypeName; ?>'</h4>
                                    <button class="add-sanction-btn" data-violation-type-id="<?php echo $violationTypeId; ?>" data-violation-type-name="<?php echo $violationTypeName; ?>">
                                        <i class="fas fa-plus"></i> Add Sanction
                                    </button>
                                </div>
                                <div class="sanction-table-container">
                                    <table class="sanction-config-table">
                                        <thead>
                                            <tr>
                                                <th>Offense Level</th>
                                                <th>Disciplinary Sanction</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="sanction-table-body" id="sanction-table-body-<?php echo $violationTypeId; ?>">
                                            <tr><td colspan='3' class='no-records-cell'><i class="fas fa-spinner fa-spin"></i> Loading sanctions...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php
                            }
                        } else {
                            echo "<p class='no-records-cell'>No violation types found. Please add violation types in the Violations page first.</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        <?php endif; ?> </div>

    <div id="viewSanctionDetailsModal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="head-modal">
                <h3>Sanction Request Management</h3>
                <span class="close-modal-button" data-modal="viewSanctionDetailsModal">&times;</span>
            </div>
            <div id="approveSanctionModalMessage" class="modal-message" style="display: none;"></div>
            <form id="approveSanctionForm" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="approve_sanction" value="1">
                <input type="hidden" id="approveStudentNumber" name="student_number">
                <input type="hidden" id="approveViolationId" name="violation_id">
                <input type="hidden" id="approveAssignedSanctionId" name="assigned_sanction_id">
                

                <div class="details-content">
                    <p><strong>Student Name:</strong> <span id="detailStudentName"></span></p>
                    <p><strong>Student Number:</strong> <span id="detailStudentNumber"></span></p>
                    <p><strong>Course | Year & Section:</strong> <span id="detailCourseYearSection"></span></p>
                    <br>
                    <p><strong>Violation Type:</strong> <span id="detailViolationType"></span></p>
                    <p><strong>Disciplinary Sanction:</strong> <span id="detailDisciplinarySanction"></span></p>
                    <p><strong>Offense Level:</strong> <span id="detailOffenseLevel"></span></p>
                    <br>
                    <p><strong>Date Requested:</strong> <span id="detailDateRequested"></span></p>
                </div>

                <div class="row">
                    <div class="column full-width">
                        <label for="deadlineDate">Set Starting Date for Compliance:</label>
                        <input type="date" id="deadlineDate" name="deadline_date" class="modal-input" required>
                    </div>
                </div>

                <div class="button-row">
                    <button type="submit" class="modal-button-publish"><i class="fas fa-check"></i> Approve</button>
                    <button type="button" class="modal-button-cancel close-modal-button" data-modal="viewSanctionDetailsModal"><i class="fas fa-times"></i> Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="addSanctionModal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="head-modal">
                <h3>Add Disciplinary Sanction</h3>
                <span class="close-modal-add-sanction-button" style="float:right; cursor:pointer; font-size: 1.5em;">&times;</span>
            </div>
            <div id="addSanctionModalMessage" class="modal-message" style="display: none;"></div>
            <form id="addSanctionForm" class="form-container" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="add_disciplinary_sanction" value="1">
                <input type="hidden" id="sanctionViolationTypeId" name="violation_type_id_sanction_modal">
                <input type="hidden" id="sanctionViolationTypeNameHidden" name="violation_type_name_hidden">
                <p>For Violation Type: <strong id="sanctionViolationTypeNameDisplay"></strong></p>
                <div class="row">
                    <div class="column full-width">
                        <label for="offenseLevelSanctionModal">Offense Level:</label>
                        <input type="text" id="offenseLevelSanctionModal" name="offense_level_sanction_modal" required />
                    </div>
                </div>
                <div class="row">
                    <div class="column full-width">
                        <label for="disciplinarySanctionText">Disciplinary Sanction:</label>
                        <textarea id="disciplinarySanctionText" name="disciplinary_sanction_text" rows="3" required></textarea>
                    </div>
                </div>
                <div class="button-row">
                    <button type="submit" class="modal-button-add-edit"><i class="fas fa-plus"></i> Add Sanction</button>
                    <button type="button" class="modal-button-cancel close-modal-add-sanction-button"><i class="fas fa-times"></i> Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editSanctionModal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="head-modal">
                <h3>Edit Disciplinary Sanction</h3>
                <span class="close-modal-edit-sanction-button" style="float:right; cursor:pointer; font-size: 1.5em;">&times;</span>
            </div>
            <div id="editSanctionModalMessage" class="modal-message" style="display: none;"></div>
            <form id="editSanctionForm" class="form-container" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="edit_disciplinary_sanction_submit" value="1">
                <input type="hidden" id="editDisciplinarySanctionId" name="edit_disciplinary_sanction_id">
                <input type="hidden" id="editSanctionViolationTypeId" name="edit_violation_type_id_sanction_modal">
                <input type="hidden" id="editSanctionViolationTypeNameHidden" name="edit_violation_type_name_hidden">
                <p>For Violation Type: <strong id="editSanctionViolationTypeNameDisplay"></strong></p>
                <div class="row">
                    <div class="column full-width">
                        <label for="editOffenseLevelSanctionModal">Offense Level:</label>
                        <input type="text" id="editOffenseLevelSanctionModal" name="edit_offense_level_sanction_modal" required />
                    </div>
                </div>
                <div class="row">
                    <div class="column full-width">
                        <label for="editDisciplinarySanctionText">Disciplinary Sanction:</label>
                        <textarea id="editDisciplinarySanctionText" name="edit_disciplinary_sanction_text" rows="3" required></textarea>
                    </div>
                </div>
                <div class="button-row">
                    <button type="submit" class="modal-button-add-edit"><i class="fas fa-save"></i> Save Changes</button>
                    <button type="button" class="modal-button-cancel close-modal-edit-sanction-button"><i class="fas fa-times"></i> Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteSanctionModal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="head-modal">
                <h3>Delete Disciplinary Sanction</h3>
                <span class="close-modal-delete-sanction-button" style="float:right; cursor:pointer; font-size: 1.5em;">&times;</span>
            </div>
            <div id="deleteSanctionModalMessage" class="modal-message" style="display: none;"></div>
            <div class="confirmation-content">
                <p>Are you sure you want to delete this Disciplinary Sanction?</p>
                <input type="hidden" id="deleteSanctionViolationTypeIdHidden" name="violation_type_id_hidden">
                <input type="hidden" id="deleteSanctionViolationTypeNameHidden" name="violation_type_name_hidden">
                <input type="hidden" id="deleteSanctionOffenseLevelHidden" name="offense_level_hidden">
                <input type="hidden" id="deleteSanctionTextHidden" name="sanction_details_hidden">

                <p>For Violation Type: <strong id="deleteSanctionViolationTypeNameDisplay"></strong></p>
                <p><strong>Offense Level:</strong> <span id="deleteSanctionOffenseLevelDisplay"></span></p>
                <p><strong>Sanction:</strong> <span id="deleteSanctionTextDisplay"></span></p>
            </div>
            <div class="button-row">
                <button type="button" id="confirmDeleteSanctionBtn" class="btn-confirm-delete"><i class="fas fa-check"></i> Confirm Delete</button>
                <button type="button" class="modal-button-cancel close-modal-delete-sanction-button"><i class="fas fa-times"></i> Cancel</button>
            </div>
        </div>
    </div>

    <script src="./admin_sanction.js"></script>
</body>
</html>
<?php $conn->close(); ?>