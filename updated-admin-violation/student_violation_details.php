<?php
session_start();
include '../PHP/dbcon.php';

$student_number_display = '';
$student_details = null;
$violations = [];
$violation_summary_details = [];

$back_link_target = './admin_violation_page.php';
$current_page_filename = basename($_SERVER['PHP_SELF']);

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
        }

        $sql_violations = "SELECT v.violation_id, vt.violation_type_id, vc.category_name, vt.violation_type, v.violation_date, v.description AS remarks,
                                  CONCAT(ai.firstname, ' ', ai.lastname) AS admin_full_name,
                                  ai.position
                           FROM violation_tbl v
                           JOIN violation_type_tbl vt ON v.violation_type = vt.violation_type_id
                           LEFT JOIN violation_category_tbl vc ON vt.violation_category_id = vc.violation_category_id
                           LEFT JOIN admin_info_tbl ai ON v.recorder_id = ai.admin_id
                           WHERE v.student_number = ?
                           ORDER BY v.violation_date DESC";
        $stmt_violations = $conn->prepare($sql_violations);

        if ($stmt_violations) {
            $stmt_violations->bind_param("s", $student_number_from_get);
            $stmt_violations->execute();
            $result_violations = $stmt_violations->get_result();
            $temp_summary_data = [];

            while ($row = $result_violations->fetch_assoc()) {
                $violations[] = $row;
                $typeName = $row['violation_type'];
                $categoryName = $row['category_name'] ?? 'Uncategorized';
                $remark_from_db = trim($row['remarks'] ?? '');
                $key = $categoryName . "||" . $typeName;

                if (!isset($temp_summary_data[$key])) {
                    $temp_summary_data[$key] = [
                        'category' => $categoryName,
                        'type' => $typeName,
                        'violation_type_id' => $row['violation_type_id'],
                        'count' => 0,
                        'remark_display' => empty($remark_from_db) ? 'No remarks' : $remark_from_db
                    ];
                }
                $temp_summary_data[$key]['count']++;
                if ($temp_summary_data[$key]['count'] > 1) {
                    $temp_summary_data[$key]['remark_display'] = '(Multiple instances - see log)';
                }
            }
            $stmt_violations->close();

            $violation_summary_details = array_values($temp_summary_data);
            usort($violation_summary_details, function($a, $b) {
                $catComp = strcmp($a['category'], $b['category']);
                if ($catComp == 0) {
                    return strcmp($a['type'], $b['type']);
                }
                return $catComp;
            });
        }
    }
}

$totalViolations = count($violations);

function getCategoryClass($categoryName) {
    $class = strtolower($categoryName);
    $class = preg_replace('/[^a-z0-9]+/', '-', $class);
    return 'category-' . trim($class, '-');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Student Violation Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="./admin_violation.css" />
    <link rel="stylesheet" href="./student_violation_details_style.css" />
</head>
<body>

<header class="navbar navbar-expand-lg custom-navbar">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <img src="../IMAGE/Tracker_logo.png" alt="Logo" width="40" height="40" class="d-inline-block align-text-top">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link" href="../HTML/admin_homepage.html"><strong>Home</strong></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="<?php echo htmlspecialchars($back_link_target); ?>"><strong>Violations</strong></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../updated-admin-sanction/admin_sanction.php"><strong>Student Sanction</strong></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../user-management/user_management.php"><strong>User Management</strong></a>
                </li>
            </ul>
            <ul class="navbar-nav icon-nav">
                <li class="nav-item">
                    <a class="nav-link icon-link" href="#">
                        <img src="https://img.icons8.com/?size=100&id=83193&format=png&color=000000" alt="Notification">
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link icon-link" href="#">
                        <img src="https://img.icons8.com/?size=100&id=77883&format=png&color=000000" alt="Account">
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>

<div class="details-container">
    <div class="page-header">
        <h2 class="page-title">Violation Record</h2>
        <a href="<?php echo htmlspecialchars($back_link_target); ?>" class="back-button">Back to Violation List</a>
    </div>

    <?php if ($student_details): ?>
        <div class="student-info-block">
            <h3 class="student-name"><?php echo htmlspecialchars($student_details['first_name'] . ' ' . ($student_details['middle_name'] ? $student_details['middle_name'] . ' ' : '') . $student_details['last_name']); ?></h3>
            <p><strong>Student Number:</strong> <?php echo htmlspecialchars($student_details['student_number']); ?></p>
            <div class="details-pills">
                <span><strong>Course:</strong> <?php echo htmlspecialchars($student_details['course_name'] ?? 'N/A'); ?></span>
                <span><strong>Year:</strong> <?php echo htmlspecialchars($student_details['year'] ?? 'N/A'); ?></span>
                <span><strong>Section:</strong> <?php echo htmlspecialchars($student_details['section_name'] ?? 'N/A'); ?></span>
            </div>
        </div>

        <div class="stat-card">
            <p>Total Violations Committed: <strong><?php echo $totalViolations; ?></strong></p>
        </div>

        <div class="content-card">
            <h3 class="section-title"><i class="fas fa-list-ul"></i> Summary by Violation</h3>
            <?php if (!empty($violation_summary_details)): ?>
                <div class="table-responsive-wrapper">
                    <table class="details-table summary-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Violation Type</th>
                                <th>Offense Level</th>
                                <th>Status</th>
                                <th>Remarks (for single instance)</th>
                                <th>Disciplinary Sanction</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($violation_summary_details as $summary_item): ?>
                                <tr class="<?php echo getCategoryClass($summary_item['category']); ?>">
                                    <td data-label="Category"><?php echo htmlspecialchars($summary_item['category']); ?></td>
                                    <td data-label="Violation Type"><?php echo htmlspecialchars($summary_item['type']); ?></td>
                                    <td data-label="Offense Level" style="text-align: center;">
                                        <?php
                                        $offense_count = $summary_item['count'];
                                        $offense_level_display_str = ($offense_count == 1) ? '1st Offense' : (($offense_count == 2) ? '2nd Offense' : (($offense_count == 3) ? '3rd Offense' : $offense_count . 'th Offense'));
                                        echo "<span class='offense-status-text offense-level-badge'>" . htmlspecialchars($offense_level_display_str) . "</span>";
                                        ?>
                                    </td>
                                    <td data-label="Status" style="text-align: center;">
                                        <?php
                                        $status_text = 'Warning';
                                        $status_class = 'offense-warning';
                                        $disciplinary_sanction = '—';

                                        $sanction_lookup_sql = "SELECT disciplinary_sanction FROM disciplinary_sanctions WHERE violation_type_id = ? AND offense_level = ?";
                                        $stmt_sanc = $conn->prepare($sanction_lookup_sql);
                                        if ($stmt_sanc) {
                                            $stmt_sanc->bind_param("is", $summary_item['violation_type_id'], $offense_level_display_str);
                                            $stmt_sanc->execute();
                                            $sanc_res = $stmt_sanc->get_result();
                                            if ($sanc_row = $sanc_res->fetch_assoc()) {
                                                if (!empty($sanc_row['disciplinary_sanction'])) {
                                                    $disciplinary_sanction = $sanc_row['disciplinary_sanction'];
                                                    if (stripos($disciplinary_sanction, 'warning') === false) {
                                                        $status_text = 'Sanction';
                                                        $status_class = 'offense-sanction';
                                                    }
                                                }
                                            }
                                            $stmt_sanc->close();
                                        }
                                        echo "<span class='offense-status-text " . $status_class . "'>" . htmlspecialchars($status_text) . "</span>";
                                        ?>
                                    </td>
                                    <td data-label="Remarks"><?php echo nl2br(htmlspecialchars($summary_item['remark_display'])); ?></td>
                                    <td data-label="Sanction"><?php echo htmlspecialchars($disciplinary_sanction); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-records">No violation summaries available.</p>
            <?php endif; ?>
        </div>

        <div class="content-card">
            <h3 class="section-title"><i class="fas fa-history"></i> Individual Violations Log</h3>
            <?php if (!empty($violations)): ?>
                <div class="table-responsive-wrapper">
                    <table class="details-table table-log">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Violation Type</th>
                                <th>Date of Violation</th>
                                <th>Remarks</th>
                                <th>Recorded By</th>
                                <th>Position</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($violations as $violation): ?>
                                <tr class="<?php echo getCategoryClass($violation['category_name']); ?>">
                                    <td data-label="Category" class="<?php echo ($violation['category_name'] ?? 'N/A') == 'N/A' ? 'data-na' : ''; ?>"><?php echo htmlspecialchars($violation['category_name'] ?? 'N/A'); ?></td>
                                    <td data-label="Violation Type"><?php echo htmlspecialchars($violation['violation_type']); ?></td>
                                    <td data-label="Date"><?php echo htmlspecialchars(date("F j, Y, g:i a", strtotime($violation['violation_date']))); ?></td>
                                    <td data-label="Remarks"><?php echo nl2br(htmlspecialchars(trim($violation['remarks'] ?? '') === '' ? 'No remarks' : $violation['remarks'])); ?></td>
                                    <td data-label="Recorded By" class="<?php echo ($violation['admin_full_name'] ?? 'N/A') == 'N/A' ? 'data-na' : ''; ?>"><?php echo htmlspecialchars($violation['admin_full_name'] ?? 'N/A'); ?></td>
                                    <td data-label="Position" class="<?php echo ($violation['position'] ?? 'N/A') == 'N/A' ? 'data-na' : ''; ?>"><?php echo htmlspecialchars($violation['position'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-records">No individual violations recorded for this student.</p>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <p class="no-records">Student not found or no student number provided.</p>
    <?php endif; ?>
</div>

</body>
</html>
<?php $conn->close(); ?>