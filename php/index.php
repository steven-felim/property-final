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

        if (!isset($tableMap[$role])) {
            $error = "Invalid role selected.";
        } else {
            if ($role === 'staff') {
                $sql = "SELECT fname, lname, password, sPosition FROM {$tableMap[$role]} WHERE email = ?";
            } else {
                $sql = "SELECT fname, lname, password FROM {$tableMap[$role]} WHERE email = ?";
            }
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($user = $result->fetch_assoc()) {
                    if (password_verify($password, $user['password'])) {
                        $_SESSION['user_email'] = $email;
                        $_SESSION['user_role'] = $role;
                        $_SESSION['user_name'] = $user['fname'] . ' ' . $user['lname'];
                        if ($role === 'staff') {
                            $_SESSION['sPosition'] = $user['sPosition'];
                            header("Location: staff.php");
                        } else {
                            header("Location: homepage.php");
                        }
                        exit();
                    } else {
                        $error = "Email or password is incorrect.";
                    }
                } else {
                    $error = "Email, password, or role is incorrect.";
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
    <title>Sign In - HBProperty</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body class="register-page">
    <div class="register-container">
        <div class="register-brand">
            <h2>Welcome Back</h2>
            <p>Sign in to your HBProperty account</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="auth-form">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="Enter your email address" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter your password">
            </div>
            
            <div class="form-group">
                <label for="role">Login as</label>
                <select id="role" name="role" required>
                    <option value="">Select your role</option>
                    <option value="client" <?php echo (isset($_POST['role']) && $_POST['role'] === 'client') ? 'selected' : ''; ?>>Client - Looking for properties</option>
                    <option value="property_owner" <?php echo (isset($_POST['role']) && $_POST['role'] === 'property_owner') ? 'selected' : ''; ?>>Property Owner - List my properties</option>
                    <option value="staff" <?php echo (isset($_POST['role']) && $_POST['role'] === 'staff') ? 'selected' : ''; ?>>Staff - Administrative access</option>
                </select>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn-login">Sign In</button>
            </div>
            
            <div class="auth-links">
                <p>Don't have an account? <a href="register.php">Create one here</a></p>
                <p><a href="forgot-password.php">Forgot your password?</a></p>
            </div>
        </form>
    </div>
</body>
</html>