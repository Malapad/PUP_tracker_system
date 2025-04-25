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
            <di class="content">
        <!-- Search Bar -->
        <div class="search-wrapper">
            <input class="search-wrapper" type="text" placeholder="Search">
        </div>
        <div>
        <div class="container">
    <header>
      <h1>Code of Discipline</h1>
      <p>Summary of University Violations</p>
    </header>

    <div class="search-wrapper">
      <input type="text" id="searchInput" placeholder="Search violation...">
    </div>

    <ul id="violationList">
      <?php
      $search = isset($_GET['search']) ? trim($_GET['search']) : '';
      $query = "SELECT * FROM violations";

      if (!empty($search)) {
          $query .= " WHERE title = ?";
          $stmt = $conn->prepare($query);
          $stmt->bind_param("s", $search);
      } else {
          $stmt = $conn->prepare($query);
      }

      if ($stmt) {
          $stmt->execute();
          $result = $stmt->get_result();

          while ($row = $result->fetch_assoc()) {
              echo "<li><a href='view.php?id={$row['id']}' class='violation-link'>{$row['id']}. {$row['title']}</a></li>";
          }
      } else {
          echo "<li>Query error: " . $conn->error . "</li>";
      }
      ?>
    </ul>
  </div>
        </div>
        <ul id="violationList">
      <?php
      $search = isset($_GET['search']) ? trim($_GET['search']) : '';
      $query = "SELECT * FROM violations";

      if (!empty($search)) {
          $query .= " WHERE title = ?";
          $stmt = $conn->prepare($query);
          $stmt->bind_param("s", $search);
      } else {
          $stmt = $conn->prepare($query);
      }

      if ($stmt) {
          $stmt->execute();
          $result = $stmt->get_result();

          while ($row = $result->fetch_assoc()) {
              echo "<li><a href='view.php?id={$row['id']}' class='violation-link'>{$row['id']}. {$row['title']}</a></li>";
          }
      } else {
          echo "<li>Query error: " . $conn->error . "</li>";
      }
      ?>
    </ul>
  </div>
    <!--        <div  class="handbook">
                <embed src="../assets/PUP-Student-Handbook.pdf" type="application/pdf" 
                width="300px" height="400" />
            </div> -->
            </div>
        </div>
    </div>

</main>
       <script src="../search violations/script.js"></script>
</body>
</html>
