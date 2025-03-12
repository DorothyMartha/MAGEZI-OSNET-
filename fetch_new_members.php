<?php
session_start();

require_once 'libs/db-connection.php';

// Check connection
if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed: " . $conn->connect_error]));
}

// Fetch newly registered users (e.g., last 5 users)
$sql = "
    SELECT 
        u.full_name, 
        u.profile_photo, 
        a.school_name 
    FROM 
        users u 
    LEFT JOIN 
        alumni_associations a 
    ON 
        u.association_id = a.id 
    ORDER BY 
        u.created_at DESC 
    LIMIT 5
";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    echo json_encode($users); // Return data as JSON
} else {
    echo json_encode(["error" => "No new members found"]);
}

$conn->close();
?>