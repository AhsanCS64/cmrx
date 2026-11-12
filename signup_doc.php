<?php
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
    // Retrieve form data and sanitize inputs
    $doc_id = htmlspecialchars($_POST['doc_id']);  // Manually entered Doc_ID
    $hospital_serial = htmlspecialchars($_POST['hospital_serial']);
    $department_id = htmlspecialchars($_POST['department_id']);
    $doc_name = htmlspecialchars($_POST['doc_name']);
    $certification = htmlspecialchars($_POST['certification']);
    $doc_pass = htmlspecialchars($_POST['doc_pass']); // Storing password as plain text (not recommended)

    // Check if Doc_ID already exists
    $check_stmt = $conn->prepare("SELECT Doc_ID FROM doctor_details WHERE Doc_ID = ?");
    $check_stmt->bind_param("s", $doc_id);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        echo "Error: Doc_ID already exists. Please use a unique Doc_ID.";
    } else {
        // Insert new doctor record
        $stmt = $conn->prepare("INSERT INTO doctor_details (Doc_ID, Hospital_Serial, Department_ID, Doc_Name, Certification, doc_pass) 
                                VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siisss", $doc_id, $hospital_serial, $department_id, $doc_name, $certification, $doc_pass);

        if ($stmt->execute()) {
            echo "New doctor record created successfully.";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    }

    $check_stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Sign Up</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(135deg, #6c63ff, #3c39a6);
            overflow: hidden;
        }

        .signup-container {
            background: rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .signup-container::before {
            content: '';
            position: absolute;
            top: -50px;
            left: -50px;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            animation: float 6s infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(20px); }
        }

        h2 {
            color: white;
            font-weight: 600;
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: white;
            text-align: left;
        }

        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            outline: none;
        }

        input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .btn-submit {
            background: #ff6f61;
            color: white;
            font-size: 18px;
            font-weight: bold;
            padding: 12px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            width: 100%;
            transition: 0.3s ease;
            box-shadow: 0 5px 15px rgba(255, 111, 97, 0.4);
        }

        .btn-submit:hover {
            background: #ff4a3d;
        }

        .form-footer {
            margin-top: 15px;
            font-size: 14px;
            color: white;
        }

        .form-footer a {
            color: #ffdf5f;
            text-decoration: none;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <h2>Doctor Sign Up</h2>
        <form action="signup_doc.php" method="post">
            <label for="doc_id">Doctor ID:</label>
            <input type="text" id="doc_id" name="doc_id" placeholder="Enter your ID" required>

            <label for="hospital_serial">Hospital Serial:</label>
            <input type="text" id="hospital_serial" name="hospital_serial" placeholder="Hospital Serial" required>

            <label for="department_id">Department ID:</label>
            <input type="text" id="department_id" name="department_id" placeholder="Department ID" required>

            <label for="doc_name">Doctor Name:</label>
            <input type="text" id="doc_name" name="doc_name" placeholder="Your Name" required>

            <label for="certification">Certification:</label>
            <input type="text" id="certification" name="certification" placeholder="Certification (Optional)">

            <label for="doc_pass">Password:</label>
            <input type="password" id="doc_pass" name="doc_pass" placeholder="Create Password" required>

            <button type="submit" class="btn-submit">Sign Up</button>
        </form>
        <div class="form-footer">
            Already have an account? <a href="login_doc.php">Log In</a>
        </div>
    </div>
</body>
</html>