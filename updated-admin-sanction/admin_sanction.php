<?php
include '../PHP/dbcon.php'; // Assuming dbcon.php handles the database connection

// --- NEW: Central Admin Info (replace with your session logic) ---
$admin_id = 1; // HARDCODED: Replace with actual logged-in admin ID from session
$admin_name = "System Admin"; // Example admin name

// Active tab determination
$DEFAULT_TAB = 'sanction-request';
$active_tab = $_GET['tab'] ?? $DEFAULT_TAB;
$active_view = $_GET['view'] ?? 'list'; // 'list' or 'history'

// Filter and Search parameters
$filterViolation = $_GET['violation_type'] ?? '';
$search = trim($_GET['search_student_number'] ?? '');
$filterCourse = $_GET['filter_course'] ?? '';
$filterYear = $_GET['filter_year'] ?? '';
$filterSection = $_GET['filter_section'] ?? '';
$filterComplianceStatus = $_GET['status_filter'] ?? 'Pending'; // Default to 'Pending' for compliance tab

// --- BACKEND ACTION HANDLERS ---

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

    $stmt = $conn->prepare(
        "INSERT INTO student_sanction_records_tbl (student_number, violation_id, assigned_sanction_id, deadline_date, assigned_by_admin_id, status) VALUES (?, ?, ?, ?, ?, 'Pending')"
    );

    if ($stmt) {
        $stmt->bind_param("siisi", $student_number, $violation_id, $assigned_sanction_id, $deadline_date, $admin_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $response['success'] = true;
                $response['message'] = 'Sanction approved and assigned successfully!';
            } else {
                $response['message'] = 'Failed to assign sanction. Please try again.';
            }
        } else {
            $response['message'] = 'Database execution error: ' . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    } else {
        $response['message'] = 'Database prepare error: ' . htmlspecialchars($conn->error);
    }

    echo json_encode($response);
    exit;
}

// --- NEW: Handle marking sanction status (Completed/Pending) ---
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

// --- SANCTION CONFIGURATION CRUD HANDLERS REMOVED AS REQUESTED ---
// The following PHP blocks were removed to clear the functionality:
// - Handle adding new sanction type
// - Handle deleting sanction type
// - Handle updating sanction type

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sanction</title>
    <link rel="stylesheet" href="./admin_sanction_styles.css">
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
            <?php else: ?>
            <h1>Student Sanction List</h1>

            <div class="tabs">
                <button class="tab <?php echo ($active_tab == 'sanction-request' ? 'active' : ''); ?>" data-tab="sanction-request"><i class="fas fa-user-graduate"></i> Sanction Request</button>
                <button class="tab <?php echo ($active_tab == 'sanction-compliance' ? 'active' : ''); ?>" data-tab="sanction-compliance"><i class="fas fa-tasks"></i> Sanction Compliance</button>
                <button class="tab <?php echo ($active_tab == 'sanction-config' ? 'active' : ''); ?>" data-tab="sanction-config"><i class="fas fa-cogs"></i> Sanction Configuration</button>
            </div>

            <div id="sanction-request" class="tab-content" style="<?php echo ($active_tab == 'sanction-request' ? 'display: block;' : 'display: none;'); ?>">
                <?php
                if ($active_tab == 'sanction-request' && (!empty($filterViolation) || !empty($search) || !empty($filterCourse) || !empty($filterYear) || !empty($filterSection))) {
                    $baseUrl = strtok($_SERVER["REQUEST_URI"], '?');
                    echo '<div class="clear-filters-container">';
                    echo '    <a href="' . htmlspecialchars($baseUrl) . '?tab=sanction-request" class="clear-filters-btn"><i class="fas fa-eraser"></i> Clear Filters</a>';
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
                                <th>Student Name</th>
                                <th>Course</th>
                                <th>Year</th>
                                <th>Date of Request</th>
                                <th>Violation Type</th>
                                <th>Offense Level</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT
                                        u.student_number, u.first_name, u.middle_name, u.last_name,
                                        c.course_name, y.year, s.section_name,
                                        v.violation_id, v.violation_date, vt.violation_type,
                                        (SELECT COUNT(*) FROM violation_tbl WHERE student_number = u.student_number AND violation_type = v.violation_type) as offense_level
                                    FROM violation_tbl v
                                    JOIN users_tbl u ON v.student_number = u.student_number
                                    JOIN violation_type_tbl vt ON v.violation_type = vt.violation_type_id
                                    LEFT JOIN course_tbl c ON u.course_id = c.course_id
                                    LEFT JOIN year_tbl y ON u.year_id = y.year_id
                                    LEFT JOIN section_tbl s ON u.section_id = s.section_id
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
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['student_number']) . "</td>";
                                        echo "<td>" . $student_full_name . "</td>";
                                        echo "<td>" . htmlspecialchars($row['course_name'] ?? 'N/A') . "</td>";
                                        echo "<td>" . htmlspecialchars($row['year'] ?? 'N/A') . "</td>";
                                        echo "<td>" . htmlspecialchars(date("F j, Y", strtotime($row['violation_date']))) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['violation_type']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['offense_level']) . "</td>";
                                        // MODIFIED: Status column now shows a button
                                        echo "<td><button class='sanction-request-btn'>Sanction</button></td>";
                                        echo "<td class='action-buttons-cell'>";
                                        echo "<button class='view-manage-btn'
                                                        data-student-number='" . htmlspecialchars($row['student_number']) . "'
                                                        data-student-name='" . $student_full_name . "'
                                                        data-violation-id='" . htmlspecialchars($row['violation_id']) . "'
                                                        data-violation-type='" . htmlspecialchars($row['violation_type']) . "'
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
                <div class="compliance-controls">
                    <div class="compliance-filter-tabs">
                        <a href="?tab=sanction-compliance&status_filter=Pending" class="filter-tab-btn <?php echo ($filterComplianceStatus == 'Pending' ? 'active' : ''); ?>">Pending</a>
                        <a href="?tab=sanction-compliance&status_filter=Completed" class="filter-tab-btn <?php echo ($filterComplianceStatus == 'Completed' ? 'active' : ''); ?>">Completed</a>
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
                                <th>Student Name</th>
                                <th>Violation Type</th>
                                <th>Assigned Sanction</th>
                                <th>Deadline of Compliance</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // MODIFIED: Query now filters by status
                            $sql_ongoing = "SELECT
                                                ssr.record_id, ssr.status, ssr.deadline_date,
                                                u.student_number, u.first_name, u.middle_name, u.last_name,
                                                vt.violation_type,
                                                st.sanction_name
                                            FROM student_sanction_records_tbl ssr
                                            JOIN users_tbl u ON ssr.student_number = u.student_number
                                            JOIN violation_tbl v ON ssr.violation_id = v.violation_id
                                            JOIN violation_type_tbl vt ON v.violation_type = vt.violation_type_id
                                            JOIN sanction_type_tbl st ON ssr.assigned_sanction_id = st.sanction_id
                                            WHERE ssr.status = ?
                                            ORDER BY ssr.deadline_date ASC";

                            $stmt_ongoing = $conn->prepare($sql_ongoing);
                            $stmt_ongoing->bind_param("s", $filterComplianceStatus);
                            $stmt_ongoing->execute();
                            $result_ongoing = $stmt_ongoing->get_result();
                            
                            if ($result_ongoing && $result_ongoing->num_rows > 0) {
                                while ($row = $result_ongoing->fetch_assoc()) {
                                    $status_class = 'status-default';
                                    if ($row['status'] == 'Pending') $status_class = 'status-pending';
                                    if ($row['status'] == 'Completed') $status_class = 'status-completed';

                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['student_number']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'][0] . '. ' : '') . $row['last_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['violation_type']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['sanction_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars(date("F j, Y", strtotime($row['deadline_date']))) . "</td>";
                                    echo "<td><span class='status-badge " . $status_class . "'>" . htmlspecialchars($row['status']) . "</span></td>";
                                    
                                    // MODIFIED: Action button now toggles status
                                    echo "<td class='action-buttons-cell'>";
                                    if ($row['status'] == 'Pending') {
                                        echo "<button class='update-status-btn status-completed-btn' data-record-id='" . htmlspecialchars($row['record_id']) . "' data-student-number='" . htmlspecialchars($row['student_number']) . "' data-new-status='Completed'><i class='fas fa-check-circle'></i> Mark Completed</button>";
                                    } else {
                                        echo "<button class='update-status-btn status-pending-btn' data-record-id='" . htmlspecialchars($row['record_id']) . "' data-student-number='" . htmlspecialchars($row['student_number']) . "' data-new-status='Pending'><i class='fas fa-undo'></i> Mark as Pending</button>";
                                    }
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7' class='no-records-cell'>No " . strtolower(htmlspecialchars($filterComplianceStatus)) . " sanctions found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="sanction-config" class="tab-content" style="<?php echo ($active_tab == 'sanction-config' ? 'display: block;' : 'display: none;'); ?>">
                </div>
        <?php endif; ?> </div>

    <div id="viewSanctionDetailsModal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="head-modal">
                <h3>Manage Sanction Request</h3>
                <span class="close-modal-button" data-modal="viewSanctionDetailsModal">&times;</span>
            </div>
             <div id="approveSanctionModalMessage" class="modal-message" style="display: none;"></div>
            <form id="approveSanctionForm" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="approve_sanction" value="1">
                <input type="hidden" id="approveStudentNumber" name="student_number">
                <input type="hidden" id="approveViolationId" name="violation_id">

                <div class="details-content">
                    <p><strong>Student Number:</strong> <span id="detailStudentNumber"></span></p>
                    <p><strong>Student Name:</strong> <span id="detailStudentName"></span></p>
                    <p><strong>Violation Type:</strong> <span id="detailViolationType"></span></p>
                </div>

                <div class="row">
                    <div class="column full-width">
                        <label for="assignedSanction">Assign Sanction:</label>
                        <select id="assignedSanction" name="assigned_sanction_id" class="modal-select" required>
                            <option value="" disabled selected>Choose a sanction...</option>
                            <?php
                            $sanctions_result = $conn->query("SELECT sanction_id, sanction_name FROM sanction_type_tbl ORDER BY sanction_name ASC");
                            if ($sanctions_result) {
                                while ($sanc_row = $sanctions_result->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($sanc_row['sanction_id']) . "'>" . htmlspecialchars($sanc_row['sanction_name']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
                 <div class="row">
                    <div class="column full-width">
                        <label for="deadlineDate">Set Deadline:</label>
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

    <script src="./admin_sanction.js"></script>
</body>
</html>
<?php $conn->close(); ?>