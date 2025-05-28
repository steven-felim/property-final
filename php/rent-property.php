<?php
session_start();
require_once './db_connection.php';

if (!isset($_SESSION['user_email']) || $_SESSION['user_role'] !== 'client') {
    header("Location: index.php");
    exit();
}

$clientEmail = $_SESSION['user_email'];
$propertyNo = $_POST['propertyNo']; 
$staffNo = $_POST['staffNo']; 
$dateJoined = date('Y-m-d');

// Ambil clientNo dari email
$stmt = $conn->prepare("SELECT clientNo FROM cclient WHERE eMail = ?");
$stmt->bind_param("s", $clientEmail);
$stmt->execute();
$stmt->bind_result($clientNo);
$stmt->fetch();
$stmt->close();

// Ambil branchNo dari property
$stmt = $conn->prepare("SELECT branchNo FROM propertyforrent WHERE propertyNo = ?");
$stmt->bind_param("s", $propertyNo);
$stmt->execute();
$stmt->bind_result($branchNo);
$stmt->fetch();
$stmt->close();

// Cek apakah client sudah terdaftar di registration
$stmt = $conn->prepare("SELECT clientNo FROM registration WHERE clientNo = ?");
$stmt->bind_param("s", $clientNo);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows == 0) {
    // Insert baru
    $stmt->close();
    $stmt = $conn->prepare("INSERT INTO registration (clientNo, branchNo, staffNo, dateJoined) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $clientNo, $branchNo, $staffNo, $dateJoined);
    $stmt->execute();
    $stmt->close();
} else {
    // Sudah ada, bisa update jika ingin
    $stmt->close();
    // $stmt = $conn->prepare("UPDATE Registration SET branchNo=?, staffNo=? WHERE clientNo=?");
    // $stmt->bind_param("sss", $branchNo, $staffNo, $clientNo);
    // $stmt->execute();
    // $stmt->close();
}

$conn->close();
header("Location: profile.php");
exit();