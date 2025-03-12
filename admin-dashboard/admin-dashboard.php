<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../libs/db-connection.php';

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Get total number of alumni (users)
$sqlUsers = "SELECT COUNT(*) AS total_users FROM users";
$resultUsers = $conn->query($sqlUsers);
$totalUsers = ($resultUsers->num_rows > 0) ? $resultUsers->fetch_assoc()['total_users'] : 0;

// Get pending withdrawals count
$sqlWithdrawals = "SELECT COUNT(*) AS pending_withdrawals FROM withdrawals WHERE status = 'Pending'";
$resultWithdrawals = $conn->query($sqlWithdrawals);
$pendingWithdrawals = ($resultWithdrawals->num_rows > 0) ? $resultWithdrawals->fetch_assoc()['pending_withdrawals'] : 0;

// Get total funds (sum of all successful subscriptions)
$sqlFunds = "SELECT COALESCE(SUM(amount), 0) AS total_funds FROM subscriptions WHERE payment_status = 'COMPLETED'";
$resultFunds = $conn->query($sqlFunds);
$totalFunds = ($resultFunds->num_rows > 0) ? $resultFunds->fetch_assoc()['total_funds'] : 0;

// Fetch all users and their associations for the table
$users = [];
$sqlAllUsers = "SELECT id, full_name, email, phone_number, occupation, marital_status, association_id, country_of_residence, status FROM users";
$resultAllUsers = $conn->query($sqlAllUsers);
if ($resultAllUsers->num_rows > 0) {
    while ($row = $resultAllUsers->fetch_assoc()) {
        $user_id = $row['id'];
        // Get additional association IDs from user_associations for this user
        $assoc_ids = [$row['association_id']]; // Start with the primary association_id from users
        $sqlUserAssociations = "SELECT association_id FROM user_associations WHERE user_id = ?";
        $stmt = $conn->prepare($sqlUserAssociations);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $resultAssociations = $stmt->get_result();
        if ($resultAssociations->num_rows > 0) {
            while ($assoc = $resultAssociations->fetch_assoc()) {
                $assoc_ids[] = $assoc['association_id'];
            }
        }
        $stmt->close();
        // Convert association IDs to a comma-separated string, removing duplicates and NULL/empty values
        $assoc_ids = array_filter(array_unique($assoc_ids));
        $row['association_ids'] = !empty($assoc_ids) ? implode(', ', $assoc_ids) : 'N/A';
        $users[] = $row;
    }
}

$conn->close();
?>

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/admindashboard.css">
</head>
<body>

<div class="main-content">
    <div class="top-nav">
        <h2>Admin Dashboard</h2>
        <div class="user-info">
            <span>Welcome,You are a SuperAdmin</span>
        </div>
    </div>

    <h2>Dashboard Overview</h2>

    <div class="dashboard-cards">
        <div class="card primary">
            <h3>Total Alumni</h3>
            <p><?php echo $totalUsers; ?></p>
        </div>
        <div class="card secondary">
            <h3>Pending Withdrawals</h3>
            <p><?php echo $pendingWithdrawals; ?></p>
        </div>
        <div class="card tertiary">
            <h3>Total in Subscriptions</h3>
            <p>UGX <?php echo number_format($totalFunds, 2); ?></p>
        </div>
    </div>

    <!-- Users Table Section with Search Bar -->
    <div class="users-table-container">
        <h2>Registered Users</h2>
        <div class="search-container">
            <input type="text" id="userSearch" placeholder="Search by Full Name, Email, or Country of Residence...">
        </div>
        <table class="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone Number</th>
                    <th>Occupation</th>
                    <th>Marital Status</th>
                    <th>Association ID</th>
                    <th>Country of Residence</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="9">No users found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone_number'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($user['occupation'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($user['marital_status'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($user['association_ids']); ?></td>
                            <td><?php echo htmlspecialchars($user['country_of_residence']); ?></td>
                            <td><?php echo htmlspecialchars($user['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // JavaScript for search functionality
    document.getElementById('userSearch').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('#usersTableBody tr');

        rows.forEach(row => {
            const fullName = row.cells[1].textContent.toLowerCase(); // Full Name column
            const email = row.cells[2].textContent.toLowerCase();   // Email column
            const country = row.cells[7].textContent.toLowerCase(); // Country of Residence column

            // Show row if it matches search term in any of the specified columns
            if (fullName.includes(searchTerm) || email.includes(searchTerm) || country.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
</script>

</body>
</html>