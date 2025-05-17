<?php
require 'db_connection.php'; // use your centralized DB connection

$propertyId = $_GET['property_id'] ?? '';

if (!$propertyId) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT image FROM PropertyImage WHERE propertyNo = ?");
$stmt->bind_param("s", $propertyId);
$stmt->execute();
$result = $stmt->get_result();

$images = [];
while ($row = $result->fetch_assoc()) {
    $images[] = $row['image'];
}

echo json_encode($images);
$stmt->close();
$conn->close();
?>