<?php
session_start();
// Database connection settings
$servername = "sql310.infinityfree.com";
$username = "if0_43424129";
$password = "Kushal11199";
$database = "if0_43424129_cmrx";
$port = 3306;

// Create connection
$con = mysqli_connect($servername, $username, $password, $database, $port);

$med_id = isset($_GET['med_id']) ? intval($_GET['med_id']) : null;

if (!isset($_SESSION['DOC_ID'])) {
    header("Location: login_doc.php");
    exit();
}

$doc_name = $_SESSION['Doc_Name'];
$hospital_serial = $_SESSION['Hospital_Serial'];
$department_id = $_SESSION['Department_ID'];
$certification = $_SESSION['Certification'];

// Fetch Department Name
$dept_query = "SELECT Department_Name FROM global_departments WHERE Department_ID = ?";
$stmt = $con->prepare($dept_query);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$stmt->bind_result($department_name);
$stmt->fetch();
$stmt->close();

// Fetch Hospital Name
$hospital_query = "SELECT Hospital_Name FROM hospital WHERE Hospital_Serial = ?";
$stmt = $con->prepare($hospital_query);
$stmt->bind_param("i", $hospital_serial);
$stmt->execute();
$stmt->bind_result($hospital_name);
$stmt->fetch();
$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMRx | Doctor Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #10b981;
            --dark: #1e293b;
            --light: #f8fafc;
            --glass: rgba(255, 255, 255, 0.15);
            --glass-border: rgba(255, 255, 255, 0.25);
            --card-bg: rgba(255, 255, 255, 0.05);
            --neon-glow: 0 0 10px rgba(99, 102, 241, 0.7);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: var(--light);
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
        }

        /* Neuromorphic Glass Sidebar */
        .sidebar {
            width: 280px;
            height: 100vh;
            background: var(--glass);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-right: 1px solid var(--glass-border);
            padding: 2rem 1.5rem;
            position: fixed;
            z-index: 100;
            display: flex;
            flex-direction: column;
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            margin-bottom: 2.5rem;
        }

        .sidebar-header h2 {
            font-weight: 700;
            font-size: 1.5rem;
            background: linear-gradient(to right, #e0e7ff, #a5b4fc);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: -0.5px;
        }

        .sidebar-nav {
            flex: 1;
        }

        .sidebar-nav ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .sidebar-nav li a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.85rem 1.25rem;
            border-radius: 0.75rem;
            color: var(--light);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .sidebar-nav li a::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 4px;
            height: 100%;
            background: var(--primary);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }

        .sidebar-nav li a:hover {
            background: var(--card-bg);
        }

        .sidebar-nav li a:hover::before,
        .sidebar-nav li a.active::before {
            transform: scaleY(1);
        }

        .sidebar-nav li a i {
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
        }

        .sidebar-footer {
            margin-top: auto;
            padding-top: 1.5rem;
            border-top: 1px solid var(--glass-border);
        }

        /* Main Content */
        .dashboard-container {
            flex: 1;
            margin-left: 280px;
            padding: 2rem 3rem;
            position: relative;
        }

        /* Holographic Header */
        .dashboard-header {
            margin-bottom: 2.5rem;
            position: relative;
        }

        .dashboard-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            background: linear-gradient(to right, #e0e7ff, #a5b4fc, #818cf8);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: -1px;
            line-height: 1.2;
        }

        .dashboard-header h1.welcome {
            font-size: 1.8rem;
            font-weight: 700;
            margin-top: 1.5rem;
        }

        .dashboard-header p {
            color: #94a3b8;
            font-size: 1.1rem;
        }

        /* Bio-luminescent Cards */
        .dashboard-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .info-card {
            background: var(--card-bg);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-radius: 1rem;
            padding: 1.75rem;
            border: 1px solid var(--glass-border);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .info-card::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.1) 0%, transparent 70%);
            transform: rotate(30deg);
            pointer-events: none;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--neon-glow);
            border-color: rgba(99, 102, 241, 0.5);
        }

        .info-card h3 {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
            margin-bottom: 0.5rem;
        }

        .info-card p {
            font-size: 1.4rem;
            font-weight: 600;
            color: white;
        }

        /* Quantum Form Elements */
        .prescription-form, .search-form {
            background: var(--card-bg);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2.5rem;
            border: 1px solid var(--glass-border);
            box-shadow: 0 10px 30px -15px rgba(0, 0, 0, 0.2);
        }

        .prescription-form h3, .search-form h1 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: white;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: #94a3b8;
            font-size: 0.95rem;
        }

        .form-input {
            width: 100%;
            padding: 0.85rem 1.25rem;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid #334155;
            border-radius: 0.75rem;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
        }

        .form-input::placeholder {
            color: #64748b;
        }

        .file-upload {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-upload-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.85rem 1.25rem;
            background: rgba(15, 23, 42, 0.5);
            border: 1px dashed #334155;
            border-radius: 0.75rem;
            color: #64748b;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-upload-label:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .file-upload-input {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.85rem 1.75rem;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-size: 1rem;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 15px -5px var(--primary);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px -5px var(--primary);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        /* Floating Particles Background */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            background: rgba(99, 102, 241, 0.6);
            border-radius: 50%;
            filter: blur(1px);
            animation: float 15s infinite linear;
        }

        @keyframes float {
            0% { transform: translateY(0) rotate(0deg); opacity: 1; }
            100% { transform: translateY(-1000px) rotate(720deg); opacity: 0; }
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .dashboard-container {
                margin-left: 0;
            }
            .sidebar.active {
                transform: translateX(0);
            }
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1.5rem;
            }
            .dashboard-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>
    <!-- Floating Particles Background -->
    <div class="particles" id="particles"></div>

    <!-- Neuroglass Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>CMRx</h2>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="Homepage.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="#issue-prescription"><i class="fas fa-prescription-bottle-alt"></i> Issue Prescription</a></li>
                <li><a href="#"><i class="fas fa-calendar-alt"></i> Appointments</a></li>
                <li><a href="#"><i class="fas fa-chart-line"></i> Analytics</a></li>
                <li><a href="#"><i class="fas fa-bell"></i> Notifications</a></li>
                <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </nav>
        <div class="sidebar-footer">
            <a href="logout_doc.php" class="btn btn-primary" style="width: 100%;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Central Medical Records and Disease Tracking</h1>
            <h1 class="welcome">Welcome back, Dr. <?php echo htmlspecialchars($doc_name); ?></h1>
            <p>Your neuro-enhanced healthcare interface</p>
        </div>

        <div class="dashboard-content">
            <div class="info-card">
                <h3>Hospital</h3>
                <p><?php echo htmlspecialchars($hospital_name); ?></p>
            </div>
            <div class="info-card">
                <h3>Department</h3>
                <p><?php echo htmlspecialchars($department_name); ?></p>
            </div>
            <div class="info-card">
                <h3>Certification</h3>
                <p><?php echo htmlspecialchars($certification); ?></p>
            </div>
            <div class="info-card">
                <h3>Today's Appointments</h3>
                <p>NULL</p>
            </div>
        </div>

        <!-- Holographic Prescription Form -->
        <div class="prescription-form" id="issue-prescription">
            <h3><i class="fas fa-prescription-bottle-alt"></i> Issue Prescription</h3>
            <form action="issue_prescription.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="med_id" class="form-label">Patient MED_ID</label>
                    <input type="text" name="med_id" id="med_id" class="form-input" placeholder="Enter patient identifier" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Prescription File</label>
                    <div class="file-upload">
                        <label class="file-upload-label" id="file-label">
                            <span>Choose file (PDF/DOC)</span>
                            <i class="fas fa-cloud-upload-alt"></i>
                        </label>
                        <input type="file" name="pres_file" class="file-upload-input" accept=".pdf,.doc,.docx" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Issue Prescription
                </button>
            </form>
        </div>

        <!-- Quantum Search Interface -->
        <div class="search-form">
            <h1><i class="fas fa-search"></i> Patient Search</h1>
            <form method="GET" action="">
                <div class="form-group">
                    <label for="search_med_id" class="form-label">Enter MED_ID</label>
                    <input type="number" name="med_id" id="search_med_id" class="form-input" 
                           placeholder="Enter patient identifier" required value="<?php echo htmlspecialchars($med_id); ?>">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Patient Search
                </button>
            </form>
        </div>
    </div>

    <script>
        // Create floating particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 30;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                // Random properties
                const size = Math.random() * 8 + 2;
                const posX = Math.random() * window.innerWidth;
                const posY = Math.random() * window.innerHeight;
                const delay = Math.random() * 15;
                const duration = Math.random() * 10 + 10;
                const opacity = Math.random() * 0.5 + 0.1;
                
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.left = `${posX}px`;
                particle.style.top = `${posY}px`;
                particle.style.animationDelay = `${delay}s`;
                particle.style.animationDuration = `${duration}s`;
                particle.style.opacity = opacity;
                
                particlesContainer.appendChild(particle);
            }
        }

        // File upload label update
        document.querySelector('.file-upload-input').addEventListener('change', function(e) {
            const label = document.getElementById('file-label');
            if (this.files.length > 0) {
                label.querySelector('span').textContent = this.files[0].name;
            } else {
                label.querySelector('span').textContent = 'Choose file (PDF/DOC)';
            }
        });

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            
            // Add active class to current nav item
            const currentPage = window.location.pathname.split('/').pop();
            document.querySelectorAll('.sidebar-nav a').forEach(link => {
                if (link.getAttribute('href') === currentPage) {
                    link.classList.add('active');
                }
            });
        });
    </script>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['med_id'])) {
    $med_id = (int)$_GET['med_id'];
    
    // Retrieve patient details
    $stmt = $con->prepare("SELECT Name, Gender, Blood_Group FROM patient_details WHERE MED_ID = ?");
    $stmt->bind_param("i", $med_id);
    $stmt->execute();
    $stmt->bind_result($name, $gender, $blood_group);
    $stmt->fetch();
    $stmt->close();

    if ($name) {    
        echo "<div class='search-results'>";
        
        // Patient Details Card - Only shown once
        echo "<div class='patient-card glass-card'>";
        echo "<div class='card-header'>";
        echo "<h3><i class='fas fa-user-circle'></i> Patient Profile</h3>";
        echo "<span class='med-id-badge'>MED_ID: " . htmlspecialchars($med_id) . "</span>";
        echo "</div>";
        echo "<div class='card-body'>";
        echo "<div class='patient-info-grid'>";
        echo "<div class='info-item'><span class='info-label'><i class='fas fa-user'></i> Name:</span><span class='info-value'>" . htmlspecialchars($name) . "</span></div>";
        echo "<div class='info-item'><span class='info-label'><i class='fas fa-venus-mars'></i> Gender:</span><span class='info-value'>" . htmlspecialchars($gender) . "</span></div>";
        echo "<div class='info-item'><span class='info-label'><i class='fas fa-tint'></i> Blood Group:</span><span class='info-value'>" . htmlspecialchars($blood_group) . "</span></div>";
        echo "</div>";
        echo "</div>";
        echo "</div>";

        // Allergies Section - Only shown once
        $stmt = $con->prepare("SELECT Allergy_Name FROM patient_allergies WHERE MED_ID = ?");
        $stmt->bind_param("i", $med_id);
        $stmt->execute();
        $stmt->bind_result($allergy_name);
        $allergies = [];
        while ($stmt->fetch()) {
            $allergies[] = $allergy_name;
        }
        $stmt->close();

        if (!empty($allergies)) {
            echo "<div class='data-section glass-card'>";
            echo "<div class='section-header'>";
            echo "<h3><i class='fas fa-allergies'></i> Allergies</h3>";
            echo "</div>";
            echo "<div class='section-body'>";
            echo "<div class='tag-container'>";
            foreach ($allergies as $allergy) {
                echo "<span class='allergy-tag'>" . htmlspecialchars($allergy) . "</span>";
            }
            echo "</div>";
            echo "</div>";
            echo "</div>";
        }

        // Disease History Section - Only shown once
        $stmt = $con->prepare("SELECT Disease_Name, Present_Condition FROM disease_history WHERE MED_ID = ?");
        $stmt->bind_param("i", $med_id);
        $stmt->execute();
        $stmt->bind_result($disease_name, $present_condition);
        $disease_history = [];
        while ($stmt->fetch()) {
            $disease_history[] = [
                'Disease_Name' => $disease_name,
                'Present_Condition' => $present_condition
            ];
        }
        $stmt->close();

        if (!empty($disease_history)) {
            echo "<div class='data-section glass-card'>";
            echo "<div class='section-header'>";
            echo "<h3><i class='fas fa-file-medical-alt'></i> Medical History</h3>";
            echo "</div>";
            echo "<div class='section-body'>";
            echo "<div class='medical-table'>";
            echo "<div class='table-header'>";
            echo "<div class='table-row'>";
            echo "<div class='table-cell'>Condition</div>";
            echo "<div class='table-cell'>Status</div>";
            echo "</div>";
            echo "</div>";
            echo "<div class='table-body'>";
            foreach ($disease_history as $row) {
                echo "<div class='table-row'>";
                echo "<div class='table-cell'>" . htmlspecialchars($row['Disease_Name']) . "</div>";
                echo "<div class='table-cell'><span class='status-badge'>" . htmlspecialchars($row['Present_Condition']) . "</span></div>";
                echo "</div>";
            }
            echo "</div>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
        }

        echo "</div>"; // Close search-results
    } else {
        echo "<div class='no-results glass-card'>";
        echo "<i class='fas fa-user-slash'></i>";
        echo "<p>No patient found with MED_ID: " . htmlspecialchars($med_id) . "</p>";
        echo "</div>";
    }
}
?>
<style>
    /* Search Results Styling */
    .search-results {
        margin-top: 2rem;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .glass-card {
        background: var(--card-bg);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border-radius: 1rem;
        border: 1px solid var(--glass-border);
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .glass-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--neon-glow);
    }

    .card-header, .section-header {
        padding: 1.25rem 1.5rem;
        background: rgba(15, 23, 42, 0.3);
        border-bottom: 1px solid var(--glass-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .card-header h3, .section-header h3 {
        font-size: 1.25rem;
        color: white;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .med-id-badge {
        background: var(--primary);
        color: white;
        padding: 0.35rem 0.75rem;
        border-radius: 2rem;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .card-body, .section-body {
        padding: 1.5rem;
    }

    .patient-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
    }

    .info-item {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .info-label {
        color: #94a3b8;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .info-value {
        color: white;
        font-size: 1.1rem;
        font-weight: 500;
    }

    .tag-container {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .allergy-tag {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        font-size: 0.85rem;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .medical-table {
        display: flex;
        flex-direction: column;
        border-radius: 0.75rem;
        overflow: hidden;
        border: 1px solid var(--glass-border);
    }

    .table-header {
        background: rgba(15, 23, 42, 0.5);
    }

    .table-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
    }

    .table-cell {
        padding: 0.85rem 1rem;
        border-bottom: 1px solid var(--glass-border);
    }

    .table-header .table-cell {
        font-weight: 600;
        color: #94a3b8;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }

    .table-body .table-row:last-child .table-cell {
        border-bottom: none;
    }

    .status-badge {
        background: rgba(16, 185, 129, 0.15);
        color: #10b981;
        padding: 0.35rem 0.75rem;
        border-radius: 2rem;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .no-data {
        color: #94a3b8;
        font-style: italic;
        text-align: center;
        padding: 1rem;
    }

    .no-results {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 3rem 2rem;
        text-align: center;
        margin-top: 2rem;
    }

    .no-results i {
        font-size: 3rem;
        color: #94a3b8;
        margin-bottom: 1.5rem;
    }

    .no-results p {
        color: #e2e8f0;
        font-size: 1.1rem;
    }
</style>
  
</body>
</html>