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
    <title>About Us - Property Renting Website</title>
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
    </header>

    <section class="about">
        <div class="container">
            <h1>About Us</h1>
            <p>We connect people with their perfect homes through a seamless and secure renting experience.</p>
            <p>Founded in 2025, our mission is to simplify the property rental process for both renters and property owners.</p>
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
