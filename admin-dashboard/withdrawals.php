<?php 
// Include your header and sidebar
include 'header.php'; 
include 'sidebar.php'; 

require_once '../libs/db-connection.php';

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to count pending withdrawals
$sql_count = "SELECT COUNT(*) AS pending_count FROM withdrawals WHERE status = 'Pending'";
$result_count = $conn->query($sql_count);
$pending_count = 0;
if ($result_count->num_rows > 0) {
    $row_count = $result_count->fetch_assoc();
    $pending_count = $row_count['pending_count']; // Number of pending withdrawals
}

// Fetch withdrawal requests
$sql = "SELECT w.id, a.school_name, w.amount, w.requested_at, w.status
        FROM withdrawals w
        JOIN alumni_associations a ON w.association_id = a.id
        ORDER BY w.requested_at DESC";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/withdrawals.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
</head>
<body>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <div class="top-nav">
            <h2>Admin Dashboard</h2>
            <div class="user-info">
                <span>Welcome, Admin</span>
            </div>
            <!-- Notification Icon -->
            <div class="notification-icon">
                <i class="fas fa-bell"></i>
                <span class="notification-count">3</span> <!-- Dynamic count can be set here -->
            </div>
        </div>

        <!-- Page Content -->
        <h2>Fund Withdrawal Requests</h2>

        <!-- Notification Section -->
        <div class="notification">
        <span style="display: block; text-align: center;">You have <?php echo $pending_count; ?> pending withdrawal request(s).</span>

</div>


        <!-- Withdrawals Table -->
        <table class="withdrawals-table">
            <thead>
                <tr>
                    <th>School Name</th>
                    <th>Amount Requested</th>
                    <th>Request Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    // Loop through each withdrawal request and display the data
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>" . htmlspecialchars($row['school_name']) . "</td>
                                <td>Ugx. " . number_format($row['amount'], 2) . "</td>
                                <td>" . htmlspecialchars($row['requested_at']) . "</td>
                                <td>" . htmlspecialchars($row['status']) . "</td>
                                <td>
                                    <button class='approve-btn'>Approve</button>
                                    <button class='reject-btn'>Reject</button>
                                </td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' style='text-align: center;'>No withdrawal requests found.</td></tr>";

                }
                ?>
            </tbody>
        </table>
    </div>

    <?php
    // Close the connection
    $conn->close();
    ?>

</body>
</html>
