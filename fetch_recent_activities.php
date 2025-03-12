<?php
require_once 'libs/db-connection.php';
// Check connection
if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed: " . $conn->connect_error]));
}

// Fetch the latest 5 activities from the access_logs table
$sql = "SELECT action, logged_at FROM access_logs ORDER BY logged_at DESC LIMIT 2";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $activities = [];
    while ($row = $result->fetch_assoc()) {
        $activities[] = $row;
    }
    echo json_encode($activities);
} else {
    echo json_encode(['error' => 'No recent activities found']);
}

$conn->close();
?>
