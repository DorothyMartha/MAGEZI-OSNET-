<?php
session_start(); // Already at the top from previous fix
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once '../libs/db-connection.php';

// Fetch alumni associations from the database
try {
    $sqlAssociations = "SELECT id, association_name, school_name, short_name, address FROM alumni_associations";
    $stmtAssociations = mysqli_query($conn, $sqlAssociations);
    $associations = mysqli_fetch_all($stmtAssociations, MYSQLI_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching associations: " . $e->getMessage();
}

// Fetch countries from the database
try {
    $sqlCountries = "SELECT name FROM country";
    $stmtCountries = mysqli_query($conn, $sqlCountries);
    $countries = mysqli_fetch_all($stmtCountries, MYSQLI_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching countries: " . $e->getMessage();
}

// Handle file upload for profile photo (Optional)
$profile_photo = null;
$upload_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../registration/uploads/profile_photos/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name = uniqid() . "_" . basename($_FILES['profile_photo']['name']);
        $upload_file = $upload_dir . $file_name;
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($_FILES['profile_photo']['tmp_name']);
        if (in_array($file_type, $allowed_types)) {
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_file)) {
                $upload_message = "File uploaded successfully: <a href='$upload_file'>$upload_file</a>";
                $profile_photo = "../registration/uploads/profile_photos/" . $file_name;
            } else {
                $upload_message = "Error uploading file.";
            }
        } else {
            $upload_message = "Invalid file type.";
        }
    } else {
        $upload_message = "No file uploaded.";
    }
}

// Process form submission (unchanged)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $years_of_attendance = isset($_POST['years_of_attendance']) ? $_POST['years_of_attendance'] : [];
    $occupation = trim($_POST['occupation']);
    $country_of_residence = trim($_POST['country_of_residence']);
    $marital_status = $_POST['marital_status'];
    $association_ids = isset($_POST['association_ids']) ? array_map('intval', (array)$_POST['association_ids']) : [];

    $sqlDefaultCategory = "SELECT id FROM membership_categories WHERE is_default = 1 LIMIT 1";
    $stmtDefaultCategory = mysqli_query($conn, $sqlDefaultCategory);
    $defaultCategory = mysqli_fetch_assoc($stmtDefaultCategory);
    $membership_category_id = $defaultCategory ? $defaultCategory['id'] : null;

    $sqlCheckUser = "SELECT id FROM users WHERE email = ? OR full_name = ?";
    $stmtCheckUser = mysqli_prepare($conn, $sqlCheckUser);
    if ($stmtCheckUser) {
        $stmtCheckUser->bind_param("ss", $email, $full_name);
        $stmtCheckUser->execute();
        $check_result = $stmtCheckUser->get_result();
        if ($check_result->num_rows > 0) {
            $user = $check_result->fetch_assoc();
            $user_id = $user['id'];
            $_SESSION['user_id'] = $user_id;
            $_SESSION['association_ids'] = $association_ids;
            header("Location: add_association.php");
            exit();
        }
    }

    if ($password !== $confirm_password) {
        $error = "Error: Passwords do not match.";
    } elseif (strpos($full_name, ' ') !== false) {
        $error = "Error: Username cannot contain spaces.";
    } else {
        $sqlCheckUsername = "SELECT id FROM users WHERE full_name = ?";
        $stmtCheckUsername = mysqli_prepare($conn, $sqlCheckUsername);
        if ($stmtCheckUsername) {
            $stmtCheckUsername->bind_param("s", $full_name);
            $stmtCheckUsername->execute();
            $username_result = $stmtCheckUsername->get_result();
            if ($username_result->num_rows > 0) {
                $error = "Error: Username already exists.";
            } else {
                $sqlCheckEmail = "SELECT id FROM users WHERE email = ?";
                $stmtCheckEmail = mysqli_prepare($conn, $sqlCheckEmail);
                if ($stmtCheckEmail) {
                    $stmtCheckEmail->bind_param("s", $email);
                    $stmtCheckEmail->execute();
                    $email_result = $stmtCheckEmail->get_result();
                    if ($email_result->num_rows > 0) {
                        $error = "Error: Email already exists.";
                    }
                }
            }
        }
    }

    if (empty($error) && $membership_category_id !== null) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $primary_association_id = !empty($association_ids) ? $association_ids[0] : null;
        try {
            $sqlUser = "INSERT INTO users 
            (full_name, email, phone_number, password_hash, occupation, 
            country_of_residence, marital_status, association_id, membership_category_id, profile_photo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtUser = mysqli_prepare($conn, $sqlUser);
            if ($stmtUser === false) {
                throw new Exception("Failed to prepare statement: " . mysqli_error($conn));
            }
            if (empty($full_name) || empty($email) || empty($phone_number) || empty($password_hash) || 
                empty($occupation) || empty($country_of_residence) || empty($marital_status)) {
                throw new Exception("Missing required user data.");
            }
            if ($primary_association_id === null) {
                throw new Exception("No primary association selected.");
            }
            if ($membership_category_id === null) {
                throw new Exception("Default membership category not found.");
            }
            $stmtUser->bind_param("sssssssiis", $full_name, $email, $phone_number, $password_hash, $occupation, $country_of_residence, $marital_status, $primary_association_id, $membership_category_id, $profile_photo);
            $stmtUser->execute();
            $user_id = $conn->insert_id;
            if (!empty($association_ids)) {
                $sqlAssoc = "INSERT INTO user_associations (user_id, association_id, years_of_attendance) 
                             VALUES (?, ?, ?)";
                $stmtAssoc = mysqli_prepare($conn, $sqlAssoc);
                if ($stmtAssoc === false) {
                    throw new Exception("Failed to prepare association statement: " . mysqli_error($conn));
                }
                foreach ($association_ids as $assoc_id) {
                    $years = isset($years_of_attendance[$assoc_id]) && is_array($years_of_attendance[$assoc_id]) 
                        ? trim(implode('-', array_filter([
                            $years_of_attendance[$assoc_id]['start'] ?? '',
                            $years_of_attendance[$assoc_id]['end'] ?? ''
                        ]))) : null;
                    $stmtAssoc->bind_param("iis", $user_id, $assoc_id, $years);
                    $stmtAssoc->execute();
                }
            }
            header("Location: /osnet/index.php?registration=success");
            exit();
        } catch (Exception $e) {
            $error = "Registration failed: " . $e->getMessage();
        }
    } elseif ($membership_category_id === null) {
        $error = "Error: Default membership category not found.";
    } elseif (empty($association_ids)) {
        $error = "Error: Please select at least one alumni association.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAGEZI OSNET Registration</title>
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            padding: 1rem;
            background: #f3f4f6;
        }

        .container {
            display: flex;
            max-width: 1200px;
            width: 100%;
            min-height: 80vh;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .form-section-wrapper {
            flex: 0 0 60%;
            padding: 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto; /* Allow scrolling if content overflows */
        }

        .image-section {
            flex: 0 0 40%;
            position: relative;
            background: url('https://images.unsplash.com/photo-1501785888041-af3ef285b470?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') no-repeat center center;
            background-size: cover;
        }

        .image-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(30, 58, 138, 0.7), rgba(20, 184, 166, 0.5));
            z-index: 1;
        }

        .image-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #ffffff;
            text-align: center;
            z-index: 2;
            padding: 1rem;
        }

        .image-text h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(90deg, #ffffff, #d1fae5);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .image-text p {
            font-size: 1.2rem;
            color: #e5e7eb;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 2rem;
            color: #1e3a8a;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: #6b7280;
            font-size: 1rem;
        }

        label {
            font-weight: 600;
            color: #1f2937;
            font-size: 1rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        input,
        select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
            color: #1f2937;
            background: #ffffff;
            transition: border-color 0.3s ease, box-shadow 0.3s ease, transform 0.2s ease;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #1e3a8a;
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.2);
            transform: translateY(-2px);
        }

        /* Styles for association search and list */
        #association-search {
            margin-bottom: 1rem;
        }

        #association-list {
            max-height: 200px;
            overflow-y: auto;
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 0.5rem;
            display: none;
        }

        #association-list.visible {
            display: block;
        }

        #association-list .option {
            padding: 0.5rem;
            display: flex;
            align-items: center;
        }

        #association-list input[type="checkbox"] {
            margin-right: 0.5rem;
        }

        #selected-associations {
            margin-top: 1rem;
        }

        #selected-associations > div {
            background: #f3f4f6;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
        }

        #selected-associations .remove {
            color: #dc2626;
            font-size: 1.2rem;
            cursor: pointer;
        }

        .years-picker {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .years-picker select {
            width: auto;
            min-width: 100px;
        }

        .error-message,
        .upload-message {
            margin-bottom: 1.5rem;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid;
            text-align: left;
            font-size: 0.95rem;
        }

        .error-message {
            background: #fee2e2;
            color: #dc2626;
            border-color: #dc2626;
        }

        .upload-message {
            background: #d1fae5;
            color: #047857;
            border-color: #047857;
        }

        .submit-btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(90deg, #1e3a8a 0%, #14b8a6 100%);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(20, 184, 166, 0.3);
        }

        .submit-btn.loading {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .submit-btn.loading::after {
            content: "⏳";
            margin-left: 0.5rem;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                max-width: 100%;
                min-height: auto;
            }

            .form-section-wrapper,
            .image-section {
                flex: 0 0 100%;
                height: 400px;
            }

            .header h1 {
                font-size: 1.5rem;
            }

            .image-text h2 {
                font-size: 2rem;
            }

            .image-text p {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-section-wrapper">
            <div class="header">
                <h1>Alumni Registration</h1>
                <p>Join our community and stay connected</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($upload_message)): ?>
                <div class="upload-message"><?php echo $upload_message; ?></div>
            <?php endif; ?>

            <form method="post" action="registration.php" enctype="multipart/form-data">
                <div class="form-section">
                    <label for="full_name">UserName</label>
                    <input type="text" name="full_name" id="full_name" required placeholder="Enter your Username">

                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" required placeholder="your.email@example.com">

                    <label for="phone_number">Phone Number</label>
                    <input type="tel" name="phone_number" id="phone_number" required placeholder="Enter your phone number">

                    <label for="profile_photo">Profile Photo</label>
                    <input type="file" name="profile_photo" id="profile_photo" accept="image/*" required>

                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required placeholder="Create a strong password">

                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" required placeholder="Confirm your password">
                </div>

                <div class="form-section">
                    <label for="association-search">Alumni Associations (Select all that apply)</label>
                    <input type="text" id="association-search" placeholder="Search for your alumni associations..." required>
                    <div id="association-list"></div>
                    <div id="selected-associations"></div>
                    <div class="association-ids"></div>

                    <label for="occupation">Occupation</label>
                    <input type="text" name="occupation" id="occupation" required placeholder="Current occupation">

                    <label for="country_of_residence">Country of Residence</label>
                    <select name="country_of_residence" id="country_of_residence" required>
                        <option value="">Select your country</option>
                        <?php foreach ($countries as $country): ?>
                            <option value="<?= htmlspecialchars($country['name']); ?>">
                                <?= htmlspecialchars($country['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="marital_status">Marital Status</label>
                    <select name="marital_status" id="marital_status" required>
                        <option value="">Select status</option>
                        <option value="Single">Single</option>
                        <option value="Married">Married</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <button type="submit" class="submit-btn">Complete Registration</button>
            </form>
        </div>
        <div class="image-section">
            <div class="image-text">
                <h2>Get Started today</h2>
                <p>Welcome to MAGEZI OSNET</p>
            </div>
        </div>
    </div>

    <script>
    const associations = <?php echo json_encode($associations); ?>;
    const searchInput = document.getElementById('association-search');
    const assocList = document.getElementById('association-list');
    const selectedAssocDiv = document.getElementById('selected-associations');
    const assocIdsContainer = document.querySelector('.association-ids');
    const form = document.querySelector('form');
    const submitBtn = document.querySelector('.submit-btn');

    let selectedAssociations = [];

    const currentYear = new Date().getFullYear();
    function generateYearOptions() {
        let options = '<option value="">Year</option>';
        for (let year = 1950; year <= currentYear + 5; year++) {
            options += `<option value="${year}">${year}</option>`;
        }
        return options;
    }

    function renderAssociations(filter = '') {
        assocList.innerHTML = '';
        const filtered = associations.filter(assoc => 
            assoc.association_name.toLowerCase().includes(filter.toLowerCase()) || 
            assoc.short_name.toLowerCase().includes(filter.toLowerCase())
        );

        filtered.forEach(assoc => {
            const isSelected = selectedAssociations.some(sel => sel.id === assoc.id);
            const option = document.createElement('div');
            option.className = 'option';
            option.innerHTML = `
                <input type="checkbox" id="assoc-${assoc.id}" ${isSelected ? 'checked' : ''}>
                <label for="assoc-${assoc.id}">${assoc.association_name} (${assoc.short_name}) - ${assoc.school_name} (${assoc.address})</label>
            `;
            const checkbox = option.querySelector('input');
            const label = option.querySelector('label');
            option.innerHTML = '';
            option.appendChild(checkbox);
            option.appendChild(label);
            option.querySelector('input').addEventListener('change', (e) => {
                if (e.target.checked) {
                    selectedAssociations.push({ id: assoc.id, name: `${assoc.association_name} (${assoc.short_name})`, years: '' });
                } else {
                    selectedAssociations = selectedAssociations.filter(sel => sel.id !== assoc.id);
                }
                updateSelectedDisplay();
            });
            assocList.appendChild(option);
        });

        assocList.classList.add('visible');
    }

    function updateSelectedDisplay() {
        selectedAssocDiv.innerHTML = '';
        assocIdsContainer.innerHTML = '';
        selectedAssociations.forEach((assoc, index) => {
            const div = document.createElement('div');
            div.innerHTML = `
                <span>${assoc.name} <span class="remove" data-id="${assoc.id}">×</span></span>
                <div class="years-picker">
                    <select name="years_of_attendance[${assoc.id}][start]" onchange="updateYears(${assoc.id}, this)">
                        ${generateYearOptions()}
                    </select>
                    <span>-</span>
                    <select name="years_of_attendance[${assoc.id}][end]" onchange="updateYears(${assoc.id}, this)">
                        ${generateYearOptions()}
                    </select>
                </div>
            `;
            div.querySelector('.remove').addEventListener('click', () => {
                selectedAssociations = selectedAssociations.filter(sel => sel.id !== assoc.id);
                updateSelectedDisplay();
            });
            selectedAssocDiv.appendChild(div);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'association_ids[]';
            input.value = assoc.id;
            assocIdsContainer.appendChild(input);
        });
    }

    function updateYears(assocId, selectElement) {
        const assoc = selectedAssociations.find(sel => sel.id === assocId);
        const startYear = document.querySelector(`select[name="years_of_attendance[${assocId}][start]"]`).value;
        const endYear = document.querySelector(`select[name="years_of_attendance[${assocId}][end]"]`).value;
        if (startYear && endYear) {
            assoc.years = `${startYear}-${endYear}`;
        } else if (startYear) {
            assoc.years = startYear;
        } else if (endYear) {
            assoc.years = endYear;
        } else {
            assoc.years = '';
        }
    }

    searchInput.addEventListener('focus', () => renderAssociations(''));
    searchInput.addEventListener('input', (e) => renderAssociations(e.target.value));
    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !assocList.contains(e.target)) {
            assocList.classList.remove('visible');
        }
    });

    form.addEventListener('submit', (e) => {
        if (selectedAssociations.length === 0) {
            e.preventDefault();
            alert('Please select at least one alumni association.');
            return;
        }
        submitBtn.classList.add('loading');
        submitBtn.textContent = 'Registering...';
        setTimeout(() => {
            submitBtn.classList.remove('loading');
            submitBtn.textContent = 'Complete Registration';
        }, 2000);
    });
    </script>
</body>
</html>