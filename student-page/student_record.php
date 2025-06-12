<?php
require_once "../PHP/dbcon.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_POST['action']) && $_POST['action'] == 'request_sanction') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Authentication failed. Please log in again.'];

    if (isset($_SESSION['user_student_number']) && isset($conn)) {
        $student_number = $_SESSION['user_student_number'];

        $check_stmt = $conn->prepare("SELECT request_id FROM sanction_requests_tbl WHERE student_number = ? AND is_active = 1");
        $check_stmt->bind_param("s", $student_number);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $check_stmt->close();

        if ($result->num_rows > 0) {
            $response['success'] = false;
            $response['message'] = 'You already have a pending sanction request.';
        } else {
            $insert_stmt = $conn->prepare("INSERT INTO sanction_requests_tbl (student_number) VALUES (?)");
            if ($insert_stmt) {
                $insert_stmt->bind_param("s", $student_number);
                if ($insert_stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Sanction request sent successfully!';
                } else {
                    $response['message'] = 'Error: Could not submit your request. ' . $conn->error;
                }
                $insert_stmt->close();
            } else {
                    $response['message'] = 'Database error: Could not prepare the request.';
            }
        }
    }
    echo json_encode($response);
    exit;
}

if (!isset($_SESSION["current_user_id"]) || !isset($_SESSION["user_student_number"])) {
    header("Location: student_login.php");
    exit();
}

$session_user_id = $_SESSION["current_user_id"];
$student_stud_number_from_session = $_SESSION["user_student_number"];

if (isset($_GET['notif_id']) && is_numeric($_GET['notif_id']) && isset($conn)) {
    $notification_id_to_mark = (int)$_GET['notif_id'];
    $sql_mark_direct = "UPDATE notifications_tbl SET is_read = TRUE 
                        WHERE notification_id = ? AND student_number = ?";
    if ($stmt_mark_direct = $conn->prepare($sql_mark_direct)) {
        $stmt_mark_direct->bind_param("is", $notification_id_to_mark, $student_stud_number_from_session);
        $stmt_mark_direct->execute();
        $stmt_mark_direct->close();
    }
}

$unread_notifications_header = [];
$unread_notification_count_header = 0;

if (isset($conn)) {
    $sql_notifications_list_header = "SELECT notification_id, message, created_at, link 
                                      FROM notifications_tbl 
                                      WHERE student_number = ? AND is_read = FALSE 
                                      ORDER BY created_at DESC LIMIT 5";
    if ($stmt_notifications_list_header = $conn->prepare($sql_notifications_list_header)) {
        $stmt_notifications_list_header->bind_param("s", $student_stud_number_from_session);
        $stmt_notifications_list_header->execute();
        $result_notifications_list_header = $stmt_notifications_list_header->get_result();
        while ($row_notif_h = $result_notifications_list_header->fetch_assoc()) {
            $unread_notifications_header[] = $row_notif_h;
        }
        $stmt_notifications_list_header->close();
    }

    $sql_notifications_count_header = "SELECT COUNT(*) as total_unread 
                                       FROM notifications_tbl 
                                       WHERE student_number = ? AND is_read = FALSE";
    if ($stmt_notifications_count_header = $conn->prepare($sql_notifications_count_header)) {
        $stmt_notifications_count_header->bind_param("s", $student_stud_number_from_session);
        $stmt_notifications_count_header->execute();
        $result_count_h = $stmt_notifications_count_header->get_result()->fetch_assoc();
        $unread_notification_count_header = $result_count_h['total_unread'] ?? 0;
        $stmt_notifications_count_header->close();
    }
}

$student_details = null;
$violations_log = [];
$violation_summary = [];
$sanction_records = []; 
$student_stud_number_for_page_violations = '';
$year_display = "N/A";
$page_error = null;

$sql_student_info = "SELECT u.first_name, u.middle_name, u.last_name, u.student_number, u.year_id,
                            c.course_name, s.section_name
                     FROM users_tbl u
                     LEFT JOIN course_tbl c ON u.course_id = c.course_id
                     LEFT JOIN section_tbl s ON u.section_id = s.section_id
                     WHERE u.user_id = ?";

if (isset($conn) && $stmt_info = $conn->prepare($sql_student_info)) {
    $stmt_info->bind_param("i", $session_user_id);
    $stmt_info->execute();
    $result_info = $stmt_info->get_result();
    if ($result_info->num_rows == 1) {
        $student_details = $result_info->fetch_assoc();
        $student_stud_number_for_page_violations = $student_details['student_number'];
        
        if (isset($student_details['year_id']) && !empty($student_details['year_id'])) {
            $year_details_sql = "SELECT year FROM year_tbl WHERE year_id = ?";
            if($stmt_year = $conn->prepare($year_details_sql)){
                $stmt_year->bind_param("i", $student_details['year_id']);
                $stmt_year->execute();
                $year_res = $stmt_year->get_result();
                if($year_row = $year_res->fetch_assoc()){
                    $year_display = $year_row['year'];
                }
                $stmt_year->close();
            }
        }
        $student_details['FirstNameDisplay'] = htmlspecialchars($student_details['first_name'] ?? '');
        $student_details['MiddleNameDisplay'] = htmlspecialchars($student_details['middle_name'] ?? '');
        $student_details['LastNameDisplay'] = htmlspecialchars($student_details['last_name'] ?? '');
        $student_details['StudNumberDisplay'] = htmlspecialchars($student_details['student_number'] ?? '');
        $student_details['CourseNameDisplay'] = htmlspecialchars($student_details['course_name'] ?? 'N/A');
        $student_details['YearDisplay'] = htmlspecialchars($year_display);
        $student_details['SectionNameDisplay'] = htmlspecialchars($student_details['section_name'] ?? 'N/A');
    }
    $stmt_info->close();
} else {
    $page_error = "Could not load student details. Please try again later.";
}

$has_sanctionable_offense = false; 

if (isset($conn) && !empty($student_stud_number_for_page_violations)) {
    $sql_violations_updated = "SELECT v.violation_id, vc.category_name, vt.violation_type, v.violation_date, v.description AS remarks
                               FROM violation_tbl v
                               JOIN violation_type_tbl vt ON v.violation_type = vt.violation_type_id
                               LEFT JOIN violation_category_tbl vc ON vt.violation_category_id = vc.violation_category_id
                               WHERE v.student_number = ?
                               ORDER BY v.violation_date DESC, vc.category_name ASC, vt.violation_type ASC";

    if ($stmt_violations_updated = $conn->prepare($sql_violations_updated)) {
        $stmt_violations_updated->bind_param("s", $student_stud_number_for_page_violations);
        $stmt_violations_updated->execute();
        $result_violations_updated = $stmt_violations_updated->get_result();
        
        $temp_summary_data = [];
        while ($row = $result_violations_updated->fetch_assoc()) {
            $violations_log[] = $row; 

            $typeName = $row['violation_type'];
            $categoryName = $row['category_name'] ?? 'Uncategorized';
            $remark_from_db = trim($row['remarks'] ?? '');
            $key = $categoryName . "||" . $typeName; 

            if (!isset($temp_summary_data[$key])) {
                $temp_summary_data[$key] = [
                    'category' => $categoryName,
                    'type' => $typeName,
                    'count' => 0,
                    'remark_display' => empty($remark_from_db) ? 'No remarks' : $remark_from_db 
                ];
            }
            $temp_summary_data[$key]['count']++;
            
            if ($temp_summary_data[$key]['count'] > 1) {
                $temp_summary_data[$key]['remark_display'] = '(Multiple instances - see log)'; 
            }
        }
        $stmt_violations_updated->close();
        
        foreach($temp_summary_data as $data_item){
            $violation_summary[] = $data_item;
            if ($data_item['count'] >= 2) {
                $has_sanctionable_offense = true;
            }
        }
        
        usort($violation_summary, function($a, $b) {
            $catComp = strcmp($a['category'], $b['category']);
            if ($catComp == 0) {
                return strcmp($a['type'], $b['type']);
            }
            return $catComp;
        });

    } else {
        if (!$page_error) $page_error = "Could not load violation records.";
    }
}

if (isset($conn) && !empty($student_stud_number_for_page_violations)) {
    $sql_sanction_records = "SELECT ssr.record_id, ssr.date_assigned, ssr.status,
                                    ds.disciplinary_sanction,
                                    v.description AS violation_remarks,
                                    vt.violation_type AS violation_type_name
                             FROM student_sanction_records_tbl ssr
                             JOIN disciplinary_sanctions ds ON ssr.assigned_sanction_id = ds.disciplinary_sanction_id
                             LEFT JOIN violation_tbl v ON ssr.violation_id = v.violation_id
                             LEFT JOIN violation_type_tbl vt ON v.violation_type = vt.violation_type_id
                             WHERE ssr.student_number = ?
                             ORDER BY ssr.date_assigned DESC";
    
    if ($stmt_sanction_records = $conn->prepare($sql_sanction_records)) {
        $stmt_sanction_records->bind_param("s", $student_stud_number_for_page_violations);
        $stmt_sanction_records->execute();
        $result_sanction_records = $stmt_sanction_records->get_result();
        while ($row = $result_sanction_records->fetch_assoc()) {
            $sanction_records[] = $row;
        }
        $stmt_sanction_records->close();
    } else {
        if (!$page_error) $page_error = "Could not load sanction records. " . $conn->error;
    }
}


$total_individual_violations = count($violations_log);
$button_disabled = !$has_sanctionable_offense;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Record</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="./student_style.css">
</head>
<body>
    
<header class="main-header">
    <div class="header-content">
        <div class="logo">
            <img src="../IMAGE/Tracker-logo.png" alt="PUP Logo">
        </div>

        <nav class="main-nav" id="primary-navigation">
            <div class="nav-links">
                <a href="./student_dashboard.php">Home</a>
                <a href="./student_record.php" class="active-nav">Record</a>
                <a href="./student_announcements.php">Announcements</a>

                <div class="mobile-only">
                    <a href="./student_account.php" class="profile-icon admin">
                        <svg class="header-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                        <span>My Account</span>
                    </a>
                    <a href="../PHP/logout.php" class="logout-link">
                        <svg class="header-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16 17v-3H9v-4h7V7l5 5-5 5zM14 2a2 2 0 0 1 2 2v2h-2V4H5v16h9v-2h2v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9z"></path></svg>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </nav>

        <div class="header-actions">
            <div class="notification-icon-area">
                <a href="#" class="notification" id="notificationLinkToggle">
                    <svg class="header-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19 13.586V10c0-3.217-2.185-5.927-5.145-6.742C13.562 2.52 12.846 2 12 2s-1.562.52-1.855 1.258C7.185 4.073 5 6.783 5 10v3.586l-1.707 1.707A.996.996 0 0 0 3 16v2a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1v-2a.996.996 0 0 0-.293-.707L19 13.586zM19 17H5v-.586l1.707-1.707A.996.996 0 0 0 7 14v-4c0-2.757 2.243-5 5-5s5 2.243 5 5v4c0 .266.105.52.293.707L19 16.414V17zm-7 5a2.98 2.98 0 0 0 2.818-2H9.182A2.98 2.98 0 0 0 12 22z"/></svg>
                    <?php if ($unread_notification_count_header > 0): ?>
                        <span class="notification-count"><?php echo $unread_notification_count_header; ?></span>
                    <?php endif; ?>
                </a>
                <div class="notifications-dropdown" id="notificationsDropdownContent">
                    <div class="notification-header">
                        <h3>Notifications</h3>
                        <button id="mark-all-read-btn">Mark all as read</button>
                    </div>
                    <ul class="notification-list">
                        <?php if (!empty($unread_notifications_header)): ?>
                            <?php foreach ($unread_notifications_header as $notification_h): ?>
                                <li class="notification-item">
                                    <div class="notification-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M20 2H4c-1.103 0-2 .897-2 2v18l4-4h14c1.103 0 2-.897 2-2V4c0-1.103-.897-2-2-2zm-3 9h-4v4h-2v-4H7V9h4V5h2v4h4v2z"/></svg>
                                    </div>
                                    <div class="notification-details">
                                        <p class="notification-message"><?php echo htmlspecialchars($notification_h['message']); ?></p>
                                        <small class="notification-timestamp"><?php echo date("M d, Y h:i A", strtotime($notification_h['created_at'])); ?></small>
                                    </div>
                                    <a href="./mark_notification_read.php?id=<?php echo $notification_h['notification_id']; ?>&redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="mark-as-read-btn" title="Mark as read">
                                        <span class="read-dot-icon"></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="no-notifications">No new notifications.</li>
                        <?php endif; ?>
                    </ul>
                    <div class="notification-footer">
                        <a href="./all_notifications.php" class="view-all-notifications-link">View All Notifications</a>
                    </div>
                </div>
            </div>
            <a href="./student_account.php" class="profile-icon admin desktop-only">
                <svg class="header-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            </a>
            <button class="mobile-nav-toggle" aria-controls="primary-navigation" aria-expanded="false">
                <span class="sr-only">Menu</span>
            </button>
        </div>
    </div>
</header>

<main>
    <div class="record-wrapper">
    <?php if (isset($page_error)): ?>
        <p class="error-message"><?php echo htmlspecialchars($page_error); ?></p>
    <?php elseif ($student_details): ?>
        <h1 class="page-main-title">Student Record</h1>

        <div class="info-block">
            <p class="student-name">
                <?php echo $student_details['FirstNameDisplay'] . " " . $student_details['MiddleNameDisplay'] . " " . $student_details['LastNameDisplay']; ?>
            </p>
            <p class="meta-text"><strong>Student Number:</strong> <?php echo $student_details['StudNumberDisplay']; ?></p>
            <div class="meta-info-group">
                <p class="meta-text"><strong>Course:</strong> <span><?php echo $student_details['CourseNameDisplay']; ?></span></p>
                <p class="meta-text"><strong>Year:</strong> <span><?php echo $student_details['YearDisplay']; ?></span></p>
                <p class="meta-text"><strong>Section:</strong> <span><?php echo $student_details['SectionNameDisplay']; ?></span></p>
            </div>
        </div>

        <div class="tabs-navigation">
            <button class="tab-button active-tab-button" data-tab="violationRecordContent">Violation Record</button>
            <button class="tab-button" data-tab="sanctionRecordContent">Sanction Record</button>
        </div>

        <div id="violationRecordContent" class="tab-content active-tab">
            <div class="highlight-panel">
                <p>Total Violations Committed: <strong><?php echo $total_individual_violations; ?></strong></p>
            </div>
            
            <div class="scrollable-tables-area">
                <h3 class="section-title">Summary by Violation</h3>
                <div class="table-container">
                    <table class="data-table" id="summaryViolationTable">
                        <thead>
                            <tr>
                                <th>Violation Type</th>
                                <th>Offense</th>
                                <th>Commits</th>
                                <th>Category</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($violation_summary)): ?>
                                <?php foreach ($violation_summary as $summary_item): ?>
                                    <tr class="mobile-accordion-row">
                                        <td data-label="Violation Type" class="cell-summary"><?php echo htmlspecialchars($summary_item['type']); ?></td>
                                        <td data-label="Offense" class="cell-summary">
                                            <?php
                                            $isSanction = $summary_item['count'] >= 2;
                                            $typeOffenseStatus = $isSanction ? 'Sanction' : 'Warning';
                                            $typeOffenseClass = $isSanction ? 'offense-tag-sanction' : 'offense-tag-warning';
                                            echo "<span class='offense-tag " . $typeOffenseClass . "'>" . htmlspecialchars($typeOffenseStatus) . "</span>";
                                            ?>
                                        </td>
                                        <td data-label="Commits" class="cell-details"><?php echo $summary_item['count']; ?></td>
                                        <td data-label="Category" class="cell-details"><?php echo htmlspecialchars($summary_item['category']); ?></td>
                                        <td data-label="Remarks" class="cell-details"><?php echo nl2br(htmlspecialchars($summary_item['remark_display'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="no-records-message">No violation records found to summarize.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <h3 class="section-title">Individual Violations Log</h3>
                <div class="table-container">
                    <table class="data-table" id="individualViolationsTable">
                        <thead>
                            <tr>
                                <th>Violation Type</th>
                                <th>Date of Violation</th>
                                <th>Category</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($violations_log)): ?>
                                <?php foreach ($violations_log as $record): ?>
                                    <tr class="mobile-accordion-row">
                                        <td data-label="Violation Type" class="cell-summary"><?php echo htmlspecialchars($record['violation_type']); ?></td>
                                        <td data-label="Date" class="cell-summary"><?php echo htmlspecialchars(date("M d, Y, h:i a", strtotime($record['violation_date']))); ?></td>
                                        <td data-label="Category" class="cell-details"><?php echo htmlspecialchars($record['category_name'] ?? 'N/A'); ?></td>
                                        <td data-label="Remarks" class="cell-details"><?php echo nl2br(htmlspecialchars(trim($record['remarks'] ?? '') === '' ? 'No remarks' : $record['remarks'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="no-records-message">No individual violation records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="sanctionRecordContent" class="tab-content">
            <h3 class="section-title">Your Sanction Records</h3>
            <div class="table-container">
                <table class="data-table" id="sanctionRecordsTable">
                    <thead>
                        <tr>
                            <th>Violation Type</th>
                            <th>Status</th>
                            <th>Record ID</th>
                            <th>Date Assigned</th>
                            <th>Sanction Details</th>
                            <th>Violation Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($sanction_records)): ?>
                            <?php foreach ($sanction_records as $sanction): ?>
                                <tr class="mobile-accordion-row">
                                    <td data-label="Violation Type" class="cell-summary"><?php echo htmlspecialchars($sanction['violation_type_name'] ?? 'N/A'); ?></td>
                                    <td data-label="Status" class="cell-summary">
                                        <?php
                                        $statusClass = '';
                                        switch (strtolower($sanction['status'])) {
                                            case 'pending': $statusClass = 'offense-tag-warning'; break;
                                            case 'approved': $statusClass = 'offense-tag-sanction'; break;
                                            case 'completed': $statusClass = 'offense-tag-completed'; break;
                                            case 'declined': $statusClass = 'offense-tag-sanction'; break;
                                        }
                                        echo "<span class='offense-tag " . $statusClass . "'>" . htmlspecialchars($sanction['status']) . "</span>";
                                        ?>
                                    </td>
                                    <td data-label="Record ID" class="cell-details"><?php echo htmlspecialchars($sanction['record_id']); ?></td>
                                    <td data-label="Date Assigned" class="cell-details"><?php echo htmlspecialchars(date("M d, Y, h:i a", strtotime($sanction['date_assigned']))); ?></td>
                                    <td data-label="Sanction Details" class="cell-details"><?php echo nl2br(htmlspecialchars($sanction['disciplinary_sanction'] ?? 'N/A')); ?></td>
                                    <td data-label="Violation Remarks" class="cell-details"><?php echo nl2br(htmlspecialchars(trim($sanction['violation_remarks'] ?? '') === '' ? 'No remarks' : $sanction['violation_remarks'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="no-records-message">No sanction records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php else: ?>
        <div class="info-block">
            <h2 class="student-name">Student Details Not Found</h2>
            <p class="meta-text">There might be an issue with your session or the user ID. Please try logging out and logging back in.</p>
        </div>
    <?php endif; ?>
    </div>

    <div id="confirmationOverlay" class="overlay-container" style="display:none;">
        <div class="dialog-content">
            <p>Successfully Requested</p>
            <button id="closeOverlayButton" class="button-grey">Close</button>
        </div>
    </div>
</main>

<?php 
if ($student_details): ?>
<div class="actions-panel-sticky">
    <div class="actions-panel-content">
        <p class="reminder-paragraph">
            Please be reminded to take your sanction before the graduation. If you want to take your sanction kindly click the
            ‘Request Sanction’ button to directly request to the Head of Office of Student Services (OSS) or go to the Building B Office.
        </p>
        <div class="sanction-request-bar">
            <p>Total Individual Violation Instances: <strong><?php echo $total_individual_violations; ?></strong></p>
            
            <button id="requestSanctionButton" class="btn btn-primary" <?php if ($button_disabled) echo 'disabled'; ?>>
                Request Sanction
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="./student_scripts.js"></script>
    <?php
        if (isset($conn)) {
            $conn->close();
        }
    ?>
</body>
</html>