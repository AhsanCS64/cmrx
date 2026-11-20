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

// ============================================================================
// DATABASE-DEPENDENT FEATURES
// ============================================================================

// --- Doctor profile update ---
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

// --- Visit logger: record a patient visit for today ---
$visitMessage = $visitMessageType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_visit'])) {
    $visitMedId = isset($_POST['visit_med_id']) ? (int)$_POST['visit_med_id'] : 0;
    $visitNotes = isset($_POST['visit_notes']) && is_string($_POST['visit_notes']) ? trim($_POST['visit_notes']) : '';

    if ($visitMedId <= 0) {
        $visitMessage = "Enter a valid patient MED_ID.";
        $visitMessageType = 'error';
    } else {
        $verify = $conn->prepare("SELECT MED_ID FROM patient_details WHERE MED_ID = ?");
        $verify->bind_param("i", $visitMedId);
        $verify->execute();
        $exists = $verify->get_result()->num_rows > 0;
        $verify->close();

        if (!$exists) {
            $visitMessage = "No patient found with that MED_ID.";
            $visitMessageType = 'error';
        } else {
            $stmt = $conn->prepare("INSERT INTO visited_doctor (MED_ID, DOC_ID, Visited_Date) VALUES (?, ?, CURDATE())");
            $stmt->bind_param("ii", $visitMedId, $doctorId);
            if ($stmt->execute()) {
                $visitMessage = "Visit logged for MED_ID {$visitMedId}.";
                $visitMessageType = 'success';
            } else {
                $visitMessage = "Could not log visit. Please try again.";
                $visitMessageType = 'error';
            }
            $stmt->close();
        }
    }
}

// Fetch today's visits for this doctor
$todayVisits = [];
$stmt = $conn->prepare(
    "SELECT v.Visited_Date, p.Name AS Patient_Name, v.MED_ID
     FROM visited_doctor v
     LEFT JOIN patient_details p ON p.MED_ID = v.MED_ID
     WHERE v.DOC_ID = ? AND v.Visited_Date = CURDATE()
     ORDER BY v.MED_ID DESC"
);
$stmt->bind_param("i", $doctorId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $todayVisits[] = $row;
}
$stmt->close();

// --- Diagnostic report upload & listing ---
$reportMessage = $reportMessageType = '';
$reportUploadDir = "uploads/reports/";
if (!is_dir($reportUploadDir)) {
    @mkdir($reportUploadDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_report'])) {
    $reportMedId = isset($_POST['report_med_id']) ? (int)$_POST['report_med_id'] : 0;
    $reportName = isset($_POST['report_name']) && is_string($_POST['report_name']) ? trim($_POST['report_name']) : '';
    $reportDate = isset($_POST['report_date']) && is_string($_POST['report_date']) ? trim($_POST['report_date']) : date('Y-m-d');

    if ($reportMedId <= 0 || $reportName === '') {
        $reportMessage = "Enter a valid MED_ID and report name.";
        $reportMessageType = 'error';
    } elseif (!isset($_FILES['report_file']) || $_FILES['report_file']['error'] !== 0) {
        $reportMessage = "Please upload a report file.";
        $reportMessageType = 'error';
    } else {
        $file = $_FILES['report_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        if (!in_array($ext, $allowed, true)) {
            $reportMessage = "Only PDF, JPG, PNG, DOC, and DOCX files are allowed.";
            $reportMessageType = 'error';
        } else {
            $verify = $conn->prepare("SELECT MED_ID FROM patient_details WHERE MED_ID = ?");
            $verify->bind_param("i", $reportMedId);
            $verify->execute();
            $exists = $verify->get_result()->num_rows > 0;
            $verify->close();

            if (!$exists) {
                $reportMessage = "No patient found with that MED_ID.";
                $reportMessageType = 'error';
            } else {
                $targetFile = $reportUploadDir . uniqid('rep_', true) . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                    $stmt = $conn->prepare(
                        "INSERT INTO diagnosis (MED_ID, Report_Name, Report_Date, Rep_File_Path, Hospital_Serial)
                         VALUES (?, ?, ?, ?, (SELECT Hospital_Serial FROM doctor_details WHERE DOC_ID = ?))"
                    );
                    $stmt->bind_param("isssi", $reportMedId, $reportName, $reportDate, $targetFile, $doctorId);
                    if ($stmt->execute()) {
                        $reportMessage = "Diagnostic report saved.";
                        $reportMessageType = 'success';
                    } else {
                        $reportMessage = "Could not save report record.";
                        $reportMessageType = 'error';
                        @unlink($targetFile);
                    }
                    $stmt->close();
                } else {
                    $reportMessage = "File upload failed.";
                    $reportMessageType = 'error';
                }
            }
        }
    }
}

// Fetch recent reports across this doctor's patients
$recentReports = [];
$stmt = $conn->prepare(
    "SELECT d.Report_Name, d.Report_Date, d.Rep_File_Path, p.Name AS Patient_Name, d.MED_ID
     FROM diagnosis d
     LEFT JOIN patient_details p ON p.MED_ID = d.MED_ID
     WHERE d.Hospital_Serial = (SELECT Hospital_Serial FROM doctor_details WHERE DOC_ID = ?)
     ORDER BY d.Report_Date DESC, d.Report_Name DESC
     LIMIT 10"
);
$stmt->bind_param("i", $doctorId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recentReports[] = $row;
}
$stmt->close();

// --- Disease history management ---
$conditionMessage = $conditionMessageType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_condition'])) {
    $condMedId = isset($_POST['cond_med_id']) ? (int)$_POST['cond_med_id'] : 0;
    $condName = isset($_POST['cond_name']) && is_string($_POST['cond_name']) ? trim($_POST['cond_name']) : '';
    $condStatus = isset($_POST['cond_status']) && is_string($_POST['cond_status']) ? trim($_POST['cond_status']) : '';

    $allowed = ['Severe', 'Mild', 'Very Mild', 'Cured', 'Seasonal'];
    if ($condMedId <= 0 || $condName === '' || !in_array($condStatus, $allowed, true)) {
        $conditionMessage = "Enter a valid MED_ID, condition name, and select a status.";
        $conditionMessageType = 'error';
    } else {
        $verify = $conn->prepare("SELECT MED_ID FROM patient_details WHERE MED_ID = ?");
        $verify->bind_param("i", $condMedId);
        $verify->execute();
        $exists = $verify->get_result()->num_rows > 0;
        $verify->close();

        if (!$exists) {
            $conditionMessage = "No patient found with that MED_ID.";
            $conditionMessageType = 'error';
        } else {
            $stmt = $conn->prepare("INSERT INTO disease_history (MED_ID, Disease_Name, Present_Condition) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $condMedId, $condName, $condStatus);
            if ($stmt->execute()) {
                $conditionMessage = "Condition recorded.";
                $conditionMessageType = 'success';
            } else {
                $conditionMessage = "Could not record condition.";
                $conditionMessageType = 'error';
            }
            $stmt->close();
        }
    }
}

// ============================================================================
// NON-DATABASE (SESSION-ONLY) WELLNESS FEATURES
// All data lives in $_SESSION['doc_wellness']; zero database writes.
// ============================================================================

if (empty($_SESSION['doc_wellness']) || !is_array($_SESSION['doc_wellness'])) {
    $_SESSION['doc_wellness'] = [
        'theme' => 'dark',
        'accent' => 'blue',
        'notifications' => false,
        'bmi' => [],
        'bp' => [],
        'hr' => [],
        'temp' => [],
        'glucose' => [],
        'steps' => [],
        'water' => [],
        'water_goal' => 8,
        'medications' => [],
        'symptoms' => [],
        'moods' => [],
        'emergency' => [],
        'activity' => [],
    ];
}
$W = &$_SESSION['doc_wellness'];
$wellness_theme = $W['theme'] ?? 'dark';
$wellness_notifications = $W['notifications'] ?? false;
$noteMessage = $noteMessageType = '';

if (empty($_SESSION['doc_notes']) || !is_array($_SESSION['doc_notes'])) {
    $_SESSION['doc_notes'] = [];
}
$docNotes = &$_SESSION['doc_notes'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_note'])) {
    $noteText = isset($_POST['note_text']) && is_string($_POST['note_text']) ? trim($_POST['note_text']) : '';
    if ($noteText === '') {
        $noteMessage = "Note cannot be empty.";
        $noteMessageType = 'error';
    } else {
        $docNotes[] = ['text' => $noteText, 'time' => date('Y-m-d H:i')];
        if (count($docNotes) > 50) {
            array_shift($docNotes);
        }
        $noteMessage = "Clinical note saved.";
        $noteMessageType = 'success';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_notes'])) {
    $_SESSION['doc_notes'] = [];
    $docNotes = &$_SESSION['doc_notes'];
    $noteMessage = "All notes cleared.";
    $noteMessageType = 'success';
}

function doc_w_escape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function doc_add_activity(&$W, $text)
{
    $W['activity'][] = [
        'text' => $text,
        'time' => date('Y-m-d H:i'),
    ];
    if (count($W['activity']) > 60) {
        array_shift($W['activity']);
    }
}

$health_tips = [
    "Drink at least 8 glasses of water daily to stay hydrated.",
    "Aim for 7-9 hours of quality sleep each night.",
    "Take a 10-minute walk after meals to aid digestion.",
    "Stretch for 5 minutes every hour if you sit for long periods.",
    "Include a variety of colorful fruits and vegetables in your diet.",
    "Practice deep breathing or meditation to reduce stress.",
    "Limit screen time before bed for better sleep quality.",
    "Wash your hands regularly to prevent the spread of germs.",
    "Schedule regular health check-ups even when feeling fine.",
    "Protect your skin with sunscreen, even on cloudy days.",
    "Walk or cycle instead of driving short distances when possible.",
    "Keep your emergency contact list up to date.",
];
$health_tip = $health_tips[array_rand($health_tips)];
$w_message = $w_message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // BMI
    if (isset($_POST['w_add_bmi'])) {
        $weight = (float)($_POST['weight'] ?? 0);
        $height = (float)($_POST['height_cm'] ?? 0);
        if ($weight > 0 && $height > 0) {
            $bmi = round($weight / pow($height / 100, 2), 1);
            $cat = $bmi < 18.5 ? 'Underweight' : ($bmi < 25 ? 'Normal' : ($bmi < 30 ? 'Overweight' : 'Obese'));
            $W['bmi'][] = ['date' => date('Y-m-d H:i'), 'bmi' => $bmi, 'cat' => $cat];
            doc_add_activity($W, "Logged BMI: $bmi ($cat)");
            $w_message = "BMI recorded: $bmi ($cat).";
            $w_message_type = 'success';
        } else {
            $w_message = "Enter valid weight and height.";
            $w_message_type = 'error';
        }
    }
    // Blood pressure
    elseif (isset($_POST['w_add_bp'])) {
        $sys = (int)($_POST['bp_sys'] ?? 0);
        $dia = (int)($_POST['bp_dia'] ?? 0);
        if ($sys > 0 && $dia > 0) {
            $cat = $sys < 120 && $dia < 80 ? 'Normal' : (($sys < 130 && $dia < 80) ? 'Elevated' : (($sys < 140 || $dia < 90) ? 'High BP Stage 1' : 'High BP Stage 2'));
            $W['bp'][] = ['date' => date('Y-m-d H:i'), 'sys' => $sys, 'dia' => $dia, 'cat' => $cat];
            doc_add_activity($W, "Logged BP: $sys/$dia ($cat)");
            $w_message = "Blood pressure recorded: $sys/$dia ($cat).";
            $w_message_type = 'success';
        } else {
            $w_message = "Enter valid blood pressure values.";
            $w_message_type = 'error';
        }
    }
    // Heart rate
    elseif (isset($_POST['w_add_hr'])) {
        $bpm = (int)($_POST['hr_bpm'] ?? 0);
        if ($bpm > 0) {
            $zone = $bpm < 60 ? 'Low (Bradycardia)' : ($bpm <= 100 ? 'Normal' : 'High (Tachycardia)');
            $W['hr'][] = ['date' => date('Y-m-d H:i'), 'bpm' => $bpm, 'zone' => $zone];
            doc_add_activity($W, "Logged heart rate: $bpm BPM ($zone)");
            $w_message = "Heart rate recorded: $bpm BPM ($zone).";
            $w_message_type = 'success';
        } else {
            $w_message = "Enter a valid heart rate.";
            $w_message_type = 'error';
        }
    }
    // Temperature
    elseif (isset($_POST['w_add_temp'])) {
        $t = (float)($_POST['temp_val'] ?? 0);
        $unit = trim($_POST['temp_unit'] ?? 'c');
        if ($t > 0) {
            $W['temp'][] = ['date' => date('Y-m-d H:i'), 'val' => $t, 'unit' => $unit];
            doc_add_activity($W, "Logged temperature: $t°$unit");
            $w_message = "Temperature recorded.";
            $w_message_type = 'success';
        } else {
            $w_message = "Enter a valid temperature.";
            $w_message_type = 'error';
        }
    }
    // Glucose
    elseif (isset($_POST['w_add_glucose'])) {
        $g = (float)($_POST['glucose_val'] ?? 0);
        $when = trim($_POST['glucose_when'] ?? '');
        if ($g > 0) {
            $W['glucose'][] = ['date' => date('Y-m-d H:i'), 'val' => $g, 'when' => $when];
            doc_add_activity($W, "Logged glucose: $g mg/dL ($when)");
            $w_message = "Glucose recorded.";
            $w_message_type = 'success';
        } else {
            $w_message = "Enter a valid glucose value.";
            $w_message_type = 'error';
        }
    }
    // Water intake
    elseif (isset($_POST['w_add_water'])) {
        $today = date('Y-m-d');
        $glasses = (int)($_POST['water_glasses'] ?? 0);
        if ($glasses > 0) {
            $W['water'][$today] = (int)($W['water'][$today] ?? 0) + $glasses;
            doc_add_activity($W, "Drank $glasses glass(es) of water");
            $w_message = "Added $glasses glass(es) of water.";
            $w_message_type = 'success';
        } else {
            $w_message = "Enter a positive number of glasses.";
            $w_message_type = 'error';
        }
    }
    // Steps
    elseif (isset($_POST['w_add_steps'])) {
        $today = date('Y-m-d');
        $s = (int)($_POST['steps_val'] ?? 0);
        if ($s > 0) {
            $W['steps'][$today] = (int)($W['steps'][$today] ?? 0) + $s;
            doc_add_activity($W, "Added $s step(s)");
            $w_message = "$s step(s) added.";
            $w_message_type = 'success';
        } else {
            $w_message = "Enter a positive step count.";
            $w_message_type = 'error';
        }
    }
    // Medication add
    elseif (isset($_POST['w_add_med'])) {
        $mname = trim($_POST['med_name'] ?? '');
        $mtime = trim($_POST['med_time'] ?? '');
        $mdose = trim($_POST['med_dose'] ?? '');
        if ($mname !== '' && $mtime !== '' && $mdose !== '') {
            $W['medications'][] = ['id' => uniqid(), 'name' => $mname, 'time' => $mtime, 'dose' => $mdose, 'done' => false];
            doc_add_activity($W, "Added medication reminder: $mname");
            $w_message = "Medication reminder added.";
            $w_message_type = 'success';
        } else {
            $w_message = "Fill all medication fields.";
            $w_message_type = 'error';
        }
    }
    // Medication toggle done
    elseif (isset($_POST['w_med_done'])) {
        $id = $_POST['w_med_done'];
        foreach ($W['medications'] as &$m) {
            if ($m['id'] === $id) {
                $m['done'] = !$m['done'];
                break;
            }
        }
        unset($m);
    }
    // Medication delete
    elseif (isset($_POST['w_med_delete'])) {
        $id = $_POST['w_med_delete'];
        $W['medications'] = array_values(array_filter($W['medications'], fn($m) => $m['id'] !== $id));
        doc_add_activity($W, "Removed a medication reminder");
    }
    // Symptom add
    elseif (isset($_POST['w_add_symptom'])) {
        $sname = trim($_POST['sym_name'] ?? '');
        $sev = trim($_POST['sym_severity'] ?? '');
        $note = trim($_POST['sym_note'] ?? '');
        if ($sname !== '' && $sev !== '') {
            $W['symptoms'][] = ['date' => date('Y-m-d H:i'), 'name' => $sname, 'sev' => $sev, 'note' => $note];
            doc_add_activity($W, "Logged symptom: $sname ($sev)");
            $w_message = "Symptom logged.";
            $w_message_type = 'success';
        } else {
            $w_message = "Enter symptom name and severity.";
            $w_message_type = 'error';
        }
    }
    // Symptom delete
    elseif (isset($_POST['w_sym_delete'])) {
        $idx = (int)($_POST['w_sym_delete']);
        if (isset($W['symptoms'][$idx])) {
            unset($W['symptoms'][$idx]);
            $W['symptoms'] = array_values($W['symptoms']);
            doc_add_activity($W, "Removed a symptom entry");
        }
    }
    // Mood add
    elseif (isset($_POST['w_add_mood'])) {
        $mood = trim($_POST['mood'] ?? '');
        $mnote = trim($_POST['mood_note'] ?? '');
        if ($mood !== '') {
            $W['moods'][] = ['date' => date('Y-m-d H:i'), 'mood' => $mood, 'note' => $mnote];
            doc_add_activity($W, "Mood: $mood");
            $w_message = "Mood recorded.";
            $w_message_type = 'success';
        } else {
            $w_message = "Select a mood.";
            $w_message_type = 'error';
        }
    }
    // Mood delete
    elseif (isset($_POST['w_mood_delete'])) {
        $idx = (int)($_POST['w_mood_delete']);
        if (isset($W['moods'][$idx])) {
            unset($W['moods'][$idx]);
            $W['moods'] = array_values($W['moods']);
            doc_add_activity($W, "Removed a mood entry");
        }
    }
    // Emergency contact add
    elseif (isset($_POST['w_add_emergency'])) {
        $ename = trim($_POST['em_name'] ?? '');
        $ephone = trim($_POST['em_phone'] ?? '');
        $erel = trim($_POST['em_relation'] ?? '');
        if ($ename !== '' && $ephone !== '') {
            $W['emergency'][] = ['id' => uniqid(), 'name' => $ename, 'phone' => $ephone, 'relation' => $erel];
            doc_add_activity($W, "Added emergency contact: $ename");
            $w_message = "Emergency contact added.";
            $w_message_type = 'success';
        } else {
            $w_message = "Enter contact name and phone.";
            $w_message_type = 'error';
        }
    }
    // Emergency contact delete
    elseif (isset($_POST['w_em_delete'])) {
        $id = $_POST['w_em_delete'];
        $W['emergency'] = array_values(array_filter($W['emergency'], fn($e) => $e['id'] !== $id));
        doc_add_activity($W, "Removed an emergency contact");
    }
    // Theme toggle
    elseif (isset($_POST['w_toggle_theme'])) {
        $W['theme'] = ($W['theme'] === 'light') ? 'dark' : 'light';
        $wellness_theme = $W['theme'];
        $w_message = "Switched to " . ucfirst($wellness_theme) . " mode.";
        $w_message_type = 'success';
    }
    // Notifications toggle
    elseif (isset($_POST['w_toggle_notifications'])) {
        $W['notifications'] = !$W['notifications'];
        $wellness_notifications = $W['notifications'];
        $w_message = "Reminder notifications " . ($wellness_notifications ? 'enabled' : 'disabled') . ".";
        $w_message_type = 'success';
    }
}

// Derived wellness stats
$bmi_latest = !empty($W['bmi']) ? end($W['bmi']) : null;
$bp_latest = !empty($W['bp']) ? end($W['bp']) : null;
$hr_latest = !empty($W['hr']) ? end($W['hr']) : null;
$temp_latest = !empty($W['temp']) ? end($W['temp']) : null;
$glucose_latest = !empty($W['glucose']) ? end($W['glucose']) : null;
$today = date('Y-m-d');
$water_today = (int)($W['water'][$today] ?? 0);
$water_goal = (int)($W['water_goal'] ?? 8);
$water_pct = $water_goal > 0 ? min(100, round(($water_today / $water_goal) * 100)) : 0;
$steps_today = (int)($W['steps'][$today] ?? 0);
$active_meds = array_values(array_filter($W['medications'], fn($m) => !$m['done']));
$mood_latest = !empty($W['moods']) ? end($W['moods']) : null;

$health_score = 50;
if ($bmi_latest) { $health_score += ($bmi_latest['bmi'] >= 18.5 && $bmi_latest['bmi'] <= 24.9) ? 10 : -5; }
if ($bp_latest) { $health_score += ($bp_latest['sys'] < 130 && $bp_latest['dia'] < 90) ? 10 : -10; }
if ($hr_latest) { $health_score += ($hr_latest['bpm'] >= 60 && $hr_latest['bpm'] <= 100) ? 10 : -5; }
if ($water_today >= $water_goal) { $health_score += 10; } elseif ($water_today > 0) { $health_score += 5; }
$health_score = max(0, min(100, $health_score));

// ============================================================================
// FETCH DOCTOR / DASHBOARD DATA
// ============================================================================

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
$todayVisitsCount = (int)$stmt->get_result()->fetch_assoc()['total_visits'];
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

// Patient search (existing)
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
<html lang="en" data-theme="<?php echo doc_w_escape($wellness_theme); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#10172b">
    <title>Doctor workspace | CMRx</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/ui.css">
    <style>
        :root {
            color-scheme: dark;
            --page: #0b1020;
            --panel: rgba(20, 29, 53, .9);
            --panel-strong: #17213a;
            --line: rgba(178, 196, 233, .14);
            --text: #f4f7ff;
            --muted: #9aa8c3;
            --accent: #8b9cff;
            --teal: #4ed8c0;
            --rose: #ff8296;
            --amber: #f5a623;
            --shadow: 0 24px 70px rgba(0, 0, 0, .28);
            --radius: 16px;
        }
        [data-theme="light"] {
            color-scheme: light;
            --page: #f4f7fb;
            --panel: rgba(255, 255, 255, .72);
            --panel-strong: #ffffff;
            --line: rgba(178, 196, 233, .22);
            --text: #0f172a;
            --muted: #475569;
            --shadow: 0 24px 70px rgba(0, 0, 0, .07);
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            min-height: 100vh;
            color: var(--text);
            background:
                radial-gradient(ellipse at 82% 4%, rgba(90, 105, 230, .18), transparent 34rem),
                radial-gradient(ellipse at 14% 82%, rgba(43, 191, 170, .08), transparent 28rem),
                var(--page);
            font: 15px/1.55 Inter, "Segoe UI", system-ui, sans-serif;
        }
        [data-theme="light"] body {
            background: var(--page);
            color: var(--text);
        }
        a { color: inherit; text-decoration: none; }
        button, input, select, textarea { font: inherit; }
        .layout { min-height: 100vh; }
        /* ---------- Topbar ---------- */
        .topbar {
            position: sticky; top: 0; z-index: 40;
            height: 64px;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 18px;
            border-bottom: 1px solid var(--line);
            background: var(--panel);
            backdrop-filter: blur(16px);
            color: var(--text);
        }
        .topbar-left { display: flex; align-items: center; gap: 14px; }
        .topbar-center { display: flex; align-items: center; gap: 12px; flex: 1; max-width: 460px; }
        .topbar-right { display: flex; align-items: center; gap: 8px; }
        .brand { display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 1.15rem; letter-spacing: .02em; }
        .brand i { color: var(--teal); filter: drop-shadow(0 0 1px rgba(78,216,192,.4)); }
        .date-chip {
            padding: 7px 12px; color: #c5cee2; border: 1px solid var(--line); border-radius: 999px;
            background: rgba(255,255,255,.04); font-size: 12px;
        }
        [data-theme="light"] .date-chip { color: #475569; border-color: rgba(71,85,105,.35); background: rgba(0,0,0,.04); }
        .nav-search {
            position: relative; width: 100%;
        }
        .nav-search input {
            width: 100%; min-height: 40px; padding: 0 1rem 0 2.6rem;
            border-radius: 10px; border: 1px solid var(--line); background: rgba(8,14,28,.5);
            color: var(--text); font-size: .9rem;
        }
        [data-theme="light"] .nav-search input { background: #fff; color: var(--text); border-color: var(--line); }
        .nav-search i { position: absolute; left: .9rem; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: .95rem; }
        .topbar-btn {
            display: flex; align-items: center; justify-content: center;
            width: 40px; height: 40px; border-radius: 10px;
            border: 1px solid var(--line); background: rgba(8,14,28,.45); color: var(--text);
            font-size: 1.1rem; cursor: pointer; transition: .2s ease;
        }
        [data-theme="light"] .topbar-btn { background: #fff; color: var(--text); border-color: var(--line); }
        .topbar-btn:hover { border-color: var(--accent); background: rgba(139,156,255,.1); }
        [data-theme="light"] .topbar-btn:hover { border-color: var(--accent); background: color-mix(in srgb, var(--accent) 8%, transparent); }
        .topbar-btn.active { background: var(--accent); color: #10172b; border-color: var(--accent); }
        .user-chip {
            display: flex; align-items: center; gap: 8px;
            padding: 6px 12px; border-radius: 999px; border: 1px solid var(--line);
            background: rgba(8,14,28,.45); font-size: .9rem; font-weight: 700;
        }
        [data-theme="light"] .user-chip { background: #fff; color: var(--text); border-color: var(--line); }
        .user-chip .avatar {
            width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #a8b5ff, #56d9c4);
            color: #11182a; display: grid; place-items: center; font-weight: 800;
        }
        .notif-count {
            display: grid; place-items: center; width: 20px; height: 20px; border-radius: 50%;
            background: var(--rose); color: #10172b; font-size: 10px; font-weight: 800;
        }
        /* ---------- Sidebar ---------- */
        .sidebar {
            grid-area: sidebar;
            position: sticky; top: 0; height: 100vh; width: 260px;
            border-right: 1px solid var(--line);
            background: var(--panel); color: var(--text);
            display: flex; flex-direction: column; z-index: 30; overflow-y: auto;
            transition: transform 220ms ease;
        }
        [data-theme="light"] .sidebar { background: #fff; color: var(--text); border-right-color: var(--line); }
        .sidebar.collapsed { width: 72px; }
        .sidebar-header {
            padding: 1rem 1.1rem; border-bottom: 1px solid var(--line);
            display: flex; align-items: center; gap: .7rem;
        }
        .sidebar-header i { color: var(--teal); font-size: 1.15rem; }
        .sidebar-header h2 { font-size: .95rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
        .sidebar-profile {
            padding: .85rem 1.1rem; border-bottom: 1px solid var(--line); text-align: center;
        }
        .avatar-big {
            width: 44px; height: 44px; border-radius: 50%; margin: 0 auto .5rem;
            background: linear-gradient(135deg, #a8b5ff, #56d9c4);
            color: #11182a; display: grid; place-items: center; font-weight: 800; font-size: 1.1rem;
        }
        .sidebar-profile .name { font-weight: 700; font-size: .9rem; display: block; }
        .sidebar-profile .sub { font-size: .72rem; color: var(--muted); display: block; margin-top: .1rem; }
        .nav-list { list-style: none; padding: .9rem 0; display: grid; gap: .2rem; }
        .nav-item { margin: 0; }
        .nav-link {
            display: flex; align-items: center; gap: .85rem;
            padding: .72rem 1.1rem; border-radius: 10px; color: #a9b4cb;
            font-size: .88rem; font-weight: 600; transition: .2s ease; border: 1px solid transparent;
        }
        [data-theme="light"] .nav-link { color: #475569; }
        .nav-link .nav-icon { font-size: 1.05rem; width: 20px; text-align: center; color: #8594b5; }
        [data-theme="light"] .nav-link .nav-icon { color: #64748b; }
        .nav-link:hover, .nav-link.active {
            color: #f8f9ff; border-color: rgba(142,157,255,.18);
            background: linear-gradient(100deg, rgba(125,141,255,.17), rgba(125,141,255,.04));
        }
        [data-theme="light"] .nav-link:hover, [data-theme="light"] .nav-link.active {
            color: var(--text); background: color-mix(in srgb, var(--accent) 8%, var(--panel-strong));
            border-color: rgba(142,157,255,.25);
        }
        .nav-link.active i { color: var(--accent); }
        .nav-divider { height: 1px; background: var(--line); margin: .6rem 1.1rem; opacity: .6; }
        .nav-group-label { padding: .55rem 1.1rem .25rem; font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); }
        .logout-item { margin-top: auto; }
        .nav-link.logout-link { color: #ffadb9; border-color: transparent; }
        .nav-link.logout-link:hover { background: rgba(255,130,150,.08); color: #ffb4c0; }
        .nav-link.logout-link .nav-icon { color: #ff8296; }
        /* ---------- Main ---------- */
        .content-main {
            grid-area: main;
            padding: clamp(22px, 4vw, 64px) clamp(22px, 4vw, 48px) 60px;
            overflow-y: auto;
            min-width: 0;
        }
        /* ---------- Components ---------- */
        .topline { display: flex; justify-content: space-between; align-items: center; gap: 18px; margin-bottom: 28px; }
        .eyebrow { color: var(--teal); font-size: 11px; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
        .date-chip-inline { padding: 8px 12px; color: #c5cee2; border: 1px solid var(--line); border-radius: 999px; background: rgba(255,255,255,.035); font-size: 12px; }
        [data-theme="light"] .date-chip-inline { color: #475569; border-color: rgba(71,85,105,.35); background: rgba(0,0,0,.04); }
        .hero {
            position: relative;
            overflow: hidden;
            padding: clamp(24px, 4vw, 44px);
            border: 1px solid rgba(175, 190, 255, .2);
            border-radius: 24px;
            background: linear-gradient(116deg, rgba(40, 54, 100, .9), rgba(24, 37, 68, .68) 56%, rgba(24, 73, 82, .58));
            box-shadow: var(--shadow);
        }
        [data-theme="light"] .hero {
            background: linear-gradient(116deg, #f0f4ff, #e8eefd 56%, #e3f2f1);
            border-color: rgba(175, 190, 255, .35);
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
        [data-theme="light"] .hero p { color: #475569; }
        .hero-meta { display: flex; flex-wrap: wrap; gap: 9px; margin-top: 24px; }
        .hero-meta span { padding: 7px 11px; color: #d3dcf1; border: 1px solid rgba(220,230,255,.16); border-radius: 999px; background: rgba(10,18,37,.22); font-size: 12px; }
        [data-theme="light"] .hero-meta span { color: #475569; border-color: rgba(71,85,105,.35); background: rgba(0,0,0,.05); }
        .hero-meta i { margin-right: 6px; color: var(--teal); }
        .section-heading { display: flex; justify-content: space-between; align-items: end; gap: 16px; margin: 34px 0 15px; }
        .section-heading h2 { margin: 0; font-size: 17px; letter-spacing: -.02em; }
        .section-heading p { margin: 4px 0 0; color: var(--muted); font-size: 12px; }
        .section-actions { display: flex; gap: 8px; }
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
        [data-theme="light"] .stat {
            background: linear-gradient(145deg, #ffffff, #f4f7fb);
            color: var(--text);
        }
        .stat::after { position: absolute; width: 110px; height: 110px; top: -48px; right: -38px; border-radius: 50%; background: var(--glow); opacity: .13; content: ""; }
        .stat-top { display: flex; justify-content: space-between; color: var(--muted); font-size: 12px; }
        .stat-icon { display: grid; width: 34px; height: 34px; place-items: center; color: var(--tone); border: 1px solid color-mix(in srgb, var(--tone), transparent 72%); border-radius: 10px; background: color-mix(in srgb, var(--tone), transparent 90%); }
        .stat-value { display: block; margin-top: 15px; font-size: 29px; font-weight: 750; letter-spacing: -.04em; }
        .stat-note { color: #8391ad; font-size: 11px; }
        [data-theme="light"] .stat-note { color: #64748b; }
        .grid-two { display: grid; grid-template-columns: minmax(0, 1.05fr) minmax(320px, .95fr); gap: 16px; }
        .panel {
            min-width: 0;
            padding: 21px;
            border: 1px solid var(--line);
            border-radius: 17px;
            background: linear-gradient(145deg, var(--panel), var(--panel-strong));
            box-shadow: 0 14px 40px rgba(0,0,0,.13);
        }
        [data-theme="light"] .panel { box-shadow: 0 14px 40px rgba(0,0,0,.06); }
        .panel-title { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 17px; }
        .panel-title h2 { margin: 0; font-size: 15px; }
        .panel-title p { margin: 3px 0 0; color: var(--muted); font-size: 11px; }
        .panel-title i { color: var(--accent); }
        .form-grid { display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: end; }
        .field label { display: block; margin: 0 0 7px; color: #aab6cd; font-size: 12px; }
        [data-theme="light"] .field label { color: #64748b; }
        .field input, .field select, .field textarea {
            width: 100%;
            min-height: 45px;
            padding: 10px 13px;
            color: var(--text);
            outline: 0;
            border: 1px solid rgba(175, 190, 225, .18);
            border-radius: 10px;
            background: rgba(8, 14, 28, .58);
        }
        [data-theme="light"] .field input, [data-theme="light"] .field select, [data-theme="light"] .field textarea {
            color: var(--text); background: #fff; border-color: var(--line);
        }
        .field textarea { min-height: 90px; padding: 12px 13px; resize: vertical; }
        .field input:focus, .field select:focus, .field textarea:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(139,156,255,.13); }
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
        .button-ghost {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            color: var(--text);
            border: 1px solid var(--line);
            border-radius: 10px;
            background: rgba(8, 14, 28, .45);
            cursor: pointer;
            font-weight: 700;
            font-size: 12px;
            transition: .2s ease;
        }
        [data-theme="light"] .button-ghost { color: var(--text); background: #f4f7fb; border-color: var(--line); }
        .button-ghost:hover { border-color: var(--accent); background: rgba(139,156,255,.1); }
        [data-theme="light"] .button-ghost:hover { border-color: var(--accent); background: color-mix(in srgb, var(--accent) 8%, transparent); }
        .button-danger {
            background: linear-gradient(110deg, #ff8296, #d94f6f);
            color: #10172b;
        }
        .button-danger:hover { box-shadow: 0 8px 22px rgba(255,130,150,.25); }
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
        [data-theme="light"] .upload-label { color: #475569; background: rgba(0,0,0,.05); border-color: rgba(71,85,105,.25); }
        .upload-label i { color: var(--teal); }
        .upload input:focus + .upload-label { outline: 2px solid var(--accent); outline-offset: 3px; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 10px 11px; color: #8190ad; border-bottom: 1px solid var(--line); font-size: 10px; letter-spacing: .1em; text-transform: uppercase; white-space: nowrap; }
        td { padding: 13px 11px; color: #dce4f5; border-bottom: 1px solid rgba(178,196,233,.09); font-size: 12px; }
        [data-theme="light"] td { color: var(--text); border-bottom-color: rgba(71,85,105,.12); }
        tr:last-child td { border-bottom: 0; }
        .id-pill { color: #c7d0ff; font-weight: 700; }
        [data-theme="light"] .id-pill { color: #2563eb; }
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
        [data-theme="light"] .patient-field { background: #f4f7fb; border-color: rgba(71,85,105,.18); }
        .patient-field span { display: block; color: var(--muted); font-size: 10px; text-transform: uppercase; letter-spacing: .08em; }
        .patient-field strong { display: block; margin-top: 4px; font-size: 13px; overflow-wrap: anywhere; }
        .clinical-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 13px; }
        .clinical-card { padding: 14px; border: 1px solid var(--line); border-radius: 12px; background: rgba(8,14,28,.26); }
        [data-theme="light"] .clinical-card { background: #f4f7fb; border-color: rgba(71,85,105,.18); }
        .clinical-card h3 { margin: 0 0 10px; font-size: 12px; }
        .clinical-card h3 i { margin-right: 7px; color: var(--teal); }
        .tags { display: flex; flex-wrap: wrap; gap: 6px; }
        .tag { padding: 5px 8px; color: #ffd2d8; border: 1px solid rgba(255,130,150,.2); border-radius: 999px; background: rgba(255,130,150,.08); font-size: 11px; }
        .clinical-list { display: grid; gap: 7px; margin: 0; padding: 0; list-style: none; }
        .clinical-list li { display: flex; justify-content: space-between; gap: 10px; color: #c5d0e5; font-size: 11px; padding: 6px 8px; border: 1px solid rgba(178,196,233,.09); border-radius: 8px; background: rgba(255,255,255,.02); }
        [data-theme="light"] .clinical-list li { color: var(--text); background: #f4f7fb; border-color: rgba(71,85,105,.12); }
        .clinical-list span:last-child { color: var(--muted); text-align: right; }
        .notice { margin-top: 14px; padding: 12px; color: #ffc5cc; border: 1px solid rgba(255,130,150,.18); border-radius: 10px; background: rgba(255,130,150,.06); }
        .message-box { margin-bottom: 14px; padding: 11px 13px; border-radius: 10px; font-size: 12px; display: flex; justify-content: space-between; align-items: center; }
        .message-box.success { color: #79e1c3; border: 1px solid rgba(78,216,192,.2); background: rgba(78,216,192,.08); }
        .message-box.error { color: #ffc5cc; border: 1px solid rgba(255,130,150,.2); background: rgba(255,130,150,.07); }
        .message-close { background: none; border: none; color: inherit; opacity: .7; font-size: 16px; line-height: 1; cursor: pointer; }
        .message-close:hover { opacity: 1; }
        .footer-note { margin-top: 26px; color: #73819c; text-align: center; font-size: 11px; }

        /* ---------- Wellness hub widgets ---------- */
        .widgets { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin: 30px 0; }
        .widget { border: 1px solid var(--line); border-radius: 17px; background: linear-gradient(145deg, var(--panel), var(--panel-strong)); padding: 20px; box-shadow: 0 14px 40px rgba(0,0,0,.13); }
        [data-theme="light"] .widget { box-shadow: 0 14px 40px rgba(0,0,0,.06); }
        .widget h3 { margin: 0 0 14px; font-size: 15px; display: flex; align-items: center; gap: 8px; }
        .widget h3 i { color: var(--accent); }
        .bmi-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px; }
        .bmi-result { margin-top: 12px; padding: 10px; border-radius: 10px; background: rgba(255,255,255,.05); font-size: 13px; display: none; }
        .bmi-result.show { display: block; }
        .vitals-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; margin: 14px 0; }
        .vital-card { padding: 12px 14px; border-radius: 12px; border: 1px solid var(--line); background: rgba(8,14,28,.35); text-align: center; }
        [data-theme="light"] .vital-card { background: #f4f7fb; border-color: rgba(71,85,105,.12); color: var(--text); }
        .vital-value { display: block; font-size: 21px; font-weight: 800; letter-spacing: -.02em; margin-top: 6px; }
        .vital-label { font-size: 10px; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); }
        .vital-cat { font-size: 11px; font-weight: 700; margin-top: 4px; }
        .notes-list { display: grid; gap: 8px; max-height: 260px; overflow-y: auto; padding-right: 6px; }
        .note-item { padding: 10px 12px; border: 1px solid rgba(178,196,233,.09); border-radius: 10px; background: rgba(8,14,28,.35); display: flex; justify-content: space-between; align-items: start; gap: 10px; font-size: 12.5px; }
        [data-theme="light"] .note-item { background: #f4f7fb; border-color: rgba(71,85,105,.12); color: var(--text); }
        .note-text { flex: 1; }
        .note-time { color: var(--muted); font-size: 10px; }
        .accent-row { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .accent-swatch { width: 26px; height: 26px; border-radius: 50%; border: 2px solid var(--line); cursor: pointer; }
        .accent-swatch.active { border-color: #fff; box-shadow: 0 0 0 3px rgba(255,255,255,.2); }
        .accent-swatch[data-accent="blue"] { background: #8b9cff; }
        .accent-swatch[data-accent="teal"] { background: #4ed8c0; }
        .accent-swatch[data-accent="rose"] { background: #ff8296; }
        .accent-swatch[data-accent="amber"] { background: #f5a623; }
        .mood-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; margin-top: 8px; }
        .mood-btn {
            height: 44px; border-radius: 10px; border: 1px solid var(--line);
            background: rgba(8,14,28,.35); color: var(--text); font-size: 1.35rem;
            font-weight: 700; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .15rem;
        }
        [data-theme="light"] .mood-btn { background: #f4f7fb; border-color: var(--line); color: var(--text); }
        .mood-btn:hover { border-color: var(--accent); color: var(--accent); }
        .mood-btn.active { background: var(--accent); color: #10172b; border-color: var(--accent); }
        .mood-label { font-size: .62rem; margin-top: .15rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); }
        .emergency-list { display: grid; gap: 8px; }
        .emergency-item { display: flex; justify-content: space-between; gap: 10px; padding: 9px 12px; border: 1px solid var(--line); border-radius: 10px; background: rgba(8,14,28,.35); font-size: 12.5px; align-items: center; }
        [data-theme="light"] .emergency-item { background: #f4f7fb; border-color: rgba(71,85,105,.12); color: var(--text); }
        .activity-list { display: grid; gap: 6px; max-height: 300px; overflow-y: auto; padding-right: 6px; }
        .activity-item { display: flex; justify-content: space-between; gap: 10px; padding: 8px 10px; border: 1px solid rgba(178,196,233,.08); border-radius: 9px; background: rgba(8,14,28,.25); font-size: 12px; align-items: center; }
        [data-theme="light"] .activity-item { background: #f4f7fb; border-color: rgba(71,85,105,.1); color: var(--text); }
        .activity-time { color: var(--muted); font-size: 10px; }

        @media (max-width: 1120px) {
            .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .grid-two { grid-template-columns: 1fr; }
            .widgets { grid-template-columns: 1fr; }
            .bmi-grid { grid-template-columns: 1fr; }
            .vitals-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 780px) {
            .sidebar { position: fixed; inset: auto 0 0 0; width: 100%; height: auto; max-height: 78px; border-right: 0; border-bottom: 1px solid var(--line); border-radius: 0; flex-direction: row; padding: 0; overflow-x: auto; overflow-y: hidden; }
            .sidebar-header, .sidebar-profile { display: none; }
            .nav-list { display: flex; gap: 0; padding: 0; }
            .nav-item { flex: 0 0 auto; }
            .nav-link { flex-direction: column; gap: 5px; padding: 9px 5px; text-align: center; font-size: 10px; border-radius: 10px; border: none; }
            .nav-link .nav-icon { font-size: 14px; }
            .content-main { width: 100%; margin: 0; padding: 24px 17px 90px; }
            .section-actions { flex-direction: column; }
            .topbar-center { max-width: none; flex: 1; }
            .date-chip { display: none; }
        }
        @media (max-width: 520px) {
            .topline { align-items: flex-start; flex-direction: column; align-items: flex-start; gap: 10px; }
            .stats { gap: 9px; }
            .stat { min-height: 120px; padding: 14px; }
            .stat-value { font-size: 25px; }
            .form-grid { grid-template-columns: 1fr; }
            .form-grid .button { width: 100%; }
            .patient-grid, .clinical-grid { grid-template-columns: 1fr; }
            .settings-grid { grid-template-columns: 1fr; }
            .panel { padding: 16px; }
            .widgets { gap: 12px; }
            .vitals-grid { grid-template-columns: 1fr; }
            .mood-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; }
        }
    </style>
<link rel="stylesheet" href="assets/css/presentation.css">
    <link rel="icon" type="image/webp" href="assets/cmrx-logo.webp">
</head>
<body class="workspace">
    <div class="toast-container" id="toastContainer"></div>
    <div class="dashboard-container">
        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="topbar-btn" id="sidebarToggle" aria-label="Collapse sidebar" title="Collapse sidebar" aria-expanded="true">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="brand"><span class="page-logo"><img src="assets/cmrx-logo.webp" width="420" height="280" alt="CMRx"></span></div>
                <div class="date-chip" id="dateChip"><i class="far fa-clock"></i> <span id="liveDate"></span></div>
            </div>
            <div class="topbar-center">
                <div class="nav-search">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search patients, reports..." aria-label="Search">
                </div>
            </div>
            <div class="topbar-right">
                <button class="topbar-btn" id="themeToggle" aria-label="Toggle theme" title="Toggle theme">
                    <i class="fas fa-moon" id="themeIcon"></i>
                </button>
                <button class="topbar-btn<?php echo $wellness_notifications ? ' active' : ''; ?>" id="notifBtn" aria-label="Notifications" title="Notifications" aria-pressed="<?php echo $wellness_notifications ? 'true' : 'false'; ?>">
                    <i class="fas fa-bell"></i>
                    <span class="notif-count" id="notifCount">0</span>
                </button>
                <div class="user-chip">
                    <div class="avatar"><?php echo doc_w_escape(strtoupper(substr($doctorName, 0, 1))); ?></div>
                    <span><?php echo doc_w_escape($doctorName); ?></span>
                </div>
                <button class="topbar-btn" id="helpBtn" aria-label="Keyboard shortcuts" title="Keyboard shortcuts" onclick="CMRx.modalOpen('helpModal')">
                    <i class="fas fa-question-circle"></i>
                </button>
            </div>
        </header>

        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-user-md"></i>
                <h2>Doctor Workspace</h2>
            </div>
            <div class="sidebar-profile">
                <div class="avatar-big"><?php echo doc_w_escape(strtoupper(substr($doctorName, 0, 1))); ?></div>
                <span class="name"><?php echo doc_w_escape($doctorName); ?></span>
                <span class="sub">DOC_ID: <?php echo doc_w_escape($doctorId); ?></span>
            </div>
            <nav class="sidebar-nav" aria-label="Main navigation">
                <ul class="nav-list">
                    <li class="nav-item"><a href="#overview" class="nav-link active" data-shortcut="o" data-href="#overview"><i class="fas fa-grid-2 nav-icon"></i><span>Overview</span></a></li>
                    <li class="nav-item"><a href="#patient-search" class="nav-link" data-shortcut="p" data-href="#patient-search"><i class="fas fa-magnifying-glass nav-icon"></i><span>Patient records</span></a></li>
                    <li class="nav-item"><a href="#issue-prescription" class="nav-link" data-shortcut="x" data-href="#issue-prescription"><i class="fas fa-prescription-bottle-medical nav-icon"></i><span>Prescriptions</span></a></li>
                    <li class="nav-item"><a href="#diagnostic-reports" class="nav-link" data-shortcut="r" data-href="#diagnostic-reports"><i class="fas fa-file-medical nav-icon"></i><span>Diagnostic reports</span></a></li>
                    <li class="nav-item"><a href="#visit-log" class="nav-link" data-shortcut="v" data-href="#visit-log"><i class="fas fa-calendar-check nav-icon"></i><span>Visit log</span></a></li>
                    <li class="nav-item"><a href="#recent-activity" class="nav-link" data-shortcut="a" data-href="#recent-activity"><i class="fas fa-clock-rotate-left nav-icon"></i><span>Activity</span></a></li>
                    <li class="nav-item"><a href="#clinical-notes" class="nav-link" data-shortcut="n" data-href="#clinical-notes"><i class="fas fa-sticky-note nav-icon"></i><span>Notes</span></a></li>
                    <li class="nav-item"><a href="#wellness-hub" class="nav-link" data-shortcut="w" data-href="#wellness-hub"><i class="fas fa-heartbeat nav-icon"></i><span>Wellness hub</span></a></li>
                    <li class="nav-item"><a href="#profile-settings" class="nav-link" data-shortcut="s" data-href="#profile-settings"><i class="fas fa-user-gear nav-icon"></i><span>Settings</span></a></li>
                </ul>
            </nav>
            <div class="logout-item">
                <a href="logout_doc.php" class="nav-link logout-link"><i class="fas fa-arrow-right-from-bracket nav-icon"></i><span>Sign out</span></a>
            </div>
        </aside>

        <!-- Main content -->
        <main class="content-main" id="overview">
            <div class="topline">
                <div class="eyebrow">Care, connected</div>
                <div class="date-chip-inline"><i class="fa-regular fa-calendar"></i> <?php echo date('l, F j, Y'); ?></div>
                <div class="section-actions">
                    <button class="button-ghost" onclick="window.print()" title="Print dashboard"><i class="fa-solid fa-print"></i> Print</button>
                </div>
            </div>

            <section class="hero">
                <div class="hero-content">
                    <div class="eyebrow">Doctor workspace</div>
                    <h1>Good to see you, Dr. <?php echo dashboardEscape($doctorName); ?>.</h1>
                    <p>Your clinical activity, patient records, prescriptions, diagnostic reports, and visit log in one focused workspace.</p>
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
                    <strong class="stat-value"><?php echo dashboardEscape($todayVisitsCount); ?></strong><span class="stat-note">Recorded in visit history</span>
                </article>
                <article class="stat" style="--tone:#a9b5ff;--glow:#a9b5ff">
                    <div class="stat-top"><span>Patients seen</span><span class="stat-icon"><i class="fa-solid fa-users"></i></span></div>
                    <strong class="stat-value"><?php echo dashboardEscape($totalPatients); ?></strong><span class="stat-note">Unique patients in your history</span>
                </article>
                <article class="stat" style="--tone:#ffd286;--glow:#ffd286">
                    <div class="stat-top"><span>Prescriptions</span><span class="stat-icon"><i class="fa-solid fa-file-prescription"></i></span></div>
                    <strong class="stat-value"><?php echo dashboardEscape($totalPrescriptions); ?></strong><span class="stat-note"><?php echo dashboardEscape($recentPrescriptions); ?> in the last 7 days</span>
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
                                        <?php if ($reports): ?><ul class="clinical-list"><?php foreach ($reports as $report): ?><li><span><a href="<?php echo dashboardEscape($report['Rep_File_Path']); ?>" target="_blank" rel="noopener"><?php echo dashboardEscape($report['Report_Name']); ?></a></span><span><?php echo dashboardEscape($report['Report_Date']); ?></span></li><?php endforeach; ?></ul><?php else: ?><div class="muted">No reports recorded.</div><?php endif; ?>
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

            <div class="section-heading" id="diagnostic-reports">
                <div><h2><i class="fa-solid fa-file-medical"></i> Diagnostic Reports</h2><p>Upload and view diagnostic reports for your hospital's patients</p></div>
            </div>
            <section class="panel">
                <?php if ($reportMessage !== ''): ?>
                    <div class="message-box <?php echo dashboardEscape($reportMessageType); ?>" role="status">
                        <span><?php echo dashboardEscape($reportMessage); ?></span>
                        <button class="message-close" onclick="this.closest('.message-box').remove()">&times;</button>
                    </div>
                <?php endif; ?>
                <div class="grid-two">
                    <div>
                        <form method="post" action="#diagnostic-reports" enctype="multipart/form-data">
                            <div class="field">
                                <label for="report_med_id">Patient MED_ID</label>
                                <input type="number" id="report_med_id" name="report_med_id" min="1" step="1" placeholder="Patient MED_ID" required>
                            </div>
                            <div class="field">
                                <label for="report_name">Report name</label>
                                <input type="text" id="report_name" name="report_name" placeholder="e.g. Blood test report" required>
                            </div>
                            <div class="field">
                                <label for="report_date">Report date</label>
                                <input type="date" id="report_date" name="report_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <label class="upload" for="report_file">
                                <input type="file" id="report_file" name="report_file" required>
                                <span class="upload-label"><span id="report-file-name">Choose a report file</span><i class="fa-solid fa-cloud-arrow-up"></i></span>
                            </label>
                            <button class="button" type="submit" name="upload_report"><i class="fa-solid fa-cloud-arrow-up"></i> Save report</button>
                        </form>
                    </div>
                    <div>
                        <h3 style="font-size:13px;margin:0 0 10px">Recent reports <span style="font-weight:500;font-size:11px;color:var(--muted)">(your hospital)</span></h3>
                        <?php if ($recentReports): ?>
                        <ul class="clinical-list">
                            <?php foreach ($recentReports as $r): ?>
                            <li>
                                <span><a href="<?php echo dashboardEscape($r['Rep_File_Path']); ?>" target="_blank" rel="noopener"><?php echo dashboardEscape($r['Report_Name']); ?></a></span>
                                <span><?php echo dashboardEscape($r['Report_Date']); ?> · <span class="id-pill"><?php echo dashboardEscape($r['MED_ID']); ?></span></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                        <div class="empty">No diagnostic reports uploaded yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <div class="section-heading" id="visit-log">
                <div><h2><i class="fa-solid fa-calendar-check"></i> Visit Log</h2><p>Record and review patient visits for today</p></div>
            </div>
            <section class="panel">
                <?php if ($visitMessage !== ''): ?>
                    <div class="message-box <?php echo dashboardEscape($visitMessageType); ?>" role="status">
                        <span><?php echo dashboardEscape($visitMessage); ?></span>
                        <button class="message-close" onclick="this.closest('.message-box').remove()">&times;</button>
                    </div>
                <?php endif; ?>
                <div class="grid-two">
                    <div>
                        <form method="post" action="#visit-log">
                            <div class="field">
                                <label for="visit_med_id">Patient MED_ID</label>
                                <input type="number" id="visit_med_id" name="visit_med_id" min="1" step="1" placeholder="Patient MED_ID" required>
                            </div>
                            <div class="field">
                                <label for="visit_notes">Visit notes (optional)</label>
                                <textarea id="visit_notes" name="visit_notes" placeholder="Brief visit summary"></textarea>
                            </div>
                            <button class="button" type="submit" name="log_visit"><i class="fa-solid fa-user-check"></i> Log visit</button>
                        </form>
                    </div>
                    <div>
                        <h3 style="font-size:13px;margin:0 0 10px">Today's visits <span style="font-weight:500;font-size:11px;color:var(--muted)">(<?php echo count($todayVisits); ?>)</span></h3>
                        <?php if ($todayVisits): ?>
                        <ul class="clinical-list">
                            <?php foreach ($todayVisits as $v): ?>
                            <li>
                                <span><?php echo dashboardEscape($v['Patient_Name'] ?: 'Patient'); ?></span>
                                <span class="id-pill"><?php echo dashboardEscape($v['MED_ID']); ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                        <div class="empty">No visits logged for today.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

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

            <div class="section-heading" id="clinical-notes">
                <div><h2><i class="fa-solid fa-sticky-note"></i> Clinical Notes</h2><p>Session-only notes — not stored in the database</p></div>
            </div>
            <section class="panel">
                <?php if ($noteMessage !== ''): ?>
                    <div class="message-box <?php echo dashboardEscape($noteMessageType); ?>" role="status">
                        <span><?php echo dashboardEscape($noteMessage); ?></span>
                        <button class="message-close" onclick="this.closest('.message-box').remove()">&times;</button>
                    </div>
                <?php endif; ?>
                <form method="post" action="#clinical-notes">
                    <div class="field">
                        <label for="note_text">New note</label>
                        <textarea id="note_text" name="note_text" placeholder="Quick clinical note…" required></textarea>
                    </div>
                    <button class="button" type="submit" name="add_note"><i class="fa-solid fa-plus"></i> Add note</button>
                    <button class="button-ghost" type="submit" name="clear_notes" onclick="return confirm('Clear all session notes?')"><i class="fa-solid fa-trash"></i> Clear all</button>
                </form>
                <?php if (!empty($docNotes)): ?>
                <div class="notes-list">
                    <?php foreach (array_reverse($docNotes) as $note): ?>
                    <div class="note-item">
                        <div class="note-text"><?php echo dashboardEscape($note['text']); ?></div>
                        <div class="note-time"><?php echo dashboardEscape($note['time']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="empty">No notes yet. Add one above.</div>
                <?php endif; ?>
            </section>

            <div class="section-heading" id="wellness-hub">
                <div><h2><i class="fa-solid fa-heartbeat"></i> Wellness Hub</h2><p>Session-only health tracking — nothing is written to the database.</p></div>
                <div class="section-actions">
                    <form method="post" action="#wellness-hub" style="margin:0">
                        <button class="button-ghost" type="submit" name="w_toggle_theme"><i class="fas fa-<?php echo $wellness_theme === 'dark' ? 'sun' : 'moon'; ?>"></i> <?php echo ucfirst($wellness_theme); ?> mode</button>
                        <button class="button-ghost" type="submit" name="w_toggle_notifications"><i class="fas fa-bell"></i> Notifications <?php echo $wellness_notifications ? 'off' : 'on'; ?></button>
                    </form>
                </div>
            </div>
            <section class="panel">
                <?php if ($w_message !== ''): ?>
                    <div class="message-box <?php echo dashboardEscape($w_messageType); ?>" role="status">
                        <span><?php echo dashboardEscape($w_message); ?></span>
                        <button class="message-close" onclick="this.closest('.message-box').remove()">&times;</button>
                    </div>
                <?php endif; ?>
                <div class="vitals-grid">
                    <div class="vital-card">
                        <span class="vital-label">Health score</span>
                        <span class="vital-value" style="color:var(--accent)"><?php echo (int)$health_score; ?></span>
                        <span class="vital-cat">0 &mdash; 100</span>
                    </div>
                    <div class="vital-card">
                        <span class="vital-label">BMI</span>
                        <span class="vital-value"><?php echo $bmi_latest ? dashboardEscape($bmi_latest['bmi']) : '&mdash'; ?></span>
                        <span class="vital-cat"><?php echo $bmi_latest ? dashboardEscape($bmi_latest['cat']) : '—'; ?></span>
                    </div>
                    <div class="vital-card">
                        <span class="vital-label">Blood pressure</span>
                        <span class="vital-value"><?php echo $bp_latest ? dashboardEscape($bp_latest['sys'].'/'.$bp_latest['dia']) : '&mdash'; ?></span>
                        <span class="vital-cat"><?php echo $bp_latest ? dashboardEscape($bp_latest['cat']) : '—'; ?></span>
                    </div>
                    <div class="vital-card">
                        <span class="vital-label">Heart rate</span>
                        <span class="vital-value"><?php echo $hr_latest ? dashboardEscape($hr_latest['bpm'].' BPM') : '&mdash'; ?></span>
                        <span class="vital-cat"><?php echo $hr_latest ? dashboardEscape($hr_latest['zone']) : '—'; ?></span>
                    </div>
                    <div class="vital-card">
                        <span class="vital-label">Temperature</span>
                        <span class="vital-value"><?php echo $temp_latest ? dashboardEscape($temp_latest['val']) . '°' . dashboardEscape(strtoupper($temp_latest['unit'])) : '&mdash'; ?></span>
                        <span class="vital-cat">Latest</span>
                    </div>
                    <div class="vital-card">
                        <span class="vital-label">Glucose</span>
                        <span class="vital-value"><?php echo $glucose_latest ? dashboardEscape($glucose_latest['val'].' mg/dL') : '&mdash'; ?></span>
                        <span class="vital-cat"><?php echo $glucose_latest ? dashboardEscape($glucose_latest['when']) : '—'; ?></span>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:16px">
                    <div class="field">
                        <label>Water today</label>
                        <div style="display:flex;gap:8px;align-items:center">
                            <form method="post" action="#wellness-hub" style="margin:0;display:flex;gap:6px;flex:1">
                                <input type="number" name="water_glasses" min="1" placeholder="glasses" style="flex:1;min-height:45px;padding:10px 13px;border-radius:10px;border:1px solid rgba(175,190,225,.18);background:rgba(8,14,28,.58);color:var(--text)">
                                <button class="button" type="submit" name="w_add_water" style="min-height:45px;padding:10px 16px">Log</button>
                            </form>
                            <span style="font-size:11px;color:var(--muted)"><?php echo $water_today; ?> / <?php echo $water_goal; ?> glasses</span>
                        </div>
                        <div class="progress" style="margin-top:8px"><div class="progress-bar" style="width:<?php echo $water_pct; ?>%"></div></div>
                    </div>
                    <div class="field">
                        <label>Steps today</label>
                        <div style="display:flex;gap:8px;align-items:center">
                            <form method="post" action="#wellness-hub" style="margin:0;display:flex;gap:6px;flex:1">
                                <input type="number" name="steps_val" min="1" placeholder="steps" style="flex:1;min-height:45px;padding:10px 13px;border-radius:10px;border:1px solid rgba(175,190,225,.18);background:rgba(8,14,28,.58);color:var(--text)">
                                <button class="button" type="submit" name="w_add_steps" style="min-height:45px;padding:10px 16px">Log</button>
                            </form>
                            <span style="font-size:11px;color:var(--muted)"><?php echo number_format($steps_today); ?> steps</span>
                        </div>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:18px">
                    <div class="widget">
                        <h3><i class="fa-solid fa-calculator"></i> Log BMI</h3>
                        <form method="post" action="#wellness-hub">
                            <div class="bmi-grid">
                                <div class="field">
                                    <label>Weight (kg)</label>
                                    <input type="number" id="w-weight" name="weight" step="0.1" min="1" placeholder="e.g. 70" required>
                                </div>
                                <div class="field">
                                    <label>Height (cm)</label>
                                    <input type="number" id="w-height" name="height_cm" step="0.1" min="30" placeholder="e.g. 170" required>
                                </div>
                            </div>
                            <button class="button" type="submit" name="w_add_bmi" style="width:100"><i class="fa-solid fa-calculator"></i> Calculate &amp; log</button>
                        </form>
                        <?php if ($bmi_latest): ?>
                        <div class="bmi-result show" style="margin-top:10px">
                            Latest: <?php echo dashboardEscape($bmi_latest['bmi']); ?> &mdash; <?php echo dashboardEscape($bmi_latest['cat']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="widget">
                        <h3><i class="fa-solid fa-heart-pulse"></i> Vitals</h3>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                            <div class="field">
                                <label>BP systolic</label>
                                <input type="number" name="bp_sys" min="1" placeholder="e.g. 120" required>
                            </div>
                            <div class="field">
                                <label>BP diastolic</label>
                                <input type="number" name="bp_dia" min="1" placeholder="e.g. 80" required>
                            </div>
                        </div>
                        <button class="button" type="submit" name="w_add_bp" style="width:100;margin-top:8px"><i class="fa-solid fa-heart-pulse"></i> Log blood pressure</button>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:18px">
                    <div class="widget">
                        <h3><i class="fa-solid fa-temperature-half"></i> Temperature &amp; glucose</h3>
                        <form method="post" action="#wellness-hub" style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                            <div class="field">
                                <label>Value</label>
                                <input type="number" name="temp_val" step="0.1" min="0" placeholder="e.g. 36.6" required>
                            </div>
                            <div class="field">
                                <label>Unit</label>
                                <select name="temp_unit" required>
                                    <option value="c">&deg;C</option>
                                    <option value="f">&deg;F</option>
                                </select>
                            </div>
                            <button class="button" type="submit" name="w_add_temp" style="grid-column:1/-1"><i class="fa-solid fa-temperature-half"></i> Log temperature</button>
                        </form>
                        <form method="post" action="#wellness-hub" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:12px">
                            <div class="field">
                                <label>Glucose (mg/dL)</label>
                                <input type="number" name="glucose_val" step="0.1" min="0" placeholder="e.g. 95" required>
                            </div>
                            <div class="field">
                                <label>When</label>
                                <input type="text" name="glucose_when" placeholder="e.g. Fasting" required>
                            </div>
                            <button class="button" type="submit" name="w_add_glucose" style="grid-column:1/-1"><i class="fa-solid fa-droplet"></i> Log glucose</button>
                        </form>
                    </div>
                    <div class="widget">
                        <h3><i class="fa-solid fa-clock"></i> Heart rate</h3>
                        <form method="post" action="#wellness-hub">
                            <div class="field">
                                <label>BPM</label>
                                <input type="number" name="hr_bpm" min="1" placeholder="e.g. 72" required>
                            </div>
                            <button class="button" type="submit" name="w_add_hr" style="width:100;margin-top:8px"><i class="fa-solid fa-clock"></i> Log heart rate</button>
                        </form>
                        <?php if ($hr_latest): ?>
                        <div class="bmi-result show" style="margin-top:10px">
                            Latest: <?php echo dashboardEscape($hr_latest['bpm']); ?> BPM &mdash; <?php echo dashboardEscape($hr_latest['zone']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:18px">
                    <div class="widget">
                        <h3><i class="fa-solid fa-clipboard-list"></i> Medications</h3>
                        <form method="post" action="#wellness-hub" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px">
                            <div class="field"><label>Name</label><input type="text" name="med_name" placeholder="e.g. Lisinopril" required></div>
                            <div class="field"><label>Time</label><input type="time" name="med_time" required></div>
                            <div class="field"><label>Dose</label><input type="text" name="med_dose" placeholder="e.g. 10mg" required></div>
                            <button class="button" type="submit" name="w_add_med" style="grid-column:1/-1"><i class="fa-solid fa-plus"></i> Add reminder</button>
                        </form>
                        <?php if (!empty($W['medications'])): ?>
                        <div style="margin-top:10px">
                            <?php foreach ($W['medications'] as $m): ?>
                            <div style="display:flex;justify-content:space-between;gap:8px;padding:8px 10px;border:1px solid var(--line);border-radius:10px;background:rgba(8,14,28,.35);align-items:center;font-size:12.5px;margin-bottom:6px">
                                <span><?php echo dashboardEscape($m['name']); ?> <span style="color:var(--muted);font-size:10px">· <?php echo dashboardEscape($m['time']); ?> · <?php echo dashboardEscape($m['dose']); ?></span></span>
                                <div style="display:flex;gap:6px">
                                    <form method="post" action="#wellness-hub" style="margin:0"><button class="button-ghost" type="submit" name="w_med_done" value="<?php echo dashboardEscape($m['id']); ?>" title="<?php echo $m['done'] ? 'Mark pending' : 'Mark done'; ?>"><i class="fa-solid fa-<?php echo $m['done'] ? 'rotate-left' : 'circle-check'; ?>"></i></button></form>
                                    <form method="post" action="#wellness-hub" style="margin:0" onsubmit="return confirm('Remove this medication?')"><button class="button-ghost button-danger" type="submit" name="w_med_delete" value="<?php echo dashboardEscape($m['id']); ?>" title="Remove"><i class="fa-solid fa-trash"></i></button></form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <span style="font-size:11px;color:var(--muted)"><?php echo count($active_meds); ?> active reminder(s)</span>
                        </div>
                        <?php else: ?>
                        <div class="empty" style="margin-top:10px">No medication reminders yet.</div>
                        <?php endif; ?>
                    </div>
                    <div class="widget">
                        <h3><i class="fa-solid fa-triangle-exclamation"></i> Symptoms &amp; moods</h3>
                        <form method="post" action="#wellness-hub" style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px">
                            <div class="field"><label>Symptom</label><input type="text" name="sym_name" placeholder="e.g. Headache" required></div>
                            <div class="field"><label>Severity</label><select name="sym_severity" required><option value="Mild">Mild</option><option value="Moderate">Moderate</option><option value="Severe">Severe</option></select></div>
                            <div class="field" style="grid-column:1/-1"><label>Note</label><input type="text" name="sym_note" placeholder="Optional note"></div>
                            <button class="button" type="submit" name="w_add_symptom" style="grid-column:1/-1"><i class="fa-solid fa-plus"></i> Log symptom</button>
                        </form>
                        <div class="field" style="margin-bottom:10px"><label>Mood</label><input type="text" name="mood" list="moods" placeholder="Pick or type a mood" style="min-height:45px;padding:10px 13px;border-radius:10px;border:1px solid rgba(175,190,225,.18);background:rgba(8,14,28,.58);color:var(--text)"><datalist id="moods"><option>Happy</option><option>Neutral</option><option>Anxious</option><option>Tired</option><option>Stressed</option><option>Calm</option></datalist></div>
                        <div style="display:flex;gap:8px;align-items:center"><input type="text" name="mood_note" placeholder="Note" style="flex:1;min-height:45px;padding:10px 13px;border-radius:10px;border:1px solid rgba(175,190,225,.18);background:rgba(8,14,28,.58);color:var(--text)"><button class="button" type="submit" name="w_add_mood"><i class="fa-solid fa-face-smile"></i> Log mood</button></div>
                        <?php if (!empty($W['symptoms']) || !empty($W['moods'])): ?>
                        <div style="margin-top:10px">
                            <?php foreach (array_reverse($W['symptoms']) as $i => $s): ?>
                            <div style="display:flex;justify-content:space-between;gap:8px;padding:8px 10px;border:1px solid var(--line);border-radius:10px;background:rgba(8,14,28,.35);align-items:center;font-size:12.5px;margin-bottom:6px">
                                <span><?php echo dashboardEscape($s['name']); ?> <span style="color:var(--muted);font-size:10px">· <?php echo dashboardEscape($s['sev']); ?></span></span>
                                <form method="post" action="#wellness-hub" style="margin:0" onsubmit="return confirm('Remove this symptom?')"><button class="button-ghost button-danger" type="submit" name="w_sym_delete" value="<?php echo $i; ?>" title="Remove"><i class="fa-solid fa-trash"></i></button></form>
                            </div>
                            <?php endforeach; ?>
                            <?php foreach (array_reverse($W['moods']) as $i => $mo): ?>
                            <div style="display:flex;justify-content:space-between;gap:8px;padding:8px 10px;border:1px solid var(--line);border-radius:10px;background:rgba(8,14,28,.35);align-items:center;font-size:12.5px;margin-bottom:6px">
                                <span><?php echo dashboardEscape($mo['mood']); ?> <span style="color:var(--muted);font-size:10px">· <?php echo dashboardEscape($mo['note'] ?: '—'); ?></span></span>
                                <form method="post" action="#wellness-hub" style="margin:0" onsubmit="return confirm('Remove this mood?')"><button class="button-ghost button-danger" type="submit" name="w_mood_delete" value="<?php echo $i; ?>" title="Remove"><i class="fa-solid fa-trash"></i></button></form>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:18px">
                    <div class="widget">
                        <h3><i class="fa-solid fa-phone-flip"></i> Emergency contacts</h3>
                        <form method="post" action="#wellness-hub" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px">
                            <div class="field"><label>Name</label><input type="text" name="em_name" placeholder="e.g. Spouse" required></div>
                            <div class="field"><label>Phone</label><input type="tel" name="em_phone" placeholder="e.g. +1 555-0000" required></div>
                            <div class="field"><label>Relation</label><input type="text" name="em_relation" placeholder="e.g. Spouse"></div>
                            <button class="button" type="submit" name="w_add_emergency" style="grid-column:1/-1"><i class="fa-solid fa-plus"></i> Add contact</button>
                        </form>
                        <?php if (!empty($W['emergency'])): ?>
                        <div class="emergency-list">
                            <?php foreach ($W['emergency'] as $e): ?>
                            <div class="emergency-item">
                                <span><?php echo dashboardEscape($e['name']); ?> <span style="color:var(--muted);font-size:10px">· <?php echo dashboardEscape($e['phone']); ?> <?php echo $e['relation'] ? '· '.dashboardEscape($e['relation']) : ''; ?></span></span>
                                <form method="post" action="#wellness-hub" style="margin:0" onsubmit="return confirm('Remove this contact?')"><button class="button-ghost button-danger" type="submit" name="w_em_delete" value="<?php echo dashboardEscape($e['id']); ?>" title="Remove"><i class="fa-solid fa-trash"></i></button></form>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="empty" style="margin-top:10px">No emergency contacts yet.</div>
                        <?php endif; ?>
                    </div>
                    <div class="widget">
                        <h3><i class="fa-solid fa-clock-rotate-left"></i> Recent activity</h3>
                        <?php $recentActivity = array_slice(array_reverse($W['activity']), 0, 10); ?>
                        <?php if ($recentActivity): ?>
                        <div class="activity-list">
                            <?php foreach ($recentActivity as $act): ?>
                            <div class="activity-item">
                                <span><?php echo dashboardEscape($act['text']); ?></span>
                                <span class="activity-time"><?php echo dashboardEscape($act['time']); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="empty" style="margin-top:10px">No activity yet.</div>
                        <?php endif; ?>
                        <div style="margin-top:10px;color:var(--muted);font-size:11px"><?php echo dashboardEscape($health_tip); ?></div>
                    </div>
                </div>
            </section>

            <div class="section-heading" id="profile-settings">
                <div><h2><i class="fa-solid fa-user-gear"></i> Profile settings</h2><p>Keep your doctor name and certification current</p></div>
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
                    <div class="accent-row" style="margin-top:12px">
                        <span style="font-size:11px;color:var(--muted);margin-right:8px">Accent:</span>
                        <button type="button" class="accent-swatch active" data-accent="blue" onclick="setAccent(this,'blue')" title="Blue"></button>
                        <button type="button" class="accent-swatch" data-accent="teal" onclick="setAccent(this,'teal')" title="Teal"></button>
                        <button type="button" class="accent-swatch" data-accent="rose" onclick="setAccent(this,'rose')" title="Rose"></button>
                        <button type="button" class="accent-swatch" data-accent="amber" onclick="setAccent(this,'amber')" title="Amber"></button>
                    </div>
                    <button class="button" type="submit" name="update_doctor_profile" value="1"><i class="fa-solid fa-floppy-disk"></i> Save settings</button>
                    <p class="settings-note">Hospital and department assignments are managed by your hospital administrator.</p>
                </form>
            </section>

            <div class="widgets">
                <section class="widget" id="bmi-calc">
                    <h3><i class="fa-solid fa-calculator"></i> Quick BMI</h3>
                    <div class="bmi-grid">
                        <div class="field">
                            <label>Weight (kg)</label>
                            <input type="number" id="bmi-weight" step="0.1" min="1" placeholder="e.g. 70">
                        </div>
                        <div class="field">
                            <label>Height (cm)</label>
                            <input type="number" id="bmi-height" step="0.1" min="30" placeholder="e.g. 170">
                        </div>
                    </div>
                    <button class="button" type="button" style="width:100%" onclick="calcBMI()"><i class="fa-solid fa-chart-simple"></i> Calculate BMI</button>
                    <div class="bmi-result" id="bmi-result"></div>
                </section>
            </div>

            <div class="footer-note">CMRx · Patient information is intended for authorized care workflows.</div>
        </main>
    </div>

    <div class="modal-backdrop" id="helpModal">
        <div class="modal">
            <h2><i class="fa-solid fa-keyboard"></i> Keyboard shortcuts</h2>
            <p>Press <kbd>Alt</kbd> + key to navigate quickly.</p>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:8px;font-size:13px;color:var(--muted)">
                <div>Alt + O · Overview</div>
                <div>Alt + P · Patient records</div>
                <div>Alt + X · Prescriptions</div>
                <div>Alt + R · Diagnostic reports</div>
                <div>Alt + V · Visit log</div>
                <div>Alt + A · Activity</div>
                <div>Alt + N · Clinical notes</div>
                <div>Alt + W · Wellness hub</div>
                <div>Alt + S · Settings</div>
            </div>
            <div class="modal-actions"><button class="button-ghost" onclick="CMRx.modalClose('helpModal')">Close</button></div>
        </div>
    </div>

    <script>
        const prescriptionFile = document.getElementById('pres_file');
        prescriptionFile.addEventListener('change', function () {
            const label = document.getElementById('file-name');
            label.textContent = this.files.length ? this.files[0].name : 'Choose a PDF or Word document';
        });
        const reportFile = document.getElementById('report_file');
        reportFile.addEventListener('change', function () {
            const label = document.getElementById('report-file-name');
            label.textContent = this.files.length ? this.files[0].name : 'Choose a report file';
        });

        function calcBMI() {
            const w = parseFloat(document.getElementById('bmi-weight').value);
            const h = parseFloat(document.getElementById('bmi-height').value);
            const res = document.getElementById('bmi-result');
            if (!w || !h || w <= 0 || h <= 0) {
                res.textContent = 'Enter valid weight and height.';
                res.className = 'bmi-result';
                res.style.display = 'block';
                return;
            }
            const bmi = (w / Math.pow(h / 100, 2)).toFixed(1);
            let cat = bmi < 18.5 ? 'Underweight' : (bmi < 25 ? 'Normal' : (bmi < 30 ? 'Overweight' : 'Obese'));
            res.textContent = 'BMI: ' + bmi + ' — ' + cat;
            res.className = 'bmi-result show';
            res.style.display = 'block';
        }

        function setAccent(el, name) {
            document.querySelectorAll('.accent-swatch').forEach(s => s.classList.remove('active'));
            el.classList.add('active');
            const root = document.documentElement;
            const map = { blue: '#8b9cff', teal: '#4ed8c0', rose: '#ff8296', amber: '#f5a623' };
            root.style.setProperty('--accent', map[name]);
            sessionStorage.setItem('doc_accent', name);
        }
        (function () {
            const saved = sessionStorage.getItem('doc_accent');
            if (saved) {
                const el = document.querySelector('.accent-swatch[data-accent="' + saved + '"]');
                if (el) setAccent(el, saved);
            }
        })();
    </script>
    <script src="assets/js/ui.js"></script>
</body>
</html>
