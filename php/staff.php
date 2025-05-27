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

// Handle staff removal
$removeStaffMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_staff'])) {
    $staffNo = $_POST['staffNo'];
    $stmt = $conn->prepare("DELETE FROM staff WHERE staffNo = ?");
    $stmt->bind_param("s", $staffNo);
    if ($stmt->execute()) {
        $removeStaffMsg = "Staff removed successfully.";
        header("Refresh:0"); // auto refresh
        exit();
    } else {
        $removeStaffMsg = "Failed to remove staff.";
    }
    $stmt->close();
}

// Handle add staff by admin
$addStaffMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
    $staffFName = $_POST['staff_fname'];
    $staffLName = $_POST['staff_lname'];
    $staffEmail = $_POST['staff_email'];
    $staffPass = password_hash($_POST['staff_pass'], PASSWORD_DEFAULT);
    $staffPosition = $_POST['staff_position'];

    // Generate staffNo sesuai posisi
    $prefix = ($staffPosition === 'admin') ? 'A' : 'S';
    $result = $conn->query("SELECT staffNo FROM staff WHERE staffNo LIKE '{$prefix}%' ORDER BY staffNo DESC LIMIT 1");
    $lastNo = 0;
    if ($result && $row = $result->fetch_assoc()) {
        $lastNo = intval(substr($row['staffNo'], 1));
    }
    $newNo = $lastNo + 1;
    $staffNo = $prefix . str_pad($newNo, 2, '0', STR_PAD_LEFT);

    // Pilih branchNo default (atau bisa dari input)
    $branchNo = 'B001';

    // Cek apakah email sudah ada
    $check = $conn->prepare("SELECT * FROM staff WHERE eMail = ?");
    $check->bind_param("s", $staffEmail);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $addStaffMsg = "Staff email already exists.";
    } else {
        $stmt = $conn->prepare("INSERT INTO staff (staffNo, fName, lName, eMail, password, sPosition, branchNo) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $staffNo, $staffFName, $staffLName, $staffEmail, $staffPass, $staffPosition, $branchNo);
        if ($stmt->execute()) {
            $addStaffMsg = "Staff added successfully.";
        } else {
            $addStaffMsg = "Failed to add staff.";
        }
        $stmt->close();
    }
    $check->close();
}

if (isset($_GET['ajax_staff_search'])) {
    $q = $_GET['ajax_staff_search'];
    $data = [];
    $stmt = $conn->prepare("SELECT staffNo, fName, lName FROM staff WHERE sPosition != 'admin' AND (staffNo LIKE ? OR fName LIKE ? OR lName LIKE ?)");
    $like = "%$q%";
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'staffNo' => $row['staffNo'],
            'name' => $row['staffNo'] . ' - ' . $row['fName'] . ' ' . $row['lName']
        ];
    }
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
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
                        <li><a href="staff.php">Staff Dashboard</a></li>
                    <?php else: ?>
                        <li><a href="homepage.php">Home</a></li>
                    <?php endif; ?>
                    <?php if (($postition ?? '') === 'admin'): ?>
                        <li><a href="xml-admin-report.php">XML Report</a></li>
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

        <!-- 3. Remove Staff (Admin only) -->
        <?php if ($postition === 'admin'): ?>
        <section>
            <h2>Remove Staff</h2>
            <?php if (!empty($removeStaffMsg)): ?>
                <div class="success-message"><?php echo htmlspecialchars($removeStaffMsg); ?></div>
            <?php endif; ?>
            <label>Search Staff:
                <input type="text" id="staffSearch" placeholder="Search staff by name or code..." autocomplete="off">
            </label>
            <div style="overflow-x:auto;">
                <table border="1" cellpadding="8" style="width:100%;margin-bottom:30px;" id="staffTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Email</th>
                            <th>Position</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    // Ambil semua staff (kecuali admin utama)
                    $staffList = [];
                    $res = $conn->query("SELECT staffNo, fName, lName, eMail, sPosition FROM staff WHERE sPosition != 'admin'");
                    while ($row = $res->fetch_assoc()) {
                        $staffList[] = $row;
                    }
                    foreach ($staffList as $s): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['staffNo']); ?></td>
                            <td><?php echo htmlspecialchars($s['fName']); ?></td>
                            <td><?php echo htmlspecialchars($s['lName']); ?></td>
                            <td><?php echo htmlspecialchars($s['eMail']); ?></td>
                            <td><?php echo htmlspecialchars($s['sPosition']); ?></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="remove_staff" value="1">
                                    <input type="hidden" name="staffNo" value="<?php echo htmlspecialchars($s['staffNo']); ?>">
                                    <button type="submit" class="btn-remove-staff" onclick="return confirm('Are you sure you want to remove this staff?');">Remove</button>
                                </form>
                                <form method="get" action="edit-profileStaff.php" style="display:inline;">
                                    <input type="hidden" name="staffNo" value="<?php echo htmlspecialchars($s['staffNo']); ?>">
                                    <button type="submit" class="btn-edit-staff">Edit</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>

        <!-- 4. Add Staff (Admin only) -->
        <?php if ($postition === 'admin'): ?>
        <section>
            <h2>Add New Staff</h2>
            <?php if (!empty($addStaffMsg)): ?>
                <div class="success-message"><?php echo htmlspecialchars($addStaffMsg); ?></div>
            <?php endif; ?>
            <form method="post" style="margin-bottom:30px;">
                <input type="hidden" name="add_staff" value="1">
                <label>First Name:
                    <input type="text" name="staff_fname" required>
                </label>
                <label>Last Name:
                    <input type="text" name="staff_lname">
                </label>
                <label>Email:
                    <input type="email" name="staff_email" required>
                </label>
                <label>Password:
                    <input type="password" name="staff_pass" required>
                </label>
                <label>Position:
                    <select name="staff_position" required>
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                </label>
                <button type="submit">Add Staff</button>
            </form>
        </section>
        <?php endif; ?>
    </div>

    <footer>
        <div class="container">
            <p>&copy; 2025 HBProperty | All Rights Reserved</p>
        </div>
    </footer>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    const staffSearch = document.getElementById('staffSearch');
    const staffTable = document.getElementById('staffTable');
    if (staffSearch && staffTable) {
        staffSearch.addEventListener('input', function() {
            const filter = staffSearch.value.toLowerCase();
            const rows = staffTable.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }
});
</script>
</body>

</html>