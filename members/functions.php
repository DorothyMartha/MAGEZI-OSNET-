<?php
function logActivity($conn, $user_id, $action, $ip_address, $page_url, $details = null, $status = 'success', $session_id = null, $user_agent = null, $error_message = null) {
    $sql = "INSERT INTO access_logs (user_id, action, ip_address, status, session_id, user_agent, page_url, details, error_message) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('issssssss', $user_id, $action, $ip_address, $status, $session_id, $user_agent, $page_url, $details, $error_message);
    $stmt->execute();
    $stmt->close();
}
?>