<?php
// functions.php
function logActivity($pdo, $user_id, $action, $ip_address, $page_url, $details = null, $status = 'success', $session_id = null, $user_agent = null, $error_message = null) {
    file_put_contents('debug.log', "logActivity called: $action - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    try {
        $sql = "INSERT INTO access_logs (user_id, action, ip_address, status, session_id, user_agent, page_url, details, error_message) 
                VALUES (:user_id, :action, :ip_address, :status, :session_id, :user_agent, :page_url, :details, :error_message)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $user_id,
            ':action' => $action,
            ':ip_address' => $ip_address,
            ':status' => $status,
            ':session_id' => $session_id,
            ':user_agent' => $user_agent,
            ':page_url' => $page_url,
            ':details' => $details,
            ':error_message' => $error_message
        ]);
    } catch (PDOException $e) {
        file_put_contents('error.log', "LogActivity failed: " . $e->getMessage() . " - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    }
}
?>