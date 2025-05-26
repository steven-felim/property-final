<?php
session_start();

if (!isset($_SESSION['user_email'])) {
    header("Location: index.php");
    exit();
}

$userEmail = $_SESSION['user_email'];
$userRole = $_SESSION['user_role'];

require_once './db_connection.php';

$tableMap = [
    "client" => "cclient",
    "property_owner" => "privateowner", 
    "staff" => "staff"
];

if (!isset($tableMap[$userRole])) {
    die("Invalid role.");
}

$table = $tableMap[$userRole];

// Prepare role-specific SELECT query to get all needed fields
switch ($userRole) {
    case 'client':
        $query = "SELECT clientNo, fname, lname, telNo, prefType, maxRent FROM $table WHERE eMail = ?";
        break;

    case 'property_owner':
        $query = "SELECT ownerNo, fname, lname, street, city, postcode, telNo FROM $table WHERE eMail = ?";
        break;

    case 'staff':
        $query = "SELECT fname, lname, sPosition, sex, DOB, salary FROM $table WHERE email = ?";
        break;

    default:
        die("Invalid role.");
}

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $userEmail);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User not found.");
}

// Extract clientNo or ownerNo for further queries
$clientNo = $ownerNo = null;
if ($userRole === 'client') {
    $clientNo = $user['clientNo'];    // Ambil info branch dan staff dari registration
    $stmt = $conn->prepare("
        SELECT b.street AS branchStreet, b.city AS branchCity, s.fName AS staffFName, s.lName AS staffLName
        FROM registration r
        LEFT JOIN branch b ON r.branchNo = b.branchNo
        LEFT JOIN staff s ON r.staffNo = s.staffNo
        WHERE r.clientNo = ?
    ");
    $stmt->bind_param("s", $clientNo);
    $stmt->execute();
    $result = $stmt->get_result();
    $regInfo = $result->fetch_assoc();
    $stmt->close();
} elseif ($userRole === 'property_owner') {
    $ownerNo = $user['ownerNo'];
}

// Fetch viewed properties (client only)
$viewedProperties = [];
if ($userRole === 'client' && $clientNo) {
    $stmt = $conn->prepare("
        SELECT p.street, p.city, p.pType
        FROM viewing v
        JOIN propertyforrent p ON v.propertyNo = p.propertyNo
        WHERE v.clientNo = ?
    ");
    $stmt->bind_param("s", $clientNo);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $viewedProperties[] = $row;
    }
    $stmt->close();
}

// Fetch rented properties (client only)
$rentedProperties = [];
if ($userRole === 'client' && $clientNo) {
    $stmt = $conn->prepare("
        SELECT p.street, p.city, p.pType, r.rentStart, r.rentEnd
        FROM rent r
        JOIN propertyforrent p ON r.propertyNo = p.propertyNo
        WHERE r.clientNo = ?
    ");
    $stmt->bind_param("s", $clientNo);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $rentedProperties[] = $row;
    }
    $stmt->close();
}

// Fetch owned properties (property_owner only)
$ownedProperties = [];
if ($userRole === 'property_owner' && $ownerNo) {
    $stmt = $conn->prepare("
        SELECT street, city, pType, rent
        FROM propertyforrent
        WHERE ownerNo = ?
    ");
    $stmt->bind_param("s", $ownerNo);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $ownedProperties[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Your Profile - Property Renting Website</title>
    <link rel="stylesheet" href="../css/styles.css?v=<?php echo time(); ?>" />
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <img src="../img/logo.png" alt="Logo" class="logo-img" />
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
                    <?php if (in_array($userRole, ['staff', 'property_owner'])): ?>
                        <li><a href="viewing.php">Viewing</a></li>
                    <?php endif; ?>
                    <li><a href="profile.php">Profile</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="profile">
        <div class="container">
            <h1>Your Profile</h1>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($user['fname'] . ' ' . $user['lname']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($userEmail); ?></p>

            <?php if ($userRole === 'client'): ?>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['telNo']); ?></p>
                <p><strong>Preferred Type:</strong> <?php echo htmlspecialchars($user['prefType']); ?></p>
                <p><strong>Max Rent:</strong> $<?php echo htmlspecialchars($user['maxRent']); ?></p>
                <?php if (!empty($regInfo)): ?>
                    <p><strong>Registered Branch:</strong> <?php echo htmlspecialchars($regInfo['branchStreet'] . ', ' . $regInfo['branchCity']); ?></p>
                    <p><strong>Assigned Staff:</strong> <?php echo htmlspecialchars($regInfo['staffFName'] . ' ' . $regInfo['staffLName']); ?></p>
                <?php else: ?>
                    <p><strong>Registered Branch:</strong> -</p>
                    <p><strong>Assigned Staff:</strong> -</p>
                <?php endif; ?>

                <h3>Viewed Properties</h3>
                <ul>
                    <?php if (!empty($viewedProperties)): ?>
                        <?php foreach ($viewedProperties as $property): ?>
                            <li><?php echo htmlspecialchars("{$property['street']}, {$property['city']} ({$property['pType']})"); ?></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>No viewed properties found.</li>
                    <?php endif; ?>
                </ul>

                <h3>Rented Properties</h3>
                <ul>
                    <?php if (!empty($rentedProperties)): ?>
                        <?php foreach ($rentedProperties as $property): ?>
                            <li>
                                <?php echo htmlspecialchars("{$property['street']}, {$property['city']} ({$property['pType']})"); ?>
                                — Rented from <?php echo htmlspecialchars($property['rentStart']); ?> to <?php echo htmlspecialchars($property['rentEnd']); ?>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>No rented properties found.</li>
                    <?php endif; ?>
                </ul>

            <?php elseif ($userRole === 'property_owner'): ?>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['telNo']); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars("{$user['street']}, {$user['city']} {$user['postcode']}"); ?></p>

                <h3>Owned Properties</h3>
                <ul>
                    <?php if (!empty($ownedProperties)): ?>
                        <?php foreach ($ownedProperties as $property): ?>
                            <li><?php echo htmlspecialchars("{$property['street']}, {$property['city']} ({$property['pType']}) - Rent: £{$property['rent']}"); ?></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>No owned properties found.</li>
                    <?php endif; ?>
                </ul>

            <?php elseif ($userRole === 'staff'): ?>
                <p><strong>Position:</strong> <?php echo htmlspecialchars($user['sPosition']); ?></p>
                <p><strong>Sex:</strong> <?php echo htmlspecialchars($user['sex']); ?></p>
                <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($user['DOB']); ?></p>
                <p><strong>Salary:</strong> $<?php echo htmlspecialchars($user['salary']); ?></p>
            <?php endif; ?>
        </div>
    </section>

    <div class="container" style="display: flex; justify-content: center; align-items: center; margin-top: 20px; gap: 10px;">
        <form action="index.php" method="post" style="display: inline;">
            <input type="hidden" name="logout" value="1" />
            <button type="submit">Log Out</button>
        </form>
        <button type="button" style="margin-left: 10px;" onclick="window.location.href='edit-profile.php'">Edit Profile</button>
    </div>

    <?php
    // Logout handling: better to place this at the top or separate logout script,
    // but keeping here for your current structure.
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
        session_unset();
        session_destroy();
        header("Location: index.php");
        exit();
    }

    $conn->close();
    ?>

    <footer>
        <div class="container">
            <p>&copy; 2025 HBProperty | All Rights Reserved</p>
        </div>
    </footer>
</body>
</html>
