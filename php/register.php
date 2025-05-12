<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form inputs
    $firstName = $_POST['fname'];
    $lastName = $_POST['lname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Connect to the database
    $servername = "localhost";
    $dbUsername = "root";
    $dbPassword = "";
    $dbName = "property";

    $conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Choose the table
    if ($role === "client") {
        $sql = "INSERT INTO CClient (fname, lname, email, password) VALUES (?, ?, ?, ?)";
    } elseif ($role === "property_owner") {
        $sql = "INSERT INTO PropertyOwner (fname, lname, email, password) VALUES (?, ?, ?, ?)";
    } else {
        die("Invalid role specified.");
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $firstName, $lastName, $email, $hashedPassword);

    if ($stmt->execute()) {
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = $role;
        $$_SESSION['user_name'] = trim($firstName . " " . $lastName);
        header("Location: homepage.php");
        exit();
    } else {
        $error = "Registration error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Register</title>
        <link rel="stylesheet" href="../css/styles.css">
    </head>
    <body class="register-page">
        <div class="register-container">
            <h2>Register</h2>
            <?php if (!empty($error)): ?>
                <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <form action="" method="POST">
                <div class="form-group">
                    <label for="fname">First Name</label>
                    <input type="text" id="fname" name="fname" required>
                </div>
                <div class="form-group">
                    <label for="lname">Last Name</label>
                    <input type="text" id="lname" name="lname" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="role">Sign up as</label>
                    <select id="role" name="role" required>
                        <option value="client">Client</option>
                        <option value="property_owner">Property Owner</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit">Register</button>
                </div>
                <div class="form-group">
                    <p>Already have an account? <a href="index.php">Sign in here!</a></p>
                </div>
            </form>
        </div>
    </body>
</html>
