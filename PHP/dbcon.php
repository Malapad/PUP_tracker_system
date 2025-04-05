<?php
$servername = "localhost";
$username = "root";
$password = "password";
$database = "pup_trackersys";

$conn = new mysqli($servername, $username, $password, $database);

if (!$conn) {
    die("❌ Connection failed: " . mysqli_connect_error());
} else {
//   echo "✅ Connected to the database!";
}
?>
