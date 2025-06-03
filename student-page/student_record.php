<?php
require_once "../PHP/dbcon.php";
session_start();

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
        if (!$stmt_mark_direct->execute()) {
            error_log("Error marking direct notification as read in " . basename(__FILE__) . ": " . $stmt_mark_direct->error);
        }
        $stmt_mark_direct->close();
    } else {
        error_log("Error preparing direct mark_read query in " . basename(__FILE__) . ": " . $conn->error);
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
    } else {
        error_log("Error preparing notification header list query in " . basename(__FILE__) . ": " . $conn->error);
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
    } else {
        error_log("Error preparing notification count query in " . basename(__FILE__) . ": " . $conn->error);
    }
} else {
    error_log("Database connection not available in " . basename(__FILE__) . " for fetching header notifications.");
}

$student_details = null;
$violations_log = [];
$violation_summary = [];
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
        
        if ($student_stud_number_from_session !== $student_stud_number_for_page_violations) {
            error_log("Session student number mismatch in student_record.php. Session: $student_stud_number_from_session, DB: $student_stud_number_for_page_violations for user_id: $session_user_id");
        }

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
            } else {
                error_log("Error preparing year details query: " . $conn->error);
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
    error_log("Error preparing student details query (or DB connection issue): " . (isset($conn) ? $conn->error : 'DB connection not set'));
    $page_error = "Could not load student details. Please try again later.";
}

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
        }
        usort($violation_summary, function($a, $b) {
            $catComp = strcmp($a['category'], $b['category']);
            if ($catComp == 0) {
                return strcmp($a['type'], $b['type']);
            }
            return $catComp;
        });

    } else {
        error_log("Error preparing violation records query: " . $conn->error);
        if (!$page_error) $page_error = "Could not load violation records.";
    }
}

$total_individual_violations = count($violations_log);
$button_disabled = $total_individual_violations === 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Violation Record</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../student-page/student_record_style.css">
    <link rel="stylesheet" href="./student_dashboard_style.css">
</head>
<body>
    <header>
        <div class="logo"><img src="../assets/PUPlogo.png" alt="PUP Logo"></div>
        <nav>
            <a href="./student_dashboard.php">Home</a>
            <a href="./student_record.php" class="active-nav">Record</a>
        </nav>
        <div class="user-icons admin-icons">
            <div class="notification-icon-area">
                <a href="#" class="notification" id="notificationLinkToggle">
                    <img src="https://img.icons8.com/?size=100&id=83193&format=png&color=000000" alt="Notifications"/>
                    <?php if ($unread_notification_count_header > 0): ?>
                        <span class="notification-count"><?php echo $unread_notification_count_header; ?></span>
                    <?php endif; ?>
                </a>
                <div class="notifications-dropdown" id="notificationsDropdownContent">
                    <ul>
                        <?php if (!empty($unread_notifications_header)): ?>
                            <?php foreach ($unread_notifications_header as $notification_h): ?>
                                <li class="notification-item">
                                    <a href="<?php echo !empty($notification_h['link']) ? htmlspecialchars($notification_h['link']) . (strpos($notification_h['link'], '?') === false ? '?' : '&') . 'notif_id=' . $notification_h['notification_id'] : '#'; ?>"
                                       class="notification-message-link"
                                       data-notification-id="<?php echo $notification_h['notification_id']; ?>">
                                        <?php echo htmlspecialchars($notification_h['message']); ?>
                                        <small class="notification-timestamp"><?php echo date("M d, Y h:i A", strtotime($notification_h['created_at'])); ?></small>
                                    </a>
                                    <a href="./mark_notification_read.php?id=<?php echo $notification_h['notification_id']; ?>&redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="mark-as-read-link">Mark read</a>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="no-notifications">No new notifications.</li>
                        <?php endif; ?>
                        <li class="view-all-container">
                            <a href="./all_notifications.php" class="view-all-notifications-link">View All Notifications</a>
                        </li>
                    </ul>
                </div>
            </div>
            <a href="./student_account.php" class="profile-icon admin"><img src="https://img.icons8.com/?size=100&id=77883&format=png&color=000000" alt="Account"/></a>
        </div>
    </header>

<main>
    <div class="page-main-title-container">
        <h1>Violation Record</h1>
    </div>

    <?php if (isset($page_error)): ?>
        <div class="content-wrapper-wide">
            <p class="error-message" style="color: red; text-align:center; padding: 20px; background-color: #ffebee; border: 1px solid red; border-radius: 4px;">
                <?php echo htmlspecialchars($page_error); ?>
            </p>
        </div>
    <?php elseif ($student_details): ?>
        <div class="content-wrapper-wide">
            <div class="info-block-wide">
                <p class="student-name-prominent">
                    <?php echo $student_details['FirstNameDisplay'] . " " . $student_details['MiddleNameDisplay'] . " " . $student_details['LastNameDisplay']; ?>
                </p>
                <p class="meta-text-wide"><strong>Student Number:</strong> <?php echo $student_details['StudNumberDisplay']; ?></p>
                <p class="meta-text-wide">
                    <strong>Course:</strong> <?php echo $student_details['CourseNameDisplay']; ?> |
                    <strong>Year:</strong> <?php echo $student_details['YearDisplay']; ?> |
                    <strong>Section:</strong> <?php echo $student_details['SectionNameDisplay']; ?>
                </p>
            </div>
            
            <hr class="divider-red-wide">

            <div class="highlight-panel-wide">
                <p>Total Violations Committed: <strong><?php echo $total_individual_violations; ?></strong></p>
            </div>

            <h3 class="section-title-styled">Summary by Violation</h3>
            <div class="table-container-wide">
                <table class="data-table-wide" id="summaryViolationTable">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Violation Type</th>
                            <th>Number of commits</th>
                            <th>Offense</th>
                            <th>Remarks (for single instance)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($violation_summary)): ?>
                            <?php foreach ($violation_summary as $summary_item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($summary_item['category']); ?></td>
                                    <td><?php echo htmlspecialchars($summary_item['type']); ?></td>
                                    <td style="text-align: center;"><?php echo $summary_item['count']; ?></td>
                                    <td style="text-align: center;">
                                        <?php
                                        $typeOffenseStatus = ($summary_item['count'] >= 2) ? 'Sanction' : 'Warning';
                                        $typeOffenseClass = ($summary_item['count'] >= 2) ? 'offense-tag-look offense-tag-look-sanction' : 'offense-tag-look offense-tag-look-warning';
                                        echo "<span class='" . $typeOffenseClass . "'>" . htmlspecialchars($typeOffenseStatus) . "</span>";
                                        ?>
                                    </td>
                                    <td><?php echo nl2br(htmlspecialchars($summary_item['remark_display'])); ?></td>
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

            <h3 class="section-title-styled">Individual Violations Log</h3>
            <div class="log-table-scroll-container">
                <table class="data-table-wide" id="individualViolationsTable">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Violation Type</th>
                            <th>Date of Violation</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($violations_log)): ?>
                            <?php foreach ($violations_log as $record): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['category_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($record['violation_type']); ?></td>
                                    <td><?php echo htmlspecialchars(date("M d, Y, h:i a", strtotime($record['violation_date']))); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars(trim($record['remarks'] ?? '') === '' ? 'No remarks' : $record['remarks'])); ?></td>
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

            <div class="student-actions-panel-wide">
                <p class="reminder-paragraph-wide">
                    Please be reminded to take your sanction before the graduation. If you want to take your sanction kindly click the
                    ‘Request Sanction’ button to directly request to the Head of Office of Student Services (OSS) or go to the Building B Office.
                </p>
                <div class="sanction-request-bar-wide">
                    <p class="instance-count-display-wide">Total Individual Violation Instances: <strong><?php echo $total_individual_violations; ?></strong></p>
                    <button id="requestSanctionButtonWide" class="button-green-wide" <?php if ($button_disabled) echo 'disabled'; ?>>
                        Request Sanction
                    </button>
                </div>
            </div>

        </div>
    <?php else: ?>
        <div class="content-wrapper-wide">
            <div class="info-block-wide">
                    <h2 class="student-name-prominent">Student Details Not Found</h2>
                    <p class="meta-text-wide">There might be an issue with your session or the user ID. Please try logging out and logging back in. If the problem persists, contact support.</p>
            </div>
        </div>
    <?php endif; ?>

    <div id="confirmationOverlayWide" class="overlay-container-wide" style="display:none;">
        <div class="dialog-content-wide">
            <p>Successfully Requested</p>
            <button id="closeOverlayButtonWide" class="button-grey-wide">Close</button>
        </div>
    </div>
</main>
<script src="./student_record.js" defer></script>
    <?php
        if (isset($conn)) {
            $conn->close();
        }
    ?>
</body>
</html>