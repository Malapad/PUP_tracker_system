<?php
require_once "../PHP/dbcon.php";
session_start();

if (!isset($_SESSION["current_user_id"])) { 
    header("Location: ./student_login.php");
    exit();
}

$session_user_id_for_account = $_SESSION["current_user_id"];
$student_info = null;

$sql_student = "SELECT u.first_name, u.middle_name, u.last_name, u.student_number, u.email,
                        c.course_name,
                        s.section_name,
                        y.year AS year_level,
                        g.gender_name
                FROM users_tbl u
                LEFT JOIN course_tbl c ON u.course_id = c.course_id
                LEFT JOIN section_tbl s ON u.section_id = s.section_id
                LEFT JOIN year_tbl y ON u.year_id = y.year_id
                LEFT JOIN gender_tbl g ON u.gender_id = g.gender_id
                WHERE u.user_id = ?";

if ($stmt_student = $conn->prepare($sql_student)) {
    $stmt_student->bind_param("i", $session_user_id_for_account);
    $stmt_student->execute();
    $result_student = $stmt_student->get_result();
    if ($result_student->num_rows == 1) {
        $student_info = $result_student->fetch_assoc();
    }
    $stmt_student->close();
} else {
    // error_log("Error preparing student information query: " . $conn->error); 
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Account Information</title>
    <link rel="stylesheet" href="./student_account.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <header>
        <div class="logo">
            <img src="../assets/PUPlogo.png" alt="PUP Logo">
        </div>
        <nav>
            <a href="student_dashboard.php">Home</a>
            <a href="student_record.php">Record</a>
        </nav>
        <div class="admin-icons"> 
            <a href="notification.html" class="notification">
                <img src="https://img.icons8.com/?size=100&id=83193&format=png&color=000000" alt="Notifications"/></a>
            <a href="student_account.php" class="admin"> 
                <img src="https://img.icons8.com/?size=100&id=77883&format=png&color=000000" alt="Account"/></a>
        </div>
    </header>

    <div class="container">
        <h1>Account Information</h1>
        <?php if ($student_info): ?>
        <div class="info-box">
            <div class="info-row">
                <span class="info-label">
                    <i class="fas fa-user icon-style"></i>
                    <strong>First Name:</strong>
                </span>
                <span class="info-value">
                    <?php echo htmlspecialchars($student_info['first_name'] ?? 'N/A'); ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">
                    <i class="fas fa-user icon-style"></i>
                    <strong>Middle Name:</strong>
                </span>
                <span class="info-value">
                    <?php echo htmlspecialchars($student_info['middle_name'] ?? 'N/A'); ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">
                    <i class="fas fa-user icon-style"></i>
                    <strong>Last Name:</strong>
                </span>
                <span class="info-value">
                    <?php echo htmlspecialchars($student_info['last_name'] ?? 'N/A'); ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">
                    <i class="fas fa-venus-mars icon-style"></i>
                    <strong>Gender:</strong>
                </span>
                <span class="info-value">
                    <?php echo htmlspecialchars($student_info['gender_name'] ?? 'N/A'); ?>
                </span>
            </div>

            <div class="info-row">
                <span class="info-label">
                    <i class="fas fa-id-card icon-style"></i>
                    <strong>Student Number:</strong>
                </span>
                <span class="info-value">
                    <?php echo htmlspecialchars($student_info['student_number'] ?? 'N/A'); ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">
                    <i class="fas fa-envelope icon-style"></i>
                    <strong>Email:</strong>
                </span>
                <span class="info-value">
                    <?php echo htmlspecialchars($student_info['email'] ?? 'N/A'); ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">
                    <i class="fas fa-graduation-cap icon-style"></i>
                    <strong>Course:</strong>
                </span>
                <span class="info-value">
                    <?php echo htmlspecialchars($student_info['course_name'] ?? 'N/A'); ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">
                    <i class="fas fa-layer-group icon-style"></i>
                    <strong>Year Level:</strong>
                </span>
                <span class="info-value">
                    <?php echo htmlspecialchars($student_info['year_level'] ?? 'N/A'); ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">
                    <i class="fas fa-tag icon-style"></i>
                    <strong>Section:</strong>
                </span>
                <span class="info-value">
                    <?php echo htmlspecialchars($student_info['section_name'] ?? 'N/A'); ?>
                </span>
            </div>
        </div>
        <?php else: ?>
            <p class="error-message">Could not retrieve student information. Please ensure you are logged in correctly or contact support.</p>
        <?php endif; ?>
        <a href="../PHP/logout.php?role=student" id="signOutBtn" class="sign-out-button">Sign Out</a>
    </div>
    <script>
        const signOutButton = document.getElementById("signOutBtn");
        if (signOutButton) {
            signOutButton.addEventListener("click", function(event) {
                event.preventDefault(); 
                window.location.href = this.href;
            });
        }
    </script>
</body>
</html>