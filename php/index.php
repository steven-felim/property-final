<?php
    session_start();

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    $error = "";

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST['logout'])) {
            session_unset();
            session_destroy();
            header("Location: index.php");
            exit();
        }

        require_once './db_connection.php';

        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $role = $_POST['role'];

        $tableMap = [
            "client" => "CClient",
            "property_owner" => "PrivateOwner",
            "staff" => "Staff"
        ];

        $conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Determine table by role
        if ($role === "client") {
            $sql = "SELECT * FROM CClient WHERE email = ?";
        } elseif ($role === "property_owner") {
            $sql = "SELECT * FROM PropertyOwner WHERE email = ?";
        } elseif ($role === "staff") {
            $sql = "SELECT * FROM Staff WHERE email = ?";
        } else {
            $error = "Invalid role selected.";
        }

        if (empty($error)) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($user = $result->fetch_assoc()) {
                    if (password_verify($password, $user['password'])) {
                        $_SESSION['user_email'] = $email;
                        $_SESSION['user_role'] = $role;
                        header("Location: homepage.php");
                        exit();
                    } else {
                        $error = "Incorrect password.";
                    }
                } else {
                    $error = "Incorrect password.";
                }
            } else {
                $error = "No account found with that email.";
            }

            $stmt->close();
        }

        $conn->close();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body class="register-page">
    <div class="register-container">
        <h2>Log In</h2>
        <?php if (!empty($error)): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form action="" method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="role">Login as</label>
                <select id="role" name="role" required>
                    <option value="client">Client</option>
                    <option value="property_owner">Property Owner</option>
                    <option value="staff">Staff</option>
                </select>
            </div>
            <div class="form-group">
                <button type="submit">Log In</button>
            </div>
            <div class="form-group">
                <p>Don't have an account? <a href="register.php">Sign up here!</a></p>
            </div>
        </form>
    </div>
</body>
</html>
