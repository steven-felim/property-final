<?php
    session_start();

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        require_once './db_connection.php';

        // Get form inputs
        $firstName = $_POST['fname'];
        $lastName = $_POST['lname'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $role = $_POST['role'];

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Check if email already exists
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
            $tableMap = [
                "client" => ["table" => "CClient", "id_column" => "clientNo", "email_column" => "eMail"],
                "property_owner" => ["table" => "PrivateOwner", "id_column" => "ownerNo", "email_column" => "eMail"],
                "staff" => ["table" => "Staff", "id_column" => "staffNo", "email_column" => "email"]
            ];

            function generateRoleId($conn, $role, $tableName, $idColumn) {
                switch ($role) {
                    case 'client':
                        $prefix = 'CR';
                        break;
                    case 'property_owner':
                        $prefix = 'CO';
                        break;
                    case 'staff':
                        $prefix = 'A';
                        break;
                    default:
                        return null;
                }

                // Get the highest current ID
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
                        // Example: AZ07 → AZ08
                        $alpha = substr($maxId, 1, 1);
                        $num = (int)substr($maxId, 2);
                        $num++;
                        if ($num > 99) {
                            $alpha = chr(ord($alpha) + 1);
                            $num = 1;
                        }
                        return $prefix . $alpha . str_pad($num, 2, '0', STR_PAD_LEFT);
                    } else {
                        // Example: CR09 → CR10
                        $num = (int)substr($maxId, 2);
                        $num++;
                        return $prefix . str_pad($num, 2, '0', STR_PAD_LEFT);
                    }
                } else {
                    // First ID
                    if ($role === 'staff') {
                        return $prefix . 'A01';
                    } else {
                        return $prefix . '01';
                    }
                }
            }

            function generateRoleId($conn, $role, $tableName, $idColumn) {
                switch ($role) {
                    case 'client':
                        $prefix = 'CR';
                        break;
                    case 'property_owner':
                        $prefix = 'CO';
                        break;
                    case 'staff':
                        $prefix = 'A';
                        break;
                    default:
                        return null;
                }

                // Get the highest current ID
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
                        // Example: AZ07 → AZ08
                        $alpha = substr($maxId, 1, 1);
                        $num = (int)substr($maxId, 2);
                        $num++;
                        if ($num > 99) {
                            $alpha = chr(ord($alpha) + 1);
                            $num = 1;
                        }
                        return $prefix . $alpha . str_pad($num, 2, '0', STR_PAD_LEFT);
                    } else {
                        // Example: CR09 → CR10
                        $num = (int)substr($maxId, 2);
                        $num++;
                        return $prefix . str_pad($num, 2, '0', STR_PAD_LEFT);
                    }
                } else {
                    // First ID
                    if ($role === 'staff') {
                        return $prefix . 'A01';
                    } else {
                        return $prefix . '01';
                    }
                }
            }

            function generateRoleId($conn, $role, $tableName, $idColumn) {
                switch ($role) {
                    case 'client':
                        $prefix = 'CR';
                        break;
                    case 'property_owner':
                        $prefix = 'CO';
                        break;
                    case 'staff':
                        $prefix = 'A';
                        break;
                    default:
                        return null;
                }

                // Get the highest current ID
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
                        // Example: AZ07 → AZ08
                        $alpha = substr($maxId, 1, 1);
                        $num = (int)substr($maxId, 2);
                        $num++;
                        if ($num > 99) {
                            $alpha = chr(ord($alpha) + 1);
                            $num = 1;
                        }
                        return $prefix . $alpha . str_pad($num, 2, '0', STR_PAD_LEFT);
                    } else {
                        // Example: CR09 → CR10
                        $num = (int)substr($maxId, 2);
                        $num++;
                        return $prefix . str_pad($num, 2, '0', STR_PAD_LEFT);
                    }
                } else {
                    // First ID
                    if ($role === 'staff') {
                        return $prefix . 'A01';
                    } else {
                        return $prefix . '01';
                    }
                }
            }

            function generateRoleId($conn, $role, $tableName, $idColumn) {
                switch ($role) {
                    case 'client':
                        $prefix = 'CR';
                        break;
                    case 'property_owner':
                        $prefix = 'CO';
                        break;
                    case 'staff':
                        $prefix = 'A';
                        break;
                    default:
                        return null;
                }

                // Get the highest current ID
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
                        // Example: AZ07 → AZ08
                        $alpha = substr($maxId, 1, 1);
                        $num = (int)substr($maxId, 2);
                        $num++;
                        if ($num > 99) {
                            $alpha = chr(ord($alpha) + 1);
                            $num = 1;
                        }
                        return $prefix . $alpha . str_pad($num, 2, '0', STR_PAD_LEFT);
                    } else {
                        // Example: CR09 → CR10
                        $num = (int)substr($maxId, 2);
                        $num++;
                        return $prefix . str_pad($num, 2, '0', STR_PAD_LEFT);
                    }
                } else {
                    // First ID
                    if ($role === 'staff') {
                        return $prefix . 'A01';
                    } else {
                        return $prefix . '01';
                    }
                }
            }


            // Choose the table
            if (!isset($tableMap[$role])) {
                $error = "Invalid role selected.";
            } else {
                $tableName = $tableMap[$role]['table'];
                $idColumn = $tableMap[$role]['id_column'];
                $emailColumn = $tableMap[$role]['email_column'];
                $newId = uniqid(); // You can use a UUID instead for better uniqueness if needed

                // Ensure generated ID is unique (you may want to loop here for robustness)
                $newId = generateRoleId($conn, $role, $tableName, $idColumn);

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
