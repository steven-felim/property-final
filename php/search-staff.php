<?php
require_once './db_connection.php';
$q = $_GET['q'] ?? '';
$data = [];
if ($q !== '') {
    $stmt = $conn->prepare("SELECT staffNo, fName, lName FROM staff WHERE sPosition != 'admin' AND (staffNo LIKE ? OR fName LIKE ? OR lName LIKE ?)");
    $like = "%$q%";
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'staffNo' => $row['staffNo'],
            'name' => $row['staffNo'] . ' - ' . $row['fName'] . ' ' . $row['lName']
        ];
    }
    $stmt->close();
}
header('Content-Type: application/json');
echo json_encode($data);
?>