<?php
// Database connection parameters
$host = 'pdb1992.awardspace.com';
$db   = 'ivnape_osnet';
$db_user = 'ivnape_osnet';
$db_pass = "?K4mp4l4311";
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handling the search query
$associations = [];
$search_query = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['search_query'])) {
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Alumni Associations</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        .container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto;
        }

        h1 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #333;
        }

        .search-box {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .search-box input[type="text"] {
            width: 80%;
            padding: 10px;
            font-size: 1rem;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        .search-box button {
            width: 15%;
            padding: 10px;
            background-color: #1f3d7a;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .search-box button:hover {
            background-color: #1557b0;
        }

        .results {
            margin-top: 1.5rem;
        }

        .association-item {
            background-color: #f8f9fa;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .association-item h3 {
            margin: 0;
            color: #1f3d7a;
        }

        .association-item p {
            color: #555;
            font-size: 0.9rem;
        }

        .association-item a {
            text-decoration: none;
            color: #1f3d7a;
            font-weight: bold;
        }

        .association-item a:hover {
            text-decoration: underline;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .details-table th,
        .details-table td {
            padding: 8px;
            border: 1px solid #ccc;
            text-align: left;
        }

        .details-table th {
            background-color: #f1f1f1;
        }

        .join-btn {
    display: inline-block;
    padding: 10px 20px;
    background-color: #1f3d7a;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-weight: bold;
    transition: background 0.3s;
}

.join-btn:hover {
    background-color: #1557b0;
}

    </style>
</head>

<body>

    <div class="container">
        <h1>Search Alumni Associations</h1>

        <!-- Search Form -->
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="search-box">
                <input type="text" name="search_query" placeholder="Enter school name or association" value="<?= htmlspecialchars($search_query) ?>">
                <button type="submit">Search</button>
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

</body>

</html>
