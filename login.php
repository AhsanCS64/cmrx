<?php
// Start session and prevent caching
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

// Redirect to dashboard if already logged in
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: dashboard.php");
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

$error = ''; // Initialize error variable

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $med_id = isset($_POST['med_id']) ? trim($_POST['med_id']) : '';
    $acc_pass = isset($_POST['acc_pass']) ? trim($_POST['acc_pass']) : '';

    // Basic input validation
    if (empty($med_id) || empty($acc_pass)) {
        $error = 'Both MED_ID and Password are required.';
    } else {
        // Check if MED_ID exists and fetch the password
        $stmt = $con->prepare("SELECT acc_pass FROM patient_details WHERE MED_ID = ?");
        if ($stmt) {
            $stmt->bind_param("i", $med_id); // Use "s" if MED_ID is stored as string
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                $stmt->bind_result($stored_pass);
                $stmt->fetch();

                $passwordOk = false;
                if (!empty($stored_pass) && str_starts_with($stored_pass, '$2y$')) {
                    $passwordOk = @password_verify($acc_pass, $stored_pass);
                }
                if (!$passwordOk && $acc_pass === $stored_pass) {
                    $passwordOk = true;
                }

                if ($passwordOk) {
                    session_regenerate_id(true);
                    $_SESSION['loggedin'] = true;
                    $_SESSION['med_id'] = $med_id;
                    $_SESSION['login_time'] = time();

                    header("Location: dashboard.php");
                    exit;
                } else {
                    $error = 'Incorrect password.';
                }
            } else {
                $error = 'MED_ID not found.';
            }
            $stmt->close();
        } else {
            error_log("DB Error: " . $con->error);
            $error = 'A server error occurred. Please try again later.';
        }
    }
}
?>


<?php
$loginRole = 'Patient';
$loginIdentifier = 'med_id';
$loginIdentifierLabel = 'Medical ID';
$loginPasswordField = 'acc_pass';
$loginFeedback = $error ?? '';
$loginRegistration = 'signup.php';
$loginDescription = 'Access your prescriptions, medical history and wellness tools.';
require __DIR__ . '/partials/login-view.php';
?>
<?php
$con->close();
?>