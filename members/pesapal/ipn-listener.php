<?php
// Enable error reporting for debugging (set to 0 in production)
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 0);

// Include database connection and OAuth library
require_once '../../libs/db-connection.php';
require_once 'OAuth.php'; // osnet/members/pesapal/OAuth.php

// Log all incoming requests (GET and POST) with method
$request_method = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
$request_data = $request_method === 'GET' ? $_GET : $_POST;
$request_headers = getallheaders();
$headers_log = json_encode($request_headers);
file_put_contents('ipn_access.log', date('Y-m-d H:i:s') . " - Method: $request_method, Data: " . json_encode($request_data) . ", Headers: $headers_log\n", FILE_APPEND);

// Check for parameters in both GET and POST
$notification_type = $_GET['pesapal_notification_type'] ?? $_POST['pesapal_notification_type'] ?? '';
$tracking_id = $_GET['pesapal_transaction_tracking_id'] ?? $_POST['pesapal_transaction_tracking_id'] ?? '';
$reference = $_GET['pesapal_merchant_reference'] ?? $_POST['pesapal_merchant_reference'] ?? '';

// Log initial parameters for debugging
file_put_contents('ipn_debug.log', date('Y-m-d H:i:s') . " - Received IPN: method=$request_method, notification_type=$notification_type, tracking_id=$tracking_id, reference=$reference\n", FILE_APPEND);

// Define the pesapalCheckStatus class
class pesapalCheckStatus {
    private $token;
    private $params;
    private $signature_method;
    private $consumer;
    private $QueryPaymentStatus;
    private $QueryPaymentStatusByMerchantRef;
    private $querypaymentdetails;

    public function __construct() {
        $this->token = $this->params = NULL;
        $consumer_key = "XxJ0q6LwV6UlkZovkV0+vivcnBccFI7d";
        $consumer_secret = "LHCNEJiTQRW6IuEs9SH75RFaPqw=";

        $this->signature_method = new OAuthSignatureMethod_HMAC_SHA1();
        $this->consumer = new OAuthConsumer($consumer_key, $consumer_secret);

        $api = 'https://www.pesapal.com';
        $this->QueryPaymentStatus = $api . '/API/QueryPaymentStatus';
        $this->QueryPaymentStatusByMerchantRef = $api . '/API/QueryPaymentStatusByMerchantRef';
        $this->querypaymentdetails = $api . '/API/querypaymentdetails';
    }

    public function checkStatusUsingTrackingIdandMerchantRef($pesapalMerchantReference, $pesapalTrackingId) {
        $request_status = OAuthRequest::from_consumer_and_token(
            $this->consumer,
            $this->token,
            "GET",
            $this->QueryPaymentStatus,
            $this->params
        );
        $request_status->set_parameter("pesapal_merchant_reference", $pesapalMerchantReference);
        $request_status->set_parameter("pesapal_transaction_tracking_id", $pesapalTrackingId);
        $request_status->sign_request($this->signature_method, $this->consumer, $this->token);

        return $this->httpRequest($request_status);
    }

    private function httpRequest($request_status) {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 30,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ]
        ]);

        $response = @file_get_contents($request_status->to_url(), false, $context);
        if ($response === false) {
            $error = error_get_last()['message'] ?? 'Unknown error';
            file_put_contents('ipn_error.log', date('Y-m-d H:i:s') . " - HTTP Request Error: $error\n", FILE_APPEND);
            return "PENDING";
        }

        $header_size = strpos($response, "\r\n\r\n") + 4;
        $elements = preg_split("/=/", substr($response, $header_size));
        $pesapal_response_data = trim($elements[1] ?? 'PENDING');

        file_put_contents('pesapal_response.log', date('Y-m-d H:i:s') . " - API Response: $pesapal_response_data\n", FILE_APPEND);
        return $pesapal_response_data;
    }
}

if ($notification_type === 'CHANGE' && !empty($tracking_id) && !empty($reference)) {
    file_put_contents('ipn_data.log', date('Y-m-d H:i:s') . " - Valid IPN Data: method=$request_method, data=" . json_encode($request_data) . "\n", FILE_APPEND);

    try {
        // Ensure DB connection
        if (!isset($conn) || $conn->connect_error) {
            file_put_contents('ipn_error.log', date('Y-m-d H:i:s') . " - DB Connection Failed: " . ($conn->connect_error ?? 'Unknown error') . "\n", FILE_APPEND);
            header("HTTP/1.1 200 OK");
            echo "pesapal_notification_type=CHANGE&pesapal_transaction_tracking_id=$tracking_id&pesapal_merchant_reference=$reference";
            exit();
        }

        // Use Pesapal API for accurate status
        $pesapal = new pesapalCheckStatus();
        $status = $pesapal->checkStatusUsingTrackingIdandMerchantRef($reference, $tracking_id);
        file_put_contents('pesapal_response.log', date('Y-m-d H:i:s') . " - API Status: $status\n", FILE_APPEND);

        // Fallback if API fails
        if (!in_array($status, ['COMPLETED', 'FAILED', 'PENDING'])) {
            $status = !empty($tracking_id) ? 'COMPLETED' : 'PENDING';
            file_put_contents('pesapal_response.log', date('Y-m-d H:i:s') . " - Fallback Status: $status\n", FILE_APPEND);
        }

        // Validate status
        $valid_statuses = ['COMPLETED', 'FAILED', 'PENDING'];
        $status = in_array($status, $valid_statuses) ? $status : 'PENDING';

        // Update payment status and transaction_tracking_id using merchant_reference only
        $query = "UPDATE subscriptions SET payment_status = ?, transaction_tracking_id = ? WHERE merchant_reference = ?";
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            file_put_contents('ipn_error.log', date('Y-m-d H:i:s') . " - Prepare Failed: " . $conn->error . "\n", FILE_APPEND);
            header("HTTP/1.1 200 OK");
            echo "pesapal_notification_type=CHANGE&pesapal_transaction_tracking_id=$tracking_id&pesapal_merchant_reference=$reference";
            exit();
        }
        $stmt->bind_param("sss", $status, $tracking_id, $reference);
        if (!$stmt->execute()) {
            file_put_contents('ipn_error.log', date('Y-m-d H:i:s') . " - Execute Failed: " . $stmt->error . "\n", FILE_APPEND);
            $stmt->close();
            header("HTTP/1.1 200 OK");
            echo "pesapal_notification_type=CHANGE&pesapal_transaction_tracking_id=$tracking_id&pesapal_merchant_reference=$reference";
            exit();
        }
        $affected_rows = $stmt->affected_rows;
        $stmt->close();

        file_put_contents('db_query.log', date('Y-m-d H:i:s') . " - Updated Payment Status to: $status, Tracking ID: $tracking_id (Rows Affected: $affected_rows)\n", FILE_APPEND);

        // If payment completed, update user membership & funds
        if ($status === "COMPLETED" && $affected_rows > 0) {
            $query = "SELECT user_id, amount, plan_id FROM subscriptions WHERE merchant_reference = ?";
            $stmt = $conn->prepare($query);
            if ($stmt === false) {
                file_put_contents('ipn_error.log', date('Y-m-d H:i:s') . " - Prepare Failed (Fetch): " . $conn->error . "\n", FILE_APPEND);
                header("HTTP/1.1 200 OK");
                echo "pesapal_notification_type=CHANGE&pesapal_transaction_tracking_id=$tracking_id&pesapal_merchant_reference=$reference";
                exit();
            }
            $stmt->bind_param("s", $reference);
            $stmt->execute();
            $result = $stmt->get_result();
            $payment = $result->fetch_assoc();
            $stmt->close();

            if ($payment) {
                $user_id = $payment['user_id'];
                $amount = $payment['amount'];
                $plan_id = $payment['plan_id'];

                // Update user membership (with foreign key check)
                $query = "SELECT id FROM membership_categories WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $plan_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $category_exists = $result->num_rows > 0;
                $stmt->close();

                if ($category_exists) {
                    $query = "UPDATE users SET membership_category_id = ? WHERE id = ?";
                    $stmt = $conn->prepare($query);
                    if ($stmt === false) {
                        file_put_contents('ipn_error.log', date('Y-m-d H:i:s') . " - Prepare Failed (Users): " . $conn->error . "\n", FILE_APPEND);
                    } else {
                        $stmt->bind_param("ii", $plan_id, $user_id);
                        if (!$stmt->execute()) {
                            file_put_contents('ipn_error.log', date('Y-m-d H:i:s') . " - Execute Failed (Users): " . $stmt->error . "\n", FILE_APPEND);
                        }
                        $stmt->close();
                        file_put_contents('db_query.log', date('Y-m-d H:i:s') . " - Updated User $user_id to Plan $plan_id\n", FILE_APPEND);
                    }
                } else {
                    file_put_contents('ipn_error.log', date('Y-m-d H:i:s') . " - Invalid membership_category_id: $plan_id not found in membership_categories\n", FILE_APPEND);
                }

                // Ensure funds record exists
                $conn->query("INSERT INTO funds (id, total_funds) VALUES (1, 0) ON DUPLICATE KEY UPDATE id=id");

                // Update funds table
                $query = "UPDATE funds SET total_funds = total_funds + ? WHERE id = 1";
                $stmt = $conn->prepare($query);
                if ($stmt === false) {
                    file_put_contents('ipn_error.log', date('Y-m-d H:i:s') . " - Prepare Failed (Funds): " . $conn->error . "\n", FILE_APPEND);
                } else {
                    $stmt->bind_param("d", $amount);
                    if (!$stmt->execute()) {
                        file_put_contents('ipn_error.log', date('Y-m-d H:i:s') . " - Execute Failed (Funds): " . $stmt->error . "\n", FILE_APPEND);
                    }
                    $stmt->close();
                    file_put_contents('db_query.log', date('Y-m-d H:i:s') . " - Updated Funds with Amount: $amount\n", FILE_APPEND);
                }
            } else {
                file_put_contents('ipn_error.log', date('Y-m-d H:i:s') . " - No payment found for reference: $reference\n", FILE_APPEND);
            }
        }

        header("HTTP/1.1 200 OK");
        echo "pesapal_notification_type=CHANGE&pesapal_transaction_tracking_id=$tracking_id&pesapal_merchant_reference=$reference";
    } catch (Exception $e) {
        file_put_contents('ipn_error.log', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
        header("HTTP/1.1 200 OK");
        echo "pesapal_notification_type=CHANGE&pesapal_transaction_tracking_id=$tracking_id&pesapal_merchant_reference=$reference";
    }
} else {
    file_put_contents('ipn_error.log', date('Y-m-d H:i:s') . " - Invalid IPN: method=$request_method, data=" . json_encode($request_data) . "\n", FILE_APPEND);
    header("HTTP/1.1 200 OK");
    echo "";
}
?>