<?php
include '../PHP/dbcon.php';

// Handle form submission for adding a violation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['studentNumber'])) {
    $studentNumber = trim($_POST['studentNumber'] ?? '');
    $violationTypeId = trim($_POST['violationType'] ?? '');

    if ($studentNumber && $violationTypeId) {
        $stmt = $conn->prepare("INSERT INTO violation_tbl (student_number, violation_type, violation_date) VALUES (?, ?, NOW())");
        $stmt->bind_param("si", $studentNumber, $violationTypeId);

        if ($stmt->execute()) {
            echo "<script>alert('Violation added successfully.'); window.location.href = '" . $_SERVER['PHP_SELF'] . "';</script>";
        } else {
            echo "<script>alert('Error adding violation.');</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Please fill in all required fields.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Student Violation List</title>
    <link rel="stylesheet" href="../CSS/admin_violation_style.css" />
</head>
<body>
    <header>
        <div class="logo">
            <img src="../assets/PUPlogo.png" alt="PUP Logo" />
        </div>
        <nav>
            <a href="../HTML/admin_homepage.html">Home</a>
            <a href="../HTML/admin_dashboard_violation.html">Violations</a>
            <a href="../HTML/admin_sanction.html">Student Sanction</a>
            <a href="../user-management/user_management.php">User Management</a>
        </nav>
        <div class="admin-icons">
            <a href="notification.html" class="notification">
                <img src="https://img.icons8.com/?size=100&id=83193&format=png&color=000000" />
            </a>
            <a href="admin_account.html" class="admin">
                <img src="https://img.icons8.com/?size=100&id=77883&format=png&color=000000" />
            </a>
        </div>
    </header>

    <div class="container">
        <h1>Student Violation Records</h1>

        <div class="table-controls">
            <div class="filter-search-group">
                <form method="GET" action="" id="filter-form">
                    <select name="violation_type" onchange="this.form.submit()" class="filter-select">
                        <option value="">Filter by Violation Type</option>
                        <?php
                        $vtQuery = "SELECT violation_type_id, violation_type FROM violation_type_tbl ORDER BY violation_type ASC";
                        $vtResult = $conn->query($vtQuery);
                        while ($row = $vtResult->fetch_assoc()) {
                            $selected = isset($_GET['violation_type']) && $_GET['violation_type'] == $row['violation_type_id'] ? 'selected' : '';
                            echo "<option value='{$row['violation_type_id']}' $selected>" . htmlspecialchars($row['violation_type']) . "</option>";
                        }
                        ?>
                    </select>
                    <input type="text" name="search" placeholder="Search by Student Number or Last Name" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" class="search-input"/>
                    <button type="submit" class="search-button">Search</button>

                </form>
            </div>
            <button id="addViolationBtn" class="add-violation-button">Add Violation</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Student Number</th>
                    <th>First Name</th>
                    <th>Middle Name</th>
                    <th>Last Name</th>
                    <th>Course</th>
                    <th>Year</th>
                    <th>Section</th>
                    <th>Violation Type</th>
                    <th>Violation Date</th>
                </tr>
            </thead>
            <tbody id="violationTableBody">
                <?php
                $search = trim($_GET['search'] ?? '');
                $filterViolation = trim($_GET['violation_type'] ?? '');

                $sql = "SELECT v.violation_id, u.student_number, u.first_name, u.middle_name, u.last_name, 
                               c.course_name, y.year, s.section_name, vt.violation_type, v.violation_date 
                                FROM violation_tbl v
                                JOIN users_tbl u ON v.student_number = u.student_number
                                JOIN violation_type_tbl vt ON v.violation_type = vt.violation_type_id
                                LEFT JOIN course_tbl c ON u.course_id = c.course_id
                                LEFT JOIN year_tbl y ON u.year_id = y.year_id
                                LEFT JOIN section_tbl s ON u.section_id = s.section_id
                                WHERE 1";

                if (!empty($search)) {
                    $searchEscaped = $conn->real_escape_string($search);
                    $sql .= " AND (u.student_number LIKE '%$searchEscaped%' OR u.last_name LIKE '%$searchEscaped%')";
                }

                if (!empty($filterViolation)) {
                    $filterViolationEscaped = $conn->real_escape_string($filterViolation);
                    $sql .= " AND vt.violation_type_id = '$filterViolationEscaped'";
                }

                $sql .= " ORDER BY v.violation_id DESC";

                $result = $conn->query($sql);

                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['student_number']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['first_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['middle_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['last_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['course_name'] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars($row['year'] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars($row['section_name'] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars($row['violation_type']) . "</td>";
                        echo "<td>" . date("Y-m-d H:i:s", strtotime($row['violation_date'])) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='9'>No records found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <div id="modal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="head-modal">
                <h3>Add Violation</h3>
            </div>
            <form id="violationForm" class="form-container" method="POST" action="">
                <div class="row">
                    <div class="column">
                        <label for="studentNumber">Student Number:</label>
                        <input type="text" id="studentNumber" name="studentNumber" required />
                    </div>
                    <div class="column">
                        <label for="violationType">Violation Type:</label>
                        <select id="violationType" name="violationType" required>
                            <option value="">Select Violation Type</option>
                            <?php
                            $vtSql = "SELECT violation_type_id, violation_type FROM violation_type_tbl ORDER BY violation_type ASC";
                            $vtResult = $conn->query($vtSql);
                            if ($vtResult && $vtResult->num_rows > 0) {
                                while ($vtRow = $vtResult->fetch_assoc()) {
                                    echo '<option value="' . htmlspecialchars($vtRow['violation_type_id']) . '">' 
                                         . htmlspecialchars($vtRow['violation_type']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="button-row">
                    <button type="submit" class="modal-button-add">Add</button>
                    <button type="button" id="closeModal" class="modal-button-cancel">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="./admin_violation.js"></script>
</body>
</html>

<?php $conn->close(); ?>