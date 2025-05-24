<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_once './db_connection.php';

    // Force charset and collation (PREVENTS COLLATION ERRORS)
    $conn->set_charset("utf8mb4");
    $conn->query("SET collation_connection = 'utf8mb4_general_ci'");

    // Sanitize input
    $firstName = trim($_POST['fname']);
    $lastName = trim($_POST['lname']);
    $email = trim($_POST['email']);
    $telNo = trim($_POST['telNo']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $email = mb_convert_encoding($email, 'UTF-8', 'UTF-8'); // Normalize encoding

    // Check if email exists in any table
    $checkEmailQuery = "SELECT eMail FROM CClient WHERE eMail = ? 
                    UNION 
                    SELECT eMail FROM PrivateOwner WHERE eMail = ? 
                    UNION 
                    SELECT email FROM Staff WHERE email = ?";

    $stmt = $conn->prepare($checkEmailQuery);
    $stmt->bind_param("sss", $email, $email, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $error = "Email already exists.";
    } else {
        // Role to table mapping
        $tableMap = [
            "client" => ["table" => "CClient", "id_column" => "clientNo"],
            "property_owner" => ["table" => "PrivateOwner", "id_column" => "ownerNo"],
            "staff" => ["table" => "Staff", "id_column" => "staffNo"]
        ];

        if (!isset($tableMap[$role])) {
            $error = "Invalid role selected.";
        } else {
            $tableName = $tableMap[$role]['table'];
            $idColumn = $tableMap[$role]['id_column'];

            // Function to generate custom ID
            function generateRoleId($conn, $role, $tableName, $idColumn) {
                $prefix = $role === 'client' ? 'CR' : ($role === 'property_owner' ? 'CO' : 'A');

                $sql = "SELECT MAX($idColumn) AS max_id FROM $tableName WHERE $idColumn LIKE ?";
                $likePrefix = $prefix . '%';
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $likePrefix);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();

                if ($row && $row['max_id']) {
                    $maxId = $row['max_id'];

                    if ($role === 'staff') {
                        $alpha = substr($maxId, 1, 1);
                        $num = (int)substr($maxId, 2);
                        $num++;
                        if ($num > 99) {
                            $alpha = chr(ord($alpha) + 1);
                            $num = 1;
                        }
                        return 'A' . $alpha . str_pad($num, 2, '0', STR_PAD_LEFT);
                    } else {
                        $num = (int)substr($maxId, 2);
                        $num++;
                        return $prefix . str_pad($num, 2, '0', STR_PAD_LEFT);
                    }
                } else {
                    return $role === 'staff' ? 'AA01' : $prefix . '01';
                }
            }

            $newId = generateRoleId($conn, $role, $tableName, $idColumn);

            if ($role === 'client') {
                $sql = "INSERT INTO {$tableName} ($idColumn, fname, lname, email, password, telNo) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssss", $newId, $firstName, $lastName, $email, $hashedPassword, $telNo);
            } else if ($role === 'property_owner') {
                $sql = "INSERT INTO {$tableName} ($idColumn, fname, lname, email, password, telNo) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssss", $newId, $firstName, $lastName, $email, $hashedPassword, $telNo);
            } else {
                // staff (jika ada)
                $sql = "INSERT INTO {$tableName} ($idColumn, fname, lname, email, password) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssss", $newId, $firstName, $lastName, $email, $hashedPassword);
            }

            if ($stmt->execute()) {
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = $role;
                header("Location: homepage.php");
                exit();
            } else {
                $error = "Registration error: " . $stmt->error;
            }

        }
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
                    <label for="telNo">Phone Number</label>
                    <input type="text" id="telNo" name="telNo" required>
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
