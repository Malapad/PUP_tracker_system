<?php
// dbcon.php should contain your database connection settings.
include 'dbcon.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve data from the AJAX request
    $studentNumber = mysqli_real_escape_string($conn, $_POST['studentNumber']);
    $lastName = mysqli_real_escape_string($conn, $_POST['lastName']);
    $firstName = mysqli_real_escape_string($conn, $_POST['firstName']);
    $middleName = mysqli_real_escape_string($conn, $_POST['middleName']);
    $program = mysqli_real_escape_string($conn, $_POST['course']);
    $yearLevel = mysqli_real_escape_string($conn, $_POST['year']);
    $violationType = mysqli_real_escape_string($conn, $_POST['violationType']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);

    // Insert data into the database
    $sql = "INSERT INTO violation_tbl (student_number, last_name, first_name, middle_name, course, year, violation_type, date)
            VALUES ('$studentNumber', '$lastName', '$firstName', '$middleName', '$course', '$year', '$violationType', '$date')";

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
    <title>Violation Record</title>
    <link rel="stylesheet" href="/CSS/late_regicard.css">
</head>
<body>
    <h2>Violation Record</h2>
    <button>Add Row</button>
    <table>
        <tr>
            <th>Student Number</th>
            <th>Last Name</th>
            <th>First Name</th>
            <th>Middle Name</th>
            <th>Course</th>
            <th>Year</th>
            <th>Violation Type</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
    </table>
    <script src="/JS/late_regicard.js"></script>
</body>
</html>