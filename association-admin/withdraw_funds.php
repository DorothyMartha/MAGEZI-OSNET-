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

// Check if admin is logged in
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

// Fetch total completed subscription funds for the association
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

// Handle withdrawal request submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_withdrawal'])) {
    $amount = floatval($_POST['amount']);

    // Validate amount
    if ($amount <= 0) {
        $error = "Amount must be greater than zero.";
    } elseif ($amount > $totalFunds) {
        $error = "Requested amount (UGX " . number_format($amount, 2) . ") exceeds available funds (UGX " . number_format($totalFunds, 2) . ").";
    } else {
        $query = "INSERT INTO withdrawals (association_id, requested_by, amount, status, requested_at) 
                  VALUES (?, ?, ?, 'Pending', NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iid", $adminAssociation, $admin_id, $amount);
        
        if ($stmt->execute()) {
            $success = "Withdrawal request of UGX " . number_format($amount, 2) . " submitted successfully. Awaiting superAdmin approval.";
        } else {
            $error = "Failed to submit withdrawal request. Please try again.";
        }
    }
}

// Fetch withdrawal requests for this association
$query = "SELECT id, amount, status, requested_at, processed_at 
          FROM withdrawals 
          WHERE association_id = ? 
          ORDER BY requested_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $adminAssociation);
$stmt->execute();
$withdrawalsResult = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdraw Funds</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f4f7fa;
        }

        .main-content {
            margin-left: 350px;
            padding: 20px;
            max-width: 1000px;
            margin-top: 80px;
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

        h3 {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
        }

        .funds-info {
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            font-size: 16px;
            color: #34495e;
        }

        .withdrawals-container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .withdrawals-container h4 {
            font-size: 18px;
            color: #34495e;
            margin: 0 0 15px 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            font-size: 14px;
            color: #34495e;
            font-weight: 600;
            margin-bottom: 5px;
        }

        input[type="number"] {
            width: 100%;
            padding: 8px 12px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }

        input[type="number"]:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.5);
        }

        .submit-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .submit-btn:hover {
            background-color: #2980b9;
        }

        .submit-btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        .error-message, .success-message {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .note {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .table-container {
            max-height: 400px;
            overflow-y: auto;
            overflow-x: auto;
            width: 100%;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            min-width: 600px;
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

        .status-pending {
            color: #e67e22;
        }

        .status-approved {
            color: #27ae60;
            font-weight: bold;
        }

        .status-rejected {
            color: #e74c3c;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .withdrawals-container {
                max-width: 100%;
            }
        }
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
            <h2>Withdraw Funds</h2>
            <div class="user-info">
                <span>Welcome, Admin</span>
            </div>
        </div>

        <h3>Withdraw Funds</h3>

        <div class="funds-info">
            Available Subscription Funds: <strong>UGX <?php echo number_format($totalFunds, 2); ?></strong>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="withdrawals-container">
            <h4>Request a Withdrawal from Subscriptions</h4>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="withdrawalForm">
                <div class="form-group">
                    <label for="amount">Withdrawal Amount (UGX):</label>
                    <input type="number" id="amount" name="amount" step="0.01" min="0.01" max="<?php echo $totalFunds; ?>" placeholder="e.g., 1000" required>
                    <div class="note">Enter an amount up to UGX <?php echo number_format($totalFunds, 2); ?>.</div>
                </div>
                <button type="submit" name="submit_withdrawal" class="submit-btn" id="submitBtn">Submit Request</button>
            </form>

            <h4>Subscription Funds Withdrawal Requests</h4>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Amount (UGX)</th>
                            <th>Status</th>
                            <th>Requested At</th>
                            <th>Processed At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($withdrawalsResult->num_rows > 0): ?>
                            <?php while ($row = $withdrawalsResult->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo number_format($row['amount'], 2); ?></td>
                                    <td class="status-<?php echo strtolower($row['status']); ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                        <?php if ($row['status'] === 'Approved') echo ' (Successful)'; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($row['requested_at']))); ?></td>
                                    <td>
                                        <?php 
                                        if ($row['processed_at']) {
                                            echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($row['processed_at'])));
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5">No withdrawal requests found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('withdrawalForm').addEventListener('input', function() {
            const amountInput = document.getElementById('amount');
            const submitBtn = document.getElementById('submitBtn');
            const maxFunds = <?php echo $totalFunds; ?>;
            const amount = parseFloat(amountInput.value) || 0;

            if (amount > maxFunds || amount <= 0) {
                submitBtn.disabled = true;
                if (amount > maxFunds) {
                    amountInput.setCustomValidity('Amount exceeds available funds (UGX <?php echo number_format($totalFunds, 2); ?>)');
                } else if (amount <= 0) {
                    amountInput.setCustomValidity('Amount must be greater than zero');
                }
            } else {
                submitBtn.disabled = false;
                amountInput.setCustomValidity('');
            }
        });

        document.getElementById('withdrawalForm').addEventListener('submit', function(e) {
            const amount = parseFloat(document.getElementById('amount').value) || 0;
            const maxFunds = <?php echo $totalFunds; ?>;
            if (amount > maxFunds || amount <= 0) {
                e.preventDefault();
                alert('Please enter a valid amount between 0.01 and UGX <?php echo number_format($totalFunds, 2); ?>.');
            }
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>