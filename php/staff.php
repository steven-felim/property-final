<?php
session_start();
require_once './db_connection.php';
// Cek login
if (!isset($_SESSION['user_email']) && $_SESSION['user_role'] == 'staff') {
    header("Location: index.php");
    exit();
}

$userEmail = $_SESSION['user_email'];
$userRole = $_SESSION['user_role'];
$postition = $_SESSION['sPosition'] ?? '';

// Fetch all properties
$properties = [];
$sql = "SELECT p.propertyNo, p.street, p.city, p.rent, p.pType, po.fName AS ownerFName, po.lName AS ownerLName
        FROM propertyforrent p
        JOIN privateowner po ON p.ownerNo = po.ownerNo
        ORDER BY p.propertyNo DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $properties[] = $row;
    }
}

// Fetch all clients and owners for registration
$clients = [];
$owners = [];
$res = $conn->query("SELECT clientNo, fName, lName FROM cclient");
while ($row = $res->fetch_assoc())
    $clients[] = $row;
$res = $conn->query("SELECT ownerNo, fName, lName FROM privateowner");
while ($row = $res->fetch_assoc())
    $owners[] = $row;

// Handle register client to property owner
$registerMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_client'])) {
    $clientNo = $_POST['clientNo'];
    $ownerNo = $_POST['ownerNo'];    // Assign client to owner's property (example: update registration table)
    $sql = "UPDATE registration SET branchNo = (SELECT branchNo FROM propertyforrent WHERE ownerNo = ? LIMIT 1) WHERE clientNo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $ownerNo, $clientNo);
    if ($stmt->execute()) {
        $registerMsg = "Client successfully registered to property owner.";
    } else {
        $registerMsg = "Failed to register client.";
    }
    $stmt->close();
}

// Handle property removal by staff
$removeMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_property'])) {
    $propertyNo = $_POST['propertyNo'];
    $stmt = $conn->prepare("DELETE FROM propertyforrent WHERE propertyNo = ?");
    $stmt->bind_param("s", $propertyNo);
    if ($stmt->execute()) {
        $removeMsg = "Property removed successfully.";
        header("Refresh:0"); // auto refresh
        exit();
    } else {
        $removeMsg = "Failed to remove property.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Staff Dashboard</title>
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
                    <?php if (in_array($userRole, ['staff', 'property_owner'])): ?>
                        <li><a href="viewing.php">Viewing</a></li>
                    <?php endif; ?>
                    <li><a href="profile.php">Profile</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container-staff" style="margin-top: 40px;">
        <h1>Staff Dashboard</h1>

        <!-- 1. View Property -->
        <section>
            <h2>All Properties</h2>
            <?php if (!empty($removeMsg)): ?>
                <div class="success-message"><?php echo htmlspecialchars($removeMsg); ?></div>
            <?php endif; ?>
            <table border="1" cellpadding="8" style="width:100%;margin-bottom:30px;">
                <tr>
                    <th>No</th>
                    <th>Street</th>
                    <th>City</th>
                    <th>Type</th>
                    <th>Rent</th>
                    <th>Owner</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($properties as $p): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($p['propertyNo']); ?></td>
                        <td><?php echo htmlspecialchars($p['street']); ?></td>
                        <td><?php echo htmlspecialchars($p['city']); ?></td>
                        <td><?php echo htmlspecialchars($p['pType']); ?></td>
                        <td>$<?php echo number_format($p['rent'], 0, ',', '.'); ?></td>
                        <td><?php echo htmlspecialchars($p['ownerFName'] . ' ' . $p['ownerLName']); ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="propertyNo"
                                    value="<?php echo htmlspecialchars($p['propertyNo']); ?>">
                                <button type="submit" name="remove_property"
                                    onclick="return confirm('Are you sure you want to remove this property?');">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </section>

        <!-- 2. Register Client to Property Owner -->
        <section>
            <h2>Register Client to Property Owner</h2>
            <?php if (!empty($registerMsg)): ?>
                <div class="success-message"><?php echo htmlspecialchars($registerMsg); ?></div>
            <?php endif; ?>
            <form method="post" style="margin-bottom:30px;">
                <input type="hidden" name="register_client" value="1">
                <label>Client:
                    <select name="clientNo" required>
                        <?php foreach ($clients as $c): ?>
                            <option value="<?php echo htmlspecialchars($c['clientNo']); ?>">
                                <?php echo htmlspecialchars($c['clientNo'] . ' - ' . $c['fName'] . ' ' . $c['lName']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Property Owner:
                    <select name="ownerNo" required>
                        <?php foreach ($owners as $o): ?>
                            <option value="<?php echo htmlspecialchars($o['ownerNo']); ?>">
                                <?php echo htmlspecialchars($o['ownerNo'] . ' - ' . $o['fName'] . ' ' . $o['lName']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit">Register</button>
            </form>
        </section>
    </div>

    <footer>
        <div class="container">
            <p>&copy; 2025 HBProperty | All Rights Reserved</p>
        </div>
    </footer>
</body>

</html>