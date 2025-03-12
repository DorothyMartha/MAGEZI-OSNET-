<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sidebar</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>

    <style>
        /* Sidebar Links */
        .sidebar ul {
            list-style: none;
            padding: 0;
            width: 100%;
            margin: 0;
        }

        .sidebar ul li {
            width: 100%;
            margin-bottom: 5px;
        }

        .sidebar ul li a {
            display: flex;
            align-items: center;
            padding: 8px;
            font-size: 14px;
            color: #fff;
            text-decoration: none;
            transition: background 0.3s ease-in-out, color 0.3s;
            border-radius: 5px;
            line-height: 1;
        }

        /* Sidebar Icons */
        .sidebar ul li a i {
            margin-right: 15px !important;
            font-size: 24px !important;
        }

        /* Hover Effect with #1f3d7a */
        .sidebar ul li a:hover {
            background-color: #1f3d7a;
            color: #ffffff;
        }

        /* Logout Button Styling */
        .logout-btn {
            color: #d9534f;
            font-weight: 600;
        }

        .logout-btn:hover {
            background-color: #f8d7da;
        }

        /* Ensure sidebar is scrollable at all times without visible scrollbar */
        .sidebar {
            height: 100vh; /* Full viewport height */
            overflow-y: scroll; /* Always scrollable */
            -webkit-overflow-scrolling: touch; /* Smooth scrolling on touch devices */
            scrollbar-width: none; /* Hide scrollbar in Firefox */
            -ms-overflow-style: none; /* Hide scrollbar in IE/Edge */
        }

        .sidebar::-webkit-scrollbar {
            display: none; /* Hide scrollbar in Chrome/Safari */
        }

        /* Dropdown Styling */
        .dropdown .dropdown-menu {
            display: none; /* Hidden by default */
            list-style: none;
            padding: 0 0 0 5px; /* Indent left only, no top/bottom padding for vertical flow */
            margin: 0;
            border: none; /* Remove any border */
        }

        .dropdown.active .dropdown-menu {
            display: block; /* Show when active class is added */
        }

        .dropdown-menu li a {
            padding: 8px; /* Match main links */
            font-size: 13px; /* Slightly smaller for compactness */
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <ul>
            <li><a href="admin-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="alumni.php"><i class="fas fa-users"></i> Associations</a></li>
            <li><a href="withdrawals.php"><i class="fas fa-money-bill-wave"></i> Withdrawals</a></li>
            <li><a href="access_logs.php"><i class="fas fa-history"></i> View Access Logs</a></li>
            <li><a href="/osnet/members/reports.php"><i class="fas fa-file-alt"></i> Generate Reports</a></li>

            <li class="dropdown">
                <a href="settings.php" class="dropdown-toggle"><i class="fas fa-cog"></i> Settings ▼</a>
                <ul class="dropdown-menu">
                    <li><a href="payment_processing_fees.php"><i class="fas fa-credit-card"></i> Payment Processing Fees</a></li>
                    <li><a href="country_codes.php"><i class="fas fa-globe"></i> Set Country Codes</a></li>
                    <li><a href="assign_association_admin.php"><i class="fas fa-user-shield"></i> Association Admins</a></li>
                </ul>
            </li>

            <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <script>
        // Toggle dropdown on click
        document.querySelector('.dropdown-toggle').addEventListener('click', function(e) {
            e.preventDefault(); // Prevent default link behavior
            const dropdown = this.parentElement; // Get the parent .dropdown li
            dropdown.classList.toggle('active'); // Toggle active class
        });
    </script>
</body>
</html>