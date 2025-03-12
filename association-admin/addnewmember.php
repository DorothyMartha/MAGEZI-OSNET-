<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once '../libs/db-connection.php';

// Add logging call (assuming admin is logged in)
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    die("Unauthorized access. Please log in as an admin.");
}

// Log the session user_id for debugging
error_log("Session user_id: " . $_SESSION['user_id']);

// Check if user has association admin role
$admin_role_query = "SELECT role FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $admin_role_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$admin_role_result = mysqli_stmt_get_result($stmt);
$admin_role_row = mysqli_fetch_row($admin_role_result);
$admin_role = $admin_role_row ? $admin_role_row[0] : null;
mysqli_stmt_close($stmt);

if ($admin_role !== 'AssociationAdmin') {
    die("Access denied. Only association admins can add members.");
}

// Fetch admin's association ID from the users table
$admin_assoc_query = "SELECT association_id FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $admin_assoc_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$admin_assoc_result = mysqli_stmt_get_result($stmt);
$admin_assoc_row = mysqli_fetch_row($admin_assoc_result);
$admin_association_id = $admin_assoc_row ? $admin_assoc_row[0] : null;
mysqli_stmt_close($stmt);

if (!$admin_association_id) {
    // Log the issue for debugging
    error_log("Admin with user_id {$_SESSION['user_id']} is not associated with any association.");
    // Redirect to join an association or display a better error message
    header("Location: /osnet/registration/join_association.php?error=not_associated");
    exit();
}

// Log successful fetch of association ID
error_log("Admin association_id: " . $admin_association_id);

// Fetch countries from the database
try {
    $sqlCountries = "SELECT name FROM country";
    $countries_result = mysqli_query($conn, $sqlCountries);
    $countries = mysqli_fetch_all($countries_result, MYSQLI_ASSOC);
} catch (Exception $e) {
    $error = "Error fetching countries: " . mysqli_error($conn);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize registration inputs
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $occupation = trim($_POST['occupation']);
    $country_of_residence = trim($_POST['country_of_residence']);
    $marital_status = $_POST['marital_status'];

    // Fetch the default membership category
    $sqlDefaultCategory = "SELECT id FROM membership_categories WHERE is_default = 1 LIMIT 1";
    $defaultCategory_result = mysqli_query($conn, $sqlDefaultCategory);
    $defaultCategory = mysqli_fetch_assoc($defaultCategory_result);
    $membership_category_id = $defaultCategory ? $defaultCategory['id'] : null;

    // Check if user already exists
    $sqlCheckUser = "SELECT id FROM users WHERE email = ? OR full_name = ?";
    $stmt = mysqli_prepare($conn, $sqlCheckUser);
    mysqli_stmt_bind_param($stmt, "ss", $email, $full_name);
    mysqli_stmt_execute($stmt);
    $check_result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($check_result) > 0) {
        $error = "Error: User with this email or username already exists.";
    } elseif ($membership_category_id === null) {
        $error = "Error: Default membership category not found.";
    }
    mysqli_stmt_close($stmt);

    // If no errors, proceed with registration
    if (empty($error) && $membership_category_id !== null) {
        // Generate verification token and expiry
        $verification_token = bin2hex(random_bytes(32));
        $token_expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

        try {
            $sqlUser = "INSERT INTO users 
                        (full_name, email, phone_number, occupation, 
                         marital_status, association_id, membership_category_id, 
                         country_of_residence, verification_token, token_expiry, role, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Member', 'pending')";

            $stmt = mysqli_prepare($conn, $sqlUser);
            if ($stmt === false) {
                throw new Exception("Failed to prepare statement: " . mysqli_error($conn));
            }

            if (empty($full_name) || empty($email) || empty($phone_number) || 
                empty($occupation) || empty($country_of_residence) || empty($marital_status)) {
                throw new Exception("Missing required user data.");
            }

            // Bind parameters with appropriate types
            mysqli_stmt_bind_param($stmt, "sssssiiiss", $full_name, $email, $phone_number, $occupation, $marital_status, $admin_association_id, $membership_category_id, $country_of_residence, $verification_token, $token_expiry);
            mysqli_stmt_execute($stmt);

            $user_id = mysqli_insert_id($conn);

            // Send email with verification link
            $subject = "Set Your Password and Upload Profile for MAGEZI OSNET";
            $message = "Hello $full_name,\n\nAn association admin has registered you on MAGEZI OSNET. Please set your password and upload your profile picture by clicking the link below:\n\nhttps://magezi.ac.ug/osnet/setpassword.php?token=$verification_token\n\nThis link will expire in 24 hours.\n\nBest regards,\nMAGEZI OSNET Team";
            $headers = "From: \"MAGEZI OSNET Admin\" <no-reply@magezi.ac.ug>\r\n";
            $headers .= "Reply-To: \"MAGEZI OSNET Support\" <support@magezi.ac.ug>\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

            if (mail($email, $subject, $message, $headers)) {
                header("Location: /osnet/index.php?registration=success&email_sent=true");
                exit();
            } else {
                $error = "Registration successful, but failed to send email. Please contact support.";
            }

            mysqli_stmt_close($stmt);
        } catch (Exception $e) {
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Add Member - MAGEZI OSNET</title>
    <link rel="stylesheet" href="assets/css/registration.css">
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            padding: 1rem;
            background: #f3f4f6;
        }

        .container {
            display: flex;
            max-width: 1200px;
            width: 100%;
            min-height: 80vh;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .form-section-wrapper {
            flex: 0 0 60%;
            padding: 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .image-section {
            flex: 0 0 40%;
            position: relative;
            background: url('https://images.unsplash.com/photo-1501785888041-af3ef285b470?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') no-repeat center center;
            background-size: cover;
        }

        .image-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(30, 58, 138, 0.7), rgba(20, 184, 166, 0.5));
            z-index: 1;
        }

        .image-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #ffffff;
            text-align: center;
            z-index: 2;
            padding: 1rem;
        }

        .image-text h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(90deg, #ffffff, #d1fae5);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .image-text p {
            font-size: 1.2rem;
            color: #e5e7eb;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 2rem;
            color: #1e3a8a;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: #6b7280;
            font-size: 1rem;
        }

        label {
            font-weight: 600;
            color: #1f2937;
            font-size: 1rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        input,
        select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
            color: #1f2937;
            background: #ffffff;
            transition: border-color 0.3s ease, box-shadow 0.3s ease, transform 0.2s ease;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #1e3a8a;
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.2);
            transform: translateY(-2px);
        }

        .error-message {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: #fee2e2;
            color: #dc2626;
            border-radius: 8px;
            border-left: 4px solid #dc2626;
            text-align: left;
            font-size: 0.95rem;
        }

        .submit-btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(90deg, #1e3a8a 0%, #14b8a6 100%);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(20, 184, 166, 0.3);
        }

        .submit-btn.loading {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .submit-btn.loading::after {
            content: "⏳";
            margin-left: 0.5rem;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                max-width: 100%;
                min-height: auto;
            }

            .form-section-wrapper,
            .image-section {
                flex: 0 0 100%;
                height: 400px;
            }

            .header h1 {
                font-size: 1.5rem;
            }

            .image-text h2 {
                font-size: 2rem;
            }

            .image-text p {
                font-size: 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="form-section-wrapper">
            <div class="header">
                <h1>Add Member</h1>
                <p>Register a new member for your association</p>
            </div>

            <!-- Display error message if any -->
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="post" action="addnewmember.php">
                <div class="form-section">
                    <label for="full_name">UserName</label>
                    <input type="text" name="full_name" id="full_name" required placeholder="Enter the member's Username">

                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" required placeholder="member.email@example.com">

                    <label for="phone_number">Phone Number</label>
                    <input type="tel" name="phone_number" id="phone_number" required placeholder="Enter the member's phone number">
                </div>

                <div class="form-section">
                    <label for="occupation">Occupation</label>
                    <input type="text" name="occupation" id="occupation" required placeholder="Member's current occupation">

                    <label for="country_of_residence">Country of Residence</label>
                    <select name="country_of_residence" id="country_of_residence" required>
                        <option value="">Select the member's country</option>
                        <?php foreach ($countries as $country): ?>
                            <option value="<?= htmlspecialchars($country['name']); ?>">
                                <?= htmlspecialchars($country['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="marital_status">Marital Status</label>
                    <select name="marital_status" id="marital_status" required>
                        <option value="">Select status</option>
                        <option value="Single">Single</option>
                        <option value="Married">Married</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <button type="submit" class="submit-btn">Complete Registration</button>
            </form>
        </div>
        <div class="image-section">
            <div class="image-text">
                <h2>Register a New Member</h2>
                <p>Welcome to MAGEZI OSNET</p>
            </div>
        </div>
    </div>

    <script>
    // No association selection or file upload JavaScript needed
    const form = document.querySelector('form');
    const submitBtn = document.querySelector('.submit-btn');

    // Add loading spinner on form submission
    form.addEventListener('submit', (e) => {
        submitBtn.classList.add('loading');
        submitBtn.textContent = 'Registering...';
        setTimeout(() => {
            submitBtn.classList.remove('loading');
            submitBtn.textContent = 'Complete Registration';
        }, 2000);
    });
    </script>
</body>

</html>
<?php
// Close the database connection
if (isset($conn)) mysqli_close($conn);
?>