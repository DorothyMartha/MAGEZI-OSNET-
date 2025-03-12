<?php
// libs/db_connect.php

// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'mageziac_osnet');
define('DB_PASSWORD', '?P4ssw0rd311');
define('DB_NAME', 'mageziac_osnet');

// Create connection
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optionally, make $conn global or return it for use in other files
// global $conn; // If you prefer to use it globally
// return $conn; // If you want to return it for specific use
?>