<?php
include '../PHP/dbcon.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="../CSS/student_dashboard_style.css">
    <link rel="stylesheet" href="../search violations/styles.css">

</head>
<body>
         <!-- Navbar -->
     <header>
        <div class="logo">
            <img src="../assets/PUPlogo.png" alt="PUP Logo">
        </div>
        <nav>
            <a href="../HTML/student_dashboard.html">Home</a>
            <a href="../HTML/student_record.html">Record</a>
        </nav>
        <div class="admin-icons">
            <a href="notification.html" class="notification">
                <img src="https://img.icons8.com/?size=100&id=83193&format=png&color=000000"/></a>
            <a href="../HTML/student_account.html" class="admin">
               <img src="https://img.icons8.com/?size=100&id=77883&format=png&color=000000"/></a>
        </div>
    </header> 

<main>
    <div class="container">
        <!-- Violation Notice Section -->
        <div class="box">
            <div class="header">Violation Notice!</div>
            <div class="content" id="violation-container">
        <!-- New "Clear All Violations" Button -->
            <button id="clear-violations-btn">Clear All Violations</button>
                <p id="no-violations">No new violations.</p>
                <ul id="violation-list"></ul>
            </div>
        </div>
<!-- Student Handbook Section -->
<div class="box">
  <div class="header">Student Handbook</div>
  <div class="content">
    <!-- Search Bar -->
   <form id="handbookSearchForm">
     <div class="search-wrapper">
        <input type="text" id="handbookSearchInput" placeholder="Search violation..." />
        <button type="submit">Search</button>
      </div>
    </form>

    <ul id="handbookViolationList">
      <?php
        include '../PHP/dbcon.php';
        $query = "SELECT * FROM violations";
        $result = $conn->query($query);

        while ($row = $result->fetch_assoc()) {
            echo "<li class='violation-item'><a href='#' class='violation-link' data-id='{$row['id']}'>{$row['id']}. {$row['title']}</a></li>";
        }
      ?>
    </ul>
  </div>
</div>


</main>
       <script src="../search violations/script.js"></script>
</body>
</html>
