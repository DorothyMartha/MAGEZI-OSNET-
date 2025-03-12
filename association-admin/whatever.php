<?php 
session_start();
if (!isset($_SESSION)) {
    die("Session not started.");
}
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'header.php'; 
include 'sidebar.php'; 
require_once('db_connection.php'); 

if (!$mysqli) {
    die("Failed to connect to the database.");
}

// Assuming admin is logged in and their ID is stored in $_SESSION['admin_id']

$admin_id = $_SESSION['user_id'] ?? null;

if (!$admin_id) {
    die("Unauthorized access. Please log in.");
}

// Get admin's association ID
$query = "SELECT association_id FROM users WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$adminAssociation = $result->fetch_assoc()['association_id'] ?? null;

if (!$adminAssociation) {
    die("No associated association found for this admin.");
}

// Fetch the total number of members in the same association as the admin
$query = "SELECT COUNT(*) AS total_members FROM users WHERE association_id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $adminAssociation);
$stmt->execute();
$result = $stmt->get_result();
$totalMembers = $result->fetch_assoc()['total_members'];

// Fetch total amount of completed donations for the association
$query = "
    SELECT SUM(d.amount) AS total_donations 
    FROM donations d
    JOIN users u ON d.user_id = u.id
    WHERE u.association_id = ? AND d.payment_status = 'completed'";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $adminAssociation);
$stmt->execute();
$result = $stmt->get_result();
$totalDonations = $result->fetch_assoc()['total_donations'] ?? 0;

// Fetch total subscription funds for users in the same association
$query = "
    SELECT SUM(s.amount) AS total_subscriptions 
    FROM subscriptions s
    JOIN users u ON s.user_id = u.id
    WHERE u.association_id = ? AND s.payment_status = 'SUCCESS'";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $adminAssociation);
$stmt->execute();
$result = $stmt->get_result();
$totalFunds = $result->fetch_assoc()['total_subscriptions'] ?? 0;

// Fetch number of pending withdrawals for the association
$query = "SELECT COUNT(*) AS pending_withdrawals FROM withdrawals WHERE association_id = ? AND status = 'Pending'";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $adminAssociation);
$stmt->execute();
$result = $stmt->get_result();
$pendingWithdrawals = $result->fetch_assoc()['pending_withdrawals'];

if ($pendingWithdrawals == 0) {
    $pendingWithdrawals = "None";
}
?>

<div class="main-content">
    <div class="top-nav">
        <h2>Alumni Association Admin Dashboard</h2>
        <div class="user-info">
            <span>Welcome, Admin</span>
        </div>
    </div>

    <h2>Dashboard Overview</h2>

    <div class="dashboard-cards">
        <!-- Total Members Card -->
        <div class="card primary">
            <h3>Total Members</h3>
            <p><?php echo $totalMembers; ?></p>
        </div>

        <!-- Total Donations Card -->
        <a href="fundraising_campaigns.php" style="text-decoration: none;">
            <div class="card secondary" style="display: flex; flex-direction: column; align-items: center; text-align: center; cursor: pointer; padding: 25px;">
                <h3>Total Donations</h3>
                <p>UGX <?php echo number_format($totalDonations, 2); ?></p>
            </div>
        </a>

        <!-- Total Subscription Funds Card -->
        <div class="card tertiary">
            <h3>Amount in subscriptions</h3>
            <p>UGX <?php echo number_format($totalFunds, 2); ?></p>
        </div>

        <!-- Pending Withdrawals Card -->
        <div class="card quaternary">
            <h3>Pending Withdrawals</h3>
            <p><?php echo $pendingWithdrawals; ?></p>
        </div>
    </div>

    <div class="quick-actions">
        <h3>Quick Actions</h3>
        <ul>
            <li><a href="membership_categories.php">Manage Membership Categories</a></li>
            <li><a href="fundraising_campaigns.php">Create Fundraising Campaign</a></li>
            <li><a href="withdrawals.php">Request Funds Withdrawal</a></li>
            <li><a href="customize_page.php">Edit Association Page</a></li>
        </ul>
    </div>

    <!-- Users Table -->
<div class="users-table">
    <h3>Members in Your Association</h3>
    <?php
    // Fetch users with the same association_id
    $query = "
        SELECT 
            id,
            full_name,
            email,
            phone_number,
            role,
            created_at,
            status
        FROM users 
        WHERE association_id = ? 
        ORDER BY created_at DESC";
    
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $adminAssociation);
    $stmt->execute();
    $result = $stmt->get_result();
    ?>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Phone Number</th>
                <th>Role</th>
                <th>Join Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while ($user = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($user['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                    echo "<td>" . htmlspecialchars($user['phone_number'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($user['role']) . "</td>";
                    echo "<td>" . date('Y-m-d', strtotime($user['created_at'])) . "</td>";
                    echo "<td>" . htmlspecialchars($user['status']) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='7'>No members found in this association.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>
</div>


<script>
    // Script to toggle the dropdown menu on smaller screens
    document.querySelector('.toggle-btn').addEventListener('click', function() {
        const dropdownMenu = document.querySelector('.dropdown-menu');
        dropdownMenu.classList.toggle('show');
    });
</script>

</body>
</html>
