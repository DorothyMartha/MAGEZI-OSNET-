<?php
$servername = "pdb1992.awardspace.com";
$username = "ivnape_osnet";  
$password = "?K4mp4l4311";     
$dbname = "ivnape_osnet"; 
$conn = new mysqli($servername, $username, $password, $dbname);

// If connection fails, set $conn to null and let callback.php handle it
if (!$conn || $conn->connect_error) {
    $conn = null;
}
?>