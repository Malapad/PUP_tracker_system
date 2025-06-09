<?php
include '../PHP/dbcon.php';

$student_number_display = '';
$student_details = null;
$violations = [];
$violation_summary_details = [];

$back_link_target = '../updated-admin-violation/admin_violation_page.php';

// Get the current script name to determine which tab should be active in the header
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
        } else {
            error_log("Prepare failed for student details: " . $conn->error);
        }

        $sql_violations = "SELECT v.violation_id, vc.category_name, vt.violation_type, v.violation_date, v.description AS remarks
                           FROM violation_tbl v
                           JOIN violation_type_tbl vt ON v.violation_type = vt.violation_type_id
                           LEFT JOIN violation_category_tbl vc ON vt.violation_category_id = vc.violation_category_id
                           WHERE v.student_number = ?
                           ORDER BY v.violation_date DESC, vc.category_name ASC, vt.violation_type ASC"; // Corrected ORDER BY
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

            foreach($temp_summary_data as $data_item){
                $violation_summary_details[] = $data_item;
            }
            usort($violation_summary_details, function($a, $b) {
                $catComp = strcmp($a['category'], $b['category']);
                if ($catComp == 0) {
                    return strcmp($a['type'], $b['type']);
                }
                return $catComp;
            });


        } else {
            error_log("Prepare failed for violations list: " . $conn->error);
        }
    }
}

$totalViolations = count($violations);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Student Violation Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="./admin_violation.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="./student_violation_details_style.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>

    <header>
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top py-0">
            <div class="container-fluid px-4 px-md-5">
                <a class="navbar-brand py-0" href="../HTML/admin_homepage.html">
                    <img src="../IMAGE/Tracker-logo.png" alt="PUP Logo" class="img-fluid" style="height: 60px; width: 180px;">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link" href="../HTML/admin_homepage.html">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="<?php echo htmlspecialchars($back_link_target); ?>">Violations</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../updated-admin-sanction/admin_sanction.php">Student Sanction</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../user-management/user_management.php">User Management</a>
                        </li>
                    </ul>
                    <div class="d-flex align-items-center">
                        <a href="notification.html" class="me-3">
                            <img src="https://img.icons8.com/?size=100&id=83193&format=png&color=000000" alt="Notifications" style="width: 35px; height: 35px;"/>
                        </a>
                        <a href="admin_account.html">
                            <img src="https://img.icons8.com/?size=100&id=77883&format=png&color=000000" alt="Admin Account" style="width: 35px; height: 35px;"/>
                        </a>
                    </div>
                </div>
            </div>
        </nav>
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
        </div>

        <?php if (!empty($violation_summary_details)): ?>
            <div class="violation-summary-by-type">
                <h3 class="summary-title">Summary by Violation</h3>
                <table class="violations-summary-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Violation Type</th>
                            <th>Number of commits</th>
                            <th>Offense</th>
                            <th>Remarks (for single instance)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($violation_summary_details as $summary_item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($summary_item['category']); ?></td>
                                <td><?php echo htmlspecialchars($summary_item['type']); ?></td>
                                <td style="text-align: center;"><?php echo $summary_item['count']; ?></td>
                                <td style="text-align: center;">
                                    <?php
                                    $typeOffenseStatus = ($summary_item['count'] >= 2) ? 'Sanction' : 'Warning';
                                    $typeOffenseClass = ($summary_item['count'] >= 2) ? 'offense-status-text offense-sanction' : 'offense-status-text offense-warning';
                                    echo "<span class='" . $typeOffenseClass . "'>" . htmlspecialchars($typeOffenseStatus) . "</span>";
                                    ?>
                                </td>
                                 <td><?php echo nl2br(htmlspecialchars($summary_item['remark_display'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($totalViolations > 0): ?>
            <div class="violation-summary-by-type">
                <p>Could not generate violation summary.</p>
            </div>
        <?php endif; ?>

        <h3 class="log-title">Individual Violations Log</h3>
        <?php if (!empty($violations)): ?>
            <div class="table-scroll-container">
                <table class="violations-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Violation Type</th>
                            <th>Date of Violation</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($violations as $violation): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($violation['category_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($violation['violation_type']); ?></td>
                                <td><?php echo htmlspecialchars(date("F j, Y, g:i a", strtotime($violation['violation_date']))); ?></td>
                                <td><?php echo nl2br(htmlspecialchars(trim($violation['remarks'] ?? '') === '' ? 'No remarks' : $violation['remarks'])); ?></td>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
<?php $conn->close(); ?>