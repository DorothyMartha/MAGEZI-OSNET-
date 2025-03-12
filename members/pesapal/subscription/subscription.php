<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require_once '../../../libs/db-connection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    file_put_contents('../pesapal/debug_log.txt', date('Y-m-d H:i:s') . " - Redirect to index.php: User not logged in\n", FILE_APPEND);
    $_SESSION['error_message'] = "You must be logged in to access this page.";
    header("Location: .../../../index.php");
    exit();
}

// Get the logged-in user's ID
$user_id = $_SESSION['user_id'];

// Fetch user details from the users table
$query = "SELECT association_id, full_name, email, phone_number FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    file_put_contents('../pesapal/debug_log.txt', date('Y-m-d H:i:s') . " - Error: User not found for user_id: $user_id\n", FILE_APPEND);
    die("Error: User not found.");
}

$association_id = $user['association_id'];
$full_name = $user['full_name'];
$email = $user['email'];
$phone_number = $user['phone_number'];

// Fetch all associations the user belongs to
$all_association_ids = [];

if ($association_id !== null) {
    $all_association_ids[] = $association_id;
}

$query = "SELECT association_id FROM user_associations WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $all_association_ids[] = $row['association_id'];
}
$stmt->close();

$all_association_ids = array_unique($all_association_ids);
if (empty($all_association_ids)) {
    file_put_contents('../pesapal/debug_log.txt', date('Y-m-d H:i:s') . " - Redirect to dashboard: No associations for user_id: $user_id\n", FILE_APPEND);
    $_SESSION['error_message'] = "You are not associated with any associations. Please contact support.";
    header("Location: ../../dashboard.php");
    exit();
}

// Fetch association details (e.g., names) for the dropdown
$associations = [];
$query = "SELECT id, association_name FROM alumni_associations WHERE id IN (" . implode(',', array_fill(0, count($all_association_ids), '?')) . ")";
$stmt = $conn->prepare($query);
$stmt->bind_param(str_repeat("i", count($all_association_ids)), ...$all_association_ids);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $associations[$row['id']] = $row['association_name'];
}
$stmt->close();

// Fetch membership categories for all associations the user belongs to
$membership_categories = [];
$query = "SELECT id, category_name, fee, association_id FROM membership_categories WHERE association_id IN (" . implode(',', array_fill(0, count($all_association_ids), '?')) . ")";
$stmt = $conn->prepare($query);
$stmt->bind_param(str_repeat("i", count($all_association_ids)), ...$all_association_ids);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $membership_categories[$row['association_id']][] = $row;
}
$stmt->close();

if (empty($membership_categories)) {
    file_put_contents('../pesapal/debug_log.txt', date('Y-m-d H:i:s') . " - Redirect to dashboard: No membership categories for user_id: $user_id\n", FILE_APPEND);
    $_SESSION['error_message'] = "No membership categories available for your associations. Please contact support.";
    header("Location: ../../dashboard.php");
    
}

// Handle form submission to initiate payment tracking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['plan']) && !empty($_POST['plan'])) {
    file_put_contents('../pesapal/debug_log.txt', date('Y-m-d H:i:s') . " - Form submitted with POST: " . print_r($_POST, true) . "\n", FILE_APPEND);

    $plan_id = filter_input(INPUT_POST, 'plan', FILTER_VALIDATE_INT);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $selected_association_id = filter_input(INPUT_POST, 'association_id', FILTER_VALIDATE_INT);

    file_put_contents('../pesapal/debug_log.txt', date('Y-m-d H:i:s') . " - Validated: plan_id=$plan_id, amount=$amount, association_id=$selected_association_id\n", FILE_APPEND);

    if ($plan_id === false || $amount === false || $selected_association_id === false) {
        file_put_contents('../pesapal/debug_log.txt', date('Y-m-d H:i:s') . " - Redirect to dashboard: Invalid input (plan_id, amount, or association_id)\n", FILE_APPEND);
        $_SESSION['error_message'] = "Invalid plan, amount, or association. Please try again.";
        header("Location: ../../dashboard.php");
        
    }

    // Verify the plan_id exists, belongs to the selected association, and the amount matches
    $query = "SELECT fee FROM membership_categories WHERE id = ? AND association_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $plan_id, $selected_association_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $plan = $result->fetch_assoc();
    $stmt->close();

    if (!$plan || $plan['fee'] != $amount) {
        file_put_contents('../pesapal/debug_log.txt', date('Y-m-d H:i:s') . " - Redirect to dashboard: Plan or amount mismatch\n", FILE_APPEND);
        $_SESSION['error_message'] = "Invalid membership plan or amount mismatch. Please try again.";
        header("Location: ../../dashboard.php");
        
    }

    $merchant_reference = $_POST['merchant_reference'] ?? time() . rand(10 * 445, 100 * 98);

    file_put_contents('../pesapal/payment_debug.txt', date('Y-m-d H:i:s') . " - Received POST: plan=$plan_id, amount=$amount, reference=$merchant_reference, association_id=$selected_association_id\n", FILE_APPEND);

    // Save the payment details in the subscriptions table
    $query = "INSERT INTO subscriptions (user_id, amount, payment_status, merchant_reference, plan_id, association_id) VALUES (?, ?, 'PENDING', ?, ?, ?)";
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        file_put_contents('../pesapal/debug_log.txt', date('Y-m-d H:i:s') . " - Redirect to dashboard: Prepare failed: " . $conn->error . "\n", FILE_APPEND);
        file_put_contents('../pesapal/payment_error.log', date('Y-m-d H:i:s') . " - Prepare failed: " . $conn->error . "\n", FILE_APPEND);
        $_SESSION['error_message'] = "Database error. Please try again.";
        header("Location: ../../dashboard.php");
        exit();
    }

    $stmt->bind_param("idsii", $user_id, $amount, $merchant_reference, $plan_id, $selected_association_id);
    if ($stmt->execute()) {
        file_put_contents('../pesapal/payment_log.txt', date('Y-m-d H:i:s') . " - Payment saved: user_id=$user_id, amount=$amount, reference=$merchant_reference, plan_id=$plan_id, association_id=$selected_association_id\n", FILE_APPEND);

        // Prepare data for iframe.php via POST using a hidden form
        $full_name_parts = explode(' ', $full_name); // Split full_name into first_name and last_name
        $first_name = $full_name_parts[0] ?? $full_name;
        $last_name = implode(' ', array_slice($full_name_parts, 1)) ?? '';
        ?>
        <!-- Hidden form to submit data to iframe.php -->
        <form id="paymentForm" action="../iframe.php" method="post">
            <input type="hidden" name="amount" value="<?php echo htmlspecialchars($amount); ?>">
            <input type="hidden" name="description" value="Upgrading Membership">
            <input type="hidden" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>"> 
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            <input type="hidden" name="phone_number" value="<?php echo htmlspecialchars($phone_number); ?>">
            <input type="hidden" name="currency" value="UGX">
            <input type="hidden" name="reference" value="<?php echo htmlspecialchars($merchant_reference); ?>">
            <input type="hidden" name="plan" value="<?php echo htmlspecialchars($plan_id); ?>">
        </form>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('paymentForm').submit();
            });
        </script>
        <?php 
    } else {
        file_put_contents('../pesapal/debug_log.txt', date('Y-m-d H:i:s') . " - Redirect to dashboard: Execute failed: " . $stmt->error . "\n", FILE_APPEND);
        file_put_contents('../pesapal/payment_error.log', date('Y-m-d H:i:s') . " - Execute failed: " . $stmt->error . "\n", FILE_APPEND);
        $_SESSION['error_message'] = "Failed to save your payment. Please try again.";
        header("Location: ../../dashboard.php");
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesapal Payment</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #fff;
            margin: 0;
            padding: 0;
        }

        .card-body {
            background-color: #efefef;
            padding: 20px;
            max-width: 800px;
            margin: 30px auto;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        form {
            display: flex;
            flex-direction: column;
        }

        p {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            text-align: center;
        }

        table {
            width: 100%;
            border-spacing: 10px;
            border-collapse: separate;
        }

        td {
            padding: 8px;
        }

        label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }

        input[type="text"], select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }

        input[type="text"]:focus, select:focus {
            border-color: #007bff;
            outline: none;
        }

        input[type="submit"] {
            padding: 10px 15px;
            background-color: #1f3d7a;
            color: #fff;
            font-size: 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 20px;
        }

        input[type="submit"]:hover {
            background-color: #163b61;
        }

        hr {
            border: 0;
            height: 1px;
            background-color: #ccc;
            margin: 15px 0;
        }

        .hidden {
            display: none;
        }

        @media (max-width: 600px) {
            .card-body {
                padding: 15px;
            }

            input[type="text"], select {
                padding: 10px;
            }

            input[type="submit"] {
                padding: 12px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="card-body">
        <form id="rightcol" action="/osnet/members/pesapal/subscription/subscription.php" method="post" class="rounded5">
            <p>PESAPAL PAYMENTS</p>
            <input type="hidden" name="merchant_reference" value="<?php echo time() . rand(10 * 445, 100 * 98); ?>">
            <table>
            <tr>
                    <td>UserName:</td>
                    <td><input type="text" name="full_name" value="<?php echo $full_name; ?>" readonly /></td>
                </tr>
                <tr>
                    <td><label>Association:</label></td>
                    <td>
                        <select name="association_id" id="association_id" style="width: 93%;" required>
                            <option value="">Select an association...</option>
                            <?php foreach ($associations as $assoc_id => $assoc_name) { ?>
                                <option value="<?php echo $assoc_id; ?>">
                                    <?php echo htmlspecialchars($assoc_name); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><label>Plan:</label></td>
                    <td>
                        <select name="plan" id="plan" style="width: 93%;" required>
                            <option value="">Select a membership category...</option>
                            <!-- Options will be populated dynamically by JavaScript -->
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <td>Email Address:</td>
                    <td><input type="text" name="email" value="<?php echo htmlspecialchars($email); ?>" readonly /></td>
                </tr>
                <tr>
                    <td>Phone Number:</td>
                    <td><input type="text" name="phone_number" value="<?php echo htmlspecialchars($phone_number); ?>" readonly /></td>
                </tr>
                <tr>
                    <td>Currency:</td>
                    <td><input type="text" name="currency" value="UGX" readonly /></td>
                </tr>
                <tr>
                    <td>Amount:</td>
                    <td><input type="text" name="amount" id="amt" value="" readonly /></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <hr />
                    </td>
                </tr>
                <tr>
                    <td>Description:</td>
                    <td><input type="text" name="description" value="Upgrading Membership" readonly /></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="submit" value="Make Payment" class="btn btn-primary" />
                        <div id="loading" style="display: none; text-align: center; margin-top: 10px;">
                            Processing payment, please wait...
                        </div>
                    </td>
                </tr>
            </table>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Form submission feedback
        const form = document.querySelector('#rightcol');
        form.addEventListener('submit', function () {
            document.getElementById('loading').style.display = 'block';
            document.querySelector('input[type="submit"]').disabled = true;
        });

        // Pre-load membership categories for all associations
        const membershipCategories = <?php echo json_encode($membership_categories); ?>;

        const associationDropdown = document.getElementById('association_id');
        const planDropdown = document.getElementById('plan');
        const amountField = document.getElementById('amt');

        if (!associationDropdown || !planDropdown || !amountField) {
            console.error("Required elements not found!");
            return;
        }

        // Update plan dropdown when association changes
        associationDropdown.addEventListener('change', function () {
            const selectedAssociationId = this.value;
            console.log("Association selected:", selectedAssociationId);

            // Clear current options
            planDropdown.innerHTML = '<option value="">Select a membership category...</option>';

            // Populate plans for the selected association
            if (selectedAssociationId && membershipCategories[selectedAssociationId]) {
                membershipCategories[selectedAssociationId].forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.id;
                    option.setAttribute('data-fee', category.fee);
                    option.textContent = `${category.category_name} - UGX ${parseFloat(category.fee).toFixed(2)}`;
                    planDropdown.appendChild(option);
                });
            }

            // Reset amount field
            amountField.value = '';
        });

        // Update amount field when plan changes
        planDropdown.addEventListener('change', function () {
            console.log("Plan dropdown changed");
            const selectedOption = this.options[this.selectedIndex];
            const fee = selectedOption.getAttribute('data-fee');
            console.log("Fee:", fee);
            amountField.value = fee || '';
            console.log("Amount field updated:", amountField.value);
        });
    });
    </script>
</body>
</html>