<?php
session_start();
require_once './db_connection.php';

if (!isset($_SESSION['user_email']) || $_SESSION['user_role'] != 'staff') {
    header("Location: index.php");
    exit();
}

$userEmail = $_SESSION['user_email'];
$userRole = $_SESSION['user_role'];
$position = $_SESSION['sPosition'] ?? '';

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
    $ownerNo = $_POST['ownerNo'];    // Assign client to owner's property
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

// Set page variables for header
$pageTitle = "Staff Dashboard - HBProperty";
$bodyClass = "staff-dashboard";
$additionalHead = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">';

// Include header
include 'header.php';
?>

    <div class="container-staff">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <h1><i class="fas fa-users-cog"></i> Staff Dashboard</h1>
            <p class="dashboard-subtitle">Manage properties, clients, and staff members</p>
            <div class="user-greeting">
                <div class="greeting-text">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Staff Member'); ?>!</div>
                <div class="user-role"><?php echo ucfirst($position); ?> Dashboard</div>
            </div>
        </div>

        <!-- 1. View Properties Section -->
        <section class="dashboard-section">
            <div class="section-header">
                <h2>
                    <span class="section-icon"><i class="fas fa-home"></i></span>
                    All Properties
                    <span class="section-count"><?php echo count($properties); ?></span>
                </h2>
            </div>
            <div class="section-body">
                <?php if (!empty($removeMsg)): ?>
                    <div class="message success">
                        <span class="message-icon"><i class="fas fa-check-circle"></i></span>
                        <?php echo htmlspecialchars($removeMsg); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($properties)): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-home"></i></div>
                        <div class="empty-title">No Properties Found</div>
                        <div class="empty-message">There are currently no properties in the system.</div>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="enhanced-table">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-hashtag"></i> Property ID</th>
                                    <th><i class="fas fa-map-marker-alt"></i> Location</th>
                                    <th><i class="fas fa-building"></i> Type</th>
                                    <th><i class="fas fa-dollar-sign"></i> Rent</th>
                                    <th><i class="fas fa-user"></i> Owner</th>
                                    <th><i class="fas fa-cogs"></i> Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($properties as $p): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($p['propertyNo']); ?></td>
                                        <td>
                                            <div class="property-cell">
                                                <div class="property-icon">
                                                    <i class="fas fa-home"></i>
                                                </div>
                                                <div class="property-details">
                                                    <div class="property-address"><?php echo htmlspecialchars($p['street']); ?></div>
                                                    <div class="property-type"><?php echo htmlspecialchars($p['city']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($p['pType']); ?></td>
                                        <td>
                                            <span class="price-display">$<?php echo number_format($p['rent'], 0, ',', '.'); ?></span>
                                        </td>
                                        <td>
                                            <div class="owner-info">
                                                <div class="owner-avatar">
                                                    <?php echo strtoupper(substr($p['ownerFName'], 0, 1) . substr($p['ownerLName'], 0, 1)); ?>
                                                </div>
                                                <div class="owner-name">
                                                    <?php echo htmlspecialchars($p['ownerFName'] . ' ' . $p['ownerLName']); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="propertyNo" value="<?php echo htmlspecialchars($p['propertyNo']); ?>">
                                                    <button type="submit" name="remove_property" class="btn-action btn-remove"
                                                        onclick="return confirm('Are you sure you want to remove this property?');">
                                                        <i class="fas fa-trash"></i> Remove
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- 2. Register Client to Property Owner -->
        <section class="dashboard-section">
            <div class="section-header">
                <h2>
                    <span class="section-icon"><i class="fas fa-user-plus"></i></span>
                    Client Registration
                </h2>
            </div>
            <div class="section-body">
                <?php if (!empty($registerMsg)): ?>
                    <div class="message success">
                        <span class="message-icon"><i class="fas fa-check-circle"></i></span>
                        <?php echo htmlspecialchars($registerMsg); ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" class="enhanced-form">
                    <input type="hidden" name="register_client" value="1">
                    <div class="form-grid">
                        <div class="form-field">
                            <label for="clientNo">
                                <i class="fas fa-user"></i> Select Client
                            </label>
                            <select name="clientNo" id="clientNo" required>
                                <option value="">Choose a client...</option>
                                <?php foreach ($clients as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c['clientNo']); ?>">
                                        <?php echo htmlspecialchars($c['clientNo'] . ' - ' . $c['fName'] . ' ' . $c['lName']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="ownerNo">
                                <i class="fas fa-building"></i> Property Owner
                            </label>
                            <select name="ownerNo" id="ownerNo" required>
                                <option value="">Choose an owner...</option>
                                <?php foreach ($owners as $o): ?>
                                    <option value="<?php echo htmlspecialchars($o['ownerNo']); ?>">
                                        <?php echo htmlspecialchars($o['ownerNo'] . ' - ' . $o['fName'] . ' ' . $o['lName']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Register Client
                    </button>
                </form>
            </div>
        </section>

        <!-- 3. Staff Management (Admin only) -->
        <?php if ($position === 'admin'): ?>
            <!-- Remove Staff Section -->
            <section class="dashboard-section">
                <div class="section-header">
                    <h2>
                        <span class="section-icon"><i class="fas fa-users"></i></span>
                        Staff Management
                        <span class="admin-badge">Admin Only</span>
                    </h2>
                </div>
                <div class="section-body">
                    <?php if (!empty($removeStaffMsg)): ?>
                        <div class="message success">
                            <span class="message-icon"><i class="fas fa-check-circle"></i></span>
                            <?php echo htmlspecialchars($removeStaffMsg); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="staffSearch" class="search-input" 
                               placeholder="Search staff by name or ID..." autocomplete="off">
                    </div>
                    
                    <div style="overflow-x: auto;">
                        <table class="enhanced-table" id="staffTable">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-id-badge"></i> Staff ID</th>
                                    <th><i class="fas fa-user"></i> Name</th>
                                    <th><i class="fas fa-envelope"></i> Email</th>
                                    <th><i class="fas fa-briefcase"></i> Position</th>
                                    <th><i class="fas fa-cogs"></i> Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $staffList = [];
                            $res = $conn->query("SELECT staffNo, fName, lName, eMail, sPosition FROM staff WHERE sPosition != 'admin'");
                            while ($row = $res->fetch_assoc()) {
                                $staffList[] = $row;
                            }
                            foreach ($staffList as $s): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($s['staffNo']); ?></td>
                                    <td>
                                        <div class="property-cell">
                                            <div class="property-icon">
                                                <?php echo strtoupper(substr($s['fName'], 0, 1) . substr($s['lName'], 0, 1)); ?>
                                            </div>
                                            <div class="property-details">
                                                <div class="property-address"><?php echo htmlspecialchars($s['fName'] . ' ' . $s['lName']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($s['eMail']); ?></td>
                                    <td>
                                        <span class="admin-badge" style="background: var(--secondary-color);">
                                            <?php echo htmlspecialchars($s['sPosition']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="remove_staff" value="1">
                                                <input type="hidden" name="staffNo" value="<?php echo htmlspecialchars($s['staffNo']); ?>">
                                                <button type="submit" class="btn-action btn-remove"
                                                    onclick="return confirm('Are you sure you want to remove this staff member?');">
                                                    <i class="fas fa-trash"></i> Remove
                                                </button>
                                            </form>
                                            <form method="get" action="edit-profileStaff.php" style="display:inline;">
                                                <input type="hidden" name="staffNo" value="<?php echo htmlspecialchars($s['staffNo']); ?>">
                                                <button type="submit" class="btn-action btn-edit">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Add Staff Section -->
            <section class="dashboard-section">
                <div class="section-header">
                    <h2>
                        <span class="section-icon"><i class="fas fa-user-plus"></i></span>
                        Add New Staff
                        <span class="admin-badge">Admin Only</span>
                    </h2>
                </div>
                <div class="section-body">
                    <?php if (!empty($addStaffMsg)): ?>
                        <div class="message <?php echo strpos($addStaffMsg, 'successfully') !== false ? 'success' : 'error'; ?>">
                            <span class="message-icon">
                                <i class="fas fa-<?php echo strpos($addStaffMsg, 'successfully') !== false ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                            </span>
                            <?php echo htmlspecialchars($addStaffMsg); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" class="enhanced-form">
                        <input type="hidden" name="add_staff" value="1">
                        <div class="form-grid">
                            <div class="form-field">
                                <label for="staff_fname">
                                    <i class="fas fa-user"></i> First Name
                                </label>
                                <input type="text" name="staff_fname" id="staff_fname" required placeholder="Enter first name">
                            </div>
                            <div class="form-field">
                                <label for="staff_lname">
                                    <i class="fas fa-user"></i> Last Name
                                </label>
                                <input type="text" name="staff_lname" id="staff_lname" placeholder="Enter last name">
                            </div>
                            <div class="form-field">
                                <label for="staff_email">
                                    <i class="fas fa-envelope"></i> Email
                                </label>
                                <input type="email" name="staff_email" id="staff_email" required placeholder="Enter email address">
                            </div>
                            <div class="form-field">
                                <label for="staff_pass">
                                    <i class="fas fa-lock"></i> Password
                                </label>
                                <input type="password" name="staff_pass" id="staff_pass" required placeholder="Enter password">
                            </div>
                            <div class="form-field">
                                <label for="staff_position">
                                    <i class="fas fa-briefcase"></i> Position
                                </label>
                                <select name="staff_position" id="staff_position" required>
                                    <option value="">Select position...</option>
                                    <option value="staff">Staff</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-plus"></i> Add Staff Member
                        </button>
                    </form>
                </div>
            </section>
        <?php endif; ?>
    </div>

<?php
// Set additional footer scripts
$additionalFooterScripts = '
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Staff search functionality
        const staffSearch = document.getElementById("staffSearch");
        const staffTable = document.getElementById("staffTable");
        
        if (staffSearch && staffTable) {
            staffSearch.addEventListener("input", function() {
                const filter = staffSearch.value.toLowerCase();
                const rows = staffTable.querySelectorAll("tbody tr");
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(filter)) {
                        row.style.display = "";
                        row.classList.add("fade-in");
                    } else {
                        row.style.display = "none";
                    }
                });
            });
        }

        // Add smooth animations
        const sections = document.querySelectorAll(".dashboard-section");
        sections.forEach((section, index) => {
            section.style.animationDelay = `${index * 0.1}s`;
            section.classList.add("fade-in");
        });

        // Enhanced table hover effects
        const tableRows = document.querySelectorAll(".enhanced-table tbody tr");
        tableRows.forEach(row => {
            row.addEventListener("mouseenter", function() {
                this.style.transform = "scale(1.02)";
            });
            row.addEventListener("mouseleave", function() {
                this.style.transform = "scale(1)";
            });
        });
    });

    // Add loading states for forms
    document.querySelectorAll("form").forEach(form => {
        form.addEventListener("submit", function() {
            const submitBtn = this.querySelector("button[type=\"submit\"]");
            if (submitBtn) {
                submitBtn.innerHTML = "<i class=\"fas fa-spinner fa-spin\"></i> Processing...";
                submitBtn.disabled = true;
            }
        });
    });
</script>';

// Include footer
include 'footer.php';
?>