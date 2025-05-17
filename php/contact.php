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
    <title>Contact Us - Property Renting Website</title>
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


    <section class="contact">
        <div class="container">
            <h1>Contact Us</h1>
            <form action="../php/send-message.php" method="POST">
                <label for="name">Name:</label>
                <input type="text" name="name" required>

                <label for="email">Email:</label>
                <input type="email" name="email" required>

                <label for="message">Message:</label>
                <textarea name="message" rows="5" required></textarea>

                <button type="submit">Send Message</button>
            </form>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>&copy; 2025 HBProperty | All Rights Reserved</p>
            
        </div>
    </footer>
</body>
</html>
