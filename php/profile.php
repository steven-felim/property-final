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
        $query = "SELECT clientNo, fName, lName, telNo, prefType, maxRent FROM $table WHERE eMail = ?";
        break;

    case 'property_owner':
        $query = "SELECT ownerNo, fName, lName, street, city, postcode, telNo FROM $table WHERE eMail = ?";
        break;

    case 'staff':
        $query = "SELECT staffNo, fName, lName, sPosition, sex, DOB, salary, branchNo FROM $table WHERE eMail = ?";
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
    $clientNo = $user['clientNo'];
    // Get registration info
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
        SELECT p.street, p.city, p.pType, v.viewDate
        FROM viewing v
        JOIN propertyforrent p ON v.propertyNo = p.propertyNo
        WHERE v.clientNo = ?
        ORDER BY v.viewDate DESC
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
        SELECT p.street, p.city, p.pType, r.rentStart, r.rentEnd, p.rent
        FROM rent r
        JOIN propertyforrent p ON r.propertyNo = p.propertyNo
        WHERE r.clientNo = ?
        ORDER BY r.rentStart DESC
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
        SELECT street, city, pType, rent, rooms, ownerNo
        FROM propertyforrent
        WHERE ownerNo = ?
        ORDER BY street, city
    ");
    $stmt->bind_param("s", $ownerNo);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $ownedProperties[] = $row;
    }
    $stmt->close();
}

// Get user initials for avatar
$initials = strtoupper(substr($user['fName'], 0, 1) . substr($user['lName'], 0, 1));

// Handle logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

// Set page variables for header
$pageTitle = "Profile - " . htmlspecialchars($user['fName'] . ' ' . $user['lName']) . " | HBProperty";
$pageDescription = "Manage your account and view your activity on HBProperty.";
$bodyClass = "profile";
$additionalHead = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">';

// Include header
include 'header.php';
?>

<main class="profile">
    <div class="container">
        <div class="profile-container">
            <div class="profile-header">
                <h1>My Profile</h1>
                <p class="subtitle">Manage your account and view your activity</p>
            </div>

            <div class="profile-layout">
                <!-- Profile Card -->
                <div class="profile-card">
                    <div class="profile-avatar">
                        <div class="avatar-circle">
                            <?php echo $initials; ?>
                        </div>
                        <div class="profile-name"><?php echo htmlspecialchars($user['fName'] . ' ' . $user['lName']); ?></div>
                        <div class="profile-role"><?php echo ucfirst(str_replace('_', ' ', $userRole)); ?></div>
                    </div>

                    <ul class="profile-info-list">
                        <li class="profile-info-item">
                            <i class="fas fa-envelope info-icon"></i>
                            <div class="info-content">
                                <span class="info-label">Email</span>
                                <span class="info-value"><?php echo htmlspecialchars($userEmail); ?></span>
                            </div>
                        </li>

                        <?php if ($userRole === 'staff'): ?>
                            <li class="profile-info-item">
                                <i class="fas fa-id-badge info-icon"></i>
                                <div class="info-content">
                                    <span class="info-label">Staff ID</span>
                                    <span class="info-value"><?php echo htmlspecialchars($user['staffNo']); ?></span>
                                </div>
                            </li>
                            <li class="profile-info-item">
                                <i class="fas fa-briefcase info-icon"></i>
                                <div class="info-content">
                                    <span class="info-label">Position</span>
                                    <span class="info-value"><?php echo htmlspecialchars(ucfirst($user['sPosition'])); ?></span>
                                </div>
                            </li>
                            <li class="profile-info-item">
                                <i class="fas fa-venus-mars info-icon"></i>
                                <div class="info-content">
                                    <span class="info-label">Gender</span>
                                    <span class="info-value"><?php echo htmlspecialchars(ucfirst($user['sex'])); ?></span>
                                </div>
                            </li>
                            <li class="profile-info-item">
                                <i class="fas fa-birthday-cake info-icon"></i>
                                <div class="info-content">
                                    <span class="info-label">Date of Birth</span>
                                    <span class="info-value"><?php echo htmlspecialchars(date('d M Y', strtotime($user['DOB']))); ?></span>
                                </div>
                            </li>
                            <li class="profile-info-item">
                                <i class="fas fa-dollar-sign info-icon"></i>
                                <div class="info-content">
                                    <span class="info-label">Salary</span>
                                    <span class="info-value">$<?php echo number_format($user['salary']); ?></span>
                                </div>
                            </li>
                            <li class="profile-info-item">
                                <i class="fas fa-building info-icon"></i>
                                <div class="info-content">
                                    <span class="info-label">Branch</span>
                                    <span class="info-value"><?php echo htmlspecialchars($user['branchNo']); ?></span>
                                </div>
                            </li>
                        <?php endif; ?>

                        <?php if ($userRole === 'client'): ?>
                            <li class="profile-info-item">
                                <i class="fas fa-phone info-icon"></i>
                                <div class="info-content">
                                    <span class="info-label">Phone</span>
                                    <span class="info-value"><?php echo htmlspecialchars($user['telNo']); ?></span>
                                </div>
                            </li>
                            <li class="profile-info-item">
                                <i class="fas fa-home info-icon"></i>
                                <div class="info-content">
                                    <span class="info-label">Preferred Type</span>
                                    <span class="info-value"><?php echo htmlspecialchars($user['prefType']); ?></span>
                                </div>
                            </li>
                            <li class="profile-info-item">
                                <i class="fas fa-pound-sign info-icon"></i>
                                <div class="info-content">
                                    <span class="info-label">Max Budget</span>
                                    <span class="info-value">$<?php echo number_format($user['maxRent']); ?></span>
                                </div>
                            </li>
                            <?php if (!empty($regInfo)): ?>
                                <li class="profile-info-item">
                                    <i class="fas fa-map-marker-alt info-icon"></i>
                                    <div class="info-content">
                                        <span class="info-label">Branch</span>
                                        <span class="info-value"><?php echo htmlspecialchars($regInfo['branchStreet'] . ', ' . $regInfo['branchCity']); ?></span>
                                    </div>
                                </li>
                                <li class="profile-info-item">
                                    <i class="fas fa-user-tie info-icon"></i>
                                    <div class="info-content">
                                        <span class="info-label">Assigned Staff</span>
                                        <span class="info-value"><?php echo htmlspecialchars($regInfo['staffFName'] . ' ' . $regInfo['staffLName']); ?></span>
                                    </div>
                                </li>
                            <?php endif; ?>

                        <?php elseif ($userRole === 'property_owner'): ?>
                            <li class="profile-info-item">
                                <i class="fas fa-phone info-icon"></i>
                                <div class="info-content">
                                    <span class="info-label">Phone</span>
                                    <span class="info-value"><?php echo htmlspecialchars($user['telNo']); ?></span>
                                </div>
                            </li>
                            <li class="profile-info-item">
                                <i class="fas fa-map-marker-alt info-icon"></i>
                                <div class="info-content">
                                    <span class="info-label">Address</span>
                                    <span class="info-value"><?php echo htmlspecialchars("{$user['street']}, {$user['city']} {$user['postcode']}"); ?></span>
                                </div>
                            </li>

                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Profile Content -->
                <div class="profile-content">
                    <?php if ($userRole === 'staff'): ?>
                        <!-- Staff Dashboard Overview -->
                        <div class="section-card">
                            <div class="section-header">
                                <h3 class="section-title">
                                    <i class="fas fa-chart-bar section-icon"></i>
                                    Dashboard Overview
                                </h3>
                            </div>
                            <div class="section-body">
                                <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                                    <div class="stat-card" style="background: var(--light-bg); padding: 20px; border-radius: var(--border-radius); text-align: center; transition: var(--transition);">
                                        <div class="stat-number" style="font-size: 2rem; font-weight: 700; color: var(--secondary-color); margin-bottom: 10px;">
                                            <?php
                                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM propertyforrent");
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            $count = $result->fetch_assoc();
                                            echo $count['count'];
                                            $stmt->close();
                                            ?>
                                        </div>
                                        <div class="stat-label" style="color: var(--text-secondary); font-weight: 500;">Total Properties</div>
                                    </div>
                                    <div class="stat-card" style="background: var(--light-bg); padding: 20px; border-radius: var(--border-radius); text-align: center; transition: var(--transition);">
                                        <div class="stat-number" style="font-size: 2rem; font-weight: 700; color: var(--success-color); margin-bottom: 10px;">
                                            <?php
                                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM cclient");
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            $count = $result->fetch_assoc();
                                            echo $count['count'];
                                            $stmt->close();
                                            ?>
                                        </div>
                                        <div class="stat-label" style="color: var(--text-secondary); font-weight: 500;">Total Clients</div>
                                    </div>
                                    <div class="stat-card" style="background: var(--light-bg); padding: 20px; border-radius: var(--border-radius); text-align: center; transition: var(--transition);">
                                        <div class="stat-number" style="font-size: 2rem; font-weight: 700; color: var(--warning-color); margin-bottom: 10px;">
                                            <?php
                                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM viewing WHERE viewDate >= CURDATE()");
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            $count = $result->fetch_assoc();
                                            echo $count['count'];
                                            $stmt->close();
                                            ?>
                                        </div>
                                        <div class="stat-label" style="color: var(--text-secondary); font-weight: 500;">Upcoming Viewings</div>
                                    </div>
                                    <div class="stat-card" style="background: var(--light-bg); padding: 20px; border-radius: var(--border-radius); text-align: center; transition: var(--transition);">
                                        <div class="stat-number" style="font-size: 2rem; font-weight: 700; color: var(--accent-color); margin-bottom: 10px;">
                                            <?php
                                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM rent WHERE rentEnd >= CURDATE()");
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            $count = $result->fetch_assoc();
                                            echo $count['count'];
                                            $stmt->close();
                                            ?>
                                        </div>
                                        <div class="stat-label" style="color: var(--text-secondary); font-weight: 500;">Active Rentals</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions for Staff -->
                        <div class="section-card">
                            <div class="section-header">
                                <h3 class="section-title">
                                    <i class="fas fa-bolt section-icon"></i>
                                    Quick Actions
                                </h3>
                            </div>
                            <div class="section-body">
                                <div class="quick-actions" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                                    <a href="staff.php" class="quick-action-btn" style="display: flex; align-items: center; gap: 15px; padding: 20px; background: var(--light-bg); border-radius: var(--border-radius); text-decoration: none; color: var(--text-primary); transition: var(--transition);">
                                        <div class="action-icon" style="width: 50px; height: 50px; background: linear-gradient(135deg, var(--secondary-color), var(--primary-color)); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem;">
                                            <i class="fas fa-tachometer-alt"></i>
                                        </div>
                                        <div class="action-content">
                                            <div class="action-title" style="font-weight: 600; color: var(--primary-color);">Staff Dashboard</div>
                                            <div class="action-desc" style="font-size: 0.9rem; color: var(--text-secondary);">Manage properties and clients</div>
                                        </div>
                                    </a>
                                    <?php if ($_SESSION['sPosition'] === 'admin'): ?>
                                    <a href="xml-admin-report.php" class="quick-action-btn" style="display: flex; align-items: center; gap: 15px; padding: 20px; background: var(--light-bg); border-radius: var(--border-radius); text-decoration: none; color: var(--text-primary); transition: var(--transition);">
                                        <div class="action-icon" style="width: 50px; height: 50px; background: linear-gradient(135deg, var(--warning-color), #e67e22); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem;">
                                            <i class="fas fa-file-code"></i>
                                        </div>
                                        <div class="action-content">
                                            <div class="action-title" style="font-weight: 600; color: var(--primary-color);">XML Reports</div>
                                            <div class="action-desc" style="font-size: 0.9rem; color: var(--text-secondary);">Generate and download reports</div>
                                        </div>
                                    </a>
                                    <?php endif; ?>
                                    <a href="change-password.php" class="quick-action-btn" style="display: flex; align-items: center; gap: 15px; padding: 20px; background: var(--light-bg); border-radius: var(--border-radius); text-decoration: none; color: var(--text-primary); transition: var(--transition);">
                                        <div class="action-icon" style="width: 50px; height: 50px; background: linear-gradient(135deg, var(--accent-color), #c0392b); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem;">
                                            <i class="fas fa-key"></i>
                                        </div>
                                        <div class="action-content">
                                            <div class="action-title" style="font-weight: 600; color: var(--primary-color);">Change Password</div>
                                            <div class="action-desc" style="font-size: 0.9rem; color: var(--text-secondary);">Update your security</div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($userRole === 'client'): ?>
                        <!-- Viewed Properties Section -->
                        <div class="section-card">
                            <div class="section-header">
                                <h3 class="section-title">
                                    <i class="fas fa-eye section-icon"></i>
                                    Viewed Properties
                                    <span class="section-count"><?php echo count($viewedProperties); ?></span>
                                </h3>
                            </div>
                            <div class="section-body">
                                <?php if (!empty($viewedProperties)): ?>
                                    <div class="property-list-enhanced">
                                        <?php foreach ($viewedProperties as $property): ?>
                                            <div class="property-item">
                                                <div class="property-icon">
                                                    <i class="fas fa-eye"></i>
                                                </div>
                                                <div class="property-details">
                                                    <div class="property-address"><?php echo htmlspecialchars("{$property['street']}, {$property['city']}"); ?></div>
                                                    <div class="property-type"><?php echo htmlspecialchars($property['pType']); ?></div>
                                                    <div class="property-meta">
                                                        <div class="meta-item">
                                                            <i class="fas fa-calendar meta-icon"></i>
                                                            Viewed on <?php echo htmlspecialchars(date('d M Y', strtotime($property['viewDate']))); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-search-minus empty-icon"></i>
                                        <div class="empty-title">No Properties Viewed</div>
                                        <div class="empty-message">You haven't viewed any properties yet. <a href="properties.php">Browse properties</a> to get started!</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Rented Properties Section -->
                        <div class="section-card">
                            <div class="section-header">
                                <h3 class="section-title">
                                    <i class="fas fa-key section-icon"></i>
                                    Rented Properties
                                    <span class="section-count"><?php echo count($rentedProperties); ?></span>
                                </h3>
                            </div>
                            <div class="section-body">
                                <?php if (!empty($rentedProperties)): ?>
                                    <div class="property-list-enhanced">
                                        <?php foreach ($rentedProperties as $property): ?>
                                            <div class="property-item">
                                                <div class="property-icon">
                                                    <i class="fas fa-key"></i>
                                                </div>
                                                <div class="property-details">
                                                    <div class="property-address"><?php echo htmlspecialchars("{$property['street']}, {$property['city']}"); ?></div>
                                                    <div class="property-type"><?php echo htmlspecialchars($property['pType']); ?></div>
                                                    <div class="property-meta">
                                                        <div class="meta-item">
                                                            <i class="fas fa-calendar-alt meta-icon"></i>
                                                            <?php echo htmlspecialchars(date('d M Y', strtotime($property['rentStart']))); ?> - <?php echo htmlspecialchars(date('d M Y', strtotime($property['rentEnd']))); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="property-rent">
                                                    $<?php echo number_format($property['rent']); ?>
                                                    <div class="rent-period">per month</div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-home empty-icon"></i>
                                        <div class="empty-title">No Rental History</div>
                                        <div class="empty-message">You haven't rented any properties yet. <a href="properties.php">Explore available properties</a> to find your next home!</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php elseif ($userRole === 'property_owner'): ?>
                        <!-- Owned Properties Section -->
                        <div class="section-card">
                            <div class="section-header">
                                <h3 class="section-title">
                                    <i class="fas fa-building section-icon"></i>
                                    My Properties
                                    <span class="section-count"><?php echo count($ownedProperties); ?></span>
                                </h3>
                            </div>
                            <div class="section-body">
                                <?php if (!empty($ownedProperties)): ?>
                                    <div class="property-list-enhanced">
                                        <?php foreach ($ownedProperties as $property): ?>
                                            <div class="property-item">
                                                <div class="property-icon">
                                                    <i class="fas fa-building"></i>
                                                </div>
                                                <div class="property-details">
                                                    <div class="property-address"><?php echo htmlspecialchars("{$property['street']}, {$property['city']}"); ?></div>
                                                    <div class="property-type"><?php echo htmlspecialchars($property['pType']); ?></div>
                                                    <div class="property-meta">
                                                        <?php if (isset($property['rooms'])): ?>
                                                            <div class="meta-item">
                                                                <i class="fas fa-bed meta-icon"></i>
                                                                <?php echo htmlspecialchars($property['rooms']); ?> rooms
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="property-rent">
                                                    $<?php echo number_format($property['rent']); ?>
                                                    <div class="rent-period">per month</div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-plus-circle empty-icon"></i>
                                        <div class="empty-title">No Properties Listed</div>
                                        <div class="empty-message">You haven't listed any properties yet. <a href="add-property.php">Add your first property</a> to start earning rental income!</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php endif; ?>
                </div>
            </div>

            <!-- Profile Actions -->
            <div class="profile-actions" style="display: flex; justify-content: center; gap: 15px; margin-top: 40px; flex-wrap: wrap;">
                <?php if ($userRole === 'staff'): ?>
                    <form action="index.php" method="post" style="display: inline;">
                        <input type="hidden" name="logout" value="1" />
                        <button type="submit" class="btn-outline" onclick="return confirmLogout()">
                            <i class="fas fa-sign-out-alt"></i>
                            Log Out
                        </button>
                    </form>
                <?php else: ?>
                    <a href="edit-profile.php" class="btn-primary">
                        <i class="fas fa-edit"></i>
                        Edit Profile
                    </a>
                    <a href="change-password.php" class="btn-secondary">
                        <i class="fas fa-key"></i>
                        Change Password
                    </a>
                    <form action="index.php" method="post" style="display: inline;">
                        <input type="hidden" name="logout" value="1" />
                        <button type="submit" class="btn-outline" onclick="return confirmLogout()">
                            <i class="fas fa-sign-out-alt"></i>
                            Log Out
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php
$conn->close();

// Set additional footer scripts
$additionalFooterScripts = '
<style>
.quick-action-btn:hover {
    background: #e8f4fd !important;
    transform: translateY(-2px);
    box-shadow: var(--shadow-medium);
}

.stat-card:hover {
    background: #e8f4fd !important;
    transform: translateY(-2px);
    box-shadow: var(--shadow-medium);
}
</style>';

// Include footer
include 'footer.php';
?>
