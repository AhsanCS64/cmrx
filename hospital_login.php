<?php
session_start();
if (isset($_SESSION['hospital_serial'])) {
    header("Location: hospital_dashboard.php");
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

$login_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $registration_no = $_POST['registration_no'] ?? '';
    $entered_password = $_POST['password'] ?? '';

    $sql = "SELECT Hospital_Serial, Hospital_Name, Address, acc_password FROM hospital WHERE Registration_No = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $registration_no);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();
        if ($entered_password === $row['acc_password']) {
            $_SESSION['hospital_serial'] = $row['Hospital_Serial'];
            $_SESSION['hospital_name'] = $row['Hospital_Name'];
            $_SESSION['hospital_address'] = $row['Address'];
            header("Location: hospital_dashboard.php");
            exit;
        } else {
            $login_error = "Incorrect password.";
        }
    } else {
        $login_error = "Invalid registration number.";
    }
    $stmt->close();
}
$conn->close();
?>
<?php
$loginRole = 'Hospital';
$loginIdentifier = 'registration_no';
$loginIdentifierLabel = 'Registration number';
$loginPasswordField = 'password';
$loginFeedback = $login_error ?? '';
$loginRegistration = 'hospital_signup.php';
$loginDescription = 'Manage your doctor directory and review hospital clinical activity.';
require __DIR__ . '/partials/login-view.php';
?>