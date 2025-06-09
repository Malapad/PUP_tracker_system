<?php
session_start();
include '../PHP/dbcon.php';

$adminPageData = [
    'isLoggedIn' => false,
    'firstName' => 'N/A',
    'middleName' => 'N/A',
    'lastName' => 'N/A',
    'position' => 'N/A',
    'email' => 'N/A',
    'errorMessage' => null
];

if (isset($_SESSION['admin_id'])) {
    $adminPageData['isLoggedIn'] = true;
    $admin_id = $_SESSION['admin_id'];

    if ($conn) {
        $query = "SELECT ai.firstname, ai.middlename, ai.lastname, ai.Position, a.email
                  FROM admin_info_tbl ai
                  JOIN admins a ON ai.admin_id = a.id
                  WHERE ai.admin_id = ?";

        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $admin_id);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $adminDetails = $result->fetch_assoc();
                    $adminPageData['firstName'] = htmlspecialchars($adminDetails['firstname']);
                    $adminPageData['middleName'] = $adminDetails['middlename'] ? htmlspecialchars($adminDetails['middlename']) : '';
                    $adminPageData['lastName'] = htmlspecialchars($adminDetails['lastname']);
                    $adminPageData['position'] = htmlspecialchars($adminDetails['Position']);
                    $adminPageData['email'] = htmlspecialchars($adminDetails['email']);
                } else {
                    $adminPageData['errorMessage'] = 'Admin details not found in the database.';
                }
            } else {
                $adminPageData['errorMessage'] = 'Failed to execute database query: ' . htmlspecialchars($stmt->error);
            }
            $stmt->close();
        } else {
            $adminPageData['errorMessage'] = 'Failed to prepare database query: ' . htmlspecialchars($conn->error);
        }
        $conn->close();
    } else {
        $adminPageData['errorMessage'] = 'Database connection could not be established.';
    }
} else {
    $adminPageData['errorMessage'] = 'Not authorized or session has expired. Please log in.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Account Information</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="../CSS/admin_account.css">
    <link rel="stylesheet" href="../CSS/header.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>

    <header>
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top py-0">
            <div class="container-fluid px-4 px-md-5">
                <a class="navbar-brand py-0" href="../HTML/admin_homepage.html">
                    <img src="../IMAGE/Tracker-logo.png" alt="PUP Logo" class="img-fluid" style="height: 60px; width: 180px;">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link" href="../HTML/admin_homepage.html">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../updated-admin-violation/admin-violationpage.php">Violations</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">Student Sanction</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../user-management/user_management.php">User Management</a>
                        </li>
                    </ul>
                    <div class="d-flex align-items-center">
                        <a href="notification.html" class="me-3">
                            <img src="https://img.icons8.com/?size=100&id=83193&format=png&color=000000" alt="Notifications" style="width: 35px; height: 35px;"/>
                        </a>
                        <a href="admin_account.html">
                            <img src="https://img.icons8.com/?size=100&id=77883&format=png&color=000000" alt="Admin Account" style="width: 35px; height: 35px;"/>
                        </a>
                    </div>
                </div>
            </div>
        </nav>
    </header>


    <div class="container">
        <h1>Account Information</h1>
        <div class="info-box">
            <div class="info-row">
                <span class="info-label">
                    <i class="fas fa-user icon-style"></i>
                    <strong>First Name:</strong>
                </span>
                <span class="info-value" id="adminFirstName"></span>
            </div>
            <div class="info-row">
                <span class="info-label">
                    <i class="fas fa-user icon-style"></i>
                    <strong>Middle Name:</strong>
                </span>
                <span class="info-value" id="adminMiddleName"></span>
            </div>
            <div class="info-row">
                <span class="info-label">
                    <i class="fas fa-user icon-style"></i>
                    <strong>Last Name:</strong>
                </span>
                <span class="info-value" id="adminLastName"></span>
            </div>
            <div class="info-row">
                <span class="info-label">
                    <i class="fas fa-briefcase icon-style"></i>
                    <strong>Position:</strong>
                </span>
                <span class="info-value" id="adminPosition"></span>
            </div>
            <div class="info-row">
                <span class="info-label">
                    <i class="fas fa-envelope icon-style"></i>
                    <strong>Email:</strong>
                </span>
                <span class="info-value" id="adminEmail"></span>
            </div>
        </div>
        <button id="signOutBtn">Sign Out</button>
        <div id="errorMessageDisplay" style="color: red; margin-top: 15px;"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        const adminData = <?php echo json_encode($adminPageData); ?>;

        document.addEventListener("DOMContentLoaded", function() {
            const adminFirstNameElement = document.getElementById("adminFirstName");
            const adminMiddleNameElement = document.getElementById("adminMiddleName");
            const adminLastNameElement = document.getElementById("adminLastName");
            const adminPositionElement = document.getElementById("adminPosition");
            const adminEmailElement = document.getElementById("adminEmail");
            const signOutBtn = document.getElementById("signOutBtn");
            const errorMessageDisplayElement = document.getElementById("errorMessageDisplay");

            if (adminData.isLoggedIn && !adminData.errorMessage) {
                adminFirstNameElement.textContent = adminData.firstName;
                adminMiddleNameElement.textContent = adminData.middleName || '';
                adminLastNameElement.textContent = adminData.lastName;
                adminPositionElement.textContent = adminData.position;
                adminEmailElement.textContent = adminData.email;
            } else {
                adminFirstNameElement.textContent = 'N/A';
                adminMiddleNameElement.textContent = 'N/A';
                adminLastNameElement.textContent = 'N/A';
                adminPositionElement.textContent = 'N/A';
                adminEmailElement.textContent = 'N/A';
                if (errorMessageDisplayElement && adminData.errorMessage) {
                    errorMessageDisplayElement.textContent = "Error: " + adminData.errorMessage;
                }
                if (adminData.errorMessage && (adminData.errorMessage.includes("Not authorized") || adminData.errorMessage.includes("session has expired"))) {
                    setTimeout(() => {
                        window.location.href = './login.html';
                    }, 3000);
                }
            }

            if (signOutBtn) {
                signOutBtn.addEventListener("click", function() {
                    window.location.href = '../PHP/logout.php?role=admins';
                });
            }
        });
    </script>
</body>
</html>