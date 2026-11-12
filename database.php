<?php
// Start session
session_start();

// Prevent browser from caching (so back button doesn't cause issues)
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies

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
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input data
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $dob = isset($_POST['dob']) ? trim($_POST['dob']) : '';
    $blood_group = isset($_POST['blood_group']) ? trim($_POST['blood_group']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
    $acc_password = isset($_POST['acc_password']) ? trim($_POST['acc_password']) : '';
    $patient_email = isset($_POST['patient_email']) ? trim($_POST['patient_email']) : '';
    $Gender = isset($_POST['Gender']) ? trim($_POST['Gender']) : '';

    // Check if required fields are filled
    if (empty($name) || empty($dob) || empty($blood_group) || empty($address) || empty($contact) || empty($acc_password) || empty($patient_email) || empty($Gender)) {
        echo "<script>alert('All fields are required.');</script>";
        exit;
    }

    // Begin transaction
    $con->begin_transaction();

    try {
        // Insert primary details into patient_details table
        $stmt = $con->prepare("INSERT INTO patient_details (name, dob, blood_group, acc_pass, Gender) 
                                VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssss", $name, $dob, $blood_group, $acc_password, $Gender);
            $stmt->execute();

            // Get the auto-generated MED_ID
            $med_id = $stmt->insert_id;
            $stmt->close();
        } else {
            throw new Exception("Error preparing patient_details statement: " . $con->error);
        }

        // Insert address into patient_address table
        $stmt = $con->prepare("INSERT INTO patient_address (MED_ID, address) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("is", $med_id, $address);
            $stmt->execute();
            $stmt->close();
        } else {
            throw new Exception("Error preparing patient_address statement: " . $con->error);
        }

        // Insert contact into patient_mobile table
        $stmt = $con->prepare("INSERT INTO patient_mobile (MED_ID, mobile_no) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("is", $med_id, $contact);
            $stmt->execute();
            $stmt->close();
        } else {
            throw new Exception("Error preparing patient_mobile statement: " . $con->error);
        }

        // Insert email into patient_email table
        $stmt = $con->prepare("INSERT INTO patient_email (MED_ID, Email) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("is", $med_id, $patient_email);
            $stmt->execute();
            $stmt->close();
        } else {
            throw new Exception("Error preparing patient_email statement: " . $con->error);
        }

        // Commit the transaction
        $con->commit();

        // Display success message with MED_ID in a popup and redirect to login.php
        echo "<script>
            alert('Signup successful! Your MED_ID is: $med_id');
            setTimeout(function() {
                window.location.href = 'login.php';
            }, 1000);  // Redirect after 1 second
        </script>";
        exit;

    } catch (Exception $e) {
        // Rollback transaction in case of error
        $con->rollback();
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
    }

    // Close the database connection
    $con->close();
}
?>
