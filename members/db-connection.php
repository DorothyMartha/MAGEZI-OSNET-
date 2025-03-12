<?php
// Database connection details
$servername = "pdb1992.awardspace.com";
$username = "ivnape_osnet";  
$password = "?K4mp4l4311";     
$dbname = "ivnape_osnet"; 

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optionally, set the character set for the connection (for UTF-8 support)
$conn->set_charset("utf8mb4");

?>
