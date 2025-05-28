<?php
session_start();
require_once './db_connection.php';

// Admin only access
if (!isset($_SESSION['user_email']) || ($_SESSION['sPosition'] ?? '') !== 'admin') {
    header("Location: index.php");
    exit();
}

$staffNo = $_GET['staffNo'] ?? '';
if (!$staffNo) {
    echo "Staff not found.";
    exit();
}

// Fetch data staff
$stmt = $conn->prepare("SELECT staffNo, fName, lName, eMail, sPosition, salary, sex, DOB FROM staff WHERE staffNo = ?");
$stmt->bind_param("s", $staffNo);
$stmt->execute();
$result = $stmt->get_result();
$staff = $result->fetch_assoc();
$stmt->close();

if (!$staff) {
    echo "Staff not found.";
    exit();
}

$successMsg = '';
$errorMsg = '';

// Proses update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_staff'])) {
    $fName = $_POST['fName'];
    $lName = $_POST['lName'];
    $eMail = $_POST['eMail'];
    $sPosition = $_POST['sPosition'];
    $salary = $_POST['salary'];
    $sex = $_POST['sex'];
    $DOB = $_POST['DOB'];

    // Jika password diisi, update password juga
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE staff SET fName=?, lName=?, eMail=?, sPosition=?, salary=?, sex=?, DOB=?, password=? WHERE staffNo=?");
        $stmt->bind_param("ssssdssss", $fName, $lName, $eMail, $sPosition, $salary, $sex, $DOB, $password, $staffNo);
    } else {
        $stmt = $conn->prepare("UPDATE staff SET fName=?, lName=?, eMail=?, sPosition=?, salary=?, sex=?, DOB=? WHERE staffNo=?");
        $stmt->bind_param("ssssdsss", $fName, $lName, $eMail, $sPosition, $salary, $sex, $DOB, $staffNo);
    }

    if ($stmt->execute()) {
        // Show alert and redirect to staff dashboard
        echo "<script>
            alert('Staff profile updated successfully.');
            window.location.href = 'staff.php';
        </script>";
        exit();
    } else {
        $errorMsg = "Failed to update staff profile.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Staff Profile</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container" style="margin-top: 100px; max-width: 600px;">
        <h1>Edit Profile</h1>
        <?php if ($successMsg): ?>
            <div class="success-message"><?php echo htmlspecialchars($successMsg); ?></div>
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            <div class="error-message"><?php echo htmlspecialchars($errorMsg); ?></div>
        <?php endif; ?>
        <form method="post" class="edit-profile-form">
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="fName" value="<?php echo htmlspecialchars($staff['fName']); ?>" required>
            </div>
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="lName" value="<?php echo htmlspecialchars($staff['lName']); ?>">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="eMail" value="<?php echo htmlspecialchars($staff['eMail']); ?>" required>
            </div>
            <div class="form-group">
                <label>Position</label>
                <select name="sPosition" required>
                    <option value="staff" <?php if($staff['sPosition']=='staff') echo 'selected'; ?>>Staff</option>
                    <option value="admin" <?php if($staff['sPosition']=='admin') echo 'selected'; ?>>Admin</option>
                </select>
            </div>
            <div class="form-group">
                <label>Salary</label>
                <input type="number" name="salary" min="0" value="<?php echo htmlspecialchars($staff['salary']); ?>" required>
            </div>
            <div class="form-group">
                <label>Sex</label>
                <div class="sex-radio-group">
                    <label>
                        <input type="radio" name="sex" value="F" <?php if($staff['sex']=='F') echo 'checked'; ?>> Female
                    </label>
                    <label>
                        <input type="radio" name="sex" value="M" <?php if($staff['sex']=='M') echo 'checked'; ?>> Male
                    </label>
                </div>
            </div>
            <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" name="DOB" value="<?php echo htmlspecialchars($staff['DOB']); ?>" required>
            </div>
            <div class="form-group">
                <label>New Password (leave blank if not changing)</label>
                <input type="password" name="password">
            </div>
            <button type="submit" name="update_staff">Update</button>
            <button type="button" style="margin-left: 10px;" onclick="window.location.href='staff.php'">Cancel</button>
        </form>
    </div>
</body>
</html>