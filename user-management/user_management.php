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
            <a href="../updated-admin-violation/admin_violation_page.php">Violations</a>
            <a href="../HTML/admin_sanction.html">Student Sanction</a>
            <a href="../user-management/user_management.php" class="active">User Management</a>
        </nav>
        <div class="admin-icons">
            <a href="../HTML/notification.html" class="notification">
                <img src="https://img.icons8.com/?size=100&id=83193&format=png&color=000000" alt="Notifications"/>
            </a>
            <a href="../PHP/admin_account.php" class="admin">
                <img src="https://img.icons8.com/?size=100&id=77883&format=png&color=000000" alt="Admin Account"/>
            </a>
        </div>
    </header>

    <div class="container">
        <h1>User Management</h1>
        <div class="tabs">
            <button class="tab active" data-tab="students">Students</button>
            <button class="tab" data-tab="admins">Admins</button>
        </div>

        <div id="students-content" class="tab-content active">
            <div class="controls" id="student-controls">
                <?php
                $is_student_filter_active = !empty($_GET['course']) || !empty($_GET['year']) || !empty($_GET['section']) || !empty($_GET['status']) || !empty($_GET['search']);
                if ($is_student_filter_active && (!isset($_GET['tab']) || $_GET['tab'] === 'students')):
                ?>
                <div class="clear-filters-row">
                    <div class="clear-filters-container">
                        <a href="user_management.php?tab=students" class="clear-filters-btn">
                            <i class="fas fa-eraser"></i> Clear Student Filters
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <div class="main-controls-wrapper">
                    <div class="left-control-group">
                        <form method="GET" action="" id="student-filter-form">
                            <input type="hidden" name="tab" value="students">
                            <select id="course" name="course" onchange="this.form.submit()">
                                <option value="">Select Course</option>
                                <?php
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
                                <input type="text" name="search" id="search-student-input" placeholder="Search by Student Number" value="<?php echo isset($_GET['search']) && (!isset($_GET['tab']) || $_GET['tab'] === 'students') ? htmlspecialchars($_GET['search']) : ''; ?>">
                                <button type="submit" class="search-button">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="right-control-group table-actions-group">
                        <button type="button" id="refresh-student-list-btn" class="refresh-button">
                            <i class="fas fa-sync-alt"></i> Refresh List
                        </button>
                        <button type="button" id="open-add-student-modal-btn" class="add-user-button">
                            <i class="fas fa-plus"></i> Add Student
                        </button>
                    </div>
                </div>
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
                    if ($conn) {
                        $search_student = isset($_GET['search']) && (!isset($_GET['tab']) || $_GET['tab'] === 'students') ? mysqli_real_escape_string($conn, $_GET['search']) : '';
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

                        if (!empty($search_student)) {
                            $query .= " AND u.student_number LIKE '%$search_student%'";
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
                                        $status_class = 'status-default';
                                        $status_name_lower = strtolower(htmlspecialchars($row['status_name']));
                                        if ($status_name_lower == 'active') {
                                            $status_class = 'status-active';
                                        } elseif ($status_name_lower == 'inactive') {
                                            $status_class = 'status-inactive';
                                        }
                                        ?>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo htmlspecialchars($row['status_name']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="table-action-buttons">
                                            <button class='edit-btn student-edit-btn' data-student='<?php echo $student_data_json; ?>'>
                                                <i class="fas fa-pencil-alt"></i> Edit
                                            </button>
                                            <button type="button" class="delete-btn student-delete-trigger-btn" 
                                                    data-id="<?php echo htmlspecialchars($row['student_number']); ?>" 
                                                    data-name="<?php echo htmlspecialchars($row['student_number']); ?>"
                                                    data-type="student">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                             echo "<tr><td colspan='10'>No student data available</td></tr>";
                        }
                    } else {
                        echo "<tr><td colspan='10'>Database connection error.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div id="admins-content" class="tab-content">
            <div class="controls" id="admin-controls">
                 <?php
                $is_admin_filter_active = !empty($_GET['admin_search']);
                if ($is_admin_filter_active && isset($_GET['tab']) && $_GET['tab'] === 'admins'):
                ?>
                <div class="clear-filters-row">
                    <div class="clear-filters-container">
                        <a href="user_management.php?tab=admins" class="clear-filters-btn">
                            <i class="fas fa-eraser"></i> Clear Admin Filters
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                <div class="main-controls-wrapper">
                    <div class="left-control-group">
                        <form method="GET" action="" id="admin-filter-form">
                             <input type="hidden" name="tab" value="admins">
                             <div class="search-field-group">
                                <input type="text" name="admin_search" id="admin-search-input" placeholder="Search by Name or Email" value="<?php echo isset($_GET['admin_search']) && isset($_GET['tab']) && $_GET['tab'] === 'admins' ? htmlspecialchars($_GET['admin_search']) : ''; ?>">
                                <button type="submit" class="search-button"><i class="fas fa-search"></i> Search</button>
                            </div>
                        </form>
                    </div>
                    <div class="right-control-group table-actions-group">
                        <button type="button" id="refresh-admin-list-btn" class="refresh-button">
                            <i class="fas fa-sync-alt"></i> Refresh List
                        </button>
                        <button type="button" id="open-add-admin-modal-btn" class="add-user-button">
                            <i class="fas fa-plus"></i> Add Admin
                        </button>
                    </div>
                </div>
            </div>
            <table id="admin-table">
                <thead>
                    <tr>
                        <th>Position</th>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>Middle Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($conn) {
                        $admin_search = isset($_GET['admin_search']) && isset($_GET['tab']) && $_GET['tab'] === 'admins' ? mysqli_real_escape_string($conn, $_GET['admin_search']) : '';

                        $adminQuery = "SELECT ai.admin_id, ai.firstname, ai.middlename, ai.lastname, ai.Position, 
                                              a.username, a.email, ai.status_id, st.status_name
                                       FROM admin_info_tbl ai
                                       JOIN admins a ON ai.admin_id = a.id
                                       LEFT JOIN status_tbl st ON ai.status_id = st.status_id 
                                       WHERE 1";
                        if (!empty($admin_search)) {
                            $adminQuery .= " AND (ai.firstname LIKE '%$admin_search%' OR ai.middlename LIKE '%$admin_search%' OR ai.lastname LIKE '%$admin_search%' OR a.email LIKE '%$admin_search%' OR ai.Position LIKE '%$admin_search%')";
                        }
                        $adminQuery .= " ORDER BY ai.lastname ASC, ai.firstname ASC";

                        $adminResult = mysqli_query($conn, $adminQuery);

                        if ($adminResult && mysqli_num_rows($adminResult) > 0) {
                            while ($adminRow = mysqli_fetch_assoc($adminResult)) {
                                $adminFullName = htmlspecialchars($adminRow['firstname'] . ' ' . $adminRow['lastname']);
                                $admin_data_json = htmlspecialchars(json_encode([
                                    'admin_id' => $adminRow['admin_id'],
                                    'first_name' => $adminRow['firstname'],
                                    'middle_name' => $adminRow['middlename'],
                                    'last_name' => $adminRow['lastname'],
                                    'position' => $adminRow['Position'],
                                    'username' => $adminRow['username'], 
                                    'email' => $adminRow['email'],
                                    'status_id' => $adminRow['status_id']
                                ]), ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($adminRow['Position']); ?></td>
                                    <td><?php echo htmlspecialchars($adminRow['lastname']); ?></td>
                                    <td><?php echo htmlspecialchars($adminRow['firstname']); ?></td>
                                    <td><?php echo htmlspecialchars($adminRow['middlename']); ?></td>
                                    <td><?php echo htmlspecialchars($adminRow['email']); ?></td>
                                    <td>
                                        <?php
                                        $status_class = 'status-default';
                                        $current_status_name = $adminRow['status_name'] ?? 'Unknown';
                                        $status_name_lower = strtolower(htmlspecialchars($current_status_name));
                                        
                                        if ($status_name_lower == 'active') {
                                            $status_class = 'status-active';
                                        } elseif ($status_name_lower == 'inactive') {
                                            $status_class = 'status-inactive';
                                        }
                                        ?>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo htmlspecialchars($current_status_name); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="table-action-buttons">
                                            <button class='edit-btn admin-edit-btn' data-admin='<?php echo $admin_data_json; ?>'>
                                                <i class="fas fa-pencil-alt"></i> Edit
                                            </button>
                                            <button type="button" class="delete-btn admin-delete-trigger-btn" 
                                                    data-id="<?php echo htmlspecialchars($adminRow['admin_id']); ?>" 
                                                    data-name="<?php echo $adminFullName; ?>"
                                                    data-type="admin">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo "<tr><td colspan='7'>No admin data available</td></tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7'>Database connection error.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="edit-student-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal close-edit-student">&times;</span>
            <h3>Edit Student</h3>
            <form id="edit-student-form">
                <input type="hidden" id="original-student-number" name="original_student_number">
                <div><label for="edit-student-number">Student Number:</label><input type="text" id="edit-student-number" name="student_number" placeholder="Student Number" required></div>
                <div><label for="edit-student-first-name">First Name:</label><input type="text" id="edit-student-first-name" name="first_name" placeholder="First Name" required></div>
                <div><label for="edit-student-middle-name">Middle Name:</label><input type="text" id="edit-student-middle-name" name="middle_name" placeholder="Middle Name"></div>
                <div><label for="edit-student-last-name">Last Name:</label><input type="text" id="edit-student-last-name" name="last_name" placeholder="Last Name" required></div>
                <div><label for="edit-student-email">Email:</label><input type="email" id="edit-student-email" name="email" placeholder="Email" required></div>
                <div><label for="edit-student-course">Course:</label><select id="edit-student-course" name="course_id" required>
                    <option value="">Select Course</option>
                    <?php
                    if ($conn && isset($courseResultPHP) && $courseResultPHP && mysqli_num_rows($courseResultPHP) > 0) {
                        mysqli_data_seek($courseResultPHP, 0);
                        while ($row = mysqli_fetch_assoc($courseResultPHP)) {
                            echo "<option value='{$row['course_id']}'>{$row['course_name']}</option>";
                        }
                    } elseif ($conn) {
                        $courseResultModal = mysqli_query($conn, "SELECT course_id, course_name FROM course_tbl ORDER BY course_name ASC");
                        if ($courseResultModal) { while ($row = mysqli_fetch_assoc($courseResultModal)) { echo "<option value='{$row['course_id']}'>{$row['course_name']}</option>"; } }
                    }
                    ?>
                </select></div>
                <div><label for="edit-student-year">Year:</label><select id="edit-student-year" name="year_id" required>
                    <option value="">Select Year</option>
                    <?php
                    if ($conn && isset($yearResultPHP) && $yearResultPHP && mysqli_num_rows($yearResultPHP) > 0) {
                        mysqli_data_seek($yearResultPHP, 0);
                        while ($row = mysqli_fetch_assoc($yearResultPHP)) {
                            echo "<option value='{$row['year_id']}'>{$row['year']}</option>";
                        }
                    } elseif ($conn) {
                        $yearResultModal = mysqli_query($conn, "SELECT year_id, year FROM year_tbl ORDER BY year ASC");
                        if ($yearResultModal) { while ($row = mysqli_fetch_assoc($yearResultModal)) { echo "<option value='{$row['year_id']}'>{$row['year']}</option>"; } }
                    }
                    ?>
                </select></div>
                <div><label for="edit-student-section">Section:</label><select id="edit-student-section" name="section_id" required>
                    <option value="">Select Section</option>
                    <?php
                    if ($conn && isset($sectionResultPHP) && $sectionResultPHP && mysqli_num_rows($sectionResultPHP) > 0) {
                        mysqli_data_seek($sectionResultPHP, 0);
                        while ($row = mysqli_fetch_assoc($sectionResultPHP)) {
                            echo "<option value='{$row['section_id']}'>{$row['section_name']}</option>";
                        }
                    } elseif ($conn) {
                        $sectionResultModal = mysqli_query($conn, "SELECT section_id, section_name FROM section_tbl ORDER BY section_name ASC");
                        if ($sectionResultModal) { while ($row = mysqli_fetch_assoc($sectionResultModal)) { echo "<option value='{$row['section_id']}'>{$row['section_name']}</option>"; } }
                    }
                    ?>
                </select></div>
                <div><label for="edit-student-status">Status:</label><select id="edit-student-status" name="status_id" required>
                    <option value="">Select Status</option>
                    <?php
                    if ($conn && isset($statusResultPHP) && $statusResultPHP && mysqli_num_rows($statusResultPHP) > 0) {
                         mysqli_data_seek($statusResultPHP, 0);
                        while ($row = mysqli_fetch_assoc($statusResultPHP)) {
                            echo "<option value='{$row['status_id']}'>{$row['status_name']}</option>";
                        }
                    } elseif ($conn) {
                        $statusResultModal = mysqli_query($conn, "SELECT status_id, status_name FROM status_tbl ORDER BY status_name ASC");
                        if ($statusResultModal) { while ($row = mysqli_fetch_assoc($statusResultModal)) { echo "<option value='{$row['status_id']}'>{$row['status_name']}</option>"; } }
                    }
                    ?>
                </select></div>
                <button type="submit">Update Student</button>
            </form>
        </div>
    </div>

    <div id="add-student-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal close-add-student">&times;</span>
            <h3>Add New Student</h3>
            <form id="add-student-form">
                <div><label for="add-student-number">Student Number:</label><input type="text" id="add-student-number" name="student_number" placeholder="Student Number" required></div>
                <div><label for="add-student-first-name">First Name:</label><input type="text" id="add-student-first-name" name="first_name" placeholder="First Name" required></div>
                <div><label for="add-student-middle-name">Middle Name:</label><input type="text" id="add-student-middle-name" name="middle_name" placeholder="Middle Name"></div>
                <div><label for="add-student-last-name">Last Name:</label><input type="text" id="add-student-last-name" name="last_name" placeholder="Last Name" required></div>
                <div><label for="add-student-email">Email:</label><input type="email" id="add-student-email" name="email" placeholder="Email" required></div>
                <div><label for="add-student-course">Course:</label><select id="add-student-course" name="course_id" required>
                    <option value="">Select Course</option>
                    <?php
                    if (isset($courseResultPHP) && $courseResultPHP && mysqli_num_rows($courseResultPHP) > 0) { mysqli_data_seek($courseResultPHP, 0); while ($row = mysqli_fetch_assoc($courseResultPHP)) { echo "<option value='{$row['course_id']}'>{$row['course_name']}</option>"; }
                    } elseif($conn) { $courseResultModalFallback = mysqli_query($conn, "SELECT course_id, course_name FROM course_tbl ORDER BY course_name ASC"); if ($courseResultModalFallback) { while ($row = mysqli_fetch_assoc($courseResultModalFallback)) { echo "<option value='{$row['course_id']}'>{$row['course_name']}</option>"; } } }
                    ?>
                </select></div>
                <div><label for="add-student-year">Year:</label><select id="add-student-year" name="year_id" required>
                    <option value="">Select Year</option>
                    <?php
                    if (isset($yearResultPHP) && $yearResultPHP && mysqli_num_rows($yearResultPHP) > 0) { mysqli_data_seek($yearResultPHP, 0); while ($row = mysqli_fetch_assoc($yearResultPHP)) { echo "<option value='{$row['year_id']}'>{$row['year']}</option>"; }
                    } elseif($conn) { $yearResultModalFallback = mysqli_query($conn, "SELECT year_id, year FROM year_tbl ORDER BY year ASC"); if ($yearResultModalFallback) { while ($row = mysqli_fetch_assoc($yearResultModalFallback)) { echo "<option value='{$row['year_id']}'>{$row['year']}</option>"; } } }
                    ?>
                </select></div>
                <div><label for="add-student-section">Section:</label><select id="add-student-section" name="section_id" required>
                    <option value="">Select Section</option>
                     <?php
                    if (isset($sectionResultPHP) && $sectionResultPHP && mysqli_num_rows($sectionResultPHP) > 0) { mysqli_data_seek($sectionResultPHP, 0); while ($row = mysqli_fetch_assoc($sectionResultPHP)) { echo "<option value='{$row['section_id']}'>{$row['section_name']}</option>"; }
                    } elseif($conn) { $sectionResultModalFallback = mysqli_query($conn, "SELECT section_id, section_name FROM section_tbl ORDER BY section_name ASC"); if ($sectionResultModalFallback) { while ($row = mysqli_fetch_assoc($sectionResultModalFallback)) { echo "<option value='{$row['section_id']}'>{$row['section_name']}</option>"; } } }
                    ?>
                </select></div>
                <div><label for="add-student-status">Status:</label><select id="add-student-status" name="status_id" required>
                    <option value="">Select Status</option>
                    <?php
                    if (isset($statusResultPHP) && $statusResultPHP && mysqli_num_rows($statusResultPHP) > 0) { mysqli_data_seek($statusResultPHP, 0); while ($row = mysqli_fetch_assoc($statusResultPHP)) { echo "<option value='{$row['status_id']}'>{$row['status_name']}</option>"; }
                    } elseif($conn) { $statusResultModalFallback = mysqli_query($conn, "SELECT status_id, status_name FROM status_tbl ORDER BY status_name ASC"); if ($statusResultModalFallback) { while ($row = mysqli_fetch_assoc($statusResultModalFallback)) { echo "<option value='{$row['status_id']}'>{$row['status_name']}</option>"; } } }
                    ?>
                </select></div>
                <button type="submit">Add Student</button>
            </form>
        </div>
    </div>

    <div id="add-admin-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal close-add-admin">&times;</span>
            <h3>Add New Admin</h3>
            <form id="add-admin-form">
                <div><label for="add-admin-first-name">First Name:</label><input type="text" id="add-admin-first-name" name="first_name" placeholder="First Name" required></div>
                <div><label for="add-admin-middle-name">Middle Name:</label><input type="text" id="add-admin-middle-name" name="middle_name" placeholder="Middle Name"></div>
                <div><label for="add-admin-last-name">Last Name:</label><input type="text" id="add-admin-last-name" name="last_name" placeholder="Last Name" required></div>
                <div><label for="add-admin-position">Position:</label><input type="text" id="add-admin-position" name="position" placeholder="Position" required></div>
                <div><label for="add-admin-email">Email (will be used as Username):</label><input type="email" id="add-admin-email" name="email" placeholder="Email" required></div>
                <button type="submit">Add Admin</button>
            </form>
        </div>
    </div>

    <div id="edit-admin-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal close-edit-admin">&times;</span>
            <h3>Edit Admin</h3>
            <form id="edit-admin-form">
                <input type="hidden" id="edit-admin-id" name="admin_id">
                <div><label for="edit-admin-first-name">First Name:</label><input type="text" id="edit-admin-first-name" name="first_name" placeholder="First Name" required></div>
                <div><label for="edit-admin-middle-name">Middle Name:</label><input type="text" id="edit-admin-middle-name" name="middle_name" placeholder="Middle Name"></div>
                <div><label for="edit-admin-last-name">Last Name:</label><input type="text" id="edit-admin-last-name" name="last_name" placeholder="Last Name" required></div>
                <div><label for="edit-admin-position">Position:</label><input type="text" id="edit-admin-position" name="position" placeholder="Position" required></div>
                <div><label for="edit-admin-email">Email:</label><input type="email" id="edit-admin-email" name="email" placeholder="Email" required></div>
                <div><label for="edit-admin-password">New Password:</label><input type="password" id="edit-admin-password" name="password" placeholder="Leave blank to keep current password"></div>
                <div><label for="edit-admin-status">Status:</label><select id="edit-admin-status" name="status_id" required>
                    <option value="">Select Status</option>
                    <?php
                    if ($conn) {
                        $statusQueryModalAdmin = "SELECT status_id, status_name FROM status_tbl ORDER BY status_name ASC";
                        $statusResultModalAdmin = mysqli_query($conn, $statusQueryModalAdmin);
                        if ($statusResultModalAdmin) {
                            while ($row = mysqli_fetch_assoc($statusResultModalAdmin)) {
                                echo "<option value='{$row['status_id']}'>{$row['status_name']}</option>";
                            }
                        }
                    }
                    ?>
                </select></div>
                <button type="submit">Update Admin</button>
            </form>
        </div>
    </div>

    <div id="delete-confirm-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal close-delete-confirm">&times;</span>
            <h3><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h3>
            <p id="delete-confirm-text">Are you sure you want to delete <span id="delete-item-type-placeholder">this item</span>: <strong id="delete-item-identifier-placeholder"></strong>?</p>
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