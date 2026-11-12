<?php
session_start();

if (!isset($_SESSION['DOC_ID'])) {
    header("Location: login_doc.php");
    exit;
}

$servername = "sql310.infinityfree.com";
$username = "if0_43424129";
$password = "Kushal11199";
$database = "if0_43424129_cmrx";
$port = 3306;

$conn = new mysqli($servername, $username, $password, $database, $port);
if ($conn->connect_error) {
    die("Unable to connect to the database.");
}
$conn->set_charset("utf8mb4");

function dashboardEscape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function dashboardTextLength($value)
{
    $matches = [];
    $length = preg_match_all('/./us', $value, $matches);
    return $length === false ? strlen($value) : $length;
}

$doctorId = (int)$_SESSION['DOC_ID'];
$doctorName = $_SESSION['Doc_Name'] ?? 'Doctor';
$certification = $_SESSION['Certification'] ?? '';
$profileFormName = $doctorName;
$profileFormCertification = $certification;
$profileMessage = $_SESSION['doctor_profile_message'] ?? '';
$profileMessageType = $_SESSION['doctor_profile_message_type'] ?? '';
unset($_SESSION['doctor_profile_message'], $_SESSION['doctor_profile_message_type']);

if (empty($_SESSION['doctor_profile_csrf'])) {
    $_SESSION['doctor_profile_csrf'] = bin2hex(random_bytes(32));
}
$doctorProfileCsrf = $_SESSION['doctor_profile_csrf'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_doctor_profile'])) {
    $postedToken = isset($_POST['doctor_profile_csrf']) && is_string($_POST['doctor_profile_csrf'])
        ? $_POST['doctor_profile_csrf']
        : '';

    if (!hash_equals($doctorProfileCsrf, $postedToken)) {
        $profileMessage = "Your session has expired. Reload the page and try again.";
        $profileMessageType = 'error';
    } else {
        $updatedDoctorName = isset($_POST['doctor_name']) && is_string($_POST['doctor_name'])
            ? trim($_POST['doctor_name'])
            : '';
        $updatedCertification = isset($_POST['certification']) && is_string($_POST['certification'])
            ? trim($_POST['certification'])
            : '';

        $profileFormName = $updatedDoctorName;
        $profileFormCertification = $updatedCertification;

        if ($updatedDoctorName === '' || dashboardTextLength($updatedDoctorName) > 100) {
            $profileMessage = "Enter a doctor name of 1 to 100 characters.";
            $profileMessageType = 'error';
        } elseif (dashboardTextLength($updatedCertification) > 255) {
            $profileMessage = "Certification must be 255 characters or fewer.";
            $profileMessageType = 'error';
        } else {
            try {
                $profileStmt = $conn->prepare(
                    "UPDATE doctor_details
                     SET Doc_Name = ?, Certification = ?
                     WHERE DOC_ID = ?"
                );
                if (!$profileStmt) {
                    throw new RuntimeException($conn->error);
                }
                $profileStmt->bind_param("ssi", $updatedDoctorName, $updatedCertification, $doctorId);
                if (!$profileStmt->execute()) {
                    throw new RuntimeException($profileStmt->error);
                }
                $profileStmt->close();

                $_SESSION['Doc_Name'] = $updatedDoctorName;
                $_SESSION['Certification'] = $updatedCertification;
                $_SESSION['doctor_profile_message'] = "Profile settings saved.";
                $_SESSION['doctor_profile_message_type'] = 'success';
                header("Location: dashboard_doc.php#profile-settings");
                exit;
            } catch (mysqli_sql_exception $e) {
                error_log("Doctor profile update failed: " . $e->getMessage());
                $profileMessage = "Unable to save profile settings. Please try again.";
                $profileMessageType = 'error';
            } catch (RuntimeException $e) {
                error_log("Doctor profile update failed: " . $e->getMessage());
                $profileMessage = "Unable to save profile settings. Please try again.";
                $profileMessageType = 'error';
            }
        }
    }
}

$departmentName = 'Department not assigned';
$hospitalName = 'Hospital not assigned';

$stmt = $conn->prepare(
    "SELECT h.Hospital_Name, g.Department_Name
     FROM doctor_details d
     LEFT JOIN hospital h ON h.Hospital_Serial = d.Hospital_Serial
     LEFT JOIN global_departments g ON g.Department_ID = d.Department_ID
     WHERE d.DOC_ID = ?"
);
$stmt->bind_param("i", $doctorId);
$stmt->execute();
$doctorInfo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$doctorInfo) {
    http_response_code(403);
    exit("Doctor account could not be verified.");
}
$hospitalName = $doctorInfo['Hospital_Name'] ?: $hospitalName;
$departmentName = $doctorInfo['Department_Name'] ?: $departmentName;
$certificationDisplay = $certification !== '' ? $certification : 'Not provided';

$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total_visits
     FROM visited_doctor
     WHERE DOC_ID = ? AND Visited_Date = CURDATE()"
);
$stmt->bind_param("i", $doctorId);
$stmt->execute();
$todayVisits = (int)$stmt->get_result()->fetch_assoc()['total_visits'];
$stmt->close();

$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total_prescriptions,
            COALESCE(SUM(Prescription_Date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)), 0) AS recent_prescriptions
     FROM prescription
     WHERE DOC_ID = ?"
);
$stmt->bind_param("i", $doctorId);
$stmt->execute();
$prescriptionStats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$totalPrescriptions = (int)$prescriptionStats['total_prescriptions'];
$recentPrescriptions = (int)$prescriptionStats['recent_prescriptions'];

$stmt = $conn->prepare(
    "SELECT COUNT(DISTINCT MED_ID) AS total_patients
     FROM visited_doctor
     WHERE DOC_ID = ?"
);
$stmt->bind_param("i", $doctorId);
$stmt->execute();
$totalPatients = (int)$stmt->get_result()->fetch_assoc()['total_patients'];
$stmt->close();

$stmt = $conn->prepare(
    "SELECT p.Prescription_Serial, p.MED_ID, p.Prescription_Date, p.Pres_File_Path,
            pd.Name AS Patient_Name
     FROM prescription p
     LEFT JOIN patient_details pd ON pd.MED_ID = p.MED_ID
     WHERE p.DOC_ID = ?
     ORDER BY p.Prescription_Date DESC, p.Prescription_Serial DESC
     LIMIT 5"
);
$stmt->bind_param("i", $doctorId);
$stmt->execute();
$recentPrescriptionRows = $stmt->get_result();
$stmt->close();

$searchMedId = filter_input(INPUT_GET, 'med_id', FILTER_VALIDATE_INT);
$patient = null;
$allergies = [];
$conditions = [];
$reports = [];
$vaccines = [];
$searchPerformed = isset($_GET['med_id']);

if ($searchPerformed && $searchMedId !== false && $searchMedId !== null && $searchMedId > 0) {
    $stmt = $conn->prepare(
        "SELECT MED_ID, Name, Gender, Blood_Group, DOB
         FROM patient_details
         WHERE MED_ID = ?"
    );
    $stmt->bind_param("i", $searchMedId);
    $stmt->execute();
    $patient = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($patient) {
        $stmt = $conn->prepare("SELECT Allergy_Name FROM patient_allergies WHERE MED_ID = ? ORDER BY Allergy_Name");
        $stmt->bind_param("i", $searchMedId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $allergies[] = $row['Allergy_Name'];
        }
        $stmt->close();

        $stmt = $conn->prepare(
            "SELECT Disease_Name, Present_Condition
             FROM disease_history
             WHERE MED_ID = ?
             ORDER BY Disease_Name"
        );
        $stmt->bind_param("i", $searchMedId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $conditions[] = $row;
        }
        $stmt->close();

        $stmt = $conn->prepare(
            "SELECT Report_Name, Report_Date
             FROM diagnosis
             WHERE MED_ID = ?
             ORDER BY Report_Date DESC
             LIMIT 5"
        );
        $stmt->bind_param("i", $searchMedId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $reports[] = $row;
        }
        $stmt->close();

        $stmt = $conn->prepare(
            "SELECT Vaccine_Name, Dose_No, Vacc_Admin_Date
             FROM vaccine_tracking
             WHERE MED_ID = ?
             ORDER BY Vacc_Admin_Date DESC
             LIMIT 5"
        );
        $stmt->bind_param("i", $searchMedId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $vaccines[] = $row;
        }
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#10172b">
    <title>Doctor workspace | CMRx</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            color-scheme: dark;
            --page: #0b1020;
            --panel: rgba(22, 31, 55, .82);
            --panel-strong: #17213a;
            --line: rgba(178, 196, 233, .14);
            --text: #f4f7ff;
            --muted: #9aa8c3;
            --accent: #8b9cff;
            --teal: #4ed8c0;
            --rose: #ff8296;
            --shadow: 0 24px 70px rgba(0, 0, 0, .28);
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            min-height: 100vh;
            color: var(--text);
            background:
                radial-gradient(ellipse at 82% 4%, rgba(90, 105, 230, .22), transparent 34rem),
                radial-gradient(ellipse at 14% 82%, rgba(43, 191, 170, .1), transparent 28rem),
                var(--page);
            font: 15px/1.55 Inter, "Segoe UI", system-ui, sans-serif;
        }
        a { color: inherit; }
        button, input { font: inherit; }
        .layout { min-height: 100vh; }
        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 5;
            display: flex;
            flex-direction: column;
            width: 258px;
            padding: 28px 18px 22px;
            background: rgba(13, 19, 37, .88);
            border-right: 1px solid var(--line);
            backdrop-filter: blur(20px);
        }
        .brand { display: flex; align-items: center; gap: 12px; padding: 4px 10px 34px; }
        .brand-mark {
            display: grid;
            width: 40px;
            height: 40px;
            place-items: center;
            color: #10172b;
            border-radius: 13px;
            background: linear-gradient(140deg, #a9b5ff, #63e3cc);
            box-shadow: 0 8px 26px rgba(105, 202, 220, .22);
            font-size: 19px;
        }
        .brand strong { display: block; letter-spacing: .12em; font-size: 15px; }
        .brand small { color: var(--muted); font-size: 11px; }
        .nav-label { padding: 0 12px 10px; color: #6f7e9c; font-size: 10px; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
        .nav { display: grid; gap: 6px; }
        .nav a {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 12px 13px;
            color: #a9b4cb;
            border: 1px solid transparent;
            border-radius: 12px;
            text-decoration: none;
            transition: .2s ease;
        }
        .nav a i { width: 18px; text-align: center; color: #8594b5; }
        .nav a:hover, .nav a.active {
            color: #f8f9ff;
            border-color: rgba(142, 157, 255, .18);
            background: linear-gradient(100deg, rgba(125, 141, 255, .17), rgba(125, 141, 255, .04));
        }
        .nav a.active i { color: var(--accent); }
        .sidebar-bottom { margin-top: auto; }
        .profile-mini {
            display: flex;
            align-items: center;
            gap: 11px;
            margin-bottom: 15px;
            padding: 14px 10px;
            border-top: 1px solid var(--line);
        }
        .avatar {
            display: grid;
            flex: 0 0 38px;
            width: 38px;
            height: 38px;
            place-items: center;
            color: #11182a;
            border-radius: 50%;
            background: linear-gradient(135deg, #a8b5ff, #56d9c4);
            font-weight: 800;
        }
        .profile-mini strong { display: block; overflow: hidden; max-width: 150px; text-overflow: ellipsis; white-space: nowrap; font-size: 12px; }
        .profile-mini span { color: var(--muted); font-size: 11px; }
        .logout {
            display: flex;
            justify-content: center;
            gap: 9px;
            padding: 11px;
            color: #ffadb9;
            border: 1px solid rgba(255, 130, 150, .2);
            border-radius: 11px;
            background: rgba(255, 130, 150, .06);
            text-decoration: none;
        }
        .main { width: min(1540px, calc(100% - 258px)); margin-left: 258px; padding: 34px clamp(22px, 4vw, 64px) 60px; }
        .topline { display: flex; justify-content: space-between; align-items: center; gap: 18px; margin-bottom: 28px; }
        .eyebrow { color: var(--teal); font-size: 11px; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
        .date-chip { padding: 8px 12px; color: #c5cee2; border: 1px solid var(--line); border-radius: 999px; background: rgba(255,255,255,.035); font-size: 12px; }
        .hero {
            position: relative;
            overflow: hidden;
            padding: clamp(24px, 4vw, 44px);
            border: 1px solid rgba(175, 190, 255, .2);
            border-radius: 24px;
            background: linear-gradient(116deg, rgba(40, 54, 100, .9), rgba(24, 37, 68, .68) 56%, rgba(24, 73, 82, .58));
            box-shadow: var(--shadow);
        }
        .hero::after {
            position: absolute;
            top: -110px;
            right: -40px;
            width: 390px;
            height: 390px;
            border: 1px solid rgba(178, 196, 255, .16);
            border-radius: 50%;
            box-shadow: 0 0 0 42px rgba(178,196,255,.035), 0 0 0 84px rgba(178,196,255,.025);
            content: "";
            pointer-events: none;
        }
        .hero-content { position: relative; z-index: 1; max-width: 700px; }
        .hero h1 { margin: 9px 0 8px; font-size: clamp(27px, 4vw, 42px); letter-spacing: -.04em; line-height: 1.13; }
        .hero p { margin: 0; color: #b7c3dd; max-width: 560px; }
        .hero-meta { display: flex; flex-wrap: wrap; gap: 9px; margin-top: 24px; }
        .hero-meta span { padding: 7px 11px; color: #d3dcf1; border: 1px solid rgba(220,230,255,.16); border-radius: 999px; background: rgba(10,18,37,.22); font-size: 12px; }
        .hero-meta i { margin-right: 6px; color: var(--teal); }
        .section-heading { display: flex; justify-content: space-between; align-items: end; gap: 16px; margin: 34px 0 15px; }
        .section-heading h2 { margin: 0; font-size: 17px; letter-spacing: -.02em; }
        .section-heading p { margin: 4px 0 0; color: var(--muted); font-size: 12px; }
        .stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
        .stat {
            position: relative;
            overflow: hidden;
            min-height: 135px;
            padding: 19px;
            border: 1px solid var(--line);
            border-radius: 17px;
            background: linear-gradient(145deg, rgba(29, 40, 69, .9), rgba(20, 29, 51, .84));
            box-shadow: 0 10px 30px rgba(0,0,0,.12);
        }
        .stat::after { position: absolute; width: 110px; height: 110px; top: -48px; right: -38px; border-radius: 50%; background: var(--glow); opacity: .13; content: ""; }
        .stat-top { display: flex; justify-content: space-between; color: var(--muted); font-size: 12px; }
        .stat-icon { display: grid; width: 34px; height: 34px; place-items: center; color: var(--tone); border: 1px solid color-mix(in srgb, var(--tone), transparent 72%); border-radius: 10px; background: color-mix(in srgb, var(--tone), transparent 90%); }
        .stat-value { display: block; margin-top: 15px; font-size: 29px; font-weight: 750; letter-spacing: -.04em; }
        .stat-note { color: #8391ad; font-size: 11px; }
        .grid-two { display: grid; grid-template-columns: minmax(0, 1.05fr) minmax(320px, .95fr); gap: 16px; }
        .panel {
            min-width: 0;
            padding: 21px;
            border: 1px solid var(--line);
            border-radius: 17px;
            background: linear-gradient(145deg, rgba(23, 33, 58, .92), rgba(17, 26, 47, .92));
            box-shadow: 0 14px 40px rgba(0,0,0,.13);
        }
        .panel-title { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 17px; }
        .panel-title h2 { margin: 0; font-size: 15px; }
        .panel-title p { margin: 3px 0 0; color: var(--muted); font-size: 11px; }
        .panel-title i { color: var(--accent); }
        .form-grid { display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: end; }
        .field label { display: block; margin: 0 0 7px; color: #aab6cd; font-size: 12px; }
        .field input {
            width: 100%;
            min-height: 45px;
            padding: 10px 13px;
            color: var(--text);
            outline: 0;
            border: 1px solid rgba(175, 190, 225, .18);
            border-radius: 10px;
            background: rgba(8, 14, 28, .58);
        }
        .field input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(139,156,255,.13); }
        .button {
            display: inline-flex;
            min-height: 45px;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 15px;
            color: #10172b;
            border: 0;
            border-radius: 10px;
            background: linear-gradient(110deg, #aab5ff, #68dfca);
            cursor: pointer;
            font-weight: 750;
            text-decoration: none;
            white-space: nowrap;
            transition: transform .18s ease, box-shadow .18s ease;
        }
        .button:hover { transform: translateY(-1px); box-shadow: 0 8px 22px rgba(95, 205, 200, .2); }
        .upload { display: block; margin: 0 0 16px; }
        .upload input { position: absolute; width: 1px; height: 1px; overflow: hidden; opacity: 0; }
        .upload-label {
            display: flex;
            min-height: 54px;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 14px;
            color: #adb9d1;
            border: 1px dashed rgba(160, 179, 226, .32);
            border-radius: 11px;
            background: rgba(8, 14, 28, .35);
            cursor: pointer;
        }
        .upload-label i { color: var(--teal); }
        .upload input:focus + .upload-label { outline: 2px solid var(--accent); outline-offset: 3px; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 10px 11px; color: #8190ad; border-bottom: 1px solid var(--line); font-size: 10px; letter-spacing: .1em; text-transform: uppercase; white-space: nowrap; }
        td { padding: 13px 11px; color: #dce4f5; border-bottom: 1px solid rgba(178,196,233,.09); font-size: 12px; }
        tr:last-child td { border-bottom: 0; }
        .id-pill { color: #c7d0ff; font-weight: 700; }
        .muted { color: var(--muted); }
        .empty { padding: 20px 10px; color: var(--muted); text-align: center; }
        .settings-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-bottom: 15px; }
        .profile-settings-message { margin-bottom: 14px; padding: 11px 13px; border: 1px solid transparent; border-radius: 10px; font-size: 12px; }
        .profile-settings-message.success { color: #79e1c3; border-color: rgba(78,216,192,.2); background: rgba(78,216,192,.08); }
        .profile-settings-message.error { color: #ffc5cc; border-color: rgba(255,130,150,.2); background: rgba(255,130,150,.07); }
        .settings-note { margin: 13px 0 0; color: var(--muted); font-size: 11px; }
        .search-results { margin-top: 18px; }
        .patient-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin: 15px 0; }
        .patient-field { padding: 12px; border: 1px solid var(--line); border-radius: 11px; background: rgba(255,255,255,.025); }
        .patient-field span { display: block; color: var(--muted); font-size: 10px; text-transform: uppercase; letter-spacing: .08em; }
        .patient-field strong { display: block; margin-top: 4px; font-size: 13px; overflow-wrap: anywhere; }
        .clinical-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 13px; }
        .clinical-card { padding: 14px; border: 1px solid var(--line); border-radius: 12px; background: rgba(8,14,28,.26); }
        .clinical-card h3 { margin: 0 0 10px; font-size: 12px; }
        .clinical-card h3 i { margin-right: 7px; color: var(--teal); }
        .tags { display: flex; flex-wrap: wrap; gap: 6px; }
        .tag { padding: 5px 8px; color: #ffd2d8; border: 1px solid rgba(255,130,150,.2); border-radius: 999px; background: rgba(255,130,150,.08); font-size: 11px; }
        .clinical-list { display: grid; gap: 7px; margin: 0; padding: 0; list-style: none; }
        .clinical-list li { display: flex; justify-content: space-between; gap: 10px; color: #c5d0e5; font-size: 11px; }
        .clinical-list span:last-child { color: var(--muted); text-align: right; }
        .notice { margin-top: 14px; padding: 12px; color: #ffc5cc; border: 1px solid rgba(255,130,150,.18); border-radius: 10px; background: rgba(255,130,150,.06); }
        .footer-note { margin-top: 26px; color: #73819c; text-align: center; font-size: 11px; }
        @media (max-width: 1120px) {
            .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .grid-two { grid-template-columns: 1fr; }
        }
        @media (max-width: 780px) {
            .sidebar { position: static; width: auto; height: auto; padding: 15px 18px; border-right: 0; border-bottom: 1px solid var(--line); }
            .brand { padding: 0 3px 13px; }
            .nav-label, .sidebar-bottom { display: none; }
            .nav { grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 5px; }
            .nav a { flex-direction: column; gap: 5px; padding: 9px 5px; text-align: center; font-size: 10px; }
            .nav a i { font-size: 14px; }
            .main { width: 100%; margin: 0; padding: 24px 17px 42px; }
        }
        @media (max-width: 520px) {
            .topline { align-items: flex-start; }
            .date-chip { font-size: 10px; }
            .stats { gap: 9px; }
            .stat { min-height: 120px; padding: 14px; }
            .stat-value { font-size: 25px; }
            .form-grid { grid-template-columns: 1fr; }
            .form-grid .button { width: 100%; }
            .patient-grid, .clinical-grid { grid-template-columns: 1fr; }
            .settings-grid { grid-template-columns: 1fr; }
            .panel { padding: 16px; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; }
        }
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-mark"><i class="fa-solid fa-heart-pulse"></i></div>
            <div><strong>CMRx</strong><small>Clinical workspace</small></div>
        </div>
        <div class="nav-label">Workspace</div>
        <nav class="nav" aria-label="Main navigation">
            <a class="active" href="#overview"><i class="fa-solid fa-grid-2"></i>Overview</a>
            <a href="#patient-search"><i class="fa-solid fa-magnifying-glass"></i>Patient records</a>
            <a href="#issue-prescription"><i class="fa-solid fa-prescription-bottle-medical"></i>Prescriptions</a>
            <a href="#recent-activity"><i class="fa-solid fa-clock-rotate-left"></i>Activity</a>
            <a href="#profile-settings"><i class="fa-solid fa-user-gear"></i>Settings</a>
        </nav>
        <div class="sidebar-bottom">
            <div class="profile-mini">
                <div class="avatar"><?php echo dashboardEscape(strtoupper(substr($doctorName, 0, 1))); ?></div>
                <div><strong><?php echo dashboardEscape($doctorName); ?></strong><span>Clinical staff</span></div>
            </div>
            <a class="logout" href="logout_doc.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sign out</a>
        </div>
    </aside>

    <main class="main" id="overview">
        <div class="topline">
            <div class="eyebrow">Care, connected</div>
            <div class="date-chip"><i class="fa-regular fa-calendar"></i> <?php echo date('l, F j, Y'); ?></div>
        </div>

        <section class="hero">
            <div class="hero-content">
                <div class="eyebrow">Doctor workspace</div>
                <h1>Good to see you, Dr. <?php echo dashboardEscape($doctorName); ?>.</h1>
                <p>Your clinical activity, patient records, and prescription workflow in one focused workspace.</p>
                <div class="hero-meta">
                    <span><i class="fa-solid fa-hospital"></i><?php echo dashboardEscape($hospitalName); ?></span>
                    <span><i class="fa-solid fa-stethoscope"></i><?php echo dashboardEscape($departmentName); ?></span>
                    <span><i class="fa-solid fa-award"></i><?php echo dashboardEscape($certificationDisplay); ?></span>
                </div>
            </div>
        </section>

        <div class="section-heading">
            <div><h2>Practice at a glance</h2><p>Live totals from your CMRx activity</p></div>
        </div>
        <section class="stats" aria-label="Doctor activity summary">
            <article class="stat" style="--tone:#65dec7;--glow:#65dec7">
                <div class="stat-top"><span>Visits today</span><span class="stat-icon"><i class="fa-solid fa-user-check"></i></span></div>
                <strong class="stat-value"><?php echo $todayVisits; ?></strong><span class="stat-note">Recorded in visit history</span>
            </article>
            <article class="stat" style="--tone:#a9b5ff;--glow:#a9b5ff">
                <div class="stat-top"><span>Patients seen</span><span class="stat-icon"><i class="fa-solid fa-users"></i></span></div>
                <strong class="stat-value"><?php echo $totalPatients; ?></strong><span class="stat-note">Unique patients in your history</span>
            </article>
            <article class="stat" style="--tone:#ffd286;--glow:#ffd286">
                <div class="stat-top"><span>Prescriptions</span><span class="stat-icon"><i class="fa-solid fa-file-prescription"></i></span></div>
                <strong class="stat-value"><?php echo $totalPrescriptions; ?></strong><span class="stat-note"><?php echo $recentPrescriptions; ?> in the last 7 days</span>
            </article>
            <article class="stat" style="--tone:#ff91a5;--glow:#ff91a5">
                <div class="stat-top"><span>Clinical focus</span><span class="stat-icon"><i class="fa-solid fa-shield-heart"></i></span></div>
                <strong class="stat-value" style="font-size:20px"><?php echo dashboardEscape($departmentName); ?></strong><span class="stat-note">Your assigned department</span>
            </article>
        </section>

        <div class="section-heading">
            <div><h2>Clinical tools</h2><p>Quick access to the records and actions you need</p></div>
        </div>
        <div class="grid-two">
            <section class="panel" id="patient-search">
                <div class="panel-title">
                    <div><h2><i class="fa-solid fa-magnifying-glass"></i> Patient record lookup</h2><p>Review known allergies, history, reports, and vaccinations</p></div>
                </div>
                <form method="get" action="#patient-search">
                    <div class="form-grid">
                        <div class="field">
                            <label for="med_id">Patient MED_ID</label>
                            <input type="number" id="med_id" name="med_id" min="1" step="1" placeholder="Enter a medical record ID" required value="<?php echo $searchPerformed && $searchMedId ? dashboardEscape($searchMedId) : ''; ?>">
                        </div>
                        <button class="button" type="submit"><i class="fa-solid fa-arrow-right"></i> Find record</button>
                    </div>
                </form>

                <?php if ($searchPerformed): ?>
                    <div class="search-results">
                        <?php if ($patient): ?>
                            <div class="panel-title">
                                <div><h2><?php echo dashboardEscape($patient['Name'] ?: 'Patient profile'); ?></h2><p>Record summary · MED_ID <?php echo dashboardEscape($patient['MED_ID']); ?></p></div>
                            </div>
                            <div class="patient-grid">
                                <div class="patient-field"><span>Gender</span><strong><?php echo dashboardEscape($patient['Gender'] ?: 'Not recorded'); ?></strong></div>
                                <div class="patient-field"><span>Blood group</span><strong><?php echo dashboardEscape($patient['Blood_Group'] ?: 'Not recorded'); ?></strong></div>
                                <div class="patient-field"><span>Date of birth</span><strong><?php echo dashboardEscape($patient['DOB'] ?: 'Not recorded'); ?></strong></div>
                            </div>
                            <div class="clinical-grid">
                                <section class="clinical-card">
                                    <h3><i class="fa-solid fa-triangle-exclamation"></i>Allergies</h3>
                                    <?php if ($allergies): ?><div class="tags"><?php foreach ($allergies as $allergy): ?><span class="tag"><?php echo dashboardEscape($allergy); ?></span><?php endforeach; ?></div><?php else: ?><div class="muted">No allergies recorded.</div><?php endif; ?>
                                </section>
                                <section class="clinical-card">
                                    <h3><i class="fa-solid fa-notes-medical"></i>Medical history</h3>
                                    <?php if ($conditions): ?><ul class="clinical-list"><?php foreach ($conditions as $condition): ?><li><span><?php echo dashboardEscape($condition['Disease_Name']); ?></span><span><?php echo dashboardEscape($condition['Present_Condition']); ?></span></li><?php endforeach; ?></ul><?php else: ?><div class="muted">No history recorded.</div><?php endif; ?>
                                </section>
                                <section class="clinical-card">
                                    <h3><i class="fa-solid fa-file-medical"></i>Diagnostic reports</h3>
                                    <?php if ($reports): ?><ul class="clinical-list"><?php foreach ($reports as $report): ?><li><span><?php echo dashboardEscape($report['Report_Name']); ?></span><span><?php echo dashboardEscape($report['Report_Date']); ?></span></li><?php endforeach; ?></ul><?php else: ?><div class="muted">No reports recorded.</div><?php endif; ?>
                                </section>
                                <section class="clinical-card">
                                    <h3><i class="fa-solid fa-syringe"></i>Vaccinations</h3>
                                    <?php if ($vaccines): ?><ul class="clinical-list"><?php foreach ($vaccines as $vaccine): ?><li><span><?php echo dashboardEscape($vaccine['Vaccine_Name']); ?> · dose <?php echo dashboardEscape($vaccine['Dose_No']); ?></span><span><?php echo dashboardEscape($vaccine['Vacc_Admin_Date']); ?></span></li><?php endforeach; ?></ul><?php else: ?><div class="muted">No vaccinations recorded.</div><?php endif; ?>
                                </section>
                            </div>
                        <?php else: ?>
                            <div class="notice"><i class="fa-solid fa-circle-info"></i> No patient record found for MED_ID <?php echo dashboardEscape($searchMedId ?: 'provided'); ?>.</div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="panel" id="issue-prescription">
                <div class="panel-title">
                    <div><h2><i class="fa-solid fa-prescription-bottle-medical"></i> Issue a prescription</h2><p>Attach the signed prescription document for the patient</p></div>
                </div>
                <form action="issue_prescription.php" method="post" enctype="multipart/form-data">
                    <div class="field" style="margin-bottom:15px">
                        <label for="prescription_med_id">Patient MED_ID</label>
                        <input type="number" id="prescription_med_id" name="med_id" min="1" step="1" placeholder="Enter the patient's MED_ID" required>
                    </div>
                    <label class="upload" for="pres_file">
                        <input type="file" id="pres_file" name="pres_file" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required>
                        <span class="upload-label"><span id="file-name">Choose a PDF or Word document</span><i class="fa-solid fa-cloud-arrow-up"></i></span>
                    </label>
                    <button class="button" type="submit"><i class="fa-solid fa-paper-plane"></i> Submit prescription</button>
                </form>
                <div class="notice"><i class="fa-solid fa-lock"></i> Confirm the patient MED_ID before submitting a document.</div>
            </section>
        </div>

        <div class="section-heading" id="recent-activity">
            <div><h2>Recent prescriptions</h2><p>Your five most recently issued records</p></div>
        </div>
        <section class="panel">
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Record</th><th>Patient</th><th>MED_ID</th><th>Issued</th><th>Document</th></tr></thead>
                    <tbody>
                    <?php if ($recentPrescriptionRows->num_rows > 0): ?>
                        <?php while ($row = $recentPrescriptionRows->fetch_assoc()): ?>
                            <tr>
                                <td class="id-pill">RX-<?php echo dashboardEscape($row['Prescription_Serial']); ?></td>
                                <td><?php echo dashboardEscape($row['Patient_Name'] ?: 'Patient'); ?></td>
                                <td><?php echo dashboardEscape($row['MED_ID']); ?></td>
                                <td><?php echo dashboardEscape($row['Prescription_Date']); ?></td>
                                <td><?php echo dashboardEscape($row['Pres_File_Path'] ? basename($row['Pres_File_Path']) : 'Not attached'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td class="empty" colspan="5">No prescriptions have been issued from your account yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="section-heading" id="profile-settings">
            <div><h2>Profile settings</h2><p>Keep your doctor name and certification current</p></div>
        </div>
        <section class="panel">
            <div class="panel-title">
                <div><h2><i class="fa-solid fa-user-gear"></i> Professional profile</h2><p>Changes are saved to your doctor account</p></div>
            </div>
            <?php if ($profileMessage !== ''): ?>
                <div class="profile-settings-message <?php echo dashboardEscape($profileMessageType); ?>" role="status">
                    <?php echo dashboardEscape($profileMessage); ?>
                </div>
            <?php endif; ?>
            <form method="post" action="#profile-settings">
                <input type="hidden" name="doctor_profile_csrf" value="<?php echo dashboardEscape($doctorProfileCsrf); ?>">
                <div class="settings-grid">
                    <div class="field">
                        <label for="doctor_name">Doctor name</label>
                        <input type="text" id="doctor_name" name="doctor_name" maxlength="100" required value="<?php echo dashboardEscape($profileFormName); ?>">
                    </div>
                    <div class="field">
                        <label for="certification">Certification</label>
                        <input type="text" id="certification" name="certification" maxlength="255" value="<?php echo dashboardEscape($profileFormCertification); ?>">
                    </div>
                </div>
                <button class="button" type="submit" name="update_doctor_profile" value="1"><i class="fa-solid fa-floppy-disk"></i> Save settings</button>
            </form>
            <p class="settings-note">Hospital and department assignments are managed by your hospital administrator.</p>
        </section>
        <div class="footer-note">CMRx · Patient information is intended for authorized care workflows.</div>
    </main>
</div>
<script>
    const prescriptionFile = document.getElementById('pres_file');
    prescriptionFile.addEventListener('change', function () {
        const label = document.getElementById('file-name');
        label.textContent = this.files.length ? this.files[0].name : 'Choose a PDF or Word document';
    });
</script>
</body>
</html>
