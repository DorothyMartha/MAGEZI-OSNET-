<?php
ob_start();
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'libs/db-connection.php';
require_once 'admin-dashboard/functions.php';
// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['form_type'])) {
        if ($_POST['form_type'] === 'login') {
            // Handle login form submission
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

                // Log the login
                logActivity(
                    $conn,
                    $user['id'],
                    "Logged in",
                    $_SERVER['REMOTE_ADDR'],
                    $_SERVER['REQUEST_URI'],
                    null, // No details for login
                    'success',
                    session_id(),
                    $_SERVER['HTTP_USER_AGENT']
                );

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
                // Log failed login
                logActivity(
                    $conn,
                    null, // No user_id if login fails
                    "Failed login attempt",
                    $_SERVER['REMOTE_ADDR'],
                    $_SERVER['REQUEST_URI'],
                    json_encode(['login_input' => $login_input]),
                    'failure',
                    session_id(),
                    $_SERVER['HTTP_USER_AGENT'],
                    "Invalid credentials"
                );
                $error = "Invalid credentials, please try again.";
            }
        } elseif ($_POST['form_type'] === 'search') {
            // Handle search form submission
            $search_query = trim($_POST['search_query']);
            $sqlAssociations = "SELECT * 
                               FROM alumni_associations 
                               WHERE association_name LIKE :search_query 
                               OR school_name LIKE :search_query 
                               OR address LIKE :search_query";
            $stmtAssociations = $pdo->prepare($sqlAssociations);
            $stmtAssociations->execute(['search_query' => "%$search_query%"]);
            $associations = $stmtAssociations->fetchAll();
        }
    }
}

// Define $search_query for initial page load
$search_query = isset($_POST['search_query']) ? trim($_POST['search_query']) : '';

// Fetch a random association
$sql = "SELECT short_name, school_name, address, contact_person_name FROM alumni_associations ORDER BY RAND() LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $association = $result->fetch_assoc();
} else {
    echo json_encode(["error" => "No associations found"]);
}

// Close connection
$conn->close();

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Magezi Osnet | Alumni Association Platform</title>
<link rel="stylesheet" href="login-system/assets/css/visitors.css">
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
theme: {
extend: {
colors: {
primary: '#1E90FF',
secondary: '#F0F7FF'
},
borderRadius: {
'none': '0px',
'sm': '4px',
DEFAULT: '8px',
'md': '12px',
'lg': '16px',
'xl': '20px',
'2xl': '24px',
'3xl': '32px',
'full': '9999px',
'button': '8px'
}
}
}
}
</script>
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/indexpage.css">
</head>
<body class="bg-white min-h-screen">

<div class="relative overflow-hidden">
<div class="absolute inset-0">
<img src="https://public.readdy.ai/ai/img_res/748e0446a1fe41316372ccdf254ce3f8.jpg" class="w-full h-full object-cover" alt="Hero background">
<div class="absolute inset-0 bg-gradient-to-r from-white via-white/90 to-transparent"></div>
</div>
<div class="container relative mx-auto px-8 py-24 max-w-screen-xl">
    <!-- Positioning the Register link at the top right -->
    <a href="registration/registration.php" class="visitor-link absolute top-4 right-4 text-white font-medium text-lg bg-[#1f3d7a] px-4 py-2 rounded-lg shadow-md hover:bg-[#163d60] transition-all transform hover:scale-105 z-10">
        Register
    </a>

    <div class="flex flex-wrap items-start mx-8">
        <!-- Left Column: Empowerment Text and Buttons -->
        <div class="max-w-2xl w-full md:w-3/5 lg:w-2/3 pl-2">
            <h1 class="text-5xl font-bold mb-6 leading-tight">Empowering Alumni Networks Worldwide</h1>
            <p class="text-xl text-gray-600 mb-8 leading-relaxed">Join our thriving community. Build meaningful connections, access exclusive resources, and create lasting impact together.</p>
            <div class="flex gap-4">
                <a href="registration/registration.php">
                    <button class="bg-[#1f3d7a] text-white px-8 py-4 rounded-button hover:bg-[#163d60] transition-all transform hover:scale-105 whitespace-nowrap font-medium">
                        Get Started Today
                    </button>
                </a>
                <a href="#target-div">
                    <button class="bg-white/80 backdrop-blur border-2 border-primary text-primary px-8 py-4 rounded-button hover:bg-primary/5 transition-all transform hover:scale-105 whitespace-nowrap font-medium">
                        Explore
                    </button>
                </a>
            </div>
        </div>

        <!-- Right Column: Login Form -->
        <div class="w-full md:w-2/5 lg:w-1/3 pr-8">
            <div class="login-container bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-6">Login</h2>
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                    <div class="input-group">
                        <input type="text" name="full_name" placeholder="Username or Email" required>
                    </div>
                    <div class="input-group">
                        <input type="password" name="password" id="password" placeholder="Password" required>
                        <input type="checkbox" id="show-password"> Show Password
                    </div>
                    <input type="hidden" name="form_type" value="login">
                    <button type="submit" class="bg-[#1f3d7a] text-white px-6 py-3 rounded-lg hover:bg-primary/90 transition-all">
                        Login
                    </button>
                    <p class="register-link">Don't have an account? <a href="registration/registration.php">Sign up</a></p>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
<div class="wide-container mx-auto px-3 py-8">
<div class="flex mb-5">
<div class="w-[99.9%] pl-6 space-y-8">
<div class="relative mb-8">
<div class="absolute left-4 top-0 h-12 w-5 flex items-center justify-center text-gray-400">
<i class="ri-search-line"></i>
</div>

<form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
    <div class="search-box">
        <input type="text" name="search_query" placeholder="Search for your alumni association..." value="<?= htmlspecialchars($search_query) ?>" class="w-full h-12 pl-12 pr-4 text-sm border border-gray-200 rounded shadow-sm focus:outline-none focus:border-primary">
        <input type="hidden" name="form_type" value="search">
        <button type="submit" class="join-btn justify-center">Search</button>
    </div>
</form>

        <!-- Results Section -->
        <?php if (!empty($associations)): ?>
            <div class="results">
                <h2>Search Results:</h2>
                <?php foreach ($associations as $association): ?>
                    <div class="association-item">
                        <h3><?= htmlspecialchars($association['association_name']) ?></h3>
                        <table class="details-table">
                            <tr>
                                <th>School Name</th>
                                <td><?= htmlspecialchars($association['school_name']) ?></td>
                            </tr>
                            <tr>
                                <th>Address</th>
                                <td><?= htmlspecialchars($association['address']) ?></td>
                            </tr>
                            <tr>
                                <th>Contact Person Email</th>
                                <td><?= htmlspecialchars($association['contact_person_email']) ?></td>
                            </tr>

                            <tr>
                                <th>Contact Person Email 2</th>
                                <td><?= htmlspecialchars($association['contact_person_email_2']) ?></td>
                            </tr>
                            <tr>
                                <th>Short Name</th>
                                <td><?= htmlspecialchars($association['short_name']) ?></td>
                            </tr>
                            <tr>
                                <th>Created At</th>
                                <td><?= htmlspecialchars($association['created_at']) ?></td>
                            </tr>
                        </table>
                        <button onclick="location.href='/osnet/registration/registration.php';" class="join-btn">Join Now</button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif (isset($_POST['search_query']) && empty($associations)): ?>
            <p>No alumni associations found matching your search.</p>
        <?php endif; ?>

</div>


<div class="mb-8">
  <h2 class="text-xl font-semibold mb-4">Featured Associations</h2>
  <div class="grid grid-cols-2 gap-4">
    <!-- Association 1 Card -->
    <div class="p-4 bg-white rounded-lg shadow-sm border border-gray-100">
      <div class="flex items-center gap-3 mb-3">
        <div class="w-12 h-12 bg-secondary rounded-full flex items-center justify-center">
          <i class="ri-building-4-line text-primary text-xl"></i>
        </div>
        <div>
          <h3 id="assoc-name-1" class="font-medium">Loading...</h3>
          <p id="assoc-school-1" class="text-sm text-gray-500">Fetching school...</p>
        </div>
      </div>
      <p id="assoc-address-1" class="text-sm text-gray-600 mb-3">Fetching address...</p>
      <p id="assoc-contact-1" class="text-sm text-gray-600 mb-3">Fetching contact person...</p>
      <a id="learn-more-btn-1" href="registration/registration.php" class="text-sm text-primary hover:underline inline-block">Learn more</a>
    </div>

    <!-- Association 2 Card -->
    <div class="p-4 bg-white rounded-lg shadow-sm border border-gray-100">
      <div class="flex items-center gap-3 mb-3">
        <div class="w-12 h-12 bg-secondary rounded-full flex items-center justify-center">
          <i class="ri-building-4-line text-primary text-xl"></i>
        </div>
        <div>
          <h3 id="assoc-name-2" class="font-medium">Loading...</h3>
          <p id="assoc-school-2" class="text-sm text-gray-500">Fetching school...</p>
        </div>
      </div>
      <p id="assoc-address-2" class="text-sm text-gray-600 mb-3">Fetching address...</p>
      <p id="assoc-contact-2" class="text-sm text-gray-600 mb-3">Fetching contact person...</p>
      <a id="learn-more-btn-2" href="registration/registration.php" class="text-sm text-primary hover:underline inline-block">Learn more</a>
    </div>
  </div>
</div>
<script>
    function fetchFeaturedAssociation() {
    fetch("fetch_featured.php")
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error("Error fetching association:", data.error);
                return;
            }

            // Update the first association card
            document.getElementById("assoc-name-1").textContent = data[0].short_name;
            document.getElementById("assoc-school-1").textContent = data[0].school_name;
            document.getElementById("assoc-address-1").textContent = data[0].address;
            document.getElementById("assoc-contact-1").textContent = data[0].contact_person_name;

            // Update the second association card
            document.getElementById("assoc-name-2").textContent = data[1].short_name;
            document.getElementById("assoc-school-2").textContent = data[1].school_name;
            document.getElementById("assoc-address-2").textContent = data[1].address;
            document.getElementById("assoc-contact-2").textContent = data[1].contact_person_name;

            
        })
        .catch(error => console.error("Error:", error));
}

// Fetch new associations every 10 seconds
setInterval(fetchFeaturedAssociation, 10000);

// Initial load
fetchFeaturedAssociation();
</script>


<div class="mb-8">
  <h2 class="text-xl font-semibold mb-4">Recent Activities</h2>
  <div class="grid gap-4" id="recent-activities-container">
    <!-- Dynamic cards will be inserted here by JavaScript -->
  </div>
</div>

<!-- JavaScript -->
<script>
    function fetchRecentActivities() {
    fetch("fetch_recent_activities.php")  // Fetch recent activities from the server
        .then(response => response.json())
        .then(data => {
            console.log(data); // Debug log to see what data is being returned
            
            // Get the container where the cards will be inserted
            const activitiesContainer = document.getElementById('recent-activities-container');
            activitiesContainer.innerHTML = ''; // Clear previous activities

            // Loop through the data and create the HTML cards (display only 2)
            data.slice(0, 2).forEach(activity => { // Ensure only 2 cards
                const activityCard = document.createElement('div');
                activityCard.classList.add('p-4', 'bg-white', 'rounded-lg', 'shadow-sm', 'border', 'border-gray-100');

                // Construct the card HTML using data from the server
                const activityContent = `
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="font-medium">${activity.action}</h3>
                            <p class="text-sm text-gray-500">New activity logged</p>
                        </div>
                        <span class="text-sm text-gray-400">${new Date(activity.logged_at).toLocaleString()}</span>
                    </div>
                `;

                activityCard.innerHTML = activityContent;
                activitiesContainer.appendChild(activityCard);  // Append the card to the container
            });
        })
        .catch(error => console.error("Error fetching activities:", error)); // Error handling
}

// Fetch the activities initially
fetchRecentActivities();

// Set an interval to fetch and update activities every 20 seconds
setInterval(fetchRecentActivities, 20000);

</script>

<div>
    <h2 class="text-xl font-semibold mb-4 flex items-center justify-center">New Members</h2>
    <div class="pl-10 flex gap-4 overflow-x-auto pb-4" id="new-members-container">
        <!-- Dynamic cards will be inserted here -->
    </div>
</div>

<script>
    function fetchNewMembers() {
        fetch("fetch_new_members.php")
            .then(response => response.json())
            .then(data => {
                console.log(data); // Debug log

                const membersContainer = document.getElementById('new-members-container');
                if (!membersContainer) {
                    console.error("new-members-container not found in the DOM");
                    return; // Exit if container isn’t found
                }
                membersContainer.innerHTML = ''; // Clear previous members

                data.forEach(user => {
                    const userCard = document.createElement('div');
                    userCard.classList.add('flex-shrink-0', 'w-48', 'p-4', 'bg-white', 'rounded-lg', 'shadow-sm', 'border', 'border-gray-100');

                    // Check if the user has a profile photo, if not use the default icon
                    const userProfilePhoto = user.profile_photo ? user.profile_photo : '';

                    let userProfileImage;
                    if (userProfilePhoto) {
                        // Apply the same dimensions (w-10 h-10) to the profile photo image
                        // Handle different path formats with debugging
                        console.log("Profile Photo Path:", userProfilePhoto); // Debug the raw path
                        let photoSrc = userProfilePhoto;
                        // Check if it’s a relative path or filename, then build the correct URL
                        if (!userProfilePhoto.startsWith('http') && !userProfilePhoto.startsWith('/osnet')) {
                            // Assume it’s a filename or relative path (e.g., uploads/profile_photos/[filename] or just filename)
                            if (userProfilePhoto.startsWith('uploads/profile_photos/')) {
                                photoSrc = `/osnet/${userProfilePhoto}`; // Use as-is with /osnet/ prefix
                            } else if (userProfilePhoto.startsWith('../registration/uploads/profile_photos/')) {
                                photoSrc = `/osnet/${userProfilePhoto.substring(3)}`; // Remove '../' and prepend /osnet/
                            } else {
                                // Default to filename under registration/uploads/profile_photos/
                                photoSrc = `/osnet/registration/uploads/profile_photos/${userProfilePhoto}`;
                            }
                        }
                        console.log("Generated Photo Src:", photoSrc); // Debug the final src
                        userProfileImage = `<img src="${photoSrc}" alt="${user.full_name}'s Profile" class="w-10 h-10 rounded-full object-cover" onerror="this.onerror=null; this.src='/osnet/members/assets/images/social_icon.jpg';">`;
                    } else {
                        // If no profile photo, show the default icon
                        userProfileImage = `<div class="w-10 h-10 bg-secondary rounded-full flex items-center justify-center">
                                                <i class="ri-user-line text-primary"></i>
                                              </div>`;
                    }

                    const userCardContent = `
                        <div class="flex items-center gap-3 mb-2">
                            ${userProfileImage}
                            <div>
                                <h3 class="font-medium">${user.full_name}</h3>
                                <p class="text-xs text-gray-500">${user.school_name} Alumni</p>
                            </div>
                        </div>
                    `;

                    userCard.innerHTML = userCardContent;
                    membersContainer.appendChild(userCard);
                });
            })
            .catch(error => console.error("Error fetching new members:", error));
    }

    // Fetch new members initially
    fetchNewMembers();

    // Set an interval to fetch and update new members every 20 seconds
    setInterval(fetchNewMembers, 20000);
</script>

</div> 

<div class="login w-6 mx-auto">
</div>
</div>

<script>
        // Show/Hide password toggle
        document.getElementById('show-password').addEventListener('change', function() {
            document.getElementById('password').type = this.checked ? 'text' : 'password';
        });
    </script>

<div id="targeted-div" class="bg-gradient-to-b from-secondary to-white py-20">
<div class="container mx-auto px-4">
<div class="text-center max-w-2xl mx-auto mb-12">
<h2 class="text-3xl font-bold mb-4">Unlock Exclusive Benefits</h2>
<p class="text-gray-600">Join our alumni network and gain access to a world of opportunities designed to support your personal and professional growth.</p>
</div>
<div class="grid md:grid-cols-3 gap-8">
<div class="bg-white p-6 rounded-lg shadow-sm">
<div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center mb-4">
<i class="ri-team-line text-primary text-xl"></i>
</div>
<h3 class="text-xl font-medium mb-2">Networking Opportunities</h3>
<p class="text-gray-600">Connect with fellow alumni through exclusive events and our online directory.</p>
</div>
<div class="bg-white p-6 rounded-lg shadow-sm">
<div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center mb-4">
<i class="ri-briefcase-line text-primary text-xl"></i>
</div>
<h3 class="text-xl font-medium mb-2">Career Resources</h3>
<p class="text-gray-600">Access job boards, mentorship programs, and professional development workshops.</p>
</div>
<div class="bg-white p-6 rounded-lg shadow-sm">
<div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center mb-4">
<i class="ri-gift-line text-primary text-xl"></i>
</div>
<h3 class="text-xl font-medium mb-2">Exclusive Perks</h3>
<p class="text-gray-600">Enjoy member-only discounts, library access, and campus facility privileges.</p>
</div>
</div>
</div>
</div>

<div id="target-div" class="wide-container mx-auto px-3 py-20">
    <div class="flex justify-between items-center mb-12">
        <div>
            <h2 class="text-3xl font-bold mb-2">Upcoming Fundraising Campaigns</h2>
            <p class="text-gray-600">Support your fellow alumni in our exciting fundraising initiatives</p>
        </div>
        <!-- "View All Campaigns" Button (Updated to Link) -->
        <a href="all_campaigns.php" class="text-primary hover:text-primary/80 flex items-center gap-2 font-medium border-2 border-primary px-4 py-2 rounded-lg hover:bg-primary/10 transition-all">
            View All Campaigns
            <i class="ri-arrow-right-line"></i>
        </a>
    </div>

    <div class="grid md:grid-cols-3 gap-8" id="campaign-container">
        <!-- Dynamic fundraising campaign cards will be inserted here -->
    </div>
</div>

<script>
    function fetchCampaigns() {
        fetch('fetch_fundraising_campaigns.php')
            .then(response => response.json())
            .then(data => {
                console.log(data); // Debug log

                const campaignContainer = document.getElementById('campaign-container');
                campaignContainer.innerHTML = ''; // Clear previous campaigns

                data.forEach(campaign => {
                    const campaignCard = document.createElement('div');
                    // Apply the new light shiny orange background color and set minimum height
                    campaignCard.classList.add('bg-light-shiny-orange', 'p-6', 'rounded-lg', 'shadow-sm', 'border', 'border-gray-100', 'flex', 'flex-col', 'justify-between', 'h-full');

                    const campaignContent = `
                        <div>
                            <div class="text-primary font-medium mb-2">
                                ${new Date(campaign.start_date).toLocaleDateString()} | ${new Date(campaign.end_date).toLocaleDateString()}
                            </div>
                            <h3 class="text-xl font-medium mb-2">${campaign.title}</h3>
                            <p class="text-light-gray mb-4">${campaign.description}</p> <!-- Light gray description text -->
                            <div class="flex items-center text-sm text-dark-gray mb-2"> <!-- Darker gray location text -->
                                <i class="ri-map-pin-line mr-2"></i>
                                <span>${campaign.address}</span>
                            </div>
                            <div class="text-sm font-medium text-dark-gray mb-4">
                                Organized by: <span class="font-semibold">${campaign.association_name}</span>
                            </div>
                        </div>
                        <!-- Donate Button (Fixed at Bottom) -->
                        <div class="mt-auto">
                            <a href="registration/registration.php"
   class="bg-[#1f3d7a] text-white px-3 py-1.5 rounded-md hover:bg-[#163d60] transition-all block text-center font-bold text-sm !text-white">
    Donate
</a>

                        </div>
                    `;

                    campaignCard.innerHTML = campaignContent;
                    campaignContainer.appendChild(campaignCard);
                });
            })
            .catch(error => console.error("Error fetching campaigns:", error));
    }

    // Fetch campaigns initially
    fetchCampaigns();

    // Set an interval to fetch and update campaigns every 20 seconds
    setInterval(fetchCampaigns, 20000);
</script>

<div class="bg-gradient-to-b from-secondary to-white py-20">
<div class="container mx-auto px-4 py-20">
  <div class="flex justify-between items-center mb-12">
    <div>
      <h2 class="text-3xl font-bold mb-2">Featured Product Promotions</h2>
      <p class="text-gray-600">Exclusive offers and features from our product lineup</p>
    </div>
    <button class="text-primary hover:text-primary/80 flex items-center gap-2 font-medium whitespace-nowrap !rounded-button">
      Post a Promotion
      <i class="ri-add-line"></i>
    </button>
  </div>
  
  <div class="grid md:grid-cols-3 gap-8">
    <!-- MHL ECDP Card -->
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 hover:shadow-md transition-shadow relative">
      <img src="https://magezi.ac.ug/osnet/adverts/images/ecdp.png" alt="MHL ECDP" class="w-full h-48 object-cover rounded-t-lg mb-4" style="filter: brightness(80%);">
      <div class="absolute top-0 left-0 right-0 bottom-0 bg-black opacity-40 rounded-t-lg"></div>
      <div class="relative z-10">
        <div class="flex items-center gap-4 mb-4">
          <div class="w-16 h-16 bg-secondary rounded-lg flex items-center justify-center">
            <i class="ri-laptop-fill text-2xl text-primary"></i>
          </div>
          <div>
            <h3 class="text-xl font-medium text-white">MHL ECDP</h3>
            <p class="text-white text-sm">Content Delivery Platform</p>
          </div>
        </div>
        <p class="text-white mb-4">A simple-to-use platform tailored for learners with varying access to devices and internet connectivity.</p>
        <div class="flex justify-between items-center">
          <span class="text-sm text-gray-200">Accessed via <a href="https://www.magezi.ac.ug" class="text-[#1e3a8a] hover:text-[#1d4ed8]">magezi.ac.ug</a></span>
          <a href="https://mageziharvest.co.ug/mhl-ecdp/" class="text-[#1e3a8a] hover:text-[#1d4ed8] text-lg font-medium whitespace-nowrap !rounded-button">Learn More</a>
        </div>
      </div>
    </div>

    <!-- MHL ERP Card -->
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 hover:shadow-md transition-shadow relative">
      <img src="adverts/images/ero.png" alt="MHL ERP" class="w-full h-48 object-cover rounded-t-lg mb-4" style="filter: brightness(80%);">
      <div class="absolute top-0 left-0 right-0 bottom-0 bg-black opacity-40 rounded-t-lg"></div>
      <div class="relative z-10">
        <div class="flex items-center gap-4 mb-4">
          <div class="w-16 h-16 bg-secondary rounded-lg flex items-center justify-center">
            <i class="ri-database-2-line text-2xl text-primary"></i>
          </div>
          <div>
            <h3 class="text-xl font-medium text-white">MHL ERP</h3>
            <p class="text-white text-sm">Business Management Solution</p>
          </div>
        </div>
        <p class="text-white mb-4">An advanced ERP solution designed for Uganda’s growing business needs, featuring HR and Project Management modules.</p>
        <div class="flex justify-between items-center">
          <span class="text-sm text-gray-200">Custom-built for Ugandan businesses</span>
          <a href="https://mageziharvest.co.ug/mhl-erp/" class="text-[#1e3a8a] hover:text-[#1d4ed8] text-lg font-medium whitespace-nowrap !rounded-button">Learn More</a>
        </div>
      </div>
    </div>

    <!-- WHISTLEBLOWING SOFTWARE Card -->
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 hover:shadow-md transition-shadow relative">
      <img src="adverts/images/whitleblowing.png" alt="Whistleblowing Software" class="w-full h-48 object-cover rounded-t-lg mb-4" style="filter: brightness(80%);">
      <div class="absolute top-0 left-0 right-0 bottom-0 bg-black opacity-40 rounded-t-lg"></div>
      <div class="relative z-10">
        <div class="flex items-center gap-4 mb-4">
          <div class="w-16 h-16 bg-secondary rounded-lg flex items-center justify-center">
            <i class="ri-alert-fill text-2xl text-primary"></i>
          </div>
          <div>
            <h3 class="text-xl font-medium text-white">Whistleblowing Software</h3>
            <p class="text-white text-sm">Employee Engagement Platform</p>
          </div>
        </div>
        <p class="text-white mb-4">FaceUp technology offers a globally recognized whistleblowing software to protect employees and encourage reporting.</p>
        <div class="flex justify-between items-center">
          <span class="text-sm text-gray-200">Official reseller of FaceUp</span>
          <a href="https://mageziharvest.co.ug/whistleblowing-software/" class="text-[#1e3a8a] hover:text-[#1d4ed8] text-lg font-medium whitespace-nowrap !rounded-button">Learn More</a>
        </div>
      </div>
    </div>
  </div>
</div>
</div>





<footer class="bg-gray-900 text-white py-12">
<div class="container mx-auto px-4">
<div class="grid md:grid-cols-4 gap-8">
<div>
<h3 class="font-medium mb-4">Quick Links</h3>
<ul class="space-y-2">
<li><a href="#" class="text-gray-400 hover:text-white">About Us</a></li>
<li><a href="visitor/visitor.php" class="text-gray-400 hover:text-white">Association Directory</a></li>
<li><a href="targeted-div" class="text-gray-400 hover:text-white">Career Services</a></li>
<li><a href="#target-div" class="text-gray-400 hover:text-white">Events Calendar</a></li>
</ul>
</div>
<div>
<h3 class="font-medium mb-4">Resources</h3>
<ul class="space-y-2">
<li><a href="members/pesapal/donations/donations.php" class="text-gray-400 hover:text-white">Giving Back</a></li>

</ul>
</div>
<div>
<h3 class="font-medium mb-4">Contact Us</h3>
<ul class="space-y-2">
<li class="text-gray-400">Plot 425, Block 253</li>
<li class="text-gray-400">Lukuli, Zone 5, Makindye Division</li>
<li class="text-gray-400">Phone:  +256 200 912403</li>
<li class="text-gray-400">Email: info@mageziharvest.co.ug</li>
</ul>
</div>
<div>
<h3 class="font-medium mb-4">Follow Us</h3>
<div class="flex gap-4">
<a href="https://www.linkedin.com/company/magezi-harvest-ltd" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-gray-700">
<i class="ri-linkedin-fill"></i>
</a>
<a href="https://x.com/mageziharvest" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-gray-700">
<i class="ri-twitter-x-fill"></i>
</a>
</div>
</div>
</div>
<div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
<p>&copy; <span id="year"></span> Magezi Osnet. All rights reserved.</p>

<script>
    document.getElementById('year').textContent = new Date().getFullYear();
</script>

</div>
</div>
</footer>
<script>
document.getElementById('loginForm').addEventListener('submit', function(e) {
e.preventDefault();
const formData = new FormData(e.target);
console.log('Login attempt with:', Object.fromEntries(formData));
});
document.querySelectorAll('button').forEach(button => {
  if(button.textContent.trim() === 'Apply Now') {
    button.addEventListener('click', function() {
      const company = this.closest('.bg-white').querySelector('h3').textContent;
      const position = this.closest('.bg-white').querySelector('.text-gray-500').textContent;
      
      const modal = document.createElement('div');
      modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
      modal.innerHTML = `
        <div class="bg-white p-6 rounded-lg max-w-md w-full mx-4">
          <h3 class="text-xl font-medium mb-4">Apply for ${position} at ${company}</h3>
          <p class="text-gray-600 mb-4">Thank you for your interest! Please upload your resume to apply for this position.</p>
          <div class="flex justify-end gap-4">
            <button class="px-4 py-2 text-gray-600 hover:text-gray-800 !rounded-button" onclick="this.closest('.fixed').remove()">Cancel</button>
            <button class="px-4 py-2 bg-primary text-white hover:bg-primary/90 !rounded-button">Upload Resume</button>
          </div>
        </div>
      `;
      document.body.appendChild(modal);
    });
  }
});

    // Rest of your existing JavaScript remains unchanged
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        console.log('Login attempt with:', Object.fromEntries(formData));
    });

    document.querySelectorAll('button').forEach(button => {
        if (button.textContent.trim() === 'Apply Now') {
            button.addEventListener('click', function() {
                const company = this.closest('.bg-white').querySelector('h3').textContent;
                const position = this.closest('.bg-white').querySelector('.text-gray-500').textContent;
                
                const modal = document.createElement('div');
                modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
                modal.innerHTML = `
                    <div class="bg-white p-6 rounded-lg max-w-md w-full mx-4">
                        <h3 class="text-xl font-medium mb-4">Apply for ${position} at ${company}</h3>
                        <p class="text-gray-600 mb-4">Thank you for your interest! Please upload your resume to apply for this position.</p>
                        <div class="flex justify-end gap-4">
                            <button class="px-4 py-2 text-gray-600 hover:text-gray-800 !rounded-button" onclick="this.closest('.fixed').remove()">Cancel</button>
                            <button class="px-4 py-2 bg-primary text-white hover:bg-primary/90 !rounded-button">Upload Resume</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
            });
        }
    });

    function fetchFeaturedAssociation() {
        fetch("fetch_featured.php")
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error("Error fetching association:", data.error);
                    return;
                }
                document.getElementById("assoc-name-1").textContent = data.short_name;
                document.getElementById("assoc-school-1").textContent = data.school_name;
                document.getElementById("assoc-address-1").textContent = data.address;
                document.getElementById("assoc-contact-1").textContent = data.contact_person_name;
            })
            .catch(error => console.error("Error:", error));
    }

    setInterval(fetchFeaturedAssociation, 10000);
    fetchFeaturedAssociation();
</script>
</body>
</html>

