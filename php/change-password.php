<?php
session_start();

if (!isset($_SESSION['user_email'])) {
    header("Location: index.php");
    exit();
}

$userEmail = $_SESSION['user_email'];
$userRole = $_SESSION['user_role'];
require_once './db_connection.php';

$tableMap = [
    "client" => "cclient",
    "property_owner" => "privateowner",
    "staff" => "staff"
];

if (!isset($tableMap[$userRole])) {
    die("Invalid role.");
}

$table = $tableMap[$userRole];

// Fetch current hashed password
$emailField = ($userRole === 'staff') ? 'email' : 'eMail';
$stmt = $conn->prepare("SELECT password FROM $table WHERE $emailField = ?");
$stmt->bind_param("s", $userEmail);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User not found.");
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Validate input
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = "Please fill in all fields.";
    } elseif (strlen($newPassword) < 6) {
        $error = "New password must be at least 6 characters long.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "New passwords do not match.";
    } elseif (!password_verify($currentPassword, $user['password'])) {
        $error = "Current password is incorrect.";
    } else {
        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE $table SET password=? WHERE $emailField=?");
        $stmt->bind_param("ss", $hashedPassword, $userEmail);
        if ($stmt->execute()) {
            $success = "Password changed successfully!";
        } else {
            $error = "Error updating password: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Set page variables for header
$pageTitle = "Change Password - HBProperty";
$pageDescription = "Update your account password";
$showSearchForm = false;

// Include header
include 'header.php';
?>

<main class="change-password-page">
    <div class="container">
        <div class="page-header">
            <h1>Change Password</h1>
            <p>Update your account password for security</p>
        </div>

        <div class="password-change-container">
            <div class="password-change-card">
                <?php if (!empty($error)): ?>
                    <div class="notification error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="notification success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form action="change-password.php" method="post" class="change-password-form">
                    <div class="form-group">
                        <label for="current_password">Current Password *</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="current_password" name="current_password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('current_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password *</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="new_password" name="new_password" required minlength="6">
                            <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="form-help">Password must be at least 6 characters long</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password *</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="password-strength">
                        <div class="strength-meter">
                            <div class="strength-bar" id="strength-bar"></div>
                        </div>
                        <div class="strength-text" id="strength-text">Enter a password</div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-key"></i>
                            Change Password
                        </button>
                        <a href="profile.php" class="btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php
$conn->close();

// Set additional footer scripts
$additionalFooterScripts = '
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<script>
// Password visibility toggle
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const toggle = field.nextElementSibling;
    const icon = toggle.querySelector("i");
    
    if (field.type === "password") {
        field.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        field.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}

// Password strength checker
function checkPasswordStrength(password) {
    let strength = 0;
    let feedback = [];

    if (password.length >= 8) {
        strength += 1;
    } else {
        feedback.push("at least 8 characters");
    }

    if (/[a-z]/.test(password)) {
        strength += 1;
    } else {
        feedback.push("lowercase letters");
    }

    if (/[A-Z]/.test(password)) {
        strength += 1;
    } else {
        feedback.push("uppercase letters");
    }

    if (/[0-9]/.test(password)) {
        strength += 1;
    } else {
        feedback.push("numbers");
    }

    if (/[^A-Za-z0-9]/.test(password)) {
        strength += 1;
    } else {
        feedback.push("special characters");
    }

    return { strength, feedback };
}

// Update password strength indicator
document.getElementById("new_password").addEventListener("input", function() {
    const password = this.value;
    const strengthBar = document.getElementById("strength-bar");
    const strengthText = document.getElementById("strength-text");
    
    if (password.length === 0) {
        strengthBar.style.width = "0%";
        strengthBar.className = "strength-bar";
        strengthText.textContent = "Enter a password";
        return;
    }

    const { strength, feedback } = checkPasswordStrength(password);
    const percentage = (strength / 5) * 100;
    
    strengthBar.style.width = percentage + "%";
    
    let className = "strength-bar ";
    let text = "";
    
    if (strength <= 2) {
        className += "weak";
        text = "Weak password";
    } else if (strength <= 3) {
        className += "medium";
        text = "Medium password";
    } else if (strength <= 4) {
        className += "strong";
        text = "Strong password";
    } else {
        className += "very-strong";
        text = "Very strong password";
    }
    
    if (feedback.length > 0) {
        text += " - Add: " + feedback.join(", ");
    }
    
    strengthBar.className = className;
    strengthText.textContent = text;
});

// Password match validation
document.getElementById("confirm_password").addEventListener("input", function() {
    const newPassword = document.getElementById("new_password").value;
    const confirmPassword = this.value;
    
    if (confirmPassword && newPassword !== confirmPassword) {
        this.setCustomValidity("Passwords do not match");
    } else {
        this.setCustomValidity("");
    }
});

// Auto-hide notifications
setTimeout(() => {
    const notifications = document.querySelectorAll(".notification");
    notifications.forEach(notification => {
        notification.style.opacity = "0";
        setTimeout(() => notification.remove(), 300);
    });
}, 5000);
</script>
';

// Include footer
include 'footer.php';
?>
