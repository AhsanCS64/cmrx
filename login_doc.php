<?php
session_start(); // Start session to store doctor details

$servername = "sql310.infinityfree.com";
$username = "if0_43424129";
$password = "Kushal11199";
$port = 3306;
$dbname = "if0_43424129_cmrx";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $doc_id = (int)($_POST['doc_id'] ?? 0);
    $doc_pass = $_POST['doc_pass'] ?? '';

    if ($doc_id <= 0 || $doc_pass === '') {
        $error_message = "Please enter both Doctor ID and Password.";
    } else {
        // Query to check doctor credentials
        $sql = "SELECT * FROM doctor_details WHERE DOC_ID=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $doc_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stored_pass = $row['doc_pass'] ?? '';
            $passwordOk = false;

            if (!empty($stored_pass) && str_starts_with($stored_pass, '$2y$')) {
                $passwordOk = @password_verify($doc_pass, $stored_pass);
            }
            if (!$passwordOk && $doc_pass === $stored_pass) {
                $passwordOk = true;
            }

            if ($passwordOk) {
                session_regenerate_id(true);
                // Store doctor details in session
                $_SESSION['DOC_ID'] = $row['DOC_ID'];
                $_SESSION['Hospital_Serial'] = $row['Hospital_Serial'];
                $_SESSION['Department_ID'] = $row['Department_ID'];
                $_SESSION['Doc_Name'] = $row['Doc_Name'];
                $_SESSION['Certification'] = $row['Certification'];

                // Redirect to doctor dashboard
                header("Location: dashboard_doc.php");
                exit();
            } else {
                $error_message = "Invalid password";
            }
        } else {
            $error_message = "No doctor found with this DOC_ID";
        }

        $stmt->close();
    }
}

$conn->close();
?>
<?php
$loginRole = 'Doctor';
$loginIdentifier = 'doc_id';
$loginIdentifierLabel = 'Doctor ID';
$loginPasswordField = 'doc_pass';
$loginFeedback = $error_message ?? '';
$loginRegistration = 'signup_doc.php';
$loginDescription = 'Find patient records, issue prescriptions and manage clinical activity.';
require __DIR__ . '/partials/login-view.php';
?>
