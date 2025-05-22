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

// Fetch current user data
$stmt = $conn->prepare("SELECT fname, lname, email FROM {$tableMap[$userRole]} WHERE email = ?");
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
    $stmt = $conn->prepare("UPDATE {$tableMap[$userRole]} SET fname=?, lname=?, email=? WHERE email=?");
    $stmt->bind_param("ssss", $fname, $lname, $newEmail, $userEmail);
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
            <button type="submit" class="btn-add-property">Save Changes</button>
            <a href="profile.php" class="btn-cancel" style="margin-left: 10px;">Cancel</a>
        </form>
    </div>
</body>
</html>
