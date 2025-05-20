<?php
    session_start();

    if (!isset($_SESSION['user_email'])) {
        header("Location: index.php");
        exit();
    }

    $userEmail = $_SESSION['user_email'];
    $userRole = $_SESSION['user_role'];
    require_once './db_connection.php';
    // Fetch all properties with their images
    $properties = [];
    $sql = "SELECT p.propertyNo, p.street, p.city, p.rent, p.pType, pi.image FROM PropertyForRent p LEFT JOIN PropertyImage pi ON p.propertyNo = pi.propertyNo ORDER BY p.propertyNo DESC";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $properties[] = $row;
        }
    }
    $conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Viewings - Property Renting Website</title>
    <link rel="stylesheet" href="../css/styles.css">
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
    </header>

    <section class="properties">
        <div class="container" style="margin-top: 100px;">
            <h1>All Viewings</h1>
            <div class="property-list" id="property-list">
                <?php if (count($properties) > 0): ?>
                    <?php foreach ($properties as $property): ?>
                        <div class="property-card">
                            <img src="../img/<?php echo $property['image'] ? htmlspecialchars($property['image']) : 'no-image-available.png'; ?>" alt="<?php echo htmlspecialchars($property['pType']); ?>">
                            <h3><?php echo htmlspecialchars($property['pType']); ?> - <?php echo htmlspecialchars($property['city']); ?></h3>
                            <p>$<?php echo htmlspecialchars($property['rent']); ?>/month</p>
                            <p><?php echo htmlspecialchars($property['street']); ?></p>
                            <a href="view.php?propertyNo=<?php echo urlencode($property['propertyNo']); ?>">View Details</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No properties found.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>&copy; 2025 HBProperty | All Rights Reserved</p>
        </div>
    </footer>
</body>
</html>
