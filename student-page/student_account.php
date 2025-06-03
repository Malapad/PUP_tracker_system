<?php
require_once "../PHP/dbcon.php";
session_start();

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
            if (!$stmt_mark_direct->execute()) {
                error_log("Error marking direct notification as read in student_account.php: " . $stmt_mark_direct->error);
            }
            $stmt_mark_direct->close();
        } else {
            error_log("Error preparing direct mark_read query in student_account.php: " . $conn->error);
        }
    } else {
        error_log("Student number not found in session for marking notification in student_account.php.");
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
    } else {
        error_log("Error preparing notification header list query in student_account.php: " . $conn->error);
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
        error_log("Error preparing notification count query in student_account.php: " . $conn->error);
    }
} elseif (!isset($conn)) {
    error_log("Database connection not available in student_account.php for fetching header notifications.");
} elseif (empty($student_stud_number_from_session)) {
    error_log("Student number not available in session for fetching notifications in student_account.php.");
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
            if ($student_stud_number_from_session !== $student_info['student_number']) {
                 error_log("Session student number (" . $student_stud_number_from_session . ") mismatch with DB student_number (" . $student_info['student_number'] . ") for user_id: " . $session_user_id_for_account . " in student_account.php. Notifications will use session student_number.");
            }
        } else {
            $page_error = "Student information not found for your account.";
            error_log("Student info not found for user_id: $session_user_id_for_account in student_account.php");
        }
        $stmt_student->close();
    } else {
        $page_error = "An error occurred while fetching your account details. Please try again later.";
        error_log("Error preparing student information query in student_account.php: " . $conn->error);
    }
} else {
    $page_error = "Database connection error. Cannot fetch account details.";
    error_log("Database connection not available for student info query in student_account.php.");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Account Information</title>
    <link rel="stylesheet" href="./student_account.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <header>
        <div class="logo">
            <img src="../assets/PUPlogo.png" alt="PUP Logo">
        </div>
        <nav>
            <a href="student_dashboard.php">Home</a>
            <a href="student_record.php">Record</a>
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
                                    <a href="<?php echo !empty($notification_h['link']) ? htmlspecialchars($notification_h['link']) . (strpos($notification_h['link'], '?') === false ? '?' : '&') . 'notif_id=' . $notification_h['notification_id'] : '#_self'; ?>"
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
            <a href="./student_account.php" class="admin profile-icon"> <img src="https://img.icons8.com/?size=100&id=77883&format=png&color=000000" alt="Account"/>
            </a>
        </div>
    </header>

    <div class="container">
        <h1>Account Information</h1>
        <?php if ($page_error): ?>
            <p class="error-message" style="color: red; text-align:center; padding: 10px; background-color: #ffebee; border: 1px solid red;">
                <?php echo htmlspecialchars($page_error); ?>
            </p>
        <?php endif; ?>

        <?php if ($student_info): ?>
        <div class="info-box">
            <div class="info-row">
                <span class="info-label">
                    <i class="fas fa-user icon-style"></i>
                    <strong>First Name:</strong>
                </span>
                <span class="info-value">
                    <?php echo htmlspecialchars($student_info['first_name'] ?? 'N/A'); ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">
                    <i class="fas fa-user icon-style"></i>
                    <strong>Middle Name:</strong>
                </span>
                <span class="info-value">
                    <?php echo htmlspecialchars($student_info['middle_name'] ?? 'N/A'); ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">
                    <i class="fas fa-user icon-style"></i>
                    <strong>Last Name:</strong>
                </span>
                <span class="info-value">
                    <?php echo htmlspecialchars($student_info['last_name'] ?? 'N/A'); ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">
                    <i class="fas fa-venus-mars icon-style"></i>
                    <strong>Gender:</strong>
                </span>
                <span class="info-value">
                    <?php echo htmlspecialchars($student_info['gender_name'] ?? 'N/A'); ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">
                    <i class="fas fa-id-card icon-style"></i>
                    <strong>Student Number:</strong>
                </span>
                <span class="info-value">
                    <?php echo htmlspecialchars($student_info['student_number'] ?? 'N/A'); ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">
                    <i class="fas fa-envelope icon-style"></i>
                    <strong>Email:</strong>
                </span>
                <span class="info-value">
                    <?php echo htmlspecialchars($student_info['email'] ?? 'N/A'); ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">
                    <i class="fas fa-graduation-cap icon-style"></i>
                    <strong>Course:</strong>
                </span>
                <span class="info-value">
                    <?php echo htmlspecialchars($student_info['course_name'] ?? 'N/A'); ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">
                    <i class="fas fa-layer-group icon-style"></i>
                    <strong>Year Level:</strong>
                </span>
                <span class="info-value">
                    <?php echo htmlspecialchars($student_info['year_level'] ?? 'N/A'); ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">
                    <i class="fas fa-tag icon-style"></i>
                    <strong>Section:</strong>
                </span>
                <span class="info-value">
                    <?php echo htmlspecialchars($student_info['section_name'] ?? 'N/A'); ?>
                </span>
            </div>
        </div>
        <?php elseif (!$page_error): ?>
            <p class="error-message">Could not retrieve student information. Please ensure you are logged in correctly or contact support.</p>
        <?php endif; ?>
        <a href="../PHP/logout.php?role=student" id="signOutBtn" class="sign-out-button">Sign Out</a>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const notificationLinkToggle = document.getElementById('notificationLinkToggle');
    const notificationsDropdownContent = document.getElementById('notificationsDropdownContent');

    if (notificationLinkToggle && notificationsDropdownContent) {
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
</script>
<?php
    if (isset($conn)) {
        $conn->close();
    }
?>
</body>
</html>