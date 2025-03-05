<?php
$servername = "localhost";
$username = "root";
$password = "Tayotomika04";
$database = "pup_trackersys";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

//echo "Connected successfully";
?>
