<?php
include '../PHP/dbcon.php';

$filterViolation = $_GET['violation_type'] ?? '';
$filterCourse = $_GET['course_id'] ?? '';
$filterYear = $_GET['year_id'] ?? '';
$search = trim($_GET['search'] ?? '');
$active_tab = $_GET['tab'] ?? 'Violation'; // New: To manage active tab state

// --- START POST HANDLING FOR ADDING STUDENT VIOLATION (EXISTING FUNCTIONALITY) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['studentNumber'])) {
    $response = ['success' => false, 'message' => 'An unexpected error occurred.'];

    $studentNumber = trim($_POST['studentNumber'] ?? '');
    $violationTypeId = trim($_POST['violationType'] ?? '');

    if (empty($studentNumber) || empty($violationTypeId)) {
        $response['message'] = 'Please fill in all required fields.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    $checkStudentSql = "SELECT student_number FROM users_tbl WHERE student_number = ?";
    $checkStmt = $conn->prepare($checkStudentSql);
    if ($checkStmt) {
        $checkStmt->bind_param("s", $studentNumber);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $stmt = $conn->prepare("INSERT INTO violation_tbl (student_number, violation_type, violation_date) VALUES (?, ?, NOW())");
            if ($stmt) {
                $stmt->bind_param("si", $studentNumber, $violationTypeId);
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Violation added successfully.';

                    $violationTypeName = "Unknown Violation";
                    $sql_get_vt_name = "SELECT violation_type FROM violation_type_tbl WHERE violation_type_id = ? LIMIT 1";
                    if ($stmt_vt_name = $conn->prepare($sql_get_vt_name)) {
                        $stmt_vt_name->bind_param("i", $violationTypeId);
                        $stmt_vt_name->execute();
                        $result_vt_name = $stmt_vt_name->get_result();
                        if ($row_vt_name = $result_vt_name->fetch_assoc()) {
                            $violationTypeName = $row_vt_name['violation_type'];
                        }
                        $stmt_vt_name->close();
                    } else {
                        error_log("Error preparing statement to get violation type name: " . $conn->error);
                    }

                    $notification_message = "You have a new violation: " . htmlspecialchars($violationTypeName);
                    $notification_link = "./student_record.php";

                    $sql_notify = "INSERT INTO notifications_tbl (student_number, message, link) VALUES (?, ?, ?)";
                    if ($stmt_notify = $conn->prepare($sql_notify)) {
                        $stmt_notify->bind_param("sss", $studentNumber, $notification_message, $notification_link);
                        if (!$stmt_notify->execute()) {
                            error_log("Error creating notification for student " . $studentNumber . ": " . $stmt_notify->error);
                        }
                        $stmt_notify->close();
                    } else {
                        error_log("Error preparing notification query for student " . $studentNumber . ": " . $conn->error);
                    }

                } else {
                    $response['message'] = 'Error adding violation: ' . htmlspecialchars($stmt->error);
                }
                $stmt->close();
            } else {
                $response['message'] = 'Error preparing statement for insert: ' . htmlspecialchars($conn->error);
            }
        } else {
            $response['message'] = 'Error: Student Number does not exist.';
        }
        $checkStmt->close();
    } else {
        $response['message'] = 'Error preparing statement for student check: ' . htmlspecialchars($conn->error);
    }

    if (isset($_POST['ajax_submit'])) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } else {
        if ($response['success']) {
            echo "<script>alert('" . addslashes($response['message']) . "'); window.location.href = '" . htmlspecialchars($_SERVER['PHP_SELF']) . "';</script>";
        } else {
            echo "<script>alert('" . addslashes($response['message']) . "'); window.history.back();</script>";
        }
        exit;
    }
}
// --- END POST HANDLING FOR ADDING STUDENT VIOLATION (EXISTING FUNCTIONALITY) ---


// --- START POST HANDLING FOR ADDING NEW VIOLATION CATEGORY + FIRST TYPE (NEW Multi-Step) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_new_category_and_type'])) {
    $response = ['success' => false, 'message' => 'An unexpected error occurred.'];

    $new_category_name = strtoupper(trim($_POST['new_category_name'] ?? ''));
    $new_resolution_number = strtoupper(trim($_POST['new_resolution_number_cat_modal'] ?? ''));
    $new_violation_type = strtoupper(trim($_POST['new_violation_type_cat_modal'] ?? ''));
    $new_violation_description = trim($_POST['new_violation_description_cat_modal'] ?? '');

    if (empty($new_category_name) || empty($new_resolution_number) || empty($new_violation_type) || empty($new_violation_description)) {
        $response['message'] = 'All fields are required.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // Check if category already exists
    $category_id = null;
    $stmt_check_category = $conn->prepare("SELECT violation_category_id FROM violation_category_tbl WHERE category_name = ?");
    if ($stmt_check_category) {
        $stmt_check_category->bind_param("s", $new_category_name);
        $stmt_check_category->execute();
        $result_check_category = $stmt_check_category->get_result();
        if ($row_category = $result_check_category->fetch_assoc()) {
            $response['message'] = 'Error: Violation Category "' . htmlspecialchars($new_category_name) . '" already exists.';
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
        $stmt_check_category->close();
    } else {
        $response['message'] = 'Error preparing category check statement: ' . htmlspecialchars($conn->error);
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // Insert new category
    $stmt_insert_category = $conn->prepare("INSERT INTO violation_category_tbl (category_name) VALUES (?)");
    if ($stmt_insert_category) {
        $stmt_insert_category->bind_param("s", $new_category_name);
        if ($stmt_insert_category->execute()) {
            $category_id = $conn->insert_id;
        } else {
            $response['message'] = 'Error creating new category: ' . htmlspecialchars($stmt_insert_category->error);
            header('Content-Type: application/json'); echo json_encode($response); exit;
        }
        $stmt_insert_category->close();
    } else {
        $response['message'] = 'Error preparing category insert statement: ' . htmlspecialchars($conn->error);
        header('Content-Type: application/json'); echo json_encode($response); exit;
    }

    if ($category_id) {
        // Check if violation type already exists (globally, even if new category)
        $check_type_stmt = $conn->prepare("SELECT violation_type_id FROM violation_type_tbl WHERE violation_type = ?");
        if ($check_type_stmt) {
            $check_type_stmt->bind_param("s", $new_violation_type);
            $check_type_stmt->execute();
            $check_type_result = $check_type_stmt->get_result();
            if ($check_type_result->num_rows > 0) {
                // If type exists, and it's a new category, this is a logic conflict.
                // For simplicity, we'll prevent adding it if the type name is already taken.
                $response['message'] = 'Error: Violation Type "' . htmlspecialchars($new_violation_type) . '" already exists (try adding it to an existing category).';
            } else {
                // Insert the first violation type into the new category
                $insert_type_stmt = $conn->prepare("INSERT INTO violation_type_tbl (violation_type, resolution_number, violation_description, violation_category_id, date_published) VALUES (?, ?, ?, ?, CURDATE())");
                if ($insert_type_stmt) {
                    $insert_type_stmt->bind_param("sssi", $new_violation_type, $new_resolution_number, $new_violation_description, $category_id);
                    if ($insert_type_stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'Successfully Published a new Violation Category and Type.';
                    } else {
                        $response['message'] = 'Error adding first violation type: ' . htmlspecialchars($insert_type_stmt->error);
                    }
                    $insert_type_stmt->close();
                } else {
                    $response['message'] = 'Error preparing type insert statement: ' . htmlspecialchars($conn->error);
                }
            }
            $check_type_stmt->close();
        } else {
            $response['message'] = 'Error preparing type existence check: ' . htmlspecialchars($conn->error);
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
// --- END POST HANDLING FOR ADDING NEW VIOLATION CATEGORY + FIRST TYPE (NEW Multi-Step) ---


// --- START POST HANDLING FOR ADDING VIOLATION TYPE TO EXISTING CATEGORY (NEW Single-Step) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_type_to_existing_category'])) {
    $response = ['success' => false, 'message' => 'An unexpected error occurred.'];

    $new_resolution_number = strtoupper(trim($_POST['new_resolution_number_type_modal'] ?? ''));
    $new_violation_type = strtoupper(trim($_POST['new_violation_type_type_modal'] ?? '')); // Forced uppercase by JS
    $new_violation_description = trim($_POST['new_violation_description_type_modal'] ?? '');
    $existing_category_name = strtoupper(trim($_POST['existing_category_name'] ?? '')); // Hidden input

    if (empty($new_resolution_number) || empty($new_violation_type) || empty($new_violation_description) || empty($existing_category_name)) {
        $response['message'] = 'All fields are required.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // Get category ID from existing category name
    $category_id = null;
    $stmt_get_category = $conn->prepare("SELECT violation_category_id FROM violation_category_tbl WHERE category_name = ?");
    if ($stmt_get_category) {
        $stmt_get_category->bind_param("s", $existing_category_name);
        $stmt_get_category->execute();
        $result_get_category = $stmt_get_category->get_result();
        if ($row_category = $result_get_category->fetch_assoc()) {
            $category_id = $row_category['violation_category_id'];
        } else {
            // This should ideally not happen if button is clicked from existing category, but good to check.
            $response['message'] = 'Error: Selected category not found.';
            header('Content-Type: application/json'); echo json_encode($response); exit;
        }
        $stmt_get_category->close();
    } else {
        $response['message'] = 'Error preparing category lookup statement: ' . htmlspecialchars($conn->error);
        header('Content-Type: application/json'); echo json_encode($response); exit;
    }

    if ($category_id) {
        // Check if violation type already exists
        $check_stmt = $conn->prepare("SELECT violation_type_id FROM violation_type_tbl WHERE violation_type = ?");
        if ($check_stmt) {
            $check_stmt->bind_param("s", $new_violation_type);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $response['message'] = 'Error: Violation Type "' . htmlspecialchars($new_violation_type) . '" already exists.';
            } else {
                // Insert new violation type
                $insert_stmt = $conn->prepare("INSERT INTO violation_type_tbl (violation_type, resolution_number, violation_description, violation_category_id, date_published) VALUES (?, ?, ?, ?, CURDATE())");
                if ($insert_stmt) {
                    $insert_stmt->bind_param("sssi", $new_violation_type, $new_resolution_number, $new_violation_description, $category_id);
                    if ($insert_stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'Successfully Published a new Violation Type.'; // Specific message
                    } else {
                        $response['message'] = 'Error adding new violation type: ' . htmlspecialchars($insert_stmt->error);
                    }
                    $insert_stmt->close();
                } else {
                    $response['message'] = 'Error preparing statement to add violation type: ' . htmlspecialchars($conn->error);
                }
            }
            $check_stmt->close();
        } else {
            $response['message'] = 'Error preparing statement to check violation type existence: ' . htmlspecialchars($conn->error);
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
// --- END POST HANDLING FOR ADDING VIOLATION TYPE TO EXISTING CATEGORY (NEW Single-Step) ---


// --- START POST HANDLING FOR EDITING VIOLATION TYPE (EXISTING, BUT REVISED) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_violation_type_submit'])) {
    $response = ['success' => false, 'message' => 'An unexpected error occurred.'];

    $violation_type_id = $_POST['violation_type_id'] ?? '';
    $new_resolution_number = strtoupper(trim($_POST['edit_resolution_number_config'] ?? ''));
    $new_violation_type = strtoupper(trim($_POST['edit_violation_type_config'] ?? '')); // Forced uppercase by JS
    $new_violation_description = trim($_POST['edit_violation_description_config'] ?? '');
    $new_violation_category_name = strtoupper(trim($_POST['edit_violation_category_config'] ?? ''));

    if (empty($violation_type_id) || empty($new_resolution_number) || empty($new_violation_type) || empty($new_violation_description) || empty($new_violation_category_name)) {
        $response['message'] = 'All fields are required.';
        header('Content-Type: application/json'); echo json_encode($response); exit;
    }

    // Get or create violation category ID
    $category_id = null;
    $stmt_get_category = $conn->prepare("SELECT violation_category_id FROM violation_category_tbl WHERE category_name = ?");
    if ($stmt_get_category) {
        $stmt_get_category->bind_param("s", $new_violation_category_name);
        $stmt_get_category->execute();
        $result_get_category = $stmt_get_category->get_result();
        if ($row_category = $result_get_category->fetch_assoc()) {
            $category_id = $row_category['violation_category_id'];
        } else {
            // Category does not exist, insert it
            $stmt_insert_category = $conn->prepare("INSERT INTO violation_category_tbl (category_name) VALUES (?)");
            if ($stmt_insert_category) {
                $stmt_insert_category->bind_param("s", $new_violation_category_name);
                if ($stmt_insert_category->execute()) {
                    $category_id = $conn->insert_id;
                } else {
                    $response['message'] = 'Error creating new category during edit: ' . htmlspecialchars($stmt_insert_category->error);
                    header('Content-Type: application/json'); echo json_encode($response); exit;
                }
                $stmt_insert_category->close();
            } else {
                $response['message'] = 'Error preparing category insert statement during edit: ' . htmlspecialchars($conn->error);
                header('Content-Type: application/json'); echo json_encode($response); exit;
            }
        }
        $stmt_get_category->close();
    } else {
        $response['message'] = 'Error preparing category check statement during edit: ' . htmlspecialchars($conn->error);
        header('Content-Type: application/json'); echo json_encode($response); exit;
    }

    if ($category_id) {
        // Check for duplicate violation type (excluding current violation_type_id)
        $check_duplicate_stmt = $conn->prepare("SELECT violation_type_id FROM violation_type_tbl WHERE violation_type = ? AND violation_type_id != ?");
        if ($check_duplicate_stmt) {
            $check_duplicate_stmt->bind_param("si", $new_violation_type, $violation_type_id);
            $check_duplicate_stmt->execute();
            $duplicate_result = $check_duplicate_stmt->get_result();
            if ($duplicate_result->num_rows > 0) {
                $response['message'] = 'Error: Violation Type "' . htmlspecialchars($new_violation_type) . '" already exists for another entry.';
            } else {
                $update_stmt = $conn->prepare("UPDATE violation_type_tbl SET resolution_number = ?, violation_type = ?, violation_description = ?, violation_category_id = ? WHERE violation_type_id = ?");
                if ($update_stmt) {
                    $update_stmt->bind_param("sssi", $new_resolution_number, $new_violation_type, $new_violation_description, $category_id, $violation_type_id);
                    if ($update_stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'Violation type updated successfully.';
                    } else {
                        $response['message'] = 'Error updating violation type: ' . htmlspecialchars($update_stmt->error);
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
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
// --- END POST HANDLING FOR EDITING VIOLATION TYPE (EXISTING, BUT REVISED) ---


// --- START POST HANDLING FOR DELETING VIOLATION TYPE (EXISTING) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_violation_type_id'])) {
    $response = ['success' => false, 'message' => 'An unexpected error occurred.'];
    $violation_type_id = $_POST['delete_violation_type_id'];

    if (empty($violation_type_id)) {
        $response['message'] = 'Violation ID not provided.';
        header('Content-Type: application/json'); echo json_encode($response); exit;
    }

    $delete_stmt = $conn->prepare("DELETE FROM violation_type_tbl WHERE violation_type_id = ?");
    if ($delete_stmt) {
        $delete_stmt->bind_param("i", $violation_type_id);
        if ($delete_stmt->execute()) {
            if ($delete_stmt->affected_rows > 0) {
                $response['success'] = true;
                $response['message'] = 'Violation type deleted successfully.';
            } else {
                $response['message'] = 'Violation type not found or already deleted.';
            }
        } else {
            $response['message'] = 'Error deleting violation type: ' . htmlspecialchars($delete_stmt->error);
        }
        $delete_stmt->close();
    } else {
        $response['message'] = 'Error preparing delete statement: ' . htmlspecialchars($conn->error);
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
// --- END POST HANDLING FOR DELETING VIOLATION TYPE (EXISTING) ---

// --- START AJAX GET FOR FETCHING VIOLATION TYPE DETAILS FOR EDIT/DELETE MODAL (EXISTING) ---
if (isset($_GET['action']) && $_GET['action'] == 'get_violation_type_details' && isset($_GET['id'])) {
    $response = ['success' => false, 'message' => 'Details not found.', 'data' => null];
    $violation_type_id = $_GET['id'];

    if (empty($violation_type_id)) {
        $response['message'] = 'Violation ID not provided.';
        header('Content-Type: application/json'); echo json_encode($response); exit;
    }

    $sql_details = "SELECT vt.violation_type_id, vt.violation_type, vt.resolution_number, vt.violation_description, vc.category_name
                    FROM violation_type_tbl vt
                    LEFT JOIN violation_category_tbl vc ON vt.violation_category_id = vc.violation_category_id
                    WHERE vt.violation_type_id = ?";
    $stmt_details = $conn->prepare($sql_details);
    if ($stmt_details) {
        $stmt_details->bind_param("i", $violation_type_id);
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
// --- END AJAX GET FOR FETCHING VIOLATION TYPE DETAILS FOR EDIT/DELETE MODAL (EXISTING) ---

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Sanction</title>
    <link rel="stylesheet" href="./admin_violation.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>

<div id="toast-notification" class="toast"></div>
<header>
    <div class="logo">
        <img src="../assets/PUPlogo.png" alt="PUP Logo" />
    </div>
    <nav>
        <a href="../HTML/admin_homepage.html">Home</a>
        <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="active">Violations</a>
        <a href="../updated-admin-sanction/admin_sanction.php">Student Sanction</a>
        <a href="../user-management/user_management.php">User Management</a>
    </nav>
    <div class="admin-icons">
        <a href="../HTML/notification.html" class="notification">
            <img src="https://img.icons8.com/?size=100&id=83193&format=png&color=000000" alt="Notifications"/>
        </a>
        <a href="../HTML/admin_account.html" class="admin">
            <img src="https://img.icons8.com/?size=100&id=77883&format=png&color=000000" alt="Admin Account"/>
        </a>
    </div>
</header>

<div class="container">
    <h1>Student Violation Records</h1>

    <div class="tabs">
        <button class="tab <?php echo ($active_tab == 'Violation' ? 'active' : ''); ?>" data-tab="Violation"><i class="fas fa-user-graduate"></i>Student</button>
        <button class="tab <?php echo ($active_tab == 'Configuration' ? 'active' : ''); ?>" data-tab="Configuration"><i class="fas fa-cogs"></i>Violation Configuration</button>
    </div>

    <div id="Violation" class="tab-content" style="<?php echo ($active_tab == 'Violation' ? 'display: block;' : 'display: none;'); ?>">
        <?php
        // Moved the clear filters button inside the tab content
        if ($active_tab == 'Violation' && (!empty($filterViolation) || !empty($filterCourse) || !empty($filterYear) || !empty($search))) {
            $baseUrl = strtok($_SERVER["REQUEST_URI"], '?');
            echo '<div class="clear-filters-container">';
            echo '     <a href="' . htmlspecialchars($baseUrl) . '" class="clear-filters-btn"><i class="fas fa-eraser"></i> Clear Filters</a>';
            echo '</div>';
        }
        ?>

        <div class="table-controls">
            <div class="filters-area">
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="filter-form">
                    <input type="hidden" name="tab" value="Violation"> <select name="violation_type" class="filter-select">
                        <option value="">Filter by Violation Type</option>
                        <?php
                        $vtQueryMain = "SELECT violation_type_id, violation_type FROM violation_type_tbl ORDER BY violation_type ASC";
                        $vtResultMain = $conn->query($vtQueryMain);
                        if ($vtResultMain) {
                            while ($rowVt = $vtResultMain->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($rowVt['violation_type_id']) . "' " . (($filterViolation == $rowVt['violation_type_id']) ? 'selected' : '') . ">" . htmlspecialchars($rowVt['violation_type']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                    <select name="course_id" class="filter-select">
                        <option value="">Filter by Course</option>
                        <?php
                        $courseQueryMain = "SELECT course_id, course_name FROM course_tbl ORDER BY course_name ASC";
                        $courseResultMain = $conn->query($courseQueryMain);
                        if ($courseResultMain) {
                            while ($rowCourse = $courseResultMain->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($rowCourse['course_id']) . "' " . (($filterCourse == $rowCourse['course_id']) ? 'selected' : '') . ">" . htmlspecialchars($rowCourse['course_name']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                    <select name="year_id" class="filter-select">
                        <option value="">Filter by Year</option>
                        <?php
                        $yearQueryMain = "SELECT year_id, year FROM year_tbl ORDER BY year ASC";
                        $yearResultMain = $conn->query($yearQueryMain);
                        if ($yearResultMain) {
                            while ($rowYear = $yearResultMain->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($rowYear['year_id']) . "' " . (($filterYear == $rowYear['year_id']) ? 'selected' : '') . ">" . htmlspecialchars($rowYear['year']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                    <input
                        type="text"
                        id="searchInput"
                        name="search"
                        placeholder="Search by Student Number, Name"
                        value="<?php echo htmlspecialchars($search); ?>"
                        class="search-input"
                    />
                    <button type="submit" class="search-button"><i class="fas fa-search"></i> Search</button>
                </form>
            </div>
            <div class="table-actions">
                <button type="button" id="refreshTableBtn" class="refresh-button"><i class="fas fa-sync-alt"></i> Refresh List</button>
                <button id="addViolationBtn" class="add-violation-button"><i class="fas fa-plus"></i> Add Violation</button>
            </div>
        </div>

        <div class="main-table-scroll-container">
            <div class="table-overlay-spinner" id="tableSpinner" style="display: none;">
                <div class="spinner"></div>
            </div>
            <table>
                <thead>
                <tr>
                    <th>Student Number</th>
                    <th>First Name</th>
                    <th>Middle Name</th>
                    <th>Last Name</th>
                    <th>Course</th>
                    <th>Year</th>
                    <th>Section</th>
                    <th>Violations Committed</th>
                    <th>Violation Count</th>
                    <th>Offense</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody id="violationTableBody">
                <?php
                // Refactored SQL query to use prepared statements for filtering
                $sql = "SELECT
                            u.student_number, u.first_name, u.middle_name, u.last_name,
                            c.course_name, y.year, s.section_name,
                            GROUP_CONCAT(DISTINCT vt_main.violation_type ORDER BY vt_main.violation_type SEPARATOR ', ') AS violations_committed_list
                        FROM users_tbl u
                        LEFT JOIN course_tbl c ON u.course_id = c.course_id
                        LEFT JOIN year_tbl y ON u.year_id = y.year_id
                        LEFT JOIN section_tbl s ON u.section_id = s.section_id
                        LEFT JOIN violation_tbl v_main ON u.student_number = v_main.student_number
                        LEFT JOIN violation_type_tbl vt_main ON v_main.violation_type = vt_main.violation_type_id
                        ";

                $whereClauses = ["v_main.violation_id IS NOT NULL"];
                $params = [];
                $paramTypes = "";

                if (!empty($search)) {
                    $whereClauses[] = "(u.student_number LIKE ? OR u.last_name LIKE ? OR u.first_name LIKE ?)";
                    $params[] = "%{$search}%";
                    $params[] = "%{$search}%";
                    $params[] = "%{$search}%";
                    $paramTypes .= "sss";
                }
                if (!empty($filterCourse)) {
                    $whereClauses[] = "u.course_id = ?";
                    $params[] = $filterCourse;
                    $paramTypes .= "i";
                }
                if (!empty($filterYear)) {
                    $whereClauses[] = "u.year_id = ?";
                    $params[] = $filterYear;
                    $paramTypes .= "i";
                }

                if (!empty($filterViolation)) {
                    $whereClauses[] = "EXISTS (SELECT 1 FROM violation_tbl v_filter
                                                WHERE v_filter.student_number = u.student_number
                                                AND v_filter.violation_type = ?)";
                    $params[] = $filterViolation;
                    $paramTypes .= "i";
                }

                $sql .= " WHERE " . implode(" AND ", $whereClauses);
                $sql .= " GROUP BY u.student_number, u.first_name, u.middle_name, u.last_name, c.course_name, y.year, s.section_name";
                $sql .= " ORDER BY u.last_name ASC, u.first_name ASC";

                $stmt_main = $conn->prepare($sql);

                if ($stmt_main) {
                    if (!empty($params)) {
                        $stmt_main->bind_param($paramTypes, ...$params);
                    }
                    $stmt_main->execute();
                    $result = $stmt_main->get_result();

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['student_number']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['first_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['middle_name'] ?? '') . "</td>";
                            echo "<td>" . htmlspecialchars($row['last_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['course_name'] ?? 'N/A') . "</td>";
                            echo "<td>" . htmlspecialchars($row['year'] ?? 'N/A') . "</td>";
                            echo "<td>" . htmlspecialchars($row['section_name'] ?? 'N/A') . "</td>";
                            echo "<td>" . htmlspecialchars($row['violations_committed_list'] ?? 'No Violations') . "</td>";

                            $student_number_for_detail = $row['student_number'];
                            $detail_sql = "SELECT vt.violation_type_id
                                            FROM violation_tbl v_detail
                                            JOIN violation_type_tbl vt ON v_detail.violation_type = vt.violation_type_id
                                            WHERE v_detail.student_number = ?";

                            $violations_by_type_count = [];
                            $total_individual_violations = 0;

                            $detail_stmt = $conn->prepare($detail_sql);
                            if ($detail_stmt) {
                                $detail_stmt->bind_param("s", $student_number_for_detail);
                                $detail_stmt->execute();
                                $detail_result = $detail_stmt->get_result();

                                while ($detail_row = $detail_result->fetch_assoc()) {
                                    $type_id_key = $detail_row['violation_type_id'];
                                    if (!isset($violations_by_type_count[$type_id_key])) {
                                        $violations_by_type_count[$type_id_key] = 0;
                                    }
                                    $violations_by_type_count[$type_id_key]++;
                                    $total_individual_violations++;
                                }
                                $detail_stmt->close();
                            } else {
                                error_log("Failed to prepare detail statement for student: " . $student_number_for_detail . " - " . $conn->error);
                            }

                            echo "<td>" . htmlspecialchars($total_individual_violations) . "</td>";

                            $offenseText = 'Warning';
                            $offenseClass = 'offense-text-warning';
                            $hasSanction = false;

                            if ($total_individual_violations > 0) {
                                foreach ($violations_by_type_count as $violation_id_key => $count) {
                                    if ($count >= 2) {
                                        $hasSanction = true;
                                        break;
                                    }
                                }

                                if ($hasSanction) {
                                    $offenseText = 'Sanction';
                                }
                            } else {
                                $offenseText = '-';
                            }

                            if ($offenseText === 'Sanction') {
                                $offenseClass = 'offense-text-sanction';
                            } elseif ($offenseText === 'Warning') {
                                $offenseClass = 'offense-text-warning';
                            } else {
                                $offenseClass = 'offense-text-default';
                            }

                            echo "<td><span class='" . $offenseClass . "'>" . htmlspecialchars($offenseText) . "</span></td>";
                            echo "<td><a href='student_violation_details.php?student_number=" . urlencode($row['student_number']) . "' class='more-details-btn'><i class='fas fa-info-circle'></i> More Details</a></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='11' class='no-records-cell'>";
                        echo "No student violations match your current selection. <br>Please try adjusting your search or filter criteria.";
                        echo "</td></tr>";
                    }
                    $stmt_main->close();
                } else {
                    echo "<tr><td colspan='11' class='no-records-cell'>Query Error: " . htmlspecialchars($conn->error) . "</td></tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="Configuration" class="tab-content" style="<?php echo ($active_tab == 'Configuration' ? 'display: block;' : 'display: none;'); ?>">
        <div class="category-config-controls">
            <button id="addViolationCategoryBtn" class="add-violation-category-btn"><i class="fas fa-plus"></i> Add Violation Category</button>
        </div>

        <div class="accordion-container-wrapper">
            <div class="accordion-container">
                <?php
                $categoryQuery = "SELECT violation_category_id, category_name FROM violation_category_tbl ORDER BY category_name ASC";
                $categoryResult = $conn->query($categoryQuery);
                if ($categoryResult && $categoryResult->num_rows > 0) {
                    while ($categoryRow = $categoryResult->fetch_assoc()) {
                        $categoryId = htmlspecialchars($categoryRow['violation_category_id']);
                        $categoryName = htmlspecialchars($categoryRow['category_name']);
                ?>
                        <div class="accordion-item">
                            <button class="accordion-header">
                                <?php echo $categoryName; ?>
                                <i class="fas fa-chevron-down accordion-icon"></i>
                            </button>
                            <div class="accordion-content">
                                <div class="accordion-controls">
                                    <button class="add-type-to-category-btn" data-category-name="<?php echo $categoryName; ?>"><i class="fas fa-plus"></i> Add Type</button>
                                </div>
                                <div class="config-table-scroll-container">
                                    <table class="config-table">
                                        <thead>
                                            <tr>
                                                <th>Resolution Order</th>
                                                <th>Type of Violation</th>
                                                <th>Violation Description</th>
                                                <th>Date Published</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sql_config_violations = "SELECT
                                                                        vt.violation_type_id,
                                                                        vt.violation_type,
                                                                        vt.resolution_number,
                                                                        vt.violation_description,
                                                                        vt.date_published
                                                                      FROM violation_type_tbl vt
                                                                      WHERE vt.violation_category_id = ?
                                                                      ORDER BY vt.violation_type ASC";
                                            $stmt_config_violations = $conn->prepare($sql_config_violations);
                                            if ($stmt_config_violations) {
                                                $stmt_config_violations->bind_param("i", $categoryId);
                                                $stmt_config_violations->execute();
                                                $result_config = $stmt_config_violations->get_result();

                                                if ($result_config->num_rows > 0) {
                                                    while ($row_config = $result_config->fetch_assoc()) {
                                            ?>
                                                        <tr data-id="<?php echo $row_config['violation_type_id']; ?>" class="violation-type-row">
                                                            <td><?php echo htmlspecialchars($row_config['resolution_number'] ?? 'N/A'); ?></td>
                                                            <td><?php echo htmlspecialchars($row_config['violation_type']); ?></td>
                                                            <td><?php echo htmlspecialchars($row_config['violation_description'] ?? 'No description'); ?></td>
                                                            <td><?php echo htmlspecialchars($row_config['date_published'] ? date("F j, Y", strtotime($row_config['date_published'])) : 'N/A'); ?></td>
                                                            <td class="action-buttons-cell">
                                                                <div class="action-buttons-container" style="display: none;">
                                                                    <button class='edit-violation-type-btn btn-secondary' data-id='<?php echo $row_config['violation_type_id']; ?>'><i class='fas fa-edit'></i> Update</button>
                                                                    <button class='delete-violation-type-btn btn-danger' data-id='<?php echo $row_config['violation_type_id']; ?>'><i class='fas fa-trash-alt'></i> Delete</button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                            <?php
                                                    }
                                                } else {
                                                    echo "<tr><td colspan='5' class='no-records-cell'>No violation types in this category.</td></tr>";
                                                }
                                                $stmt_config_violations->close();
                                            } else {
                                                error_log("Failed to prepare config violation list statement: " . $conn->error);
                                                echo "<tr><td colspan='5' class='no-records-cell'>Error loading violation types.</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="row-action-buttons-container">
                                    </div>
                            </div>
                        </div>
                <?php
                    }
                } else {
                    echo "<p class='no-records-cell'>No violation categories found. Please add some categories first.</p>";
                }
                ?>
            </div>
        </div>
    </div>
</div>

<div id="modal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="head-modal">
            <h3>Add Student Violation</h3>
            <span class="close-modal-button" style="float:right; cursor:pointer; font-size: 1.5em;">&times;</span>
        </div>
        <div id="modalMessage" class="modal-message" style="display: none;"></div>

        <form id="violationForm" class="form-container" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <input type="hidden" name="ajax_submit" value="1">
            <div class="row">
                <div class="column">
                    <label for="studentNumber">Student Number:</label>
                    <input type="text" id="studentNumber" name="studentNumber" required />
                </div>
                <div class="column">
                    <label for="violationType">Violation Type:</label>
                    <select id="violationType" name="violationType" required>
                        <option value="">Select Violation Type</option>
                        <?php
                        // Fetch all violation types for the student violation modal
                        $vtSqlModal = "SELECT violation_type_id, violation_type FROM violation_type_tbl ORDER BY violation_type ASC";
                        $vtResultModal = $conn->query($vtSqlModal);
                        if ($vtResultModal && $vtResultModal->num_rows > 0) {
                            while ($vtRowModal = $vtResultModal->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($vtRowModal['violation_type_id']) . '">'
                                    . htmlspecialchars($vtRowModal['violation_type']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="button-row">
                <button type="submit" class="modal-button-add"><i class="fas fa-check"></i> Add</button>
                <button type="button" id="closeModal" class="modal-button-cancel"><i class="fas fa-times"></i> Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="addViolationCategoryModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="head-modal">
            <h3>Add New Violation Category</h3>
            <span class="close-modal-category-button" style="float:right; cursor:pointer; font-size: 1.5em;">&times;</span>
        </div>
        <div id="addViolationCategoryModalMessage" class="modal-message" style="display: none;"></div>

        <form id="addViolationCategoryForm" class="form-container" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <input type="hidden" name="add_new_category_and_type" value="1">
            <div id="addCategoryStep1" class="modal-step" style="display: block;">
                <div class="row">
                    <div class="column full-width">
                        <label for="newCategoryName">Violation Category Name:</label>
                        <input type="text" id="newCategoryName" name="new_category_name" required style="text-transform: uppercase;" />
                    </div>
                </div>
                <div class="button-row">
                    <button type="button" id="nextToCategoryStep2" class="modal-button-next"><i class="fas fa-arrow-right"></i> Next</button>
                    <button type="button" id="cancelCategoryStep1" class="modal-button-cancel"><i class="fas fa-times"></i> Cancel</button>
                </div>
            </div>

            <div id="addCategoryStep2" class="modal-step" style="display: none;">
                <div class="row">
                    <div class="column">
                        <label for="newResolutionNumberCatModal">Resolution Order:</label>
                        <input type="text" id="newResolutionNumberCatModal" name="new_resolution_number_cat_modal" required style="text-transform: uppercase;" />
                    </div>
                    <div class="column">
                        <label for="newViolationTypeCatModal">Type of Violation:</label>
                        <input type="text" id="newViolationTypeCatModal" name="new_violation_type_cat_modal" required style="text-transform: uppercase;" />
                    </div>
                </div>
                <div class="row">
                    <div class="column full-width">
                        <label for="newViolationDescriptionCatModal">Violation Description:</label>
                        <textarea id="newViolationDescriptionCatModal" name="new_violation_description_cat_modal" rows="3" required></textarea>
                    </div>
                </div>
                <div class="button-row">
                    <button type="submit" class="modal-button-publish"><i class="fas fa-check"></i> Publish</button>
                    <button type="button" id="backToCategoryStep1" class="modal-button-back"><i class="fas fa-arrow-left"></i> Back</button>
                    <button type="button" id="cancelCategoryStep2" class="modal-button-cancel"><i class="fas fa-times"></i> Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="addTypeToCategoryModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="head-modal">
            <h3>Add Violation Type</h3>
            <span class="close-modal-add-type-button" style="float:right; cursor:pointer; font-size: 1.5em;">&times;</span>
        </div>
        <div id="addTypeToCategoryModalMessage" class="modal-message" style="display: none;"></div>

        <form id="addTypeToCategoryForm" class="form-container" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <input type="hidden" name="add_type_to_existing_category" value="1">
            <div class="row">
                <div class="column">
                    <label for="existingCategoryName">Violation Category:</label>
                    <input type="text" id="existingCategoryName" name="existing_category_name" readonly style="text-transform: uppercase; background-color: #f0f0f0;" />
                </div>
                <div class="column">
                    <label for="newResolutionNumberTypeModal">Resolution Order:</label>
                    <input type="text" id="newResolutionNumberTypeModal" name="new_resolution_number_type_modal" required style="text-transform: uppercase;" />
                </div>
            </div>
            <div class="row">
                <div class="column full-width">
                    <label for="newViolationTypeTypeModal">Type of Violation:</label>
                    <input type="text" id="newViolationTypeTypeModal" name="new_violation_type_type_modal" required style="text-transform: uppercase;" />
                </div>
            </div>
            <div class="row">
                <div class="column full-width">
                    <label for="newViolationDescriptionTypeModal">Violation Description:</label>
                    <textarea id="newViolationDescriptionTypeModal" name="new_violation_description_type_modal" rows="3" required></textarea>
                </div>
            </div>

            <div class="button-row">
                <button type="submit" class="modal-button-publish"><i class="fas fa-check"></i> Publish Type</button>
                <button type="button" id="closeAddTypeToCategoryModal" class="modal-button-cancel"><i class="fas fa-times"></i> Cancel</button>
            </div>
        </form>
    </div>
</div>


<div id="editViolationTypeModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="head-modal">
            <h3>Edit Violation Type</h3>
            <span class="close-modal-edit-button" style="float:right; cursor:pointer; font-size: 1.5em;">&times;</span>
        </div>
        <div id="editViolationTypeModalMessage" class="modal-message" style="display: none;"></div>

        <form id="editViolationTypeForm" class="form-container" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <input type="hidden" name="edit_violation_type_submit" value="1">
            <input type="hidden" id="editViolationTypeId" name="violation_type_id">
            <div class="row">
                <div class="column">
                    <label for="editResolutionNumberConfig">Resolution Order:</label>
                    <input type="text" id="editResolutionNumberConfig" name="edit_resolution_number_config" required style="text-transform: uppercase;" />
                </div>
                <div class="column">
                    <label for="editViolationCategoryConfig">Violation Category:</label>
                    <input type="text" id="editViolationCategoryConfig" name="edit_violation_category_config" required style="text-transform: uppercase;" />
                </div>
            </div>
            <div class="row">
                <div class="column full-width">
                    <label for="editViolationTypeConfig">Type of Violation:</label>
                    <input type="text" id="editViolationTypeConfig" name="edit_violation_type_config" required style="text-transform: uppercase;" />
                </div>
            </div>
            <div class="row">
                <div class="column full-width">
                    <label for="editViolationDescriptionConfig">Violation Description:</label>
                    <textarea id="editViolationDescriptionConfig" name="edit_violation_description_config" rows="3" required></textarea>
                </div>
            </div>
            <div class="button-row">
                <button type="submit" class="modal-button-publish"><i class="fas fa-save"></i> Save Changes</button>
                <button type="button" id="cancelEditViolationTypeModal" class="modal-button-cancel"><i class="fas fa-times"></i> Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="deleteViolationTypeModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="head-modal">
            <h3>Delete Violation Type</h3>
            <span class="close-modal-delete-button" style="float:right; cursor:pointer; font-size: 1.5em;">&times;</span>
        </div>
        <div id="deleteViolationTypeModalMessage" class="modal-message" style="display: none;"></div>

        <div class="confirmation-content">
            <p>Are you sure you want to delete this Violation?</p>
            <p><strong>Category:</strong> <span id="deleteViolationCategoryDisplay"></span></p>
            <p><strong>Violation Type:</strong> <span id="deleteViolationTypeDisplay"></span></p>
            <p><strong>Description:</strong> <span id="deleteViolationDescriptionDisplay"></span></p>
        </div>

        <div class="button-row">
            <button type="button" id="confirmDeleteViolationTypeBtn" class="btn-confirm-delete"><i class="fas fa-check"></i> Confirm Delete</button>
            <button type="button" id="cancelDeleteViolationTypeModal" class="modal-button-cancel"><i class="fas fa-times"></i> Cancel</button>
        </div>
    </div>
</div>


<script src="./admin_violation_page.js"></script>
</body>
</html>
<?php $conn->close(); ?>