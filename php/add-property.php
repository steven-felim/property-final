<?php
session_start();

if (!isset($_SESSION['user_email']) || $_SESSION['user_role'] !== 'property_owner') {
    header("Location: index.php");
    exit();
}

require_once './db_connection.php';

// Get ownerNo from PrivateOwner table using session email
$ownerNo = null;
$email = $_SESSION['user_email'];
$stmt = $conn->prepare("SELECT ownerNo FROM privateowner WHERE eMail = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($ownerNo);
$stmt->fetch();
$stmt->close();

if (!$ownerNo) {
    die('Owner not found.');
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $price = floatval($_POST['price']);
    $city = trim($_POST['city']);
    $street = trim($_POST['street']);
    $postcode = trim($_POST['postcode']);
    $rooms = intval($_POST['rooms']);
    $pType = trim($_POST['pType']);
    $branchNo = $_POST['branchNo'];
    $description = trim($_POST['description'] ?? '');

    // Validation
    if (empty($title) || empty($city) || empty($street) || empty($postcode) || empty($pType) || $price <= 0 || $rooms <= 0) {
        $error_message = 'Please fill in all required fields with valid values.';
    } else {
        // Handle multiple image upload
        $imagePaths = [];
        if (isset($_FILES['image']) && $_FILES['image']['error'][0] !== UPLOAD_ERR_NO_FILE) {
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $maxFileSize = 5 * 1024 * 1024;
            
            foreach ($_FILES['image']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['image']['error'][$key] === UPLOAD_ERR_OK) {
                    if (!in_array($_FILES['image']['type'][$key], $allowedTypes)) {
                        $error_message = 'Only JPG, PNG, and GIF images are allowed.';
                        break;
                    }
\
                    if ($_FILES['image']['size'][$key] > $maxFileSize) {
                        $error_message = 'Image size must be less than 5MB.';
                        break;
                    }
                    
                    $imgName = uniqid('property_') . '_' . basename($_FILES['image']['name'][$key]);
                    $targetDir = '../img/';
                    $targetFile = $targetDir . $imgName;
                    
                    if (move_uploaded_file($tmp_name, $targetFile)) {
                        $imagePaths[] = $imgName;
                    } else {
                        $error_message = 'Failed to upload image.';
                        break;
                    }
                }
            }
        }

        if (empty($error_message)) {
            $result = $conn->query("SELECT propertyNo FROM propertyforrent WHERE propertyNo LIKE 'PB%' ORDER BY propertyNo DESC LIMIT 1");
            $lastNo = 0;
            if ($result && $row = $result->fetch_assoc()) {
                $lastNo = intval(substr($row['propertyNo'], 2));
            }
            $newNo = $lastNo + 1;
            $propertyNo = 'PB' . str_pad($newNo, 2, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare("INSERT INTO propertyforrent (propertyNo, street, city, postcode, pType, rooms, rent, ownerNo, branchNo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssdiss", $propertyNo, $street, $city, $postcode, $pType, $rooms, $price, $ownerNo, $branchNo);
            
            if ($stmt->execute()) {
                foreach ($imagePaths as $img) {
                    $stmtImg = $conn->prepare("INSERT INTO propertyimage (propertyNo, image) VALUES (?, ?)");
                    $stmtImg->bind_param("ss", $propertyNo, $img);
                    $stmtImg->execute();
                    $stmtImg->close();
                }
                
                $success_message = 'Property added successfully! Property ID: ' . $propertyNo;

                $title = $price = $city = $street = $postcode = $rooms = $pType = $branchNo = $description = '';
            } else {
                $error_message = 'Error adding property: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Fetch branches for dropdown
$branches = [];
$result = $conn->query("SELECT branchNo, street, city FROM branch ORDER BY city, street");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $branches[] = $row;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Property - HBProperty</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <meta name="description" content="Add a new property to your rental portfolio">
</head>
<body>
<header>
    <div class="container">
        <div class="logo">
            <img src="../img/logo.png" alt="HBProperty Logo" class="logo-img">
        </div>
        <nav>
            <ul>
                <li><a href="homepage.php">Home</a></li>
                <li><a href="properties.php">Properties</a></li>
                <li><a href="viewing.php">Viewing</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="index.php" onclick="return confirmLogout()">Logout</a></li>
            </ul>
        </nav>
    </div>
</header>

<main class="add-property-page">
    <div class="container">
        <div class="page-header">
            <h1>Add New Property</h1>
            <p>List your property and start earning rental income</p>
            <div class="breadcrumb">
                <a href="homepage.php">Home</a> > <a href="properties.php">Properties</a> > <span>Add Property</span>
            </div>
        </div>

        <div class="form-container">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                    </svg>
                    <?php echo htmlspecialchars($success_message); ?>
                    <div class="success-actions">
                        <a href="properties.php" class="btn-outline">View All Properties</a>
                        <a href="viewing.php" class="btn-primary">Manage Viewings</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form id="add-property-form" action="add-property.php" method="post" enctype="multipart/form-data" class="modern-form">
                <div class="form-sections">
                    <section class="form-section">
                        <h3>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                            Basic Information
                        </h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="title">
                                    Property Title *
                                    <span class="help-text">Descriptive name for your property</span>
                                </label>
                                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title ?? ''); ?>" 
                                       placeholder="e.g., Cozy 2BR Apartment in Downtown" required>
                            </div>

                            <div class="form-group">
                                <label for="pType">
                                    Property Type *
                                    <span class="help-text">Select the type of property</span>
                                </label>
                                <select id="pType" name="pType" required>
                                    <option value="">Choose property type</option>
                                    <option value="House" <?php echo (isset($pType) && $pType === 'House') ? 'selected' : ''; ?>>House</option>
                                    <option value="Apartment" <?php echo (isset($pType) && $pType === 'Apartment') ? 'selected' : ''; ?>>Apartment</option>
                                    <option value="Condo" <?php echo (isset($pType) && $pType === 'Condo') ? 'selected' : ''; ?>>Condo</option>
                                    <option value="Studio" <?php echo (isset($pType) && $pType === 'Studio') ? 'selected' : ''; ?>>Studio</option>
                                    <option value="Townhouse" <?php echo (isset($pType) && $pType === 'Townhouse') ? 'selected' : ''; ?>>Townhouse</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="rooms">
                                    Number of Rooms *
                                    <span class="help-text">Total bedrooms</span>
                                </label>
                                <input type="number" id="rooms" name="rooms" value="<?php echo htmlspecialchars($rooms ?? ''); ?>" 
                                       min="1" max="10" placeholder="e.g., 3" required>
                            </div>

                            <div class="form-group">
                                <label for="price">
                                    Monthly Rent *
                                    <span class="help-text">Amount in USD</span>
                                </label>
                                <div class="input-with-icon">
                                    <span class="input-icon">$</span>
                                    <input type="number" id="price" name="price" value="<?php echo htmlspecialchars($price ?? ''); ?>" 
                                           min="1" step="0.01" placeholder="1500" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label for="description">
                                Property Description
                                <span class="help-text">Detailed description of your property (optional)</span>
                            </label>
                            <textarea id="description" name="description" rows="4" 
                                      placeholder="Describe your property's features, amenities, and unique selling points..."><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                        </div>
                    </section>

                    <section class="form-section">
                        <h3>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                            </svg>
                            Location Details
                        </h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="street">
                                    Street Address *
                                    <span class="help-text">Full street address</span>
                                </label>
                                <input type="text" id="street" name="street" value="<?php echo htmlspecialchars($street ?? ''); ?>" 
                                       placeholder="123 Main Street" required>
                            </div>

                            <div class="form-group">
                                <label for="city">
                                    City *
                                    <span class="help-text">City or town</span>
                                </label>
                                <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($city ?? ''); ?>" 
                                       placeholder="New York" required>
                            </div>

                            <div class="form-group">
                                <label for="postcode">
                                    Postal Code *
                                    <span class="help-text">ZIP or postal code</span>
                                </label>
                                <input type="text" id="postcode" name="postcode" value="<?php echo htmlspecialchars($postcode ?? ''); ?>" 
                                       placeholder="10001" required>
                            </div>

                            <div class="form-group">
                                <label for="branchNo">
                                    Managing Branch *
                                    <span class="help-text">Select nearest branch office</span>
                                </label>
                                <select id="branchNo" name="branchNo" required>
                                    <option value="">Select managing branch</option>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?php echo htmlspecialchars($branch['branchNo']); ?>"
                                                <?php echo (isset($branchNo) && $branchNo === $branch['branchNo']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($branch['branchNo'] . ' - ' . $branch['street'] . ', ' . $branch['city']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </section>

                    <section class="form-section">
                        <h3>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                            </svg>
                            Property Images
                        </h3>
                        
                        <div class="image-upload-section">
                            <div class="upload-area" id="upload-area">
                                <div class="upload-content">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                                    </svg>
                                    <h4>Upload Property Images</h4>
                                    <p>Drag and drop images here or click to browse</p>
                                    <p class="upload-info">Supports: JPG, PNG, GIF (Max 5MB each, up to 10 images)</p>
                                </div>
                                <input type="file" id="image" name="image[]" accept="image/*" multiple style="display: none;">
                            </div>
                            
                            <div id="image-preview" class="image-preview"></div>
                        </div>
                    </section>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-outline" onclick="window.history.back()">Cancel</button>
                    <button type="submit" class="btn-submit">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                        </svg>
                        Add Property
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<footer>
    <div class="container">
        <div class="footer-bottom">
            <p>&copy; 2025 HBProperty | All Rights Reserved</p>
        </div>
    </div>
</footer>

<script>
class PropertyForm {
    constructor() {
        this.imageFiles = [];
        this.maxImages = 10;
        this.maxFileSize = 5 * 1024 * 1024;
        this.allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        this.init();
    }

    init() {
        this.setupImageUpload();
        this.setupFormValidation();
        this.setupAutoSave();
    }

    setupImageUpload() {
        const uploadArea = document.getElementById('upload-area');
        const fileInput = document.getElementById('image');
        const imagePreview = document.getElementById('image-preview');

        uploadArea.addEventListener('click', () => fileInput.click());

        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('drag-over');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('drag-over');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('drag-over');
            this.handleFiles(e.dataTransfer.files);
        });

        fileInput.addEventListener('change', (e) => {
            this.handleFiles(e.target.files);
        });
    }

    handleFiles(files) {
        const fileArray = Array.from(files);
        
        if (this.imageFiles.length + fileArray.length > this.maxImages) {
            this.showAlert(`Maximum ${this.maxImages} images allowed`, 'error');
            return;
        }

        fileArray.forEach(file => {
            if (!this.validateFile(file)) return;
            
            this.imageFiles.push(file);
            this.addImagePreview(file);
        });

        this.updateFileInput();
    }

    validateFile(file) {
        if (!this.allowedTypes.includes(file.type)) {
            this.showAlert('Only JPG, PNG, and GIF images are allowed', 'error');
            return false;
        }

        if (file.size > this.maxFileSize) {
            this.showAlert('Image size must be less than 5MB', 'error');
            return false;
        }

        return true;
    }

    addImagePreview(file) {
        const preview = document.getElementById('image-preview');
        const reader = new FileReader();

        reader.onload = (e) => {
            const imageItem = document.createElement('div');
            imageItem.className = 'image-item';
            imageItem.innerHTML = `
                <img src="${e.target.result}" alt="Preview">
                <div class="image-info">
                    <span class="image-name">${file.name}</span>
                    <span class="image-size">${this.formatFileSize(file.size)}</span>
                </div>
                <button type="button" class="remove-image" onclick="propertyForm.removeImage('${file.name}')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                    </svg>
                </button>
            `;
            preview.appendChild(imageItem);
        };

        reader.readAsDataURL(file);
    }

    removeImage(fileName) {
        this.imageFiles = this.imageFiles.filter(file => file.name !== fileName);
        
        const preview = document.getElementById('image-preview');
        const imageItems = preview.querySelectorAll('.image-item');
        
        imageItems.forEach(item => {
            if (item.querySelector('.image-name').textContent === fileName) {
                item.remove();
            }
        });

        this.updateFileInput();
    }

    updateFileInput() {
        const fileInput = document.getElementById('image');
        const dataTransfer = new DataTransfer();
        
        this.imageFiles.forEach(file => {
            dataTransfer.items.add(file);
        });
        
        fileInput.files = dataTransfer.files;
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    setupFormValidation() {
        const form = document.getElementById('add-property-form');
        const inputs = form.querySelectorAll('input[required], select[required]');

        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearFieldError(input));
        });

        form.addEventListener('submit', (e) => {
            if (!this.validateForm()) {
                e.preventDefault();
            }
        });
    }

    validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let message = '';

        if (field.hasAttribute('required') && !value) {
            isValid = false;
            message = 'This field is required';
        } else if (field.type === 'number' && value) {
            const num = parseFloat(value);
            if (isNaN(num) || num <= 0) {
                isValid = false;
                message = 'Please enter a valid positive number';
            }
        } else if (field.type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                message = 'Please enter a valid email address';
            }
        }

        this.setFieldValidation(field, isValid, message);
        return isValid;
    }

    setFieldValidation(field, isValid, message) {
        const group = field.closest('.form-group');
        const errorElement = group.querySelector('.field-error');

        group.classList.toggle('has-error', !isValid);

        if (!isValid && message) {
            if (!errorElement) {
                const error = document.createElement('span');
                error.className = 'field-error';
                error.textContent = message;
                group.appendChild(error);
            } else {
                errorElement.textContent = message;
            }
        } else if (errorElement) {
            errorElement.remove();
        }
    }

    clearFieldError(field) {
        const group = field.closest('.form-group');
        group.classList.remove('has-error');
        const errorElement = group.querySelector('.field-error');
        if (errorElement) {
            errorElement.remove();
        }
    }

    validateForm() {
        const form = document.getElementById('add-property-form');
        const inputs = form.querySelectorAll('input[required], select[required]');
        let isValid = true;

        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });

        if (!isValid) {
            this.showAlert('Please correct the errors below', 'error');

            const firstError = form.querySelector('.has-error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        return isValid;
    }

    setupAutoSave() {
        const form = document.getElementById('add-property-form');
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('input', () => {
                this.saveFormData();
            });
        });

        this.loadFormData();
    }

    saveFormData() {
        const form = document.getElementById('add-property-form');
        const formData = new FormData(form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            if (key !== 'image[]') {
                data[key] = value;
            }
        }
        
        localStorage.setItem('propertyFormData', JSON.stringify(data));
    }

    loadFormData() {
        const savedData = localStorage.getItem('propertyFormData');
        if (!savedData) return;

        try {
            const data = JSON.parse(savedData);
            Object.keys(data).forEach(key => {
                const input = document.querySelector(`[name="${key}"]`);
                if (input && !input.value) { 
                    input.value = data[key];
                }
            });
        } catch (error) {
            console.error('Error loading saved form data:', error);
        }
    }

    clearSavedData() {
        localStorage.removeItem('propertyFormData');
    }

    showAlert(message, type = 'info') {
        const existingAlert = document.querySelector('.alert-temp');
        if (existingAlert) {
            existingAlert.remove();
        }

        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-temp`;
        alert.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
            </svg>
            ${message}
        `;
        
        const container = document.querySelector('.form-container');
        container.insertBefore(alert, container.firstChild);

        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }
}

function confirmLogout() {
    return confirm('Are you sure you want to logout?');
}

document.addEventListener('DOMContentLoaded', () => {
    window.propertyForm = new PropertyForm();
});

<?php if (!empty($success_message)): ?>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.propertyForm) {
            window.propertyForm.clearSavedData();
        }
    });
<?php endif; ?>
</script>

<style>
.add-property-page {
    padding-top: 100px;
    min-height: 100vh;
    background: var(--light-bg);
}

.page-header {
    text-align: center;
    margin-bottom: 40px;
    background: white;
    padding: 40px 0;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-light);
}

.page-header h1 {
    font-size: 2.5rem;
    color: var(--primary-color);
    margin-bottom: 10px;
}

.page-header p {
    font-size: 1.1rem;
    color: var(--text-secondary);
    margin-bottom: 20px;
}

.breadcrumb {
    font-size: 0.9rem;
    color: var(--text-secondary);
}

.breadcrumb a {
    color: var(--secondary-color);
    text-decoration: none;
}

.breadcrumb a:hover {
    text-decoration: underline;
}

/* Form Container */
.form-container {
    max-width: 800px;
    margin: 0 auto;
}

.modern-form {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-light);
    overflow: hidden;
}

/* Form Sections */
.form-sections {
    padding: 0;
}

.form-section {
    padding: 40px;
    border-bottom: 1px solid var(--border-color);
}

.form-section:last-child {
    border-bottom: none;
}

.form-section h3 {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.3rem;
    color: var(--primary-color);
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--light-bg);
}

.form-section h3 svg {
    color: var(--secondary-color);
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 25px;
    margin-bottom: 25px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    font-weight: 600;
    color: var(--primary-color);
    margin-bottom: 8px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.help-text {
    font-size: 0.85rem;
    font-weight: 400;
    color: var(--text-secondary);
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: 15px;
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius);
    font-size: 1rem;
    transition: var(--transition);
    background: white;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--secondary-color);
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.form-group.has-error input,
.form-group.has-error select,
.form-group.has-error textarea {
    border-color: #e74c3c;
}

.field-error {
    color: #e74c3c;
    font-size: 0.85rem;
    margin-top: 5px;
}

.input-with-icon {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-secondary);
    font-weight: 600;
    pointer-events: none;
}

.input-with-icon input {
    padding-left: 35px;
}


.image-upload-section {
    margin-top: 20px;
}

.upload-area {
    border: 3px dashed var(--border-color);
    border-radius: var(--border-radius);
    padding: 40px;
    text-align: center;
    cursor: pointer;
    transition: var(--transition);
    background: var(--light-bg);
}

.upload-area:hover,
.upload-area.drag-over {
    border-color: var(--secondary-color);
    background: rgba(52, 152, 219, 0.05);
}

.upload-content svg {
    color: var(--text-secondary);
    margin-bottom: 15px;
}

.upload-content h4 {
    color: var(--primary-color);
    margin-bottom: 10px;
}

.upload-content p {
    color: var(--text-secondary);
    margin-bottom: 8px;
}

.upload-info {
    font-size: 0.85rem;
    color: var(--text-muted);
}


.image-preview {
    margin-top: 20px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 15px;
}

.image-item {
    position: relative;
    background: white;
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--shadow-light);
    transition: var(--transition);
}

.image-item:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-medium);
}

.image-item img {
    width: 100%;
    height: 120px;
    object-fit: cover;
}

.image-info {
    padding: 10px;
}

.image-name {
    display: block;
    font-size: 0.8rem;
    font-weight: 500;
    color: var(--primary-color);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.image-size {
    font-size: 0.75rem;
    color: var(--text-secondary);
}

.remove-image {
    position: absolute;
    top: 8px;
    right: 8px;
    background: rgba(231, 76, 60, 0.9);
    color: white;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--transition);
}

.remove-image:hover {
    background: #e74c3c;
    transform: scale(1.1);
}

.form-actions {
    padding: 30px 40px;
    background: var(--light-bg);
    display: flex;
    gap: 15px;
    justify-content: flex-end;
}

.btn-submit {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 15px 30px;
    background: var(--secondary-color);
    color: white;
    border: none;
    border-radius: var(--border-radius);
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
}

.btn-submit:hover {
    background: #2980b9;
    transform: translateY(-2px);
    box-shadow: var(--shadow-medium);
}

/* Alerts */
.alert {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 20px;
    border-radius: var(--border-radius);
    margin-bottom: 25px;
    font-weight: 500;
}

.alert-success {
    background: rgba(46, 204, 113, 0.1);
    color: #27ae60;
    border: 1px solid rgba(46, 204, 113, 0.3);
}

.alert-error {
    background: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
    border: 1px solid rgba(231, 76, 60, 0.3);
}

.success-actions {
    display: flex;
    gap: 15px;
    margin-top: 15px;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-section {
        padding: 25px 20px;
    }
    
    .form-actions {
        padding: 20px;
        flex-direction: column;
    }
    
    .page-header h1 {
        font-size: 2rem;
    }
    
    .upload-area {
        padding: 25px 15px;
    }
    
    .success-actions {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .form-container {
        margin: 0 10px;
    }
    
    .image-preview {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    }
}
</style>
</body>
</html>
