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

// Fetch current user data
switch ($userRole) {
    case 'client':
        $stmt = $conn->prepare("SELECT fname, lname, eMail, telNo, prefType, maxRent FROM $table WHERE eMail = ?");
        break;
    case 'property_owner':
        $stmt = $conn->prepare("SELECT fname, lname, eMail, street, city, postcode, telNo FROM $table WHERE eMail = ?");
        break;
    case 'staff':
        $stmt = $conn->prepare("SELECT fname, lname, email, sPosition, sex, DOB, salary FROM $table WHERE email = ?");
        break;
    default:
        die("Invalid role.");
}

$stmt->bind_param("s", $userEmail);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User not found.");
}

$success = '';
$error = '';

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $newEmail = trim($_POST['email']);

    // Validate input
    if (empty($fname) || empty($lname) || empty($newEmail)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        switch ($userRole) {
            case 'client':
                $telNo = trim($_POST['telNo']);
                $prefType = trim($_POST['prefType']);
                $maxRent = (int)$_POST['maxRent'];
                $stmt = $conn->prepare("UPDATE $table SET fname=?, lname=?, eMail=?, telNo=?, prefType=?, maxRent=? WHERE eMail=?");
                $stmt->bind_param("sssssis", $fname, $lname, $newEmail, $telNo, $prefType, $maxRent, $userEmail);
                break;

            case 'property_owner':
                $street = trim($_POST['street']);
                $city = trim($_POST['city']);
                $postcode = trim($_POST['postcode']);
                $telNo = trim($_POST['telNo']);
                $stmt = $conn->prepare("UPDATE $table SET fname=?, lname=?, eMail=?, street=?, city=?, postcode=?, telNo=? WHERE eMail=?");
                $stmt->bind_param("ssssssss", $fname, $lname, $newEmail, $street, $city, $postcode, $telNo, $userEmail);
                break;

            case 'staff':
                $sPosition = trim($_POST['sPosition']);
                $sex = $_POST['sex'];
                $DOB = $_POST['DOB'];
                $stmt = $conn->prepare("UPDATE $table SET fname=?, lname=?, email=?, sPosition=?, sex=?, DOB=? WHERE email=?");
                $stmt->bind_param("sssssss", $fname, $lname, $newEmail, $sPosition, $sex, $DOB, $userEmail);
                break;
        }

        if ($stmt->execute()) {
            $_SESSION['user_email'] = $newEmail;
            $success = "Profile updated successfully!";
            // Refresh user data
            $user['fname'] = $fname;
            $user['lname'] = $lname;
            if ($userRole === 'staff') {
                $user['email'] = $newEmail;
            } else {
                $user['eMail'] = $newEmail;
            }
        } else {
            $error = "Error updating profile: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Set page variables for header
$pageTitle = "Edit Profile - HBProperty";
$pageDescription = "Update your profile information";
$showSearchForm = false;

// Include header
include 'header.php';
?>

<main class="edit-profile-page">
    <div class="container">
        <div class="page-header">
            <h1>Edit Profile</h1>
            <p>Update your personal information</p>
        </div>

        <div class="profile-edit-container">
            <div class="profile-edit-card">
                <?php if (!empty($error)): ?>
                    <div class="notification error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="notification success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form action="edit-profile.php" method="post" class="edit-profile-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fname">First Name *</label>
                            <input type="text" id="fname" name="fname" value="<?php echo htmlspecialchars($user['fname']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="lname">Last Name *</label>
                            <input type="text" id="lname" name="lname" value="<?php echo htmlspecialchars($user['lname']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userRole === 'staff' ? $user['email'] : $user['eMail']); ?>" required>
                    </div>

                    <?php if ($userRole === 'client'): ?>
                        <div class="form-group">
                            <label for="telNo">Phone Number</label>
                            <input type="tel" id="telNo" name="telNo" value="<?php echo htmlspecialchars($user['telNo'] ?? ''); ?>" maxlength="14">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="prefType">Preferred Property Type</label>
                                <select id="prefType" name="prefType">
                                    <option value="">Select Type</option>
                                    <option value="House" <?php echo (isset($pType) && $pType === 'House') ? 'selected' : ''; ?>>House</option>
                                    <option value="Apartment" <?php echo (isset($pType) && $pType === 'Apartment') ? 'selected' : ''; ?>>Apartment</option>
                                    <option value="Condo" <?php echo (isset($pType) && $pType === 'Condo') ? 'selected' : ''; ?>>Condo</option>
                                    <option value="Studio" <?php echo (isset($pType) && $pType === 'Studio') ? 'selected' : ''; ?>>Studio</option>
                                    <option value="Townhouse" <?php echo (isset($pType) && $pType === 'Townhouse') ? 'selected' : ''; ?>>Townhouse</option>
                                    <option value="Villa" <?php echo (isset($pType) && $pType === 'Villa') ? 'selected' : ''; ?>>Villa</option>
                                    <option value="Kos" <?php echo (isset($pType) && $pType === 'Kos') ? 'selected' : ''; ?>>Kos</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="maxRent">Maximum Budget ($)</label>
                                <input type="number" id="maxRent" name="maxRent" value="<?php echo htmlspecialchars($user['maxRent'] ?? ''); ?>" min="0" step="50">
                            </div>
                        </div>

                    <?php elseif ($userRole === 'property_owner'): ?>
                        <div class="form-group">
                            <label for="street">Street Address</label>
                            <input type="text" id="street" name="street" value="<?php echo htmlspecialchars($user['street'] ?? ''); ?>" maxlength="25">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">City</label>
                                <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" maxlength="20">
                            </div>
                            <div class="form-group">
                                <label for="postcode">Postcode</label>
                                <input type="text" id="postcode" name="postcode" value="<?php echo htmlspecialchars($user['postcode'] ?? ''); ?>" maxlength="7">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="telNo">Phone Number</label>
                            <input type="tel" id="telNo" name="telNo" value="<?php echo htmlspecialchars($user['telNo'] ?? ''); ?>" maxlength="14">
                        </div>

                    <?php elseif ($userRole === 'staff'): ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="sPosition">Position</label>
                                <select id="sPosition" name="sPosition" required>
                                    <option value="Manager" <?php echo ($user['sPosition'] === 'Manager') ? 'selected' : ''; ?>>Manager</option>
                                    <option value="Assistant" <?php echo ($user['sPosition'] === 'Assistant') ? 'selected' : ''; ?>>Assistant</option>
                                    <option value="Supervisor" <?php echo ($user['sPosition'] === 'Supervisor') ? 'selected' : ''; ?>>Supervisor</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Gender</label>
                                <div class="radio-group">
                                    <label class="radio-label">
                                        <input type="radio" name="sex" value="M" <?php echo ($user['sex'] === 'M') ? 'checked' : ''; ?>>
                                        <span>Male</span>
                                    </label>
                                    <label class="radio-label">
                                        <input type="radio" name="sex" value="F" <?php echo ($user['sex'] === 'F') ? 'checked' : ''; ?>>
                                        <span>Female</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="DOB">Date of Birth</label>
                            <input type="date" id="DOB" name="DOB" value="<?php echo htmlspecialchars($user['DOB'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="salary">Salary</label>
                            <input type="text" id="salary" value="$<?php echo number_format($user['salary'] ?? 0); ?>" readonly>
                            <small>Salary cannot be modified</small>
                        </div>
                    <?php endif; ?>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i>
                            Save Changes
                        </button>
                        <a href="profile.php" class="btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php
$conn->close();

// Set additional footer scripts
$additionalFooterScripts = '
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<script>
// Auto-hide notifications
setTimeout(() => {
    const notifications = document.querySelectorAll(".notification");
    notifications.forEach(notification => {
        notification.style.opacity = "0";
        setTimeout(() => notification.remove(), 300);
    });
}, 5000);
</script>
';

// Include footer
include 'footer.php';
?>
