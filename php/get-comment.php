<?php
// filepath: c:\xampp\htdocs\SBD\property\php\get-comments.php
require_once './db_connection.php';
$propertyId = $_GET['property_id'] ?? '';
$comments = [];
if ($propertyId) {    $stmt = $conn->prepare(
        "SELECT c.fName, c.lName, v.vComment, v.viewDate
         FROM viewing v
         JOIN cclient c ON v.clientNo = c.clientNo
         WHERE v.propertyNo = ? AND v.vComment IS NOT NULL AND v.vComment != ''
         ORDER BY v.viewDate DESC"
    );
    $stmt->bind_param("s", $propertyId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $comments[] = [
            'user' => $row['fName'] . ' ' . $row['lName'],
            'comment' => $row['vComment'],
            'date' => $row['viewDate']
        ];
    }
    $stmt->close();
}
header('Content-Type: application/json');
echo json_encode($comments);
$conn->close();