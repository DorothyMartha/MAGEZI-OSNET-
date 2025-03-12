<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'libs/db-connection.php';

// Check connection
if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed: " . $conn->connect_error]));
}

// Fetch fundraising campaigns that are active and still ongoing
$sql = "SELECT fc.title, fc.description, fc.start_date, fc.end_date, fc.status, 
               s.address, s.association_name
        FROM fundraising_campaigns fc
        JOIN alumni_associations s ON fc.association_id = s.id
        WHERE fc.status = 'Active' AND fc.end_date >= CURDATE()
        ORDER BY RAND() LIMIT 3"; // Fetch 3 random active and ongoing campaigns

$result = $conn->query($sql);
$campaigns = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $campaigns[] = $row;
    }
}

// Output as JSON
echo json_encode($campaigns);

// Close the connection
$conn->close();
?>
