<?php
include '../PHP/dbcon.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../user-management/user_management.css">
    <link rel="stylesheet" href="../user-management/add_student.css">
    <title>User Management</title>
</head>

<body>
    <header>
        <div class="logo">
            <img src="../assets/PUP_logo.png" alt="PUP Logo">
        </div>
        <nav>
            <a href="../HTML/admin_homepage.html">Home</a>
            <a href="../admin-violation/admin_violation.php">Violations</a>
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
        <h2>User Management</h2>
        <div class="tabs">
            <button class="tab active">Students</button>
        </div>

        <div class="controls">
            <div class="filter-group">
                <form method="GET" action="" id="filter-form">
                    <select id="course" name="course" required onchange="this.form.submit()">
                        <option value="">Select Course</option>
                        <?php
                        $courseQuery = "SELECT course_id, course_name FROM course_tbl";
                        $courseResult = mysqli_query($conn, $courseQuery);
                        while ($row = mysqli_fetch_assoc($courseResult)) {
                            $selected = isset($_GET['course']) && $_GET['course'] == $row['course_id'] ? 'selected' : '';
                            echo "<option value='{$row['course_id']}' $selected>{$row['course_name']}</option>";
                        }
                        ?>
                    </select>

                    <select id="year" name="year" required onchange="this.form.submit()">
                        <option value="">Select Year</option>
                        <?php
                        $yearQuery = "SELECT year_id, year FROM year_tbl";
                        $yearResult = mysqli_query($conn, $yearQuery);
                        while ($row = mysqli_fetch_assoc($yearResult)) {
                            $selected = isset($_GET['year']) && $_GET['year'] == $row['year_id'] ? 'selected' : '';
                            echo "<option value='{$row['year_id']}' $selected>{$row['year']}</option>";
                        }
                        ?>
                    </select>

                    <select id="section" name="section" required onchange="this.form.submit()">
                        <option value="">Select Section</option>
                        <?php
                        $sectionQuery = "SELECT section_id, section_name FROM section_tbl";
                        $sectionResult = mysqli_query($conn, $sectionQuery);
                        while ($row = mysqli_fetch_assoc($sectionResult)) {
                            $selected = isset($_GET['section']) && $_GET['section'] == $row['section_id'] ? 'selected' : '';
                            echo "<option value='{$row['section_id']}' $selected>{$row['section_name']}</option>";
                        }
                        ?>
                    </select>

                    <select id="status" name="status" required onchange="this.form.submit()">
                        <option value="">Select Status</option>
                        <?php
                        $statusQuery = "SELECT status_id, status_name FROM status_tbl";
                        $statusResult = mysqli_query($conn, $statusQuery);
                        while ($row = mysqli_fetch_assoc($statusResult)) {
                            $selected = isset($_GET['status']) && $_GET['status'] == $row['status_id'] ? 'selected' : '';
                            echo "<option value='{$row['status_id']}' $selected>{$row['status_name']}</option>";
                        }
                        ?>
                    </select>
                </form>
            </div>

            <div class="search-bar">
                <form method="GET" action="">
                    <input type="text" name="search" id="search-input" placeholder="Search by Student Number" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

            <button id="add-student" class="blue-button">Add Student</button>
        </div>

        <table id="student-table">
            <thead>
                <tr>
                    <th>Student Number</th>
                    <th>Last Name</th>
                    <th>First Name</th>
                    <th>Middle Name</th>
                    <th>Email</th>
                    <th>Course</th>
                    <th>Year</th>
                    <th>Section</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
                $course_id = isset($_GET['course']) ? mysqli_real_escape_string($conn, $_GET['course']) : '';
                $year_id = isset($_GET['year']) ? mysqli_real_escape_string($conn, $_GET['year']) : '';
                $section_id = isset($_GET['section']) ? mysqli_real_escape_string($conn, $_GET['section']) : '';
                $status_id = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

                $query = "SELECT u.student_number, u.last_name, u.first_name, u.middle_name, u.email,
                            c.course_name, y.year, s.section_name, st.status_name
                        FROM users_tbl u
                        LEFT JOIN course_tbl c ON u.course_id = c.course_id
                        LEFT JOIN year_tbl y ON u.year_id = y.year_id
                        LEFT JOIN section_tbl s ON u.section_id = s.section_id
                        LEFT JOIN status_tbl st ON u.status_id = st.status_id
                        WHERE 1";
                
                if (!empty($search)) {
                    $query .= " AND u.student_number LIKE '%$search%'";
                }
                if (!empty($course_id)) {
                    $query .= " AND u.course_id = '$course_id'";
                }
                if (!empty($year_id)) {
                    $query .= " AND u.year_id = '$year_id'";
                }
                if (!empty($section_id)) {
                    $query .= " AND u.section_id = '$section_id'";
                }
                if (!empty($status_id)) {
                    $query .= " AND u.status_id = '$status_id'";
                }

                $result = mysqli_query($conn, $query);

                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        ?>
                        <tr>
                            <td><?php echo $row['student_number']; ?></td>
                            <td><?php echo $row['last_name']; ?></td>
                            <td><?php echo $row['first_name']; ?></td>
                            <td><?php echo $row['middle_name']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td><?php echo $row['course_name']; ?></td>
                            <td><?php echo $row['year']; ?></td>
                            <td><?php echo $row['section_name']; ?></td>
                            <td><?php echo $row['status_name']; ?></td>
                            <td>
                                <button class='edit-btn' data-student='<?php echo htmlspecialchars(json_encode($row)); ?>'>Edit</button>
                                <form action="delete_student.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this student?');">
                                    <input type="hidden" name="student_number" value="<?php echo $row['student_number']; ?>">
                                    <button type="submit" class="delete-btn">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    echo "<tr><td colspan='10'>No data available</td></tr>";
                }
                ?>
            </tbody>
        </table>

    </div>

    <div id="modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Add Student</h3>
            <form id="student-form">
                <input type="text" id="student-number" name="student_number" placeholder="Student Number" required>
                <input type="text" id="first-name" name="first_name" placeholder="First Name" required>
                <input type="text" id="middle-name" name="middle_name" placeholder="Middle Name">
                <input type="text" id="last-name" name="last_name" placeholder="Last Name" required>
                <input type="email" id="email" name="email" placeholder="Email" required>

                <select id="course" name="course" required>
                    <option value="">Select Course</option>
                    <?php
                        $courseQuery = "SELECT course_id, course_name FROM course_tbl";
                        $courseResult = mysqli_query($conn, $courseQuery);
                        while ($row = mysqli_fetch_assoc($courseResult)) {
                            echo "<option value='{$row['course_id']}'>{$row['course_name']}</option>";
                        }
                    ?>
                </select>

                <select id="year" name="year" required>
                    <option value="">Select Year</option>
                    <?php
                        $yearQuery = "SELECT year_id, year FROM year_tbl";
                        $yearResult = mysqli_query($conn, $yearQuery);
                        while ($row = mysqli_fetch_assoc($yearResult)) {
                            echo "<option value='{$row['year_id']}'>{$row['year']}</option>";
                        }
                    ?>
                </select>

                <select id="section" name="section" required>
                    <option value="">Select Section</option>
                    <?php
                        $sectionQuery = "SELECT section_id, section_name FROM section_tbl";
                        $sectionResult = mysqli_query($conn, $sectionQuery);
                        while ($row = mysqli_fetch_assoc($sectionResult)) {
                            echo "<option value='{$row['section_id']}'>{$row['section_name']}</option>";
                        }
                    ?>
                </select>

                <select id="status" name="status" required>
                    <option value="">Select Status</option>
                    <?php
                        $statusQuery = "SELECT status_id, status_name FROM status_tbl";
                        $statusResult = mysqli_query($conn, $statusQuery);
                        while ($row = mysqli_fetch_assoc($statusResult)) {
                            echo "<option value='{$row['status_id']}'>{$row['status_name']}</option>";
                        }
                    ?>
                </select>

                <button type="submit" id="submit-btn">Add Student</button>
            </form>
        </div>
    </div>

    <script src="./user_management.js"></script>
    <script src="./add_student.js"></script>

</body>

</html>
