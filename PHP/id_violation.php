<?php
// dbcon.php should contain your database connection settings.
include 'dbcon.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve data from the AJAX request
    $studentNumber = mysqli_real_escape_string($conn, $_POST['studentNumber']);
    $lastName = mysqli_real_escape_string($conn, $_POST['lastName']);
    $firstName = mysqli_real_escape_string($conn, $_POST['firstName']);
    $middleName = mysqli_real_escape_string($conn, $_POST['middleName']);
    $program = mysqli_real_escape_string($conn, $_POST['program']);
    $yearLevel = mysqli_real_escape_string($conn, $_POST['yearLevel']);
    $violationType = mysqli_real_escape_string($conn, $_POST['violationType']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);

    // Insert data into the database
    $sql = "INSERT INTO id_violation (student_number, last_name, first_name, middle_name, program, year_level, violation_type, date)
            VALUES ('$studentNumber', '$lastName', '$firstName', '$middleName', '$program', '$yearLevel', '$violationType', '$date')";

    if (mysqli_query($conn, $sql)) {
        echo "Data saved successfully!";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }

    // Close connection
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dynamic Row Management</title>
    <link rel="stylesheet" href="/CSS/id_violation.css">
</head>
<body>
         <!-- Navbar -->
         <header>
        <div class="logo">
            <img src="../assets/PUPlogo.png" alt="PUP Logo">
        </div>
        <nav>
            <a href="../HTML/admin_homepage.html">Home</a>
            <a href="../HTML/admin_dashboard_violation.html">Violations</a>
            <a href="../HTML/admin_sanction.html">Student Sanction</a>
            <a href="../user-management/user_management.php">User Management</a>
        </nav>
        <div class="admin-icons">
            <a href="notification.html" class="notification">
                <img src="https://img.icons8.com/?size=100&id=83193&format=png&color=000000"/></a>
            <a href="admin_account.html" class="admin">
               <img src="https://img.icons8.com/?size=100&id=77883&format=png&color=000000"/></a>
        </div>
    </header>

    <!-- Search Bar -->
    <div class="search-bar">
        <input type="text" placeholder="Search">
    </div>

    
    <div class="container">

       <h1>Violation Record</h1>
          <button id="addStudentBtn">Add Row</button>
    <table>
        <tr>
            <th>Student Number</th>
            <th>Last Name</th>
            <th>First Name</th>
            <th>Middle Name</th>
            <th>Program</th>
            <th>Year Level</th>
            <th>Violation Type</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
    </table>

    </div>
    <script src="/JS/id_violation.js"></script>
</body>
</html>
