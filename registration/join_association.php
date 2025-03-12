<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once '../libs/db-connection.php';
// Fetch alumni associations from the database
try {
    $sqlAssociations = "SELECT id, association_name, school_name, short_name, address FROM alumni_associations";
    $stmtAssociations = mysqli_query($conn, $sqlAssociations);
    $associations = mysqli_fetch_all($stmtAssociations, MYSQLI_ASSOC);
} catch (Exception $e) {
    $error = "Error fetching associations: " . $e->getMessage();
}

$error = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $association_ids = isset($_POST['association_ids']) ? array_map('intval', (array)$_POST['association_ids']) : [];
    $years_of_attendance = isset($_POST['years_of_attendance']) ? $_POST['years_of_attendance'] : [];

    // Validate email
    if (empty($email)) {
        $error = "Error: Email is required.";
    } else {
        // Check if user exists
        $sqlCheckUser = "SELECT id FROM users WHERE email = ?";
        $stmtCheckUser = mysqli_prepare($conn, $sqlCheckUser);
        if ($stmtCheckUser) {
            $stmtCheckUser->bind_param("s", $email);
            $stmtCheckUser->execute();
            $check_result = $stmtCheckUser->get_result();
            if (mysqli_num_rows($check_result) > 0) {
                $user = $check_result->fetch_assoc();
                $user_id = $user['id'];

                if (!empty($association_ids)) {
                    $sqlAssoc = "INSERT IGNORE INTO user_associations (user_id, association_id, years_of_attendance) 
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
                    $success_message = "Successfully added new association(s)!";
                } else {
                    $error = "Error: Please select at least one alumni association.";
                }
            } else {
                $error = "Error: No user found with the provided email.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Association - MAGEZI OSNET</title>
    <link rel="stylesheet" href="assets/css/registration.css">
    <style>
        /* Minimal styling to match existing form */
        .form-section {
            margin-bottom: 2rem;
            position: relative;
        }

        label {
            font-weight: 600;
            display: block;
            margin-bottom: 0.75rem;
            color: #374151;
            font-size: 1rem;
            position: relative;
        }

        input,
        select {
            width: 100%;
            padding: 1rem 1.5rem 1rem 2.5rem;
            margin-bottom: 1.5rem;
            border: 2px solid #d1d5db;
            border-radius: 12px;
            font-size: 1rem;
            color: #1f2937;
            background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: border-color 0.3s ease, box-shadow 0.3s ease, transform 0.2s ease;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #1f3d7a;
            box-shadow: 0 0 12px rgba(31, 61, 122, 0.3), 0 4px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        #association-list {
            display: none;
            position: absolute;
            width: 1000px;
            left: 0;
            transform: none;
            position: absolute;
            top: calc(100% + 2px);
            max-height: 200px;
            overflow-y: auto;
            background: #ffffff;
            border: 2px solid #d1d5db;
            border-top: none;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        #association-list.visible {
            display: block;
        }

        #association-list option {
            padding: 0.5rem 1rem;
            cursor: pointer;
            font-size: 0.85rem;
            color: #1f2937;
            display: flex;
            align-items: center;
            transition: background-color 0.2s ease;
        }

        #association-list option:hover {
            background-color: #f3f4f6;
        }

        #association-list option input[type="checkbox"] {
            margin-right: 10px;
            width: 14px;
            height: 14px;
        }

        .years-picker {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1rem;
        }

        .years-picker select {
            padding: 0.5rem;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.95rem;
            width: 100px;
        }

        .years-picker select:focus {
            border-color: #1f3d7a;
            box-shadow: 0 0 8px rgba(31, 61, 122, 0.2);
        }

        .error-message, .success-message {
            margin-bottom: 1.5rem;
            text-align: center;
            padding: 0.75rem;
            border-radius: 8px;
            border: 1px solid;
        }

        .error-message {
            color: #dc2626;
            background: #fee2e2;
            border-color: #fecaca;
        }

        .success-message {
            color: #065f46;
            background: #d1fae5;
            border-color: #34d399;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Join New Association</h1>
            <p>Add a new association to your existing membership</p>
        </div>

        <!-- Display error message if any -->
        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Display success message if any -->
        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <form method="post" action="join_association.php">
            <div class="form-section">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" required placeholder="Enter your email">

                <label for="association-search">New Associations to Join (Select all that apply)</label>
                <input type="text" id="association-search" placeholder="Search for your alumni associations..." required>
                <div id="association-list"></div>
                <div id="selected-associations"></div>
                <div class="association-ids">
                    <!-- Hidden inputs will be dynamically added here -->
                </div>
            </div>

            <button type="submit" class="submit-btn">Join Association</button>
        </form>
    </div>

    <script>
    // Pass PHP associations array to JavaScript
    const associations = <?php echo json_encode($associations); ?>;
    const searchInput = document.getElementById('association-search');
    const assocList = document.getElementById('association-list');
    const selectedAssocDiv = document.getElementById('selected-associations');
    const assocIdsContainer = document.querySelector('.association-ids');
    const form = document.querySelector('form');
    const submitBtn = document.querySelector('.submit-btn');

    // Array to track selected associations with years
    let selectedAssociations = [];

    // Generate year options (e.g., 1950 to current year + 5)
    const currentYear = new Date().getFullYear();
    function generateYearOptions() {
        let options = '<option value="">Year</option>';
        for (let year = 1950; year <= currentYear + 5; year++) {
            options += `<option value="${year}">${year}</option>`;
        }
        return options;
    }

    // Function to render association options
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

    // Function to update selected associations display and hidden inputs with years
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

    // Update years of attendance for an association
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

    // Show all associations on focus
    searchInput.addEventListener('focus', () => renderAssociations(''));

    // Filter associations on input
    searchInput.addEventListener('input', (e) => renderAssociations(e.target.value));

    // Hide list when clicking outside
    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !assocList.contains(e.target)) {
            assocList.classList.remove('visible');
        }
    });

    // Validate and submit form
    form.addEventListener('submit', (e) => {
        if (selectedAssociations.length === 0) {
            e.preventDefault();
            alert('Please select at least one alumni association.');
            return;
        }
    });
</script>
</body>

</html>