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
        if (password_verify($entered_password, $row['acc_password'])) {
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Login</title>
    <style>
        :root {
            --primary: #3b82f6;
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #1e293b;
            --error: #ef4444;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        body {
            background: var(--bg);
            display: grid;
            place-items: center;
            min-height: 100vh;
            padding: 1rem;
        }
        .login-container {
            background: var(--card);
            border-radius: 12px;
            padding: 2.5rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .logo {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--primary);
            font-size: 1.5rem;
            font-weight: 600;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }


        .form-footer {
  margin-top: 1.5rem;
  text-align: center;
}

.form-footer-text {
  color: var(--text-light); /* Using your existing CSS variable */
  font-size: 0.875rem;
}

.form-footer-link {
  color: var(--primary); /* Using your existing primary color */
  text-decoration: none;
  font-weight: 500;
  transition: color 0.2s;
}

.form-footer-link:hover {
  color: var(--primary-dark); /* Darker shade for hover */
  text-decoration: underline;
}

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            color: var(--text);
        }
        input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
        }
        button {
            width: 100%;
            padding: 0.75rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
        }
        .error {
            color: var(--error);
            font-size: 0.875rem;
            margin-top: 1rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">Hospital Portal</div>
        <form method="POST" action="">
            <div class="form-group">
                <label for="registration_no">Registration Number</label>
                <input type="text" id="registration_no" name="registration_no" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Login</button>
            <?php if ($login_error): ?>
                <div class="error"><?php echo htmlspecialchars($login_error); ?></div>
            <?php endif; ?>

                <div class="form-footer">
      <p>Don't have an account? <a href="hospital_signup.php">Sign up here</a>.</p>
    </div>
        </form>

    </div>
</body>
</html>