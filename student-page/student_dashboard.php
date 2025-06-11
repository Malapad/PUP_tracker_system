<?php
require_once '../PHP/dbcon.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["current_user_id"]) || !isset($_SESSION["user_student_number"])) {
    header("Location: ./student_login.php");
    exit();
}

$student_stud_number_from_session = $_SESSION["user_student_number"];

$violations_query = "SELECT vt.violation_category_id, vt.violation_type
                     FROM violation_tbl v
                     JOIN violation_type_tbl vt ON v.violation_type = vt.violation_type_id
                     WHERE v.student_number = ?";

$violations_by_category = [];
$total_violations = 0;

if ($stmt_violations = $conn->prepare($violations_query)) {
    $stmt_violations->bind_param("s", $student_stud_number_from_session);
    $stmt_violations->execute();
    $result_violations = $stmt_violations->get_result();
    while ($row = $result_violations->fetch_assoc()) {
        $cat_id = $row['violation_category_id'];
        if (!isset($violations_by_category[$cat_id])) {
            $violations_by_category[$cat_id] = 0;
        }
        $violations_by_category[$cat_id]++;
        $total_violations++;
    }
    $stmt_violations->close();
}


$offense_status = "Clear";
$offense_status_class = "status-clear";

if ($total_violations > 0) {
    $offense_status = "Warning";
    $offense_status_class = "status-warning";
    
    foreach ($violations_by_category as $count) {
        if ($count >= 2) {
            $offense_status = "Sanction";
            $offense_status_class = "status-sanction";
            break;
        }
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./student_style.css">
</head>
<body>
    <header class="main-header">
        <div class="header-content">
            <div class="logo">
                <img src="../IMAGE//Tracker-logo.png" alt="PUP Logo">
            </div>
            <nav class="main-nav">
                <a href="./student_dashboard.php" class="active-nav">Home</a>
                <a href="./student_record.php">Record</a>
                <a href="./student_announcements.php">Announcements</a>
            </nav>
            <div class="user-icons">
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
                <a href="./student_account.php" class="profile-icon admin">
                     <svg class="header-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                </a>
            </div>
        </div>
    </header>

<main>
    <div class="dashboard-wrapper">
        <h1 class="dashboard-page-title">Student Dashboard</h1>
        <div class="dashboard-grid">
            <div class="grid-column summary-column">
                <h2 class="section-title">Your Violation Summary</h2>
                <div class="stat-cards-container">
                    <a href="./student_record.php" class="stat-card-link">
                        <div class="stat-card">
                            <div class="stat-card-value"><?php echo $total_violations; ?></div>
                            <div class="stat-card-label">Total Violations Committed</div>
                        </div>
                    </a>
                    <div class="stat-card <?php echo $offense_status_class; ?>">
                        <div class="stat-card-value"><?php echo htmlspecialchars($offense_status); ?></div>
                        <div class="stat-card-label">Offense Status</div>
                    </div>
                </div>
                 <?php if ($total_violations > 0): ?>
                    <p class="summary-message">Please review your '<a href="./student_record.php"><strong>Record</strong></a>' for details on your recent violations.</p>
                 <?php else: ?>
                    <p class="no-violations-message">Keep up the good work! No violations recorded.</p>
                <?php endif; ?>
            </div>
            <div class="grid-column handbook-column">
                <h2 class="section-title">Student Handbook</h2>
                <div class="search-bar">
                    <input type="text" id="handbook-search-input" placeholder="Search category or type...">
                </div>
                <div class="accordion-container">
                <?php
                $handbookQuery = "SELECT vc.violation_category_id, vc.category_name, vt.violation_type 
                                  FROM violation_category_tbl vc 
                                  LEFT JOIN violation_type_tbl vt ON vc.violation_category_id = vt.violation_category_id
                                  ORDER BY vc.category_name, vt.violation_type";
                $handbookResult = $conn->query($handbookQuery);
                $handbookData = [];
                while($row = $handbookResult->fetch_assoc()) {
                    $handbookData[$row['category_name']]['id'] = $row['violation_category_id'];
                    $handbookData[$row['category_name']]['violations'][] = $row['violation_type'];
                }

                if (!empty($handbookData)) {
                  foreach ($handbookData as $categoryName => $data) {
                    $cat_id = $data['id'];
                    $violations = $data['violations'];
                    ?>
                    <details class="accordion-item">
                      <summary class="accordion-header">
                        <span><?= htmlspecialchars($categoryName) ?></span>
                        <?php if (isset($violations_by_category[$cat_id]) && $violations_by_category[$cat_id] > 0): ?>
                            <span class="violation-count-badge"><?= $violations_by_category[$cat_id] ?></span>
                        <?php endif; ?>
                      </summary>
                      <div class="accordion-content">
                        <ul>
                          <?php
                          if (!empty($violations) && $violations[0] !== null) {
                            foreach ($violations as $violation) {
                              echo "<li>" . htmlspecialchars($violation) . "</li>";
                            }
                          } else {
                            echo "<li class='no-match'>No violation types listed.</li>";
                          }
                          ?>
                        </ul>
                      </div>
                    </details>
                    <?php
                  }
                } else {
                  echo "<p class='no-records'>No Handbook Records Found</p>";
                }
                ?>
                <p id="no-results-message" style="display: none;">No matching records found.</p>
                </div>
            </div>
        </div>
    </div>
</main>
<script src="./student_scripts.js"></script>
</body>
</html>