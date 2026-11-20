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

mysqli_set_charset($con, "utf8mb4");
$med_id = (int)($_SESSION['med_id'] ?? 0);
$name = $dob = $blood_group = $Gender = $mobile_no = $email = '';

if (empty($_SESSION['profile_update_csrf'])) {
    $_SESSION['profile_update_csrf'] = bin2hex(random_bytes(32));
}
$profile_csrf_token = $_SESSION['profile_update_csrf'];
$profile_message = $_SESSION['profile_update_message'] ?? '';
$profile_message_type = $_SESSION['profile_update_message_type'] ?? '';
unset($_SESSION['profile_update_message'], $_SESSION['profile_update_message_type']);

// Fetch user details using MED_ID
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

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $updated_name = isset($_POST['name']) && is_string($_POST['name']) ? trim($_POST['name']) : '';
    $updated_dob = isset($_POST['dob']) && is_string($_POST['dob']) ? trim($_POST['dob']) : '';
    $updated_blood_group = isset($_POST['blood_group']) && is_string($_POST['blood_group']) ? trim($_POST['blood_group']) : '';
    $updated_mobile = isset($_POST['mobile_no']) && is_string($_POST['mobile_no']) ? trim($_POST['mobile_no']) : '';
    $updated_email = isset($_POST['email']) && is_string($_POST['email']) ? trim($_POST['email']) : '';
    $updated_gender = isset($_POST['Gender']) && is_string($_POST['Gender']) ? trim($_POST['Gender']) : '';

    $name = $updated_name;
    $dob = $updated_dob;
    $blood_group = $updated_blood_group;
    $mobile_no = $updated_mobile;
    $email = $updated_email;
    $Gender = $updated_gender;

    $postedToken = isset($_POST['profile_csrf_token']) && is_string($_POST['profile_csrf_token'])
        ? $_POST['profile_csrf_token']
        : '';
    if (!hash_equals($profile_csrf_token, $postedToken)) {
        $profile_message = "Your session has expired. Reload the page and try again.";
        $profile_message_type = 'error';
    } elseif (
        $updated_name === '' || $updated_dob === '' || $updated_blood_group === '' ||
        $updated_mobile === '' || $updated_email === '' ||
        !in_array($updated_gender, ['Male', 'Female', 'Other'], true)
    ) {
        $profile_message = "Please complete all profile fields.";
        $profile_message_type = 'error';
    } elseif (strlen($updated_email) > 100 || !filter_var($updated_email, FILTER_VALIDATE_EMAIL)) {
        $profile_message = "Enter a valid email address (maximum 100 characters).";
        $profile_message_type = 'error';
    } else {
        $transactionStarted = false;
        try {
            if (!$con->begin_transaction()) {
                throw new RuntimeException("Unable to begin profile update transaction.");
            }
            $transactionStarted = true;

            $update_stmt1 = $con->prepare(
                "UPDATE patient_details
                 SET Name = ?, DOB = ?, Blood_Group = ?, Gender = ?
                 WHERE MED_ID = ?"
            );
            if (!$update_stmt1) {
                throw new RuntimeException($con->error);
            }
            $update_stmt1->bind_param("ssssi", $updated_name, $updated_dob, $updated_blood_group, $updated_gender, $med_id);
            if (!$update_stmt1->execute()) {
                throw new RuntimeException($update_stmt1->error);
            }
            $update_stmt1->close();

            $update_stmt2 = $con->prepare("UPDATE patient_mobile SET Mobile_No = ? WHERE MED_ID = ?");
            if (!$update_stmt2) {
                throw new RuntimeException($con->error);
            }
            $update_stmt2->bind_param("si", $updated_mobile, $med_id);
            if (!$update_stmt2->execute()) {
                throw new RuntimeException($update_stmt2->error);
            }
            $update_stmt2->close();

            $insert_mobile = $con->prepare(
                "INSERT INTO patient_mobile (MED_ID, Mobile_No)
                 SELECT ?, ? FROM DUAL
                 WHERE NOT EXISTS (SELECT 1 FROM patient_mobile WHERE MED_ID = ?)"
            );
            if (!$insert_mobile) {
                throw new RuntimeException($con->error);
            }
            $insert_mobile->bind_param("isi", $med_id, $updated_mobile, $med_id);
            if (!$insert_mobile->execute()) {
                throw new RuntimeException($insert_mobile->error);
            }
            $insert_mobile->close();

            $update_stmt3 = $con->prepare("UPDATE patient_email SET Email = ? WHERE MED_ID = ?");
            if (!$update_stmt3) {
                throw new RuntimeException($con->error);
            }
            $update_stmt3->bind_param("si", $updated_email, $med_id);
            if (!$update_stmt3->execute()) {
                throw new RuntimeException($update_stmt3->error);
            }
            $update_stmt3->close();

            $insert_email = $con->prepare(
                "INSERT INTO patient_email (MED_ID, Email)
                 SELECT ?, ? FROM DUAL
                 WHERE NOT EXISTS (SELECT 1 FROM patient_email WHERE MED_ID = ?)"
            );
            if (!$insert_email) {
                throw new RuntimeException($con->error);
            }
            $insert_email->bind_param("isi", $med_id, $updated_email, $med_id);
            if (!$insert_email->execute()) {
                throw new RuntimeException($insert_email->error);
            }
            $insert_email->close();

            $con->commit();
            $_SESSION['profile_update_message'] = "Profile updated successfully.";
            $_SESSION['profile_update_message_type'] = 'success';
            header("Location: dashboard.php?action=update");
            exit;
        } catch (Throwable $e) {
            if ($transactionStarted) {
                $con->rollback();
            }
            error_log("Patient profile update failed: " . $e->getMessage());
            $profile_message = "Unable to update your profile. Please try again.";
            $profile_message_type = 'error';
        }
    }
}

// Handle disease update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_disease'])) {
    $disease_name = isset($_POST['disease_name']) ? trim($_POST['disease_name']) : '';
    $present_condition = isset($_POST['present_condition']) ? trim($_POST['present_condition']) : '';

    $allowed_conditions = ['Severe', 'Mild', 'Very Mild', 'Cured', 'Seasonal'];

    if (empty($disease_name) || empty($present_condition)) {
        $message = "Both fields are required.";
    } elseif (!in_array($present_condition, $allowed_conditions, true)) {
        $message = "Invalid condition value. Please select a valid condition.";
    } else {
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
        $stmt = $con->prepare("INSERT INTO patient_allergies (MED_ID, Allergy_Name) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("is", $med_id, $allergy_name);
            if ($stmt->execute()) {
                $allergy_message = "Allergy added successfully.";
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
    $dose_no = isset($_POST['dose_no']) ? (int)$_POST['dose_no'] : 0;
    $vacc_admin_date = isset($_POST['vacc_admin_date']) && is_string($_POST['vacc_admin_date']) ? trim($_POST['vacc_admin_date']) : '';

    if (empty($vaccine_name) || empty($dose_no) || empty($vacc_admin_date)) {
        $vaccine_message = "All fields are required.";
    } else {
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

// Handle prescription file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_prescription'])) {
    $prescription_date = date('Y-m-d');

    if (isset($_FILES['prescription_file']) && $_FILES['prescription_file']['error'] == 0) {
        $target_dir = "uploads/prescriptions/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . basename($_FILES["prescription_file"]["name"]);
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if ($file_type != "pdf") {
            $upload_message = "Only PDF files are allowed.";
        } else {
            if (move_uploaded_file($_FILES["prescription_file"]["tmp_name"], $target_file)) {
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

// ============================================================================
// WELLNESS HUB - NON-DATABASE (SESSION-ONLY) FEATURES
// All data below lives in $_SESSION['wellness']; zero database writes.
// ============================================================================

if (empty($_SESSION['wellness']) || !is_array($_SESSION['wellness'])) {
    $_SESSION['wellness'] = [
        'theme' => 'light',
        'notifications' => false,
        'sound' => true,
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
$W = &$_SESSION['wellness'];
$wellness_theme = $W['theme'] ?? 'light';
$wellness_notifications = $W['notifications'] ?? false;

function w_escape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function disease_badge($cond) {
    $c = strtolower((string)$cond);
    if ($c === 'cured') {
        return 'normal';
    }
    if ($c === 'severe' || strpos($c, 'severe') === 0) {
        return 'high';
    }
    return 'low';
}

function w_add_activity(&$W, $text) {
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
            w_add_activity($W, "Logged BMI: $bmi ($cat)");
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
            w_add_activity($W, "Logged BP: $sys/$dia ($cat)");
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
            w_add_activity($W, "Logged heart rate: $bpm BPM ($zone)");
            $w_message = "Heart rate recorded: $bpm BPM ($zone).";
            $w_message_type = 'success';
        } else {
            $w_message = "Enter a valid heart rate.";
            $w_message_type = 'error';
        }
    }
    // Water intake
    elseif (isset($_POST['w_add_water'])) {
        $today = date('Y-m-d');
        $glasses = (int)($_POST['water_glasses'] ?? 0);
        if ($glasses > 0) {
            $W['water'][$today] = (int)($W['water'][$today] ?? 0) + $glasses;
            w_add_activity($W, "Drank $glasses glass(es) of water");
            $w_message = "Added $glasses glass(es) of water.";
            $w_message_type = 'success';
        } else {
            $w_message = "Enter a positive number of glasses.";
            $w_message_type = 'error';
        }
    }
    // Medication
    elseif (isset($_POST['w_add_med'])) {
        $mname = trim($_POST['med_name'] ?? '');
        $mtime = trim($_POST['med_time'] ?? '');
        $mdose = trim($_POST['med_dose'] ?? '');
        if ($mname !== '' && $mtime !== '' && $mdose !== '') {
            $W['medications'][] = ['id' => uniqid(), 'name' => $mname, 'time' => $mtime, 'dose' => $mdose, 'done' => false];
            w_add_activity($W, "Added medication reminder: $mname");
            $w_message = "Medication reminder added.";
            $w_message_type = 'success';
        } else {
            $w_message = "Fill all medication fields.";
            $w_message_type = 'error';
        }
    }
    // Mark medication done / delete medication
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
    elseif (isset($_POST['w_med_delete'])) {
        $id = $_POST['w_med_delete'];
        $W['medications'] = array_values(array_filter($W['medications'], fn($m) => $m['id'] !== $id));
        w_add_activity($W, "Removed a medication reminder");
    }
    // Symptom
    elseif (isset($_POST['w_add_symptom'])) {
        $sname = trim($_POST['sym_name'] ?? '');
        $sev = trim($_POST['sym_severity'] ?? '');
        $note = trim($_POST['sym_note'] ?? '');
        if ($sname !== '' && $sev !== '') {
            $W['symptoms'][] = ['date' => date('Y-m-d H:i'), 'name' => $sname, 'sev' => $sev, 'note' => $note];
            w_add_activity($W, "Logged symptom: $sname ($sev)");
            $w_message = "Symptom logged.";
            $w_message_type = 'success';
        } else {
            $w_message = "Enter symptom name and severity.";
            $w_message_type = 'error';
        }
    }
    // Mood
    elseif (isset($_POST['w_add_mood'])) {
        $mood = trim($_POST['mood'] ?? '');
        $mnote = trim($_POST['mood_note'] ?? '');
        if ($mood !== '') {
            $W['moods'][] = ['date' => date('Y-m-d H:i'), 'mood' => $mood, 'note' => $mnote];
            w_add_activity($W, "Mood: $mood");
            $w_message = "Mood recorded.";
            $w_message_type = 'success';
        } else {
            $w_message = "Select a mood.";
            $w_message_type = 'error';
        }
    }
    // Temperature
    elseif (isset($_POST['w_add_temp'])) {
        $t = (float)($_POST['temp_val'] ?? 0);
        $unit = trim($_POST['temp_unit'] ?? 'c');
        if ($t > 0) {
            $W['temp'][] = ['date' => date('Y-m-d H:i'), 'val' => $t, 'unit' => $unit];
            w_add_activity($W, "Logged temperature: $t\u00B0$unit");
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
            w_add_activity($W, "Logged glucose: $g mg/dL ($when)");
            $w_message = "Glucose recorded.";
            $w_message_type = 'success';
        } else {
            $w_message = "Enter a valid glucose value.";
            $w_message_type = 'error';
        }
    }
    // Steps
    elseif (isset($_POST['w_add_steps'])) {
        $today = date('Y-m-d');
        $s = (int)($_POST['steps_val'] ?? 0);
        if ($s > 0) {
            $W['steps'][$today] = (int)($W['steps'][$today] ?? 0) + $s;
            w_add_activity($W, "Added $s step(s)");
            $w_message = "$s step(s) added.";
            $w_message_type = 'success';
        } else {
            $w_message = "Enter a positive step count.";
            $w_message_type = 'error';
        }
    }
    // Emergency contact
    elseif (isset($_POST['w_add_emergency'])) {
        $ename = trim($_POST['em_name'] ?? '');
        $ephone = trim($_POST['em_phone'] ?? '');
        $erel = trim($_POST['em_relation'] ?? '');
        if ($ename !== '' && $ephone !== '') {
            $W['emergency'][] = ['id' => uniqid(), 'name' => $ename, 'phone' => $ephone, 'relation' => $erel];
            w_add_activity($W, "Added emergency contact: $ename");
            $w_message = "Emergency contact added.";
            $w_message_type = 'success';
        } else {
            $w_message = "Enter contact name and phone.";
            $w_message_type = 'error';
        }
    }
    elseif (isset($_POST['w_em_delete'])) {
        $id = $_POST['w_em_delete'];
        $W['emergency'] = array_values(array_filter($W['emergency'], fn($e) => $e['id'] !== $id));
        w_add_activity($W, "Removed an emergency contact");
    }
    // Delete symptom / mood
    elseif (isset($_POST['w_sym_delete'])) {
        $idx = (int)($_POST['w_sym_delete']);
        if (isset($W['symptoms'][$idx])) {
            unset($W['symptoms'][$idx]);
            $W['symptoms'] = array_values($W['symptoms']);
            w_add_activity($W, "Removed a symptom entry");
        }
    }
    elseif (isset($_POST['w_mood_delete'])) {
        $idx = (int)($_POST['w_mood_delete']);
        if (isset($W['moods'][$idx])) {
            unset($W['moods'][$idx]);
            $W['moods'] = array_values($W['moods']);
            w_add_activity($W, "Removed a mood entry");
        }
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
$today = date('Y-m-d');
$water_today = (int)($W['water'][$today] ?? 0);
$water_goal = (int)($W['water_goal'] ?? 8);
$water_pct = $water_goal > 0 ? min(100, round(($water_today / $water_goal) * 100)) : 0;
$active_meds = array_values(array_filter($W['medications'], fn($m) => !$m['done']));
$mood_latest = !empty($W['moods']) ? end($W['moods']) : null;

// Age from DOB
$age_years = '';
if ($dob !== '') {
    $dob_ts = strtotime($dob);
    if ($dob_ts !== false) {
        $age_years = (int)floor((time() - $dob_ts) / 31556926);
    }
}

// Health score (0-100) based on available wellness inputs
$health_score = 50;
if ($bmi_latest) { $health_score += ($bmi_latest['bmi'] >= 18.5 && $bmi_latest['bmi'] <= 24.9) ? 10 : -5; }
if ($bp_latest) { $health_score += ($bp_latest['sys'] < 130 && $bp_latest['dia'] < 90) ? 10 : -10; }
if ($hr_latest) { $health_score += ($hr_latest['bpm'] >= 60 && $hr_latest['bpm'] <= 100) ? 10 : -5; }
if ($water_today >= $water_goal) { $health_score += 10; } elseif ($water_today > 0) { $health_score += 5; }
$health_score = max(0, min(100, $health_score));

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>CMRx | Patient Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --primary-light: #eff6ff;
            --secondary: #0ea5e9;
            --accent: #14b8a6;
            --danger: #dc2626;
            --danger-light: #fef2f2;
            --success: #059669;
            --success-light: #ecfdf5;
            --warning: #d97706;
            --warning-light: #fefce8;
            --ink: #0f172a;
            --muted: #64748b;
            --bg: #f4f7fb;
            --surface: #ffffff;
            --border: #e2e8f0;
            --radius: 14px;
            --shadow: 0 10px 28px rgba(15, 23, 42, 0.06), 0 2px 6px rgba(15, 23, 42, 0.03);
            --shadow-inner: inset 0 2px 4px rgba(0,0,0,0.04);
            --transition: 200ms cubic-bezier(0.4, 0, 0.2, 1);
        }
        [data-theme="dark"] {
            --bg: #0b1020;
            --surface: #111827;
            --border: #1f2937;
            --ink: #f1f5f9;
            --muted: #94a3b8;
            --shadow: 0 10px 28px rgba(0,0,0,0.35), 0 2px 6px rgba(0,0,0,0.2);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: Inter, "Segoe UI", system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--ink);
            line-height: 1.55;
            min-height: 100vh;
        }
        a { color: inherit; text-decoration: none; }
        button, input, select, textarea { font: inherit; color: inherit; }
        button { background: none; border: none; cursor: pointer; }
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }

        /* ---------- Layout ---------- */
        .dashboard-container {
            display: grid;
            grid-template-columns: 260px 1fr;
            grid-template-rows: 64px 1fr;
            grid-template-areas:
                "header header"
                "sidebar main";
            min-height: 100vh;
            max-width: 1720px;
            margin: 0 auto;
            background: var(--bg);
        }
        .topbar {
            grid-area: header;
            position: sticky; top: 0; z-index: 40;
            height: 64px;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 1rem;
            border-bottom: 1px solid var(--border);
            background: color-mix(in srgb, var(--surface) 88%, transparent);
            backdrop-filter: blur(12px);
            color: var(--ink);
        }
        .topbar-left { display: flex; align-items: center; gap: 0.9rem; }
        .brand { display: flex; align-items: center; gap: 0.55rem; font-weight: 800; font-size: 1.2rem; letter-spacing: -0.02em; color: var(--primary); }
        .brand i { color: var(--accent); filter: drop-shadow(0 0 1px rgba(20,184,166,0.4)); }
        .topbar-center { display: flex; align-items: center; gap: 1rem; flex: 1; max-width: 520px; }
        .date-chip {
            padding: 0.45rem 0.9rem; border-radius: 999px; background: var(--primary-light);
            color: var(--primary); font-size: 0.82rem; font-weight: 700; letter-spacing: 0.03em;
            border: 1px solid color-mix(in srgb, var(--primary) 35%, transparent);
        }
        [data-theme="dark"] .date-chip { background: rgba(37,99,235,0.14); color: #60a5fa; border-color: rgba(59,130,246,0.35); }
        .topbar-right { display: flex; align-items: center; gap: 0.5rem; }
        .nav-search {
            position: relative; width: 100%;
        }
        .nav-search input {
            width: 100%; min-height: 40px; padding: 0 1rem 0 2.6rem;
            border-radius: 10px; border: 1px solid var(--border); background: var(--surface);
            color: var(--ink); font-size: 0.9rem;
        }
        .nav-search i { position: absolute; left: 0.85rem; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 0.95rem; }
        .topbar-btn {
            display: flex; align-items: center; justify-content: center;
            width: 40px; height: 40px; border-radius: 10px;
            border: 1px solid var(--border); background: var(--surface); color: var(--ink);
            font-size: 1.1rem; transition: var(--transition);
        }
        .topbar-btn:hover { background: color-mix(in srgb, var(--primary) 8%, var(--surface)); border-color: color-mix(in srgb, var(--primary) 40%, transparent); }
        .topbar-btn.active { background: var(--primary); color: #fff; border-color: var(--primary); }
        .user-chip {
            display: flex; align-items: center; gap: 0.55rem;
            padding: 0.35rem 0.8rem; border-radius: 999px; border: 1px solid var(--border);
            background: var(--surface); font-size: 0.88rem; font-weight: 600;
        }
        .user-chip .avatar {
            width: 32px; height: 32px; border-radius: 50%; background: var(--primary-light);
            color: var(--primary); display: grid; place-items: center; font-weight: 800; font-size: 0.9rem;
            border: 1px solid color-mix(in srgb, var(--primary) 45%, transparent);
        }
        [data-theme="dark"] .user-chip .avatar { background: rgba(37,99,235,0.18); color: #60a5fa; border-color: rgba(59,130,246,0.45); }

        /* ---------- Sidebar ---------- */
        .sidebar {
            position: sticky; top: 0; height: 100vh; width: 260px;
            border-right: 1px solid var(--border);
            background: var(--surface); color: var(--ink);
            display: flex; flex-direction: column; z-index: 30;
            overflow-y: auto;
        }
        .sidebar-header {
            padding: 1rem 1.1rem; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 0.6rem;
        }
        .sidebar-header i { color: var(--accent); font-size: 1.2rem; }
        .sidebar-header h2 { font-size: 1rem; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; color: var(--ink); }
        .sidebar-profile {
            padding: 0.85rem 1.1rem; border-bottom: 1px solid var(--border); text-align: center;
        }
        .sidebar-profile .avatar-big {
            width: 48px; height: 48px; border-radius: 50%; margin: 0 auto 0.5rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff; display: grid; place-items: center; font-weight: 800; font-size: 1.1rem;
            box-shadow: 0 8px 20px color-mix(in srgb, var(--primary) 45%, transparent);
        }
        .sidebar-profile .name { font-weight: 700; font-size: 0.9rem; display: block; }
        .sidebar-profile .sub { font-size: 0.72rem; color: var(--muted); display: block; margin-top: 0.1rem; }
        .nav-list { list-style: none; padding: 0.9rem 0; display: grid; gap: 0.2rem; }
        .nav-item { margin: 0; }
        .nav-link {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.7rem 1.1rem; border-radius: 10px; color: var(--muted);
            font-size: 0.88rem; font-weight: 600; transition: var(--transition);
            border: 1px solid transparent;
        }
        .nav-link .nav-icon { font-size: 1.05rem; width: 20px; text-align: center; color: color-mix(in srgb, var(--muted) 70%, transparent); }
        .nav-link:hover { background: var(--primary-light); color: var(--primary); border-color: color-mix(in srgb, var(--primary) 25%, transparent); }
        .nav-link:hover .nav-icon { color: var(--primary); }
        .nav-link.active { background: var(--primary); color: #fff; box-shadow: 0 4px 14px color-mix(in srgb, var(--primary) 40%, transparent); border-color: var(--primary); }
        .nav-link.active .nav-icon { color: #eef6ff; }
        .nav-divider { height: 1px; background: var(--border); margin: 0.6rem 1.1rem; opacity: 0.6; }
        .nav-group-label { padding: 0.55rem 1.1rem 0.25rem; font-size: 0.68rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); }
        .logout-item { margin-top: auto; }
        .nav-link.logout-link { color: var(--danger); border-color: transparent; }
        .nav-link.logout-link:hover { background: var(--danger-light); color: var(--danger); }
        .nav-link.logout-link .nav-icon { color: var(--danger); }

        /* ---------- Main content ---------- */
        .content-main {
            grid-area: main;
            padding: clamp(1.2rem, 2.5vw, 2.4rem);
            overflow-y: auto;
            min-width: 0;
        }
        .content-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 1.6rem; gap: 1rem;
        }
        .content-title-wrap h1 { font-size: 1.55rem; font-weight: 800; letter-spacing: -0.01em; margin-bottom: 0.15rem; }
        .content-title-wrap p { font-size: 0.9rem; color: var(--muted); }
        .content-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .btn {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.55rem 1.05rem; border-radius: 10px; font-size: 0.88rem; font-weight: 700;
            border: 1px solid transparent; transition: var(--transition); line-height: 1;
        }
        .btn-primary { background: var(--primary); color: #fff; box-shadow: 0 4px 14px color-mix(in srgb, var(--primary) 35%, transparent); }
        .btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
        .btn-ghost { background: var(--surface); color: var(--ink); border-color: var(--border); }
        .btn-ghost:hover { background: color-mix(in srgb, var(--ink) 5%, var(--surface)); }
        .btn-success { background: var(--success); color: #fff; }
        .btn-outline { border: 1px solid var(--border); color: var(--ink); background: var(--surface); }
        .btn-outline:hover { border-color: var(--muted); background: color-mix(in srgb, var(--ink) 4%, var(--surface)); }
        .btn-sm { padding: 0.4rem 0.75rem; font-size: 0.78rem; font-weight: 700; }
        .btn-icon { padding: 0.5rem; border-radius: 8px; font-size: 0.95rem; }
        .btn-icon i { width: 16px; text-align: center; }
        .btn-danger { background: var(--danger); color: #fff; }

        /* ---------- Cards ---------- */
        .card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 16px; padding: 1.25rem; box-shadow: var(--shadow);
            transition: var(--transition);
        }
        .card:hover { box-shadow: 0 16px 40px rgba(15,23,42,0.08); }
        .card-header { display: flex; align-items: center; gap: 0.7rem; margin-bottom: 1.1rem; }
        .card-header .card-icon { width: 34px; height: 34px; border-radius: 10px; background: var(--primary-light); color: var(--primary); display: grid; place-items: center; font-size: 1.1rem; }
        .card-header h3 { font-size: 1.05rem; font-weight: 800; letter-spacing: -0.01em; }
        .card-title { font-size: 1rem; font-weight: 700; margin-bottom: 0.9rem; color: var(--ink); display: flex; align-items: center; gap: 0.55rem; }
        .card-title i { color: var(--primary); font-size: 0.95rem; }

        /* ---------- Stat cards ---------- */
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 1rem; margin-bottom: 1.4rem; }
        .stat-card {
            background: var(--surface); border: 1px solid var(--border); border-radius: 16px;
            padding: 1.1rem; display: flex; flex-direction: column; gap: 0.6rem;
            box-shadow: var(--shadow); position: relative; overflow: hidden;
        }
        .stat-card::before { content: ""; position: absolute; inset: 0 0 auto 4px; height: 4px; border-radius: 12px 12px 0 0; }
        .stat-card.primary::before { background: var(--primary); }
        .stat-card.success::before { background: var(--success); }
        .stat-card.info::before { background: var(--secondary); }
        .stat-card.warn::before { background: var(--warning); }
        .stat-card .stat-head { display: flex; align-items: center; justify-content: space-between; }
        .stat-label { font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted); }
        .stat-value { font-size: 1.7rem; font-weight: 800; letter-spacing: -0.02em; color: var(--ink); }
        .stat-note { font-size: 0.78rem; color: var(--muted); margin-top: auto; }
        .stat-icon { width: 36px; height: 36px; border-radius: 10px; color: #fff; display: grid; place-items: center; font-size: 1.2rem; box-shadow: 0 4px 12px color-mix(in srgb, var(--ink) 20%, transparent); }
        .stat-icon.primary { background: var(--primary); }
        .stat-icon.success { background: var(--success); }
        .stat-icon.info { background: var(--secondary); }
        .stat-icon.warn { background: var(--warning); }

        /* ---------- Messages ---------- */
        .msg {
            margin-bottom: 1.1rem; padding: 0.9rem 1.1rem; border-radius: 12px;
            font-size: 0.9rem; font-weight: 600; border-left: 4px solid;
            display: flex; align-items: center; gap: 0.65rem; animation: slideIn 280ms ease;
        }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
        .msg-success { background: var(--success-light); color: #047857; border-color: var(--success); }
        .msg-error { background: var(--danger-light); color: #b91c1c; border-color: var(--danger); }
        .msg i { font-size: 1.1rem; }
        .msg-close { margin-left: auto; background: transparent; border: none; color: inherit; opacity: 0.7; font-size: 1.2rem; line-height: 1; padding: 0 0.2rem; }
        .msg-close:hover { opacity: 1; }

        /* ---------- Grid & forms ---------- */
        .card-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; }
        .info-item {
            padding: 1rem; border-radius: 12px; border: 1px solid var(--border);
            background: color-mix(in srgb, var(--ink) 4%, var(--surface));
        }
        .info-label { display: block; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); margin-bottom: 0.35rem; }
        .info-value { font-size: 0.95rem; font-weight: 700; color: var(--ink); overflow-wrap: anywhere; }
        .modern-form { display: grid; gap: 1rem; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1rem; }
        .field { display: flex; flex-direction: column; gap: 0.45rem; }
        .field label { font-size: 0.78rem; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: 0.04em; }
        .field input, .field select, .field textarea {
            min-height: 44px; padding: 0 1rem; border-radius: 10px;
            border: 1px solid var(--border); background: var(--surface);
            color: var(--ink); font-size: 0.92rem; transition: var(--transition);
        }
        .field input:focus, .field select:focus, .field textarea:focus {
            outline: none; border-color: var(--primary);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--primary) 12%, transparent);
        }
        .field textarea { min-height: 90px; padding: 0.8rem 1rem; resize: vertical; }
        .mood-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 0.55rem; }
        .mood-btn {
            height: 56px; border-radius: 12px; border: 1px solid var(--border);
            background: var(--surface); color: var(--ink); font-size: 1.4rem;
            font-weight: 700; transition: var(--transition); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.2rem;
        }
        .mood-btn:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }
        .mood-btn.active { background: var(--primary); color: #fff; border-color: var(--primary); }
        .mood-label { font-size: 0.62rem; margin-top: 0.18rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }

        /* ---------- Tables ---------- */
        .resp-wrap { overflow-x: auto; border: 1px solid var(--border); border-radius: 12px; margin-bottom: 1rem; }
        table.med { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 0.9rem; }
        table.med th { background: color-mix(in srgb, var(--ink) 6%, var(--surface)); color: var(--muted); font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.07em; padding: 0.9rem 1rem; text-align: left; border-bottom: 1px solid var(--border); }
        table.med td { padding: 0.9rem 1rem; border-bottom: 1px solid var(--border); color: var(--ink); vertical-align: middle; }
        table.med tr:last-child td { border-bottom: 0; }
        table.med tr:nth-child(even) td { background: color-mix(in srgb, var(--ink) 3%, var(--surface)); }
        table.med tr:hover td { background: color-mix(in srgb, var(--primary) 6%, var(--surface)); }
        .badge {
            display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.25rem 0.7rem;
            border-radius: 999px; font-size: 0.7rem; font-weight: 800; letter-spacing: 0.04em;
        }
        .badge-normal { background: var(--success-light); color: var(--success); }
        .badge-elevated { background: var(--warning-light); color: #b45309; }
        .badge-high { background: var(--danger-light); color: var(--danger); }
        .badge-low { background: color-mix(in srgb, var(--muted) 12%, var(--surface)); color: var(--muted); }
        .empty-state { text-align: center; padding: 2.5rem 1rem; color: var(--muted); }
        .empty-state i { font-size: 2.2rem; margin-bottom: 0.8rem; opacity: 0.5; display: block; }
        .empty-state h3 { font-size: 1rem; font-weight: 700; margin-bottom: 0.3rem; color: var(--ink); }

        /* ---------- File upload ---------- */
        .file-zone {
            border: 2px dashed var(--border); border-radius: 12px; padding: 1.6rem;
            text-align: center; background: color-mix(in srgb, var(--ink) 3%, var(--surface));
            transition: var(--transition); cursor: pointer;
        }
        .file-zone:hover { border-color: var(--primary); background: var(--primary-light); }
        .file-zone i { font-size: 1.9rem; color: var(--primary); margin-bottom: 0.6rem; display: block; }
        .file-zone p { font-size: 0.9rem; color: var(--muted); margin-bottom: 0.5rem; }
        .file-zone input[type="file"] { display: none; }
        .selected-file {
            margin-top: 0.8rem; padding: 0.75rem 1rem; border-radius: 10px;
            border: 1px solid var(--success); background: var(--success-light);
            color: #047857; font-size: 0.88rem; display: flex; align-items: center; gap: 0.6rem;
        }
        .selected-file i { color: var(--success); font-size: 1.1rem; }

        /* ---------- Responsive ---------- */
        @media (max-width: 992px) {
            .dashboard-container { grid-template-columns: 1fr; }
            .sidebar { position: fixed; inset: auto 0 0 0; width: 100%; height: auto; max-height: 78px; border-right: 0; border-bottom: 1px solid var(--border); border-radius: 0; flex-direction: row; padding: 0; overflow-x: auto; overflow-y: hidden; }
            .sidebar-header { display: none; }
            .sidebar-profile { display: none; }
            .nav-list { grid: none; display: flex; gap: 0; padding: 0; }
            .nav-item { flex: 0 0 auto; }
            .nav-link { flex-direction: column; gap: 0.3rem; padding: 0.55rem 0.5rem; font-size: 0.68rem; min-width: 72px; text-align: center; border-radius: 10px; border: none; }
            .nav-link .nav-icon { font-size: 1rem; width: auto; margin-bottom: 0; color: var(--muted); }
            .nav-link:hover { background: var(--primary-light); color: var(--primary); border: none; }
            .nav-link.active { background: var(--primary-light); color: var(--primary); border: none; box-shadow: inset 0 2px var(--primary); }
            .nav-link.active .nav-icon { color: var(--primary); }
            .nav-divider { display: none; }
            .nav-group-label { display: none; }
            .content-main { padding-bottom: 90px; }
            .topbar-center { max-width: none; flex: 1; }
            .date-chip { display: none; }
        }
        @media (max-width: 576px) {
            .content-main { padding: 1rem 0.85rem 90px; }
            .content-header { flex-direction: column; align-items: flex-start; gap: 0.9rem; }
            .content-actions { width: 100%; justify-content: space-between; }
            .stat-grid { grid-template-columns: 1fr 1fr; }
            .card-grid { grid-template-columns: 1fr; }
            .mood-grid { grid-template-columns: repeat(3, 1fr); }
            .btn { width: 100%; justify-content: center; }
        }
        @media (max-width: 420px) {
            .stat-grid { grid-template-columns: 1fr; }
        }

        /* ---------- Print ---------- */
        @media print {
            .sidebar, .topbar, .btn, .file-zone input, .no-print { display: none !important; }
            .dashboard-container { grid-template-columns: 1fr; max-width: 100%; margin: 0; background: #fff; }
            .content-main { padding: 0; }
            .card { border: 1px solid #ccc; box-shadow: none; background: #fff; }
            body { background: #fff; color: #000; }
        }

    </style>
    <link rel="stylesheet" href="assets/css/ui.css">
<link rel="stylesheet" href="assets/css/presentation.css">
    <link rel="icon" type="image/webp" href="assets/cmrx-logo.webp">
</head>
<body class="workspace" data-theme="<?php echo w_escape($wellness_theme); ?>">
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
                    <input type="text" placeholder="Search records, allergies, vaccines..." aria-label="Search">
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
                    <div class="avatar"><?php echo w_escape(strtoupper(substr($name ?? 'U', 0, 1))); ?></div>
                    <span><?php echo w_escape($name); ?></span>
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
                <h2>Patient Portal</h2>
            </div>
            <div class="sidebar-profile">
                <div class="avatar-big"><?php echo w_escape(strtoupper(substr($name ?? 'U', 0, 1))); ?></div>
                <span class="name"><?php echo w_escape($name); ?></span>
                <span class="sub">MED_ID: <?php echo w_escape($med_id); ?></span>
            </div>
            <nav class="sidebar-nav" aria-label="Main navigation">
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="Homepage.php" class="nav-link"><i class="fas fa-house nav-icon"></i><span>Home Page</span></a>
                    </li>
                    <li class="nav-item">
                        <a href="?action=dashboard" class="nav-link <?php echo !isset($_GET['action']) || $_GET['action'] === 'dashboard' ? 'active' : ''; ?>">
                            <i class="fas fa-gauge-high nav-icon"></i><span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?action=wellness" class="nav-link <?php echo isset($_GET['action']) && $_GET['action'] === 'wellness' ? 'active' : ''; ?>">
                            <i class="fas fa-heart nav-icon"></i><span>Wellness Hub</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?action=update" class="nav-link <?php echo isset($_GET['action']) && $_GET['action'] === 'update' ? 'active' : ''; ?>">
                            <i class="fas fa-user-pen nav-icon"></i><span>Update Profile</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?action=update_disease" class="nav-link <?php echo isset($_GET['action']) && $_GET['action'] === 'update_disease' ? 'active' : ''; ?>">
                            <i class="fas fa-stethoscope nav-icon"></i><span>Update Disease</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?action=add_allergy" class="nav-link <?php echo isset($_GET['action']) && $_GET['action'] === 'add_allergy' ? 'active' : ''; ?>">
                            <i class="fas fa-circle-exclamation nav-icon"></i><span>Add Allergy</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?action=update_vaccine" class="nav-link <?php echo isset($_GET['action']) && $_GET['action'] === 'update_vaccine' ? 'active' : ''; ?>">
                            <i class="fas fa-syringe nav-icon"></i><span>Vaccine Tracker</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?action=upload_prescription" class="nav-link <?php echo isset($_GET['action']) && $_GET['action'] === 'upload_prescription' ? 'active' : ''; ?>">
                            <i class="fas fa-file-upload nav-icon"></i><span>Upload Prescription</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="view_prescriptions.php" class="nav-link"><i class="fas fa-folder-open nav-icon"></i><span>View Prescriptions</span></a>
                    </li>
                    <div class="nav-divider"></div>
                    <li class="nav-item logout-item">
                        <a href="logout.php" class="nav-link logout-link"><i class="fas fa-sign-out-alt nav-icon"></i><span>Log Out</span></a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main content -->
        <main class="content-main">

            <!-- ============ DASHBOARD VIEW ============ -->
            <?php if (!isset($_GET['action']) || $_GET['action'] === 'dashboard'): ?>
            <header class="content-header">
                <div class="content-title-wrap">
                    <h1>Dashboard</h1>
                    <p>Welcome back, <span style="color:var(--primary);font-weight:700"><?php echo w_escape($name); ?></span>. Here's your health snapshot.</p>
                </div>
                <div class="content-actions">
                    <button class="btn btn-ghost" id="printBtn" title="Print this dashboard"><i class="fas fa-print"></i> Print Dashboard</button>
                </div>
            </header>

            <!-- Wellness messages -->
            <?php if ($w_message !== ''): ?>
            <div class="msg msg-<?php echo w_escape($w_message_type); ?>">
                <i class="fas fa-<?php echo $w_message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo w_escape($w_message); ?>
                <button class="msg-close" onclick="this.closest('.msg').remove()">&times;</button>
            </div>
            <?php endif; ?>
            <?php if ($profile_message !== ''): ?>
            <div class="msg msg-<?php echo w_escape($profile_message_type); ?>">
                <i class="fas fa-<?php echo $profile_message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo w_escape($profile_message); ?>
                <button class="msg-close" onclick="this.closest('.msg').remove()">&times;</button>
            </div>
            <?php endif; ?>

            <!-- Wellness stat cards -->
            <div class="stat-grid">
                <div class="stat-card primary">
                    <div class="stat-head">
                        <div>
                            <div class="stat-label">Health Score</div>
                            <div class="stat-value"><?php echo (int)$health_score; ?></div>
                        </div>
                        <div class="stat-icon primary"><i class="fas fa-heart-circle-check"></i></div>
                    </div>
                    <div class="stat-note">Overall wellness based on your logged vitals.</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-head">
                        <div>
                            <div class="stat-label">Water Today</div>
                            <div class="stat-value"><?php echo $water_today; ?>/<?php echo $water_goal; ?></div>
                        </div>
                        <div class="stat-icon success"><i class="fas fa-glass-water"></i></div>
                    </div>
                    <div class="stat-note"><?php echo $water_pct; ?>% of daily goal</div>
                </div>
                <div class="stat-card info">
                    <div class="stat-head">
                        <div>
                            <div class="stat-label">Pending Meds</div>
                            <div class="stat-value"><?php echo count($active_meds); ?></div>
                        </div>
                        <div class="stat-icon info"><i class="fas fa-circle-notch"></i></div>
                    </div>
                    <div class="stat-note">Active medication reminders</div>
                </div>
                <div class="stat-card warn">
                    <div class="stat-head">
                        <div>
                            <div class="stat-label">BMI</div>
                            <div class="stat-value"><?php echo $bmi_latest ? w_escape($bmi_latest['bmi']) : '—'; ?></div>
                        </div>
                        <div class="stat-icon warn"><i class="fas fa-weight-scale"></i></div>
                    </div>
                    <div class="stat-note"><?php echo $bmi_latest ? w_escape($bmi_latest['cat']) : 'Not logged'; ?></div>
                </div>
            </div>

            <!-- Quick health tools -->
            <section class="card">
                <div class="card-header">
                    <div class="card-icon"><i class="fas fa-tools"></i></div>
                    <h3>Quick Health Tools</h3>
                </div>
                <div class="card-grid">
                    <div class="info-item" tabindex="0" role="button" data-tool="bmi" data-search="BMI Calculator weight height">
                        <div style="display:flex;align-items:center;justify-content:space-between">
                            <div><div class="info-label">BMI Calculator</div><div class="info-value">Weight & height</div></div>
                            <i class="fas fa-chevron-right" style="opacity:0.4"></i>
                        </div>
                    </div>
                    <div class="info-item" tabindex="0" role="button" data-tool="bp" data-search="Blood Pressure systolic diastolic">
                        <div style="display:flex;align-items:center;justify-content:space-between">
                            <div><div class="info-label">Blood Pressure</div><div class="info-value">Systolic / Diastolic</div></div>
                            <i class="fas fa-chevron-right" style="opacity:0.4"></i>
                        </div>
                    </div>
                    <div class="info-item" tabindex="0" role="button" data-tool="hr" data-search="Heart Rate bpm beats per minute">
                        <div style="display:flex;align-items:center;justify-content:space-between">
                            <div><div class="info-label">Heart Rate</div><div class="info-value">Beats per minute</div></div>
                            <i class="fas fa-chevron-right" style="opacity:0.4"></i>
                        </div>
                    </div>
                    <div class="info-item" tabindex="0" role="button" data-tool="water" data-search="Water Intake glasses hydration">
                        <div style="display:flex;align-items:center;justify-content:space-between">
                            <div><div class="info-label">Water Intake</div><div class="info-value"><?php echo $water_today; ?> glasses</div></div>
                            <i class="fas fa-chevron-right" style="opacity:0.4"></i>
                        </div>
                    </div>
                    <div class="info-item" tabindex="0" role="button" data-tool="meds" data-search="Meds Reminders medication">
                        <div style="display:flex;align-items:center;justify-content:space-between">
                            <div><div class="info-label">Meds & Reminders</div><div class="info-value"><?php echo count($active_meds); ?> pending</div></div>
                            <i class="fas fa-chevron-right" style="opacity:0.4"></i>
                        </div>
                    </div>
                    <div class="info-item" tabindex="0" role="button" data-tool="mood" data-search="Mood Tracker how do you feel">
                        <div style="display:flex;align-items:center;justify-content:space-between">
                            <div><div class="info-label">Mood Tracker</div><div class="info-value">How do you feel?</div></div>
                            <i class="fas fa-chevron-right" style="opacity:0.4"></i>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Daily tip -->
            <section class="card" style="background:linear-gradient(135deg,color-mix(in srgb,var(--accent) 18%,var(--surface)),var(--surface));border-color:color-mix(in srgb,var(--accent) 35%,transparent)">
                <div class="card-header">
                    <div class="card-icon" style="background:color-mix(in srgb,var(--accent) 15%,var(--surface));color:var(--accent)"><i class="fas fa-lightbulb"></i></div>
                    <h3>Daily Health Tip</h3>
                </div>
                <p style="font-size:0.98rem;color:var(--muted);line-height:1.6"><?php echo w_escape($health_tip); ?></p>
            </section>

            <!-- Personal info -->
            <section class="card">
                <div class="card-header"><div class="card-icon"><i class="fas fa-id-card"></i></div><h3>Personal Information</h3></div>
                <div class="card-grid">
                    <div class="info-item"><span class="info-label">MED_ID</span><span class="info-value"><?php echo w_escape($med_id); ?></span></div>
                    <div class="info-item"><span class="info-label">Date of Birth</span><span class="info-value"><?php echo w_escape($dob ?: '—'); ?><?php echo $age_years !== '' ? ' (' . (int)$age_years . ' yrs)' : ''; ?></span></div>
                    <div class="info-item"><span class="info-label">Blood Group</span><span class="info-value"><?php echo w_escape($blood_group ?: '—'); ?></span></div>
                    <div class="info-item"><span class="info-label">Mobile</span><span class="info-value"><?php echo w_escape($mobile_no ?: '—'); ?></span></div>
                    <div class="info-item"><span class="info-label">Email</span><span class="info-value"><?php echo w_escape($email ?: '—'); ?></span></div>
                    <div class="info-item"><span class="info-label">Gender</span><span class="info-value"><?php echo w_escape($Gender ?: '—'); ?></span></div>
                </div>
            </section>

            <!-- Medical history -->
            <section class="card">
                <div class="card-header"><div class="card-icon"><i class="fas fa-notes-medical"></i></div><h3>Medical History</h3></div>
                <div class="resp-wrap">
                    <?php if (!empty($diseases)): ?>
                    <table class="med">
                        <thead><tr><th>Disease</th><th>Condition</th></tr></thead>
                        <tbody>
                        <?php foreach ($diseases as $d): ?>
                        <tr>
                            <td><?php echo w_escape($d['Disease_Name']); ?></td>
                            <td><span class="badge badge-<?php echo disease_badge($d['Present_Condition']); ?>"><?php echo w_escape($d['Present_Condition']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state"><i class="fas fa-file-medical"></i><h3>No disease records</h3><p>Add conditions from Update Disease.</p></div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Allergies -->
            <section class="card">
                <div class="card-header"><div class="card-icon"><i class="fas fa-user-shield"></i></div><h3>Allergies</h3></div>
                <div class="resp-wrap">
                    <?php if (!empty($allergies)): ?>
                    <table class="med">
                        <thead><tr><th>Allergy</th></tr></thead>
                        <tbody>
                        <?php foreach ($allergies as $a): ?>
                        <tr><td><span class="badge badge-high" style="font-size:0.72rem;padding:0.18rem 0.55rem"><?php echo w_escape($a); ?></span></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state"><i class="fas fa-circle-exclamation"></i><h3>No allergies recorded</h3><p>Add allergies from Add Allergy.</p></div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Vaccines -->
            <section class="card">
                <div class="card-header"><div class="card-icon"><i class="fas fa-vial"></i></div><h3>Vaccines</h3></div>
                <div class="resp-wrap">
                    <?php if (!empty($vaccines)): ?>
                    <table class="med">
                        <thead><tr><th>Vaccine</th><th>Dose</th><th>Date</th></tr></thead>
                        <tbody>
                        <?php foreach ($vaccines as $v): ?>
                        <tr>
                            <td><?php echo w_escape($v['Vaccine_Name']); ?></td>
                            <td><?php echo w_escape($v['Dose_No']); ?></td>
                            <td><?php echo w_escape($v['Vacc_Admin_Date']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state"><i class="fas fa-syringe"></i><h3>No vaccine records</h3><p>Add vaccines from Vaccine Tracker.</p></div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- ============ WELLNESS HUB VIEW ============ -->
            <?php elseif (isset($_GET['action']) && $_GET['action'] === 'wellness'): ?>
            <header class="content-header">
                <div class="content-title-wrap">
                    <h1>Wellness Hub</h1>
                    <p>Track and manage your everyday health — stored locally in your session, no database needed.</p>
                </div>
                <div class="content-actions">
                    <button class="btn btn-ghost" onclick="location.href='?action=dashboard'"><i class="fas fa-arrow-left"></i> Back to Dashboard</button>
                </div>
            </header>

            <?php if ($w_message !== ''): ?>
            <div class="msg msg-<?php echo w_escape($w_message_type); ?>">
                <i class="fas fa-<?php echo $w_message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo w_escape($w_message); ?>
                <button class="msg-close" onclick="this.closest('.msg').remove()">&times;</button>
            </div>
            <?php endif; ?>

            <div class="stat-grid">
                <div class="stat-card primary">
                    <div class="stat-head">
                        <div>
                            <div class="stat-label">Health Score</div>
                            <div class="stat-value"><?php echo (int)$health_score; ?></div>
                        </div>
                        <div class="stat-icon primary"><i class="fas fa-heart-circle-check"></i></div>
                    </div>
                    <div class="stat-note">Computed from your logged vitals.</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-head">
                        <div>
                            <div class="stat-label">Water Today</div>
                            <div class="stat-value"><?php echo $water_today; ?>/<?php echo $water_goal; ?></div>
                        </div>
                        <div class="stat-icon success"><i class="fas fa-glass-water"></i></div>
                    </div>
                    <div class="stat-note"><?php echo $water_pct; ?>% of goal</div>
                </div>
                <div class="stat-card info">
                    <div class="stat-head">
                        <div>
                            <div class="stat-label">Pending Meds</div>
                            <div class="stat-value"><?php echo count($active_meds); ?></div>
                        </div>
                        <div class="stat-icon info"><i class="fas fa-circle-notch"></i></div>
                    </div>
                    <div class="stat-note">Active reminders</div>
                </div>
                <div class="stat-card warn">
                    <div class="stat-head">
                        <div>
                            <div class="stat-label">BMI</div>
                            <div class="stat-value"><?php echo $bmi_latest ? w_escape($bmi_latest['bmi']) : '—'; ?></div>
                        </div>
                        <div class="stat-icon warn"><i class="fas fa-weight-scale"></i></div>
                    </div>
                    <div class="stat-note"><?php echo $bmi_latest ? w_escape($bmi_latest['cat']) : 'Not logged'; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-head">
                        <div>
                            <div class="stat-label">Mood</div>
                            <div class="stat-value" style="font-size:1.3rem"><?php echo $mood_latest ? w_escape($mood_latest['mood']) : '—'; ?></div>
                        </div>
                        <div class="stat-icon" style="background:var(--warning)"><i class="fas fa-face-smile"></i></div>
                    </div>
                    <div class="stat-note">Latest entry</div>
                </div>
            </div>

            <section class="card">
                <div class="card-header"><div class="card-icon"><i class="fas fa-sliders"></i></div><h3>Log Your Vitals</h3></div>
                <form method="POST" class="modern-form" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr))" id="bmiForm">
                    <div class="field">
                        <label>Weight (kg)</label>
                        <input type="number" name="weight" step="0.1" min="1" placeholder="e.g. 70" required>
                    </div>
                    <div class="field">
                        <label>Height (cm)</label>
                        <input type="number" name="height_cm" step="0.1" min="30" placeholder="e.g. 170" required>
                    </div>
                    <button type="submit" name="w_add_bmi" class="btn btn-primary">Save BMI</button>
                </form>
            </section>

            <section class="card">
                <div class="card-header"><div class="card-icon"><i class="fas fa-heartbeat"></i></div><h3>Vitals Logger</h3></div>
                <div class="card-grid">
                    <div class="info-item" style="cursor:pointer;background:var(--surface);border-color:var(--border)" onclick="document.getElementById('bpForm').scrollIntoView({behavior:'smooth'})">
                        <div class="info-label">Blood Pressure</div><div class="info-value" style="font-size:1.1rem"><?php echo $bp_latest ? w_escape($bp_latest['sys'].'/'.$bp_latest['dia']) : '—'; ?></div>
                    </div>
                    <div class="info-item" style="cursor:pointer" onclick="document.getElementById('hrForm').scrollIntoView({behavior:'smooth'})">
                        <div class="info-label">Heart Rate</div><div class="info-value" style="font-size:1.1rem"><?php echo $hr_latest ? w_escape($hr_latest['bpm']).' BPM' : '—'; ?></div>
                    </div>
                    <div class="info-item" style="cursor:pointer" onclick="document.getElementById('tempForm').scrollIntoView({behavior:'smooth'})">
                        <div class="info-label">Temperature</div><div class="info-value" style="font-size:1.1rem">—</div>
                    </div>
                    <div class="info-item" style="cursor:pointer" onclick="document.getElementById('gluForm').scrollIntoView({behavior:'smooth'})">
                        <div class="info-label">Glucose</div><div class="info-value" style="font-size:1.1rem">—</div>
                    </div>
                </div>

                <form id="bpForm" method="POST" class="modern-form" style="margin-top:1rem;grid-template-columns:repeat(auto-fit,minmax(170px,1fr))">
                    <div class="field"><label>Systolic</label><input type="number" name="bp_sys" min="50" max="260" placeholder="120" required></div>
                    <div class="field"><label>Diastolic</label><input type="number" name="bp_dia" min="30" max="160" placeholder="80" required></div>
                    <button type="submit" name="w_add_bp" class="btn btn-primary">Log BP</button>
                </form>
                <form id="hrForm" method="POST" class="modern-form" style="margin-top:1rem;grid-template-columns:repeat(auto-fit,minmax(170px,1fr))">
                    <div class="field"><label>Heart Rate (BPM)</label><input type="number" name="hr_bpm" min="30" max="220" placeholder="72" required></div>
                    <button type="submit" name="w_add_hr" class="btn btn-primary">Log HR</button>
                </form>
                <form id="tempForm" method="POST" class="modern-form" style="margin-top:1rem;grid-template-columns:repeat(auto-fit,minmax(170px,1fr))">
                    <div class="field"><label>Temperature</label><input type="number" name="temp_val" step="0.1" min="30" max="45" placeholder="36.6" required></div>
                    <div class="field" style="min-height:44px;display:flex;align-items:center"><label style="margin-bottom:0">Unit</label><select name="temp_unit" style="min-height:44px;padding:0 1rem"><option value="c">°C</option><option value="f">°F</option></select></div>
                    <button type="submit" name="w_add_temp" class="btn btn-primary">Log Temp</button>
                </form>
                <form id="gluForm" method="POST" class="modern-form" style="margin-top:1rem;grid-template-columns:repeat(auto-fit,minmax(170px,1fr))">
                    <div class="field"><label>Glucose (mg/dL)</label><input type="number" name="glucose_val" step="0.1" min="20" max="600" placeholder="100" required></div>
                    <div class="field"><label>When</label><select name="glucose_when" style="min-height:44px;padding:0 1rem"><option value="fasting">Fasting</option><option value="after_meal">After Meal</option><option value="random">Random</option></select></div>
                    <button type="submit" name="w_add_glucose" class="btn btn-primary">Log Glucose</button>
                </form>
            </section>

            <section class="card" id="waterSection">
                <div class="card-header"><div class="card-icon"><i class="fas fa-glass-water"></i></div><h3>Water Intake</h3></div>
                <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
                    <div style="flex:1;min-width:200px">
                        <div class="info-label">Goal: <?php echo $water_goal; ?> glasses</div>
                        <div style="height:10px;background:color-mix(in srgb,var(--ink) 10%,var(--surface));border-radius:999px;overflow:hidden;margin-top:0.4rem;width:100%">
                            <div style="height:100%;width:<?php echo $water_pct; ?>%;background:var(--success);transition:width 600ms ease;border-radius:999px"></div>
                        </div>
                        <div class="info-note" style="font-size:0.8rem;color:var(--muted);margin-top:0.25rem"><?php echo $water_today; ?> / <?php echo $water_goal; ?> glasses today</div>
                    </div>
                    <form method="POST" class="modern-form" style="margin:0;grid-template-columns:repeat(auto-fit,minmax(150px,1fr))">
                        <div class="field"><label>Add glasses</label><input type="number" name="water_glasses" min="1" max="20" placeholder="1" required></div>
                        <button type="submit" name="w_add_water" class="btn btn-primary btn-sm">Add</button>
                    </form>
                </div>
            </section>

            <section class="card" id="medsSection">
                <div class="card-header"><div class="card-icon"><i class="fas fa-clock"></i></div><h3>Medication Reminders</h3></div>
                <?php if (!empty($active_meds)): ?>
                <div class="card-grid">
                    <?php foreach ($active_meds as $m): ?>
                    <div class="info-item" style="display:flex;align-items:center;justify-content:space-between;gap:0.6rem">
                        <div>
                            <div style="font-weight:700;color:var(--ink)"><?php echo w_escape($m['name']); ?></div>
                            <div style="font-size:0.78rem;color:var(--muted)"><?php echo w_escape($m['dose']); ?> &bull; <?php echo w_escape($m['time']); ?></div>
                        </div>
                        <form method="POST" style="margin:0">
                            <button type="submit" name="w_med_done" value="<?php echo w_escape($m['id']); ?>" class="btn btn-outline btn-sm"><i class="fas fa-check"></i> Done</button>
                            <button type="submit" name="w_med_delete" value="<?php echo w_escape($m['id']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove this reminder?')"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="empty-state"><i class="fas fa-clock"></i><h3>No active reminders</h3><p>Add medication reminders below.</p></div>
                <?php endif; ?>
                <form method="POST" class="modern-form" style="margin-top:1rem;grid-template-columns:repeat(auto-fit,minmax(180px,1fr))">
                    <div class="field"><label>Medicine name</label><input type="text" name="med_name" placeholder="e.g. Paracetamol" required></div>
                    <div class="field"><label>Time</label><input type="time" name="med_time" required></div>
                    <div class="field"><label>Dose</label><input type="text" name="med_dose" placeholder="e.g. 500mg" required></div>
                    <button type="submit" name="w_add_med" class="btn btn-primary">Add Reminder</button>
                </form>
                <?php if ($wellness_notifications): ?>
                <p class="info-label" style="font-size:0.78rem;margin-top:0.5rem"><i class="fas fa-bell"></i> Browser notifications enabled for reminders.</p>
                <?php endif; ?>
            </section>

            <section class="card" id="moodSection">
                <div class="card-header"><div class="card-icon"><i class="fas fa-face-smile-beam"></i></div><h3>Symptom & Mood Logger</h3></div>
                <div class="card-grid">
                    <div style="min-width:0">
                        <form method="POST" class="modern-form">
                            <div class="field"><label>Symptom</label><input type="text" name="sym_name" placeholder="e.g. Headache" required></div>
                            <div class="field"><label>Severity</label><select name="sym_severity" style="min-height:44px;padding:0 1rem"><option value="Mild">Mild</option><option value="Moderate">Moderate</option><option value="Severe">Severe</option></select></div>
                            <div class="field"><label>Notes</label><textarea name="sym_note" placeholder="Optional notes"></textarea></div>
                            <button type="submit" name="w_add_symptom" class="btn btn-primary">Log Symptom</button>
                        </form>
                    </div>
                    <div style="min-width:0">
                        <div class="field" style="min-height:auto"><label>How do you feel today?</label></div>
                        <div class="mood-grid" id="moodGrid">
                            <?php $moods = [['😀','Great'],['🙂','Good'],['😐','Neutral'],['😕','Low'],['😔','Sad'],['😡','Angry']]; ?>
                            <?php foreach ($moods as $mi => $mo): ?>
                            <button type="button" class="mood-btn<?php echo $mood_latest && $mood_latest['mood'] === $mo[0] ? ' active' : ''; ?>" onclick="selectMood('<?php echo $mo[0]; ?>', this)"><span style="font-size:1.4rem"><?php echo $mo[0]; ?></span><span class="mood-label"><?php echo $mo[1]; ?></span></button>
                            <?php endforeach; ?>
                            <input type="hidden" name="mood" id="moodValue" value="">
                        </div>
                        <div class="field" style="margin-top:0.5rem"><label>Note</label><input type="text" name="mood_note" placeholder="Add a note"></div>
                        <button type="submit" name="w_add_mood" class="btn btn-primary">Save Mood</button>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-header"><div class="card-icon"><i class="fas fa-user-friends"></i></div><h3>Emergency Contacts</h3></div>
                <?php if (!empty($W['emergency'])): ?>
                <div class="card-grid">
                    <?php foreach ($W['emergency'] as $e): ?>
                    <div class="info-item" style="display:flex;align-items:center;justify-content:space-between;gap:0.6rem">
                        <div>
                            <div style="font-weight:700;color:var(--ink)"><?php echo w_escape($e['name']); ?></div>
                            <div style="font-size:0.78rem;color:var(--muted)"><?php echo w_escape($e['relation']); ?> &bull; <a href="tel:<?php echo w_escape($e['phone']); ?>" style="color:var(--primary);font-weight:700"><?php echo w_escape($e['phone']); ?></a></div>
                        </div>
                        <form method="POST" style="margin:0"><button type="submit" name="w_em_delete" value="<?php echo w_escape($e['id']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove this contact?')"><i class="fas fa-trash"></i></button></form>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="empty-state"><i class="fas fa-user-friends"></i><h3>No emergency contacts</h3><p>Add contacts below.</p></div>
                <?php endif; ?>
                <form method="POST" class="modern-form" style="margin-top:1rem;grid-template-columns:repeat(auto-fit,minmax(180px,1fr))">
                    <div class="field"><label>Name</label><input type="text" name="em_name" placeholder="e.g. Spouse" required></div>
                    <div class="field"><label>Phone</label><input type="tel" name="em_phone" placeholder="e.g. +1 555-0000" required></div>
                    <div class="field"><label>Relation</label><input type="text" name="em_relation" placeholder="e.g. Mother" required></div>
                    <button type="submit" name="w_add_emergency" class="btn btn-primary">Add Contact</button>
                </form>
            </section>

            <section class="card">
                <div class="card-header"><div class="card-icon"><i class="fas fa-clock-rotate-left"></i></div><h3>Recent Activity</h3></div>
                <?php if (!empty($W['activity'])): ?>
                <div style="display:flex;flex-direction:column;gap:0.6rem">
                    <?php foreach (array_reverse(array_slice($W['activity'], -12)) as $act): ?>
                    <div style="display:flex;align-items:center;gap:0.7rem;padding:0.6rem;border-radius:10px;border:1px solid var(--border);background:color-mix(in srgb,var(--ink) 3%,var(--surface))">
                        <i class="fas fa-circle" style="font-size:0.45rem;color:var(--primary);"></i>
                        <div style="flex:1"><div style="font-size:0.88rem;color:var(--ink)"><?php echo w_escape($act['text']); ?></div><div style="font-size:0.7rem;color:var(--muted)"><?php echo w_escape($act['time']); ?></div></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="empty-state"><i class="fas fa-clock"></i><h3>No activity yet</h3><p>Start logging your vitals.</p></div>
                <?php endif; ?>
            </section>

            <section class="card">
                <div class="card-header"><div class="card-icon"><i class="fas fa-question-circle"></i></div><h3>Quick Health Quiz</h3></div>
                <p style="font-size:0.9rem;color:var(--muted);margin-bottom:0.9rem">Answer 3 quick questions to see how you're doing.</p>
                <form method="POST" class="modern-form" style="grid-template-columns:1fr;max-width:520px" onsubmit="return runQuiz(event, this)">
                    <div class="field"><label>1. How many days per week do you exercise 30+ minutes?</label>
                        <select name="q1" style="min-height:44px;padding:0 1rem"><option value="0">0 days</option><option value="1">1-2 days</option><option value="3">3-4 days</option><option value="5">5+ days</option></select></div>
                    <div class="field"><label>2. Servings of fruits/vegetables per day?</label>
                        <select name="q2" style="min-height:44px;padding:0 1rem"><option value="0">0-1</option><option value="2">2-3</option><option value="4">4-5</option><option value="6">6+</option></select></div>
                    <div class="field"><label>3. Average hours of sleep per night?</label>
                        <select name="q3" style="min-height:44px;padding:0 1rem"><option value="0">&lt;5 hrs</option><option value="2">5-6 hrs</option><option value="4">7-8 hrs</option><option value="6">9+ hrs</option></select></div>
                    <button type="submit" class="btn btn-primary">Get Result</button>
                </form>
            </section>
            <!-- ============ UPDATE PROFILE VIEW ============ -->
            <?php elseif (isset($_GET['action']) && $_GET['action'] === 'update'): ?>
            <header class="content-header">
                <div class="content-title-wrap">
                    <h1>Update Profile</h1>
                    <p>Keep your personal and contact information current.</p>
                </div>
                <div class="content-actions">
                    <a href="?action=dashboard" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Back</a>
                </div>
            </header>
            <?php if ($profile_message !== ''): ?>
            <div class="msg msg-<?php echo w_escape($profile_message_type); ?>">
                <i class="fas fa-<?php echo $profile_message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo w_escape($profile_message); ?>
                <button class="msg-close" onclick="this.closest('.msg').remove()">&times;</button>
            </div>
            <?php endif; ?>
            <section class="card form-card" style="max-width:720px;margin:0 auto">
                <form method="POST" class="modern-form">
                    <input type="hidden" name="profile_csrf_token" value="<?php echo w_escape($profile_csrf_token); ?>">
                    <div class="form-grid">
                        <div class="field"><label>Full Name</label><input type="text" name="name" value="<?php echo w_escape($name); ?>" required></div>
                        <div class="field"><label>Date of Birth</label><input type="date" name="dob" value="<?php echo w_escape($dob); ?>" required></div>
                        <div class="field"><label>Blood Group</label><input type="text" name="blood_group" value="<?php echo w_escape($blood_group); ?>" required placeholder="e.g. O+"></div>
                        <div class="field"><label>Gender</label><select name="Gender" style="min-height:44px;padding:0 1rem" required>
                            <option value="Male"<?php echo $Gender==='Male'?' selected':''; ?>>Male</option>
                            <option value="Female"<?php echo $Gender==='Female'?' selected':''; ?>>Female</option>
                            <option value="Other"<?php echo $Gender==='Other'?' selected':''; ?>>Other</option>
                        </select></div>
                        <div class="field"><label>Mobile Number</label><input type="tel" name="mobile_no" value="<?php echo w_escape($mobile_no); ?>" required></div>
                        <div class="field"><label>Email Address</label><input type="email" name="email" value="<?php echo w_escape($email); ?>" required></div>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary" style="align-self:flex-start">Save Changes</button>
                </form>
            </section>

            <!-- ============ UPDATE DISEASE VIEW ============ -->
            <?php elseif (isset($_GET['action']) && $_GET['action'] === 'update_disease'): ?>
            <header class="content-header">
                <div class="content-title-wrap">
                    <h1>Update Disease</h1>
                    <p>Record or update a medical condition.</p>
                </div>
                <div class="content-actions">
                    <a href="?action=dashboard" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Back</a>
                </div>
            </header>
            <?php if (isset($message)): ?>
            <div class="msg msg-<?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                <i class="fas fa-<?php echo strpos($message, 'successfully') !== false ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo w_escape($message); ?>
                <button class="msg-close" onclick="this.closest('.msg').remove()">&times;</button>
            </div>
            <?php endif; ?>
            <section class="card form-card" style="max-width:720px;margin:0 auto">
                <form method="POST" class="modern-form">
                    <div class="form-grid">
                        <div class="field"><label>Disease Name</label><input type="text" name="disease_name" placeholder="e.g. Asthma" required></div>
                        <div class="field"><label>Present Condition</label><select name="present_condition" style="min-height:44px;padding:0 1rem" required>
                            <option value="">Select condition</option>
                            <option value="Severe">Severe</option>
                            <option value="Mild">Mild</option>
                            <option value="Very Mild">Very Mild</option>
                            <option value="Cured">Cured</option>
                            <option value="Seasonal">Seasonal</option>
                        </select></div>
                    </div>
                    <button type="submit" name="update_disease" class="btn btn-primary" style="align-self:flex-start">Save Condition</button>
                </form>
            </section>

            <!-- ============ ADD ALLERGY VIEW ============ -->
            <?php elseif (isset($_GET['action']) && $_GET['action'] === 'add_allergy'): ?>
            <header class="content-header">
                <div class="content-title-wrap">
                    <h1>Add Allergy</h1>
                    <p>Record an allergy for your medical file.</p>
                </div>
                <div class="content-actions">
                    <a href="?action=dashboard" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Back</a>
                </div>
            </header>
            <?php if (isset($allergy_message)): ?>
            <div class="msg msg-<?php echo strpos($allergy_message, 'successfully') !== false ? 'success' : 'error'; ?>">
                <i class="fas fa-<?php echo strpos($allergy_message, 'successfully') !== false ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo w_escape($allergy_message); ?>
                <button class="msg-close" onclick="this.closest('.msg').remove()">&times;</button>
            </div>
            <?php endif; ?>
            <section class="card form-card" style="max-width:720px;margin:0 auto">
                <form method="POST" class="modern-form">
                    <div class="field"><label>Allergy Name</label><input type="text" name="allergy_name" placeholder="e.g. Penicillin" required></div>
                    <button type="submit" name="add_allergy" class="btn btn-primary" style="align-self:flex-start">Add Allergy</button>
                </form>
            </section>

            <!-- ============ VACCINE TRACKER VIEW ============ -->
            <?php elseif (isset($_GET['action']) && $_GET['action'] === 'update_vaccine'): ?>
            <header class="content-header">
                <div class="content-title-wrap">
                    <h1>Vaccine Tracker</h1>
                    <p>Add a vaccination record.</p>
                </div>
                <div class="content-actions">
                    <a href="?action=dashboard" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Back</a>
                </div>
            </header>
            <?php if (isset($vaccine_message)): ?>
            <div class="msg msg-<?php echo strpos($vaccine_message, 'successfully') !== false ? 'success' : 'error'; ?>">
                <i class="fas fa-<?php echo strpos($vaccine_message, 'successfully') !== false ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo w_escape($vaccine_message); ?>
                <button class="msg-close" onclick="this.closest('.msg').remove()">&times;</button>
            </div>
            <?php endif; ?>
            <section class="card form-card" style="max-width:720px;margin:0 auto">
                <form method="POST" class="modern-form">
                    <div class="form-grid">
                        <div class="field"><label>Vaccine Name</label><input type="text" name="vaccine_name" placeholder="e.g. COVID-19" required></div>
                        <div class="field"><label>Dose Number</label><input type="number" name="dose_no" min="1" placeholder="1" required></div>
                        <div class="field"><label>Administration Date</label><input type="date" name="vacc_admin_date" required></div>
                    </div>
                    <button type="submit" name="update_vaccine" class="btn btn-primary" style="align-self:flex-start">Save Record</button>
                </form>
            </section>

            <!-- ============ UPLOAD PRESCRIPTION VIEW ============ -->
            <?php elseif (isset($_GET['action']) && $_GET['action'] === 'upload_prescription'): ?>
            <header class="content-header">
                <div class="content-title-wrap">
                    <h1>Upload Prescription</h1>
                    <p>Upload a PDF prescription for your records.</p>
                </div>
                <div class="content-actions">
                    <a href="?action=dashboard" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Back</a>
                </div>
            </header>
            <?php if (isset($upload_message)): ?>
            <div class="msg msg-<?php echo strpos($upload_message, 'successfully') !== false ? 'success' : 'error'; ?>">
                <i class="fas fa-<?php echo strpos($upload_message, 'successfully') !== false ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo w_escape($upload_message); ?>
                <button class="msg-close" onclick="this.closest('.msg').remove()">&times;</button>
            </div>
            <?php endif; ?>
            <section class="card form-card" style="max-width:720px;margin:0 auto">
                <form method="POST" action="" enctype="multipart/form-data" class="modern-form">
                    <div class="field">
                        <label class="file-zone" for="prescription_file">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Choose Prescription File (PDF only)</p>
                            <input type="file" id="prescription_file" name="prescription_file" accept="application/pdf" required>
                        </label>
                        <div id="selectedFileInfo" class="selected-file" style="display:none">
                            <i class="fas fa-file-pdf"></i>
                            <span id="selectedFileName"></span>
                            <button type="button" class="remove-file-btn" id="removeFileBtn" style="margin-left:auto;padding:0.25rem;line-height:1;font-size:1.2rem" title="Remove">&times;</button>
                        </div>
                    </div>
                    <button type="submit" name="upload_prescription" class="btn btn-primary" style="align-self:flex-start">Upload Prescription</button>
                </form>
            </section>

            <?php endif; ?>

            <!-- Help / Keyboard shortcuts modal -->
            <div class="modal-backdrop" id="helpModal" aria-hidden="true">
                <div class="modal" role="dialog" aria-modal="true" aria-label="Keyboard shortcuts">
                    <h2><i class="fas fa-keyboard"></i> Keyboard Shortcuts</h2>
                    <p>Press Alt + key to navigate quickly.</p>
                    <div style="display:grid;gap:0.6rem;margin-top:1rem">
                        <div style="display:flex;justify-content:space-between;gap:1rem;padding:0.6rem;border:1px solid var(--cmrx-border);border-radius:10px;background:color-mix(in srgb,var(--cmrx-ink) 4%,var(--cmrx-surface))">
                            <span>Alt + D</span><span style="color:var(--cmrx-muted);font-size:0.85rem">Dashboard</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;gap:1rem;padding:0.6rem;border:1px solid var(--cmrx-border);border-radius:10px;background:color-mix(in srgb,var(--cmrx-ink) 4%,var(--cmrx-surface))">
                            <span>Alt + W</span><span style="color:var(--cmrx-muted);font-size:0.85rem">Wellness Hub</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;gap:1rem;padding:0.6rem;border:1px solid var(--cmrx-border);border-radius:10px;background:color-mix(in srgb,var(--cmrx-ink) 4%,var(--cmrx-surface))">
                            <span>Alt + P</span><span style="color:var(--cmrx-muted);font-size:0.85rem">Update Profile</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;gap:1rem;padding:0.6rem;border:1px solid var(--cmrx-border);border-radius:10px;background:color-mix(in srgb,var(--cmrx-ink) 4%,var(--cmrx-surface))">
                            <span>Alt + T</span><span style="color:var(--cmrx-muted);font-size:0.85rem">Toggle theme</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;gap:1rem;padding:0.6rem;border:1px solid var(--cmrx-border);border-radius:10px;background:color-mix(in srgb,var(--cmrx-ink) 4%,var(--cmrx-surface))">
                            <span>Alt + O</span><span style="color:var(--cmrx-muted);font-size:0.85rem">Overview (back to top)</span>
                        </div>
                    </div>
                    <div class="modal-actions">
                        <button class="btn btn-ghost" data-modal-close>Close</button>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script>
        // ---------- Theme (initial; interaction handled by assets/js/ui.js) ----------
        (function(){
            const root = document.documentElement;
            const icon = document.getElementById('themeIcon');
            const saved = sessionStorage.getItem('cmrx_theme') || localStorage.getItem('cmrx_theme') || '<?php echo $wellness_theme; ?>';
            root.setAttribute('data-theme', saved);
            if (icon) icon.className = saved === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        })();

        // ---------- Live date ----------
        (function(){
            const el = document.getElementById('liveDate');
            if(!el) return;
            const opts = {weekday:'short',year:'numeric',month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'};
            el.textContent = new Date().toLocaleString(undefined, opts);
            setInterval(()=>{ el.textContent = new Date().toLocaleString(undefined, opts); }, 1000);
        })();

        // ---------- Prescription file feedback ----------
        (function(){
            const input = document.getElementById('prescription_file');
            const zone = document.querySelector('.file-zone');
            const info = document.getElementById('selectedFileInfo');
            const nameEl = document.getElementById('selectedFileName');
            const rem = document.getElementById('removeFileBtn');
            if(!input) return;
            input.addEventListener('change', function(){
                if(this.files && this.files[0]){
                    nameEl.textContent = this.files[0].name;
                    info.style.display = 'flex';
                }
            });
            if(rem) rem.addEventListener('click', function(e){
                e.preventDefault();
                input.value = '';
                info.style.display = 'none';
                input.focus();
            });
        })();

        // ---------- Quick tool opener ----------
        function openTool(tool){
            const anchors = {bmi:'#bmiForm',bp:'#bpForm',hr:'#hrForm',water:'#waterSection',meds:'#medsSection',mood:'#moodSection',temp:'#tempForm',glucose:'#gluForm'};
            const id = anchors[tool];
            if(!id) return;
            if(!location.search.includes('action=wellness')){
                location.href = '?action=wellness' + id;
                return;
            }
            const el = document.querySelector(id);
            if(el) el.scrollIntoView({behavior:'smooth',block:'start'});
        }

        // ---------- Keyboard access for quick tool cards ----------
        document.addEventListener('keydown', function(e){
            if((e.key === 'Enter' || e.key === ' ') && e.target.closest('[data-tool]')){
                e.preventDefault();
                openTool(e.target.closest('[data-tool]').dataset.tool);
            }
        });

        // ---------- Mood selection ----------
        let selectedMood = '';
        function selectMood(mood, el){
            selectedMood = mood;
            document.getElementById('moodValue').value = mood;
            document.querySelectorAll('.mood-btn').forEach(b=>b.classList.remove('active'));
            el.classList.add('active');
        }

        // ---------- Quiz ----------
        function runQuiz(e, form){
            e.preventDefault();
            const fd = new FormData(form);
            const q1 = parseInt(fd.get('q1')||'0');
            const q2 = parseInt(fd.get('q2')||'0');
            const q3 = parseInt(fd.get('q3')||'0');
            let score = q1 + q2 + q3;
            let msg = '';
            if(score >= 11) msg = 'Great job! Your habits look healthy.';
            else if(score >= 7) msg = 'You\'re doing well — a little more activity or sleep could help.';
            else if(score >= 4) msg = 'There\'s room to improve. Try walking more and sleeping 7-8 hours.';
            else msg = 'Consider speaking with a healthcare provider about building healthier habits.';
            alert('Your wellness score: ' + score + '/18\n' + msg);
            return false;
        }

        // ---------- Print ----------
        document.getElementById('printBtn')?.addEventListener('click', function(){
            window.print();
        });

        // ---------- Keyboard shortcuts ----------
        document.addEventListener('keydown', function(e){
            if(e.altKey && e.key === 'd'){ e.preventDefault(); location.href='?action=dashboard'; }
            if(e.altKey && e.key === 'w'){ e.preventDefault(); location.href='?action=wellness'; }
            if(e.altKey && e.key === 'p'){ e.preventDefault(); location.href='?action=update'; }
            if(e.altKey && e.key === 't'){ e.preventDefault(); document.getElementById('themeToggle')?.click(); }
        });

        // ---------- Notification permission + reminders ----------
        (function(){
            const btn = document.getElementById('notifBtn');
            const count = document.getElementById('notifCount');
            const enabled = '<?php echo $wellness_notifications ? '1' : '0'; ?>' === '1';
            let pending = 0;
            if(enabled){
                pending = <?php echo count($active_meds); ?>;
                count.textContent = pending;
                if(pending) count.style.display = 'inline-flex';
            }
            if(btn){
                btn.addEventListener('click', async function(){
                    if(!('Notification' in window)){
                        alert('Notifications are not supported in this browser.');
                        return;
                    }
                    if(Notification.permission === 'granted'){
                        const meds = <?php echo json_encode(array_values(array_filter($W['medications'], fn($m) => !$m['done']))); ?>;
                        const now = new Date().toTimeString().slice(0,5);
                        meds.forEach(m=>{
                            if(m.time === now){
                                new Notification('Medication Reminder', {body: 'Time for ' + m.name + ' (' + m.dose + ')', icon:'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/svgs/solid/medication.svg'});
                            }
                        });
                    } else if(Notification.permission === 'default'){
                        const perm = await Notification.requestPermission();
                        if(perm === 'granted'){
                            localStorage.setItem('cmrx_notifications','1');
                            location.reload();
                        }
                    }
                });
            }
        })();
    </script>
    <script src="assets/js/ui.js"></script>
</body>
</html>
