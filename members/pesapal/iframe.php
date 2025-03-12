<?php
session_start();
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
require_once('OAuth.php');
require_once '../../libs/db-connection.php';

// Production API (your original setting)
$api = 'https://www.pesapal.com';
$iframelink = $api . '/api/PostPesapalDirectOrderV4';

// Your Ugandan production keys
$consumer_key = "XxJ0q6LwV6UlkZovkV0+vivcnBccFI7d";
$consumer_secret = "LHCNEJiTQRW6IuEs9SH75RFaPqw=";

$signature_method = new OAuthSignatureMethod_HMAC_SHA1();
$consumer = new OAuthConsumer($consumer_key, $consumer_secret);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "You must be logged in to make a payment.";
    header("Location: ../../index.php");
    
}

$user_id = $_SESSION['user_id'];

// Handle form submission from subscription.php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['plan']) && !empty($_POST['plan'])) {
    // Fetch and sanitize form data
    $amount = filter_var($_POST['amount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $desc = htmlspecialchars($_POST['description']);
    $type = 'MERCHANT';
    $full_name = $_POST['full_name'];
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phonenumber = htmlspecialchars($_POST['phone_number']);
    $currency = htmlspecialchars($_POST['currency']);
    $reference = htmlspecialchars($_POST['reference']);
    $plan_id = $_POST['plan']; // Added plan_id from form
    $callback_url = 'https://magezi.ac.ug/osnet/members/pesapal/callback.php'; 

    // Validate amount
    if (empty($amount) || !is_numeric($amount)) {
        die("Error: Amount must be a valid number (e.g., 5000.00).");
    }

    // Log POST data for debugging
    file_put_contents('../pesapal/payment_debug.txt', date('Y-m-d H:i:s') . " - Received POST: plan=$plan_id, amount=$amount, reference=$reference\n", FILE_APPEND);

    // Insert into subscriptions table
    $query = "INSERT INTO subscriptions (user_id, amount, payment_status, merchant_reference, plan_id) VALUES (?, ?, 'PENDING', ?, ?)";
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        file_put_contents('../pesapal/payment_error.log', date('Y-m-d H:i:s') . " - Prepare failed: " . $conn->error . "\n", FILE_APPEND);
        $_SESSION['error_message'] = "Database error. Please try again.";
        header("Location: ../dashboard.php");
    }

    $stmt->bind_param("idsi", $user_id, $amount, $reference, $plan_id);
    if ($stmt->execute()) {
        file_put_contents('../pesapal/payment_log.txt', date('Y-m-d H:i:s') . " - Payment saved: user_id=$user_id, amount=$amount, reference=$reference, plan_id=$plan_id\n", FILE_APPEND);
    } else {
        file_put_contents('../pesapal/payment_error.log', date('Y-m-d H:i:s') . " - Execute failed: " . $stmt->error . "\n", FILE_APPEND);
        $_SESSION['error_message'] = "Failed to save your payment. Please try again.";
        header("Location: ../dashboard.php");
    }
    $stmt->close();

    // Generate XML for the payment request
    $post_xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
                 <PesapalDirectOrderInfo 
                     xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" 
                     xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" 
                     Currency=\"" . $currency . "\" 
                     Amount=\"" . $amount . "\" 
                     Description=\"" . $desc . "\" 
                     Type=\"" . $type . "\" 
                     Reference=\"" . $reference . "\" 
                     FullName=\"" . $full_name . "\" 
                     Email=\"" . $email . "\" 
                     PhoneNumber=\"" . $phonenumber . "\" 
                     xmlns=\"http://www.pesapal.com\" />";

    // Initialize token and params
    $token = NULL; // No token required for initial request
    $params = array(); // Empty array for OAuth parameters

    try {
        // Create OAuth request
        $iframe_src = OAuthRequest::from_consumer_and_token($consumer, $token, "GET", $iframelink, $params);
        $iframe_src->set_parameter("oauth_callback", $callback_url);
        $iframe_src->set_parameter("pesapal_request_data", htmlspecialchars($post_xml));
        $iframe_src->sign_request($signature_method, $consumer, $token);

        // Get the iframe URL
        $iframe_url = $iframelink . '?' . http_build_query($iframe_src->get_parameters());

    } catch (Exception $e) {
        die("Error: Something went wrong - " . $e->getMessage());
    }
} else {
    $_SESSION['error_message'] = "Invalid request.";
    header("Location: ../dashboard.php");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pay with Pesapal</title>
</head>
<body>
    <h2>Complete Your Payment</h2>
    <iframe src="<?php echo htmlspecialchars($iframe_url); ?>" width="100%" height="700px" scrolling="no" frameborder="0">
        <p>Your browser does not support iframes.</p>
    </iframe>
</body>
</html>