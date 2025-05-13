<?php
    session_start();

    if (!isset($_SESSION['user_email'])) {
        header("Location: index.php");
        exit();
    }

    $userEmail = $_SESSION['user_email'];
    $userRole = $_SESSION['user_role'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Profile - Property Renting Website</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <img src="../img/logo.png" alt="Logo" class="logo-img">
            </div>
            <nav>
                <ul>
                    <li><a href="homepage.php">Home</a></li>
                    <li><a href="properties.php">Properties</a></li>
                    <li><a href="viewing.php">Viewing</li>
                    <li><a href="profile.php">Profile</a></li>
                </ul>
            </nav>
        </div>
    </header>    </header>


    <section class="profile">
        <div class="container">
            <h1>Your Profile</h1>
            <p><strong>Name:</strong> <?php
                $servername = "localhost";
                $username = "root";
                $password = "";
                $dbname = "property"; // Replace with your database name

                // Create connection
                $conn = new mysqli($servername, $username, $password, $dbname);

                // Check connection
                if ($conn->connect_error) {
                    die("Connection failed: " . $conn->connect_error);
                }

                if ($userRole === "client") {
                    $userQuery = "SELECT fname, lname FROM CClient WHERE email = ?";
                } elseif ($userRole === "property_owner") {
                    $userQuery = "SELECT fname, lname FROM PropertyOwner WHERE email = ?";
                } elseif ($userRole === "staff") {
                    $userQuery = "SELECT fname, lname FROM Staff WHERE email = ?";
                } else {
                    die("Invalid role selected.");
                }

                $stmt = $conn->prepare($userQuery);
                $stmt->bind_param("s", $userEmail);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();

                if ($user) {
                    echo htmlspecialchars($user['fname']) . " " . htmlspecialchars($user['lname']);
                } else {
                    echo "User not found.";
                }

                $stmt->close();
                $conn->close();
                ?>
            </p>
            <p><strong>Email:</strong> <?php echo $userEmail; ?></p>
            <p><strong>Rented Properties:</strong></p>
            <ul>
                <li>Luxury Villa - $2,500/month</li>
                <li>Modern Apartment - $1,800/month</li>
            </ul>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>&copy; 2025 Your Website | All Rights Reserved</p>
            <div class="social-links">
                <a href="#">Facebook</a>
                <a href="#">Instagram</a>
                <a href="#">Twitter</a>
            </div>
        </div>
    </footer>
</body>
</html>
