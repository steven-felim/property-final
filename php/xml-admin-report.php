<?php
session_start();
if (!isset($_SESSION['user_email']) || ($_SESSION['sPosition'] ?? '') !== 'admin') {
    header("Location: index.php");
    exit();
}

$reportMsg = '';
$reportType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['export_properties'])) {
        include 'xml-export-properties.php';
        $reportMsg = "Properties XML exported successfully! <a href='../XML/export-properties.xml' download class='download-link'><i class='fas fa-download'></i> Download File</a>";
        $reportType = 'properties';
    }
    if (isset($_POST['export_viewing_rent'])) {
        include 'xml-export-viewing-rent.php';
        $reportMsg = "Viewing & Rent XML exported successfully! <a href='../XML/viewing-rent.xml' download class='download-link'><i class='fas fa-download'></i> Download File</a>";
        $reportType = 'viewing_rent';
    }
    if (isset($_POST['generate_feed'])) {
        include 'xml-export-rented-property.php';
        $reportMsg = "Properties Feed XML generated successfully! <a href='../XML/properties.xml' download class='download-link'><i class='fas fa-download'></i> Download File</a>";
        $reportType = 'feed';
    }
}

// Set page variables for header
$pageTitle = "XML Reports - Admin Dashboard | HBProperty";
$pageDescription = "Generate and download XML reports for properties, viewings, and rental data.";
$bodyClass = "xml-reports";
$additionalHead = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">';

// Include header
include 'header.php';
?>

<main class="xml-reports-page">
    <div class="container">
        <div class="reports-container">
            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="fas fa-file-code"></i> XML Reports</h1>
                <p class="subtitle">Generate and download comprehensive XML reports for system data</p>
                <div class="admin-badge">
                    <i class="fas fa-shield-alt"></i>
                    Admin Only
                </div>
            </div>

            <!-- Success Message -->
            <?php if (!empty($reportMsg)): ?>
                <div class="message success">
                    <span class="message-icon"><i class="fas fa-check-circle"></i></span>
                    <div class="message-content">
                        <?php echo $reportMsg; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Reports Grid -->
            <div class="reports-grid">
                <!-- Properties Report -->
                <div class="report-card">
                    <div class="report-header">
                        <div class="report-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <div class="report-info">
                            <h3>Properties Report</h3>
                            <p>Export all property listings with detailed information</p>
                        </div>
                    </div>
                    <div class="report-body">
                        <div class="report-details">
                            <div class="detail-item">
                                <i class="fas fa-list"></i>
                                <span>All property data</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-user"></i>
                                <span>Owner information</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>Location details</span>
                            </div>
                        </div>
                        <form method="post">
                            <button type="submit" name="export_properties" class="btn-report btn-primary">
                                <i class="fas fa-download"></i>
                                Generate Properties XML
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Viewing & Rent Report -->
                <div class="report-card">
                    <div class="report-header">
                        <div class="report-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="report-info">
                            <h3>Viewing & Rent Report</h3>
                            <p>Export viewing schedules and rental agreements</p>
                        </div>
                    </div>
                    <div class="report-body">
                        <div class="report-details">
                            <div class="detail-item">
                                <i class="fas fa-eye"></i>
                                <span>Property viewings</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-key"></i>
                                <span>Rental agreements</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-calendar"></i>
                                <span>Date ranges</span>
                            </div>
                        </div>
                        <form method="post">
                            <button type="submit" name="export_viewing_rent" class="btn-report btn-secondary">
                                <i class="fas fa-download"></i>
                                Generate Viewing & Rent XML
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Properties Feed Report -->
                <div class="report-card">
                    <div class="report-header">
                        <div class="report-icon">
                            <i class="fas fa-rss"></i>
                        </div>
                        <div class="report-info">
                            <h3>Properties Feed</h3>
                            <p>Generate XML feed for external property portals</p>
                        </div>
                    </div>
                    <div class="report-body">
                        <div class="report-details">
                            <div class="detail-item">
                                <i class="fas fa-globe"></i>
                                <span>Public feed format</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-sync"></i>
                                <span>Real-time updates</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-share-alt"></i>
                                <span>Portal integration</span>
                            </div>
                        </div>
                        <form method="post">
                            <button type="submit" name="generate_feed" class="btn-report btn-accent">
                                <i class="fas fa-download"></i>
                                Generate Properties Feed XML
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Download Section -->
            <div class="download-section">
                <div class="section-header">
                    <h3><i class="fas fa-cloud-download-alt"></i> Previously Generated Files</h3>
                    <p>Download XML files that were generated earlier</p>
                </div>
                <div class="download-grid">
                    <div class="download-item">
                        <div class="download-icon">
                            <i class="fas fa-file-code"></i>
                        </div>
                        <div class="download-info">
                            <div class="download-name">Properties XML</div>
                            <div class="download-desc">Complete property listings</div>
                        </div>
                        <a href="../XML/export-properties.xml" download class="btn-download">
                            <i class="fas fa-download"></i>
                            Download
                        </a>
                    </div>
                    <div class="download-item">
                        <div class="download-icon">
                            <i class="fas fa-file-code"></i>
                        </div>
                        <div class="download-info">
                            <div class="download-name">Viewing & Rent XML</div>
                            <div class="download-desc">Viewings and rental data</div>
                        </div>
                        <a href="../XML/viewing-rent.xml" download class="btn-download">
                            <i class="fas fa-download"></i>
                            Download
                        </a>
                    </div>
                    <div class="download-item">
                        <div class="download-icon">
                            <i class="fas fa-file-code"></i>
                        </div>
                        <div class="download-info">
                            <div class="download-name">Properties Feed XML</div>
                            <div class="download-desc">External portal feed</div>
                        </div>
                        <a href="../XML/properties.xml" download class="btn-download">
                            <i class="fas fa-download"></i>
                            Download
                        </a>
                    </div>
                </div>
            </div>

            <!-- Back to Dashboard -->
            <div class="back-section">
                <a href="staff.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i>
                    Back to Staff Dashboard
                </a>
            </div>
        </div>
    </div>
</main>

<?php
// Set additional footer scripts
$additionalFooterScripts = '
<style>
.xml-reports-page {
    padding: 100px 0 60px;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
}

.reports-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.page-header {
    text-align: center;
    margin-bottom: 50px;
    padding: 40px 0;
}

.page-header h1 {
    font-size: 3rem;
    color: var(--primary-color);
    margin-bottom: 15px;
    font-weight: 700;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.page-header .subtitle {
    font-size: 1.2rem;
    color: var(--text-secondary);
    font-weight: 500;
    margin-bottom: 20px;
}

.admin-badge {
    display: inline-block;
    background: linear-gradient(135deg, var(--warning-color), #e67e22);
    color: white;
    padding: 10px 20px;
    border-radius: 25px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: var(--shadow-light);
}

.reports-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 30px;
    margin-bottom: 50px;
}

.report-card {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-light);
    overflow: hidden;
    transition: var(--transition);
}

.report-card:hover {
    box-shadow: var(--shadow-medium);
    transform: translateY(-5px);
}

.report-header {
    padding: 30px;
    display: flex;
    align-items: center;
    gap: 20px;
    border-bottom: 1px solid var(--border-color);
}

.report-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.report-info h3 {
    font-size: 1.5rem;
    color: var(--primary-color);
    margin-bottom: 8px;
    font-weight: 700;
}

.report-info p {
    color: var(--text-secondary);
    font-size: 0.95rem;
    line-height: 1.5;
}

.report-body {
    padding: 30px;
}

.report-details {
    margin-bottom: 25px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
    color: var(--text-secondary);
}

.detail-item i {
    width: 20px;
    color: var(--secondary-color);
}

.btn-report {
    width: 100%;
    padding: 15px 25px;
    border: none;
    border-radius: var(--border-radius);
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    font-size: 1rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.btn-report.btn-primary {
    background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
    color: white;
}

.btn-report.btn-secondary {
    background: linear-gradient(135deg, var(--success-color), #27ae60);
    color: white;
}

.btn-report.btn-accent {
    background: linear-gradient(135deg, var(--warning-color), #e67e22);
    color: white;
}

.btn-report:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-medium);
}

.download-section {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-light);
    padding: 40px;
    margin-bottom: 40px;
}

.section-header {
    text-align: center;
    margin-bottom: 30px;
}

.section-header h3 {
    font-size: 1.8rem;
    color: var(--primary-color);
    margin-bottom: 10px;
    font-weight: 700;
}

.section-header p {
    color: var(--text-secondary);
    font-size: 1rem;
}

.download-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.download-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: var(--light-bg);
    border-radius: var(--border-radius);
    transition: var(--transition);
}

.download-item:hover {
    background: #e8f4fd;
    transform: translateY(-2px);
}

.download-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.download-info {
    flex: 1;
}

.download-name {
    font-weight: 600;
    color: var(--primary-color);
    margin-bottom: 4px;
}

.download-desc {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.btn-download {
    padding: 10px 20px;
    background: linear-gradient(135deg, var(--accent-color), #c0392b);
    color: white;
    border: none;
    border-radius: var(--border-radius);
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
}

.btn-download:hover {
    background: linear-gradient(135deg, #c0392b, var(--accent-color));
    transform: translateY(-2px);
    box-shadow: var(--shadow-medium);
}

.download-link {
    color: var(--secondary-color);
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-left: 10px;
    padding: 8px 15px;
    background: var(--light-bg);
    border-radius: var(--border-radius);
    transition: var(--transition);
}

.download-link:hover {
    background: var(--secondary-color);
    color: white;
    transform: translateY(-1px);
}

.back-section {
    text-align: center;
}

.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 15px 30px;
    background: linear-gradient(135deg, var(--text-secondary), #7f8c8d);
    color: white;
    text-decoration: none;
    border-radius: var(--border-radius);
    font-weight: 600;
    transition: var(--transition);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn-back:hover {
    background: linear-gradient(135deg, #7f8c8d, var(--text-secondary));
    transform: translateY(-2px);
    box-shadow: var(--shadow-medium);
}

@media (max-width: 768px) {
    .page-header h1 {
        font-size: 2.5rem;
    }
    
    .reports-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .download-grid {
        grid-template-columns: 1fr;
    }
    
    .report-header {
        padding: 20px;
        flex-direction: column;
        text-align: center;
    }
    
    .report-body {
        padding: 20px;
    }
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Add smooth animations
    const cards = document.querySelectorAll(".report-card, .download-item");
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.style.animation = "fadeInUp 0.6s ease-out forwards";
        card.style.opacity = "0";
        card.style.transform = "translateY(20px)";
    });
    
    // Form submission loading states
    document.querySelectorAll("form").forEach(form => {
        form.addEventListener("submit", function() {
            const submitBtn = this.querySelector("button[type=\"submit\"]");
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = "<i class=\"fas fa-spinner fa-spin\"></i> Generating...";
                submitBtn.disabled = true;
                
                // Re-enable after 3 seconds (in case of success)
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 3000);
            }
        });
    });
});

// CSS Animation keyframe
const style = document.createElement("style");
style.textContent = `
@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
`;
document.head.appendChild(style);
</script>';

// Include footer
include 'footer.php';
?>