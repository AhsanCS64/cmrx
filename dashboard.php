<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Database connection settings
$servername = "sql310.infinityfree.com";
$username = "if0_43424129";
$password = "Kushal11199";
$database = "if0_43424129_cmrx";
$port = 3306;

// Create connection
$con = mysqli_connect($servername, $username, $password, $database, $port);

// Check connection
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

// Fetch user details using MED_ID
$med_id = $_SESSION['med_id'];
$stmt = $con->prepare("SELECT Name, DOB, Blood_Group, Gender FROM patient_details WHERE MED_ID = ?");
if ($stmt) {
    $stmt->bind_param("i", $med_id);
    $stmt->execute();
    $stmt->bind_result($name, $dob, $blood_group, $Gender);
    $stmt->fetch();
    $stmt->close();
}

// Fetch phone number
$stmt1 = $con->prepare("SELECT Mobile_No FROM patient_mobile WHERE MED_ID = ?");
if ($stmt1) {
    $stmt1->bind_param("i", $med_id);
    $stmt1->execute();
    $stmt1->bind_result($mobile_no);
    $stmt1->fetch();
    $stmt1->close();
}

// Fetch email
$stmt2 = $con->prepare("SELECT Email FROM patient_email WHERE MED_ID = ?");
if ($stmt2) {
    $stmt2->bind_param("i", $med_id);
    $stmt2->execute();
    $stmt2->bind_result($email);
    $stmt2->fetch();
    $stmt2->close();
}

// Fetch allergies
$allergies = [];
$stmt3 = $con->prepare("SELECT Allergy_Name FROM patient_allergies WHERE MED_ID = ?");
if ($stmt3) {
    $stmt3->bind_param("i", $med_id);
    $stmt3->execute();
    $result = $stmt3->get_result();
    while ($row = $result->fetch_assoc()) {
        $allergies[] = $row['Allergy_Name'];
    }
    $stmt3->close();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $updated_name = $_POST['name'];
    $updated_dob = $_POST['dob'];
    $updated_blood_group = $_POST['blood_group'];
    $updated_mobile = $_POST['mobile_no'];
    $updated_email = $_POST['email'];
    $updated_gender = $_POST['Gender'];

    // Update patient_details table
    $update_stmt1 = $con->prepare("UPDATE patient_details SET Name = ?, DOB = ?, Blood_Group = ?, Gender = ? WHERE MED_ID = ?");
    if ($update_stmt1) {
        $update_stmt1->bind_param("ssssi", $updated_name, $updated_dob, $updated_blood_group, $updated_gender, $med_id);
        $update_stmt1->execute();
        $update_stmt1->close();
    }

    // Update patient_mobile table
    $update_stmt2 = $con->prepare("UPDATE patient_mobile SET Mobile_No = ? WHERE MED_ID = ?");
    if ($update_stmt2) {
        $update_stmt2->bind_param("si", $updated_mobile, $med_id);
        $update_stmt2->execute();
        $update_stmt2->close();
    }

    // Update patient_email table
    $update_stmt3 = $con->prepare("UPDATE patient_email SET Email = ? WHERE MED_ID = ?");
    if ($update_stmt3) {
        $update_stmt3->bind_param("si", $updated_email, $med_id);
        $update_stmt3->execute();
        $update_stmt3->close();
    }
}

// Handle disease update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_disease'])) {
    $disease_name = isset($_POST['disease_name']) ? trim($_POST['disease_name']) : '';
    $present_condition = isset($_POST['present_condition']) ? trim($_POST['present_condition']) : '';

    // Define allowed enum values
    $allowed_conditions = ['Severe', 'Mild', 'Very Mild', 'Cured', 'Seasonal'];

    // Validate input
    if (empty($disease_name) || empty($present_condition)) {
        $message = "Both fields are required.";
    } elseif (!in_array($present_condition, $allowed_conditions)) {
        $message = "Invalid condition value. Please select a valid condition.";
    } else {
        // Update database
        $stmt = $con->prepare("INSERT INTO disease_history (MED_ID, Disease_Name, Present_Condition) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE Disease_Name = ?, Present_Condition = ?");
        if ($stmt) {
            $stmt->bind_param("issss", $med_id, $disease_name, $present_condition, $disease_name, $present_condition);
            if ($stmt->execute()) {
                $message = "Disease updated successfully.";
            } else {
                $message = "Error: Could not update disease.";
            }
            $stmt->close();
        } else {
            $message = "Error preparing statement: " . $con->error;
        }
    }
}

// Handle allergy update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_allergy'])) {
    $allergy_name = isset($_POST['allergy_name']) ? trim($_POST['allergy_name']) : '';

    if (empty($allergy_name)) {
        $allergy_message = "Allergy name is required.";
    } else {
        // Insert allergy into the database
        $stmt = $con->prepare("INSERT INTO patient_allergies (MED_ID, Allergy_Name) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("is", $med_id, $allergy_name);
            if ($stmt->execute()) {
                $allergy_message = "Allergy added successfully.";
                // Refresh allergies list
                $allergies[] = $allergy_name;
            } else {
                $allergy_message = "Error: Could not add allergy.";
            }
            $stmt->close();
        } else {
            $allergy_message = "Error preparing statement: " . $con->error;
        }
    }
}

// Handle vaccine update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_vaccine'])) {
    $vaccine_name = isset($_POST['vaccine_name']) ? trim($_POST['vaccine_name']) : '';
    $dose_no = isset($_POST['dose_no']) ? trim($_POST['dose_no']) : '';
    $vacc_admin_date = isset($_POST['vacc_admin_date']) ? trim($_POST['vacc_admin_date']) : '';

    // Validate input
    if (empty($vaccine_name) || empty($dose_no) || empty($vacc_admin_date)) {
        $vaccine_message = "All fields are required.";
    } else {
        // Insert vaccine record into the database
        $stmt = $con->prepare("INSERT INTO vaccine_tracking (MED_ID, Vaccine_Name, Dose_No, Vacc_Admin_Date) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("isis", $med_id, $vaccine_name, $dose_no, $vacc_admin_date);
            if ($stmt->execute()) {
                $vaccine_message = "Vaccine record added successfully.";
            } else {
                $vaccine_message = "Error: Could not add vaccine record.";
            }
            $stmt->close();
        } else {
            $vaccine_message = "Error preparing statement: " . $con->error;
        }
    }
}

// Fetch disease records for the logged-in user
$diseases = [];
$stmt = $con->prepare("SELECT Disease_Name, Present_Condition FROM disease_history WHERE MED_ID = ?");
if ($stmt) {
    $stmt->bind_param("i", $med_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $diseases[] = $row;
    }
    $stmt->close();
}

// Fetch vaccine records for the logged-in user
$vaccines = [];
$stmt = $con->prepare("SELECT Vaccine_Name, Dose_No, Vacc_Admin_Date FROM vaccine_tracking WHERE MED_ID = ?");
if ($stmt) {
    $stmt->bind_param("i", $med_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $vaccines[] = $row;
    }
    $stmt->close();
}

// Handle prescription file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_prescription'])) {
    $prescription_date = date('Y-m-d'); // Current date
    
    // Handle file upload
    if (isset($_FILES['prescription_file']) && $_FILES['prescription_file']['error'] == 0) {
        $target_dir = "uploads/prescriptions/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . basename($_FILES["prescription_file"]["name"]);
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if file is a PDF
        if ($file_type != "pdf") {
            $upload_message = "Only PDF files are allowed.";
        } else {
            if (move_uploaded_file($_FILES["prescription_file"]["tmp_name"], $target_file)) {
                // Insert file path into the database
                $stmt = $con->prepare("INSERT INTO prescription (MED_ID, Prescription_Date, Pres_File_Path) VALUES (?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("iss", $med_id, $prescription_date, $target_file);
                    if ($stmt->execute()) {
                        $upload_message = "Prescription uploaded successfully.";
                    } else {
                        $upload_message = "Error: Could not upload prescription.";
                    }
                    $stmt->close();
                } else {
                    $upload_message = "Error preparing statement: " . $con->error;
                }
            } else {
                $upload_message = "Error uploading file.";
            }
        }
    } else {
        $upload_message = "No file uploaded or there was an error uploading the file.";
    }
}

// Close the database connection
$con->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMRx | Patient Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        
:root {
    --primary-color: #4361ee;
    --secondary-color: #3f37c9;
    --accent-color: #4895ef;
    --danger-color: #f72585;
    --success-color: #4cc9f0;
    --warning-color: #f8961e;
    --light-color: #f8f9fa;
    --dark-color: #212529;
    --gray-color: #6c757d;
    --glass-bg: rgba(255, 255, 255, 0.15);
    --glass-border: rgba(255, 255, 255, 0.18);
    --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.12);
    --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
    --transition: all 0.3s ease;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
    color: var(--dark-color);
    line-height: 1.6;
}


/* Dashboard Layout */
.dashboard-container {
    display: grid;
    grid-template-columns: 280px 1fr;
    min-height: 100vh;
}

/* Glassmorphism Sidebar */
.sidebar-glass {
    background: var(--glass-bg);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-right: 1px solid var(--glass-border);
    box-shadow: var(--shadow-md);
    padding: 1.5rem 0;
    position: relative;
    z-index: 10;
}

.sidebar-header {
    padding: 0 1.5rem 1.5rem;
    border-bottom: 1px solid var(--glass-border);
}

.sidebar-title {
    color: var(--primary-color);
    font-size: 1.25rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.nav-list {
    list-style: none;
    padding: 1rem 0;
}

.nav-item {
    margin: 0.25rem 0;
    position: relative;
    overflow: hidden;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    color: var(--dark-color);
    text-decoration: none;
    transition: var(--transition);
}

.nav-link:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: translateX(5px);
}

.nav-icon {
    margin-right: 1rem;
    font-size: 1.1rem;
    color: var(--primary-color);
    width: 24px;
    text-align: center;
}

.nav-text {
    font-weight: 500;
}

.logout-item {
    margin-top: 2rem;
    border-top: 1px solid var(--glass-border);
    padding-top: 1rem;
}

.logout-item .nav-link {
    color: var(--danger-color);
}

.logout-item .nav-icon {
    color: var(--danger-color);
}

/* Main Content */
.content-main {
    padding: 2rem;
    overflow-y: auto;
}

.content-header {
    margin-bottom: 2rem;
}

.main-title {
    color: var(--primary-color);
    font-size: 2rem;
    margin-bottom: 0.5rem;
    font-weight: 700;
}

.welcome-message {
    color: var(--gray-color);
    font-weight: 400;
    font-size: 1.25rem;
}

.user-name {
    color: var(--primary-color);
    font-weight: 600;
}

/* Card Design */
.card {
    background: white;
    border-radius: 12px;
    box-shadow: var(--shadow-md);
    padding: 1.5rem;
    margin-bottom: 2rem;
    transition: var(--transition);
}

.card:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-2px);
}

.card-title {
    font-size: 1.25rem;
    margin-bottom: 1.5rem;
    color: var(--primary-color);
    font-weight: 600;
    display: flex;
    align-items: center;
}

.card-title::before {
    content: '';
    display: inline-block;
    width: 8px;
    height: 20px;
    background: var(--primary-color);
    margin-right: 0.75rem;
    border-radius: 4px;
}

/* Profile Grid */
.card-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
}

.info-item {
    background: rgba(67, 97, 238, 0.05);
    padding: 1rem;
    border-radius: 8px;
    border-left: 3px solid var(--primary-color);
}

.info-label {
    display: block;
    font-size: 0.85rem;
    color: var(--gray-color);
    margin-bottom: 0.25rem;
    font-weight: 500;
}

.info-value {
    font-size: 1rem;
    font-weight: 600;
    color: var(--dark-color);
}

/* Tables */
.responsive-table {
    overflow-x: auto;
}

.medical-table, .allergy-table, .vaccine-table {
    width: 100%;
    border-collapse: collapse;
    margin: 1rem 0;
}

.medical-table th, 
.allergy-table th, 
.vaccine-table th {
    background-color: var(--primary-color);
    color: white;
    padding: 0.75rem;
    text-align: left;
}

.medical-table td, 
.allergy-table td, 
.vaccine-table td {
    padding: 0.75rem;
    border-bottom: 1px solid #eee;
}

.medical-table tr:nth-child(even), 
.allergy-table tr:nth-child(even), 
.vaccine-table tr:nth-child(even) {
    background-color: #f9f9f9;
}

.medical-table tr:hover, 
.allergy-table tr:hover, 
.vaccine-table tr:hover {
    background-color: rgba(67, 97, 238, 0.05);
}

/* Condition Badges */
.condition-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.severe { background-color: #ffebee; color: #c62828; }
.mild { background-color: #fff8e1; color: #ff8f00; }
.very-mild { background-color: #e8f5e9; color: #2e7d32; }
.cured { background-color: #e3f2fd; color: #1565c0; }
.seasonal { background-color: #f3e5f5; color: #7b1fa2; }

/* Forms */
.form-card {
    max-width: 800px;
    margin: 0 auto;
}

.form-title {
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
    color: var(--primary-color);
    font-weight: 600;
    position: relative;
    padding-bottom: 0.5rem;
}

.form-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 60px;
    height: 3px;
    background: var(--primary-color);
    border-radius: 3px;
}

.modern-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-label {
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--dark-color);
}

.form-input, .form-select {
    padding: 0.75rem 1rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
    transition: var(--transition);
    background: white;
}

.form-input:focus, .form-select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
}

.form-submit {
    background: var(--primary-color);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    align-self: flex-start;
}

.form-submit:hover {
    background: var(--secondary-color);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

/* File Upload */
.file-upload {
    position: relative;
}

.file-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    border: 2px dashed #ddd;
    border-radius: 8px;
    cursor: pointer;
    transition: var(--transition);
    text-align: center;
}

.file-label:hover {
    border-color: var(--primary-color);
    background: rgba(67, 97, 238, 0.05);
}

.upload-icon {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.file-text {
    font-size: 1rem;
    color: var(--gray-color);
}

.file-upload input[type="file"] {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    border: 0;
}

/* Status Messages */
.status-message {
    padding: 1rem;
    border-radius: 8px;
    margin-top: 1.5rem;
    font-weight: 500;
}

.success {
    background: rgba(76, 201, 240, 0.1);
    color: var(--success-color);
    border-left: 4px solid var(--success-color);
}

.error {
    background: rgba(247, 37, 133, 0.1);
    color: var(--danger-color);
    border-left: 4px solid var(--danger-color);
}

.no-data-message {
    color: var(--gray-color);
    font-style: italic;
    text-align: center;
    padding: 2rem;
}

/* Responsive Design */
@media (max-width: 992px) {
    .dashboard-container {
        grid-template-columns: 1fr;
    }
    
    .sidebar-glass {
        position: fixed;
        width: 100%;
        height: auto;
        bottom: 0;
        left: 0;
        z-index: 100;
        padding: 0.5rem 0;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }
    
    .sidebar-header {
        display: none;
    }
    
    .nav-list {
        display: flex;
        overflow-x: auto;
        padding: 0;
    }
    
    .nav-item {
        flex: 0 0 auto;
        margin: 0;
    }
    
    .nav-link {
        flex-direction: column;
        padding: 0.75rem;
        font-size: 0.8rem;
    }
    
    .nav-icon {
        margin-right: 0;
        margin-bottom: 0.25rem;
        font-size: 1rem;
    }
    
    .logout-item {
        margin-top: 0;
        border-top: none;
        padding-top: 0;
        border-left: 1px solid var(--glass-border);
    }
    
    .content-main {
        padding-bottom: 80px;
    }
}

@media (max-width: 576px) {
    .content-main {
        padding: 1.5rem;
    }
    
    .card-grid {
        grid-template-columns: 1fr;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
}
.sidebar-glass {
  width: 280px;
  height: 100vh;
  top: 0; left: 0;
  background: linear-gradient(135deg, #1e40afcc, #2563ebcc); /* vibrant blue gradient with transparency */
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  box-shadow: 0 8px 40px rgba(37, 99, 235, 0.6);
  border-radius: 16px 0 0 16px;
  border: 1px solid rgba(59, 130, 246, 0.7);

  padding: 25px 0;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  color: #e0f2fe; /* bright icy blue text */
  transition: all 0.3s ease;
  
}

.sidebar-title {
  color: #a0d8ff; /* Soft bright blue */
  text-shadow: 0 0 8px #60aaff;
}

.nav-link {
  color: #cce4ff;
}

.nav-icon {
  color: #85b8ff;
}

.nav-link:hover,
.nav-link:focus {
  background: rgba(30, 70, 150, 0.5);
  color: #e1f0ff;
}

.nav-link:hover .nav-icon,
.nav-link:focus .nav-icon {
  color: #aad1ff;
}

.nav-link.active {
  background: #3d72ff; /* Strong blue background */
  color: #ffffff;
  box-shadow: 0 0 12px #5291ff;
}

.nav-link.active .nav-icon {
  color: #e0eefe;
}

.logout-link {
  color: #fafafaff; /* reddish for logout */
}

.logout-link:hover,
.logout-link:focus {
  background: rgba(255, 40, 40, 0.85);
  color: #ffffffff;
  box-shadow: 0 0 10px #ff4c4c;
}

.logout-link .nav-icon {
  color: #ffffffff;
}

.logout-link:hover .nav-icon,
.logout-link:focus .nav-icon {
  color: #fbf5f5ff;
}

</style>
<style>
    :root {
        --primary-color: #2563eb;
        --secondary-color: #1d4ed8;
        --accent-color: #14b8a6;
        --danger-color: #dc2626;
        --success-color: #059669;
        --warning-color: #d97706;
        --light-color: #f8fafc;
        --dark-color: #0f172a;
        --gray-color: #64748b;
        --shadow-sm: 0 2px 8px rgba(15, 23, 42, 0.04);
        --shadow-md: 0 12px 30px rgba(15, 23, 42, 0.07);
        --shadow-lg: 0 20px 48px rgba(15, 23, 42, 0.1);
        --transition: 180ms ease;
    }

    body {
        background: #f4f7fb;
        color: #0f172a;
        font-family: Inter, "Segoe UI", Arial, sans-serif;
        line-height: 1.55;
    }

    .dashboard-container {
        grid-template-columns: 276px minmax(0, 1fr);
        max-width: 1680px;
        margin: 0 auto;
        background: #f4f7fb;
    }

    .sidebar-glass {
        position: sticky;
        top: 0;
        height: 100vh;
        width: auto;
        overflow-y: auto;
        padding: 1.5rem 1rem;
        border: 0;
        border-radius: 0;
        background: linear-gradient(180deg, #10243f 0%, #142f52 100%);
        box-shadow: 8px 0 32px rgba(15, 23, 42, 0.08);
        color: #e2e8f0;
    }

    .sidebar-header {
        padding: 0.7rem 0.8rem 1.5rem;
        border-bottom: 1px solid rgba(226, 232, 240, 0.12);
    }

    .sidebar-title {
        color: #fff;
        text-shadow: none;
        font-size: 0.92rem;
        letter-spacing: 0.12em;
    }

    .nav-list {
        display: grid;
        gap: 0.35rem;
        padding: 1.1rem 0;
    }

    .nav-item { margin: 0; overflow: visible; }

    .nav-link {
        min-height: 46px;
        padding: 0.7rem 0.85rem;
        border-radius: 12px;
        color: #cbd5e1;
        transition: background var(--transition), color var(--transition), transform var(--transition);
    }

    .nav-link:hover,
    .nav-link:focus-visible {
        background: rgba(255, 255, 255, 0.09);
        color: #fff;
        transform: translateX(3px);
        outline: none;
    }

    .nav-icon,
    .nav-link:hover .nav-icon,
    .nav-link:focus .nav-icon {
        color: #7dd3fc;
    }

    .nav-link.active {
        background: rgba(59, 130, 246, 0.22);
        color: #fff;
        box-shadow: inset 3px 0 #60a5fa;
    }

    .logout-item {
        margin-top: 1.1rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(226, 232, 240, 0.12);
    }

    .logout-link,
    .logout-link .nav-icon { color: #fecaca; }

    .logout-link:hover,
    .logout-link:focus {
        background: rgba(220, 38, 38, 0.2);
        color: #fff;
        box-shadow: none;
    }

    .content-main {
        min-width: 0;
        padding: clamp(1.25rem, 3vw, 2.75rem);
        overflow: visible;
    }

    .content-header {
        position: relative;
        margin-bottom: 1.6rem;
        padding: clamp(1.5rem, 3vw, 2.4rem);
        border: 1px solid rgba(255, 255, 255, 0.55);
        border-radius: 24px;
        background:
            radial-gradient(circle at 90% 15%, rgba(45, 212, 191, 0.25), transparent 28%),
            linear-gradient(120deg, #eaf3ff 0%, #f8fbff 60%, #e8fbf7 100%);
        box-shadow: var(--shadow-sm);
    }

    .main-title {
        margin-bottom: 0.5rem;
        color: #1d4ed8;
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.14em;
        text-transform: uppercase;
    }

    .welcome-message {
        color: #0f172a;
        font-size: clamp(1.7rem, 3vw, 2.35rem);
        font-weight: 750;
        letter-spacing: -0.045em;
    }

    .user-name { color: #2563eb; }

    .card {
        margin-bottom: 1.35rem;
        padding: clamp(1.2rem, 2vw, 1.75rem);
        border: 1px solid #e5eaf2;
        border-radius: 20px;
        background: #fff;
        box-shadow: var(--shadow-sm);
        transition: box-shadow var(--transition), border-color var(--transition);
    }

    .card:hover {
        transform: none;
        border-color: #d9e3f1;
        box-shadow: var(--shadow-md);
    }

    .card-title {
        margin-bottom: 1.25rem;
        color: #0f172a;
        font-size: 1.12rem;
        font-weight: 750;
    }

    .card-title::before {
        width: 4px;
        height: 22px;
        margin-right: 0.75rem;
        border-radius: 99px;
        background: #2563eb;
    }

    .card-grid { grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 0.9rem; }

    .info-item {
        min-width: 0;
        padding: 1rem 1.05rem;
        border: 1px solid #e7edf5;
        border-left: 0;
        border-radius: 14px;
        background: #f8fafc;
    }

    .info-label {
        margin-bottom: 0.35rem;
        color: #64748b;
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .info-value {
        overflow-wrap: anywhere;
        color: #172554;
        font-size: 0.98rem;
        font-weight: 650;
    }

    .responsive-table { margin-top: 0.2rem; border: 1px solid #e8edf4; border-radius: 14px; }

    .medical-table, .allergy-table, .vaccine-table {
        margin: 0;
        border-collapse: separate;
        border-spacing: 0;
        color: #334155;
    }

    .medical-table th, .allergy-table th, .vaccine-table th {
        padding: 0.9rem 1rem;
        background: #f1f5f9;
        color: #475569;
        font-size: 0.75rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .medical-table td, .allergy-table td, .vaccine-table td {
        padding: 0.9rem 1rem;
        border-bottom: 1px solid #edf1f6;
    }

    .medical-table tr:last-child td,
    .allergy-table tr:last-child td,
    .vaccine-table tr:last-child td { border-bottom: 0; }

    .medical-table tr:nth-child(even),
    .allergy-table tr:nth-child(even),
    .vaccine-table tr:nth-child(even) { background: #fff; }

    .medical-table tr:hover,
    .allergy-table tr:hover,
    .vaccine-table tr:hover { background: #f8fbff; }

    .condition-badge {
        padding: 0.32rem 0.72rem;
        font-size: 0.7rem;
        letter-spacing: 0.03em;
    }

    .form-card { max-width: 900px; margin: 0; }
    .form-title { color: #0f172a; font-size: 1.45rem; }
    .form-title::after { background: #2563eb; }
    .modern-form { gap: 1.25rem; }
    .form-grid { grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1.1rem; }
    .form-label { color: #334155; font-size: 0.86rem; font-weight: 700; }
    .form-input, .form-select {
        min-height: 46px;
        border-color: #dbe3ee;
        border-radius: 12px;
        color: #0f172a;
    }

    .form-input:focus, .form-select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
    }

    .form-submit {
        min-height: 46px;
        padding: 0.75rem 1.25rem;
        border-radius: 12px;
        background: #2563eb;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.16);
    }

    .form-submit:hover { background: #1d4ed8; }
    .file-label { border-color: #bfcee0; border-radius: 16px; background: #f8fbff; }
    .file-label:hover { border-color: #3b82f6; background: #eff6ff; }
    .upload-icon { color: #2563eb; }
    .status-message { border-radius: 12px; }
    .success { background: #ecfdf5; color: #047857; border-left-color: #10b981; }
    .error { background: #fef2f2; color: #b91c1c; border-left-color: #ef4444; }
    .no-data-message { color: #64748b; font-style: normal; }

    @media (max-width: 992px) {
        .dashboard-container { display: block; }
        .sidebar-glass {
            position: fixed;
            top: auto;
            bottom: 0;
            left: 0;
            width: 100%;
            height: auto;
            max-height: 88px;
            padding: 0.35rem 0.5rem max(0.35rem, env(safe-area-inset-bottom));
            border-top: 1px solid rgba(255,255,255,0.12);
            background: rgba(16, 36, 63, 0.97);
            box-shadow: 0 -8px 28px rgba(15, 23, 42, 0.12);
        }

        .sidebar-header { display: none; }
        .nav-list { display: flex; gap: 0.3rem; padding: 0; overflow-x: auto; scrollbar-width: thin; }
        .nav-item { flex: 0 0 auto; }
        .nav-link {
            min-width: 74px;
            min-height: 58px;
            flex-direction: column;
            justify-content: center;
            gap: 0.12rem;
            padding: 0.35rem 0.45rem;
            border-radius: 10px;
            font-size: 0.65rem;
            text-align: center;
            white-space: nowrap;
        }
        .nav-link:hover, .nav-link:focus-visible { transform: none; }
        .nav-icon { margin: 0; font-size: 1rem; }
        .nav-link.active { box-shadow: inset 0 2px #60a5fa; }
        .logout-item { margin: 0; padding: 0; border: 0; }
        .content-main { padding-bottom: 105px; }
    }

    @media (max-width: 576px) {
        .content-main { padding: 1rem 0.85rem 105px; }
        .content-header { padding: 1.3rem; border-radius: 18px; }
        .card { padding: 1.1rem; border-radius: 16px; }
        .card-grid, .form-grid { grid-template-columns: 1fr; }
        .responsive-table { overflow-x: auto; }
        .medical-table, .allergy-table, .vaccine-table { min-width: 520px; }
        .nav-link { min-width: 68px; }
    }
</style>
</head>
<body>
    <div class="dashboard-container">
<!-- Glassmorphism Sidebar -->
<aside class="sidebar-glass">
  <div class="sidebar-header">
    <h3 class="sidebar-title">USER DASHBOARD</h3>
  </div>
  <nav class="sidebar-nav" aria-label="Patient navigation">
    <ul class="nav-list">
      <li class="nav-item">
        <a href="Homepage.php" class="nav-link">
          <i class="fas fa-home nav-icon"></i>
          <span class="nav-text">Home Page</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="?action=dashboard" class="nav-link <?php echo !isset($_GET['action']) || $_GET['action'] === 'dashboard' ? 'active' : ''; ?>">
          <i class="fas fa-tachometer-alt nav-icon"></i>
          <span class="nav-text">Dashboard</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="?action=update" class="nav-link <?php echo isset($_GET['action']) && $_GET['action'] === 'update' ? 'active' : ''; ?>">
          <i class="fas fa-user-edit nav-icon"></i>
          <span class="nav-text">Update Profile</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="?action=update_disease" class="nav-link <?php echo isset($_GET['action']) && $_GET['action'] === 'update_disease' ? 'active' : ''; ?>">
          <i class="fas fa-notes-medical nav-icon"></i>
          <span class="nav-text">Update Disease</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="?action=add_allergy" class="nav-link <?php echo isset($_GET['action']) && $_GET['action'] === 'add_allergy' ? 'active' : ''; ?>">
          <i class="fas fa-allergies nav-icon"></i>
          <span class="nav-text">Add Allergy</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="?action=update_vaccine" class="nav-link <?php echo isset($_GET['action']) && $_GET['action'] === 'update_vaccine' ? 'active' : ''; ?>">
          <i class="fas fa-syringe nav-icon"></i>
          <span class="nav-text">Add Vaccine</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="?action=upload_prescription" class="nav-link <?php echo isset($_GET['action']) && $_GET['action'] === 'upload_prescription' ? 'active' : ''; ?>">
          <i class="fas fa-file-upload nav-icon"></i>
          <span class="nav-text">Upload Prescription</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="view_prescriptions.php" class="nav-link">
          <i class="fas fa-file-medical nav-icon"></i>
          <span class="nav-text">View Prescriptions</span>
        </a>
      </li>
      <li class="nav-item logout-item">
        <a href="logout.php" class="nav-link logout-link">
          <i class="fas fa-sign-out-alt nav-icon"></i>
          <span class="nav-text">Log Out</span>
        </a>
      </li>
    </ul>
  </nav>
</aside>


        <!-- Main Content Area -->
        <main class="content-main">
            <?php if (!isset($_GET['action']) || $_GET['action'] === 'dashboard'): ?>
                <header class="content-header">
                    <h1 class="main-title">CMRx</h1>
                    <h2 class="welcome-message">Welcome, <span class="user-name"><?php echo htmlspecialchars($name); ?></span>!</h2>
                </header>

                <!-- User Profile Card -->
                <section class="card profile-card">
                    <h3 class="card-title">Personal Information</h3>
                    <div class="card-grid">
                        <div class="info-item">
                            <span class="info-label">MED_ID:</span>
                            <span class="info-value"><?php echo htmlspecialchars($med_id); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Date of Birth:</span>
                            <span class="info-value"><?php echo htmlspecialchars($dob); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Blood Group:</span>
                            <span class="info-value"><?php echo htmlspecialchars($blood_group); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Mobile Number:</span>
                            <span class="info-value"><?php echo htmlspecialchars($mobile_no); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?php echo htmlspecialchars($email); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Gender:</span>
                            <span class="info-value"><?php echo htmlspecialchars($Gender); ?></span>
                        </div>
                    </div>
                </section>

                <!-- Medical History Section -->
                <section class="card medical-section">
                    <h3 class="card-title">Medical History</h3>
                    <?php if (!empty($diseases)): ?>
                        <div class="responsive-table">
                            <table class="medical-table">
                                <thead>
                                    <tr>
                                        <th>Disease Name</th>
                                        <th>Present Condition</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($diseases as $disease): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($disease['Disease_Name']); ?></td>
                                            <td><span class="condition-badge <?php echo strtolower(str_replace(' ', '-', $disease['Present_Condition'])); ?>"><?php echo htmlspecialchars($disease['Present_Condition']); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="no-data-message">No disease records found.</p>
                    <?php endif; ?>
                </section>

                <!-- Allergies Section -->
                <section class="card allergies-section">
                    <h3 class="card-title">Allergies</h3>
                    <?php if (!empty($allergies)): ?>
                        <div class="responsive-table">
                            <table class="allergy-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Allergy Name</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allergies as $index => $allergy): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($allergy); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="no-data-message">No allergies found.</p>
                    <?php endif; ?>
                </section>

                <!-- Vaccine Records Section -->
                <section class="card vaccine-section">
                    <h3 class="card-title">Vaccine Records</h3>
                    <?php if (!empty($vaccines)): ?>
                        <div class="responsive-table">
                            <table class="vaccine-table">
                                <thead>
                                    <tr>
                                        <th>Vaccine Name</th>
                                        <th>Dose Number</th>
                                        <th>Administration Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($vaccines as $vaccine): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($vaccine['Vaccine_Name']); ?></td>
                                            <td><?php echo htmlspecialchars($vaccine['Dose_No']); ?></td>
                                            <td><?php echo htmlspecialchars($vaccine['Vacc_Admin_Date']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="no-data-message">No vaccine records found.</p>
                    <?php endif; ?>
                </section>

            <?php elseif ($_GET['action'] === 'update'): ?>
                <!-- Update Profile Form -->
                <section class="card form-card">
                    <h2 class="form-title">Update Profile</h2>
                    <form method="POST" action="" class="modern-form">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" id="name" name="name" class="form-input" value="<?php echo htmlspecialchars($name); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="Gender" class="form-label">Gender</label>
                                <select id="Gender" name="Gender" class="form-select" required>
                                    <option value="Male" <?php echo $Gender === 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo $Gender === 'Female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo $Gender === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="dob" class="form-label">Date of Birth</label>
                                <input type="date" id="dob" name="dob" class="form-input" value="<?php echo htmlspecialchars($dob); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="blood_group" class="form-label">Blood Group</label>
                                <input type="text" id="blood_group" name="blood_group" class="form-input" value="<?php echo htmlspecialchars($blood_group); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="mobile_no" class="form-label">Mobile Number</label>
                                <input type="text" id="mobile_no" name="mobile_no" class="form-input" value="<?php echo htmlspecialchars($mobile_no); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" id="email" name="email" class="form-input" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                        </div>
                        <button type="submit" name="update_profile" class="form-submit">Update Profile</button>
                    </form>
                </section>

            <?php elseif ($_GET['action'] === 'update_disease'): ?>
                <!-- Update Disease Form -->
                <section class="card form-card">
                    <h2 class="form-title">Update Disease</h2>
                    <form method="POST" action="" class="modern-form">
                        <div class="form-group">
                            <label for="disease_name" class="form-label">Disease Name</label>
                            <input type="text" id="disease_name" name="disease_name" class="form-input" placeholder="Enter disease name" required>
                        </div>
                        <div class="form-group">
                            <label for="present_condition" class="form-label">Present Condition</label>
                            <select id="present_condition" name="present_condition" class="form-select" required>
                                <option value="">--Select Condition--</option>
                                <option value="Severe">Severe</option>
                                <option value="Mild">Mild</option>
                                <option value="Very Mild">Very Mild</option>
                                <option value="Cured">Cured</option>
                                <option value="Seasonal">Seasonal</option>
                            </select>
                        </div>
                        <button type="submit" name="update_disease" class="form-submit">Update Disease</button>
                    </form>
                    <?php if (isset($message)): ?>
                        <div class="status-message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                </section>

            <?php elseif ($_GET['action'] === 'add_allergy'): ?>
                <!-- Add Allergy Form -->
                <section class="card form-card">
                    <h2 class="form-title">Add Allergy</h2>
                    <form method="POST" action="" class="modern-form">
                        <div class="form-group">
                            <label for="allergy_name" class="form-label">Allergy Name</label>
                            <input type="text" id="allergy_name" name="allergy_name" class="form-input" placeholder="Enter allergy name" required>
                        </div>
                        <button type="submit" name="add_allergy" class="form-submit">Add Allergy</button>
                    </form>
                    <?php if (isset($allergy_message)): ?>
                        <div class="status-message <?php echo strpos($allergy_message, 'successfully') !== false ? 'success' : 'error'; ?>">
                            <?php echo htmlspecialchars($allergy_message); ?>
                        </div>
                    <?php endif; ?>
                </section>

            <?php elseif ($_GET['action'] === 'update_vaccine'): ?>
                <!-- Update Vaccine Form -->
                <section class="card form-card">
                    <h2 class="form-title">Update Vaccine Information</h2>
                    <form method="POST" action="" class="modern-form">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="vaccine_name" class="form-label">Vaccine Name</label>
                                <input type="text" id="vaccine_name" name="vaccine_name" class="form-input" placeholder="Enter vaccine name" required>
                            </div>
                            <div class="form-group">
                                <label for="dose_no" class="form-label">Dose Number</label>
                                <input type="number" id="dose_no" name="dose_no" class="form-input" placeholder="Enter dose number" required>
                            </div>
                            <div class="form-group">
                                <label for="vacc_admin_date" class="form-label">Administration Date</label>
                                <input type="date" id="vacc_admin_date" name="vacc_admin_date" class="form-input" required>
                            </div>
                        </div>
                        <button type="submit" name="update_vaccine" class="form-submit">Update Vaccine</button>
                    </form>
                    <?php if (isset($vaccine_message)): ?>
                        <div class="status-message <?php echo strpos($vaccine_message, 'successfully') !== false ? 'success' : 'error'; ?>">
                            <?php echo htmlspecialchars($vaccine_message); ?>
                        </div>
                    <?php endif; ?>
                </section>

            <?php elseif ($_GET['action'] === 'upload_prescription'): ?>
                <!-- Upload Prescription Form -->
                <section class="card form-card">
                    <h2 class="form-title">Upload Prescription</h2>
                    <form method="POST" action="" enctype="multipart/form-data" class="modern-form">
                        <div class="form-group file-upload">
                            <label for="prescription_file" class="file-label">
                                <i class="fas fa-cloud-upload-alt upload-icon"></i>
                                <span class="file-text">Choose Prescription File (PDF only)</span>
                                <input type="file" id="prescription_file" name="prescription_file" accept="application/pdf" required>
                            </label>
                        </div>
                        <button type="submit" name="upload_prescription" class="form-submit">Upload Prescription</button>
                    </form>
                    <?php if (isset($upload_message)): ?>
                        <div class="status-message <?php echo strpos($upload_message, 'successfully') !== false ? 'success' : 'error'; ?>">
                            <?php echo htmlspecialchars($upload_message); ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>