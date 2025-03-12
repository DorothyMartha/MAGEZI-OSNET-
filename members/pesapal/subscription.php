<?php
session_start(); // Start the session

require_once '../libs/db-connection.php';
include("../includes/subscription_fees.inc.php");

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Set a session variable to display a notification after redirection
    $_SESSION['error_message'] = "You must be logged in to access this page.";
    
    // Redirect to the login page
    header("Location: /osnet/index.php");
    exit();
}

// Fetch necessary data
$subscription_fees = getSubscriptionFees($conn);
$gateways = getGateways($conn);

// Split the user's name
$names = explode(" ", $_SESSION['name']);
?>


<!doctype html>
<html lang="en">

<head>
    <title>Magezi Osnet - Upgrade</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700,800,900" rel="stylesheet">
    <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous"> -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link href="./css/normalize.css" rel="stylesheet">
    <!-- <link href="./css/bootstrap.css" rel="stylesheet"> -->
    <link href="./css/bootstrap-responsive.css" rel="stylesheet">
    <link href="./css/style.css" rel="stylesheet">
    <link href="./css/style_forms_validation.css" rel="stylesheet" type="text/css">

    <script>
        var url = null;

        function addFee(obj) {
            document.getElementById("amt").value = obj.value.split(',')[1];
            url = "https://mageziharvest.co.ug/login.php?st=<?php echo $_SESSION['id']; ?>&pl=" + obj.value.split(',')[0];
            document.getElementById("redirect_url").value = url;
        }

        function openGateway(gateway) {
            if (gateway === "flutterwave") {
                document.getElementById("flutterwave").style.display = "block";
                document.getElementById("rightcol").style.display = "none";
            } else if (gateway === "pesapal") {
                document.getElementById("rightcol").style.display = "block";
                document.getElementById("flutterwave").style.display = "none";
            }
        }
    </script>
    <style type="text/css">
        @media (max-width: 768px) {
            .div-width {
                width: 200px !important;
            }
        }
    </style>
</head>

<body>

    <div class="wrapper d-flex align-items-stretch">

        <?php include("../navigation/sidebar.php"); ?>

        <!-- Page Content  -->
        <div id="content" class="p-4 p-md-5">

            <?php include("../navigation/topbar.php"); ?>
            <h4 class="mb-4">Subscribe</h4>
            <hr />

            <?php
            foreach ($gateways as $gateway) {
                $row = json_decode($gateway);
                if ($row->name === "flutterwave" && $row->status === "on") {
            ?>
                    <div class="card" onclick="openGateway('flutterwave')">
                        <div class="card-body">
                            <img src="../images/flutterwave.png" height=30 width=50 />
                            Flutterwave Payment <br>
                            <img src="../images/modes.png" height=20 width=200 />
                        </div>
                    </div>
                <?php
                } else if ($row->name === "pesapal" && $row->status === "on") {
                ?>

                    <div class="card" onclick="openGateway('pesapal')" style="margin-top: 20px;">
                        <div class="card-body">
                            <img src="../images/pesapal.png" width=50 />
                            Pesapal Payment <br>
                            <img src="../images/modes.png" height=20 width=200 />
                        </div>
                    </div>
            <?php
                }
            }
            ?>
            <div class="card-body">
                <form id="rightcol" action="pesapal/iframe.php" method="post" class="rounded5" style="display: none;">
                    <p>PESAPAL</p>
                    <table>
                        <tr>
                            <td><label>Plan:</label></td>
                            <td>
                                <select name="plan" style="width: 93%;" onchange="addFee(this)" required>
                                    <option value="">Select a plan...</option>
                                    <?php
                                    foreach ($subscription_fees as $fee) {
                                        $item = json_decode($fee);
                                    ?>
                                        <option value="<?php echo $item->id; ?>, <?php echo $item->amount; ?>" id="<?php echo $item->id; ?>"><?php echo $item->duration . " - " . $item->amount; ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>First Name:</td>
                            <td><input type="text" name="first_name" value="<?php echo $names[0]; ?>" readOnly /></td>
                        </tr>
                        <tr>
                            <td>Last Name:</td>
                            <td><input type="text" name="last_name" value="<?php echo $names[1]; ?>" readOnly /></td>
                        </tr>
                        <tr>
                            <td>Email Address:</td>
                            <td><input type="text" name="email" value="<?php echo $_SESSION['email']; ?>" readOnly /></td>
                        </tr>
                        <tr>
                            <td>Phone Number:</td>
                            <td><input type="text" name="phone_number" readOnly value="<?php echo $_SESSION['contact']; ?>" /></td>
                        </tr>
                        <tr>
                            <td>Currency:</td>
                            <td><input type="text" name="currency" value="UGX" readOnly /></td>
                        </tr>
                        <tr style="visibility: hidden">
                            <td>Amount:</td>
                            <td><input type="text" name="amount" id="amt" value="" readOnly /></td>
                        </tr>

                        <tr id="refholder" style="visibility: hidden">
                            <td>Reference:</td>
                            <td><input type="text" name="reference" readOnly value="<?php echo time() . rand(10 * 445, 100 * 98); ?>" /></td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <hr />
                            </td>
                        </tr>
                        <tr>
                            <td>Description:</td>
                            <td><input type="text" name="description" value="Subscription Payments" readOnly /></td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <hr />
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2"><input type="submit" value="Make Payment" class="btn btn-primary" /></td>
                        </tr>
                    </table>
                </form>
                <form method="POST" id="flutterwave" action="https://checkout.flutterwave.com/v3/hosted/pay" style="display: none;">
                    <p>FLUTTERWAVE</p>
                    <div>
                        <div class="div-width" style="width:400px; margin:auto;">
                            <label>Plan:</label>
                            <div class="form-group">
                                <select name="plan" class="custom-select" onchange="addFee(this)" required>
                                    <option value="">Select a plan...</option>
                                    <?php
                                    foreach ($subscription_fees as $fee) {
                                        $item = json_decode($fee);
                                    ?>
                                        <option value="<?php echo $item->id; ?>, <?php echo $item->amount; ?>" id="<?php echo $item->id; ?>"><?php echo $item->duration . " - " . $item->amount; ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="div-width" style="width:400px; margin:auto;">
                            <label>Name:</label>
                            <input type="text" class="form-control" value="<?php echo $_SESSION['name']; ?>" disabled required>
                        </div>
                    </div>
                    <div>
                        <div class="div-width" style="width:400px; margin:auto;">
                            <label>Email:</label>
                            <input type="text" class="form-control" value="<?php echo $_SESSION['email']; ?>" disabled required>
                        </div>
                    </div>
                    <div>
                        <div class="div-width" style="width:400px; margin:auto;">
                            <label>Currency:</label>
                            <input type="text" class="form-control" value="UGX" disabled required>
                        </div>
                    </div>
                    <div>
                        <div class="div-width" style="width:400px; margin:auto;">
                            <input type="hidden" class="form-control" id="amt" name="amount" value="">
                        </div>
                    </div>
                    <input type="hidden" name="public_key" value="FLWPUBK_TEST-2af1c0b1f18e4c8a83ab8e3a00b47e80-X" />
                    <input type="hidden" name="tx_ref" value="<?php echo time() . rand(10 * 445, 100 * 98); ?>" />
                    <input type="hidden" name="currency" value="UGX" />
                    <input type="hidden" id="redirect_url" name="redirect_url" value="" />
                    <!-- <input type="hidden" name="meta[token]" value="54" /> -->
                    <input type="hidden" name="customer[name]" value="<?php echo $_SESSION['name']; ?>" />
                    <input type="hidden" name="customer[email]" value="<?php echo $_SESSION['email']; ?>" />
                    <div>
                        <div class="form-group div-width" style="width:400px; margin:auto; margin-top: 20px;">
                            <button type="submit" class="btn btn-primary" style="width: 100%;">Subscribe</button>
                        </div>
                    </div>
                </form>
            </div>
            <?php include("./footer.php"); ?>
        </div>
    </div>

    <script src="../js/jquery.min.js"></script>
    <script src="../js/popper.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/main.js"></script>
</body>

</html>