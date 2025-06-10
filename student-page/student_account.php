<?php
require_once "../PHP/dbcon.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["current_user_id"]) || !isset($_SESSION["user_student_number"])) {
    header("Location: ./student_login.php");
    exit();
}

$session_user_id_for_account = $_SESSION["current_user_id"];
$student_stud_number_from_session = $_SESSION["user_student_number"];


if (isset($_GET['notif_id']) && is_numeric($_GET['notif_id']) && isset($conn)) {
    $notification_id_to_mark = (int)$_GET['notif_id'];
    if (!empty($student_stud_number_from_session)) {
        $sql_mark_direct = "UPDATE notifications_tbl SET is_read = TRUE 
                            WHERE notification_id = ? AND student_number = ?";
        if ($stmt_mark_direct = $conn->prepare($sql_mark_direct)) {
            $stmt_mark_direct->bind_param("is", $notification_id_to_mark, $student_stud_number_from_session);
            $stmt_mark_direct->execute();
            $stmt_mark_direct->close();
        }
    }
}

$unread_notifications_header = [];
$unread_notification_count_header = 0;

if (isset($conn) && !empty($student_stud_number_from_session)) {
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

$student_info = null;
$page_error = null;

$sql_student = "SELECT u.first_name, u.middle_name, u.last_name, u.student_number, u.email,
                       c.course_name,
                       s.section_name,
                       y.year AS year_level,
                       g.gender_name
                  FROM users_tbl u
                  LEFT JOIN course_tbl c ON u.course_id = c.course_id
                  LEFT JOIN section_tbl s ON u.section_id = s.section_id
                  LEFT JOIN year_tbl y ON u.year_id = y.year_id
                  LEFT JOIN gender_tbl g ON u.gender_id = g.gender_id
                  WHERE u.user_id = ?";

if (isset($conn)) {
    if ($stmt_student = $conn->prepare($sql_student)) {
        $stmt_student->bind_param("i", $session_user_id_for_account);
        $stmt_student->execute();
        $result_student = $stmt_student->get_result();
        if ($result_student->num_rows == 1) {
            $student_info = $result_student->fetch_assoc();
        } else {
            $page_error = "Student information not found for your account.";
        }
        $stmt_student->close();
    } else {
        $page_error = "An error occurred while fetching your account details.";
    }
} else {
    $page_error = "Database connection error. Cannot fetch account details.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Account</title>
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
            <nav class="main-nav">
                <a href="./student_dashboard.php">Home</a>
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
                <a href="./student_account.php" class="profile-icon admin active-nav">
                     <svg class="header-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                </a>
            </div>
        </div>
    </header>

<main>
    <div class="account-wrapper">
        <h1 class="page-main-title">Account Information</h1>
        <?php if ($page_error): ?>
            <p class="error-message"><?php echo htmlspecialchars($page_error); ?></p>
        <?php endif; ?>

        <?php if ($student_info): ?>
        <ul class="info-list">
            <li class="info-list-item">
                <span class="info-label"><svg class="info-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 5.9c1.16 0 2.1.94 2.1 2.1s-.94 2.1-2.1 2.1S9.9 9.16 9.9 8s.94-2.1 2.1-2.1m0 9c2.97 0 6.1 1.46 6.1 2.1v1.1H5.9V17c0-.64 3.13-2.1 6.1-2.1M12 4C9.79 4 8 5.79 8 8s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm0 9c-2.67 0-8 1.34-8 4v3h16v-3c0-2.66-5.33-4-8-4z"/></svg>First Name</span>
                <span class="info-value"><?php echo htmlspecialchars($student_info['first_name'] ?? 'N/A'); ?></span>
            </li>
            <li class="info-list-item">
                <span class="info-label"><svg class="info-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 5.9c1.16 0 2.1.94 2.1 2.1s-.94 2.1-2.1 2.1S9.9 9.16 9.9 8s.94-2.1 2.1-2.1m0 9c2.97 0 6.1 1.46 6.1 2.1v1.1H5.9V17c0-.64 3.13-2.1 6.1-2.1M12 4C9.79 4 8 5.79 8 8s1.79 4 4 4 4-1.79-4-4-1.79-4-4-4zm0 9c-2.67 0-8 1.34-8 4v3h16v-3c0-2.66-5.33-4-8-4z"/></svg>Middle Name</span>
                <span class="info-value"><?php echo htmlspecialchars($student_info['middle_name'] ?? 'N/A'); ?></span>
            </li>
            <li class="info-list-item">
                <span class="info-label"><svg class="info-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 5.9c1.16 0 2.1.94 2.1 2.1s-.94 2.1-2.1 2.1S9.9 9.16 9.9 8s.94-2.1 2.1-2.1m0 9c2.97 0 6.1 1.46 6.1 2.1v1.1H5.9V17c0-.64 3.13-2.1 6.1-2.1M12 4C9.79 4 8 5.79 8 8s1.79 4 4 4 4-1.79-4-4-1.79-4-4-4zm0 9c-2.67 0-8 1.34-8 4v3h16v-3c0-2.66-5.33-4-8-4z"/></svg>Last Name</span>
                <span class="info-value"><?php echo htmlspecialchars($student_info['last_name'] ?? 'N/A'); ?></span>
            </li>
            <li class="info-list-item">
                <span class="info-label"><svg class="info-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 4c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm0 14c-2.03 0-4.43-.82-6.14-2.88.02-2.34 4.08-3.62 6.14-3.62 2.06 0 6.12 1.28 6.14 3.62C16.43 19.18 14.03 20 12 20z"/></svg>Gender</span>
                <span class="info-value"><?php echo htmlspecialchars($student_info['gender_name'] ?? 'N/A'); ?></span>
            </li>
            <li class="info-list-item">
                <span class="info-label"><svg class="info-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3 5v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2H5c-1.11 0-2 .9-2 2zm12 4c0 1.66-1.34 3-3 3s-3-1.34-3-3 1.34-3 3-3 3 1.34 3 3zm-9 8c0-2 4-3.1 6-3.1s6 1.1 6 3.1v1H6v-1z"/></svg>Student Number</span>
                <span class="info-value"><?php echo htmlspecialchars($student_info['student_number'] ?? 'N/A'); ?></span>
            </li>
            <li class="info-list-item">
                <span class="info-label"><svg class="info-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"/></svg>Email</span>
                <span class="info-value"><?php echo htmlspecialchars($student_info['email'] ?? 'N/A'); ?></span>
            </li>
            <li class="info-list-item">
                <span class="info-label"><svg class="info-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="m12 12 5-5-5-5-5 5 5 5zm0 2L7 9l-5 5 5 5 5-5zm0 0 5 5 5-5-5-5-5 5z"/></svg>Course</span>
                <span class="info-value"><?php echo htmlspecialchars($student_info['course_name'] ?? 'N/A'); ?></span>
            </li>
            <li class="info-list-item">
                <span class="info-label"><svg class="info-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm-.5-13h1v6h-1zm.5 10c.55 0 1-.45 1-1s-.45-1-1-1-1 .45-1 1 .45 1 1 1z"/></svg>Year Level</span>
                <span class="info-value"><?php echo htmlspecialchars($student_info['year_level'] ?? 'N/A'); ?></span>
            </li>
            <li class="info-list-item">
                <span class="info-label"><svg class="info-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/></svg>Section</span>
                <span class="info-value"><?php echo htmlspecialchars($student_info['section_name'] ?? 'N/A'); ?></span>
            </li>
        </ul>
        <div class="button-container">
             <a href="../PHP/logout.php?role=student" id="signOutBtn" class="sign-out-button">Sign Out</a>
        </div>
        <?php elseif (!$page_error): ?>
            <p class="error-message">Could not retrieve student information.</p>
        <?php endif; ?>
    </div>
</main>
<script src="./student_scripts.js"></script>
<?php
    if (isset($conn)) {
        $conn->close();
    }
?>
</body>
</html>