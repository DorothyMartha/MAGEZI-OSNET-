<?php
// functions.php
function logActivity($conn, $user_id, $action, $ip_address, $page_url, $details = null, $status = 'success', $session_id = null, $user_agent = null, $error_message = null) {
    try {
        $sql = "INSERT INTO access_logs (user_id, action, ip_address, status, session_id, user_agent, page_url, details, error_message) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("issssssss", $user_id, $action, $ip_address, $status, $session_id, $user_agent, $page_url, $details, $error_message);
            $stmt->execute();
            $stmt->close();
        } else {
            throw new Exception("Prepare failed: " . $conn->error);
        }
    } catch (Exception $e) {
        // Log error to a file instead of crashing
        file_put_contents('/srv/disk2/ivnape/www/magezi.ac.ug/osnet/error.log', 
            "LogActivity failed: " . $e->getMessage() . " - " . date('Y-m-d H:i:s') . "\n", 
            FILE_APPEND
        );
    }
}
?>