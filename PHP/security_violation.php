<?php
session_start();
require '../dbcon.php';

if (!isset($_SESSION['security_id'])) {
    header("Location: security_login.php");
    exit();
}

// Query to fetch student violators
$sql = "SELECT sv.stud_violation_id, s.Stud_number, 
               CONCAT(s.Firstname, ' ', s.Middlename, ' ', s.Lastname) AS StudentName,
               c.course_name AS Program, 
               v.violation_name AS Violation, 
               sv.date
        FROM student_violation_tbl sv
        JOIN student_info_tbl s ON sv.stud_id = s.student_id
        JOIN course_tbl c ON s.course_id = c.course_id
        JOIN violation_type_tbl v ON sv.violation_id = v.violation_id
        ORDER BY sv.date DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Violations</title>
    <link rel="stylesheet" href="../CSS/security_style.css">
</head>
<body>
    <h2>Student List Violators</h2>
    
    <table>
        <thead>
            <tr>
                <th>Student Number</th>
                <th>Student Name</th>
                <th>Program</th>
                <th>Violation</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Stud_number']); ?></td>
                        <td><?= htmlspecialchars($row['StudentName']); ?></td>
                        <td><?= htmlspecialchars($row['Program']); ?></td>
                        <td><?= htmlspecialchars($row['Violation']); ?></td>
                        <td><?= htmlspecialchars($row['date']); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">No violations found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
