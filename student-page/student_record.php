<?php
require_once "../PHP/dbcon.php";
session_start();

if (!isset($_SESSION["current_user_id"])) {
    header("Location: student_login.php");
    exit();
}

$session_user_id = $_SESSION["current_user_id"];
$student_details = null;
$violation_records = [];
$student_stud_number_for_violations = '';
$year_display = "N/A";
$type_commit_counts = [];
$total_commits_for_summary = 0;

$sql_student_info = "SELECT u.first_name, u.middle_name, u.last_name, u.student_number, u.year_id,
                            c.course_name, s.section_name
                        FROM users_tbl u
                        LEFT JOIN course_tbl c ON u.course_id = c.course_id
                        LEFT JOIN section_tbl s ON u.section_id = s.section_id
                        WHERE u.user_id = ?";

if ($stmt_info = $conn->prepare($sql_student_info)) {
    $stmt_info->bind_param("i", $session_user_id);
    $stmt_info->execute();
    $result_info = $stmt_info->get_result();
    if ($result_info->num_rows == 1) {
        $student_details = $result_info->fetch_assoc();
        $student_stud_number_for_violations = $student_details['student_number'];
        
        if (isset($student_details['year_id']) && !empty($student_details['year_id'])) {
            $year_display = $student_details['year_id']; 
        }
        $student_details['FirstNameDisplay'] = htmlspecialchars(isset($student_details['first_name']) ? $student_details['first_name'] : '');
        $student_details['MiddleNameDisplay'] = htmlspecialchars(isset($student_details['middle_name']) ? $student_details['middle_name'] : '');
        $student_details['LastNameDisplay'] = htmlspecialchars(isset($student_details['last_name']) ? $student_details['last_name'] : '');
        $student_details['StudNumberDisplay'] = htmlspecialchars(isset($student_details['student_number']) ? $student_details['student_number'] : '');
        $student_details['CourseNameDisplay'] = htmlspecialchars(isset($student_details['course_name']) ? $student_details['course_name'] : 'N/A');
        $student_details['YearDisplay'] = htmlspecialchars($year_display);
        $student_details['SectionNameDisplay'] = htmlspecialchars(isset($student_details['section_name']) ? $student_details['section_name'] : 'N/A');
    }
    $stmt_info->close();
} else {
    die("Error preparing student details query: " . $conn->error);
}

if (!empty($student_stud_number_for_violations)) {
    $sql_violations = "SELECT
                            v.description AS ViolationDescription,
                            v.violation_date AS ViolationDate,
                            vt.violation_type AS ViolationName
                        FROM violation_tbl v
                        LEFT JOIN violation_type_tbl vt ON v.violation_type = vt.violation_type_id
                        WHERE v.student_number = ?
                        ORDER BY v.violation_date DESC";

    if ($stmt_violations = $conn->prepare($sql_violations)) {
        $stmt_violations->bind_param("s", $student_stud_number_for_violations);
        $stmt_violations->execute();
        $result_violations = $stmt_violations->get_result();
        while ($row = $result_violations->fetch_assoc()) {
            $violation_records[] = $row;
            $typeNamePart1 = isset($row['ViolationName']) ? $row['ViolationName'] : null;
            $typeNamePart2 = isset($row['ViolationDescription']) ? $row['ViolationDescription'] : 'Unknown Type';
            $typeName = ($typeNamePart1 !== null) ? $typeNamePart1 : $typeNamePart2;

            if (!isset($type_commit_counts[$typeName])) {
                $type_commit_counts[$typeName] = 0;
            }
            $type_commit_counts[$typeName]++;
        }
        $stmt_violations->close();
    } else {
        echo "Error preparing violation records query: " . $conn->error;
    }
}

$individual_violation_instance_count = count($violation_records);
$total_commits_for_summary = array_sum($type_commit_counts);

$button_disabled = true;
if ($individual_violation_instance_count > 0) {
    $button_disabled = false;
}

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
</head>
<body>
    <header>
        <div class="logo"><img src="../assets/PUPlogo.png" alt="PUP Logo"></div>
        <nav>
            <a href="student_dashboard.php">Home</a>
            <a href="student_record.php" class="active-nav">Record</a>
        </nav>
        <div class="user-icons">
            <a href="notification.html" class="notification-icon"><img src="https://img.icons8.com/?size=100&id=83193&format=png&color=000000" alt="Notifications"/></a>
            <a href="student_account.php" class="profile-icon"><img src="https://img.icons8.com/?size=100&id=77883&format=png&color=000000" alt="Account"/></a>
        </div>
    </header>

<main>
    <div class="page-main-title-container">
        <h1>Violation Record</h1>
    </div>
    <div class="content-wrapper-wide">
        <?php if ($student_details): ?>
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
                <p>Total Violations Committed (Summary): <strong><?php echo $total_commits_for_summary; ?></strong></p>
            </div>

            <h3 class="section-title-styled">Summary by Violation Type</h3>
            <div class="table-container-wide">
                <table class="data-table-wide" id="summaryViolationTable">
                    <thead>
                        <tr>
                            <th>Violation Type</th>
                            <th>Number of commits</th>
                            <th>Offense</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($type_commit_counts)): ?>
                            <?php foreach ($type_commit_counts as $violationName => $commitCount): ?>
                                <?php
                                    $offense_status_for_type = "Warning"; 
                                    $offense_tag_class = "offense-tag-look-warning";
                                    if ($commitCount >= 2) {
                                        $offense_status_for_type = "Sanction";
                                        $offense_tag_class = "offense-tag-look-sanction";
                                    }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($violationName); ?></td>
                                    <td><?php echo $commitCount; ?></td>
                                    <td><span class="offense-tag-look <?php echo $offense_tag_class; ?>"><?php echo $offense_status_for_type; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="no-records-message">No violation records found in summary.</td>
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
                            <th>Violation Type</th>
                            <th>Date of Violation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($violation_records)): ?>
                            <?php foreach ($violation_records as $record): ?>
                                <?php
                                    $violation_name_display = isset($record['ViolationName']) ? $record['ViolationName'] : (isset($record['ViolationDescription']) ? $record['ViolationDescription'] : 'Unknown Type');
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($violation_name_display); ?></td>
                                    <td><?php echo htmlspecialchars(date("M d, Y, g:i a", strtotime($record['ViolationDate']))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="no-records-message">No individual violation records found.</td>
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
                    <p class="instance-count-display-wide">Total Individual Violation Instances: <strong><?php echo $individual_violation_instance_count; ?></strong></p>
                    <button id="requestSanctionButtonWide" class="button-green-wide" <?php if ($button_disabled) echo 'disabled'; ?>>
                        Request Sanction
                    </button>
                </div>
            </div>

        <?php else: ?>
            <div class="info-block-wide">
                    <h2 class="student-name-prominent">Student Details Not Found</h2>
                    <p class="meta-text-wide">There might be an issue with your session or the user ID. Please try logging out and logging back in. If the problem persists, contact support.</p>
            </div>
        <?php endif; ?>
    </div>

    <div id="confirmationOverlayWide" class="overlay-container-wide">
        <div class="dialog-content-wide">
            <p>Successfully Requested</p>
            <button id="closeOverlayButtonWide" class="button-grey-wide">Close</button>
        </div>
    </div>
</main>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const requestButton = document.getElementById("requestSanctionButtonWide");
            const overlay = document.getElementById("confirmationOverlayWide");
            const closeButton = document.getElementById("closeOverlayButtonWide");

            if (requestButton) {
                requestButton.addEventListener("click", function() {
                    if (!this.disabled) {
                        if (overlay) overlay.style.display = "flex";
                    }
                });
            }
            
            if (closeButton) {
                closeButton.addEventListener("click", function() {
                    if (overlay) overlay.style.display = "none";
                });
            }

            if (overlay) {
                overlay.addEventListener("click", function(event) {
                    if (event.target === overlay) {
                        overlay.style.display = "none";
                    }
                });
            }
        });
    </script>
    <?php $conn->close(); ?>
</body>
</html>