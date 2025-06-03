<?php
include '../PHP/dbcon.php'; // Assuming dbcon.php handles the database connection

$filterViolation = $_GET['violation_type'] ?? '';
$search = trim($_GET['search_student_number'] ?? '');
$filterCourse = $_GET['filter_course'] ?? '';
$filterYear = $_GET['filter_year'] ?? '';
$filterSection = $_GET['filter_section'] ?? '';
$active_tab = $_GET['tab'] ?? 'sanction-request';

// Handle adding new sanction type
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_sanction_type'])) {
    $response = ['success' => false, 'message' => 'An unexpected error occurred.'];

    $sanction_name = strtoupper(trim($_POST['sanction_name'] ?? ''));
    $hours_required = $_POST['hours_required'] ?? null; // Capture hours_required

    if (empty($sanction_name)) {
        $response['message'] = 'Sanction Type name is required.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // Validate hours_required
    if ($hours_required !== null && (!is_numeric($hours_required) || $hours_required < 0)) {
        $response['message'] = 'Hours must be a non-negative number.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // Check if sanction type already exists
    $stmt_check = $conn->prepare("SELECT sanction_id FROM sanction_type_tbl WHERE sanction_name = ?");
    if ($stmt_check) {
        $stmt_check->bind_param("s", $sanction_name);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows > 0) {
            $response['message'] = 'Error: Sanction Type "' . htmlspecialchars($sanction_name) . '" already exists.';
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
        $stmt_check->close();
    } else {
        $response['message'] = 'Error preparing check statement: ' . htmlspecialchars($conn->error);
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // Insert new sanction type with hours_required
    $stmt_insert = $conn->prepare("INSERT INTO sanction_type_tbl (sanction_name, hours_required) VALUES (?, ?)");
    if ($stmt_insert) {
        // Use 'i' for integer hours, 'd' for double/float if decimals are allowed
        $stmt_insert->bind_param("si", $sanction_name, $hours_required);
        if ($stmt_insert->execute()) {
            $response['success'] = true;
            $response['message'] = 'Sanction Type "' . htmlspecialchars($sanction_name) . '" added successfully.';
        } else {
            $response['message'] = 'Error adding new sanction type: ' . htmlspecialchars($stmt_insert->error);
        }
        $stmt_insert->close();
    } else {
        $response['message'] = 'Error preparing insert statement: ' . htmlspecialchars($conn->error);
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Handle deleting sanction type
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_sanction_id'])) {
    $response = ['success' => false, 'message' => 'An unexpected error occurred.'];
    $sanction_id = $_POST['delete_sanction_id'];

    if (empty($sanction_id)) {
        $response['message'] = 'Sanction ID not provided.';
        header('Content-Type: application/json'); echo json_encode($response); exit;
    }

    $delete_stmt = $conn->prepare("DELETE FROM sanction_type_tbl WHERE sanction_id = ?");
    if ($delete_stmt) {
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
    } else {
        $response['message'] = 'Error preparing delete statement: ' . htmlspecialchars($conn->error);
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Handle fetching sanction type details for modals (for edit/delete)
if (isset($_GET['action']) && $_GET['action'] == 'get_sanction_type_details' && isset($_GET['id'])) {
    $response = ['success' => false, 'message' => 'Details not found.', 'data' => null];
    $sanction_id = $_GET['id'];

    if (empty($sanction_id)) {
        $response['message'] = 'Sanction ID not provided.';
        header('Content-Type: application/json'); echo json_encode($response); exit;
    }

    $sql_details = "SELECT sanction_id, sanction_name, hours_required FROM sanction_type_tbl WHERE sanction_id = ?";
    $stmt_details = $conn->prepare($sql_details);
    if ($stmt_details) {
        $stmt_details->bind_param("i", $sanction_id);
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

// Handle updating sanction type
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_sanction_type_submit'])) {
    $response = ['success' => false, 'message' => 'An unexpected error occurred.'];

    $sanction_id = $_POST['edit_sanction_id'] ?? '';
    $new_sanction_name = strtoupper(trim($_POST['edit_sanction_name'] ?? ''));
    $new_hours_required = $_POST['edit_hours_required'] ?? null; // Capture new hours

    if (empty($sanction_id) || empty($new_sanction_name)) {
        $response['message'] = 'Sanction ID and Name are required.';
        header('Content-Type: application/json'); echo json_encode($response); exit;
    }

    // Validate new_hours_required
    if ($new_hours_required !== null && (!is_numeric($new_hours_required) || $new_hours_required < 0)) {
        $response['message'] = 'Hours must be a non-negative number.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // Check for duplicate sanction type (excluding current sanction_id)
    $check_duplicate_stmt = $conn->prepare("SELECT sanction_id FROM sanction_type_tbl WHERE sanction_name = ? AND sanction_id != ?");
    if ($check_duplicate_stmt) {
        $check_duplicate_stmt->bind_param("si", $new_sanction_name, $sanction_id);
        $check_duplicate_stmt->execute();
        $duplicate_result = $check_duplicate_stmt->get_result();
        if ($duplicate_result->num_rows > 0) {
            $response['message'] = 'Error: Sanction Type "' . htmlspecialchars($new_sanction_name) . '" already exists.';
        } else {
            // Update both sanction_name and hours_required
            $update_stmt = $conn->prepare("UPDATE sanction_type_tbl SET sanction_name = ?, hours_required = ? WHERE sanction_id = ?");
            if ($update_stmt) {
                $update_stmt->bind_param("sii", $new_sanction_name, $new_hours_required, $sanction_id); // s i i
                if ($update_stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Sanction type updated successfully.';
                } else {
                    $response['message'] = 'Error updating sanction type: ' . htmlspecialchars($update_stmt->error);
                }
                $update_stmt->close();
            } else {
                $response['message'] = 'Error preparing update statement: ' . htmlspecialchars($conn->error);
            }
        }
        $check_duplicate_stmt->close();
    } else {
        $response['message'] = 'Error preparing duplicate check statement: ' . htmlspecialchars($conn->error);
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
    <link rel="stylesheet" href="./admin_sanction_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <div id="toast-notification" class="toast"></div>

    <header>
        <div class="logo">
            <img src="../assets/PUPlogo.png" alt="PUP Logo">
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
    </header>

    <div class="container">
        <h1>Student Sanction List</h1>

        <div class="tabs">
            <button class="tab <?php echo ($active_tab == 'sanction-request' ? 'active' : ''); ?>" data-tab="sanction-request"><i class="fas fa-user-graduate"></i> Sanction Request</button>
            <button class="tab <?php echo ($active_tab == 'sanction-config' ? 'active' : ''); ?>" data-tab="sanction-config"><i class="fas fa-cogs"></i> Sanction Configuration</button>
        </div>

        <div id="sanction-request" class="tab-content" style="<?php echo ($active_tab == 'sanction-request' ? 'display: block;' : 'display: none;'); ?>">
            <?php
            // Display clear filters button if any filters are active
            if ($active_tab == 'sanction-request' && (!empty($filterViolation) || !empty($search) || !empty($filterCourse) || !empty($filterYear) || !empty($filterSection))) {
                $baseUrl = strtok($_SERVER["REQUEST_URI"], '?');
                echo '<div class="clear-filters-container">';
                echo '     <a href="' . htmlspecialchars($baseUrl) . '?tab=sanction-request" class="clear-filters-btn"><i class="fas fa-eraser"></i> Clear Filters</a>';
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
                                    // Uncomment this PHP block to populate courses from your database
                                    $courseQuery = "SELECT course_id, course_name FROM course_tbl ORDER BY course_name ASC";
                                    $courseResult = $conn->query($courseQuery);
                                    if ($courseResult) {
                                        while ($rowCourse = $courseResult->fetch_assoc()) {
                                            echo "<option value='" . htmlspecialchars($rowCourse['course_id']) . "' " . (($filterCourse == $rowCourse['course_id']) ? 'selected' : '') . ">" . htmlspecialchars($rowCourse['course_name']) . "</option>";
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
                                    // Uncomment this PHP block to populate years from your database
                                    $yearQuery = "SELECT year_id, year FROM year_tbl ORDER BY year ASC";
                                    $yearResult = $conn->query($yearQuery);
                                    if ($yearResult) {
                                        while ($rowYear = $yearResult->fetch_assoc()) {
                                            echo "<option value='" . htmlspecialchars($rowYear['year_id']) . "' " . (($filterYear == $rowYear['year_id']) ? 'selected' : '') . ">" . htmlspecialchars($rowYear['year']) . "</option>";
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
                                    // Uncomment this PHP block to populate sections from your database
                                    $sectionQuery = "SELECT section_id, section_name FROM section_tbl ORDER BY section_name ASC";
                                    $sectionResult = $conn->query($sectionQuery);
                                    if ($sectionResult) {
                                        while ($rowSection = $sectionResult->fetch_assoc()) {
                                            echo "<option value='" . htmlspecialchars($rowSection['section_id']) . "' " . (($filterSection == $rowSection['section_id']) ? 'selected' : '') . ">" . htmlspecialchars($rowSection['section_name']) . "</option>";
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
                                    $vtQuery = "SELECT violation_type_id, violation_type FROM violation_type_tbl ORDER BY violation_type ASC";
                                    $vtResult = $conn->query($vtQuery);
                                    if ($vtResult) {
                                        while ($rowVt = $vtResult->fetch_assoc()) {
                                            echo "<option value='" . htmlspecialchars($rowVt['violation_type_id']) . "' " . (($filterViolation == $rowVt['violation_type_id']) ? 'selected' : '') . ">" . htmlspecialchars($rowVt['violation_type']) . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                                <span class="select-arrow"></span>
                            </div>
                        </div>

                        <div class="search-group">
                            <div class="search-input-wrapper">
                                <input
                                    type="text"
                                    id="searchInputNew"
                                    name="search_student_number"
                                    placeholder="Search by Student Number"
                                    value="<?php echo htmlspecialchars($search); ?>"
                                    class="search-input-field"
                                />
                                <button type="submit" class="search-button-new"><i class="fas fa-search"></i> Search</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="main-table-scroll-container">
                <div class="table-overlay-spinner" id="tableSpinner" style="display: none;">
                    <div class="spinner"></div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Student Number</th>
                            <th>Student Name</th>
                            <th>Course</th>
                            <th>Year</th>
                            <th>Date Submitted</th>
                            <th>Violation Type</th>
                            <th>Offense</th>
                            <th>Sanction</th>
                            <th>Date of Deadline</th>
                            <th>Status</th>
                            <th>Actions</th> </tr>
                    </thead>
                    <tbody>
                        <?php
                        // PHP logic to fetch and display sanction requests
                        $sql = "SELECT
                                        u.student_number,
                                        u.first_name,
                                        u.middle_name,
                                        u.last_name,
                                        c.course_name,
                                        y.year,
                                        s.section_name, -- Added section_name
                                        v.violation_date,
                                        vt.violation_type,
                                        COUNT(v.violation_id) OVER (PARTITION BY u.student_number, vt.violation_type_id) as specific_violation_count,
                                        (SELECT COUNT(*) FROM violation_tbl WHERE student_number = u.student_number) as total_violations_student
                                    FROM
                                        users_tbl u
                                    JOIN
                                        violation_tbl v ON u.student_number = v.student_number
                                    JOIN
                                        violation_type_tbl vt ON v.violation_type = vt.violation_type_id
                                    LEFT JOIN
                                        course_tbl c ON u.course_id = c.course_id
                                    LEFT JOIN
                                        year_tbl y ON u.year_id = y.year_id
                                    LEFT JOIN
                                        section_tbl s ON u.section_id = s.section_id -- Joined section table
                                    WHERE 1=1"; // Start with a true condition for easy appending

                        $params = [];
                        $paramTypes = "";

                        if (!empty($filterViolation)) {
                            $sql .= " AND vt.violation_type_id = ?";
                            $params[] = $filterViolation;
                            $paramTypes .= "i";
                        }
                        if (!empty($filterCourse)) {
                            $sql .= " AND c.course_id = ?";
                            $params[] = $filterCourse;
                            $paramTypes .= "i";
                        }
                        if (!empty($filterYear)) {
                            $sql .= " AND y.year_id = ?";
                            $params[] = $filterYear;
                            $paramTypes .= "i";
                        }
                        if (!empty($filterSection)) {
                            $sql .= " AND s.section_id = ?";
                            $params[] = $filterSection;
                            $paramTypes .= "i";
                        }

                        if (!empty($search)) {
                            $sql .= " AND (u.student_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
                            $params[] = "%{$search}%";
                            $params[] = "%{$search}%";
                            $params[] = "%{$search}%";
                            $paramTypes .= "sss";
                        }

                        $sql .= " ORDER BY v.violation_date DESC";

                        $stmt = $conn->prepare($sql);
                        if ($stmt) {
                            if (!empty($params)) {
                                // Dynamically bind parameters
                                $stmt->bind_param($paramTypes, ...$params);
                            }
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result->num_rows > 0) {
                                $displayed_students = []; // To prevent duplicate rows for the same student and violation type
                                while ($row = $result->fetch_assoc()) {
                                    $combined_key = $row['student_number'] . '-' . $row['violation_type'];
                                    if (in_array($combined_key, $displayed_students)) {
                                        continue; // Skip if already displayed
                                    }
                                    $displayed_students[] = $combined_key;

                                    $offense = ($row['specific_violation_count'] >= 2) ? 'Sanction' : 'Warning';
                                    $status_class = ($offense == 'Sanction') ? 'status-pending' : 'status-warning'; // Default pending for sanction, warning for warning
                                    $sanction_text = ($offense == 'Sanction') ? 'Pending Action' : 'N/A'; // Placeholder
                                    $deadline_text = ($offense == 'Sanction') ? 'TBD' : 'N/A'; // Placeholder


                                    echo "<tr data-student-number='" . htmlspecialchars($row['student_number']) . "'>";
                                    echo "<td>" . htmlspecialchars($row['student_number']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'][0] . '. ' : '') . $row['last_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['course_name'] ?? 'N/A') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['year'] ?? 'N/A') . "</td>";
                                    echo "<td>" . htmlspecialchars(date("F j, Y", strtotime($row['violation_date']))) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['violation_type']) . "</td>";
                                    echo "<td>" . htmlspecialchars($offense) . "</td>";
                                    echo "<td>" . htmlspecialchars($sanction_text) . "</td>";
                                    echo "<td>" . htmlspecialchars($deadline_text) . "</td>";
                                    echo "<td><span class='status-badge " . $status_class . "'>" . htmlspecialchars(($offense == 'Sanction' ? 'Pending' : 'Warning')) . "</span></td>";
                                    // New action button for Sanction Request rows
                                    echo "<td class='action-buttons-cell'>";
                                    echo "<button class='view-sanction-details-btn btn-secondary'
                                        data-student-number='" . htmlspecialchars($row['student_number']) . "'
                                        data-student-name='" . htmlspecialchars($row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'][0] . '. ' : '') . $row['last_name']) . "'
                                        data-course='" . htmlspecialchars($row['course_name'] ?? 'N/A') . "'
                                        data-year='" . htmlspecialchars($row['year'] ?? 'N/A') . "'
                                        data-date-submitted='" . htmlspecialchars(date("F j, Y", strtotime($row['violation_date']))) . "'
                                        data-violation-type='" . htmlspecialchars($row['violation_type']) . "'
                                        data-offense='" . htmlspecialchars($offense) . "'
                                        data-sanction='" . htmlspecialchars($sanction_text) . "'
                                        data-deadline='" . htmlspecialchars($deadline_text) . "'
                                        data-status-text='" . htmlspecialchars(($offense == 'Sanction' ? 'Pending' : 'Warning')) . "'
                                        data-status-class='" . htmlspecialchars($status_class) . "'
                                        ><i class='fas fa-eye'></i> View/Manage</button>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='11' class='no-records-cell'>"; // Updated colspan
                                echo "No sanction requests or warnings match your current selection. <br>Please try adjusting your search or filter criteria.";
                                echo "</td></tr>";
                            }
                        } else {
                            echo "<tr><td colspan='11' class='no-records-cell'>Database query error.</td></tr>"; // Updated colspan
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
                            <th>Hours</th> <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Hardcoded sanction types - These will now have 'Hours' as well
                        // NOTE: You should consider fetching all sanction types from DB for consistency
                        $default_sanctions = [
                            ['id' => 'default_1', 'name' => '3 Hours Community Service', 'hours' => 3],
                            ['id' => 'default_2', 'name' => '6 Hours Community Service', 'hours' => 6],
                            ['id' => 'default_3', 'name' => '1 Week Suspension', 'hours' => 0], // Assuming 0 hours for suspension types
                            ['id' => 'default_4', 'name' => '2 Week Suspension', 'hours' => 0],
                            ['id' => 'default_5', 'name' => '1 Month Suspension', 'hours' => 0],
                        ];

                        foreach ($default_sanctions as $sanction) {
                            echo "<tr data-id='" . htmlspecialchars($sanction['id']) . "' class='sanction-type-row'>";
                            echo "<td>" . htmlspecialchars($sanction['name']) . "</td>";
                            echo "<td>" . htmlspecialchars($sanction['hours']) . "</td>"; // Display hours
                            echo "<td class='action-buttons-cell'>";
                            echo "<div class='action-buttons-container'>"; // No style='display:none;'
                            echo "<button class='edit-sanction-type-btn btn-secondary' data-id='" . htmlspecialchars($sanction['id']) . "' data-name='" . htmlspecialchars($sanction['name']) . "' data-hours='" . htmlspecialchars($sanction['hours']) . "'><i class='fas fa-edit'></i> Update</button>";
                            echo "<button class='delete-sanction-type-btn btn-danger' data-id='" . htmlspecialchars($sanction['id']) . "' data-name='" . htmlspecialchars($sanction['name']) . "'><i class='fas fa-trash-alt'></i> Delete</button>";
                            echo "</div>";
                            echo "</td>";
                            echo "</tr>";
                        }

                        // Fetch dynamically added sanction types from the database
                        $sql_sanction_types = "SELECT sanction_id, sanction_name, hours_required FROM sanction_type_tbl ORDER BY sanction_name ASC";
                        $result_sanction_types = $conn->query($sql_sanction_types);

                        if ($result_sanction_types && $result_sanction_types->num_rows > 0) {
                            while ($row_sanction = $result_sanction_types->fetch_assoc()) {
                                echo "<tr data-id='" . htmlspecialchars($row_sanction['sanction_id']) . "' class='sanction-type-row'>";
                                echo "<td>" . htmlspecialchars($row_sanction['sanction_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row_sanction['hours_required'] ?? 'N/A') . "</td>"; // Display hours from DB
                                echo "<td class='action-buttons-cell'>";
                                echo "<div class='action-buttons-container'>"; // No style='display:none;'
                                echo "<button class='edit-sanction-type-btn btn-secondary' data-id='" . htmlspecialchars($row_sanction['sanction_id']) . "' data-name='" . htmlspecialchars($row_sanction['sanction_name']) . "' data-hours='" . htmlspecialchars($row_sanction['hours_required']) . "'><i class='fas fa-edit'></i> Update</button>";
                                echo "<button class='delete-sanction-type-btn btn-danger' data-id='" . htmlspecialchars($row_sanction['sanction_id']) . "' data-name='" . htmlspecialchars($row_sanction['sanction_name']) . "'><i class='fas fa-trash-alt'></i> Delete</button>";
                                echo "</div>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            // Only show this message if no dynamic types and no default types are shown (unlikely with default types always present)
                            // echo "<tr><td colspan='2' class='no-records-cell'>No custom sanction types added.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
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
                            <label for="newHoursRequired">Hours:</label>
                            <input type="number" id="newHoursRequired" name="hours_required" min="0" value="0" required />
                        </div>
                    </div>
                    <div class="button-row">
                        <button type="button" id="nextToAddSanctionStep2" class="modal-button-next"><i class="fas fa-arrow-right"></i> Next</button>
                        <button type="button" id="cancelAddSanctionStep1" class="modal-button-cancel"><i class="fas fa-times"></i> Cancel</button>
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
                        <button type="button" id="cancelAddSanctionStep2" class="modal-button-cancel"><i class="fas fa-times"></i> Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="viewSanctionDetailsModal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="head-modal">
                <h3>Sanction Details</h3>
                </div>
            <div class="details-content">
                <p><strong>Student Number:</strong> <span id="detailStudentNumber"></span></p>
                <p><strong>Student Name:</strong> <span id="detailStudentName"></span></p>
                <p><strong>Course:</strong> <span id="detailCourse"></span></p>
                <p><strong>Year:</strong> <span id="detailYear"></span></p>
                <p><strong>Date Submitted:</strong> <span id="detailDateSubmitted"></span></p>
                <p><strong>Violation Type:</strong> <span id="detailViolationType"></span></p>
                <p><strong>Offense:</strong> <span id="detailOffense"></span></p>
                <p><strong>Sanction:</strong> <span id="detailSanction"></span></p>
                <p><strong>Date of Deadline:</strong> <span id="detailDeadline"></span></p>
                <p><strong>Status:</strong> <span id="detailSanctionStatus" class="status-badge"></span></p>

                <div class="detail-actions">
                    <button class="button status-completed-btn" onclick="alert('Mark as Completed action needed for student ' + document.getElementById('detailStudentNumber').textContent + ' for violation ' + document.getElementById('detailViolationType').textContent);"><i class="fas fa-check-circle"></i> Mark as Completed</button>
                    <button class="button status-pending-btn" onclick="alert('Mark as Pending action needed for student ' + document.getElementById('detailStudentNumber').textContent + ' for violation ' + document.getElementById('detailViolationType').textContent);"><i class="fas fa-hourglass-half"></i> Mark as Pending</button>
                </div>
            </div>
            <div class="button-row">
                <button type="button" class="modal-button-cancel close-modal-button" data-modal="viewSanctionDetailsModal"><i class="fas fa-times"></i> Close</button>
            </div>
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