<?php
require_once "../PHP/dbcon.php";
session_start();

// Debug: Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $studentNumber = trim($_POST['student_number']);
    $password = $_POST['password'];

    // Check if fields are filled
    if (empty($studentNumber) || empty($password)) {
        die("<script>alert('All fields are required!'); window.history.back();</script>");
    }

    if (!$conn) {
        die("Database connection failed: " . mysqli_connect_error());
    }

    // Query to check student number in the database
    $sql = "SELECT student_id, Firstname, Password FROM student_info_tbl WHERE Stud_number = ?";

    // Debug: Check if statement prepared successfully
    if (!$stmt = $conn->prepare($sql)) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $studentNumber);

    // Debug: Check execution
    if (!$stmt->execute()) {
        die("Execution failed: " . $stmt->error);
    }

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();

        // Debug: Show fetched password for verification
        error_log("Stored Hashed Password: " . $row["Password"]);
        error_log("Entered Password: " . $password);

        // Verify hashed password
        if (password_verify($password, $row["Password"])) {
            $_SESSION["student_id"] = $row["student_id"];
            $_SESSION["student_name"] = $row["Firstname"];

            echo "<script>alert('Login Successful!'); window.location.href='../HTML/student_dashboard.html';</script>";
            exit();
        } else {
            die("<script>alert('Incorrect password! Please try again.'); window.history.back();</script>");
        }
    } else {
        die("<script>alert('Student number not found! Please sign up.'); window.location.href='../HTML/student_signup.html';</script>");
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login</title>
    <link rel="stylesheet" href="../CSS/student_login_style.css">
</head>
<body>

    <div class="login-container">
        <div class="welcome-section">
            <h1>Welcome PUPTians!</h1>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
        </div>
        
        <div class="login-section">
            <img src="../assets/PUP_logo.png" alt="PUP Logo" class="logo">
            <h2>Student Account</h2>
            
            <form action="" method="POST">
                <input type="text" name="student_number" placeholder="Student Number" required>
                <input type="password" name="password" placeholder="Password" required>
                
                <p>Don't have an account? <a href="../PHP/student_signup.php">Sign up here</a></p>
                
                <button type="submit" class="login-btn">Sign In</button>
            </form>
        </div>
    </div>

</body>
</html>