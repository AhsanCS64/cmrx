<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database connection
    $host = "sql310.infinityfree.com";
    $user = "if0_43424129";
    $pass = "Kushal11199";
    $dbname = "if0_43424129_cmrx";
    $port = 3306;

    $conn = new mysqli($host, $user, $pass, $dbname, $port);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Get form values
    $name         = $_POST['name'];
    $dob          = $_POST['dob'];
    $blood_group  = $_POST['blood_group'];
    $gender       = $_POST['Gender'];
    $address      = $_POST['address'];
    $contact      = $_POST['contact'];
    $email        = $_POST['patient_email'];
    $password     = $_POST['acc_password'];

    // Insert into patient_details
    $sql1 = "INSERT INTO patient_details (Name, Gender, DOB, Blood_Group, acc_pass)
             VALUES (?, ?, ?, ?, ?)";
    $stmt1 = $conn->prepare($sql1);
    $stmt1->bind_param("sssss", $name, $gender, $dob, $blood_group, $password);

    if ($stmt1->execute()) {
        // Get MED_ID
        $med_id = $stmt1->insert_id;

        // Insert address
        $stmt2 = $conn->prepare("INSERT INTO patient_address (MED_ID, Address) VALUES (?, ?)");
        $stmt2->bind_param("is", $med_id, $address);
        $stmt2->execute();

        // Insert mobile
        $stmt3 = $conn->prepare("INSERT INTO patient_mobile (MED_ID, Mobile_No) VALUES (?, ?)");
        $stmt3->bind_param("is", $med_id, $contact);
        $stmt3->execute();

        // Insert email
        $stmt4 = $conn->prepare("INSERT INTO patient_email (MED_ID, Email) VALUES (?, ?)");
        $stmt4->bind_param("is", $med_id, $email);
        $stmt4->execute();

        // Show MED_ID in popup and redirect
        echo "<script>
                alert('Signup successful! Your MED_ID is: {$med_id}');
                window.location='login.php';
              </script>";
        exit;
    } else {
        echo "Error: " . $stmt1->error;
    }

    $stmt1->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">
    <title>Patient Signup Form</title>
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
            min-height: 100vh;
            background: linear-gradient(135deg, #28a745, #218838);
            padding: 20px;
        }

        .signup-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            width: 350px;
            text-align: center;
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        h2 {
            color: #218838;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 15px;
            text-align: left;
            position: relative;
        }

        label {
            display: block;
            color: #218838;
            font-weight: 400;
            margin-bottom: 5px;
        }

        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #218838;
            border-radius: 5px;
            outline: none;
            background: rgba(255, 255, 255, 0.8);
            font-size: 16px;
        }

        input:focus, select:focus {
            border-color: #28a745;
            box-shadow: 0 0 8px rgba(40, 167, 69, 0.5);
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
            transition: 0.3s;
        }

        .submit-btn:hover {
            background: #218838;
            transform: scale(1.05);
        }

        .signup-link {
            margin-top: 15px;
            font-size: 14px;
            text-align: center;
        }

        .signup-link a {
            color: #218838;
            font-weight: bold;
            text-decoration: none;
            transition: 0.3s;
        }

        .signup-link a:hover {
            text-decoration: underline;
            color: #155724;
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .signup-container {
                padding: 20px;
                width: 90%;
            }

            h2 {
                font-size: 24px;
            }

            input, select {
                font-size: 14px;
                padding: 10px;
            }

            .submit-btn {
                font-size: 16px;
                padding: 12px;
            }
        }
    </style>
<link rel="stylesheet" href="assets/css/presentation.css">
<script src="assets/js/account.js" defer></script>
    <link rel="icon" type="image/webp" href="assets/cmrx-logo.webp">
</head>
<body class="account-page">
<main class="account-shell account-registration">
  <aside class="account-intro">
    <a class="account-home" href="Homepage.php"><img src="assets/cmrx-logo.webp" width="420" height="280" alt="CMRx home"></a>
    <div><p>Patient portal</p><span class="account-product">Centralized Medical Records</span></div>
  </aside>
    <div class="signup-container">
        <h1 class="account-heading">Create a patient account</h1>
        <p class="account-description">Enter your details to set up your CMRx account.</p>
        <form class="account-form-grid" action="signup.php" method="POST">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required>
            </div>

            <div class="form-group">
                <label for="dob">Date of Birth</label>
                <input type="date" id="dob" name="dob" required>
            </div>

            <div class="form-group">
                <label for="blood_group">Blood Group</label>
                <select id="blood_group" name="blood_group" required>
                    <option value="">Select Blood Group</option>
                    <option value="A+">A+</option>
                    <option value="A-">A-</option>
                    <option value="B+">B+</option>
                    <option value="B-">B-</option>
                    <option value="AB+">AB+</option>
                    <option value="AB-">AB-</option>
                    <option value="O+">O+</option>
                    <option value="O-">O-</option>
                </select>
            </div>

            <div class="form-group">
                <label for="Gender">Gender</label>
                <select id="Gender" name="Gender" required>
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" id="address" name="address" required>
            </div>

            <div class="form-group">
                <label for="contact">Contact Number</label>
                <input type="text" id="contact" name="contact" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="patient_email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" autocomplete="new-password" id="password" name="acc_password" required>
            </div>

            <button type="submit" class="submit-btn">Sign Up</button>
            <a href="login.php" class="login-link">Already have an account? Log In</a>
        </form>
        
    </div>
</main>
</body>
</html>
