<?php
require_once '../PHP/dbcon.php';
session_start();

if (!isset($_SESSION["current_user_id"]) || !isset($_SESSION["user_student_number"])) {
    header("Location: ./student_login.php");
    exit();
}

$student_stud_number_from_session = $_SESSION["user_student_number"];

$violations = [];
if (!empty($student_stud_number_from_session)) {
    $sql_violations = "SELECT v.description, v.violation_date, vt.violation_type
                       FROM violation_tbl v
                       LEFT JOIN violation_type_tbl vt ON v.violation_type = vt.violation_type_id
                       WHERE v.student_number = ?
                       ORDER BY v.violation_date DESC";
    if ($stmt_violations = $conn->prepare($sql_violations)) {
        $stmt_violations->bind_param("s", $student_stud_number_from_session);
        $stmt_violations->execute();
        $result_violations = $stmt_violations->get_result();
        while ($row = $result_violations->fetch_assoc()) {
            $violations[] = $row;
        }
        $stmt_violations->close();
    } else {
        echo "Error preparing violation query: " . htmlspecialchars($conn->error);
    }
}

$total_violations = count($violations);
$offense_status = "Clear";
$status_color = "green";

if ($total_violations > 0) {
    $violation_type_counts = [];
    foreach ($violations as $v) {
        $typeName = $v['violation_type'];
        if (!isset($violation_type_counts[$typeName])) {
            $violation_type_counts[$typeName] = 0;
        }
        $violation_type_counts[$typeName]++;
    }

    $offense_status = "Warning";
    $status_color = "orange";
    foreach ($violation_type_counts as $type => $count) {
        if ($count >= 2) {
            $offense_status = "Sanction";
            $status_color = "#AF1414";
            break;
        }
    }
}

$handbook_violations = [];
$sql_handbook = "SELECT violation_type_id, violation_type FROM violation_type_tbl ORDER BY violation_type";
if ($stmt_handbook = $conn->prepare($sql_handbook)) {
    $stmt_handbook->execute();
    $result_handbook = $stmt_handbook->get_result();
    while ($row_hb = $result_handbook->fetch_assoc()) {
        $handbook_violations[] = $row_hb;
    }
    $stmt_handbook->close();
} else {
    echo "Error preparing handbook query: " . htmlspecialchars($conn->error);
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="./student_dashboard_style.css">
    <link rel="stylesheet" href="violation-config.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="logo">
            <img src="../assets/PUPLogo.png" alt="PUP Logo">
        </div>
        <nav>
            <a href="./student_dashboard.php" class="active-nav">Home</a>
            <a href="./student_record.php">Record</a>
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
    <h3 class="dashboard-page-title">Student Dashboard</h3>

    <div class="container">
        <div class="box">
            <div class="header">Your Violation Summary</div>
            <div class="content violation-summary-content">
                <?php if ($total_violations > 0): ?>
                    <p><span class="summary-label">Total Violations Committed:</span> <span class="summary-value"><?php echo $total_violations; ?></span></p>
                    <p>
                        <span class="summary-label">Offense Status:</span>
                        <span class="summary-value offense-status-text" style="color: <?php echo $status_color; ?>;">
                            <?php echo htmlspecialchars($offense_status); ?>
                        </span>
                    </p>
                    <?php if ($offense_status === 'Warning'): ?>
                        <p class="summary-message">Please review your '<a href="./student_record.php"><strong>Record</strong></a>' for details on your recent violations.</p>
                    <?php elseif ($offense_status === 'Sanction'): ?>
                        <p class="summary-message">A '<strong>Sanction</strong>' status indicates repeated violations. See your '<a href="./student_record.php"><strong>Record</strong></a>' for more information.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p><span class="summary-label">Total Violations Committed:</span> <span class="summary-value">0</span></p>
                    <p>
                        <span class="summary-label">Offense Status:</span>
                        <span class="summary-value offense-status-text" style="color: <?php echo $status_color; ?>;">
                            <?php echo htmlspecialchars($offense_status); ?>
                        </span>
                    </p>
                    <p class="no-violations-message">Keep up the good work! No violations recorded.</p>
                <?php endif; ?>
            </div>
        </div>



        <div class="box">
            <div class="header">Student Handbook - Violation Types</div>
            <?php
            $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
            ?>

            <div class="search-bar">
              <form method="GET" action="">
                <input type="text" name="search" placeholder="Search category or type..." value="<?= htmlspecialchars($searchTerm) ?>">
                <button type="submit"><i class="fas fa-search"></i> Search</button>
              </form>
            </div>

            <div class="accordion-container">
            <?php
            $searchSql = "
              SELECT DISTINCT c.violation_category_id, c.category_name
              FROM violation_category_tbl c
              LEFT JOIN violation_type_tbl t ON c.violation_category_id = t.violation_category_id
              WHERE c.category_name LIKE ? OR t.violation_type LIKE ?
              ORDER BY c.category_name ASC
            ";

            $stmt = $conn->prepare($searchSql);
            $like = '%' . $searchTerm . '%';
            $stmt->bind_param("ss", $like, $like);
            $stmt->execute();
            $categoryResult = $stmt->get_result();

            if ($categoryResult && $categoryResult->num_rows > 0) {
              while ($category = $categoryResult->fetch_assoc()) {
                $categoryId = $category['violation_category_id'];
                $categoryName = $category['category_name'];
                ?>
                <div class="accordion-item">
                  <button class="accordion-header">
                    <span><?= htmlspecialchars($categoryName) ?></span>
                    <i class="fas fa-chevron-down accordion-icon"></i>
                  </button>
                  <div class="accordion-content">
                    <ul>
                      <?php
                      $typeQuery = "SELECT violation_type FROM violation_type_tbl WHERE violation_category_id = ? AND violation_type LIKE ?";
                      $stmtType = $conn->prepare($typeQuery);
                      $stmtType->bind_param("is", $categoryId, $like);
                      $stmtType->execute();
                      $typeResult = $stmtType->get_result();

                      if ($typeResult && $typeResult->num_rows > 0) {
                        while ($type = $typeResult->fetch_assoc()) {
                          echo "<li>" . htmlspecialchars($type['violation_type']) . "</li>";
                        }
                      } else {
                        echo "<li>No matching types found in this category.</li>";
                      }
                      ?>
                    </ul>
                  </div>
                </div>
                <?php
              }
            } else {
              echo "<p style='padding: 10px;'>No Record</p>";
            }
            ?>
            </div>
        </div>
    </div>
</main>
<script src="violation-config.js"></script>
<script src="./student_dashboard.js"></script>
</body>
</html>