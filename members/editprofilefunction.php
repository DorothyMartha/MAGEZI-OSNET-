<?php
function logActivity($conn, $user_id, $action, $ip_address, $uri, $details, $status, $session_id, $user_agent, $error = null) {
    try {
        $sql = "INSERT INTO activity_logs (user_id, action, ip_address, uri, details, status, session_id, user_agent, error, logged_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("issssssss", $user_id, $action, $ip_address, $uri, $details, $status, $session_id, $user_agent, $error);
            $stmt->execute();
            $stmt->close();
        } else {
            error_log("Prepare failed in logActivity: " . $conn->error);
        }
    } catch (Exception $e) {
        error_log("Error logging activity: " . $e->getMessage());
    }
}