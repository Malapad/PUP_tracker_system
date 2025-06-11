<?php
date_default_timezone_set('Asia/Manila');

function getGreeting() {
    $hour = date('H');
    if ($hour < 12) return "Good Morning!";
    if ($hour < 18) return "Good Afternoon!";
    return "Good Evening!";
}

if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $response = ['data' => null, 'error' => null];
    
    @require '../PHP/dbcon.php'; 
    if (!isset($conn) || $conn->connect_error) {
        $response['error'] = "Database connection failed.";
        echo json_encode($response);
        exit();
    }

    try {
        if ($_GET['action'] == 'get_courses') {
            $sql = "SELECT course_id, course_name FROM course_tbl ORDER BY course_name";
            $result = $conn->query($sql);
            if (!$result) throw new Exception("Course query failed: " . $conn->error);
            $response['data'] = $result->fetch_all(MYSQLI_ASSOC);
        }
        
        if ($_GET['action'] == 'get_years') {
            $sql = "SELECT year_id, year FROM year_tbl ORDER BY year";
            $result = $conn->query($sql);
            if (!$result) throw new Exception("Year query failed: " . $conn->error);
            $response['data'] = $result->fetch_all(MYSQLI_ASSOC);
        }

        if ($_GET['action'] == 'get_dashboard_data') {
            $courseFilter = isset($_GET['course']) && $_GET['course'] !== 'all' ? $_GET['course'] : null;
            $yearFilter = isset($_GET['year']) && $_GET['year'] !== 'all' ? $_GET['year'] : null;
            $periodFilter = isset($_GET['period']) ? $_GET['period'] : 'all';

            $whereClauses = "";
            $params = [];
            $types = "";
            if ($courseFilter) { $whereClauses .= " AND u.course_id = ?"; $params[] = $courseFilter; $types .= "i"; }
            if ($yearFilter) { $whereClauses .= " AND u.year_id = ?"; $params[] = $yearFilter; $types .= "i"; }
            
            $violationsQuery = "SELECT vt.violation_type, COUNT(v.violation_id) as count FROM violation_tbl v JOIN users_tbl u ON v.student_number = u.student_number JOIN violation_type_tbl vt ON v.violation_type = vt.violation_type_id WHERE 1 {$whereClauses}";
            switch ($periodFilter) {
                case 'today': $violationsQuery .= " AND DATE(v.violation_date) = CURDATE()"; break;
                case 'week': $violationsQuery .= " AND YEARWEEK(v.violation_date, 1) = YEARWEEK(CURDATE(), 1)"; break;
                case 'month': $violationsQuery .= " AND MONTH(v.violation_date) = MONTH(CURDATE()) AND YEAR(v.violation_date) = YEAR(CURDATE())"; break;
                case 'year': $violationsQuery .= " AND YEAR(v.violation_date) = YEAR(CURDATE())"; break;
            }
            $violationsQuery .= " GROUP BY vt.violation_type ORDER BY count DESC";
            
            $stmt_violations = $conn->prepare($violationsQuery);
            if (!$stmt_violations) throw new Exception("Violation query failed: " . $conn->error);
            if (!empty($params)) $stmt_violations->bind_param($types, ...$params);
            $stmt_violations->execute();
            $violationData = $stmt_violations->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_violations->close();
            
            $response['data'] = [
                'violation' => ['labels' => array_column($violationData, 'violation_type'), 'data' => array_column($violationData, 'count')]
            ];
        }

    } catch (Exception $e) {
        $response['error'] = "A fatal error occurred: " . $e->getMessage();
    }
    
    $conn->close();
    echo json_encode($response);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./security_style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header class="main-header">
        <div class="header-content">
            <div class="logo"><img src="../IMAGE/Tracker-logo.png" alt="PUP Logo"></div>
            <nav class="main-nav">
                <a href="security_dashboard.php" class="active-nav">Dashboard</a>
                <a href="./security_violation_page.php">Add Violation</a>
            </nav>
            <div class="user-icons">
                <a href="notification.html" class="notification"><svg class="header-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19 13.586V10c0-3.217-2.185-5.927-5.145-6.742C13.562 2.52 12.846 2 12 2s-1.562.52-1.855 1.258C7.185 4.073 5 6.783 5 10v3.586l-1.707 1.707A.996.996 0 0 0 3 16v2a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1v-2a.996.996 0 0 0-.293-.707L19 13.586zM19 17H5v-.586l1.707-1.707A.996.996 0 0 0 7 14v-4c0-2.757 2.243-5 5-5s5 2.243 5 5v4c0 .266.105.52.293.707L19 16.414V17zm-7 5a2.98 2.98 0 0 0 2.818-2H9.182A2.98 2.98 0 0 0 12 22z"/></svg></a>
                <a href="../PHP/security_account.php" class="admin-profile"><svg class="header-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg></a>
            </div>
        </div>
    </header>

<main>
    <div class="admin-wrapper" id="dashboard-content">
        <div class="loading-overlay" id="loadingOverlay"><div class="spinner"></div></div>
        <div class="dashboard-header">
            <div>
                <h1 class="page-main-title">Security Dashboard</h1>
                <p class="page-subtitle"><?php echo getGreeting(); ?></p>
            </div>
            <div class="controls-container">
                <div class="filter-group">
                    <label for="courseFilter">Course:</label>
                    <div class="select-wrapper"><select id="courseFilter" name="courseFilter"><option value="all">All Courses</option></select></div>
                </div>
                <div class="filter-group">
                    <label for="yearFilter">Year:</label>
                    <div class="select-wrapper"><select id="yearFilter" name="yearFilter"><option value="all">All Years</option></select></div>
                </div>
                <div class="filter-group">
                    <label for="datePeriod">Date:</label>
                    <div class="select-wrapper"><select id="datePeriod" name="datePeriod"><option value="all">All Time</option><option value="today">Today</option><option value="week">This Week</option><option value="month">This Month</option><option value="year">This Year</option></select></div>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="chart-card">
                <h2 class="chart-title">Violations Summary</h2>
                <p class="chart-insight" id="violationInsight"></p>
                <div class="chart-body" id="violationChartContainer"><canvas id="violationChart"></canvas></div>
            </div>
        </div>
    </div>
</main>
<script src="./security_scripts.js"></script>
</body>
</html>