<?php
include '../PHP/dbcon.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../user-management/user_management.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <title>User Management</title>
</head>
<body>
    <header>
        <div class="logo">
            <img src="../assets/PUP_logo.png" alt="PUP Logo">
        </div>
        <nav>
            <a href="../HTML/admin_homepage.html">Home</a>
            <a href="../updated-admin-violation/admin-violationpage.php">Violations</a>
            <a href="../updated-admin-sanction/admin_sanction.php">Student Sanction</a>
            <a href="../user-management/user_management.php" class="active">User Management</a>
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

    <div class="container">
        <h1>User Management</h1>
        <div class="tabs">
            <button class="tab active">Students</button>
        </div>

        <div class="controls">
            <?php
            $is_filter_active = !empty($_GET['course']) || !empty($_GET['year']) || !empty($_GET['section']) || !empty($_GET['status']) || !empty($_GET['search']);
            if ($is_filter_active):
            ?>
            <div class="clear-filters-row">
                <div class="clear-filters-container">
                    <a href="user_management.php" class="clear-filters-btn">
                        <i class="fas fa-eraser"></i> Clear Filters
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <div class="main-controls-wrapper">
                <div class="left-control-group">
                    <form method="GET" action="" id="filter-form">
                        <select id="course" name="course" onchange="this.form.submit()">
                            <option value="">Select Course</option>
                            <?php
                            // Ensure $conn is available and connected
                            if ($conn) {
                                $courseQuery = "SELECT course_id, course_name FROM course_tbl ORDER BY course_name ASC";
                                $courseResultPHP = mysqli_query($conn, $courseQuery);
                                if ($courseResultPHP) {
                                    while ($row = mysqli_fetch_assoc($courseResultPHP)) {
                                        $selected = isset($_GET['course']) && $_GET['course'] == $row['course_id'] ? 'selected' : '';
                                        echo "<option value='{$row['course_id']}' $selected>{$row['course_name']}</option>";
                                    }
                                }
                            }
                            ?>
                        </select>

                        <select id="year" name="year" onchange="this.form.submit()">
                            <option value="">Select Year</option>
                            <?php
                             if ($conn) {
                                $yearQuery = "SELECT year_id, year FROM year_tbl ORDER BY year ASC";
                                $yearResultPHP = mysqli_query($conn, $yearQuery);
                                if ($yearResultPHP) {
                                    while ($row = mysqli_fetch_assoc($yearResultPHP)) {
                                        $selected = isset($_GET['year']) && $_GET['year'] == $row['year_id'] ? 'selected' : '';
                                        echo "<option value='{$row['year_id']}' $selected>{$row['year']}</option>";
                                    }
                                }
                            }
                            ?>
                        </select>

                        <select id="section" name="section" onchange="this.form.submit()">
                            <option value="">Select Section</option>
                            <?php
                            if ($conn) {
                                $sectionQuery = "SELECT section_id, section_name FROM section_tbl ORDER BY section_name ASC";
                                $sectionResultPHP = mysqli_query($conn, $sectionQuery);
                                if ($sectionResultPHP) {
                                    while ($row = mysqli_fetch_assoc($sectionResultPHP)) {
                                        $selected = isset($_GET['section']) && $_GET['section'] == $row['section_id'] ? 'selected' : '';
                                        echo "<option value='{$row['section_id']}' $selected>{$row['section_name']}</option>";
                                    }
                                }
                            }
                            ?>
                        </select>

                        <select id="status" name="status" onchange="this.form.submit()">
                            <option value="">Select Status</option>
                            <?php
                            if ($conn) {
                                $statusQuery = "SELECT status_id, status_name FROM status_tbl ORDER BY status_name ASC";
                                $statusResultPHP = mysqli_query($conn, $statusQuery);
                                if ($statusResultPHP) {
                                    while ($row = mysqli_fetch_assoc($statusResultPHP)) {
                                        $selected = isset($_GET['status']) && $_GET['status'] == $row['status_id'] ? 'selected' : '';
                                        echo "<option value='{$row['status_id']}' $selected>{$row['status_name']}</option>";
                                    }
                                }
                            }
                            ?>
                        </select>
                        
                        <div class="search-field-group">
                            <input type="text" name="search" id="search-input" placeholder="Search by Student Number" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            <button type="submit" class="search-button">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </form>
                </div> 
                <div class="right-control-group table-actions-group">
                    <button type="button" id="refresh-list-btn" class="refresh-button" onclick="window.location.href=window.location.pathname;">
                        <i class="fas fa-sync-alt"></i> Refresh List
                    </button>
                    <button type="button" id="open-add-student-modal-btn" class="add-student-button">
                        <i class="fas fa-plus"></i> Add Student
                    </button>
                </div>
            </div> </div> 
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
                if ($conn) {
                    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
                    $course_id_filter = isset($_GET['course']) ? mysqli_real_escape_string($conn, $_GET['course']) : '';
                    $year_id_filter = isset($_GET['year']) ? mysqli_real_escape_string($conn, $_GET['year']) : '';
                    $section_id_filter = isset($_GET['section']) ? mysqli_real_escape_string($conn, $_GET['section']) : '';
                    $status_id_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

                    $query = "SELECT u.student_number, u.last_name, u.first_name, u.middle_name, u.email,
                                    u.course_id, c.course_name, u.year_id, y.year, u.section_id, s.section_name, u.status_id, st.status_name
                                FROM users_tbl u
                                LEFT JOIN course_tbl c ON u.course_id = c.course_id
                                LEFT JOIN year_tbl y ON u.year_id = y.year_id
                                LEFT JOIN section_tbl s ON u.section_id = s.section_id
                                LEFT JOIN status_tbl st ON u.status_id = st.status_id
                                WHERE 1";

                    if (!empty($search)) {
                        $query .= " AND u.student_number LIKE '%$search%'";
                    }
                    if (!empty($course_id_filter)) {
                        $query .= " AND u.course_id = '$course_id_filter'";
                    }
                    if (!empty($year_id_filter)) {
                        $query .= " AND u.year_id = '$year_id_filter'";
                    }
                    if (!empty($section_id_filter)) {
                        $query .= " AND u.section_id = '$section_id_filter'";
                    }
                    if (!empty($status_id_filter)) {
                        $query .= " AND u.status_id = '$status_id_filter'";
                    }
                    $query .= " ORDER BY u.last_name ASC, u.first_name ASC";

                    $result = mysqli_query($conn, $query);

                    if ($result && mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $student_data_json = htmlspecialchars(json_encode([
                                'student_number' => $row['student_number'],
                                'first_name' => $row['first_name'],
                                'middle_name' => $row['middle_name'],
                                'last_name' => $row['last_name'],
                                'email' => $row['email'],
                                'course_id' => $row['course_id'],
                                'year_id' => $row['year_id'],
                                'section_id' => $row['section_id'],
                                'status_id' => $row['status_id']
                            ]), ENT_QUOTES, 'UTF-8');
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['student_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['middle_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['year']); ?></td>
                                <td><?php echo htmlspecialchars($row['section_name']); ?></td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    $status_name_lower = strtolower(htmlspecialchars($row['status_name']));
                                    if ($status_name_lower == 'active') {
                                        $status_class = 'status-active';
                                    } elseif ($status_name_lower == 'inactive') {
                                        $status_class = 'status-inactive';
                                    } else {
                                        $status_class = 'status-default';
                                    }
                                    ?>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo htmlspecialchars($row['status_name']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="table-action-buttons">
                                        <button class='edit-btn' data-student='<?php echo $student_data_json; ?>'>
                                            <i class="fas fa-pencil-alt"></i> Edit
                                        </button>
                                        <button type="button" class="delete-btn delete-trigger-btn" data-student-number="<?php echo htmlspecialchars($row['student_number']); ?>">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo "<tr><td colspan='10'>No data available</td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='10'>Database connection error.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <div id="edit-modal" class="modal">
        <div class="modal-content">
            <span class="close-edit">&times;</span>
            <h3>Edit Student</h3>
            <form id="edit-student-form">
                <input type="hidden" id="original-student-number" name="original_student_number">
                <div><label for="edit-student-number">Student Number:</label><input type="text" id="edit-student-number" name="student_number" placeholder="Student Number" required></div>
                <div><label for="edit-first-name">First Name:</label><input type="text" id="edit-first-name" name="first_name" placeholder="First Name" required></div>
                <div><label for="edit-middle-name">Middle Name:</label><input type="text" id="edit-middle-name" name="middle_name" placeholder="Middle Name"></div>
                <div><label for="edit-last-name">Last Name:</label><input type="text" id="edit-last-name" name="last_name" placeholder="Last Name" required></div>
                <div><label for="edit-email">Email:</label><input type="email" id="edit-email" name="email" placeholder="Email" required></div>
                <div><label for="edit-course">Course:</label><select id="edit-course" name="course_id" required>
                    <option value="">Select Course</option>
                        <?php
                        if ($conn) {
                            $courseResultModal = mysqli_query($conn, "SELECT course_id, course_name FROM course_tbl ORDER BY course_name ASC");
                            if ($courseResultModal) {
                                while ($row = mysqli_fetch_assoc($courseResultModal)) {
                                    echo "<option value='{$row['course_id']}'>{$row['course_name']}</option>";
                                }
                            }
                        }
                        ?>
                </select></div>
                <div><label for="edit-year">Year:</label><select id="edit-year" name="year_id" required>
                    <option value="">Select Year</option>
                    <?php
                    if ($conn) {
                        $yearResultModal = mysqli_query($conn, "SELECT year_id, year FROM year_tbl ORDER BY year ASC");
                        if ($yearResultModal) {
                            while ($row = mysqli_fetch_assoc($yearResultModal)) {
                                echo "<option value='{$row['year_id']}'>{$row['year']}</option>";
                            }
                        }
                    }
                    ?>
                </select></div>
                <div><label for="edit-section">Section:</label><select id="edit-section" name="section_id" required>
                    <option value="">Select Section</option>
                        <?php
                        if ($conn) {
                            $sectionResultModal = mysqli_query($conn, "SELECT section_id, section_name FROM section_tbl ORDER BY section_name ASC");
                            if ($sectionResultModal) {
                                while ($row = mysqli_fetch_assoc($sectionResultModal)) {
                                    echo "<option value='{$row['section_id']}'>{$row['section_name']}</option>";
                                }
                            }
                        }
                        ?>
                </select></div>
                <div><label for="edit-status">Status:</label><select id="edit-status" name="status_id" required>
                        <option value="">Select Status</option>
                    <?php
                    if ($conn) {
                        $statusResultModal = mysqli_query($conn, "SELECT status_id, status_name FROM status_tbl ORDER BY status_name ASC");
                        if ($statusResultModal) {
                            while ($row = mysqli_fetch_assoc($statusResultModal)) {
                                echo "<option value='{$row['status_id']}'>{$row['status_name']}</option>";
                            }
                        }
                    }
                    ?>
                </select></div>
                <button type="submit">Update Student</button>
            </form>
        </div>
    </div>

    <div id="add-student-modal" class="modal">
        <div class="modal-content">
            <span class="close-add">&times;</span>
            <h3>Add New Student</h3>
            <form id="add-student-form">
                <div><label for="add-student-number">Student Number:</label><input type="text" id="add-student-number" name="student_number" placeholder="Student Number" required></div>
                <div><label for="add-first-name">First Name:</label><input type="text" id="add-first-name" name="first_name" placeholder="First Name" required></div>
                <div><label for="add-middle-name">Middle Name:</label><input type="text" id="add-middle-name" name="middle_name" placeholder="Middle Name"></div>
                <div><label for="add-last-name">Last Name:</label><input type="text" id="add-last-name" name="last_name" placeholder="Last Name" required></div>
                <div><label for="add-email">Email:</label><input type="email" id="add-email" name="email" placeholder="Email" required></div>
                <div><label for="add-course">Course:</label><select id="add-course" name="course_id" required>
                    <option value="">Select Course</option>
                        <?php
                        // Re-use $courseResultPHP if available and reset pointer
                        if (isset($courseResultPHP) && $courseResultPHP) { 
                            mysqli_data_seek($courseResultPHP, 0);
                            while ($row = mysqli_fetch_assoc($courseResultPHP)) {
                                echo "<option value='{$row['course_id']}'>{$row['course_name']}</option>";
                            }
                        } elseif($conn) { // Fallback if $courseResultPHP was not set from filter section
                             $courseResultModal = mysqli_query($conn, "SELECT course_id, course_name FROM course_tbl ORDER BY course_name ASC");
                            if ($courseResultModal) {
                                while ($row = mysqli_fetch_assoc($courseResultModal)) {
                                    echo "<option value='{$row['course_id']}'>{$row['course_name']}</option>";
                                }
                            }
                        }
                        ?>
                </select></div>
                <div><label for="add-year">Year:</label><select id="add-year" name="year_id" required>
                    <option value="">Select Year</option>
                    <?php
                    if (isset($yearResultPHP) && $yearResultPHP) { 
                        mysqli_data_seek($yearResultPHP, 0);
                        while ($row = mysqli_fetch_assoc($yearResultPHP)) {
                            echo "<option value='{$row['year_id']}'>{$row['year']}</option>";
                        }
                    } elseif($conn) {
                        $yearResultModal = mysqli_query($conn, "SELECT year_id, year FROM year_tbl ORDER BY year ASC");
                        if ($yearResultModal) {
                            while ($row = mysqli_fetch_assoc($yearResultModal)) {
                                echo "<option value='{$row['year_id']}'>{$row['year']}</option>";
                            }
                        }
                    }
                    ?>
                </select></div>
                <div><label for="add-section">Section:</label><select id="add-section" name="section_id" required>
                    <option value="">Select Section</option>
                        <?php
                        if (isset($sectionResultPHP) && $sectionResultPHP) { 
                            mysqli_data_seek($sectionResultPHP, 0);
                            while ($row = mysqli_fetch_assoc($sectionResultPHP)) {
                                echo "<option value='{$row['section_id']}'>{$row['section_name']}</option>";
                            }
                        } elseif($conn) {
                             $sectionResultModal = mysqli_query($conn, "SELECT section_id, section_name FROM section_tbl ORDER BY section_name ASC");
                            if ($sectionResultModal) {
                                while ($row = mysqli_fetch_assoc($sectionResultModal)) {
                                    echo "<option value='{$row['section_id']}'>{$row['section_name']}</option>";
                                }
                            }
                        }
                        ?>
                </select></div>
                <div><label for="add-status">Status:</label><select id="add-status" name="status_id" required>
                    <option value="">Select Status</option>
                    <?php
                    if (isset($statusResultPHP) && $statusResultPHP) { 
                        mysqli_data_seek($statusResultPHP, 0);
                        while ($row = mysqli_fetch_assoc($statusResultPHP)) {
                            echo "<option value='{$row['status_id']}'>{$row['status_name']}</option>";
                        }
                    } elseif($conn) {
                        $statusResultModal = mysqli_query($conn, "SELECT status_id, status_name FROM status_tbl ORDER BY status_name ASC");
                        if ($statusResultModal) {
                            while ($row = mysqli_fetch_assoc($statusResultModal)) {
                                echo "<option value='{$row['status_id']}'>{$row['status_name']}</option>";
                            }
                        }
                    }
                    ?>
                </select></div>
                <button type="submit">Add Student</button>
            </form>
        </div>
    </div>

    <div id="delete-confirm-modal" class="modal">
        <div class="modal-content">
            <span class="close-delete-confirm">&times;</span>
            <h3><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h3>
            <p id="delete-confirm-text">Are you sure you want to delete student: <strong id="delete-student-id-display"></strong>?</p>
            <div class="delete-confirm-actions">
                <button type="button" id="confirm-delete-action-btn" class="delete-btn">
                    <i class="fas fa-trash-alt"></i> Confirm Delete
                </button>
                <button type="button" id="cancel-delete-action-btn" class="edit-btn">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </div>
    </div>

    <div id="custom-toast-notification" class="toast-notification">
        <span id="toast-notification-message"></span>
    </div>

    <script src="./user_management.js"></script>
</body>
</html>