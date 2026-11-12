<?php
session_start();

if (!isset($_SESSION['DOC_ID'])) {
    header("Location: login.php");
    exit();
}

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

$doc_id = $_SESSION['DOC_ID'];
$med_id = $_POST['med_id'];
$prescription_date = date("Y-m-d"); // Current date
$pres_file_path = "";

// Handle file upload
if ($_FILES['pres_file']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = "uploads/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $file_name = basename($_FILES['pres_file']['name']);
    $file_path = $upload_dir . $file_name;
    if (move_uploaded_file($_FILES['pres_file']['tmp_name'], $file_path)) {
        $pres_file_path = $file_path;
    } else {
        echo "Error uploading file.";
        exit();
    }
} else {
    echo "File upload error.";
    exit();
}

// Insert prescription into the database
$sql = "INSERT INTO prescription (MED_ID, DOC_ID, Prescription_Date, Pres_File_Path)
        VALUES ('$med_id', '$doc_id', '$prescription_date', '$pres_file_path')";

if ($conn->query($sql) === TRUE) {
    echo "Prescription issued successfully!";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>