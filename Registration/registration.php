<?php
// registration.php

// Database connection parameters
$host = 'localhost';
$db   = 'magezi_osnet';
$db_user = 'root';
$db_pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize registration inputs
    $full_name           = trim($_POST['full_name']);
    $email               = trim($_POST['email']);
    $phone_number        = trim($_POST['phone_number']);
    $password            = $_POST['password'];
    $confirm_password    = $_POST['confirm_password'];
    $years_of_attendance = trim($_POST['years_of_attendance']);
    $occupation          = trim($_POST['occupation']);
    $country_of_residence = trim($_POST['country_of_residence']);
    $marital_status      = $_POST['marital_status'];
    $association_id      = intval($_POST['association_id']); // Required during registration

    // Check if the user opted to subscribe during registration
    $subscribeNow = isset($_POST['subscribe']) && $_POST['subscribe'] === 'yes';

    // Basic validation: ensure passwords match
    if ($password !== $confirm_password) {
        echo "Error: Passwords do not match.";
        exit;
    }

    // Hash the password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Begin transaction
        $pdo->beginTransaction();

        // Insert new user into the `users` table with membership status "Registered" by default
        $sqlUser = "INSERT INTO users 
                    (full_name, email, phone_number, password_hash, years_of_attendance, occupation, 
                    country_of_residence, marital_status, role, membership, association_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Member', 'Registered', ?)";
        $stmtUser = $pdo->prepare($sqlUser);
        $stmtUser->execute([
            $full_name,
            $email,
            $phone_number,
            $password_hash,
            $years_of_attendance,
            $occupation,
            $country_of_residence,
            $marital_status,
            $association_id
        ]);

        // Retrieve the new user's ID
        $user_id = $pdo->lastInsertId();

        // If the user opted to subscribe, process the payment through Pesapal
        if ($subscribeNow) {
            // ---- Pesapal Payment Integration Placeholder ----
            // Here you would integrate the Pesapal payment API for premium membership
            // For this demo, we simulate the payment as successful
            $paymentSuccessful = true;
            // ---------------------------------------------------

            if ($paymentSuccessful) {
                // Insert subscription record into `subscriptions` table
                $sqlSub = "INSERT INTO subscriptions (user_id, association_id, membership_type, subscription_date)
                           VALUES (?, ?, 'Premium', NOW())";
                $stmtSub = $pdo->prepare($sqlSub);
                $stmtSub->execute([$user_id, $association_id]);

                // Update the user's membership status to 'Premium'
                $sqlUpdate = "UPDATE users SET membership = 'Premium' WHERE id = ?";
                $stmtUpdate = $pdo->prepare($sqlUpdate);
                $stmtUpdate->execute([$user_id]);
            } else {
                throw new Exception("Payment processing via Pesapal failed. Please try again.");
            }
        }

        // Commit the transaction
        $pdo->commit();

        // Redirect to success page or show success message
        echo "<script>alert('Registration" . ($subscribeNow ? " and premium subscription" : "") . " successful!');</script>";
    } catch (Exception $e) {
        // Roll back the transaction if any error occurs
        $pdo->rollBack();
        echo "Registration failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAGEZI_OSNET Registration & Subscription</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            min-height: 100vh;
            padding: 2rem;
            margin: 0;
        }

        .container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto;
            overflow-y: auto;
            max-height: 90vh;
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f2f5;
        }

        .header h1 {
            color: #1a73e8;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: #666;
            font-size: 0.9rem;
        }

        .form-section {
            margin-bottom: 1.5rem;
        }

        label {
            font-weight: 500;
            display: block;
            margin-bottom: 0.5rem;
            color: #444;
        }

        input,
        select {
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border: 1.5px solid #ddd;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: border-color 0.2s;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #1a73e8;
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            margin: 1rem 0;
        }

        .checkbox-container input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
        }

        .subscription-fields {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }

        .benefits-list {
            list-style-type: none;
            margin: 15px 0;
            padding: 0;
        }

        .benefits-list li {
            padding: 8px 0;
            padding-left: 25px;
            position: relative;
            color: #444;
        }

        .benefits-list li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #1a73e8;
        }

        .submit-btn {
            background: #1a73e8;
            color: white;
            padding: 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
            font-weight: 500;
            transition: background-color 0.2s;
        }

        .submit-btn:hover {
            background: #1557b0;
        }

        .premium-note {
            font-size: 0.9rem;
            color: #666;
            margin-top: 10px;
            text-align: center;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .container {
                padding: 1.5rem;
            }
        }
    </style>
    <script>
        function toggleSubscriptionFields() {
            var subscriptionFields = document.getElementById('subscriptionFields');
            subscriptionFields.style.display = document.getElementById('subscribe').checked ? 'block' : 'none';
        }
    </script>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>MAGEZI_OSNET Alumni Registration</h1>
            <p>Join our community and stay connected</p>
        </div>

        <form method="post" action="registration.php">
            <div class="form-section">
                <label>Full Name</label>
                <input type="text" name="full_name" required placeholder="Enter your full name">

                <label>Email</label>
                <input type="email" name="email" required placeholder="your.email@example.com">

                <label>Phone Number</label>
                <input type="tel" name="phone_number" required placeholder="Enter your phone number">

                <label>Password</label>
                <input type="password" name="password" required placeholder="Create a strong password">

                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required placeholder="Confirm your password">
            </div>

            <div class="form-section">
                <label>Alumni Association</label>
                <select name="association_id" required>
                    <option value="">Select your alumni association</option>
                    <option value="1">Makerere University Alumni Association</option>
                    <option value="2">Uganda Christian University Alumni</option>
                    <option value="3">Mbale Secondary School Old Students</option>
                    <option value="4">Makerere University's School of Public Health Alumni Association</option>
                    <option value="5">Muljibhai Madhvani Alumni Association (MASS)</option>
                    <option value="6">International School of Uganda Alumni Association</option>
                    <option value="7">Ndejje University Alumni Association</option>
                    <option value="8">Law Development Centre Alumni Association</option>
                    <option value="9">Tororo Girls' School Alumni Association</option>
                    <option value="10">Uganda Martyrs Old Students Association Namugongo</option>
                </select>

                <label>Years of Attendance</label>
                <input type="text" name="years_of_attendance" required placeholder="e.g., 2015-2019">

                <label>Occupation</label>
                <input type="text" name="occupation" required placeholder="Current occupation">

                <label>Country of Residence</label>
                <input type="text" name="country_of_residence" required placeholder="Enter your country">

                <label>Marital Status</label>
                <select name="marital_status" required>
                    <option value="">Select status</option>
                    <option value="Single">Single</option>
                    <option value="Married">Married</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="form-section">
                <div class="checkbox-container">
                    <input type="checkbox" name="subscribe" value="yes" id="subscribe" onclick="toggleSubscriptionFields()">
                    <label for="subscribe">Upgrade to Premium Membership</label>
                </div>

                <div id="subscriptionFields" class="subscription-fields" style="display: none;">
                    <h3 style="color: #1a73e8; margin-bottom: 10px;">Premium Membership Benefits</h3>
                    <ul class="benefits-list">
                        <li>Exclusive access to networking events</li>
                        <li>Career development resources and workshops</li>
                        <li>Mentorship program participation</li>
                        <li>Full alumni directory access</li>
                        <li>Monthly newsletter subscription</li>
                        <li>Priority registration for alumni events</li>
                    </ul>
                    <p class="premium-note">Premium membership payment will be processed securely via Pesapal</p>
                </div>
            </div>

            <button type="submit" class="submit-btn">Complete Registration</button>
        </form>
    </div>
</body>

</html>