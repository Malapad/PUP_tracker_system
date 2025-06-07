<?php
include '../PHP/dbcon.php'; // Assuming dbcon.php handles the database connection

// Active tab determination
$DEFAULT_TAB = 'sanction-request';
$active_tab = $_GET['tab'] ?? $DEFAULT_TAB;

// Filter and Search parameters
$filterViolation = $_GET['violation_type'] ?? '';
$search = trim($_GET['search_student_number'] ?? '');
$filterCourse = $_GET['filter_course'] ?? '';
$filterYear = $_GET['filter_year'] ?? '';
$filterSection = $_GET['filter_section'] ?? '';

// --- BACKEND ACTION HANDLERS ---

// Handle "Approve Sanction" from modal
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['approve_sanction'])) {
    $response = ['success' => false, 'message' => 'An unexpected error occurred.'];
    header('Content-Type: application/json');

    $student_number = $_POST['student_number'] ?? '';
    $violation_id = $_POST['violation_id'] ?? '';
    $assigned_sanction_id = $_POST['assigned_sanction_id'] ?? '';
    $deadline_date = $_POST['deadline_date'] ?? null;
    $admin_id = 1; // HARDCODED: Replace with actual logged-in admin ID from session

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
                
                // --- NEW: Deactivate the request in sanction_requests_tbl ---
                $update_req_stmt = $conn->prepare("UPDATE sanction_requests_tbl SET is_active = 0 WHERE student_number = ? AND is_active = 1");
                if ($update_req_stmt) {
                    $update_req_stmt->bind_param("s", $student_number);
                    $update_req_stmt->execute();
                    $update_req_stmt->close();
                }
                // --- END: Deactivate request ---

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


// Handle adding new sanction type
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_sanction_type'])) {
    $response = ['success' => false, 'message' => 'An unexpected error occurred.'];
    header('Content-Type: application/json');

    $sanction_name = strtoupper(trim($_POST['sanction_name'] ?? ''));
    $hours_required = $_POST['hours_required'] ?? null;

    if (empty($sanction_name)) {
        $response['message'] = 'Sanction Type name is required.';
        echo json_encode($response);
        exit;
    }
    if ($hours_required !== null && (!is_numeric($hours_required) || $hours_required < 0)) {
        $response['message'] = 'Hours must be a non-negative number.';
        echo json_encode($response);
        exit;
    }

    // Check if sanction type already exists
    $stmt_check = $conn->prepare("SELECT sanction_id FROM sanction_type_tbl WHERE sanction_name = ?");
    $stmt_check->bind_param("s", $sanction_name);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        $response['message'] = 'Error: Sanction Type "' . htmlspecialchars($sanction_name) . '" already exists.';
        echo json_encode($response);
        exit;
    }
    $stmt_check->close();

    // Insert new sanction type
    $stmt_insert = $conn->prepare("INSERT INTO sanction_type_tbl (sanction_name, hours_required) VALUES (?, ?)");
    $stmt_insert->bind_param("si", $sanction_name, $hours_required);
    if ($stmt_insert->execute()) {
        $response['success'] = true;
        $response['message'] = 'Sanction Type "' . htmlspecialchars($sanction_name) . '" added successfully.';
    } else {
        $response['message'] = 'Error adding new sanction type: ' . htmlspecialchars($stmt_insert->error);
    }
    $stmt_insert->close();

    echo json_encode($response);
    exit;
}

// Handle deleting sanction type
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_sanction_id'])) {
    $response = ['success' => false, 'message' => 'An unexpected error occurred.'];
    header('Content-Type: application/json');

    $sanction_id = $_POST['delete_sanction_id'];
    if (empty($sanction_id)) {
        $response['message'] = 'Sanction ID not provided.';
        echo json_encode($response); exit;
    }

    $delete_stmt = $conn->prepare("DELETE FROM sanction_type_tbl WHERE sanction_id = ?");
    $delete_stmt->bind_param("i", $sanction_id);
    if ($delete_stmt->execute()) {
        if ($delete_stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Sanction type deleted successfully.';
        } else {
            $response['message'] = 'Sanction type not found or already deleted.';
        }
    } else {
        $response['message'] = 'Error deleting sanction type: ' . htmlspecialchars($delete_stmt->error);
    }
    $delete_stmt->close();
    
    echo json_encode($response);
    exit;
}

// Handle updating sanction type
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_sanction_type_submit'])) {
    $response = ['success' => false, 'message' => 'An unexpected error occurred.'];
    header('Content-Type: application/json');

    $sanction_id = $_POST['edit_sanction_id'] ?? '';
    $new_sanction_name = strtoupper(trim($_POST['edit_sanction_name'] ?? ''));
    $new_hours_required = $_POST['edit_hours_required'] ?? null;

    if (empty($sanction_id) || empty($new_sanction_name)) {
        $response['message'] = 'Sanction ID and Name are required.';
        echo json_encode($response); exit;
    }
    if ($new_hours_required !== null && (!is_numeric($new_hours_required) || $new_hours_required < 0)) {
        $response['message'] = 'Hours must be a non-negative number.';
        echo json_encode($response); exit;
    }

    // Check for duplicate name
    $check_duplicate_stmt = $conn->prepare("SELECT sanction_id FROM sanction_type_tbl WHERE sanction_name = ? AND sanction_id != ?");
    $check_duplicate_stmt->bind_param("si", $new_sanction_name, $sanction_id);
    $check_duplicate_stmt->execute();
    if ($check_duplicate_stmt->get_result()->num_rows > 0) {
        $response['message'] = 'Error: Sanction Type "' . htmlspecialchars($new_sanction_name) . '" already exists.';
    } else {
        $update_stmt = $conn->prepare("UPDATE sanction_type_tbl SET sanction_name = ?, hours_required = ? WHERE sanction_id = ?");
        $update_stmt->bind_param("sii", $new_sanction_name, $new_hours_required, $sanction_id);
        if ($update_stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Sanction type updated successfully.';
        } else {
            $response['message'] = 'Error updating sanction type: ' . htmlspecialchars($update_stmt->error);
        }
        $update_stmt->close();
    }
    $check_duplicate_stmt->close();
    
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
        <h1>Student Sanction List</h1>

        <div class="tabs">
            <button class="tab <?php echo ($active_tab == 'sanction-request' ? 'active' : ''); ?>" data-tab="sanction-request"><i class="fas fa-user-graduate"></i> Sanction Request</button>
            <button class="tab <?php echo ($active_tab == 'ongoing-sanctions' ? 'active' : ''); ?>" data-tab="ongoing-sanctions"><i class="fas fa-tasks"></i> Ongoing Sanctions</button>
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
                            <th>Violation Date</th>
                            <th>Violation Type</th>
                            <th>Violation Count</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // --- MODIFIED SQL QUERY to link with sanction_requests_tbl ---
                        $sql = "SELECT
                                    u.student_number, u.first_name, u.middle_name, u.last_name,
                                    c.course_name, y.year, s.section_name,
                                    v.violation_id, v.violation_date, vt.violation_type,
                                    (SELECT COUNT(*)
                                        FROM violation_tbl v_inner
                                        JOIN violation_type_tbl vt_inner ON v_inner.violation_type = vt_inner.violation_type_id
                                        WHERE v_inner.student_number = u.student_number AND vt_inner.violation_category_id = vt.violation_category_id
                                    ) as violation_category_count
                                FROM violation_tbl v
                                JOIN users_tbl u ON v.student_number = u.student_number
                                JOIN sanction_requests_tbl req ON u.student_number = req.student_number
                                JOIN violation_type_tbl vt ON v.violation_type = vt.violation_type_id
                                LEFT JOIN course_tbl c ON u.course_id = c.course_id
                                LEFT JOIN year_tbl y ON u.year_id = y.year_id
                                LEFT JOIN section_tbl s ON u.section_id = s.section_id
                                LEFT JOIN student_sanction_records_tbl ssr ON v.violation_id = ssr.violation_id
                                WHERE ssr.record_id IS NULL 
                                AND req.is_active = 1
                                AND (SELECT COUNT(*)
                                        FROM violation_tbl v_inner
                                        JOIN violation_type_tbl vt_inner ON v_inner.violation_type = vt_inner.violation_type_id
                                        WHERE v_inner.student_number = u.student_number AND vt_inner.violation_category_id = vt.violation_category_id
                                    ) >= 2";

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
                        
                        $sql .= " GROUP BY v.violation_id ORDER BY v.violation_date DESC";

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
                                    echo "<td>" . htmlspecialchars($row['violation_category_count']) . "</td>"; // Display the new category count
                                    echo "<td><span class='status-badge status-pending'>Pending Action</span></td>";
                                    echo "<td class='action-buttons-cell'>";
                                    echo "<button class='view-manage-btn'
                                            data-student-number='" . htmlspecialchars($row['student_number']) . "'
                                            data-student-name='" . $student_full_name . "'
                                            data-violation-id='" . htmlspecialchars($row['violation_id']) . "'
                                            data-violation-type='" . htmlspecialchars($row['violation_type']) . "'
                                            ><i class='fas fa-eye'></i> View Manage</button>";
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

        <div id="ongoing-sanctions" class="tab-content" style="<?php echo ($active_tab == 'ongoing-sanctions' ? 'display: block;' : 'display: none;'); ?>">
             <div class="main-table-scroll-container">
                <table>
                    <thead>
                        <tr>
                            <th>Student Number</th>
                            <th>Student Name</th>
                            <th>Violation Type</th>
                            <th>Assigned Sanction</th>
                            <th>Deadline</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
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
                                        ORDER BY ssr.deadline_date ASC";

                        $result_ongoing = $conn->query($sql_ongoing);
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
                                echo "<td class='action-buttons-cell'>";
                                echo "<button class='button status-completed-btn' data-record-id='" . htmlspecialchars($row['record_id']) . "'><i class='fas fa-check-circle'></i> Mark Completed</button>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' class='no-records-cell'>No ongoing sanctions found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="sanction-config" class="tab-content" style="<?php echo ($active_tab == 'sanction-config' ? 'display: block;' : 'display: none;'); ?>">
            <div class="config-controls">
                <button id="addSanctionTypeBtn" class="button add-sanction-type-btn"><i class="fas fa-plus"></i> Add Sanction Type</button>
            </div>
            <div class="config-table-scroll-container">
                <table class="config-table">
                    <thead>
                        <tr>
                            <th>Sanction</th>
                            <th>Hours</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql_sanction_types = "SELECT sanction_id, sanction_name, hours_required FROM sanction_type_tbl ORDER BY sanction_name ASC";
                        $result_sanction_types = $conn->query($sql_sanction_types);

                        if ($result_sanction_types && $result_sanction_types->num_rows > 0) {
                            while ($row_sanction = $result_sanction_types->fetch_assoc()) {
                                echo "<tr data-id='" . htmlspecialchars($row_sanction['sanction_id']) . "' class='sanction-type-row'>";
                                echo "<td>" . htmlspecialchars($row_sanction['sanction_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row_sanction['hours_required'] ?? 'N/A') . "</td>";
                                echo "<td class='action-buttons-cell'>";
                                echo "<div class='action-buttons-container'>";
                                echo "<button class='edit-sanction-type-btn btn-secondary' data-id='" . htmlspecialchars($row_sanction['sanction_id']) . "' data-name='" . htmlspecialchars($row_sanction['sanction_name']) . "' data-hours='" . htmlspecialchars($row_sanction['hours_required']) . "'><i class='fas fa-edit'></i> Update</button>";
                                echo "<button class='delete-sanction-type-btn btn-danger' data-id='" . htmlspecialchars($row_sanction['sanction_id']) . "' data-name='" . htmlspecialchars($row_sanction['sanction_name']) . "'><i class='fas fa-trash-alt'></i> Delete</button>";
                                echo "</div>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                                echo "<tr><td colspan='3' class='no-records-cell'>No sanction types have been configured.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

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

    <div id="addSanctionTypeModal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="head-modal">
                <h3>Add New Sanction Type</h3>
                <span class="close-modal-button" data-modal="addSanctionTypeModal">&times;</span>
            </div>
            <div id="addSanctionTypeModalMessage" class="modal-message" style="display: none;"></div>

            <form id="addSanctionTypeForm" class="form-container" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="add_sanction_type" value="1">
                <div id="addSanctionStep1" class="modal-step" style="display: block;">
                    <div class="row">
                        <div class="column full-width">
                            <label for="newSanctionName">Sanction Type Name:</label>
                            <input type="text" id="newSanctionName" name="sanction_name" required style="text-transform: uppercase;" />
                        </div>
                    </div>
                    <div class="row">
                         <div class="column full-width">
                            <label for="newHoursRequired">Hours (if applicable):</label>
                            <input type="number" id="newHoursRequired" name="hours_required" min="0" value="0" required />
                        </div>
                    </div>
                    <div class="button-row">
                        <button type="button" id="nextToAddSanctionStep2" class="modal-button-next"><i class="fas fa-arrow-right"></i> Next</button>
                    </div>
                </div>

                <div id="addSanctionStep2" class="modal-step" style="display: none;">
                    <div class="summary-content">
                        <p>Please review the details before confirming:</p>
                        <p><strong>Sanction Type:</strong> <span id="summarySanctionName"></span></p>
                        <p><strong>Hours:</strong> <span id="summaryHoursRequired"></span></p>
                    </div>
                    <div class="button-row">
                        <button type="submit" class="modal-button-publish"><i class="fas fa-check"></i> Confirm & Publish</button>
                        <button type="button" id="backToAddSanctionStep1" class="modal-button-back"><i class="fas fa-arrow-left"></i> Back</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="editSanctionTypeModal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="head-modal">
                <h3>Edit Sanction Type</h3>
                <span class="close-modal-button" data-modal="editSanctionTypeModal">&times;</span>
            </div>
            <div id="editSanctionTypeModalMessage" class="modal-message" style="display: none;"></div>

            <form id="editSanctionTypeForm" class="form-container" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="edit_sanction_type_submit" value="1">
                <input type="hidden" id="editSanctionId" name="edit_sanction_id">
                <div class="row">
                    <div class="column full-width">
                        <label for="editSanctionName">Sanction Type Name:</label>
                        <input type="text" id="editSanctionName" name="edit_sanction_name" required style="text-transform: uppercase;" />
                    </div>
                </div>
                <div class="row">
                    <div class="column full-width">
                        <label for="editHoursRequired">Hours:</label>
                        <input type="number" id="editHoursRequired" name="edit_hours_required" min="0" required />
                    </div>
                </div>
                <div class="button-row">
                    <button type="submit" class="modal-button-publish"><i class="fas fa-save"></i> Save Changes</button>
                    <button type="button" class="modal-button-cancel close-modal-button" data-modal="editSanctionTypeModal"><i class="fas fa-times"></i> Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteSanctionTypeModal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="head-modal">
                <h3>Delete Sanction Type</h3>
                <span class="close-modal-button" data-modal="deleteSanctionTypeModal">&times;</span>
            </div>
            <div id="deleteSanctionTypeModalMessage" class="modal-message" style="display: none;"></div>
            <div class="confirmation-content">
                <p>Are you sure you want to delete this Sanction Type?</p>
                <p><strong>Sanction Type:</strong> <span id="deleteSanctionTypeDisplay"></span></p>
            </div>
            <div class="button-row">
                <button type="button" id="confirmDeleteSanctionTypeBtn" class="btn-confirm-delete"><i class="fas fa-check"></i> Confirm Delete</button>
                <button type="button" class="modal-button-cancel close-modal-button" data-modal="deleteSanctionTypeModal"><i class="fas fa-times"></i> Cancel</button>
            </div>
        </div>
    </div>

    <script src="./admin_sanction.js"></script>
</body>
</html>
<?php $conn->close(); ?>