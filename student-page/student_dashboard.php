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
<?php
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Violation Categories</title>
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  <!-- External CSS file -->
  <link rel="stylesheet" href="violation-config.css" />
</head>
<body>

<div class="search-bar">
  <form method="GET" action="">
    <input type="text" name="search" placeholder="Search category or type..." value="<?= htmlspecialchars($searchTerm) ?>" />
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
      <button class="accordion-header" type="button">
        <span><?= htmlspecialchars($categoryName) ?></span>
        <i class="fas fa-chevron-down accordion-icon"></i>
      </button>
      <div class="accordion-content">
        <ul style="list-style: none; padding-left: 0;">
          <?php
          $typeQuery = "SELECT violation_type_id, violation_type FROM violation_type_tbl WHERE violation_category_id = ? AND violation_type LIKE ?";
          $stmtType = $conn->prepare($typeQuery);
          $stmtType->bind_param("is", $categoryId, $like);
          $stmtType->execute();
          $typeResult = $stmtType->get_result();

          if ($typeResult && $typeResult->num_rows > 0) {
            while ($type = $typeResult->fetch_assoc()) {
              echo "<li class='violation-type-item' data-id='" . $type['violation_type_id'] . "'>" . htmlspecialchars($type['violation_type']) . "</li>";
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

<!-- Modal -->
<div id="violation-details-modal" class="modal" style="display: none;">
  <div class="modal-content">
    <span class="close" id="close-modal">&times;</span>
    <h2>Violation Details</h2>
    <p><strong>Category:</strong> <span id="detail-category"></span></p>
    <p><strong>Type:</strong> <span id="detail-type"></span></p>
    <h3>Disciplinary Sanctions</h3>
    <table>
      <thead>
        <tr>
          <th>Offense Level</th>
          <th>Sanction</th>
        </tr>
      </thead>
      <tbody id="sanctions-table-body">
        <script src="./student_dashboard.js" defer></script>
      </tbody>
    </table>
  </div>
</div>




</body>
</html>
