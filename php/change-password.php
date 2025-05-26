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

// Fetch current hashed password
$stmt = $conn->prepare("SELECT password FROM $table WHERE email = ?");
$stmt->bind_param("s", $userEmail);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Verify current password
    if (!password_verify($currentPassword, $user['password'])) {
        $error = "Current password is incorrect.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "New passwords do not match.";
    } else {
        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE $table SET password=? WHERE email=?");
        $stmt->bind_param("ss", $hashedPassword, $userEmail);
        if ($stmt->execute()) {
            $success = "Password changed successfully.";
        } else {
            $error = "Error updating password: " . $stmt->error;
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container" style="margin-top: 100px; max-width: 600px;">
        <h1>Change Password</h1>

        <div id="notification" class="notification <?php echo isset($error) ? 'error' : (isset($success) ? 'success' : ''); ?>" style="display: <?php echo (isset($error) || isset($success)) ? 'block' : 'none'; ?>">
            <?php echo htmlspecialchars($error ?? $success ?? ''); ?>
        </div>


        <form action="change-password.php" method="post" class="change-password-form">
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit">Change Password</button>
            <button type="button" style="margin-left: 10px;" onclick="window.location.href='profile.php'">Cancel</button>
        </form>
    </div>
</body>
</html>
