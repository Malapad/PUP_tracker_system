<?php
include '../PHP/dbcon.php';

$filterViolation = $_GET['violation_type'] ?? '';
$filterCourse = $_GET['course_id'] ?? '';
$filterYear = $_GET['year_id'] ?? '';
$search = trim($_GET['search'] ?? '');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['studentNumber'])) {
    $studentNumber = trim($_POST['studentNumber'] ?? '');
    $violationTypeId = trim($_POST['violationType'] ?? '');
    
    $response = ['success' => false, 'message' => 'An unexpected error occurred.'];

    if ($studentNumber && $violationTypeId) {
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
    } else {
        $response['message'] = 'Please fill in all required fields.';
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Student Violation Records</title>
    <link rel="stylesheet" href="./admin_violation_style.css" />
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
        <a href="../HTML/admin_sanction.html">Student Sanction</a>
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
            <button class="tab active" data-tab="Violation"><i class="fas fa-user-graduate"></i>Student</button>
            <button class="tab" data-tab="sanction-config"><i class="fas fa-cogs"></i>Violation Configuration</button>
        </div>

    <?php
    if (!empty($filterViolation) || !empty($filterCourse) || !empty($filterYear) || !empty($search)) {
        $baseUrl = strtok($_SERVER["REQUEST_URI"], '?');
        echo '<div class="clear-filters-container">';
        echo '    <a href="' . htmlspecialchars($baseUrl) . '" class="clear-filters-btn"><i class="fas fa-eraser"></i> Clear Filters</a>';
        echo '</div>';
    }
    ?>

    <div class="table-controls">
        <div class="filters-area">
            <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="filter-form">
                <select name="violation_type" class="filter-select">
                    <option value="">Filter by Violation Type</option>
                    <?php
                    $vtQueryMain = "SELECT violation_type_id, violation_type FROM violation_type_tbl ORDER BY violation_type ASC";
                    $vtResultMain = $conn->query($vtQueryMain);
                    if ($vtResultMain) {
                        while ($rowVt = $vtResultMain->fetch_assoc()) {
                            $selected = ($filterViolation == $rowVt['violation_type_id']) ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($rowVt['violation_type_id']) . "' $selected>" . htmlspecialchars($rowVt['violation_type']) . "</option>";
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
                            $selected = ($filterCourse == $rowCourse['course_id']) ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($rowCourse['course_id']) . "' $selected>" . htmlspecialchars($rowCourse['course_name']) . "</option>";
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
                            $selected = ($filterYear == $rowYear['year_id']) ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($rowYear['year_id']) . "' $selected>" . htmlspecialchars($rowYear['year']) . "</option>";
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

            $whereConditions = [];
            $baseWhereConditions = ["v_main.violation_id IS NOT NULL"];

            if (!empty($search)) {
                $searchEscaped = $conn->real_escape_string($search);
                $whereConditions[] = "(u.student_number LIKE '%$searchEscaped%' OR u.last_name LIKE '%$searchEscaped%' OR u.first_name LIKE '%$searchEscaped%')";
            }
            if (!empty($filterCourse)) {
                $whereConditions[] = "u.course_id = '" . $conn->real_escape_string($filterCourse) . "'";
            }
            if (!empty($filterYear)) {
                $whereConditions[] = "u.year_id = '" . $conn->real_escape_string($filterYear) . "'";
            }
            
            if (!empty($filterViolation)) {
                $escapedFilterViolation = $conn->real_escape_string($filterViolation);
                $whereConditions[] = "EXISTS (SELECT 1 FROM violation_tbl v_filter 
                                            WHERE v_filter.student_number = u.student_number 
                                            AND v_filter.violation_type = '$escapedFilterViolation')";
            }

            if (!empty($whereConditions)) {
                    $finalWhere = array_merge($baseWhereConditions, $whereConditions);
                    $sql .= " WHERE " . implode(" AND ", $finalWhere);
            } else {
                    $sql .= " WHERE " . implode(" AND ", $baseWhereConditions);
            }

            $sql .= " GROUP BY u.student_number, u.first_name, u.middle_name, u.last_name, c.course_name, y.year, s.section_name";
            $sql .= " ORDER BY u.last_name ASC, u.first_name ASC";

            $result = $conn->query($sql);
            
            if (!$result) {
                echo "<tr><td colspan='11' class='no-records-cell'>Query Error: " . htmlspecialchars($conn->error) . "</td></tr>";
            } elseif ($result->num_rows > 0) {
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
            ?>
            </tbody>
        </table>
    </div>
</div>

<div id="modal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="head-modal">
            <h3>Add Violation</h3>
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

<script src="./admin_violation.js"></script>
</body>
</html>
<?php $conn->close(); ?>