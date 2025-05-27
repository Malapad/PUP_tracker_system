<?php
require_once '../PHP/dbcon.php';
session_start();

if (!isset($_SESSION["current_user_id"]) || !isset($_SESSION["user_student_number"])) {
    header("Location: ./student_login.php");
    exit();
}

$student_stud_number_from_session = $_SESSION["user_student_number"];
$page_error_message = null;

if (!function_exists('get_notification_icon_class')) {
    function get_notification_icon_class($type) {
        switch (strtolower($type ?? 'general')) {
            case 'violation': return 'fas fa-exclamation-triangle';
            case 'announcement': return 'fas fa-bullhorn';
            case 'reminder': return 'fas fa-bell';
            default: return 'fas fa-info-circle';
        }
    }
}

$header_unread_notifications = [];
$header_unread_count = 0;
if (isset($conn) && $conn instanceof mysqli) {
    $sql_header_list = "SELECT notification_id, message, created_at, link
                        FROM notifications_tbl
                        WHERE student_number = ? AND is_read = FALSE
                        ORDER BY created_at DESC LIMIT 5";
    if ($stmt_header_list = $conn->prepare($sql_header_list)) {
        $stmt_header_list->bind_param("s", $student_stud_number_from_session);
        $stmt_header_list->execute();
        $result_header_list = $stmt_header_list->get_result();
        while ($row_h_notif = $result_header_list->fetch_assoc()) {
            $header_unread_notifications[] = $row_h_notif;
        }
        $stmt_header_list->close();
    } else { error_log("DB Error (all_notification.php - header list): " . $conn->error); }

    $sql_header_count = "SELECT COUNT(*) as total_unread FROM notifications_tbl WHERE student_number = ? AND is_read = FALSE";
    if ($stmt_header_count = $conn->prepare($sql_header_count)) {
        $stmt_header_count->bind_param("s", $student_stud_number_from_session);
        $stmt_header_count->execute();
        $result_h_count = $stmt_header_count->get_result()->fetch_assoc();
        $header_unread_count = $result_h_count['total_unread'] ?? 0;
        $stmt_header_count->close();
    } else { error_log("DB Error (all_notification.php - header count): " . $conn->error); }
} else { $page_error_message = "Database connection error. Please try again later."; }

$all_notifications_list = [];
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$notifications_per_page = 10;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $notifications_per_page;
$total_notifications = 0;

if (isset($conn) && $conn instanceof mysqli) {
    $sql_count_base = "SELECT COUNT(*) FROM notifications_tbl WHERE student_number = ?";
    $params_count = [$student_stud_number_from_session];
    $types_count = "s";
    if (!empty($search_term)) {
        $sql_count_base .= " AND message LIKE ?";
        $search_like = "%" . $search_term . "%";
        $params_count[] = $search_like;
        $types_count .= "s";
    }
    if ($stmt_total_count = $conn->prepare($sql_count_base)) {
        $stmt_total_count->bind_param($types_count, ...$params_count);
        $stmt_total_count->execute();
        $stmt_total_count->bind_result($total_notifications);
        $stmt_total_count->fetch();
        $stmt_total_count->close();
    } else {
        error_log("DB Error (all_notification.php - total count): " . $conn->error);
        if(!$page_error_message) $page_error_message = "Could not retrieve notification count.";
    }
    $total_pages = ($total_notifications > 0) ? ceil($total_notifications / $notifications_per_page) : 0;

    if ($total_notifications > 0 || ($total_notifications === 0 && empty($search_term))) {
        $sql_page_notifications_base = "SELECT notification_id, message, created_at, link, is_read, notification_type
                                        FROM notifications_tbl WHERE student_number = ?";
        $params_page = [$student_stud_number_from_session];
        $types_page = "s";
        if (!empty($search_term)) {
            $sql_page_notifications_base .= " AND message LIKE ?";
            $params_page[] = $search_like;
            $types_page .= "s";
        }
        $sql_page_notifications_base .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params_page[] = $notifications_per_page;
        $params_page[] = $offset;
        $types_page .= "ii";
        if ($stmt_page_notifications = $conn->prepare($sql_page_notifications_base)) {
            $stmt_page_notifications->bind_param($types_page, ...$params_page);
            $stmt_page_notifications->execute();
            $result_page_notifications = $stmt_page_notifications->get_result();
            while ($row_page_n = $result_page_notifications->fetch_assoc()) {
                $all_notifications_list[] = $row_page_n;
            }
            $stmt_page_notifications->close();
        } else {
            error_log("DB Error (all_notification.php - page list): " . $conn->error);
            if(!$page_error_message) $page_error_message = "Could not retrieve notification history.";
        }
    }
}

$grouped_notifications = [];
if (!empty($all_notifications_list)) {
    foreach ($all_notifications_list as $notification) {
        $date = new DateTime($notification['created_at']);
        $today = new DateTime('today');
        $yesterday = (new DateTime('today'))->modify('-1 day');
        $date_key = '';
        if ($date->format('Y-m-d') === $today->format('Y-m-d')) {
            $date_key = 'Today';
        } elseif ($date->format('Y-m-d') === $yesterday->format('Y-m-d')) {
            $date_key = 'Yesterday';
        } else {
            $date_key = $date->format('F j, Y');
        }
        $grouped_notifications[$date_key][] = $notification;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Notifications</title>
    <link rel="stylesheet" href="./student_dashboard_style.css">
    <link rel="stylesheet" href="./all_notifications_page_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <header>
        <div class="logo">
            <img src="../assets/PUPlogo.png" alt="PUP Logo">
        </div>
        <nav>
            <a href="./student_dashboard.php">Home</a>
            <a href="./student_record.php">Record</a>
        </nav>
        <div class="admin-icons">
            <div class="notification-icon-area">
                <a href="#" class="notification" id="notificationLinkToggle">
                    <img src="https://img.icons8.com/?size=100&id=83193&format=png&color=000000" alt="Notifications"/>
                    <?php if ($header_unread_count > 0): ?>
                        <span class="notification-count"><?php echo $header_unread_count; ?></span>
                    <?php endif; ?>
                </a>
                <div class="notifications-dropdown" id="notificationsDropdownContent">
                    <ul>
                        <?php if (!empty($header_unread_notifications)): ?>
                            <?php foreach ($header_unread_notifications as $notification_h): ?>
                                <li class="notification-item">
                                    <a href="<?php echo !empty($notification_h['link']) ? htmlspecialchars($notification_h['link']) . (strpos($notification_h['link'], '?') === false ? '?' : '&') . 'notif_id=' . $notification_h['notification_id'] : '#'; ?>"
                                       class="notification-message-link"
                                       data-notification-id="<?php echo $notification_h['notification_id']; ?>">
                                        <?php echo htmlspecialchars(strip_tags($notification_h['message'])); ?>
                                        <small class="notification-timestamp"><?php echo date("M d, h:i A", strtotime($notification_h['created_at'])); ?></small>
                                    </a>
                                    <a href="./mark_notification_read.php?id=<?php echo $notification_h['notification_id']; ?>&page=all_notifications" class="mark-as-read-link">Mark read</a>
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

    <main class="all-notifications-main">
        <div class="notifications-container">
            <div class="notifications-header-area">
                <h1 class="notifications-title">All Notifications</h1>
                <?php if ($total_notifications > 0 && empty($search_term) && $current_page === 1 ): ?>
                <button id="markAllReadBtn" class="mark-all-read-btn">Mark All As Read</button>
                <?php endif; ?>
            </div>

            <form method="GET" action="all_notifications.php" class="search-notifications-form">
                <input type="text" name="search" placeholder="Search notifications..." value="<?php echo htmlspecialchars($search_term); ?>" aria-label="Search notifications">
                <button type="submit">Search</button>
                <?php if (!empty($search_term)): ?>
                    <a href="all_notifications.php" class="clear-search-btn">Clear</a>
                <?php endif; ?>
            </form>

            <?php if (!empty($page_error_message)): ?>
                <p class="error-message-container"><?php echo htmlspecialchars($page_error_message); ?></p>
            <?php endif; ?>

            <?php if (!empty($grouped_notifications)): ?>
                <?php foreach ($grouped_notifications as $date_group => $notifications_in_group): ?>
                    <h2 class="date-group-header"><?php echo htmlspecialchars($date_group); ?></h2>
                    <ul class="notification-list">
                        <?php foreach ($notifications_in_group as $notification_item): ?>
                            <li class="notification-item status-<?php echo $notification_item['is_read'] ? 'read' : 'unread'; ?>">
                                <div class="notification-content">
                                    <div class="notification-message">
                                        <?php
                                        $message_text = htmlspecialchars(strip_tags($notification_item['message']));
                                        $link_url = !empty($notification_item['link']) ? htmlspecialchars($notification_item['link']) . (strpos($notification_item['link'], '?') === false ? '?' : '&') . 'notif_id=' . $notification_item['notification_id'] : null;
                                        ?>
                                        <?php if ($link_url): ?>
                                            <a href="<?php echo $link_url; ?>"><?php echo $message_text; ?></a>
                                        <?php else: ?>
                                            <?php echo $message_text; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="notification-timestamp">
                                        <?php echo date("g:i a", strtotime($notification_item['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="notification-actions">
                                    <?php if (!$notification_item['is_read']): ?>
                                        <a href="./mark_notification_read.php?id=<?php echo $notification_item['notification_id']; ?>&redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="mark-action-link">
                                            Mark as read
                                        </a>
                                    <?php else: ?>
                                        <span class="status-indicator-read">Read</span>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endforeach; ?>
            <?php elseif (empty($page_error_message)): ?>
                    <div class="no-notifications-message">
                    <i class="fas fa-bell-slash"></i> <p>You have no notifications<?php echo !empty($search_term) ? ' matching your search "' . htmlspecialchars($search_term) . '"' : ' in your history'; ?>.</p>
                </div>
            <?php endif; ?>

            <?php if ($total_pages > 1): ?>
                <ul class="pagination">
                        <?php if ($current_page > 1): ?>
                        <li><a href="?page=<?php echo $current_page - 1; echo !empty($search_term) ? '&search='.urlencode($search_term) : ''; ?>">Prev</a></li>
                    <?php else: ?>
                        <li class="disabled"><span>Prev</span></li>
                    <?php endif; ?>
                    <?php
                    $max_pages_to_show = 5;
                    $start_page = max(1, $current_page - floor($max_pages_to_show / 2));
                    $end_page = min($total_pages, $start_page + $max_pages_to_show - 1);
                    if ($end_page - $start_page + 1 < $max_pages_to_show) {
                        $start_page = max(1, $end_page - $max_pages_to_show + 1);
                    }
                    ?>
                    <?php if ($start_page > 1): ?>
                        <li><a href="?page=1<?php echo !empty($search_term) ? '&search='.urlencode($search_term) : ''; ?>">1</a></li>
                        <?php if ($start_page > 2): ?>
                            <li class="disabled"><span>...</span></li>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <?php if ($i == $current_page): ?>
                            <li class="active"><span><?php echo $i; ?></span></li>
                        <?php else: ?>
                            <li><a href="?page=<?php echo $i; echo !empty($search_term) ? '&search='.urlencode($search_term) : ''; ?>"><?php echo $i; ?></a></li>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <li class="disabled"><span>...</span></li>
                        <?php endif; ?>
                        <li><a href="?page=<?php echo $total_pages; echo !empty($search_term) ? '&search='.urlencode($search_term) : ''; ?>"><?php echo $total_pages; ?></a></li>
                    <?php endif; ?>
                    <?php if ($current_page < $total_pages): ?>
                        <li><a href="?page=<?php echo $current_page + 1; echo !empty($search_term) ? '&search='.urlencode($search_term) : ''; ?>">Next</a></li>
                    <?php else: ?>
                        <li class="disabled"><span>Next</span></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </div>
    </main>
    <script src="./all_notifications.js" defer></script>
</body>
</html>
<?php
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>