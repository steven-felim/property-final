<?php
    session_start();

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    $error = "";

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        require_once './db_connection.php';

        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $role = $_POST['role'];

        $tableMap = [
            "client" => "CClient",
            "property_owner" => "PropertyOwner",
            "staff" => "Staff"
        ];

        if (!isset($tableMap[$role])) {
            $error = "Invalid role selected.";
        } else {
            $sql = "SELECT fname, lname, password FROM {$tableMap[$role]} WHERE email = ?";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($user = $result->fetch_assoc()) {
                    if (password_verify($password, $user['password'])) {
                        $_SESSION['user_email'] = $email;
                        $_SESSION['user_role'] = $role;
s                        header("Location: homepage.php");
                        exit();
                    } else {
                        $error = "Incorrect password.";
                    }
                } else {
                    $error = "No account found with that email.";
                }
                $stmt->close();
            } else {
                $error = "Database query failed.";
            }
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
