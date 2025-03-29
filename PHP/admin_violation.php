<?php
include 'dbcon.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Violation List</title>
    <link rel="stylesheet" href="../CSS/admin_violation_style.css">
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
            <a href="../HTML/user_management.html">User Management</a>
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
 <!-- Student Violations Table -->
<!--   
    <div class="container">
        <h1>Student List Violators</h1>
        <table>
            <thead>
                <tr>
                    <th>Student Number</th>
                    <th>Student Name</th>
                    <th>Program</th>
                    <th>Violations</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>2022-00191-TG-0</td>
                    <td>Andrea Mailgaya Donatos</td>
                    <td>DIT</td>
                    <td>No ID</td>
                    <td>02/24/25</td>
                </tr>
                <tr>
                    <td>2022-00191-TG-0</td>
                    <td>Andrea Mailgaya Donatos</td>
                    <td>DIT</td>
                    <td>Not wearing ID</td>
                    <td>02/24/25</td>
                </tr>
                <tr>
                    <td>2022-00191-TG-0</td>
                    <td>Andrea Mailgaya Donatos</td>
                    <td>DIT</td>
                    <td>No ID</td>
                    <td>02/24/25</td>
                </tr>
            </tbody>
        </table>


        <div class="buttons">
            <button class="add">Add</button>
            <button class="edit">Edit</button>
            <button class="delete">Delete</button>
        </div>
    </div> -->
        <!-- Buttons -->

        <div class="container">
            <h1>Student Violation Information</h1>
            <button id="addStudentBtn">Add Student</button>
            
            <table>
                <thead>
                    <tr>
                        <th>Student Number</th>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>Middle Name</th>
                        <th>Program</th>
                        <th>Year & Section</th>
                        <th>Violation</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="studentTableBody">
                    <tr><td colspan="9">No data available</td></tr>
                </tbody>
            </table>
        </div>
    
        <!-- Modal for Adding Student -->
        <div id="modal" class="modal">
            <div class="modal-content">
                <h3>Add Student</h3>
                <form id="studentForm">
                    <label>Student Number:</label>
                    <input type="text" id="studentNumber" required>
    
                    <label>Last Name:</label>
                    <input type="text" id="lastName" required>
    
                    <label>First Name:</label>
                    <input type="text" id="firstName" required>
    
                    <label>Middle Name:</label>
                    <input type="text" id="middleName">
    
                    <label>Program:</label>
                    <select id="program">
                        <option value="DIT">DIT</option>
                        <option value="BSIT">BSIT</option>
                        <option value="DOMT">DOMT</option>
                        <option value="BSOA">BSOA</option>
                    </select>
    
                    <label>Year & Section:</label>
                    <select id="yearSection">
                        <option value="1st Year">1st Year</option>
                        <option value="2nd Year">2nd Year</option>
                        <option value="3rd Year">3rd Year</option>
                        <option value="4th Year">4th Year</option>
                    </select>
    
                    <label>Violation:</label>
                    <input type="text" id="violation">
    
                    <label>Date:</label>
                    <input type="date" id="date">
    
                    <button type="submit">Save</button>
                    <button type="button" id="closeModal">Cancel</button>
                </form>
            </div>
        </div>
    
    
    <script src="/JS/admin_violation.js" defer></script>
    
    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $studentNumber = mysqli_real_escape_string($conn, $_POST['studentNumber']);
    $lastName = mysqli_real_escape_string($conn, $_POST['lastName']);
    $firstName = mysqli_real_escape_string($conn, $_POST['firstName']);
    $middleName = mysqli_real_escape_string($conn, $_POST['middleName']);
    $program = mysqli_real_escape_string($conn, $_POST['program']);
    $yearSection = mysqli_real_escape_string($conn, $_POST['yearSection']);
    $violation = mysqli_real_escape_string($conn, $_POST['violation']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);

    $sql = "INSERT INTO students (student_number, last_name, first_name, middle_name, program, year_section, violation, date) 
            VALUES ('$studentNumber', '$lastName', '$firstName', '$middleName', '$program', '$yearSection', '$violation', '$date')";

    if (mysqli_query($conn, $sql)) {
        echo "Student record added successfully!";
    } else {
        echo "Error: " . mysqli_error($conn);
    }

    mysqli_close($conn);
}
?>

</body>
</html>