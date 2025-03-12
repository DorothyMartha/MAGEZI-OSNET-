<?php 
session_start();
if (!isset($_SESSION)) {
    die("Session not started.");
}
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'header.php'; 
include 'sidebar.php'; 
require_once '../libs/db-connection.php'; 

if (!$conn) {
    die("Failed to connect to the database.");
}

// Assuming admin is logged in and their ID is stored in $_SESSION['user_id']
$admin_id = $_SESSION['user_id'] ?? null;

if (!$admin_id) {
    die("Unauthorized access. Please log in.");
}

// Get admin's association ID
$query = "SELECT association_id FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$adminAssociation = $result->fetch_assoc()['association_id'] ?? null;

if (!$adminAssociation) {
    die("No associated association found for this admin.");
}

// Fetch the total number of members in the same association as the admin
$query = "
    SELECT COUNT(DISTINCT u.id) AS total_members 
    FROM users u
    LEFT JOIN user_associations ua ON u.id = ua.user_id
    WHERE (u.association_id = ? AND u.role = 'Member') 
       OR (ua.association_id = ? AND u.role = 'Member')";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $adminAssociation, $adminAssociation);
$stmt->execute();
$result = $stmt->get_result();
$totalMembers = $result->fetch_assoc()['total_members'];

// Fetch total amount of completed donations for the association
$query = "
    SELECT SUM(d.amount) AS total_donations 
    FROM donations d
    JOIN users u ON d.user_id = u.id
    WHERE u.association_id = ? AND d.payment_status = 'completed'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $adminAssociation);
$stmt->execute();
$result = $stmt->get_result();
$totalDonations = $result->fetch_assoc()['total_donations'] ?? 0;

// Fetch total subscription funds for users in the same association with COMPLETED status
$query = "
    SELECT SUM(s.amount) AS total_subscriptions 
    FROM subscriptions s
    JOIN users u ON s.user_id = u.id
    WHERE u.association_id = ? AND s.payment_status = 'COMPLETED'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $adminAssociation);
$stmt->execute();
$result = $stmt->get_result();
$totalFunds = $result->fetch_assoc()['total_subscriptions'] ?? 0;

// Fetch number of pending withdrawals for the association
$query = "SELECT COUNT(*) AS pending_withdrawals FROM withdrawals WHERE association_id = ? AND status = 'Pending'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $adminAssociation);
$stmt->execute();
$result = $stmt->get_result();
$pendingWithdrawals = $result->fetch_assoc()['pending_withdrawals'];

if ($pendingWithdrawals == 0) {
    $pendingWithdrawals = "None";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f4f7fa;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            max-width: 1000px;
        }
        
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .card {
            background: #ffffff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: transform 0.2s;
        }

        .card-link {
            display: block;
            text-decoration: none;
        }

        .card.primary, .card.tertiary {
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card.primary:hover, .card.tertiary:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .card h3 {
            font-size: 14px;
            color: #fff;
            margin: 0 0 10px 0;
        }

        .card p {
            font-size: 20px;
            font-weight: 600;
            margin: 0;
        }

        .primary { color: #3498db; }
        .secondary { color: #27ae60; }
        .tertiary { color: #e67e22; }
        .quaternary { color: #e74c3c; }

        .quick-actions {
            background: #ffffff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .quick-actions h3 {
            font-size: 16px;
            color: #34495e;
            margin: 0 0 10px 0;
        }

        .quick-actions ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .quick-actions li {
            margin: 8px 0;
        }

        .quick-actions a {
            text-decoration: none;
            color: #34495e;
            font-size: 14px;
            transition: color 0.2s;
        }

        .quick-actions a:hover {
            color: #415b76; 
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .dashboard-cards {
                grid-template-columns: 1fr;
            }
        }

        .users-table, .subscriptions-table {
            margin: 20px 0;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .users-table h3, .subscriptions-table h3 {
            margin-bottom: 15px;
            color: #333;
        }

        .table-container {
            max-height: 400px;
            overflow-y: auto;
            overflow-x: auto;
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            min-width: 1200px;
        }

        th, td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            font-size: 12px;
        }

        th {
            background-color: #f5f5f5;
            font-weight: 600;
            color: #444;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        td {
            color: #666;
        }

        td:last-child {
            white-space: normal;
            line-height: 1.4;
        }

        .top-nav {
            background-color: #1f3d7a;
            width: 90%;
            max-width: 910px;
            color: #F1F1F1;
            font-family: 'Arial', sans-serif;
            font-size: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 18px;
            left: 270px;
            z-index: 1000;
            box-shadow: 0px 3px 10px rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid #ddd;
            height: 40px;
            position: fixed;
            top: 10px;
            border-radius: 8px;
        }

        .top-nav h2 {
            font-size: 16px;
            font-weight: 700;
            margin: 0;
            color: #F1F1F1;
        }

        .top-nav .user-info span {
            font-size: 14px;
            color: #E0E0E0;
        }

        .main-content {
            margin-left: 220px;
            padding: 20px;
            margin-top: 80px;
        }

        .search-container {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-container input[type="text"] {
            padding: 8px 12px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 300px;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: border-color 0.3s ease;
        }

        .search-container input[type="text"]:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.5);
        }

        .search-container label {
            font-size: 14px;
            color: #34495e;
            font-weight: 600;
        }
         /* Enhanced Form Styles */
         h2 {
            font-size: 24px;
            color: #fff;
            margin-bottom: 20px;
            text-decoration: none;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid #1f3d7a;
            padding-bottom: 8px;
        }
    </style>
</head>

<body>
    <div class="main-content">
        <div class="top-nav">
            <h3>Alumni Association Admin Dashboard</h3>
            <div class="user-info">
                <span>Welcome, Admin</span>
            </div>
        </div>

        <h3>Dashboard Overview</h3>

        <div class="dashboard-cards">
            <a href="#users-table" class="card-link">
                <div class="card primary">
                    <h3>Total Members</h3>
                    <p><?php echo $totalMembers; ?></p>
                </div>
            </a>

            <a href="fundraising_campaigns.php" class="card-link">
                <div class="card secondary">
                    <h3>Total Donations</h3>
                    <p>UGX <?php echo number_format($totalDonations, 2); ?></p>
                </div>
            </a>

            <a href="#subscriptions-table" class="card-link">
                <div class="card tertiary">
                    <h3>Subscription Funds</h3>
                    <p>UGX <?php echo number_format($totalFunds, 2); ?></p>
                </div>
            </a>

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
                <li><a href="withdraw_funds.php">Request Funds Withdrawal</a></li>
                <li><a href="customize_association.php">Edit Association Page</a></li>
            </ul>
        </div>

        <div class="users-table" id="users-table">
            <h3>Members in Your Association</h3>
            <div class="search-container">
                <label for="searchInput">Search Members:</label>
                <input type="text" id="searchInput" placeholder="Enter name, ID, role, etc...">
            </div>
            <div class="table-container">
                <?php
                $query = "
                    SELECT 
                        u.id,
                        u.full_name,
                        u.email,
                        u.phone_number,
                        u.years_of_attendance,
                        u.occupation,
                        u.marital_status,
                        u.role,
                        u.created_at,
                        u.country_id,
                        u.country_of_residence,
                        mc.category_name AS membership_category,
                        GROUP_CONCAT(DISTINCT aa.association_name SEPARATOR '|') AS other_associations
                    FROM users u
                    LEFT JOIN user_associations ua ON u.id = ua.user_id AND ua.association_id != ?
                    LEFT JOIN alumni_associations aa ON ua.association_id = aa.id
                    LEFT JOIN membership_categories mc ON u.membership_category_id = mc.id
                    WHERE (u.association_id = ? AND u.role = 'Member')
                       OR (u.id IN (SELECT user_id FROM user_associations WHERE association_id = ?) AND u.role = 'Member')
                    GROUP BY u.id
                    ORDER BY u.created_at DESC";
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iii", $adminAssociation, $adminAssociation, $adminAssociation);
                $stmt->execute();
                $result = $stmt->get_result();
                ?>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User Name</th>
                            <th>Email</th>
                            <th>Phone Number</th>
                            <th>Years of Attendance</th>
                            <th>Occupation</th>
                            <th>Marital Status</th>
                            <th>Role</th>
                            <th>Join Date</th>
                            <th>Country of Residence</th>
                            <th>Membership Category</th>
                            <th>Other Associations</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <?php
                        if ($result->num_rows > 0) {
                            while ($user = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($user['id']) . "</td>";
                                echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                                echo "<td>" . htmlspecialchars($user['phone_number'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($user['years_of_attendance'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($user['occupation'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($user['marital_status'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($user['role']) . "</td>";
                                echo "<td>" . date('Y-m-d', strtotime($user['created_at'])) . "</td>";
                                echo "<td>" . htmlspecialchars($user['country_of_residence']) . "</td>";
                                echo "<td>" . htmlspecialchars($user['membership_category'] ?? 'N/A') . "</td>";
                                if ($user['other_associations']) {
                                    $associations = explode('|', $user['other_associations']);
                                    $numbered_associations = array_map(function($index, $name) {
                                        return ($index + 1) . '. ' . htmlspecialchars($name);
                                    }, array_keys($associations), $associations);
                                    $other_associations = implode('<br>', $numbered_associations);
                                } else {
                                    $other_associations = 'None';
                                }
                                echo "<td>" . $other_associations . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='12'>No members found in this association.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="subscriptions-table" id="subscriptions-table">
            <h3>Subscription Details</h3>
            <div class="search-container">
                <label for="subscriptionsSearchInput">Search Subscriptions:</label>
                <input type="text" id="subscriptionsSearchInput" placeholder="Enter name, ID, status, etc...">
            </div>
            <div class="table-container">
                <?php
                $query = "
                    SELECT 
                        s.id,
                        u.full_name AS user_name,
                        s.amount,
                        s.payment_status,
                        s.payment_date,
                        s.transaction_tracking_id,
                        s.merchant_reference,
                        s.created_at,
                        s.plan_id
                    FROM subscriptions s
                    JOIN users u ON s.user_id = u.id
                    WHERE u.association_id = ?
                    ORDER BY s.created_at DESC";
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $adminAssociation);
                $stmt->execute();
                $result = $stmt->get_result();
                ?>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User Name</th>
                            <th>Amount (UGX)</th>
                            <th>Payment Status</th>
                            <th>Payment Date</th>
                            <th>Transaction ID</th>
                            <th>Merchant Reference</th>
                            <th>Created At</th>
                            <th>Plan ID</th>
                        </tr>
                    </thead>
                    <tbody id="subscriptionsTableBody">
                        <?php
                        if ($result->num_rows > 0) {
                            while ($subscription = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($subscription['id']) . "</td>";
                                echo "<td>" . htmlspecialchars($subscription['user_name']) . "</td>";
                                echo "<td>" . number_format($subscription['amount'], 2) . "</td>";
                                echo "<td>" . htmlspecialchars($subscription['payment_status']) . "</td>";
                                echo "<td>" . htmlspecialchars($subscription['payment_date']) . "</td>";
                                echo "<td>" . htmlspecialchars($subscription['transaction_tracking_id'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($subscription['merchant_reference'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($subscription['created_at']) . "</td>";
                                echo "<td>" . htmlspecialchars($subscription['plan_id'] ?? 'N/A') . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='9'>No subscriptions found for this association.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.querySelector('.toggle-btn').addEventListener('click', function() {
            const dropdownMenu = document.querySelector('.dropdown-menu');
            dropdownMenu.classList.toggle('show');
        });

        document.querySelectorAll('.card-link')[0].addEventListener('click', function(e) {
            e.preventDefault();
            const usersTable = document.getElementById('users-table');
            usersTable.scrollIntoView({ behavior: 'smooth' });
        });

        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#usersTableBody tr');

            rows.forEach(row => {
                const id = row.cells[0].textContent.toLowerCase();
                const userName = row.cells[1].textContent.toLowerCase();
                const yearsOfAttendance = row.cells[4].textContent.toLowerCase();
                const role = row.cells[7].textContent.toLowerCase();
                const countryOfResidence = row.cells[9].textContent.toLowerCase();
                const membershipCategory = row.cells[10].textContent.toLowerCase();

                if (id.includes(searchTerm) || 
                    userName.includes(searchTerm) || 
                    yearsOfAttendance.includes(searchTerm) || 
                    role.includes(searchTerm) || 
                    countryOfResidence.includes(searchTerm) || 
                    membershipCategory.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        document.querySelectorAll('.card-link')[2].addEventListener('click', function(e) {
            e.preventDefault();
            const subscriptionsTable = document.getElementById('subscriptions-table');
            subscriptionsTable.scrollIntoView({ behavior: 'smooth' });
        });

        document.getElementById('subscriptionsSearchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#subscriptionsTableBody tr');

            rows.forEach(row => {
                const id = row.cells[0].textContent.toLowerCase();
                const userName = row.cells[1].textContent.toLowerCase();
                const paymentStatus = row.cells[3].textContent.toLowerCase();
                const planId = row.cells[8].textContent.toLowerCase();

                if (id.includes(searchTerm) || 
                    userName.includes(searchTerm) || 
                    paymentStatus.includes(searchTerm) || 
                    planId.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>