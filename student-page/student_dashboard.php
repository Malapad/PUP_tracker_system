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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="./student_dashboard_style.css">
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
            <a href="notification.html" class="notification"><img src="https://img.icons8.com/?size=100&id=83193&format=png&color=000000" alt="Notifications"/></a>
            <a href="student_account.php" class="admin"><img src="https://img.icons8.com/?size=100&id=77883&format=png&color=000000" alt="Account"/></a>
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
            <div class="content handbook-content">
                <form id="handbookSearchForm">
                    <div class="search-wrapper">
                        <input type="text" id="handbookSearchInput" placeholder="Search violation type..." />
                        <button type="submit">Search</button>
                    </div>
                </form>
                <ul id="handbookViolationList">
                    <?php if (!empty($handbook_violations)): ?>
                        <?php foreach ($handbook_violations as $hb_v): ?>
                            <li class='handbook-item'>
                                <a href='#' class='handbook-link' data-id='<?php echo htmlspecialchars($hb_v['violation_type_id']); ?>'>
                                    <?php echo htmlspecialchars($hb_v['violation_type']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="no-handbook-items-message">No violation types found in the handbook.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</main>
</body>
</html>