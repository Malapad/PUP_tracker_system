<?php
include '../PHP/dbcon.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="add_student_page.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <title>Add New Student</title>
</head>
<body>
    <div class="form-container">
        <h2>Add New Student</h2>
        <form id="add-student-form" action="./add_student.php" method="POST">
            <div>
                <label for="student-number">Student Number:</label>
                <input type="text" id="student-number" name="student_number" placeholder="Student Number" required>
            </div>
            <div>
                <label for="first-name">First Name:</label>
                <input type="text" id="first-name" name="first_name" placeholder="First Name" required>
            </div>
            <div>
                <label for="middle-name">Middle Name:</label>
                <input type="text" id="middle-name" name="middle_name" placeholder="Middle Name">
            </div>
            <div>
                <label for="last-name">Last Name:</label>
                <input type="text" id="last-name" name="last_name" placeholder="Last Name" required>
            </div>
            <div>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" placeholder="Email" required>
            </div>
            <div>
                <label for="course">Course:</label>
                <select id="course" name="course" required>
                    <option value="">Select Course</option>
                    <?php
                        $courseQueryPage = "SELECT course_id, course_name FROM course_tbl ORDER BY course_name ASC";
                        $courseResultPage = mysqli_query($conn, $courseQueryPage);
                        if ($courseResultPage) {
                            while ($rowPage = mysqli_fetch_assoc($courseResultPage)) {
                                echo "<option value='{$rowPage['course_id']}'>{$rowPage['course_name']}</option>";
                            }
                        }
                    ?>
                </select>
            </div>
            <div>
                <label for="year">Year:</label>
                <select id="year" name="year" required>
                    <option value="">Select Year</option>
                     <?php
                        $yearQueryPage = "SELECT year_id, year FROM year_tbl ORDER BY year ASC";
                        $yearResultPage = mysqli_query($conn, $yearQueryPage);
                        if ($yearResultPage) {
                            while ($rowPage = mysqli_fetch_assoc($yearResultPage)) {
                                echo "<option value='{$rowPage['year_id']}'>{$rowPage['year']}</option>";
                            }
                        }
                    ?>
                </select>
            </div>
            <div>
                <label for="section">Section:</label>
                <select id="section" name="section" required>
                    <option value="">Select Section</option>
                    <?php
                        $sectionQueryPage = "SELECT section_id, section_name FROM section_tbl ORDER BY section_name ASC";
                        $sectionResultPage = mysqli_query($conn, $sectionQueryPage);
                        if ($sectionResultPage) {
                            while ($rowPage = mysqli_fetch_assoc($sectionResultPage)) {
                                echo "<option value='{$rowPage['section_id']}'>{$rowPage['section_name']}</option>";
                            }
                        }
                    ?>
                </select>
            </div>
            <div>
                <label for="status">Status:</label>
                <select id="status" name="status" required>
                    <option value="">Select Status</option>
                    <?php
                        $statusQueryPage = "SELECT status_id, status_name FROM status_tbl ORDER BY status_name ASC";
                        $statusResultPage = mysqli_query($conn, $statusQueryPage);
                        if ($statusResultPage) {
                            while ($rowPage = mysqli_fetch_assoc($statusResultPage)) {
                                echo "<option value='{$rowPage['status_id']}'>{$rowPage['status_name']}</option>";
                            }
                        }
                    ?>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" id="submit-student-btn">Add Student</button>
                <a href="./user_management.php" class="back-link">Back to User Management</a>
            </div>
        </form>
    </div>
</body>
</html>