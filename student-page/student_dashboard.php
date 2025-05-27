<?php
require_once '../PHP/dbcon.php';
session_start();

if (!isset($_SESSION["current_user_id"]) || !isset($_SESSION["user_student_number"])) {
    header("Location: ./student_login.php");
    exit();
}

$student_stud_number_from_session = $_SESSION["user_student_number"];
$page_error_message_dashboard = null;

$unread_notifications_for_header = [];
$unread_notification_count_for_header = 0;

if (isset($conn) && $conn instanceof mysqli) {
    $sql_notifications_list = "SELECT notification_id, message, created_at, link
                                FROM notifications_tbl
                                WHERE student_number = ? AND is_read = FALSE
                                ORDER BY created_at DESC LIMIT 5";
    if ($stmt_notifications_list = $conn->prepare($sql_notifications_list)) {
        $stmt_notifications_list->bind_param("s", $student_stud_number_from_session);
        $stmt_notifications_list->execute();
        $result_notifications_list = $stmt_notifications_list->get_result();
        while ($row_notif = $result_notifications_list->fetch_assoc()) {
            $unread_notifications_for_header[] = $row_notif;
        }
        $stmt_notifications_list->close();
    } else { error_log("DB Error (student_dashboard.php - header list): " . $conn->error); }

    $sql_notifications_count = "SELECT COUNT(*) as total_unread FROM notifications_tbl WHERE student_number = ? AND is_read = FALSE";
    if ($stmt_notifications_count = $conn->prepare($sql_notifications_count)) {
        $stmt_notifications_count->bind_param("s", $student_stud_number_from_session);
        $stmt_notifications_count->execute();
        $result_count = $stmt_notifications_count->get_result()->fetch_assoc();
        $unread_notification_count_for_header = $result_count['total_unread'] ?? 0;
        $stmt_notifications_count->close();
    } else { error_log("DB Error (student_dashboard.php - header count): " . $conn->error); }

    $violations = [];
    $sql_violations = "SELECT v.description, v.violation_date, vt.violation_type FROM violation_tbl v LEFT JOIN violation_type_tbl vt ON v.violation_type = vt.violation_type_id WHERE v.student_number = ? ORDER BY v.violation_date DESC";
    if ($stmt_violations = $conn->prepare($sql_violations)) {
        $stmt_violations->bind_param("s", $student_stud_number_from_session);
        $stmt_violations->execute();
        $result_violations = $stmt_violations->get_result();
        while ($row = $result_violations->fetch_assoc()) { $violations[] = $row; }
        $stmt_violations->close();
    } else { error_log("DB Error (student_dashboard.php - violations): " . htmlspecialchars($conn->error)); $page_error_message_dashboard = "Could not load violation summary."; }
    $total_violations = count($violations);
    $offense_status = "Clear"; $status_color = "green";
    if ($total_violations > 0) {
        $violation_type_counts = [];
        foreach ($violations as $v) { $typeName = $v['violation_type'] ?? 'Unknown Type'; if (!isset($violation_type_counts[$typeName])) { $violation_type_counts[$typeName] = 0; } $violation_type_counts[$typeName]++; }
        $offense_status = "Warning"; $status_color = "orange";
        foreach ($violation_type_counts as $type => $count) { if ($count >= 2) { $offense_status = "Sanction"; $status_color = "#800000"; break; } }
    }
    $handbook_violations = [];
    $sql_handbook = "SELECT violation_type_id, violation_type FROM violation_type_tbl ORDER BY violation_type";
    if ($stmt_handbook = $conn->prepare($sql_handbook)) {
        $stmt_handbook->execute();
        $result_handbook = $stmt_handbook->get_result();
        while ($row_hb = $result_handbook->fetch_assoc()) { $handbook_violations[] = $row_hb; }
        $stmt_handbook->close();
    } else { error_log("DB Error (student_dashboard.php - handbook): " . htmlspecialchars($conn->error));}


} else {
    error_log("Database connection not available in student_dashboard.");
    $page_error_message_dashboard = "Database connection error. Please try again later.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="./student_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <header>
        <div class="logo">
            <img src="../assets/PUPlogo.png" alt="PUP Logo">
        </div>
        <nav>
            <a href="./student_dashboard.php" class="active-nav">Home</a>
            <a href="./student_record.php">Record</a>
        </nav>
        <div class="admin-icons">
            <div class="notification-icon-area">
                <a href="#" class="notification" id="notificationLinkToggle">
                    <img src="https://img.icons8.com/?size=100&id=83193&format=png&color=000000" alt="Notifications"/>
                    <?php if ($unread_notification_count_for_header > 0): ?>
                        <span class="notification-count"><?php echo $unread_notification_count_for_header; ?></span>
                    <?php endif; ?>
                </a>
                <div class="notifications-dropdown" id="notificationsDropdownContent">
                    <ul>
                        <?php if (!empty($unread_notifications_for_header)): ?>
                            <?php foreach ($unread_notifications_for_header as $notification): ?>
                                <li class="notification-item">
                                    <a href="<?php echo !empty($notification['link']) ? htmlspecialchars($notification['link']) . (strpos($notification['link'], '?') === false ? '?' : '&') . 'notif_id=' . $notification['notification_id'] : '#'; ?>"
                                        class="notification-message-link"
                                        data-notification-id="<?php echo $notification['notification_id']; ?>">
                                        <?php echo htmlspecialchars(strip_tags($notification['message'])); ?>
                                        <small class="notification-timestamp"><?php echo date("M d, h:i A", strtotime($notification['created_at'])); ?></small>
                                    </a>
                                        <a href="./mark_notification_read.php?id=<?php echo $notification['notification_id']; ?>&page=dashboard" class="mark-as-read-link">Mark read</a>
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
            <a href="./student_account.php" class="admin">
                <img src="https://img.icons8.com/?size=100&id=77883&format=png&color=000000" alt="Account"/>
            </a>
        </div>
    </header>

    <main>
        <h3 class="dashboard-page-title">Student Dashboard</h3>
    <?php if ($page_error_message_dashboard): ?>
        <div class="error-message-container" style="margin: 0 auto 20px auto; max-width: 800px;">
            <?php echo htmlspecialchars($page_error_message_dashboard); ?>
        </div>
    <?php endif; ?>
    <div class="container">
        <div class="box">
            <div class="header">Your Violation Summary</div>
            <div class="content violation-summary-content">
                <?php if (!$page_error_message_dashboard || $total_violations > 0): ?>
                    <p><span class="summary-label">Total Violations Committed:</span> <span class="summary-value"><?php echo $total_violations; ?></span></p>
                    <p>
                        <span class="summary-label">Offense Status:</span>
                        <span class="summary-value offense-status-text" style="color: <?php echo htmlspecialchars($status_color); ?>;">
                            <?php echo htmlspecialchars($offense_status); ?>
                        </span>
                    </p>
                    <?php if ($offense_status === 'Warning' && $total_violations > 0): ?>
                        <p class="summary-message">Please review your '<a href="./student_record.php"><strong>Record</strong></a>' for details on your recent violations.</p>
                    <?php elseif ($offense_status === 'Sanction' && $total_violations > 0): ?>
                        <p class="summary-message">A '<strong>Sanction</strong>' status indicates repeated violations. See your '<a href="./student_record.php"><strong>Record</strong></a>' for more information.</p>
                    <?php elseif ($total_violations === 0 && !$page_error_message_dashboard): ?>
                        <p class="no-violations-message">Keep up the good work! No violations recorded.</p>
                    <?php endif; ?>
                <?php elseif (!$page_error_message_dashboard): ?>
                    <p class="no-violations-message">No violation data available at this time.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="box">
            <div class="header">Student Handbook - Violation Types</div>
            <div class="content handbook-content">
                <form id="handbookSearchForm" onsubmit="event.preventDefault(); handbookSearch();">
                    <div class="search-wrapper">
                        <input type="text" id="handbookSearchInput" placeholder="Search violation type..." aria-label="Search handbook violations"/>
                        <button type="submit">Search</button>
                    </div>
                </form>
                <ul id="handbookViolationList">
                    <?php if (!empty($handbook_violations)): ?>
                        <?php foreach ($handbook_violations as $hb_v): ?>
                            <li class='handbook-item'>
                                <a href='#' class='handbook-link' data-id='<?php echo htmlspecialchars($hb_v['violation_type_id']); ?>' 
                                    onclick="event.preventDefault(); alert('Details for: <?php echo htmlspecialchars(addslashes($hb_v['violation_type'])); ?> (ID: <?php echo htmlspecialchars($hb_v['violation_type_id']); ?>) - Implement actual detail view.');">
                                    <?php echo htmlspecialchars($hb_v['violation_type']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php elseif (!$page_error_message_dashboard): ?>
                        <li class="no-handbook-items-message">No violation types found in the handbook.</li>
                    <?php else: ?>
                        <li class="no-handbook-items-message">Could not load handbook items.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
    </main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const notificationLinkToggle = document.getElementById('notificationLinkToggle');
    const notificationsDropdownContent = document.getElementById('notificationsDropdownContent');
    if(notificationLinkToggle && notificationsDropdownContent) {
        notificationLinkToggle.addEventListener('click', function(event) {
            event.preventDefault();
            notificationsDropdownContent.classList.toggle('show');
        });
    }
    document.addEventListener('click', function(event) {
        if (notificationsDropdownContent && notificationLinkToggle) {
            if (!notificationLinkToggle.contains(event.target) && !notificationsDropdownContent.contains(event.target)) {
                notificationsDropdownContent.classList.remove('show');
            }
        }
    });
});
function handbookSearch() {
    const searchTerm = document.getElementById('handbookSearchInput').value.toLowerCase();
    const list = document.getElementById('handbookViolationList');
    const listItems = list.querySelectorAll('li.handbook-item');
    let foundItems = 0;
    listItems.forEach(item => {
        const text = item.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            item.style.display = '';
            foundItems++;
        } else {
            item.style.display = 'none';
        }
    });
    let noResultsMessage = list.querySelector('.no-handbook-items-message.search-specific');
    if (foundItems === 0 && searchTerm) {
        if (!noResultsMessage) {
            noResultsMessage = document.createElement('li');
            noResultsMessage.className = 'no-handbook-items-message search-specific';
            list.appendChild(noResultsMessage);
        }
        noResultsMessage.textContent = 'No violation types match your search.';
        noResultsMessage.style.display = '';
    } else if (noResultsMessage) {
            noResultsMessage.style.display = 'none';
    }
}
const handbookSearchInputElem = document.getElementById('handbookSearchInput');
if (handbookSearchInputElem) {
    handbookSearchInputElem.addEventListener('input', handbookSearch);
}
</script>
</body>
</html>
<?php
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>