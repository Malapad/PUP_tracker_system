<?php
session_start();
include '../PHP/dbcon.php';

if (isset($_GET['action']) && $_GET['action'] == 'search_student_for_violation' && isset($_GET['student_search_number'])) {
    $response = ['success' => false, 'message' => 'Student not found.', 'student' => null];
    $studentNumberInput = trim($_GET['student_search_number']);
    if (empty($studentNumberInput)) {
        $response['message'] = 'Student Number cannot be empty.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    $sql_search_student = "SELECT u.student_number, u.first_name, u.middle_name, u.last_name,
                                 COALESCE(c.course_name, 'N/A') as course_name,
                                 COALESCE(y.year, 'N/A') as year,
                                 COALESCE(s.section_name, 'N/A') as section_name
                           FROM users_tbl u
                           LEFT JOIN course_tbl c ON u.course_id = c.course_id
                           LEFT JOIN year_tbl y ON u.year_id = y.year_id
                           LEFT JOIN section_tbl s ON u.section_id = s.section_id
                           WHERE u.student_number = ? LIMIT 1";
    $stmt_search_student = $conn->prepare($sql_search_student);
    if ($stmt_search_student) {
        $stmt_search_student->bind_param("s", $studentNumberInput);
        $stmt_search_student->execute();
        $result_student = $stmt_search_student->get_result();
        if ($student_data = $result_student->fetch_assoc()) {
            $response['success'] = true;
            $response['message'] = 'Student found.';
            $response['student'] = [
                'student_number' => $student_data['student_number'],
                'first_name' => $student_data['first_name'],
                'middle_name' => $student_data['middle_name'] ?? '',
                'last_name' => $student_data['last_name'],
                'course_name' => $student_data['course_name'],
                'year' => $student_data['year'],
                'section_name' => $student_data['section_name']
            ];
        } else {
            $response['message'] = 'No student found with Student Number: ' . htmlspecialchars($studentNumberInput);
        }
        $stmt_search_student->close();
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'get_violation_types_for_category' && isset($_GET['category_id'])) {
    $response = ['success' => false, 'message' => 'Types not found.', 'types' => []];
    $categoryId = trim($_GET['category_id']);
    if (empty($categoryId) || !is_numeric($categoryId)) {
        $response['message'] = 'Invalid Category ID.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    $sql_types = "SELECT violation_type_id, violation_type FROM violation_type_tbl WHERE violation_category_id = ? ORDER BY violation_type ASC";
    $stmt_types = $conn->prepare($sql_types);
    if ($stmt_types) {
        $stmt_types->bind_param("i", $categoryId);
        $stmt_types->execute();
        $result_types = $stmt_types->get_result();
        $types_data = [];
        while ($type_row = $result_types->fetch_assoc()) {
            $types_data[] = $type_row;
        }
        $response['success'] = true;
        $response['types'] = $types_data;
        $response['message'] = empty($types_data) ? 'No violation types found for this category.' : 'Types fetched successfully.';
        $stmt_types->close();
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$filterCourse = $_GET['course_id'] ?? '';
$filterCategory = $_GET['violation_category'] ?? '';
$search = trim($_GET['search'] ?? '');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['studentNumber'])) {
    $response = ['success' => false, 'message' => 'An unexpected error occurred.'];
    $studentNumber = trim($_POST['studentNumber'] ?? '');
    $violationTypeId = trim($_POST['violationType'] ?? '');
    $violationRemarks = trim($_POST['violationRemarks'] ?? '');
    $recorder_id = $_SESSION['security_id'] ?? null;

    if (empty($studentNumber) || empty($violationTypeId)) {
        $response['message'] = 'Student Number and Violation Type are required.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    if (is_null($recorder_id)) {
        $response['message'] = 'Could not identify the recording security personnel. Please log in again.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO violation_tbl (student_number, violation_type, violation_date, description, recorder_id) VALUES (?, ?, NOW(), ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sisi", $studentNumber, $violationTypeId, $violationRemarks, $recorder_id);
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Violation added successfully.';
        } else {
            $response['message'] = 'Error adding violation: ' . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    } else {
        $response['message'] = 'Error preparing statement for insert: ' . htmlspecialchars($conn->error);
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

function getIconForCategory($categoryName) {
    switch (strtoupper(trim($categoryName))) {
        case 'ACADEMIC INTEGRITY': return 'fas fa-graduation-cap';
        case 'ID VIOLATION': return 'fas fa-id-card';
        case 'DRESS CODE POLICY': return 'fas fa-user-tie';
        case 'EVENTS AND VISITORS': return 'fas fa-calendar-check';
        case 'STUDENT CONDUCT': return 'fas fa-gavel';
        case 'UNIVERSITY PROPERTY AND FACILITIES': return 'fas fa-building';
        default: return 'fas fa-exclamation-circle';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>Student Violation Records</title>
        <link rel="stylesheet" href="./security_violation.css?v=<?php echo time(); ?>" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    </head>
    <body>
        <div id="toast-notification" class="toast"></div>

        <header class="main-header">
            <div class="header-content">
                <div class="logo"><img src="../IMAGE/Tracker-logo.png" alt="PUP Logo"></div>
                <nav class="main-nav">
                    <a href="security_dashboard.php">Dashboard</a>
                    <a href="security_violation_page.php" class="active-nav">Violations</a>
                </nav>
                <div class="user-icons">
                    <a href="notification.html" class="notification"><svg class="header-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19 13.586V10c0-3.217-2.185-5.927-5.145-6.742C13.562 2.52 12.846 2 12 2s-1.562.52-1.855 1.258C7.185 4.073 5 6.783 5 10v3.586l-1.707 1.707A.996.996 0 0 0 3 16v2a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1v-2a.996.996 0 0 0-.293-.707L19 13.586zM19 17H5v-.586l1.707-1.707A.996.996 0 0 0 7 14v-4c0-2.757 2.243-5 5-5s5 2.243 5 5v4c0 .266.105.52.293.707L19 16.414V17zm-7 5a2.98 2.98 0 0 0 2.818-2H9.182A2.98 2.98 0 0 0 12 22z"/></svg></a>
                    <a href="../PHP/security_account.php" class="admin-profile"><svg class="header-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg></a>
                </div>
            </div>
        </header>
        <div class="container">
            <h1>Student Violation Records</h1>
            <div id="Violation" class="tab-content">
                <div class="table-controls">
                    <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="filter-form">
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
                        <select name="violation_category" id="filterViolationCategory" class="filter-select">
                            <option value="">Filter by Violation</option>
                            <?php
                            $catQueryMainFilter = "SELECT violation_category_id, category_name FROM violation_category_tbl ORDER BY category_name ASC";
                            $catResultMainFilter = $conn->query($catQueryMainFilter);
                            if ($catResultMainFilter) {
                                while ($rowCatFilter = $catResultMainFilter->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($rowCatFilter['violation_category_id']) . "' " . (($filterCategory == $rowCatFilter['violation_category_id']) ? 'selected' : '') . ">" . htmlspecialchars($rowCatFilter['category_name']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                        <div class="search-group">
                            <input type="text" id="searchInput" name="search" placeholder="Search by Student Number, Name..." value="<?php echo htmlspecialchars($search); ?>" class="search-input"/>
                            <button type="submit" class="search-button"><i class="fas fa-search"></i></button>
                        </div>
                        <button type="button" id="addViolationBtn" class="add-violation-button"><i class="fas fa-plus"></i> Add Violation</button>
                    </form>
                </div>
                <div class="main-table-scroll-container">
                    <div class="table-overlay-spinner" id="tableSpinner" style="display: none;"><div class="spinner"></div></div>
                    <table>
                        <thead>
                            <tr>
                                <th style="width:18%">Student Number</th>
                                <th style="width:12%">First Name</th>
                                <th style="width:12%">Middle Name</th>
                                <th style="width:12%">Last Name</th>
                                <th style="width:8%">Course</th>
                                <th style="width:8%">Section</th>
                                <th style="width:22%" class="text-center">Violation Summary</th>
                            </tr>
                        </thead>
                        <tbody id="violationTableBody">
                        <?php
                            $sql = "
                                SELECT
                                    u.student_number, u.first_name, u.middle_name, u.last_name,
                                    c.course_name, y.year, s.section_name,
                                    vt.violation_type, v.violation_type as violation_type_id,
                                    vc.category_name,
                                    COUNT(v.violation_id) as offense_count,
                                    MAX(v.violation_date) as latest_date,
                                    SUBSTRING_INDEX(GROUP_CONCAT(v.description ORDER BY v.violation_date DESC SEPARATOR '|||'), '|||', 1) as latest_description
                                FROM violation_tbl v
                                INNER JOIN users_tbl u ON v.student_number = u.student_number
                                LEFT JOIN violation_type_tbl vt ON v.violation_type = vt.violation_type_id
                                LEFT JOIN violation_category_tbl vc ON vt.violation_category_id = vc.violation_category_id
                                LEFT JOIN course_tbl c ON u.course_id = c.course_id
                                LEFT JOIN year_tbl y ON u.year_id = y.year_id
                                LEFT JOIN section_tbl s ON u.section_id = s.section_id
                            ";
                            $filter_whereClauses = []; $params = []; $paramTypes = "";
                            if (!empty($search)) {
                                $filter_whereClauses[] = "(u.student_number LIKE ? OR u.last_name LIKE ? OR u.first_name LIKE ?)";
                                $searchTerm = "%{$search}%"; array_push($params, $searchTerm, $searchTerm, $searchTerm); $paramTypes .= "sss";
                            }
                            if (!empty($filterCourse)) { $filter_whereClauses[] = "u.course_id = ?"; $params[] = $filterCourse; $paramTypes .= "i"; }
                            if (!empty($filterCategory)) { $filter_whereClauses[] = "vt.violation_category_id = ?"; $params[] = $filterCategory; $paramTypes .= "i"; }
                            
                            if (!empty($filter_whereClauses)) { $sql .= " WHERE " . implode(" AND ", $filter_whereClauses); }
                            
                            $sql .= "
                                GROUP BY u.student_number, u.first_name, u.middle_name, u.last_name, c.course_name, y.year, s.section_name, v.violation_type, vt.violation_type, vc.category_name
                                ORDER BY latest_date DESC, u.last_name ASC, u.first_name ASC";

                            $stmt_main = $conn->prepare($sql);
                            if ($stmt_main) {
                                if (!empty($params)) { $stmt_main->bind_param($paramTypes, ...$params); }
                                $stmt_main->execute();
                                $result = $stmt_main->get_result();
                                $violations_data = [];
                                while ($row = $result->fetch_assoc()) { $violations_data[] = $row; }
                                $grouped_violations = [];
                                foreach ($violations_data as $violation) {
                                    $grouped_violations[$violation['student_number']]['info'] = $violation;
                                    $grouped_violations[$violation['student_number']]['violations'][] = $violation;
                                }

                                if (count($grouped_violations) > 0) {
                                    foreach ($grouped_violations as $student_number => $student_data) {
                                        $student_info = $student_data['info'];
                                        $violations = $student_data['violations'];
                                        $total_violations = count($violations);
                                        $student_id_safe = preg_replace('/[^a-zA-Z0-9_-]/', '-', $student_info['student_number']);

                                        $sanction_count = 0; $warning_count = 0;
                                        foreach($violations as $v) {
                                            if ($v['offense_count'] > 1) $sanction_count++;
                                            else $warning_count++;
                                        }

                                        $group_border_class = $sanction_count > 0 ? 'group-border-sanction' : 'group-border-warning';

                                        echo "<tr class='student-summary-row' data-target='details-for-{$student_id_safe}'>";
                                        echo "<td data-label='Student Number'><i class='fas fa-chevron-right expand-icon'></i> " . htmlspecialchars($student_info['student_number']) . "</td>";
                                        echo "<td data-label='First Name'>" . htmlspecialchars($student_info['first_name']) . "</td>";
                                        echo "<td data-label='Middle Name'>" . htmlspecialchars($student_info['middle_name'] ?? '') . "</td>";
                                        echo "<td data-label='Last Name'>" . htmlspecialchars($student_info['last_name']) . "</td>";
                                        echo "<td data-label='Course'>" . htmlspecialchars($student_info['course_name'] ?? 'N/A') . "</td>";
                                        echo "<td data-label='Section'>" . htmlspecialchars($student_info['section_name'] ?? 'N/A') . "</td>";
                                        echo "<td data-label='Violation Summary' class='text-center'>{$total_violations} Types <span class='badge-pill status-sanction summary-badge'>{$sanction_count}</span> <span class='badge-pill status-warning summary-badge'>{$warning_count}</span></td>";
                                        echo "</tr>";

                                        echo "<tr class='violation-detail-row' id='details-for-{$student_id_safe}'><td colspan='7' class='details-container-cell " . $group_border_class . "'><div class='details-wrapper'>";
                                        foreach ($violations as $violation_row) {
                                            $offense_count = $violation_row['offense_count'];
                                            $offense_level_display_str = ($offense_count == 1) ? '1st Offense' : (($offense_count == 2) ? '2nd Offense' : (($offense_count == 3) ? '3rd Offense' : $offense_count . 'th Offense'));
                                            $status_text = $offense_count > 1 ? 'Sanction' : 'Warning';
                                            $status_class = $offense_count > 1 ? 'status-sanction' : 'status-warning';
                                            $status_icon = $offense_count > 1 ? 'fa-gavel' : 'fa-exclamation-triangle';

                                            echo "<div class='violation-entry'>";
                                            echo "<div class='violation-main'><span class='violation-type'><i class='" . getIconForCategory($violation_row['category_name']) . "'></i> " . htmlspecialchars($violation_row['violation_type'] ?? 'Unknown Type') . "</span><div class='violation-context'><span class='violation-date'><i class='fas fa-calendar-alt'></i> " . htmlspecialchars(date("F j, Y, g:i a", strtotime($violation_row['latest_date']))) . "</span>";
                                            if (!empty($violation_row['latest_description'])) {
                                                echo "<span class='violation-remarks'>Remarks: " . htmlspecialchars($violation_row['latest_description']) . "</span>";
                                            } else {
                                                echo "<span class='violation-remarks no-remarks'>No remarks provided</span>";
                                            }
                                            echo "</div></div>";
                                            echo "<div class='violation-actions'>";
                                            echo "<span class='badge-pill offense-level-badge'>" . htmlspecialchars($offense_level_display_str) . "</span>";
                                            echo "<span class='badge-pill " . $status_class . "'><i class='fas " . $status_icon . "'></i> " . htmlspecialchars($status_text) . "</span>";
                                            echo "<a href='security_violation_details.php?student_number=" . urlencode($violation_row['student_number']) . "' class='more-details-btn'><i class='fas fa-info-circle'></i> More Details</a>";
                                            echo "</div></div>";
                                        }
                                        echo "</div></td></tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='7' class='no-records-cell'>No student violations match your current selection. <br>Please try adjusting your search or filter criteria.</td></tr>";
                                }
                                $stmt_main->close();
                            } else {
                                echo "<tr><td colspan='7' class='no-records-cell'>Query Error: " . htmlspecialchars($conn->error) . "</td></tr>";
                            }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div id="modal" class="modal" style="display:none;">
            <div class="modal-content">
                <div class="head-modal">
                    <h3>Add Student Violation</h3>
                    <span class="close-modal-button">&times;</span>
                </div>
                <div id="modalMessage" class="modal-message" style="display: none;"></div>
                <div id="searchStudentStep">
                    <div class="form-container">
                        <div class="row">
                            <div class="column full-width">
                                <label for="studentNumberSearchInput">Search Student Number:</label>
                                <div style="display: flex; gap: 10px;">
                                    <input type="text" id="studentNumberSearchInput" placeholder="Enter student number" style="flex-grow: 1;" />
                                    <button type="button" id="executeStudentSearchBtn" class="modal-button-search"><i class="fas fa-search"></i> Search</button>
                                </div>
                            </div>
                        </div>
                        <div id="studentSearchResultArea" style="display:none; margin-top:15px; padding:10px; border:1px solid #ddd; border-radius:5px;"></div>
                        <div id="searchLoadingIndicator" style="display:none; text-align:center; margin-top:10px;"><i class="fas fa-spinner fa-spin"></i> Searching...</div>
                    </div>
                </div>
                <form id="violationForm" class="form-container" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" style="display:none;">
                    <input type="hidden" name="ajax_submit" value="1">
                    <div id="confirmedStudentInfo" style="margin-bottom:15px; padding:10px; background-color:#f0f0f0; border-radius:5px;"></div>
                    <input type="hidden" id="studentNumber" name="studentNumber" />
                    <div class="row">
                        <div class="column full-width">
                            <label for="violationCategory">Violation Category:</label>
                            <select id="violationCategory" name="violationCategory" required>
                                <option value="">Select Violation Category</option>
                                <?php
                                $catSqlModal = "SELECT violation_category_id, category_name FROM violation_category_tbl ORDER BY category_name ASC";
                                $catResultModal = $conn->query($catSqlModal);
                                if ($catResultModal && $catResultModal->num_rows > 0) {
                                    while ($catRowModal = $catResultModal->fetch_assoc()) {
                                        echo '<option value="' . htmlspecialchars($catRowModal['violation_category_id']) . '">' . htmlspecialchars($catRowModal['category_name']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="column full-width">
                            <label for="violationType">Violation Type:</label>
                            <select id="violationType" name="violationType" required disabled>
                                <option value="">Select Category First</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="column full-width">
                            <label for="violationRemarks">Remarks:</label>
                            <textarea id="violationRemarks" name="violationRemarks" rows="3" placeholder="Enter reason or details for the violation..."></textarea>
                        </div>
                    </div>
                    <div class="button-row">
                        <button type="submit" class="modal-button-add"><i class="fas fa-check"></i> Add Violation</button>
                        <button type="button" id="changeStudentBtn" class="modal-button-change-student"><i class="fas fa-undo"></i> Change Student</button>
                        <button type="button" id="closeModalBtn" class="modal-button-cancel"><i class="fas fa-times"></i> Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <script src="./security_violation_page.js"></script>
    </body>
</html>