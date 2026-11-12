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
    $doc_id = htmlspecialchars($_POST['doc_id']);
    $doc_pass = htmlspecialchars($_POST['doc_pass']);

    // Query to check doctor credentials
    $sql = "SELECT * FROM doctor_details WHERE DOC_ID=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $doc_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Check if the entered password matches the stored password
        if ($doc_pass === $row['doc_pass']) {
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

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Login</title>
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

        .login-container {
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

        .login-container::before {
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

        .btn-login {
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

        .btn-login:hover {
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
    <div class="login-container">
        <h2>Doctor Login</h2>
        <form action="" method="post">
            <label for="doc_id">Doctor ID:</label>
            <input type="text" id="doc_id" name="doc_id" required>

            <label for="doc_pass">Password:</label>
            <input type="password" id="doc_pass" name="doc_pass" required>

            <button type="submit" class="btn-login">Log In</button>
        </form>
        <div class="form-footer">
            Don't have an account? <a href="signup_doc.php">Sign Up</a>
        </div>
        <div class="form-footer">
            Patient? <a href="login.php">Login</a>
        </div>
    </div>
</body>
</html>
