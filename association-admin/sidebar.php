<!-- Include Font Awesome CDN in your <head> (add this if not already in header.php) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<style>
    /* Scrolling styles from previous request */
    .sidebar ul {
        max-height: calc(100vh - 50px);
        overflow-y: auto;
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    .sidebar.dropdown-menu::-webkit-scrollbar {
        display: none;
    }
    
    .sidebar ul::-webkit-scrollbar {
        display: none;
    }

    .dropdown-menu li a {
            padding: 4px; /* Match main links */
            font-size: 8px; /* Slightly smaller for compactness */
            margin-top: 2px;
            margin-bottom: 2px;
        }
    .h2 {

        font-size: 15px;
        margin-top: 5px;
    }
</style>

<!-- Sidebar with Icons -->
<div class="sidebar">
    <h2>Alumni Association Admin</h2>
    <ul>
        <li><a href="associationadmin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="fundraising_campaigns.php"><i class="fas fa-hand-holding-usd"></i> Fundraising Campaigns</a></li>
        <li><a href="withdraw_funds.php"><i class="fas fa-money-check-alt"></i> Withdraw Funds</a></li>
        <li><a href="addnewmember.php"><i class="fas fa-users-cog"></i> Add New Members</a></li>
        
        <li class="dropdown">
            <a href="membership_categories.php" class="dropdown-toggle"><i class="fas fa-cog"></i> Settings ▼</a>
            <ul class="dropdown-menu">
                <li><a href="membership_categories.php"><i class="fas fa-users-cog"></i> Manage Membership Categories</a></li>
            </ul>
        </li>
        <li><a href="customize_association.php"><i class="fas fa-paint-brush"></i> Customize Association Page</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>
<!-- Toggle Button for mobile -->
<div class="toggle-btn">☰</div>

<!-- Dropdown Menu for mobile (icons added here too for consistency) -->
<div class="dropdown-menu">
    <a href="associationadmin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="fundraising_campaigns.php"><i class="fas fa-hand-holding-usd"></i> Fundraising Campaigns</a>
    <a href="withdraw_funds.php"><i class="fas fa-money-check-alt"></i> Withdraw Funds</a>
</div>