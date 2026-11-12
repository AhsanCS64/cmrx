<?php
session_start();
if (!isset($_SESSION['hospital_serial'])) {
    header("Location: index.php");
    exit;
}

$servername = "sql310.infinityfree.com";
$username = "if0_43424129";
$password = "Kushal11199";
$port = 3306;
$database = "if0_43424129_cmrx";

$conn = new mysqli($servername, $username, $password, $database, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($_SESSION['hospital_name']); ?> Dashboard</title>
<style>
    :root {
        --primary: #3b82f6;
        --primary-dark: #2563eb;
        --primary-light: #dbeafe;
        --bg: #f8fafc;
        --card: #ffffff;
        --text: #1e293b;
        --text-light: #64748b;
        --border: #e2e8f0;
        --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
        --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.05);
        --radius-sm: 8px;
        --radius-md: 12px;
        --radius-lg: 16px;
        --transition: all 0.2s ease-out;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
    }

    body {
        background: var(--bg);
        color: var(--text);
        display: grid;
        grid-template-columns: 240px 1fr;
        min-height: 100vh;
        line-height: 1.5;
    }

    /* Sidebar */
    .sidebar {
        background: var(--card);
        border-right: 1px solid var(--border);
        padding: 1.5rem 1rem;
        position: sticky;
        top: 0;
        height: 100vh;
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }

    .logo {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem;
        font-weight: 700;
        font-size: 1.1rem;
        color: var(--primary);
    }

    .logo-icon {
        width: 32px;
        height: 32px;
        background: var(--primary);
        color: white;
        border-radius: var(--radius-sm);
        display: grid;
        place-items: center;
        font-weight: 600;
    }

    .nav {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .nav-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem;
        border-radius: var(--radius-sm);
        text-decoration: none;
        color: var(--text);
        transition: var(--transition);
        font-weight: 500;
    }

    .nav-item:hover {
        background: var(--primary-light);
        color: var(--primary-dark);
    }

    .nav-item.active {
        background: var(--primary);
        color: white;
    }

    .nav-icon {
        width: 20px;
        height: 20px;
        flex-shrink: 0;
    }

    /* Main Content */
    .main {
        padding: 2.5rem;
        max-width: 1400px;
        width: 100%;
        margin: 0 auto;
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2.5rem;
    }

 .hospital-info h1 {
    font-size: 2rem; /* Slightly larger for more prominence */
    margin-bottom: 0.5rem;
    font-weight: 700; /* Bold for strong presence */
    color: #1e3a8a; /* Deep blue tone for medical/corporate feel */
    letter-spacing: 0.5px; /* Slightly spaced out for elegance */
    text-transform: uppercase; /* Optional: makes it feel more official */
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; /* Clean, modern font */
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.05); /* Subtle depth */
    transition: all 0.3s ease-in-out; /* Smooth hover effect */
}

.hospital-info h1:hover {
    color: #256eb; /* Slight color shift on hover */
    transform: scale(1.02); /* Slight zoom on hover */
}

    .hospital-address {
        color: var(--text-light);
        font-size: 0.875rem;
    }

    .user {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: var(--primary);
        color: white;
        display: grid;
        place-items: center;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
    }

    .avatar:hover {
        opacity: 0.9;
        transform: scale(1.03);
    }

    /* Stats Cards */
    .stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2.5rem;
    }

    .card {
        background: var(--card);
        border-radius: var(--radius-md);
        padding: 1.75rem;
        box-shadow: var(--shadow-md);
        transition: var(--transition);
        border: 1px solid var(--border);
    }

    .card:hover {
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05), 0 4px 6px -2px rgba(0,0,0,0.05);
        transform: translateY(-2px);
    }

    .stat-title {
        font-size: 0.875rem;
        color: var(--text-light);
        margin-bottom: 0.75rem;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
        letter-spacing: -0.5px;
    }

    /* Tables */
    .table-container {
        background: var(--card);
        border-radius: var(--radius-md);
        padding: 1.5rem;
        box-shadow: var(--shadow-sm);
        margin-bottom: 2rem;
        border: 1px solid var(--border);
        overflow: hidden;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    thead {
        background: var(--bg);
    }

    th {
        text-align: left;
        padding: 1rem;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-light);
        border-bottom: 1px solid var(--border);
    }

    td {
        padding: 1.25rem 1rem;
        border-bottom: 1px solid var(--border);
    }

    tr:last-child td {
        border-bottom: none;
    }

    tr:hover td {
        background: var(--bg);
    }

    .badge {
        display: inline-flex;
        align-items: center;
        padding: 0.375rem 0.875rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.2px;
    }

    .badge-primary {
        background: var(--primary-light);
        color: var(--primary-dark);
    }

    /* Responsive */
    @media (max-width: 768px) {
        body {
            grid-template-columns: 1fr;
        }
        
        .sidebar {
            height: auto;
            position: static;
            border-right: none;
            border-bottom: 1px solid var(--border);
        }
    }
</style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <div class="logo-icon">H</div>
            <span>CMRx</span>
        </div>
        
        <nav>
            <a href="#" class="nav-item active">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M3 12L5 10M5 10L12 3L19 10M5 10V20C5 20.5523 5.44772 21 6 21H9M19 10L21 12M19 10V20C19 20.5523 18.5523 21 18 21H15M9 21C9.55228 21 10 20.5523 10 20V16C10 15.4477 10.4477 15 11 15H13C13.5523 15 14 15.4477 14 16V20C14 20.5523 14.4477 21 15 21M9 21H15"/>
                </svg>
                Dashboard
            </a>
            <a href="Homepage.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88M9 11C11.2091 11 13 9.20914 13 7C13 4.79086 11.2091 3 9 3C6.79086 3 5 4.79086 5 7C5 9.20914 6.79086 11 9 11Z"/>
                </svg>
                Homepage
            </a>
            <a href="#" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5V7H9V5Z"/>
                </svg>
                Departments
            </a>
            <a href="#" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M19 21H5C3.89543 21 3 20.1046 3 19V5C3 3.89543 3.89543 3 5 3H19C20.1046 3 21 3.89543 21 5V19C21 20.1046 20.1046 21 19 21Z"/>
                    <path d="M16.5 7.5H16.51M7.5 7.5H7.51M12 7.5H12.01M7.5 12H7.51M12 12H12.01M16.5 12H16.51M7.5 16.5H7.51M12 16.5H12.01"/>
                </svg>
                Prescriptions
            </a>
            <a href="hlogout.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M9 21H5C3.89543 21 3 20.1046 3 19V5C3 3.89543 3.89543 3 5 3H9M16 17L21 12M21 12L16 7M21 12H9"/>
                </svg>
                Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main">
        <div class="header">
            <div>
                <h1 class="hospital-name"><?php echo htmlspecialchars($_SESSION['hospital_name']); ?></h1>
                <div class="hospital-address"><?php echo htmlspecialchars($_SESSION['hospital_address']); ?></div>
            </div>
            <div class="user">
                <div class="avatar"><?php echo strtoupper(substr($_SESSION['hospital_name'], 0, 2)); ?></div>
                <div>
                    <div><?php echo htmlspecialchars($_SESSION['hospital_name']); ?></div>
                    <div style="font-size: 0.75rem; color: var(--text-light)">Hospital Admin</div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats">
            <div class="card">
                <div class="stat-title">Total Doctors</div>
                <div class="stat-value">
                    <?php
                    $sql = "SELECT COUNT(*) as total FROM doctor_details WHERE Hospital_Serial = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $_SESSION['hospital_serial']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    echo $row["total"];
                    $stmt->close();
                    ?>
                </div>
            
            </div>
            
            <div class="card">
                <div class="stat-title">Active Departments</div>
                <div class="stat-value">
                    <?php
                    $sql = "SELECT COUNT(DISTINCT Department_ID) as total FROM doctor_details WHERE Hospital_Serial = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $_SESSION['hospital_serial']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    echo $row["total"];
                    $stmt->close();
                    ?>
                </div>
            </div>
            
            <div class="card">
                <div class="stat-title">Recent Prescriptions</div>
                <div class="stat-value">
                    <?php
                    $sql = "SELECT COUNT(*) as total FROM prescription p 
                            JOIN doctor_details d ON p.DOC_ID = d.DOC_ID 
                            WHERE d.Hospital_Serial = ? AND p.Prescription_Date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $_SESSION['hospital_serial']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    echo $row["total"];
                    $stmt->close();
                    ?>
                </div>
            </div>
        </div>

        <!-- Doctors Table -->
        <div class="table-container">
            <h2 style="margin-bottom: 1.5rem; font-size: 1.25rem;">Hospital Doctors</h2>
            <table>
                <thead>
                    <tr>
                        <th>Doctor Name</th>
                        <th>Department</th>
                        <th>Certification</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT d.Doc_Name, g.Department_Name, d.Certification 
                            FROM doctor_details d 
                            JOIN global_departments g ON d.Department_ID = g.Department_ID 
                            WHERE d.Hospital_Serial = ? 
                            ORDER BY d.Doc_Name";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $_SESSION['hospital_serial']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>
                                <td>".htmlspecialchars($row["Doc_Name"])."</td>
                                <td>".htmlspecialchars($row["Department_Name"])."</td>
                                <td>".htmlspecialchars($row["Certification"])."</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3'>No doctors found in this hospital</td></tr>";
                    }
                    $stmt->close();
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Recent Prescriptions -->
        <div class="table-container">
            <h2 style="margin-bottom: 1.5rem; font-size: 1.25rem;">Recent Prescriptions</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Doctor</th>
                        <th>Medical ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT p.Prescription_Date, d.Doc_Name, p.MED_ID 
                            FROM prescription p 
                            JOIN doctor_details d ON p.DOC_ID = d.DOC_ID 
                            WHERE d.Hospital_Serial = ? 
                            ORDER BY p.Prescription_Date DESC 
                            LIMIT 5";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $_SESSION['hospital_serial']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>
                                <td>".htmlspecialchars($row["Prescription_Date"])."</td>
                                <td>".htmlspecialchars($row["Doc_Name"])."</td>
                                <td>".htmlspecialchars($row["MED_ID"])."</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3'>No prescriptions found</td></tr>";
                    }
                    $stmt->close();
                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>