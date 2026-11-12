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

                // Compare the submitted password directly with the stored value.
               if ($acc_pass === $stored_pass) {
    session_regenerate_id(true); // ✅ Add this
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


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Patient Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
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
      background: linear-gradient(135deg, #28a745, #218838);
      overflow: hidden;
    }

    .login-container {
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(12px);
      padding: 40px 30px;
      border-radius: 20px;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
      width: 380px;
      text-align: center;
      animation: fadeInUp 0.8s ease forwards;
    }

    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }

    h2 {
      color: #218838;
      font-size: 28px;
      font-weight: 600;
      margin-bottom: 20px;
    }

    .form-group {
      margin-bottom: 20px;
      text-align: left;
    }

    label {
      display: block;
      color: #218838;
      font-weight: 500;
      margin-bottom: 6px;
    }

    input {
      width: 100%;
      padding: 12px;
      border: 1px solid #c3e6cb;
      border-radius: 10px;
      background: rgba(255, 255, 255, 0.95);
      font-size: 16px;
      transition: all 0.3s ease;
    }

    input:focus {
      border-color: #28a745;
      box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.2);
    }
    .error-message {
            color: #d32f2f;
            background-color: #fde0e0;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

    .submit-btn {
      width: 100%;
      padding: 14px;
      background: #28a745;
      color: white;
      border: none;
      border-radius: 10px;
      font-size: 17px;
      font-weight: 600;
      cursor: pointer;
      transition: 0.3s ease;
    }

    .submit-btn:hover {
      background: #218838;
      transform: scale(1.03);
      box-shadow: 0 6px 18px rgba(33, 136, 56, 0.3);
    }

    .signup-link {
      margin-top: 18px;
      font-size: 14px;
    }

    .signup-link a {
      color: #218838;
      font-weight: 600;
      text-decoration: none;
      transition: 0.3s ease;
    }

    .signup-link a:hover {
      text-decoration: underline;
      color: #1e7e34;
    }
  </style>
</head>
<body>
  <body>
  <div class="login-container">
    <h2>Patient Login</h2>
    <?php if (!empty($error)): ?>
      <div class="error-message">
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>
    <form action="login.php" method="POST">
      <div class="form-group">
        <label for="med_id">MED_ID</label>
        <input type="text" id="med_id" name="med_id" required value="<?php echo isset($_POST['med_id']) ? htmlspecialchars($_POST['med_id']) : ''; ?>">
      </div>
      <div class="form-group">
        <label for="acc_pass">Password</label>
        <input type="password" id="acc_pass" name="acc_pass" required>
      </div>
      <button type="submit" class="submit-btn">Login</button>
    </form>
    <div class="signup-link">
      <p>Don't have an account? <a href="signup.php">Sign up here</a>.</p>
    </div>
    <div class="signup-link">
      <p>Doctor? <a href="login_doc.php">Login</a>.</p>
    </div>
  </div>
</body>
</html>
<?php
$con->close();
?>