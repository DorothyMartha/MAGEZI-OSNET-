<?php
session_start();

require_once '../libs/db-connection.php'; 

// Add logging include (assuming this should be functions.php, not profilefunction.php)
require_once 'profilefunction.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You need to be logged in to view this page.");
}

$user_id = $_SESSION['user_id'];

// Add logging call
logActivity($conn, $user_id, "Viewed profile page", $_SERVER['REMOTE_ADDR'], $_SERVER['REQUEST_URI'], null, 'success', session_id(), $_SERVER['HTTP_USER_AGENT'] ?? null);

// Fetch user details from the database
try {
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            die("User not found.");
        }

        if (!empty($user['association_id'])) {
            $sql = "SELECT association_name FROM alumni_associations WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $user['association_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $association = $result->fetch_assoc();
                $association_name = $association ? htmlspecialchars($association['association_name']) : "No association found";
                $stmt->close();
            } else {
                throw new Exception("Association prepare failed: " . $conn->error);
            }
        } else {
            $association_name = "Not affiliated with any association";
        }
    } else {
        throw new Exception("User prepare failed: " . $conn->error);
    }
} catch (Exception $e) {
    die("Error fetching user data: " . $e->getMessage());
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = '';

    // Preferences update (Save Changes)
    if (isset($_POST['save_preferences'])) {
        $contact_privacy = $_POST['contact_privacy'];
        $accept_invites = isset($_POST['accept_invites']) ? 1 : 0;

        try {
            // Update the database
            $sql = "UPDATE users SET contact_privacy = ?, accept_invites = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("sii", $contact_privacy, $accept_invites, $user_id);
                $stmt->execute();
                $stmt->close();

                // Refresh user data to reflect the update
                $sql = "SELECT * FROM users WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                    $stmt->close();

                    echo "<script>document.getElementById('successToast').classList.remove('hidden'); setTimeout(() => document.getElementById('successToast').classList.add('hidden'), 3000);</script>";
                } else {
                    throw new Exception("Refresh prepare failed: " . $conn->error);
                }
            } else {
                throw new Exception("Update prepare failed: " . $conn->error);
            }
        } catch (Exception $e) {
            $error = "Error updating preferences: " . $e->getMessage();
        }
    }

    // Cancel action
    if (isset($_POST['cancel_preferences'])) {
        header("Location: profile.php");
        exit;
    }
}
?>
<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <style>
body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background: #f4f6f9;
    display: flex;
}

.profile-preferences {
  flex: 0 1 40%;
  background-color: #f4f6f9;
  padding-top: 20px;
  padding-left: 20px;
  padding-right: 20px;
}

.form-container {
  flex: 0 1 60%;
  background-color: #f4f6f9;
  padding-top: 50px;
  padding-left: 10px;
  padding-right: 10px;
  margin-top: 80px;
}

/* Adjustments for smaller screens */
@media (max-width: 768px) {
  .content-wrapper {
    flex-direction: column;
  }

  .profile-preferences,
  .form-container {
    flex: 1 1 100%;
    margin-bottom: 20px;
  }
}

.main-content {
    flex-direction: column;
  margin-left: 260px;
  padding-top: 50px;
  margin-top: 80px;
  width: calc(100% - 180px);
  box-sizing: border-box;
  display: flex;
  justify-content: space-between;
  gap: 0;
}

/* Header Styling */
.profile-preferences h2 {
  font-size: 1.75rem;
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 16px;
}

/* Flex Layout for Header */
.profile-preferences .flex.items-center {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
}

/* Button Styling */
button {
  border-radius: 8px;
  padding: 0.5rem 1.25rem;
  font-size: 1rem;
  font-weight: 500;
  min-width: 150px;
  text-align: center;
  transition: background-color 0.3s ease, transform 0.3s ease;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

/* Cancel Button */
button#cancelBtn {
  background-color: #ef4444;
  color: #fff;
  border: 1px solid #ef4444;
}

button#cancelBtn:hover {
  background-color: #dc2626;
  color: #fff;
  border-color: #dc2626;
  transform: translateY(-2px);
}

/* Save Button */
button#saveBtn {
  background-color: #4f46e5;
  color: white;
  border: 1px solid #1f3d7a;
}

button#saveBtn:hover {
  background-color: #4338ca;
  border-color: #4338ca;
  transform: translateY(-2px);
}

/* Active state for both buttons */
button:active {
  transform: translateY(1px);
}

/* Focus state for accessibility */
button:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
}

/* Tooltip Styling */
[data-tooltip] {
  position: relative;
}

[data-tooltip]::after {
  content: attr(data-tooltip);
  position: absolute;
  background-color: #1f2937;
  color: white;
  padding: 0.25rem;
  font-size: 0.875rem;
  border-radius: 4px;
  display: none;
  opacity: 0;
  transition: opacity 0.3s ease;
}

[data-tooltip]:hover::after {
  display: block;
  opacity: 1;
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%);
  margin-bottom: 8px;
}

/* Dropdown Styling */
#privacyDropdown {
  display: flex;
  justify-content: space-between;
  padding: 1rem;
  background-color: white;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  width: 100%;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

#privacyDropdown:hover {
  background-color: #f3f4f6;
}

#privacyOptions {
  display: none;
  position: absolute;
  background-color: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  width: 100%;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

#privacyOptions div {
  padding: 0.75rem 1rem;
  cursor: pointer;
  font-size: 1rem;
  transition: background-color 0.3s ease;
}

#privacyOptions div:hover {
  background-color: #f3f4f6;
}

/* Profile Info Block Styling */
.bg-white {
  background-color: white;
  border-radius: 8px;
  padding: 2rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.bg-white h2 {
  font-size: 1.25rem;
  color: #2d3748;
  margin-bottom: 16px;
}

.text-gray-900 {
  color: #111827;
}

/* Form Input and Checkbox Styles */
input[type="checkbox"] {
  width: 26px;
  height: 26px;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.toggle-checkbox:checked {
  background-color: #4f46e5;
}

.toggle-label {
  background-color: #e2e8f0;
  border-radius: 12px;
  width: 48px;
  height: 28px;
  position: relative;
}

.toggle-checkbox:checked + .toggle-label {
  background-color: #4f46e5;
}

.toggle-label::before {
  content: '';
  position: absolute;
  top: 2px;
  left: 2px;
  width: 22px;
  height: 22px;
  background-color: white;
  border-radius: 50%;
  transition: transform 0.3s ease;
}

.toggle-checkbox:checked + .toggle-label::before {
  transform: translateX(20px);
}

/* Toast Notification */
#successToast {
  background-color: #4caf50;
  color: white;
  padding: 0.75rem 1.25rem;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  position: fixed;
  bottom: 16px;
  right: 16px;
  display: none;
  transition: opacity 0.3s ease-in-out;
}

#successToast.show {
  display: flex;
  align-items: center;
  opacity: 1;
}

#successToast i {
  font-size: 1.25rem;
  margin-right: 10px;
}

/* Media Queries for Responsiveness */
@media (max-width: 768px) {
  .profile-preferences {
    padding: 16px;
  }

  .profile-preferences h2 {
    font-size: 1.5rem;
  }

  .profile-preferences .flex.items-center {
    flex-direction: column;
    align-items: flex-start;
  }

  button#cancelBtn, button#saveBtn {
    margin-top: 10px;
    width: 100%;
  }

  #privacyDropdown {
    padding: 0.75rem;
    font-size: 0.875rem;
  }

  .bg-white {
    padding: 1.5rem;
  }
}
.profile-preferences h3 {
  font-size: 1.2rem;
  font-weight: 700;
  color: #333;
}
/* Container for toggle */
.toggle-container {
  position: relative;
  display: inline-block;
  width: 50px;
  height: 26px;
}

/* Checkbox hidden */
.toggle-checkbox {
  opacity: 0;
  position: absolute;
  top: 0;
  left: 0;
  width: 0;
  height: 0;
}

/* Label as the actual toggle switch */
.toggle-label {
  cursor: pointer;
  display: block;
  position: relative;
  width: 100%;
  height: 100%;
  background-color: #ccc;
  border-radius: 50px;
  transition: background-color 0.3s;
}

.toggle-label::before {
  content: '';
  position: absolute;
  top: 3px;
  left: 3px;
  width: 20px;
  height: 20px;
  background-color: white;
  border-radius: 50%;
  transition: transform 0.3s;
}

/* When the checkbox is checked, the switch moves */
.toggle-checkbox:checked + .toggle-label {
  background-color: #1f3d7a;
}

.toggle-checkbox:checked + .toggle-label::before {
  transform: translateX(24px);
}

/* Tooltip Styling */
[data-tooltip] {
  position: relative;
}

[data-tooltip]::after {
  content: attr(data-tooltip);
  position: absolute;
  background-color: #1f2937;
  color: white;
  padding: 0.25rem;
  font-size: 0.75rem;
  border-radius: 4px;
  display: none;
  z-index: 10;
}

[data-tooltip]:hover::after {
  display: block;
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%);
  margin-bottom: 8px;
}

/* Enhance Tooltip appearance */
[data-tooltip]:hover::after {
  opacity: 0.8;
  transition: opacity 0.3s ease-in-out;
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
            color:#F1F1F1;
        }

        .user-info span {
            font-size: 14px;
            color: #E0E0E0;
        }

/* Container for the form */
.form-container {
  background-color: #ffffff;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  padding: 20px;
  max-width: 800px;
  margin-top: 80px;
}

/* Form labels */
.form-container label {
  font-size: 1rem;
  font-weight: 600;
  color: #333;
  margin-bottom: 8px;
  display: block;
}

/* Form input fields */
.form-container input[type="text"],
.form-container input[type="email"],
.form-container select {
  width: 100%;
  padding: 10px;
  margin-bottom: 15px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 1rem;
  color: #333;
  box-sizing: border-box;
  transition: border 0.3s ease;
}

/* Focus state for input fields */
.form-container input[type="text"]:focus,
.form-container input[type="email"]:focus,
.form-container select:focus {
  border-color: #1f3d7a;
  outline: none;
}

/* Submit button */
.form-container input[type="submit"] {
  background-color: #1f3d7a;
  color: white;
  padding: 10px 10px;
  border: none;
  border-radius: 6px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  width: 100%;
  transition: background-color 0.3s;
}

/* Hover state for submit button */
.form-container input[type="submit"]:hover {
  background-color: #1f3d7a;
}

/* Optional break between input fields for readability */
.form-container input[type="text"],
.form-container input[type="email"],
.form-container select {
  margin-bottom: 5px;
}

/* General spacing for form fields */
.form-container input[type="submit"] {
  margin-top:   10px;
}

/* Preferences Form */
.preferences-form select {
    width: 100%;
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 1rem;
    color: #333;
    background-color: white;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.preferences-form select:focus {
    border-color: #1f3d7a;
    outline: none;
}
    </style>
</head>
<body>

<?php if (!empty($error)): ?>
    <p style="color: red;"><?= $error; ?></p>
<?php endif; ?>

<div class="main-content">
    <div class="top-nav">
        <h2>Member Dashboard</h2>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($user['full_name']); ?></span>
            <span> | Membership Category: <?php 
                // Use existing $conn from top include
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

    <div class="profile-preferences">
        <form class="preferences-form" method="POST" action="profile.php">
            <div class="py-24 px-8">
                <div class="flex items-center">
                    <h3 class="text-2xl font-semibold text-gray-900">Profile Preferences</h3>
                    <div class="ml-auto flex items-center space-x-4">
                        <button id="cancelBtn" type="submit" name="cancel_preferences" class="px-4 py-2 text-sm font-medium text-white bg-primary !rounded-button hover:bg-primary/90">Cancel</button>
                        <button id="saveBtn" type="submit" name="save_preferences" class="px-4 py-2 text-sm font-medium text-white bg-primary !rounded-button hover:bg-primary/90">Save Changes</button>
                    </div>
                </div>
                <div class="mt-8 space-y-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <h2 class="text-lg font-medium text-gray-900">Contact Information Privacy</h2>
                            <div class="ml-2 w-5 h-5 flex items-center justify-center text-gray-400 cursor-help" data-tooltip="Control who can see your contact information">
                                <i class="ri-information-line"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <select name="contact_privacy">
                                <option value="Public" <?= ($user['contact_privacy'] ?? 'Private') === 'Public' ? 'selected' : ''; ?>>Public (All Members)</option>
                                <option value="Friends Only" <?= ($user['contact_privacy'] ?? 'Private') === 'Friends Only' ? 'selected' : ''; ?>>Friends Only</option>
                                <option value="Private" <?= ($user['contact_privacy'] ?? 'Private') === 'Private' ? 'selected' : ''; ?>>Private (No One)</option>
                            </select>
                            <p class="mt-2 text-sm text-gray-500">Choose who can see your email and phone number</p>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <h2 class="text-lg font-medium text-gray-900">Accept Invites from Other Alumni Associations</h2>
                                <div class="ml-2 w-5 h-5 flex items-center justify-center text-gray-400 cursor-help" data-tooltip="Allow other alumni groups to send you invitations">
                                    <i class="ri-information-line"></i>
                                </div>
                            </div>
                            <div class="toggle-container">
                                <input type="checkbox" id="inviteToggle" name="accept_invites" class="toggle-checkbox" <?= ($user['accept_invites'] ?? 0) == 1 ? 'checked' : ''; ?> />
                                <label for="inviteToggle" class="toggle-label"></label>
                            </div>
                        </div>
                        <p class="mt-2 text-sm text-gray-500">When enabled, you'll receive invitations from other alumni groups</p>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div id="successToast" class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg hidden">
        <div class="flex items-center">
            <div class="w-5 h-5 flex items-center justify-center">
                <i class="ri-check-line"></i>
            </div>
            <span class="ml-2">Preferences saved successfully!</span>
        </div>
    </div>

</div>

<script>
    const saveBtn = document.getElementById("saveBtn");
    const cancelBtn = document.getElementById("cancelBtn");
    const successToast = document.getElementById("successToast");

    saveBtn.addEventListener("click", () => {
        // Toast is triggered via PHP on successful save
    });

    const tooltips = document.querySelectorAll("[data-tooltip]");
    tooltips.forEach((tooltip) => {
        tooltip.addEventListener("mouseenter", (e) => {
            const tooltipText = tooltip.getAttribute("data-tooltip");
            const tooltipEl = document.createElement("div");
            tooltipEl.className = "absolute bg-gray-900 text-white text-sm px-2 py-1 rounded pointer-events-none";
            tooltipEl.style.top = e.pageY - 30 + "px";
            tooltipEl.style.left = e.pageX + 10 + "px";
            tooltipEl.textContent = tooltipText;
            document.body.appendChild(tooltipEl);
            tooltip.addEventListener("mouseleave", () => {
                tooltipEl.remove();
            });
        });
    });
</script>

</body>
</html>
<?php
// Close the connection
if (isset($conn)) $conn->close();
?>