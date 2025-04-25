<?php
include 'dbcon.php';

$course_query = "
    SELECT c.course_name, COUNT(*) AS total
    FROM users_tbl AS s
    JOIN course_tbl c ON s.course_id = c.course_id
    GROUP BY c.course_name
    ORDER BY total DESC
";
$course_result = $conn->query($course_query);

$courses = [];
$course_totals = [];

while ($row = $course_result->fetch_assoc()) {
    $courses[] = $row['course_name'];
    $course_totals[] = $row['total'];
}

$response = [
    'courses' => [
        'labels' => $courses,
        'data' => $course_totals
    ],
];

echo json_encode($response);

$conn->close();
?>

