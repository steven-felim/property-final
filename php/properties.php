<?php
    session_start();

    if (!isset($_SESSION['user_email'])) {
        header("Location: index.php");
        exit();
    }

    $userEmail = $_SESSION['user_email'];
    $userRole = $_SESSION['user_role'];
    require_once './db_connection.php';

    // If property_owner, get their ownerNo
    $myOwnerNo = null;
    if ($userRole === 'property_owner') {
        $stmt = $conn->prepare("SELECT ownerNo FROM privateowner WHERE eMail = ?");
        $stmt->bind_param("s", $userEmail);
        $stmt->execute();
        $stmt->bind_result($myOwnerNo);
        $stmt->fetch();
        $stmt->close();
    }

    // Fetch all properties with their images and ownerNo
    $properties = [];
    if ($userRole === 'property_owner' && $myOwnerNo) {        $sql = "SELECT p.propertyNo, p.street, p.city, p.rent, p.pType, p.ownerNo, pi.image 
                FROM propertyforrent p 
                LEFT JOIN propertyimage pi ON p.propertyNo = pi.propertyNo 
                WHERE p.ownerNo = ? 
                ORDER BY p.propertyNo DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $myOwnerNo);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $properties[] = $row;
            }
        }
        $stmt->close();
    } else {        $sql = "SELECT p.propertyNo, p.street, p.city, p.rent, p.pType, p.ownerNo, pi.image 
                FROM propertyforrent p 
                LEFT JOIN propertyimage pi ON p.propertyNo = pi.propertyNo 
                ORDER BY p.propertyNo DESC";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $properties[] = $row;
            }
        }
    }
    // Do not close $conn yet, as we may need it for other actions
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Properties - Property Renting Website</title>
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
                    <?php if($userRole === 'staff'): ?>
                        <li><a href="staff.php">Home</a></li>
                        <li><a href=#>Staff Dashboard</a></li>
                    <?php else: ?>
                        <li><a href="homepage.php">Home</a></li>
                    <?php endif; ?>
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
            <h1>All Properties</h1>
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'property_owner'): ?>
                <div style="margin-bottom: 20px;">
                    <a href="add-property.php" class="btn-add-property">+ Add Property</a>
                </div>
            <?php endif; ?>
            <div class="property-list" id="property-list">
                <?php if (count($properties) > 0): ?>
                    <?php foreach ($properties as $property): ?>
                        <div class="property-card">
                            <img src="../img/<?php echo $property['image'] ? htmlspecialchars($property['image']) : 'no-image-available.png'; ?>" alt="<?php echo htmlspecialchars($property['pType']); ?>">
                            <h3><?php echo htmlspecialchars($property['pType']); ?> - <?php echo htmlspecialchars($property['city']); ?></h3>
                            <p>$<?php echo htmlspecialchars($property['rent']); ?>/month</p>
                            <p><?php echo htmlspecialchars($property['street']); ?></p>
                            <div class="property-actions">
                                <a href="property.php?id=<?php echo urlencode($property['propertyNo']); ?>" class="btn-view-details">View Details</a>
                                <?php if ($userRole === 'property_owner' && $myOwnerNo && $property['ownerNo'] === $myOwnerNo): ?>
                                    <a href="edit-property.php?propertyNo=<?php echo urlencode($property['propertyNo']); ?>" class="btn-edit-property">Edit</a>
                                    <form action="delete-property.php" method="post" style="display:inline;">
                                        <input type="hidden" name="propertyNo" value="<?php echo htmlspecialchars($property['propertyNo']); ?>">
                                        <button type="submit" class="btn-delete-property" onclick="return confirm('Are you sure you want to delete this property?');">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </div>
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
<?php $conn->close(); ?>
