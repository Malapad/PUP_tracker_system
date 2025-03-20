    <?php
    require_once "../PHP/dbcon.php";
    session_start();

    $errors = []; 
    $studentNumber = $password = ""; 

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $studentNumber = trim($_POST['student_number']);
        $password = $_POST['password'];

        if (empty($studentNumber)) {
            $errors['student_number'] = "Student number is required!";
        }

        if (empty($password)) {
            $errors['password'] = "Password is required!";
        }

        if (empty($errors)) {
            if (!$conn) {
                die("Database connection failed: " . mysqli_connect_error());
            }

            $sql = "SELECT student_id, Firstname, Password FROM student_info_tbl WHERE Stud_number = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("s", $studentNumber);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows == 1) {
                    $row = $result->fetch_assoc();

                    if (password_verify($password, $row["Password"])) {
                        $_SESSION["student_id"] = $row["student_id"];
                        $_SESSION["student_name"] = $row["Firstname"];
                        header("Location: ../HTML/student_dashboard.html");
                        exit();
                    } else {
                        $errors['password'] = "Incorrect password! Please try again.";
                    }
                } else {
                    $errors['student_number'] = "Student number not found! Please sign up.";
                }

                $stmt->close();
            }
            $conn->close();
        }
    }
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>PUP Student Login</title>
        <link rel="stylesheet" href="../CSS/student_login_style.css">
    </head>
    <body>

        <div class="container">
            <div class="left-panel">
                <h2>Welcome PUPTians!</h2>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
            </div>

            <div class="right-panel">
                <img src="../assets/PUP_logo.png" alt="PUP Logo" class="logo">
                <h3>Student Account</h3>
                
                <form action="" method="POST">
                    <div class="input-group">
                        <input type="text" name="student_number" placeholder="Student Number" value="<?= htmlspecialchars($studentNumber) ?>" required>
                        <span class="error"><?= $errors['student_number'] ?? '' ?></span>
                    </div>

                    <div class="input-group">
                        <input type="password" name="password" placeholder="Password" required>
                        <span class="error"><?= $errors['password'] ?? '' ?></span>
                    </div>

                    <p class="signup-text">Don't have an account? <a href="../PHP/student_signup.php">Sign up here</a></p>
                    
                    <button type="submit" class="login-btn">Sign In</button>
                </form>
            </div>
        </div>

    </body>
    </html>
