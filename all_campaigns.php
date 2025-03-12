<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'libs/db-connection.php';

// Check connection
if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed: " . $conn->connect_error]));
}

// Handle search query
$search = "";
if (isset($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
}

// Fetch active fundraising campaigns based on search
$sql = "SELECT fc.title, fc.description, fc.start_date, fc.end_date, aa.association_name, aa.address
        FROM fundraising_campaigns fc
        INNER JOIN alumni_associations aa ON fc.association_id = aa.id
        WHERE fc.end_date >= CURDATE()  -- Only fetch ongoing campaigns
        AND (fc.title LIKE '%$search%' OR aa.association_name LIKE '%$search%' OR aa.school_name LIKE '%$search%')
        ORDER BY fc.start_date DESC";

$result = $conn->query($sql);
$campaigns = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $campaigns[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Fundraising Campaigns</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Custom Styling */
        .campaign-card {
            background: linear-gradient(135deg, #FFBB33, #FFD580); /* Soft Orange Gradient */
            color: #333; /* Dark Gray for Readability */
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        }
        .campaign-card h3 {
            color: #1F3D7A; /* Dark Blue Title */
        }
        .campaign-card p {
            color: #4B5563; /* Soft Gray for Description */
        }
        .campaign-card .location {
            color: #374151; /* Dark Gray for Location */
        }
        .campaign-card:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

    </style>
</head>
<body class="bg-gray-100 text-gray-900">

    <!-- Modern Clean Gray Header -->
    <div class="bg-gray-800 text-white py-6 text-center shadow-md">
        <h1 class="text-3xl font-bold">All Fundraising Campaigns</h1>
        <p class="text-gray-300">Support your Association in their exciting initiatives</p>
    </div>

    <!-- Search Section -->
    <div class="container mx-auto px-4 py-6">
        <form method="GET" action="" class="flex justify-center">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                class="border p-2 rounded-lg w-1/2 md:w-1/3"
                placeholder="Search by campaign title, school name, or association name...">
                <button type="submit" class="bg-blue-900 text-white p-2 rounded-lg ml-2 hover:bg-blue-800 transition-all">
    Search
</button>
        </form>
    </div>

    <!-- Main Container -->
    <div class="container mx-auto px-4 py-12">
        <div class="grid md:grid-cols-3 gap-8">
            <?php foreach ($campaigns as $campaign): ?>
                <div class="relative p-6 rounded-lg shadow-md border border-gray-300 hover:shadow-lg transition-all transform hover:scale-105 flex flex-col justify-between"
     style="background: linear-gradient(135deg, #FFBB33, #FFD580); color: #333; height: 100%;">

    <div>
        <div class="font-medium mb-2 text-gray-900">
            Start Date: <?php echo date("M d, Y", strtotime($campaign['start_date'])); ?> 
            | End Date: <?php echo date("M d, Y", strtotime($campaign['end_date'])); ?>
        </div>

        <h3 class="text-xl font-semibold mb-2 text-blue-900"><?php echo htmlspecialchars($campaign['title']); ?></h3>

        <p class="text-gray-800 mb-4"><?php echo htmlspecialchars($campaign['description']); ?></p>

        <div class="text-sm font-medium text-gray-900 mb-4">
            Organized by: <span class="font-semibold"><?php echo htmlspecialchars($campaign['association_name']); ?></span>
        </div>
    </div>

    <!-- Location at bottom-left corner -->
    <div class="text-sm text-gray-700 flex items-center mb-4">
        <i class="ri-map-pin-line mr-2"></i>
        <span><?php echo htmlspecialchars($campaign['address']); ?></span>
    </div>

    <!-- Donate Button at the bottom, fixed without overlap -->
    <div class="mt-auto">
        <a href="registration/registration.php"
           class="bg-blue-900 text-white px-3 py-2 rounded-md hover:bg-[#163d60] transition-all block text-center font-bold text-sm shadow-md">
            Donate
        </a>
    </div>
</div>

              
            <?php endforeach; ?>
            
        </div>

        <!-- Back to Home Button -->
        <div class="text-center mt-12">
        <a href="index.php" class="bg-blue-900 text-white px-6 py-3 rounded-lg shadow-md hover:bg-blue-800 transition-all transform hover:scale-105 font-medium">
    Back to Home
</a>
        </div>
    </div>

</body>
</html>
