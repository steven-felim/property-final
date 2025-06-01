<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get user information from session
$userEmail = $_SESSION['user_email'] ?? '';
$userName = $_SESSION['user_name'] ?? '';
$userRole = $_SESSION['user_role'] ?? '';
$position = $_SESSION['sPosition'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'HBProperty'; ?></title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <meta name="description" content="<?php echo isset($pageDescription) ? htmlspecialchars($pageDescription) : 'Find your perfect rental property with HBProperty. Browse thousands of listings and connect with property owners.'; ?>">
    <?php if (isset($additionalHead)) echo $additionalHead; ?>
</head>
<body<?php echo isset($bodyClass) ? ' class="' . htmlspecialchars($bodyClass) . '"' : ''; ?>>
<header>
    <div class="container">
        <div class="logo">
            <a href="homepage.php">
                <img src="../img/logo.png" alt="HBProperty Logo" class="logo-img">
            </a>
        </div>

        <?php if (isset($showSearchForm) && $showSearchForm): ?>
        <!-- Search Form for Homepage -->
        <form class="search-form" onsubmit="return false;">
            <input type="text" id="searchInput" name="query" placeholder="Search by location, type, or features..." autocomplete="off" onkeyup="searchProperty()" aria-label="Search properties">
            <div id="searchResults" class="search-results" role="listbox" aria-live="polite"></div>
        </form>
        <?php endif; ?>

        <!-- Navigation -->
        <nav>
            <ul>
                <?php if ($userRole === 'staff'): ?>
                    <li><a href="staff.php"<?php echo (basename($_SERVER['PHP_SELF']) === 'staff.php') ? ' class="active"' : ''; ?>>Staff Dashboard</a></li>
                    <?php if ($position === 'admin'): ?>
                        <li><a href="xml-admin-report.php"<?php echo (basename($_SERVER['PHP_SELF']) === 'xml-admin-report.php') ? ' class="active"' : ''; ?>>XML Report</a></li>
                    <?php endif; ?>
                    <li><a href="profile.php"<?php echo (basename($_SERVER['PHP_SELF']) === 'profile.php') ? ' class="active"' : ''; ?>>Profile</a></li>
                    <li><a href="index.php" onclick="return confirmLogout()">Logout</a></li>
                <?php else: ?>
                    <li><a href="properties.php"<?php echo (basename($_SERVER['PHP_SELF']) === 'properties.php') ? ' class="active"' : ''; ?>>Properties</a></li>
                    <li><a href="profile.php"<?php echo (basename($_SERVER['PHP_SELF']) === 'profile.php') ? ' class="active"' : ''; ?>>Profile</a></li>
                    <li><a href="index.php" onclick="return confirmLogout()">Logout</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <?php if (isset($_SERVER['HTTP_REFERER']) && 
            (strpos($_SERVER['HTTP_REFERER'], 'register.php') !== false || 
             strpos($_SERVER['HTTP_REFERER'], 'index.php') !== false) && 
            basename($_SERVER['PHP_SELF']) === 'homepage.php'): ?>
            <script>
                window.onload = function () {
                    if (typeof showNotification === 'function') {
                        showNotification("Welcome, <?php echo htmlspecialchars($userName); ?>!", "success");
                    }
                };
            </script>
        <?php endif; ?>
    </div>
</header>