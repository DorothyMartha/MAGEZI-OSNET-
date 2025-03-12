<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once '../libs/db-connection.php';

// Add logging functionality (if functions.php exists)
if (file_exists('functions.php')) {
    require_once 'functions.php';
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Log activity if functions.php is included and connection is available
if (function_exists('logActivity')) {
    try {
        logActivity($conn, $user_id, "Viewed dashboard", $_SERVER['REMOTE_ADDR'], $_SERVER['REQUEST_URI'], null, 'success', session_id(), $_SERVER['HTTP_USER_AGENT'] ?? null);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

// Fetch user details from the database
$user = null;
$query = "SELECT full_name, email, phone_number, profile_photo, years_of_attendance, 
                 occupation, marital_status, role, association_id
          FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
        } else {
            die("User not found.");
        }
    } else {
        error_log("User query execute failed: " . $stmt->error);
        $user = ['full_name' => 'Unknown User', 'association_id' => null]; // Fallback
    }
    $stmt->close();
} else {
    error_log("User query prepare failed: " . $conn->error);
    $user = ['full_name' => 'Unknown User', 'association_id' => null]; // Fallback
}

// Fetch association name based on association_id
$association_id = $user['association_id'];
$association_name = "Not affiliated with any association";
if (!empty($association_id)) {
    $assoc_query = "SELECT association_name FROM alumni_associations WHERE id = ?";
    $assoc_stmt = $conn->prepare($assoc_query);
    if ($assoc_stmt) {
        $assoc_stmt->bind_param("i", $association_id);
        if ($assoc_stmt->execute()) {
            $assoc_result = $assoc_stmt->get_result();
            if ($assoc_result->num_rows > 0) {
                $association = $assoc_result->fetch_assoc();
                $association_name = htmlspecialchars($association['association_name']);
            } else {
                $association_name = "No association found";
            }
        } else {
            error_log("Association query execute failed: " . $assoc_stmt->error);
        }
        $assoc_stmt->close();
    } else {
        error_log("Association query prepare failed: " . $conn->error);
    }
}

// Fetch ongoing campaigns related to the user's association
$campaign_result = null;
if (!empty($association_id)) {
    $campaign_query = "SELECT id, title, description, start_date, end_date, fee, total_donated 
                       FROM fundraising_campaigns 
                       WHERE association_id = ? 
                       AND end_date >= CURDATE() 
                       AND status = 'Active'";
    $campaign_stmt = $conn->prepare($campaign_query);
    if ($campaign_stmt) {
        $campaign_stmt->bind_param("i", $association_id);
        if ($campaign_stmt->execute()) {
            $campaign_result = $campaign_stmt->get_result();
        } else {
            error_log("Campaign query execute failed: " . $campaign_stmt->error);
        }
        $campaign_stmt->close();
    } else {
        error_log("Campaign query prepare failed: " . $conn->error);
    }
}

// Fetch user's transactions from the subscriptions table with membership category and association details
$transaction_result = null;
$transaction_query = "SELECT s.amount, s.payment_status, s.payment_date, s.transaction_tracking_id, 
                             s.merchant_reference, s.created_at, mc.category_name, aa.association_name
                      FROM subscriptions s
                      JOIN users u ON s.user_id = u.id
                      LEFT JOIN membership_categories mc ON u.membership_category_id = mc.id
                      LEFT JOIN alumni_associations aa ON u.association_id = aa.id
                      WHERE s.user_id = ?
                      ORDER BY s.created_at DESC";
$transaction_stmt = $conn->prepare($transaction_query);
if ($transaction_stmt) {
    $transaction_stmt->bind_param("i", $user_id);
    if ($transaction_stmt->execute()) {
        $transaction_result = $transaction_stmt->get_result();
    } else {
        error_log("Transaction query execute failed: " . $transaction_stmt->error);
    }
    $transaction_stmt->close();
} else {
    error_log("Transaction query prepare failed: " . $conn->error);
}

// Fetch user's associations from user_associations
$assoc_result = null;
$assoc_query = "SELECT ua.association_id, aa.association_name, ua.years_of_attendance 
                FROM user_associations ua 
                JOIN alumni_associations aa ON ua.association_id = aa.id 
                WHERE ua.user_id = ?";
$assoc_stmt = $conn->prepare($assoc_query);
if ($assoc_stmt) {
    $assoc_stmt->bind_param("i", $user_id);
    if ($assoc_stmt->execute()) {
        $assoc_result = $assoc_stmt->get_result();
    } else {
        error_log("User associations query execute failed: " . $assoc_stmt->error);
    }
    $assoc_stmt->close();
} else {
    error_log("User associations query prepare failed: " . $conn->error);
}

?>

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard</title>
    <link rel="stylesheet" href="association-admin/assets/css/styles.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7fc;
            margin: 0;
            padding: 0;
        }

        .main-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 10px 50px;
            margin-left: 260px;
        }

        .dashboard-container {
            width: 100%;
            max-width: 900px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-top: 40px;
            align-items: center;
        }

        .dashboard-container h1 {
            color: #333;
            font-size: 1.8rem;
            margin-bottom: 20px;
            font-weight: 500;
            text-align: center;
        }

        .dashboard-container p {
            font-size: 0.9rem;
            color: #666;
            line-height: 1.4;
        }

        .dashboard-container h2 {
            font-size: 1.4rem;
            margin-top: 30px;
            color: #1f3d7a;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 10px;
            font-weight: 500;
        }

        .swipe-container {
            display: flex;
            gap: 15px;
            margin-top: 15px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 10px;
            scroll-snap-type: x mandatory;
        }

        .swipe-container::-webkit-scrollbar {
            height: 8px;
        }

        .swipe-container::-webkit-scrollbar-track {
            background: #f4f7fc;
            border-radius: 4px;
        }

        .swipe-container::-webkit-scrollbar-thumb {
            background: #1f3d7a;
            border-radius: 4px;
            transition: background 0.3s ease;
        }

        .swipe-container::-webkit-scrollbar-thumb:hover {
            background: #005a87;
        }

        .card {
            flex: 0 0 auto;
            width: 280px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
            padding: 15px;
            scroll-snap-align: start;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.1);
        }

        .association-card {
            background: linear-gradient(135deg, #ff6b6b 0%, #ff8e53 100%);
            color: white;
        }

        .association-card strong {
            font-size: 1.1rem;
            color: white;
            margin-bottom: 8px;
        }

        .association-card p, .association-card em {
            font-size: 0.9rem;
            color: #fff;
            margin: 8px 0;
            line-height: 1.3;
        }

        .campaign-card {
            background: #1e1e1e;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 20px;
            border-radius: 12px;
            width: 280px;
            min-height: 300px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .campaign-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.2);
        }

        .campaign-card strong {
            font-size: 1.2rem;
            color: #fff;
            margin-bottom: 8px;
            font-weight: 600;
            line-height: 1.2;
            word-wrap: break-word;
        }

        .campaign-card hr {
            border: 0;
            height: 1px;
            background: #ccc;
            margin: 8px 0;
        }

        .campaign-card .dates {
            font-size: 1rem;
            color: #00ccff;
            margin: 6px 0;
            font-weight: 500;
        }

        .campaign-card p {
            font-size: 0.9rem;
            color: #ccc;
            margin: 6px 0;
            line-height: 1.4;
            flex-grow: 1;
        }

        .campaign-card a {
            background-color: #005a87;
            color: white;
            padding: 10px 18px;
            border-radius: 6px;
            transition: background-color 0.3s ease, color 0.3s ease;
            font-weight: 500;
            display: inline-block;
            margin-top: 12px;
            align-self: center;
            font-size: 0.9rem;
            text-decoration: none;
        }

        .campaign-card a:hover {
            background-color: #003d6b;
            color: white;
        }

        .transaction-table th, .transaction-table td {
            padding: 8px 10px;
            text-align: left;
            border: 1px solid #ddd;
            font-size: 0.8rem;
        }

        .transaction-table th {
            background-color: #333;
            color: white;
            font-weight: bold;
        }

        .transaction-table td {
            background-color: #f9f9f9;
            color: #333;
        }

        .transaction-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .transaction-table tr:nth-child(odd) {
            background-color: #fff;
        }

        .transaction-table tr:hover {
            background-color: #3a3a3a;
            color: white;
        }

        .transaction-table td, .transaction-table th {
            word-wrap: break-word;
        }

        .top-nav {
            background-color: #1f3d7a;
            width: 80%;
            max-width: 870px;
            color: #F1F1F1;
            font-family: 'Arial', sans-serif;
            font-size: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 18px;
            left: 300px;
            z-index: 1000;
            box-shadow: 0px 3px 10px rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid #ddd;
            height: 40px;
            position: fixed;
            top: 0;
            border-radius: 8px;
        }

        .top-nav h2 {
            font-size: 16px;
            font-weight: 700;
            margin: 0;
            color: #F1F1F1;
        }

        .user-info span {
            font-size: 14px;
            color: #E0E0E0;
        }

        .join-association-btn {
            display: inline-block;
            margin-bottom: 15px;
            text-decoration: none;
            color: white;
            background-color: #005a87;
            padding: 10px 18px;
            border-radius: 6px;
            transition: background-color 0.3s ease;
            font-weight: 500;
            text-align: center;
            font-size: 0.9rem;
        }

        .join-association-btn:hover {
            background-color: #003d6b;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 10px 20px;
                margin-left: 0;
            }

            .dashboard-container {
                max-width: 100%;
                padding: 20px;
            }

            .swipe-container {
                flex-direction: column;
                overflow-x: hidden;
            }

            .card {
                width: 100%;
                margin-bottom: 15px;
            }

            .top-nav {
                width: 90%;
                left: 10px;
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="top-nav">
            <h2>Member Dashboard</h2>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($user['full_name']); ?></span>
                <span> | Membership Category: <?php 
                    $query = "SELECT mc.category_name 
                              FROM users u 
                              JOIN membership_categories mc ON u.membership_category_id = mc.id 
                              WHERE u.id = ?";
                    $stmt = $conn->prepare($query);
                    if ($stmt) {
                        $stmt->bind_param("i", $user_id);
                        if ($stmt->execute()) {
                            $result = $stmt->get_result();
                            $row = $result->fetch_assoc();
                            $membership_category_name = $row['category_name'] ?? 'Not Assigned';
                        } else {
                            file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Execute Failed: " . $stmt->error . "\n", FILE_APPEND);
                            $membership_category_name = 'Error';
                        }
                        $stmt->close();
                    } else {
                        file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Prepare Failed: " . $conn->error . "\n", FILE_APPEND);
                        $membership_category_name = 'Error';
                    }
                    echo htmlspecialchars($membership_category_name);
                ?></span>
            </div>
        </div>  

        <div class="dashboard-container">
            <h2>Your Associations</h2>
            <a href="/osnet/registration/join_association.php" class="join-association-btn">Join New Association</a>
            <?php if ($assoc_result && $assoc_result->num_rows > 0) { ?>
                <div class="swipe-container">
                    <?php while ($association = $assoc_result->fetch_assoc()) { ?>
                        <div class="card association-card">
                            <strong><?php echo htmlspecialchars($association['association_name']); ?></strong>
                            <p><em>Years of Attendance: <?php echo htmlspecialchars($association['years_of_attendance'] ?? 'Not specified'); ?></em></p>
                        </div>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <p>You are not affiliated with any associations.</p>
            <?php } ?>

            <h2>Ongoing Campaigns</h2>
            <?php if ($campaign_result && $campaign_result->num_rows > 0) { ?>
                <div class="swipe-container">
                    <?php while ($campaign = $campaign_result->fetch_assoc()) { 
                        $remaining_amount = $campaign['fee'] - $campaign['total_donated'];
                    ?>
                        <div class="card campaign-card">
                            <strong><?php echo htmlspecialchars($campaign['title']); ?></strong>
                            <hr>
                            <div class="dates">Start: <?php echo htmlspecialchars($campaign['start_date']); ?> | End: <?php echo htmlspecialchars($campaign['end_date']); ?></div>
                            <p><?php echo htmlspecialchars($campaign['description']); ?></p>
                            <p><strong>Target Amount: </strong>UGX <?php echo number_format($campaign['fee'], 2); ?></p>
                            <p><strong>Total Donated: </strong>UGX <?php echo number_format($campaign['total_donated'], 2); ?></p>
                            <p><strong>Remaining Amount: </strong>UGX <?php echo number_format($remaining_amount, 2); ?></p>
                            <a href="../donations/donate?campaign_id=<?php echo $campaign['id']; ?>">Donate</a>
                        </div>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <p>No ongoing campaigns for your association.</p>
            <?php } ?>

            <h2>Transaction History for Upgrades</h2>
            <?php if ($transaction_result && $transaction_result->num_rows > 0) { ?>
                <table class="transaction-table">
                    <thead>
                        <tr>
                            <th>Amount (UGX)</th>
                            <th>Membership Category</th>
                            <th>Association Name</th>
                            <th>Payment Status</th>
                            <th>Payment Date</th>
                            <th>Transaction ID</th>
                            <th>Merchant Reference</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($transaction = $transaction_result->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo number_format($transaction['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($transaction['category_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($transaction['association_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($transaction['payment_status']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['payment_date'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($transaction['transaction_tracking_id'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($transaction['merchant_reference'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($transaction['created_at']); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } else { ?>
                <p>No transactions found.</p>
            <?php } ?>
        </div>
    </div>
</body>
</html>

<?php
// Only close the connection, as statements are closed inline
if (isset($conn)) $conn->close();
?>