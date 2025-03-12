<?php
ob_start();
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$servername = "pdb1992.awardspace.com";
$username = "ivnape_osnet";
$password = "?K4mp4l4311";
$database = "ivnape_osnet";

$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Get user IP address
$ip_address = $_SERVER['REMOTE_ADDR'];

$error = ""; // Initialize error message

// Handle the login logic (authentication)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize login inputs
    // Retrieve and sanitize login inputs
    $login_input = trim($_POST['full_name']);  // Can be full_name or email
    $password = $_POST['password'];

    // Query to check if the user exists using full_name or email
    $sql = "SELECT id, password_hash, role FROM users WHERE full_name = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $login_input, $login_input);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Start session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        // Redirect based on user role
        switch ($user['role']) {
            case 'SuperAdmin':
                header("Location: admin-dashboard/admin-dashboard.php");
                break;
            case 'AssociationAdmin':
                header("Location: association-admin/associationadmin_dashboard.php");
                break;
            case 'Member':
            default:
                header("Location: members/dashboard.php");
                break;
        }
        exit();
    } else {
        $error = "Invalid credentials, please try again.";
    }
}
ob_end_flush(); 
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Magezi Osnet</title>
    <link rel="stylesheet" href="login-system/assets/css/style.css">
    <link rel="stylesheet" href="admin-dashboard/assets/css/style.css">
    <style>
        /* Visitor link styling */
        .visitor-link {
            position: absolute;
            top: 20px;
            right: 30px;
            font-size: 16px;
            font-weight: bold;
            color: #1f3d7a;
            text-decoration: none;
            padding: 8px 15px;
            border: 2px solid #1f3d7a;
            border-radius: 5px;
            transition: 0.3s ease-in-out;
        }

        .visitor-link:hover {
            background: #1f3d7a;
            color: white;
        }
    </style>
</head>

<body>
    <!-- Visitor Link -->
    <a href="visitor/visitor.php" class="visitor-link">Are you a visitor?</a>


    <div class="login-container">
        <div class="login-card">
            <!-- Left Side: Login Form -->
            <div class="login-left">
                <h2>Login to Your Account</h2>
                <?php if (!empty($error)) {
                    echo "<p class='error'>$error</p>";
                } ?>
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                    <div class="input-group">
                        <input type="text" name="full_name" placeholder="Username or Email" required>
                    </div>

                    <div class="input-group">
                        <input type="password" name="password" id="password" placeholder="Password" required>
                        <input type="checkbox" id="show-password"> Show Password
                    </div>

                    <button type="submit">Login</button>

                    <p class="register-link">Don't have an account? <a href="registration/registration.php">Sign up</a></p>
                </form>
            </div>

            <!-- Right Side: Role Selection -->
        </div>
    </div>

    <script>
        // Show/Hide password toggle
        document.getElementById('show-password').addEventListener('change', function() {
            document.getElementById('password').type = this.checked ? 'text' : 'password';
        });
    </script>

    <style>
        /* Center login container */
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 80vh;
            background: #dcdcdc;
            width: 800px;
        }

        /* Main login card */
        .login-card {
            display: flex;
            width: 700px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        /* Left section: Login Form */
        .login-left {
            flex: 1;
            padding: 30px;
            background: #fff;
        }

        .login-left h2 {
            margin-bottom: 20px;
            font-size: 22px;
            color: #333;
        }

        .input-group {
            margin-bottom: 15px;
        }

        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            width: 100%;
            background: #1f3d7a;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        /* Right section: Role Selection */

        .error {
            color: red;
            margin-bottom: 10px;
        }
    </style>

</body>

</html>