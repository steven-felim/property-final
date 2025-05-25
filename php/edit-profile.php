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
    "client" => "CClient",
    "property_owner" => "PrivateOwner",
    "staff" => "Staff"
];

if (!isset($tableMap[$userRole])) {
    die("Invalid role.");
}

$table = $tableMap[$userRole];

// Fetch current user data
switch ($userRole) {
    case 'client':
        $stmt = $conn->prepare("SELECT fname, lname, email, telNo, prefType, maxRent FROM $table WHERE email = ?");
        break;
    case 'property_owner':
        $stmt = $conn->prepare("SELECT fname, lname, email, street, city, postcode, telNo FROM $table WHERE email = ?");
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

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $newEmail = $_POST['email'];

    switch ($userRole) {
        case 'client':
            $telNo = $_POST['telNo'];
            $prefType = $_POST['prefType'];
            $maxRent = $_POST['maxRent'];
            $stmt = $conn->prepare("UPDATE $table SET fname=?, lname=?, email=?, telNo=?, prefType=?, maxRent=? WHERE email=?");
            $stmt->bind_param("sssssis", $fname, $lname, $newEmail, $telNo, $prefType, $maxRent, $userEmail);
            break;

        case 'property_owner':
            $street = $_POST['street'];
            $city = $_POST['city'];
            $postcode = $_POST['postcode'];
            $telNo = $_POST['telNo'];
            $stmt = $conn->prepare("UPDATE $table SET fname=?, lname=?, email=?, street=?, city=?, postcode=?, telNo=? WHERE email=?");
            $stmt->bind_param("ssssssss", $fname, $lname, $newEmail, $street, $city, $postcode, $telNo, $userEmail);
            break;

        case 'staff':
            $sPosition = $_POST['sPosition'];
            $sex = $_POST['sex'];
            $DOB = $_POST['DOB'];
            $stmt = $conn->prepare("UPDATE $table SET fname=?, lname=?, email=?, sPosition=?, sex=?, DOB=? WHERE email=?");
            $stmt->bind_param("sssssss", $fname, $lname, $newEmail, $sPosition, $sex, $DOB, $userEmail);
            break;
    }

    if ($stmt->execute()) {
        $_SESSION['user_email'] = $newEmail;
        header("Location: profile.php");
        exit();
    } else {
        $error = "Error updating profile: " . $stmt->error;
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container" style="margin-top: 100px; max-width: 600px;">
        <h1>Edit Profile</h1>
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form action="edit-profile.php" method="post" class="edit-profile-form">
            <div class="form-group">
                <label for="fname">First Name</label>
                <input type="text" id="fname" name="fname" value="<?php echo htmlspecialchars($user['fname']); ?>" required>
            </div>
            <div class="form-group">
                <label for="lname">Last Name</label>
                <input type="text" id="lname" name="lname" value="<?php echo htmlspecialchars($user['lname']); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <?php if ($userRole === 'client'): ?>
                <div class="form-group">
                    <label for="telNo">Telephone Number</label>
                    <input type="text" id="telNo" name="telNo" value="<?php echo htmlspecialchars($user['telNo']); ?>" maxlength="14">
                </div>
                <div class="form-group">
                    <label for="prefType">Preferred Type</label>
                    <input type="text" id="prefType" name="prefType" value="<?php echo htmlspecialchars($user['prefType']); ?>" maxlength="14">
                </div>
                <div class="form-group">
                    <label for="maxRent">Max Rent</label>
                    <input type="number" id="maxRent" name="maxRent" value="<?php echo htmlspecialchars($user['maxRent']); ?>" min="0">
                </div>

            <?php elseif ($userRole === 'property_owner'): ?>
                <div class="form-group">
                    <label for="street">Street</label>
                    <input type="text" id="street" name="street" value="<?php echo htmlspecialchars($user['street']); ?>" maxlength="25">
                </div>
                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user['city']); ?>" maxlength="20">
                </div>
                <div class="form-group">
                    <label for="postcode">Postcode</label>
                    <input type="text" id="postcode" name="postcode" value="<?php echo htmlspecialchars($user['postcode']); ?>" maxlength="7">
                </div>
                <div class="form-group">
                    <label for="telNo">Telephone Number</label>
                    <input type="text" id="telNo" name="telNo" value="<?php echo htmlspecialchars($user['telNo']); ?>" maxlength="14">
                </div>

            <?php elseif ($userRole === 'staff'): ?>
                <div class="form-group">
                    <label for="sPosition">Staff Position</label>
                    <select id="sPosition" name="sPosition" required>
                        <option value="<?php echo htmlspecialchars($user['sPosition']); ?>" selected>
                            <?php echo htmlspecialchars($user['sPosition']); ?>
                        </option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Sex</label>
                    <input type="radio" name="sex" value="M" <?php echo ($user['sex'] === 'M') ? 'checked' : ''; ?>> <label for="sexM" style="display:inline;">Male</label>
                    <input type="radio" name="sex" value="F" <?php echo ($user['sex'] === 'F') ? 'checked' : ''; ?>> <label for="sexF" style="display:inline;">Female</label>
                </div>
                <div class="form-group">
                    <label for="DOB">Date of Birth</label>
                    <input type="date" id="DOB" name="DOB" value="<?php echo htmlspecialchars($user['DOB']); ?>">
                </div>
                <div class="form-group">
                    <label for="salary">Salary</label>
                    <input type="text" id="salary" value="<?php echo htmlspecialchars($user['salary']); ?>" readonly>
                </div>
            <?php endif; ?>

            <button type="submit">Save Changes</button>
            <button type="button" style="margin-left: 10px;" onclick="window.location.href='profile.php'">Cancel</button>
        </form>
    </div>
</body>
</html>
