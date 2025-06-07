<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Header</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../header.css"> </head>
<body>
<header class="navbar navbar-expand-lg custom-navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="../IMAGE/Tracker_logo.png" alt="Logo" width="40" height="40" class="d-inline-block align-text-top">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="../HTML/admin_homepage.html" data-tab="home"><strong>Home</strong></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" data-tab="violations"><strong>Violations</strong></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../updated-admin-sanction/admin_sanction.php" data-tab="student-sanction"><strong>Student Sanction</strong></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../user-management/user_management.php" data-tab="user-management"><strong>User Management</strong></a>
                    </li>
                </ul>
                <ul class="navbar-nav icon-nav">
                    <li class="nav-item">
                        <a class="nav-link icon-link" href="#">
                            <img src="https://img.icons8.com/?size=100&id=83193&format=png&color=000000" alt="Notification">
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link icon-link" href="#">
                            <img src="https://img.icons8.com/?size=100&id=77883&format=png&color=000000" alt="Account">
                        </a>
                    </li>
                </ul>
            </div>
        </div>
</header>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../header.js"></script> </body>
</html>