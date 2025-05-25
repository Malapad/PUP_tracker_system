<?php
include '../PHP/dbcon.php';

$student_number_display = '';
$student_details = null;
$violations = [];
$violation_counts_by_type = [];

$back_link_target = 'admin_violation.php';

if (isset($_GET['student_number'])) {
    $student_number_from_get = trim($_GET['student_number']);
    $student_number_display = htmlspecialchars($student_number_from_get);

    if (!empty($student_number_from_get)) {
        $stmt_student = $conn->prepare("SELECT u.student_number, u.first_name, u.middle_name, u.last_name,
                                             c.course_name, y.year, s.section_name
                                        FROM users_tbl u
                                        LEFT JOIN course_tbl c ON u.course_id = c.course_id
                                        LEFT JOIN year_tbl y ON u.year_id = y.year_id
                                        LEFT JOIN section_tbl s ON u.section_id = s.section_id
                                        WHERE u.student_number = ?");
        if ($stmt_student) {
            $stmt_student->bind_param("s", $student_number_from_get);
            $stmt_student->execute();
            $result_student = $stmt_student->get_result();
            if ($result_student->num_rows > 0) {
                $student_details = $result_student->fetch_assoc();
            }
            $stmt_student->close();
        } else {
            error_log("Prepare failed for student details: " . $conn->error);
        }

        $sql_violations = "SELECT v.violation_id, vt.violation_type, v.violation_date
                           FROM violation_tbl v
                           JOIN violation_type_tbl vt ON v.violation_type = vt.violation_type_id
                           WHERE v.student_number = ?
                           ORDER BY v.violation_date DESC";
        $stmt_violations = $conn->prepare($sql_violations);

        if ($stmt_violations) {
            $stmt_violations->bind_param("s", $student_number_from_get);
            $stmt_violations->execute();
            $result_violations = $stmt_violations->get_result();
            while ($row = $result_violations->fetch_assoc()) {
                $violations[] = $row;
                $typeName = $row['violation_type'];
                if (!isset($violation_counts_by_type[$typeName])) {
                    $violation_counts_by_type[$typeName] = 0;
                }
                $violation_counts_by_type[$typeName]++;
            }
            $stmt_violations->close();
            ksort($violation_counts_by_type);
        } else {
            error_log("Prepare failed for violations list: " . $conn->error);
        }
    }
}

$totalViolations = count($violations);
$offenseStatusText = 'N/A';
$offenseStatusClass = 'offense-status-text offense-na';

if ($totalViolations > 0) {
    $hasSanction_details = false;
    if (!empty($violation_counts_by_type)) {
        foreach ($violation_counts_by_type as $type => $count) {
            if ($count >= 2) {
                $hasSanction_details = true;
                break;
            }
        }
    }
    if ($hasSanction_details) {
        $offenseStatusText = 'Sanction';
        $offenseStatusClass = 'offense-status-text offense-sanction';
    } else {
        $offenseStatusText = 'Warning';
        $offenseStatusClass = 'offense-status-text offense-warning';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Student Violation Details</title>
    <link rel="stylesheet" href="./admin_violation_style.css" /> 
    <link rel="stylesheet" href="./student_violation_details_style.css" /> 
    <style>
        /* All page-specific styles are now intended to be in student_violation_details_style.css */
    </style>
</head>
<body>

<header>
    <div class="logo">
        <img src="../assets/PUPlogo.png" alt="PUP Logo" />
    </div>
    <nav>
        <a href="../HTML/admin_homepage.html">Home</a>
        <a href="<?php echo htmlspecialchars($back_link_target); ?>" class="active">Violations</a>
        <a href="../HTML/admin_sanction.html">Student Sanction</a>
        <a href="../user-management/user_management.php">User Management</a>
    </nav>
    <div class="admin-icons">
        <a href="../HTML/notification.html" class="notification">
            <img src="https://img.icons8.com/?size=100&id=83193&format=png&color=000000" alt="Notifications"/>
        </a>
        <a href="../HTML/admin_account.html" class="admin">
            <img src="https://img.icons8.com/?size=100&id=77883&format=png&color=000000" alt="Admin Account"/>
        </a>
    </div>
</header>

<div class="details-container"> 
    <div class="page-title-container"> 
        <h2>Violation Details</h2>
    </div>

    <div class="top-back-button-container">
        <a href="<?php echo htmlspecialchars($back_link_target); ?>" class="back-button">Back to Violation List</a>
    </div>

    <?php if ($student_details): ?>
        <div class="student-info-block"> 
            <h3 class="student-name"><?php echo htmlspecialchars($student_details['first_name'] . ' ' . ($student_details['middle_name'] ? $student_details['middle_name'] . ' ' : '') . $student_details['last_name']); ?></h3>
            <p><strong>Student Number:</strong> <?php echo htmlspecialchars($student_details['student_number']); ?></p>
            <p>
                <strong>Course:</strong> <?php echo htmlspecialchars($student_details['course_name'] ?? 'N/A'); ?> |
                <strong>Year:</strong> <?php echo htmlspecialchars($student_details['year'] ?? 'N/A'); ?> |
                <strong>Section:</strong> <?php echo htmlspecialchars($student_details['section_name'] ?? 'N/A'); ?>
            </p>
        </div>

        <hr class="details-divider"> 

        <div class="overall-status-block"> 
            <p><strong>Total Violations Committed:</strong> <?php echo $totalViolations; ?></p>
            <p><strong>Offense Status:</strong> <span class="<?php echo $offenseStatusClass; ?>"><?php echo htmlspecialchars($offenseStatusText); ?></span></p>
        </div>

        <?php if (!empty($violation_counts_by_type)): ?>
            <div class="violation-summary-by-type"> 
                <h3 class="summary-title">Summary by Violation Type:</h3>
                <ul class="violation-counts-list">
                    <?php foreach ($violation_counts_by_type as $type => $count): ?>
                        <li>
                            <span class="violation-type-name"><?php echo htmlspecialchars($type); ?></span>
                            <span class="violation-type-count"><?php echo $count; ?> committed</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php elseif ($totalViolations > 0): ?>
            <div class="violation-summary-by-type">
                 <p>Could not generate violation summary by type.</p>
            </div>
        <?php endif; ?>

        <h3 class="log-title">Individual Violations Log:</h3> 
        <?php if (!empty($violations)): ?>
            <div class="table-scroll-container"> 
                <table class="violations-table"> 
                    <thead>
                        <tr>
                            <th>Violation Type</th>
                            <th>Date of Violation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($violations as $violation): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($violation['violation_type']); ?></td>
                                <td><?php echo htmlspecialchars(date("F j, Y, g:i a", strtotime($violation['violation_date']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="no-records">No individual violations recorded for this student.</p> 
        <?php endif; ?>

        <?php elseif (!empty($student_number_display)): ?>
        <p class="no-records">Student with number '<?php echo $student_number_display; ?>' not found or has no details.</p>
        <?php else: ?>
        <p class="no-records">No student number provided or invalid request.</p>
        <?php endif; ?>
</div>

</body>
</html>
<?php $conn->close(); ?>