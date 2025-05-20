<?php
session_start();

if (!isset($_SESSION['user_email']) || $_SESSION['user_role'] !== 'property_owner') {
    header("Location: index.php");
    exit();
}

require_once './db_connection.php';

// Get ownerNo from PrivateOwner table using session email
$ownerNo = null;
$email = $_SESSION['user_email'];
$stmt = $conn->prepare("SELECT ownerNo FROM PrivateOwner WHERE eMail = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($ownerNo);
$stmt->fetch();
$stmt->close();

if (!$ownerNo) {
    die('Owner not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $price = $_POST['price'];
    $city = $_POST['city'];
    $street = $_POST['street'];
    $postcode = $_POST['postcode'];
    $rooms = $_POST['rooms'];
    $pType = $_POST['pType'];
    $branchNo = $_POST['branchNo'];

    // Handle image upload
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imgName = uniqid('property_') . '_' . basename($_FILES['image']['name']);
        $targetDir = '../img/';
        $targetFile = $targetDir . $imgName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $imagePath = $imgName;
        } else {
            die('Image upload failed.');
        }
    }

    // Insert property into PropertyForRent
    $stmt = $conn->prepare("INSERT INTO PropertyForRent (street, city, postcode, pType, rooms, rent, ownerNo, branchNo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssdiss", $street, $city, $postcode, $pType, $rooms, $price, $ownerNo, $branchNo);
    if ($stmt->execute()) {
        // Fetch the last inserted propertyNo for this owner
        $stmtProp = $conn->prepare("SELECT propertyNo FROM PropertyForRent WHERE ownerNo = ? ORDER BY propertyNo DESC LIMIT 1");
        $stmtProp->bind_param("s", $ownerNo);
        $stmtProp->execute();
        $stmtProp->bind_result($propertyNo);
        $stmtProp->fetch();
        $stmtProp->close();
        // Insert image into PropertyImage if uploaded
        if ($imagePath) {
            $stmtImg = $conn->prepare("INSERT INTO PropertyImage (propertyNo, image) VALUES (?, ?)");
            $stmtImg->bind_param("ss", $propertyNo, $imagePath);
            $stmtImg->execute();
            $stmtImg->close();
        }
        // Redirect to viewing.php instead of view.php
        header("Location: viewing.php");
        exit();
    } else {
        die('Error adding property: ' . $stmt->error);
    }
}
$conn->close();

// Show the add property form if not POST
// Fetch branches for dropdown
$branches = [];
require './db_connection.php';
$result = $conn->query("SELECT branchNo, street, city FROM Branch");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $branches[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Property</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container" style="margin-top: 100px; max-width: 600px;">
        <h1>Add Property</h1>
        <form action="add-property.php" method="post" enctype="multipart/form-data" class="add-property-form">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" required>
            </div>
            <div class="form-group">
                <label for="price">Price</label>
                <input type="number" id="price" name="price" required>
            </div>
            <div class="form-group">
                <label for="city">City</label>
                <input type="text" id="city" name="city" required>
            </div>
            <div class="form-group">
                <label for="street">Street</label>
                <input type="text" id="street" name="street" required>
            </div>
            <div class="form-group">
                <label for="postcode">Postcode</label>
                <input type="text" id="postcode" name="postcode" required>
            </div>
            <div class="form-group">
                <label for="rooms">Rooms</label>
                <input type="number" id="rooms" name="rooms" required>
            </div>
            <div class="form-group">
                <label for="pType">Property Type</label>
                <input type="text" id="pType" name="pType" required>
            </div>
            <div class="form-group">
                <label for="branchNo">Branch</label>
                <select id="branchNo" name="branchNo" required>
                    <option value="">Select Branch</option>
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?php echo htmlspecialchars($branch['branchNo']); ?>">
                            <?php echo htmlspecialchars($branch['branchNo'] . ' - ' . $branch['street'] . ', ' . $branch['city']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="image">Image</label>
                <input type="file" id="image" name="image" accept="image/*">
            </div>
            <button type="submit" class="btn-add-property">Add Property</button>
        </form>
    </div>
</body>
</html>
