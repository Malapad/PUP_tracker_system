<?php
include '../PHP/dbcon.php'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$admin_session_id = null;
if (isset($_SESSION['admin_user_id'])) {
    $admin_session_id = $_SESSION['admin_user_id'];
} elseif (isset($_SESSION['admin_id'])) {
    $admin_session_id = $_SESSION['admin_id'];
}

if ($admin_session_id === null) {
    header("Location: ../admin-login/admin_login.php"); 
    exit();
}

$all_admin_notifications = [];
if (isset($conn)) {
    $sql_all_notifs = "SELECT * FROM admin_notifications_tbl ORDER BY created_at DESC";
    $result_all_notifs = $conn->query($sql_all_notifs);
    while($row = $result_all_notifs->fetch_assoc()) {
        $all_admin_notifications[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Notifications</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./admin_sanction.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .notification-page-list {
            margin-top: 1.5rem;
        }

        .notification-page-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background-color: var(--card-bg-color);
            border: 1px solid var(--border-color);
            border-left: 4px solid var(--border-color);
            border-radius: var(--border-radius);
            margin-bottom: 0.75rem;
            text-decoration: none;
            color: var(--text-color);
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .notification-page-item:hover {
            box-shadow: var(--card-shadow);
            border-left-color: var(--blue);
        }

        .notification-page-item.unread {
            background-color: #f1f8ff;
            border-left-color: var(--blue);
        }

        .notification-page-item p {
            margin: 0 0 0.25rem 0;
            font-weight: 500;
        }

        .notification-page-item small {
            color: var(--text-muted);
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content-wrapper">
            <div class="logo">
                <a href="../HTML/admin_homepage.html">
                    <img src="../IMAGE/Tracker-logo.png" alt="PUP Logo">
                </a>
            </div>
            <nav>
                <a href="../admin-dashboard/admin_homepage.php">Home</a>
                <a href="../updated-admin-violation/admin_violation_page.php">Violations</a>
                <a href="./admin_sanction.php">Student Sanction</a>
                <a href="../user-management/user_management.php">User Management</a>
                <a href="../PHP/admin_announcements.php">Announcements</a>
            </nav>
            <div class="admin-icons">
                 <a href="#" class="notification">
                    <i class="fas fa-bell"></i>
                </a>
                <a href="admin_account.html" class="admin">
                    <i class="fas fa-user-circle"></i>
                </a>
            </div>
        </div>
    </header>

    <main class="container">
        <div class="page-header">
            <h1>All Notifications</h1>
        </div>
        <div class="notification-page-list">
            <?php if (!empty($all_admin_notifications)): ?>
                <?php foreach ($all_admin_notifications as $notification): ?>
                    <a href="<?php echo htmlspecialchars($notification['link']); ?>" class="notification-page-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                        <div class="notification-content">
                            <p><?php echo htmlspecialchars($notification['message']); ?></p>
                            <small><?php echo date("F j, Y, h:i a", strtotime($notification['created_at'])); ?></small>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-records-cell">
                    <p>No notifications found.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
</body>
</html>
<?php if (isset($conn)) { $conn->close(); } ?>