<?php
session_start();

if (!isset($_SESSION['user_email']) || $_SESSION['user_role'] !== 'property_owner') {
    header("Location: index.php");
    exit();
}

require_once './db_connection.php';

// Get ownerNo for the logged-in user
$ownerNo = null;
$email = $_SESSION['user_email'];
$stmt = $conn->prepare("SELECT ownerNo FROM privateowner WHERE eMail = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($ownerNo);
$stmt->fetch();
$stmt->close();

if (!$ownerNo) {
    die('Owner not found.');
}

// Get propertyNo from GET
if (!isset($_GET['propertyNo'])) {
    die('No property specified.');
}
$propertyNo = $_GET['propertyNo'];

// Fetch property data
$stmt = $conn->prepare("SELECT street, city, postcode, pType, rooms, rent, branchNo FROM propertyforrent WHERE propertyNo = ? AND ownerNo = ?");
$stmt->bind_param("ss", $propertyNo, $ownerNo);
$stmt->execute();
$stmt->bind_result($street, $city, $postcode, $pType, $rooms, $rent, $branchNo);
if (!$stmt->fetch()) {
    die('Property not found or you do not have permission to edit this property.');
}
$stmt->close();

// Fetch branches for dropdown
$branches = [];
$result = $conn->query("SELECT branchNo, street, city FROM branch");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $branches[] = $row;
    }
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStreet = $_POST['street'];
    $newCity = $_POST['city'];
    $newPostcode = $_POST['postcode'];
    $newPType = $_POST['pType'];
    $newRooms = $_POST['rooms'];
    $newRent = $_POST['rent'];
    $newBranchNo = $_POST['branchNo'];

    $stmt = $conn->prepare("UPDATE propertyforrent SET street=?, city=?, postcode=?, pType=?, rooms=?, rent=?, branchNo=? WHERE propertyNo=? AND ownerNo=?");
    $stmt->bind_param("ssssissss", $newStreet, $newCity, $newPostcode, $newPType, $newRooms, $newRent, $newBranchNo, $propertyNo, $ownerNo);
    if ($stmt->execute()) {
        header("Location: properties.php");
        exit();
    } else {
        $error = 'Error updating property: ' . $stmt->error;
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
    <title>Edit Property</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container" style="margin-top: 100px; max-width: 600px;">
        <h1>Edit Property</h1>
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form action="edit-property.php?propertyNo=<?php echo urlencode($propertyNo); ?>" method="post" class="add-property-form">
            <div class="form-group">
                <label for="street">Street</label>
                <input type="text" id="street" name="street" value="<?php echo htmlspecialchars($street); ?>" required>
            </div>
            <div class="form-group">
                <label for="city">City</label>
                <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($city); ?>" required>
            </div>
            <div class="form-group">
                <label for="postcode">Postcode</label>
                <input type="text" id="postcode" name="postcode" value="<?php echo htmlspecialchars($postcode); ?>" required>
            </div>
            <div class="form-group">
                <label for="pType">Property Type</label>
                <input type="text" id="pType" name="pType" value="<?php echo htmlspecialchars($pType); ?>" required>
            </div>
            <div class="form-group">
                <label for="rooms">Rooms</label>
                <input type="number" id="rooms" name="rooms" value="<?php echo htmlspecialchars($rooms); ?>" required>
            </div>
            <div class="form-group">
                <label for="rent">Price</label>
                <input type="number" id="rent" name="rent" value="<?php echo htmlspecialchars($rent); ?>" required>
            </div>
            <div class="form-group">
                <label for="branchNo">Branch</label>
                <select id="branchNo" name="branchNo" required>
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?php echo htmlspecialchars($branch['branchNo']); ?>" <?php if ($branchNo == $branch['branchNo']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($branch['branchNo'] . ' - ' . $branch['street'] . ', ' . $branch['city']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-add-property">Update Property</button>
        </form>
    </div>
</body>
</html>
