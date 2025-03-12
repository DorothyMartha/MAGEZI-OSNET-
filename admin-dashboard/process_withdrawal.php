<?php
header("Content-Type: application/json");

// Database connection
$conn = new mysqli('pdb1992.awardspace.com', 'ivnape_osnet', '?K4mp4l4311', 'ivnape_osnet_db');

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if (!$id || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit;
}

$status = ($action === "approve") ? "Approved" : "Rejected";
$sql = "UPDATE withdrawals SET status = ?, processed_at = NOW() WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $status, $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
