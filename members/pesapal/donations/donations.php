<?php
session_start(); // Start the session

require_once '../../../libs/db-connection.php';

// Fetch all active campaigns regardless of association
$query = "SELECT id, title, fee, 
                 (SELECT IFNULL(SUM(amount), 0) FROM donations WHERE campaign_id = fundraising_campaigns.id) AS total_contributions
          FROM fundraising_campaigns WHERE status = 'Active' AND end_date >= CURDATE()";
$stmt = $conn->prepare($query);
$stmt->execute();
$campaign_result = $stmt->get_result();

$campaigns = [];
while ($campaign = $campaign_result->fetch_assoc()) {
    $campaigns[] = $campaign;
}

$stmt->close();

// Handle form submission for donation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $campaign_id = $_POST['campaign_id'];
    $amount = $_POST['amount'];

    // Ensure the amount is valid
    if ($amount <= 0) {
        $_SESSION['error_message'] = "Amount must be greater than 0.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Insert the contribution into the donations table
   // After receiving form data and before sending to Pesapal
    $query = "INSERT INTO donations (campaign_id, user_id, amount, payment_status) VALUES (?, ?, ?, 'pending')";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iis", $campaign_id, $user_id, $amount); // Bind actual values


    $stmt->execute();
    $stmt->close();

    $_SESSION['success_message'] = "Thank you for your contribution!";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Query to calculate total contributions for completed donations
$query = "SELECT SUM(amount) AS total_contributed FROM donations WHERE payment_status = 'completed'";
$result = $conn->query($query);

if ($result) {
    $row = $result->fetch_assoc();
    $total_contributed = $row['total_contributed'];
    // If no completed donations, set total_contributed to 0
    $total_contributed = $total_contributed ?: 0;
} else {
    $total_contributed = 0; // Handle case where the query fails
}

// Callback handler for Pesapal (this is just an example)
// Assume you already have the necessary variables: $payment_status, $donation_id, $amount

if ($payment_status == 'SUCCESS') {
    // Update the donation status and amount after successful payment
    $query = "UPDATE donations SET payment_status = 'completed', amount = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("di", $amount, $donation_id); // Bind actual values
    $stmt->execute();
} else {
    // If the payment failed, mark as 'failed'
    $query = "UPDATE donations SET payment_status = 'failed' WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $donation_id); // Bind actual donation ID
    $stmt->execute();
}

?>

<!DOCTYPE html>
<html lang="en">

<body>
    <style>
        /* CSS for the form */
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

        input[type="number"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
            background-color: #f9f9f9;
            transition: border-color 0.3s ease, background-color 0.3s ease;
        }

        input[type="number"]:focus {
            border-color: #007bff;
            background-color: #eaf4ff;
            outline: none;
        }

        input[type="number"]::placeholder {
            color: #aaa;
            font-style: italic;
        }
        .search-container {
    position: relative;
    width: 100%;
}

#campaign_search {
    width: 100%;
    padding-right: 30px; /* Make space for the icon */
}

.search-icon {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none; /* Ensures the icon doesn't block input */
}

#campaign_list {
    position: absolute;
    width: 37%;
    max-height: 200px;  /* Add a max-height for scrolling if there are many items */
    overflow-y: auto;  /* Enable vertical scrolling */
    background-color: #fff;  /* White background */
    border: 1px solid #ccc;  /* Border around the dropdown */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);  /* Add a subtle shadow for depth */
    z-index: 1000;  /* Ensure it appears above other elements */
    display: none;  /* Initially hidden */
}

#campaign_list div {
    padding: 8px;
    cursor: pointer;
}

#campaign_list div:hover {
    background-color: #f1f1f1;  /* Highlight on hover */
}

    </style>

    <div class="card-body">
        <form id="rightcol" action="../iframe.php" method="post" class="rounded5">
            <p>PESAPAL PAYMENTS</p>
            <table>
                <tr>
                <td><label>Support a Campaign:</label></td>
                <td>
    <div class="search-container">
        <input type="text" id="campaign_search" placeholder="Search for campaign..." required>
        <input type="hidden" name="campaign_id" id="campaign_id">
        <span class="search-icon">🔍</span>
    </div>
    <div id="campaign_list" class="autocomplete-items"></div>
</td>

                </tr>
                <tr>
                    <td>UserName:</td>
                    <td><input type="text" name="full_name"/></td>
                </tr>
                <tr>
                    <td>Email Address:</td>
                    <td><input type="text" name="email" /></td>
                </tr>
                <tr>
                    <td>Phone Number:</td>
                    <td><input type="text" name="phone_number"/></td>
                </tr>
                <tr>
                    <td>Currency:</td>
                    <td><input type="text" name="currency" value="UGX" readonly /></td>
                </tr>
                <tr>
                    <td>Amount (UGX):</td>
                    <td><input type="number" name="amount" id="amt" min="1" required placeholder="Enter amount" /></td>
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
                    <td><input type="text" name="description" value="Donations" readonly /></td>
                </tr>
                <tr>
                    <td colspan="2"><input type="submit" value="Make Payment" class="btn btn-primary" /></td>
                </tr>
            </table>
        </form>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const campaigns = <?php echo json_encode($campaigns); ?>;
            const searchInput = document.getElementById("campaign_search");
            const campaignList = document.getElementById("campaign_list");
            const campaignIdInput = document.getElementById("campaign_id");

            searchInput.addEventListener("input", function () {
                let val = this.value.toLowerCase();
                campaignList.innerHTML = "";
                if (!val) {
                    campaignList.style.display = "none";
                    return;
                }

                let matchingCampaigns = campaigns.filter(campaign => campaign.title.toLowerCase().includes(val));

                if (matchingCampaigns.length > 0) {
                    campaignList.style.display = "block";
                    matchingCampaigns.forEach(campaign => {
                        let div = document.createElement("div");
                        div.innerHTML = `${campaign.title} - (UGX ${campaign.fee.toLocaleString()}) - Total Contributions: UGX ${campaign.total_contributions.toLocaleString()}`;
                        div.addEventListener("click", function () {
                            searchInput.value = campaign.title;
                            campaignIdInput.value = campaign.id;
                            campaignList.style.display = "none";
                        });
                        campaignList.appendChild(div);
                    });
                } else {
                    let div = document.createElement("div");
                    div.innerHTML = "No matching campaigns found";
                    campaignList.appendChild(div);
                    campaignList.style.display = "block";
                }
            });

            // Hide dropdown when clicking outside
            document.addEventListener("click", function (e) {
                if (!searchInput.contains(e.target) && !campaignList.contains(e.target)) {
                    campaignList.style.display = "none";
                }
            });
        });
    </script>
</body>
</html>
