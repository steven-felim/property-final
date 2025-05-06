<?php
// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the form inputs
    $firstName = $_POST['firstname'];
    $lastName = $_POST['lastname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Database connection
    $servername = "localhost";
    $dbUsername = "root";
    $dbPassword = "";
    $dbName = "property";

    $conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Insert data into the database
    if ($role === "client") {
        $sql = "INSERT INTO CCient (fname, lname, email, password) VALUES (?, ?, ?, ?)";
    } elseif ($role === "property_owner") {
        $sql = "INSERT INTO PropertyOwner (fname, lname, email, password) VALUES (?, ?, ?, ?)";
    } else {
        die("Invalid role specified.");
    }
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $firstname, $lastName, $email, $hashedPassword);

    if ($stmt->execute()) {
        echo "Registration successful!";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close the connection
    $stmt->close();
    $conn->close();
}
?>