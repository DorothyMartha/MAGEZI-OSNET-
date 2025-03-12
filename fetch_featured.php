<?php
session_start();

require_once 'libs/db-connection.php';

// Check connection
if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed: " . $conn->connect_error]));
}

// Fetch two random associations
$sql = "SELECT short_name, school_name, address, contact_person_name FROM alumni_associations ORDER BY RAND() LIMIT 4";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $associations = [];
    while ($row = $result->fetch_assoc()) {
        $associations[] = $row;
    }
    echo json_encode($associations); // Return data as JSON
} else {
    echo json_encode(["error" => "No associations found"]);
}

$conn->close();
?>