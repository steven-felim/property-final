<?php
    session_start();

    if (!isset($_SESSION['user_email'])) {
        header("Location: index.php");
        exit();
    }

    $userEmail = $_SESSION['user_email'];
    $userRole = $_SESSION['user_role'];

    require_once './db_connection.php';

    // Fetch clientNo for the logged-in user-------> kalo tambah tabel ini buat rented properties client
    $clientNo = null;
    if ($userRole === 'client') {
        $clientNoQuery = "SELECT clientNo FROM CClient WHERE email = ?";
        $stmtClient = $conn->prepare($clientNoQuery);
        $stmtClient->bind_param("s", $userEmail);
        $stmtClient->execute();
        $resultClient = $stmtClient->get_result();
        $clientRow = $resultClient->fetch_assoc();
        if ($clientRow) {
            $clientNo = $clientRow['clientNo'];
        }
        $stmtClient->close();
    }

    // Fetch viewed properties if user is a client  -------> kalo tambah tabel ini buat rented properties client
    $rentedProperties = [];
    if ($userRole === 'client' && $clientNo) {
        $rentQuery = "
            SELECT p.street, p.city, p.pType
            FROM viewing v
            JOIN propertyforrent p ON v.propertyNo = p.propertyNo
            WHERE v.clientNo = ?
        ";
        $stmtRent = $conn->prepare($rentQuery);
        $stmtRent->bind_param("s", $clientNo);
        $stmtRent->execute();
        $resultRent = $stmtRent->get_result();
        while ($row = $resultRent->fetch_assoc()) {
            $rentedProperties[] = $row;
        }
        $stmtRent->close();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Profile - Property Renting Website</title>
    <link rel="stylesheet" href="../css/styles.css?v=<?php echo time(); ?>">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <img src="../img/logo.png" alt="Logo" class="logo-img">
            </div>
            <nav>
                <ul>
                    <li><a href="homepage.php">Home</a></li>
                    <li><a href="properties.php">Properties</a></li>
                    <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['staff', 'property_owner'])): ?>
                        <li><a href="viewing.php">Viewing</a></li>
                    <?php endif; ?>
                    <li><a href="profile.php">Profile</a></li>
                </ul>
            </nav>
        </div>
    </header>    </header>


    <section class="profile">
        <div class="container">
            <h1>Your Profile</h1>
            <p><strong>Name:</strong> <?php
                $tableMap = [
                    "client" => "CClient",
                    "property_owner" => "PrivateOwner",
                    "staff" => "Staff"
                ];

                if (!isset($tableMap[$userRole])) {
                    $error = "Invalid role selected.";
                } else {
                    $userQuery = "SELECT fname, lname FROM {$tableMap[$userRole]} WHERE email = ?";
                    $stmt = $conn->prepare($userQuery);
                    $stmt->bind_param("s", $userEmail);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();

                    if ($user) {
                        echo htmlspecialchars($user['fname']) . " " . htmlspecialchars($user['lname']);
                    } else {
                        echo "User not found.";
                    }
                }

                $stmt->close();
                $conn->close();
            ?></p>
            <p><strong>Email:</strong> <?php echo $userEmail; ?></p>
            <p><strong>Viewed Properties:</strong></p>
            <ul>
                <?php if (!empty($rentedProperties)): ?>
                    <?php foreach ($rentedProperties as $property): ?>
                        <li>
                            <?php echo htmlspecialchars($property['street']) . ", " . htmlspecialchars($property['city']) . " (" . htmlspecialchars($property['pType']) . ")"; ?>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>No viewed properties found.</li>
                <?php endif; ?>
            </ul>
        </div>
    </section>

    <div class="container" style="display: flex; justify-content: center; align-items: center; margin-top: 20px; gap: 10px;">
        <form action="index.php" method="post" style="display: inline;">
            <input type="hidden" name="logout" value="1">
            <button type="submit" class="btn-logout">Log Out</button>
        </form>
        <a href="edit-profile.php" class="btn-edit-profile">Edit Profile</a>
    </div>
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
        session_unset();
        session_destroy();
        header("Location: index.php");
        exit();
    }
    ?>

    <footer>
        <div class="container">
            <p>&copy; 2025 HBProperty | All Rights Reserved</p>
        </div>
    </footer>
</body>
</html>
