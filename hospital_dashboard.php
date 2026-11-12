<?php
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['hospital_serial'])) {
    header("Location: hospital_login.php");
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

function hospitalEscape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function hospitalCount(mysqli $conn, string $sql, int $hospitalSerial): int
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Unable to load hospital dashboard data.");
    }
    $stmt->bind_param("i", $hospitalSerial);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($row['total'] ?? 0);
}

$hospitalSerial = (int)$_SESSION['hospital_serial'];
$hospitalName = $_SESSION['hospital_name'] ?? 'Hospital';
$hospitalAddress = $_SESSION['hospital_address'] ?? '';
$doctorSearch = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$departmentMessage = $_SESSION['hospital_department_message'] ?? '';
$departmentMessageType = $_SESSION['hospital_department_message_type'] ?? 'success';
$departmentMessageTarget = $_SESSION['hospital_department_message_target'] ?? '';
unset(
    $_SESSION['hospital_department_message'],
    $_SESSION['hospital_department_message_type'],
    $_SESSION['hospital_department_message_target']
);

if (empty($_SESSION['hospital_department_csrf'])) {
    $_SESSION['hospital_department_csrf'] = bin2hex(random_bytes(32));
}
$departmentCsrf = $_SESSION['hospital_department_csrf'];

$stmt = $conn->prepare(
    "SELECT Department_ID, Department_Name
     FROM global_departments
     ORDER BY Department_Name ASC"
);
if (!$stmt) {
    error_log("Unable to prepare hospital department options query: " . $conn->error);
    die("Unable to load hospital dashboard data.");
}
$stmt->execute();
$availableDepartments = $stmt->get_result();
$departmentOptions = [];
while ($row = $availableDepartments->fetch_assoc()) {
    $departmentOptions[] = $row;
}
$stmt->close();

try {
    $stmt = $conn->prepare(
        "SELECT g.Department_ID, g.Department_Name
         FROM hospital_departments hd
         INNER JOIN global_departments g ON g.Department_ID = hd.Department_ID
         WHERE hd.Hospital_Serial = ?
         ORDER BY g.Department_Name ASC"
    );
    if (!$stmt) {
        throw new RuntimeException($conn->error);
    }
    $stmt->bind_param("i", $hospitalSerial);
    $stmt->execute();
    $hospitalDepartmentRows = $stmt->get_result();
    $hospitalDepartments = [];
    while ($row = $hospitalDepartmentRows->fetch_assoc()) {
        $hospitalDepartments[] = $row;
    }
    $stmt->close();
} catch (mysqli_sql_exception $exception) {
    error_log("Unable to load hospital departments: " . $exception->getMessage());
    die("Unable to load hospital dashboard data.");
} catch (RuntimeException $exception) {
    error_log("Unable to load hospital departments: " . $exception->getMessage());
    die("Unable to load hospital dashboard data.");
}
$hospitalDepartmentIds = array_map('intval', array_column($hospitalDepartments, 'Department_ID'));
$departmentCatalogIds = array_map('intval', array_column($departmentOptions, 'Department_ID'));
$addableDepartments = array_values(array_filter(
    $departmentOptions,
    function ($department) use ($hospitalDepartmentIds) {
        return !in_array((int)$department['Department_ID'], $hospitalDepartmentIds, true);
    }
));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_hospital_department'])) {
    $postedToken = isset($_POST['hospital_department_csrf']) && is_string($_POST['hospital_department_csrf'])
        ? $_POST['hospital_department_csrf']
        : '';
    $departmentValue = isset($_POST['department_id']) && is_string($_POST['department_id'])
        ? $_POST['department_id']
        : '';
    $newDepartmentId = filter_var($departmentValue, FILTER_VALIDATE_INT);
    $message = '';
    $messageType = 'error';

    if (!hash_equals($departmentCsrf, $postedToken)) {
        $message = 'Your session could not be verified. Please try again.';
    } elseif ($newDepartmentId === false || $newDepartmentId <= 0 || !in_array($newDepartmentId, $departmentCatalogIds, true)) {
        $message = 'Choose a department from the available catalog.';
    } elseif (in_array($newDepartmentId, $hospitalDepartmentIds, true)) {
        $message = 'That department is already enabled for this hospital.';
        $messageType = 'success';
    } else {
        try {
            $stmt = $conn->prepare(
                "INSERT INTO hospital_departments (Hospital_Serial, Department_ID)
                 VALUES (?, ?)"
            );
            if (!$stmt) {
                throw new RuntimeException($conn->error);
            }
            $stmt->bind_param("ii", $hospitalSerial, $newDepartmentId);
            if (!$stmt->execute()) {
                throw new RuntimeException($stmt->error);
            }
            $stmt->close();
            $message = 'Department added to this hospital.';
            $messageType = 'success';
        } catch (mysqli_sql_exception $exception) {
            if ((int)$exception->getCode() === 1062) {
                $message = 'That department is already enabled for this hospital.';
                $messageType = 'success';
            } else {
                error_log("Unable to add hospital department: " . $exception->getMessage());
                $message = 'The department could not be added. Please try again.';
            }
        } catch (RuntimeException $exception) {
            error_log("Unable to add hospital department: " . $exception->getMessage());
            $message = 'The department could not be added. Please try again.';
        }
    }

    $_SESSION['hospital_department_message'] = $message;
    $_SESSION['hospital_department_message_type'] = $messageType;
    $_SESSION['hospital_department_message_target'] = 'departments';
    header("Location: hospital_dashboard.php#departments");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_doctor_department'])) {
    $postedToken = isset($_POST['hospital_department_csrf']) && is_string($_POST['hospital_department_csrf'])
        ? $_POST['hospital_department_csrf']
        : '';
    $doctorIdValue = isset($_POST['doctor_id']) && is_string($_POST['doctor_id'])
        ? $_POST['doctor_id']
        : null;
    $doctorId = $doctorIdValue !== null ? filter_var($doctorIdValue, FILTER_VALIDATE_INT) : false;
    $departmentValue = isset($_POST['department_id']) && is_string($_POST['department_id'])
        ? $_POST['department_id']
        : null;
    $selectedDepartmentId = $departmentValue === '' ? null : filter_var($departmentValue, FILTER_VALIDATE_INT);
    $message = '';
    $messageType = 'error';

    if (!hash_equals($departmentCsrf, $postedToken)) {
        $message = 'Your session could not be verified. Please try again.';
    } elseif ($doctorId === false || $doctorId <= 0) {
        $message = 'The selected doctor is invalid.';
    } elseif ($departmentValue === null || ($departmentValue !== '' && ($selectedDepartmentId === false || $selectedDepartmentId <= 0))) {
        $message = 'The selected department is invalid.';
    } elseif ($selectedDepartmentId !== null && !in_array($selectedDepartmentId, $departmentCatalogIds, true)) {
        $message = 'Choose a department from the available list.';
    } else {
        try {
            $stmt = $conn->prepare(
                "SELECT DOC_ID, Department_ID
                 FROM doctor_details
                 WHERE DOC_ID = ? AND Hospital_Serial = ?"
            );
            if (!$stmt) {
                throw new RuntimeException($conn->error);
            }
            $stmt->bind_param("ii", $doctorId, $hospitalSerial);
            $stmt->execute();
            $doctorRecord = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$doctorRecord) {
                $message = 'That doctor is not part of this hospital roster.';
            } elseif (
                $selectedDepartmentId !== null
                && !in_array($selectedDepartmentId, $hospitalDepartmentIds, true)
                && $selectedDepartmentId !== (int)$doctorRecord['Department_ID']
            ) {
                $message = 'Add that department to this hospital before assigning it to a doctor.';
            } else {
                if ($selectedDepartmentId === null) {
                    $stmt = $conn->prepare(
                        "UPDATE doctor_details
                         SET Department_ID = NULL
                         WHERE DOC_ID = ? AND Hospital_Serial = ?"
                    );
                    if (!$stmt) {
                        throw new RuntimeException($conn->error);
                    }
                    $stmt->bind_param("ii", $doctorId, $hospitalSerial);
                } else {
                    $stmt = $conn->prepare(
                        "UPDATE doctor_details
                         SET Department_ID = ?
                         WHERE DOC_ID = ? AND Hospital_Serial = ?"
                    );
                    if (!$stmt) {
                        throw new RuntimeException($conn->error);
                    }
                    $stmt->bind_param("iii", $selectedDepartmentId, $doctorId, $hospitalSerial);
                }

                if (!$stmt->execute()) {
                    throw new RuntimeException($stmt->error);
                }
                $messageType = 'success';
                $message = $stmt->affected_rows > 0
                    ? 'Doctor department updated successfully.'
                    : 'The doctor already has that department assignment.';
                $stmt->close();
            }
        } catch (mysqli_sql_exception $exception) {
            error_log("Unable to update doctor department: " . $exception->getMessage());
            $message = 'The department could not be updated. Please try again.';
        } catch (RuntimeException $exception) {
            error_log("Unable to update doctor department: " . $exception->getMessage());
            $message = 'The department could not be updated. Please try again.';
        }
    }

    $_SESSION['hospital_department_message'] = $message;
    $_SESSION['hospital_department_message_type'] = $messageType;
    $_SESSION['hospital_department_message_target'] = 'doctor-directory';
    $redirectQuery = $doctorSearch !== '' ? '?q=' . rawurlencode($doctorSearch) : '';
    header("Location: hospital_dashboard.php" . $redirectQuery . "#doctor-directory");
    exit;
}

$totalDoctors = hospitalCount(
    $conn,
    "SELECT COUNT(*) AS total FROM doctor_details WHERE Hospital_Serial = ?",
    $hospitalSerial
);
$totalDepartments = hospitalCount(
    $conn,
    "SELECT COUNT(DISTINCT Department_ID) AS total FROM doctor_details WHERE Hospital_Serial = ?",
    $hospitalSerial
);
$weeklyPrescriptions = hospitalCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM prescription p
     INNER JOIN doctor_details d ON d.DOC_ID = p.DOC_ID
     WHERE d.Hospital_Serial = ?
       AND p.Prescription_Date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)",
    $hospitalSerial
);
$todayVisits = hospitalCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM visited_doctor v
     INNER JOIN doctor_details d ON d.DOC_ID = v.DOC_ID
     WHERE d.Hospital_Serial = ? AND v.Visited_Date = CURDATE()",
    $hospitalSerial
);
$uniquePatients = hospitalCount(
    $conn,
    "SELECT COUNT(DISTINCT v.MED_ID) AS total
     FROM visited_doctor v
     INNER JOIN doctor_details d ON d.DOC_ID = v.DOC_ID
     WHERE d.Hospital_Serial = ?",
    $hospitalSerial
);
$diagnosisReports = hospitalCount(
    $conn,
    "SELECT COUNT(*) AS total FROM diagnosis WHERE Hospital_Serial = ?",
    $hospitalSerial
);
$vaccineRecords = hospitalCount(
    $conn,
    "SELECT COUNT(*) AS total FROM vaccine_tracking WHERE Hospital_Serial = ?",
    $hospitalSerial
);

$stmt = $conn->prepare(
    "SELECT g.Department_Name,
            COUNT(DISTINCT d.DOC_ID) AS doctor_count,
            COUNT(DISTINCT p.Prescription_Serial) AS prescription_count
     FROM global_departments g
     INNER JOIN doctor_details d ON d.Department_ID = g.Department_ID
     LEFT JOIN prescription p ON p.DOC_ID = d.DOC_ID
     WHERE d.Hospital_Serial = ?
     GROUP BY g.Department_ID, g.Department_Name
     ORDER BY doctor_count DESC, g.Department_Name ASC"
);
$stmt->bind_param("i", $hospitalSerial);
$stmt->execute();
$departmentRows = $stmt->get_result();
$departments = [];
$maxDepartmentDoctors = 0;
while ($row = $departmentRows->fetch_assoc()) {
    $departments[] = $row;
    $maxDepartmentDoctors = max($maxDepartmentDoctors, (int)$row['doctor_count']);
}
$stmt->close();

$doctorSql =
    "SELECT d.DOC_ID, d.Doc_Name, d.Certification, d.Department_ID, g.Department_Name,
            COUNT(DISTINCT p.Prescription_Serial) AS prescription_count
     FROM doctor_details d
     LEFT JOIN global_departments g ON g.Department_ID = d.Department_ID
     LEFT JOIN prescription p ON p.DOC_ID = d.DOC_ID
     WHERE d.Hospital_Serial = ?";
if ($doctorSearch !== '') {
    $doctorSql .= " AND (d.Doc_Name LIKE ? OR g.Department_Name LIKE ?)";
}
$doctorSql .=
    " GROUP BY d.DOC_ID, d.Doc_Name, d.Certification, d.Department_ID, g.Department_Name
      ORDER BY d.Doc_Name ASC
      LIMIT 50";
$stmt = $conn->prepare($doctorSql);
if ($doctorSearch !== '') {
    $like = '%' . $doctorSearch . '%';
    $stmt->bind_param("iss", $hospitalSerial, $like, $like);
} else {
    $stmt->bind_param("i", $hospitalSerial);
}
$stmt->execute();
$doctorRows = $stmt->get_result();

$stmt = $conn->prepare(
    "SELECT p.Prescription_Serial, p.MED_ID, p.Prescription_Date,
            d.Doc_Name, pd.Name AS Patient_Name
     FROM prescription p
     INNER JOIN doctor_details d ON d.DOC_ID = p.DOC_ID
     LEFT JOIN patient_details pd ON pd.MED_ID = p.MED_ID
     WHERE d.Hospital_Serial = ?
     ORDER BY p.Prescription_Date DESC, p.Prescription_Serial DESC
     LIMIT 8"
);
$stmt->bind_param("i", $hospitalSerial);
$stmt->execute();
$prescriptionRows = $stmt->get_result();

$stmt = $conn->prepare(
    "SELECT x.MED_ID, x.Report_Name, x.Report_Date, pd.Name AS Patient_Name
     FROM diagnosis x
     LEFT JOIN patient_details pd ON pd.MED_ID = x.MED_ID
     WHERE x.Hospital_Serial = ?
     ORDER BY x.Report_Date DESC
     LIMIT 6"
);
$stmt->bind_param("i", $hospitalSerial);
$stmt->execute();
$reportRows = $stmt->get_result();

$stmt = $conn->prepare(
    "SELECT v.MED_ID, v.Vaccine_Name, v.Dose_No, v.Vacc_Admin_Date,
            pd.Name AS Patient_Name
     FROM vaccine_tracking v
     LEFT JOIN patient_details pd ON pd.MED_ID = v.MED_ID
     WHERE v.Hospital_Serial = ?
     ORDER BY v.Vacc_Admin_Date DESC
     LIMIT 6"
);
$stmt->bind_param("i", $hospitalSerial);
$stmt->execute();
$vaccineRows = $stmt->get_result();
$conn->close();

$doctorInitials = strtoupper(substr($hospitalName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f5f7fb">
    <title><?php echo hospitalEscape($hospitalName); ?> | CMRx Hospital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --ink: #17233c;
            --muted: #74819a;
            --line: #e6ebf3;
            --paper: #fff;
            --canvas: #f5f7fb;
            --navy: #101b35;
            --blue: #4667e8;
            --teal: #17a994;
            --shadow: 0 16px 42px rgba(32, 54, 94, .08);
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; color: var(--ink); background: var(--canvas); font: 14px/1.5 Inter, "Segoe UI", system-ui, sans-serif; }
        a { color: inherit; }
        button, input { font: inherit; }
        .shell { min-height: 100vh; }
        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 4;
            display: flex;
            flex-direction: column;
            width: 250px;
            padding: 27px 18px 20px;
            color: #dce5f8;
            background:
                radial-gradient(circle at 0 0, rgba(89, 113, 226, .2), transparent 18rem),
                var(--navy);
        }
        .brand { display: flex; align-items: center; gap: 12px; padding: 2px 9px 36px; }
        .brand-icon { display: grid; width: 40px; height: 40px; place-items: center; color: white; border-radius: 13px; background: linear-gradient(140deg, #7188ff, #31bfae); box-shadow: 0 8px 24px rgba(72, 131, 224, .28); }
        .brand strong { display: block; color: #fff; font-size: 15px; letter-spacing: .12em; }
        .brand small { color: #93a2c2; font-size: 11px; }
        .nav-label { padding: 0 12px 10px; color: #8291b1; font-size: 10px; font-weight: 800; letter-spacing: .15em; text-transform: uppercase; }
        .nav { display: grid; gap: 6px; }
        .nav a { display: flex; align-items: center; gap: 12px; padding: 12px 13px; color: #aab8d2; border: 1px solid transparent; border-radius: 11px; text-decoration: none; transition: .2s ease; }
        .nav a i { width: 18px; color: #92a4c7; text-align: center; }
        .nav a:hover, .nav a.active { color: white; border-color: rgba(167, 185, 255, .13); background: linear-gradient(100deg, rgba(94, 118, 231, .3), rgba(94, 118, 231, .1)); }
        .nav a.active i { color: #9eb0ff; }
        .sidebar-bottom { margin-top: auto; }
        .account { display: flex; gap: 10px; align-items: center; padding: 14px 9px; border-top: 1px solid rgba(220,230,250,.12); }
        .account-avatar { display: grid; flex: 0 0 36px; width: 36px; height: 36px; place-items: center; color: white; border-radius: 50%; background: linear-gradient(145deg, #7690ff, #35bda9); font-weight: 800; }
        .account strong { display: block; max-width: 154px; overflow: hidden; color: #eef2ff; font-size: 12px; text-overflow: ellipsis; white-space: nowrap; }
        .account span { color: #93a2c2; font-size: 10px; }
        .logout { display: flex; justify-content: center; gap: 8px; padding: 10px; color: #f4adb8; border: 1px solid rgba(241,147,164,.18); border-radius: 10px; background: rgba(241,147,164,.06); text-decoration: none; }
        .main { width: min(1600px, calc(100% - 250px)); margin-left: 250px; padding: 29px clamp(22px, 3.5vw, 56px) 55px; }
        .topbar { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-bottom: 25px; }
        .breadcrumbs { color: var(--muted); font-size: 12px; }
        .breadcrumbs strong { color: var(--ink); font-weight: 650; }
        .topbar-actions { display: flex; align-items: center; gap: 10px; }
        .date-chip { padding: 8px 12px; color: #53617b; border: 1px solid var(--line); border-radius: 999px; background: white; font-size: 11px; }
        .hero {
            position: relative;
            display: flex;
            min-height: 230px;
            align-items: center;
            overflow: hidden;
            padding: clamp(25px, 4vw, 42px);
            color: white;
            border-radius: 22px;
            background:
                radial-gradient(circle at 82% 55%, rgba(120, 236, 214, .18), transparent 15rem),
                linear-gradient(112deg, #172b55 0%, #274078 58%, #1d777c 120%);
            box-shadow: 0 18px 38px rgba(38, 63, 117, .17);
        }
        .hero::after { position: absolute; top: -110px; right: 4%; width: 360px; height: 360px; border: 1px solid rgba(255,255,255,.18); border-radius: 50%; box-shadow: 0 0 0 34px rgba(255,255,255,.035), 0 0 0 72px rgba(255,255,255,.025); content: ""; }
        .hero-copy { position: relative; z-index: 1; max-width: 710px; }
        .hero-kicker { color: #8fe6d6; font-size: 10px; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
        .hero h1 { margin: 8px 0 8px; font-size: clamp(26px, 3.6vw, 39px); letter-spacing: -.04em; line-height: 1.13; }
        .hero p { margin: 0; color: #c6d3ed; }
        .hero-meta { display: flex; flex-wrap: wrap; gap: 9px; margin-top: 22px; }
        .hero-meta span { padding: 7px 10px; color: #e1e9fa; border: 1px solid rgba(255,255,255,.17); border-radius: 999px; background: rgba(9,24,49,.16); font-size: 11px; }
        .hero-meta i { margin-right: 6px; color: #8fe6d6; }
        .section-head { display: flex; align-items: end; justify-content: space-between; gap: 16px; margin: 30px 0 14px; }
        .section-head h2 { margin: 0; color: var(--ink); font-size: 17px; letter-spacing: -.02em; }
        .section-head p { margin: 4px 0 0; color: var(--muted); font-size: 11px; }
        .section-link { color: var(--blue); font-size: 11px; font-weight: 700; text-decoration: none; }
        .stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 13px; }
        .stat-card { position: relative; overflow: hidden; padding: 17px; border: 1px solid var(--line); border-radius: 15px; background: var(--paper); box-shadow: 0 8px 23px rgba(32,54,94,.035); }
        .stat-head { display: flex; justify-content: space-between; align-items: center; color: var(--muted); font-size: 11px; }
        .stat-icon { display: grid; width: 34px; height: 34px; place-items: center; color: var(--tone); border-radius: 11px; background: var(--tint); }
        .stat-value { display: block; margin: 12px 0 1px; color: var(--ink); font-size: 29px; font-weight: 760; letter-spacing: -.04em; }
        .stat-note { color: #929db0; font-size: 10px; }
        .grid-main { display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(280px, .75fr); gap: 15px; }
        .panel { min-width: 0; padding: 19px; border: 1px solid var(--line); border-radius: 16px; background: var(--paper); box-shadow: var(--shadow); }
        .panel-heading { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 14px; }
        .panel-heading h3 { margin: 0; color: var(--ink); font-size: 14px; }
        .panel-heading p { margin: 3px 0 0; color: var(--muted); font-size: 10px; }
        .panel-heading i { color: var(--blue); }
        .department-list { display: grid; gap: 16px; }
        .department-top { display: flex; justify-content: space-between; gap: 10px; margin-bottom: 7px; color: #394762; font-size: 11px; }
        .department-top span:last-child { color: var(--muted); font-size: 10px; }
        .department-management { margin-top: 20px; padding-top: 17px; border-top: 1px solid var(--line); }
        .department-management h4 { margin: 0 0 4px; color: var(--ink); font-size: 12px; }
        .department-management p { margin: 0 0 11px; color: var(--muted); font-size: 10px; }
        .department-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
        .department-tag { padding: 5px 8px; color: #3e5fda; border: 1px solid #dfe5ff; border-radius: 999px; background: #f6f7ff; font-size: 10px; }
        .department-add { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .department-add select { min-width: 190px; min-height: 35px; padding: 7px 9px; color: var(--ink); border: 1px solid var(--line); border-radius: 9px; background: #fff; font-size: 11px; }
        .bar-track { height: 7px; overflow: hidden; border-radius: 99px; background: #edf1f8; }
        .bar-fill { height: 100%; min-width: 4px; border-radius: inherit; background: linear-gradient(90deg, #5474ed, #24bba9); }
        .empty { padding: 20px 10px; color: var(--muted); text-align: center; font-size: 12px; }
        .actions-grid { display: grid; gap: 9px; }
        .action-card { display: flex; align-items: center; gap: 11px; padding: 12px; border: 1px solid var(--line); border-radius: 12px; background: #fbfcff; text-decoration: none; transition: transform .18s ease, border-color .18s ease; }
        .action-card:hover { transform: translateY(-2px); border-color: #b6c4ff; }
        .action-icon { display: grid; flex: 0 0 35px; width: 35px; height: 35px; place-items: center; color: var(--blue); border-radius: 10px; background: #edf1ff; }
        .action-card strong { display: block; color: #293650; font-size: 11px; }
        .action-card span { display: block; margin-top: 2px; color: var(--muted); font-size: 10px; }
        .table-panel { padding: 0; }
        .table-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 17px 19px; border-bottom: 1px solid var(--line); }
        .table-toolbar h3 { margin: 0; color: var(--ink); font-size: 14px; }
        .table-toolbar p { margin: 3px 0 0; color: var(--muted); font-size: 10px; }
        .toolbar-actions { display: flex; gap: 8px; align-items: center; }
        .search-form { display: flex; gap: 7px; }
        .search-form input { width: min(210px, 30vw); min-height: 35px; padding: 7px 10px; color: var(--ink); outline: 0; border: 1px solid var(--line); border-radius: 9px; background: #fbfcff; font-size: 11px; }
        .search-form input:focus { border-color: #94a8ff; box-shadow: 0 0 0 3px rgba(70,103,232,.1); }
        .small-button { display: inline-flex; min-height: 35px; align-items: center; justify-content: center; gap: 7px; padding: 7px 10px; color: #3e5fda; border: 1px solid #dfe5ff; border-radius: 9px; background: #f6f7ff; cursor: pointer; font-size: 10px; font-weight: 700; text-decoration: none; white-space: nowrap; }
        .small-button:hover { background: #ecefff; }
        .department-editor { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 7px; }
        .department-editor select { min-width: 130px; max-width: 185px; min-height: 30px; padding: 5px 7px; color: var(--ink); border: 1px solid var(--line); border-radius: 7px; background: #fff; font-size: 10px; }
        .department-save { min-height: 30px; padding: 5px 8px; font-size: 9px; }
        .department-message { margin: 12px 19px 0; padding: 10px 12px; border: 1px solid #bfe9df; border-radius: 9px; color: #236d61; background: #effaf7; font-size: 11px; }
        .department-message.error { border-color: #f1c7ce; color: #9c3d4d; background: #fff4f5; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 11px 15px; color: #8995aa; background: #fbfcfe; border-bottom: 1px solid var(--line); font-size: 9px; font-weight: 800; letter-spacing: .11em; text-transform: uppercase; white-space: nowrap; }
        td { padding: 12px 15px; color: #3f4d65; border-bottom: 1px solid #eff2f7; font-size: 11px; }
        tbody tr:last-child td { border-bottom: 0; }
        tbody tr:hover td { background: #fbfcff; }
        .doctor-name { color: #202c43; font-weight: 700; }
        .subtext { display: block; margin-top: 2px; color: #8995aa; font-size: 10px; }
        .pill { display: inline-flex; align-items: center; padding: 4px 8px; color: #287c72; border-radius: 999px; background: #e9f8f5; font-size: 10px; font-weight: 700; }
        .activity-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
        .mini-panel { padding: 0; }
        .mini-head { display: flex; align-items: center; justify-content: space-between; padding: 15px 16px; border-bottom: 1px solid var(--line); }
        .mini-head h3 { margin: 0; color: var(--ink); font-size: 12px; }
        .mini-head i { color: var(--blue); }
        .mini-list { display: grid; gap: 0; padding: 0 15px; }
        .mini-item { display: flex; justify-content: space-between; gap: 12px; padding: 11px 0; border-bottom: 1px solid #eff2f7; }
        .mini-item:last-child { border-bottom: 0; }
        .mini-item strong { display: block; color: #35435c; font-size: 10px; }
        .mini-item span { display: block; margin-top: 3px; color: var(--muted); font-size: 9px; }
        .mini-item time { flex: 0 0 auto; color: #8a96aa; font-size: 9px; }
        .footer { margin-top: 25px; color: #9aa5b7; text-align: center; font-size: 10px; }
        @media (max-width: 1200px) {
            .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .activity-grid { grid-template-columns: 1fr 1fr; }
            .activity-grid .mini-panel:last-child { grid-column: 1 / -1; }
        }
        @media (max-width: 900px) {
            .sidebar { position: static; width: auto; height: auto; padding: 15px 18px; }
            .brand { padding: 0 3px 13px; }
            .nav-label, .sidebar-bottom { display: none; }
            .nav { grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 4px; }
            .nav a { flex-direction: column; gap: 5px; padding: 8px 4px; text-align: center; font-size: 9px; }
            .nav a i { font-size: 13px; }
            .main { width: 100%; margin: 0; padding: 24px 18px 44px; }
        }
        @media (max-width: 640px) {
            .topbar { align-items: flex-start; }
            .grid-main, .activity-grid { grid-template-columns: 1fr; }
            .activity-grid .mini-panel:last-child { grid-column: auto; }
            .table-toolbar { align-items: flex-start; flex-direction: column; }
            .toolbar-actions, .search-form { width: 100%; }
            .search-form input { width: 100%; }
            .stats { gap: 9px; }
            .stat-card { padding: 13px; }
            .stat-value { font-size: 25px; }
            .hero { min-height: 210px; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; }
        }
        @media print {
            .sidebar, .topbar-actions, .toolbar-actions, .section-link { display: none !important; }
            .main { width: 100%; margin: 0; padding: 0; }
            body { background: white; }
            .panel, .stat-card { box-shadow: none; break-inside: avoid; }
        }
    </style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon"><i class="fa-solid fa-hospital"></i></div>
            <div><strong>CMRx</strong><small>Hospital operations</small></div>
        </div>
        <div class="nav-label">Management</div>
        <nav class="nav" aria-label="Hospital dashboard">
            <a class="active" href="#overview"><i class="fa-solid fa-chart-pie"></i>Overview</a>
            <a href="#departments"><i class="fa-solid fa-layer-group"></i>Departments</a>
            <a href="#doctors"><i class="fa-solid fa-user-doctor"></i>Doctors</a>
            <a href="#prescriptions"><i class="fa-solid fa-file-prescription"></i>Prescriptions</a>
            <a href="#clinical-activity"><i class="fa-solid fa-notes-medical"></i>Clinical activity</a>
        </nav>
        <div class="sidebar-bottom">
            <div class="account">
                <div class="account-avatar"><?php echo hospitalEscape($doctorInitials); ?></div>
                <div><strong><?php echo hospitalEscape($hospitalName); ?></strong><span>Hospital administrator</span></div>
            </div>
            <a class="logout" href="hlogout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sign out</a>
        </div>
    </aside>

    <main class="main" id="overview">
        <header class="topbar">
            <div class="breadcrumbs">CMRx <span> / </span> <strong>Hospital dashboard</strong></div>
            <div class="topbar-actions"><span class="date-chip"><i class="fa-regular fa-calendar"></i> <?php echo date('D, M j, Y'); ?></span></div>
        </header>

        <section class="hero">
            <div class="hero-copy">
                <div class="hero-kicker">Hospital command center</div>
                <h1><?php echo hospitalEscape($hospitalName); ?></h1>
                <p>One clear view of your care team, clinical activity, and recent records.</p>
                <div class="hero-meta">
                    <?php if ($hospitalAddress !== ''): ?><span><i class="fa-solid fa-location-dot"></i><?php echo hospitalEscape($hospitalAddress); ?></span><?php endif; ?>
                    <span><i class="fa-solid fa-shield-heart"></i>Secure facility overview</span>
                </div>
            </div>
        </section>

        <div class="section-head">
            <div><h2>Facility snapshot</h2><p>Metrics are calculated from the hospital's existing CMRx records</p></div>
            <button type="button" class="small-button" onclick="window.print()"><i class="fa-solid fa-print"></i> Print overview</button>
        </div>
        <section class="stats" aria-label="Hospital statistics">
            <article class="stat-card" style="--tone:#4969e8;--tint:#edf0ff">
                <div class="stat-head"><span>Doctors</span><span class="stat-icon"><i class="fa-solid fa-user-doctor"></i></span></div>
                <strong class="stat-value"><?php echo $totalDoctors; ?></strong><span class="stat-note">Registered at this facility</span>
            </article>
            <article class="stat-card" style="--tone:#15a692;--tint:#e7f8f4">
                <div class="stat-head"><span>Departments</span><span class="stat-icon"><i class="fa-solid fa-layer-group"></i></span></div>
                <strong class="stat-value"><?php echo $totalDepartments; ?></strong><span class="stat-note">Represented by the care team</span>
            </article>
            <article class="stat-card" style="--tone:#d18b24;--tint:#fff4df">
                <div class="stat-head"><span>Prescriptions · 7 days</span><span class="stat-icon"><i class="fa-solid fa-file-prescription"></i></span></div>
                <strong class="stat-value"><?php echo $weeklyPrescriptions; ?></strong><span class="stat-note">Issued by hospital doctors</span>
            </article>
            <article class="stat-card" style="--tone:#d35b76;--tint:#fff0f3">
                <div class="stat-head"><span>Visits today</span><span class="stat-icon"><i class="fa-solid fa-user-check"></i></span></div>
                <strong class="stat-value"><?php echo $todayVisits; ?></strong><span class="stat-note">Recorded in visit history</span>
            </article>
        </section>

        <div class="section-head" id="departments">
            <div><h2>Care team &amp; department overview</h2><p><?php echo $uniquePatients; ?> unique patients appear in facility visit history</p></div>
        </div>
        <div class="grid-main">
            <section class="panel">
                <div class="panel-heading">
                    <div><h3><i class="fa-solid fa-chart-column"></i> Department coverage</h3><p>Doctor and prescription totals by active department</p></div>
                </div>
                <?php if ($departments): ?>
                    <div class="department-list">
                        <?php foreach ($departments as $department): ?>
                            <?php $barWidth = $maxDepartmentDoctors > 0 ? max(5, (int)round(((int)$department['doctor_count'] / $maxDepartmentDoctors) * 100)) : 0; ?>
                            <div>
                                <div class="department-top"><strong><?php echo hospitalEscape($department['Department_Name']); ?></strong><span><?php echo (int)$department['doctor_count']; ?> doctors · <?php echo (int)$department['prescription_count']; ?> prescriptions</span></div>
                                <div class="bar-track" aria-label="<?php echo hospitalEscape($department['Department_Name']); ?> doctor coverage"><div class="bar-fill" style="width:<?php echo $barWidth; ?>%"></div></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty">Department coverage will appear when doctors are assigned to a department.</div>
                <?php endif; ?>
                <div class="department-management">
                    <h4>Departments at this hospital</h4>
                    <p>Enable catalog departments for this facility before assigning them to doctors.</p>
                    <?php if ($departmentMessage !== '' && $departmentMessageTarget === 'departments'): ?>
                        <div class="department-message <?php echo $departmentMessageType === 'error' ? 'error' : ''; ?>" role="status"><?php echo hospitalEscape($departmentMessage); ?></div>
                    <?php endif; ?>
                    <?php if ($hospitalDepartments): ?>
                        <div class="department-tags" aria-label="Departments enabled for this hospital">
                            <?php foreach ($hospitalDepartments as $hospitalDepartment): ?>
                                <span class="department-tag"><?php echo hospitalEscape($hospitalDepartment['Department_Name']); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty">No departments are enabled for this hospital yet.</div>
                    <?php endif; ?>
                    <?php if ($addableDepartments): ?>
                        <form class="department-add" method="post" action="hospital_dashboard.php#departments">
                            <input type="hidden" name="hospital_department_csrf" value="<?php echo hospitalEscape($departmentCsrf); ?>">
                            <input type="hidden" name="add_hospital_department" value="1">
                            <select name="department_id" aria-label="Choose a department to add" required>
                                <option value="">Choose a catalog department</option>
                                <?php foreach ($addableDepartments as $departmentOption): ?>
                                    <option value="<?php echo (int)$departmentOption['Department_ID']; ?>"><?php echo hospitalEscape($departmentOption['Department_Name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="small-button" type="submit"><i class="fa-solid fa-plus"></i> Add department</button>
                        </form>
                    <?php else: ?>
                        <p>All catalog departments are already enabled for this hospital.</p>
                    <?php endif; ?>
                </div>
            </section>
            <section class="panel">
                <div class="panel-heading"><div><h3><i class="fa-solid fa-bolt"></i> Quick actions</h3><p>Jump directly to the operational data you need</p></div></div>
                <div class="actions-grid">
                    <a class="action-card" href="#doctors"><span class="action-icon"><i class="fa-solid fa-users"></i></span><span><strong>Find a doctor</strong><span>Search by name or department</span></span></a>
                    <a class="action-card" href="#prescriptions"><span class="action-icon"><i class="fa-solid fa-file-medical"></i></span><span><strong>Review prescriptions</strong><span>See the latest records issued</span></span></a>
                    <a class="action-card" href="#clinical-activity"><span class="action-icon"><i class="fa-solid fa-notes-medical"></i></span><span><strong>Clinical activity</strong><span>Browse reports and vaccine records</span></span></a>
                </div>
            </section>
        </div>

        <div class="section-head" id="doctors">
            <div><h2>Hospital doctors</h2><p>Search the current roster by doctor or specialty</p></div>
        </div>
        <section class="panel table-panel" id="doctor-directory">
            <?php if ($departmentMessage !== '' && $departmentMessageTarget === 'doctor-directory'): ?>
                <div class="department-message <?php echo $departmentMessageType === 'error' ? 'error' : ''; ?>" role="status"><?php echo hospitalEscape($departmentMessage); ?></div>
            <?php endif; ?>
            <div class="table-toolbar">
                <div><h3>Doctor directory</h3><p>Showing up to 50 doctors assigned to this hospital</p></div>
                <div class="toolbar-actions">
                    <form class="search-form" method="get" action="#doctors">
                        <input type="search" name="q" value="<?php echo hospitalEscape($doctorSearch); ?>" placeholder="Name or department" aria-label="Search doctors by name or department">
                        <button class="small-button" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
                    </form>
                    <button class="small-button" type="button" id="export-doctors"><i class="fa-solid fa-download"></i> Export CSV</button>
                </div>
            </div>
            <div class="table-wrap">
                <table id="doctor-table">
                    <thead><tr><th>Doctor</th><th>Department</th><th>Certification</th><th>Prescriptions</th></tr></thead>
                    <tbody>
                    <?php if ($doctorRows->num_rows > 0): ?>
                        <?php while ($doctor = $doctorRows->fetch_assoc()): ?>
                            <?php
                            $doctorDepartmentOptions = $hospitalDepartments;
                            if (
                                $doctor['Department_ID'] !== null
                                && !in_array((int)$doctor['Department_ID'], $hospitalDepartmentIds, true)
                            ) {
                                array_unshift($doctorDepartmentOptions, [
                                    'Department_ID' => (int)$doctor['Department_ID'],
                                    'Department_Name' => $doctor['Department_Name'] ?: 'Previously assigned department'
                                ]);
                            }
                            ?>
                            <tr>
                                <td><span class="doctor-name"><?php echo hospitalEscape($doctor['Doc_Name'] ?: 'Name not provided'); ?></span><span class="subtext">Doctor ID <?php echo hospitalEscape($doctor['DOC_ID']); ?></span></td>
                                <td data-export-value="<?php echo hospitalEscape($doctor['Department_Name'] ?: 'Unassigned'); ?>">
                                    <span><?php echo hospitalEscape($doctor['Department_Name'] ?: 'Unassigned'); ?></span>
                                    <form class="department-editor" method="post" action="hospital_dashboard.php<?php echo $doctorSearch !== '' ? '?q=' . rawurlencode($doctorSearch) : ''; ?>#doctor-directory">
                                        <input type="hidden" name="hospital_department_csrf" value="<?php echo hospitalEscape($departmentCsrf); ?>">
                                        <input type="hidden" name="doctor_id" value="<?php echo (int)$doctor['DOC_ID']; ?>">
                                        <input type="hidden" name="update_doctor_department" value="1">
                                        <select name="department_id" aria-label="Department for <?php echo hospitalEscape($doctor['Doc_Name'] ?: 'doctor'); ?>">
                                            <option value="">Unassigned</option>
                                            <?php foreach ($doctorDepartmentOptions as $departmentOption): ?>
                                                <option value="<?php echo (int)$departmentOption['Department_ID']; ?>"<?php echo (int)$doctor['Department_ID'] === (int)$departmentOption['Department_ID'] ? ' selected' : ''; ?>><?php echo hospitalEscape($departmentOption['Department_Name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="small-button department-save" type="submit">Save</button>
                                    </form>
                                    <?php if (!$hospitalDepartments): ?><span class="subtext">Add a department above to assign it here.</span><?php endif; ?>
                                </td>
                                <td><?php echo hospitalEscape($doctor['Certification'] ?: 'Not provided'); ?></td>
                                <td><span class="pill"><?php echo (int)$doctor['prescription_count']; ?> total</span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td class="empty" colspan="4"><?php echo $doctorSearch !== '' ? 'No doctors matched that search.' : 'No doctors are registered at this hospital yet.'; ?></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="section-head" id="prescriptions">
            <div><h2>Latest prescriptions</h2><p>Recent prescription records issued by this hospital's doctors</p></div>
        </div>
        <section class="panel table-panel">
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Prescription</th><th>Patient</th><th>Medical ID</th><th>Doctor</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php if ($prescriptionRows->num_rows > 0): ?>
                        <?php while ($prescription = $prescriptionRows->fetch_assoc()): ?>
                            <tr>
                                <td><span class="pill">RX-<?php echo hospitalEscape($prescription['Prescription_Serial']); ?></span></td>
                                <td><?php echo hospitalEscape($prescription['Patient_Name'] ?: 'Patient'); ?></td>
                                <td><?php echo hospitalEscape($prescription['MED_ID']); ?></td>
                                <td><?php echo hospitalEscape($prescription['Doc_Name'] ?: 'Doctor'); ?></td>
                                <td><?php echo hospitalEscape($prescription['Prescription_Date']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td class="empty" colspan="5">No prescription records are available for this hospital.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="section-head" id="clinical-activity">
            <div><h2>Clinical activity</h2><p><?php echo $diagnosisReports; ?> diagnosis reports and <?php echo $vaccineRecords; ?> vaccine records are associated with this facility</p></div>
        </div>
        <div class="activity-grid">
            <section class="panel mini-panel">
                <div class="mini-head"><h3>Diagnostic reports</h3><i class="fa-solid fa-file-waveform"></i></div>
                <div class="mini-list">
                    <?php if ($reportRows->num_rows > 0): ?>
                        <?php while ($report = $reportRows->fetch_assoc()): ?>
                            <div class="mini-item"><div><strong><?php echo hospitalEscape($report['Report_Name'] ?: 'Clinical report'); ?></strong><span><?php echo hospitalEscape($report['Patient_Name'] ?: 'Patient'); ?> · MED_ID <?php echo hospitalEscape($report['MED_ID']); ?></span></div><time><?php echo hospitalEscape($report['Report_Date']); ?></time></div>
                        <?php endwhile; ?>
                    <?php else: ?><div class="empty">No reports recorded for this facility.</div><?php endif; ?>
                </div>
            </section>
            <section class="panel mini-panel">
                <div class="mini-head"><h3>Vaccination records</h3><i class="fa-solid fa-syringe"></i></div>
                <div class="mini-list">
                    <?php if ($vaccineRows->num_rows > 0): ?>
                        <?php while ($vaccine = $vaccineRows->fetch_assoc()): ?>
                            <div class="mini-item"><div><strong><?php echo hospitalEscape($vaccine['Vaccine_Name'] ?: 'Vaccine'); ?> · Dose <?php echo hospitalEscape($vaccine['Dose_No']); ?></strong><span><?php echo hospitalEscape($vaccine['Patient_Name'] ?: 'Patient'); ?> · MED_ID <?php echo hospitalEscape($vaccine['MED_ID']); ?></span></div><time><?php echo hospitalEscape($vaccine['Vacc_Admin_Date']); ?></time></div>
                        <?php endwhile; ?>
                    <?php else: ?><div class="empty">No vaccine records recorded for this facility.</div><?php endif; ?>
                </div>
            </section>
            <section class="panel mini-panel">
                <div class="mini-head"><h3>Operational notes</h3><i class="fa-solid fa-circle-info"></i></div>
                <div class="mini-list">
                    <div class="mini-item"><div><strong>Visit records</strong><span>Today's total is based on the existing visited_doctor table.</span></div><time><?php echo $todayVisits; ?> today</time></div>
                    <div class="mini-item"><div><strong>Appointment scheduling</strong><span>No appointments table is present in the current database.</span></div><time>Not enabled</time></div>
                    <div class="mini-item"><div><strong>Facility records</strong><span>Reports and vaccinations are filtered to this hospital's serial.</span></div><time>Live</time></div>
                </div>
            </section>
        </div>
        <div class="footer">CMRx Hospital Portal · Data shown is scoped to the signed-in facility.</div>
    </main>
</div>
<script>
    document.getElementById('export-doctors').addEventListener('click', function () {
        const table = document.getElementById('doctor-table');
        const rows = Array.from(table.querySelectorAll('tr')).map(function (row) {
            return Array.from(row.querySelectorAll('th, td')).map(function (cell) {
                const rawValue = cell.hasAttribute('data-export-value') ? cell.getAttribute('data-export-value') : cell.innerText;
                const value = rawValue.replace(/\s+/g, ' ').trim();
                const safeValue = /^[=+\-@]/.test(value) ? "'" + value : value;
                return '"' + safeValue.replace(/"/g, '""') + '"';
            }).join(',');
        });
        const file = new Blob([rows.join('\r\n')], { type: 'text/csv;charset=utf-8' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(file);
        link.download = 'hospital-doctor-roster.csv';
        link.click();
        setTimeout(function () { URL.revokeObjectURL(link.href); }, 1000);
    });
</script>
</body>
</html>
