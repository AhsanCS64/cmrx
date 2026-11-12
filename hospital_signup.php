<?php
// Database configuration (kept the same as original)
define('DB_SERVER', 'sql310.infinityfree.com');
define('DB_USERNAME', 'if0_43424129');
define('DB_PASSWORD', 'Kushal11199');
define('DB_NAME', 'if0_43424129_cmrx');
define('DB_PORT', 3306);

// Error reporting for development (should be disabled in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize variables
$hospital_name = $address = $registration_no = $acc_password = "";
$errors = [
    'hospital_name' => '',
    'address' => '',
    'registration_no' => '',
    'acc_password' => ''
];
$registration_success = false;

// Process form when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
    $hospital_name = sanitizeInput($_POST['hospital_name'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $registration_no = sanitizeInput($_POST['registration_no'] ?? '');
    $acc_password = $_POST['acc_password'] ?? '';

    // Validate Hospital Name
    if (empty($hospital_name)) {
        $errors['hospital_name'] = "Hospital name is required.";
    } elseif (!preg_match('/^[a-zA-Z0-9\s\-\.\,]{3,100}$/', $hospital_name)) {
        $errors['hospital_name'] = "Hospital name must be 3-100 characters with only letters, numbers, spaces, hyphens, dots, and commas.";
    }

    // Validate Address
    if (empty($address)) {
        $errors['address'] = "Address is required.";
    } elseif (strlen($address) < 10 || strlen($address) > 255) {
        $errors['address'] = "Address must be between 10-255 characters.";
    }

    // Validate Registration No
    if (empty($registration_no)) {
        $errors['registration_no'] = "Registration number is required.";
    }  else {
        // Check if registration number already exists
        if (registrationNoExists($registration_no)) {
            $errors['registration_no'] = "This registration number is already in use.";
        }
    }

    // Validate Password
    if (empty($acc_password)) {
        $errors['acc_password'] = "Password is required.";
    } elseif (strlen($acc_password) < 8) {
        $errors['acc_password'] = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/[A-Z]/', $acc_password) || !preg_match('/[0-9]/', $acc_password)) {
        $errors['acc_password'] = "Password must contain at least one uppercase letter and one number.";
    }

    // If no errors, proceed with registration
    if (empty(array_filter($errors))) {
        try {
        $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);
            
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            
            // Prepared statement to prevent SQL injection
            $stmt = $conn->prepare("INSERT INTO hospital (Hospital_Name, Address, Registration_No, acc_password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $hospital_name, $address, $registration_no, $acc_password);
            
            if ($stmt->execute()) {
                $registration_success = true;
                // Clear form fields after successful registration
                $hospital_name = $address = $registration_no = $acc_password = "";
            } else {
                throw new Exception("Error: " . $stmt->error);
            }
            
            $stmt->close();
            $conn->close();
        } catch (Exception $e) {
            error_log($e->getMessage());
            $errors['general'] = "An error occurred during registration. Please try again later.";
        }
    }
}

/**
 * Sanitize user input
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Check if registration number already exists in database
 */
function registrationNoExists($registration_no) {
    try {
        $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        $stmt = $conn->prepare("SELECT Registration_No FROM hospital WHERE Registration_No = ?");
        $stmt->bind_param("s", $registration_no);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        
        $stmt->close();
        $conn->close();
        
        return $exists;
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Registration | CMRx</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #3a86ff;
            --primary-dark: #2667cc;
            --secondary: #8338ec;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --border-radius: 8px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .registration-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 520px;
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
            z-index: 1;
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .registration-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            z-index: -1;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .logo svg {
            height: 50px;
            width: 50px;
            color: var(--primary);
        }
        
        .logo h1 {
            font-size: 1.5rem;
            margin-top: 0.5rem;
            color: var(--dark);
            font-weight: 600;
        }
        
        h2 {
            text-align: center;
            margin-bottom: 1.8rem;
            color: var(--dark);
            font-weight: 600;
            font-size: 1.5rem;
            position: relative;
        }
        
        h2::after {
            content: '';
            position: absolute;
            bottom: -0.6rem;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 2px;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
            font-size: 0.875rem;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-family: 'Poppins', sans-serif;
            font-size: 0.875rem;
            transition: var(--transition);
            background-color: #f9f9f9;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(58, 134, 255, 0.2);
            background-color: white;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .error-message {
            color: var(--danger);
            font-size: 0.75rem;
            margin-top: 0.25rem;
            display: block;
        }
        
        .success-message {
            color: var(--success);
            background-color: rgba(40, 167, 69, 0.1);
            padding: 0.75rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            text-align: center;
            font-size: 0.875rem;
            border-left: 4px solid var(--success);
        }
        
        .error-message-general {
            color: var(--danger);
            background-color: rgba(220, 53, 69, 0.1);
            padding: 0.75rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            text-align: center;
            font-size: 0.875rem;
            border-left: 4px solid var(--danger);
        }
        
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 2.3rem;
            cursor: pointer;
            color: #777;
            transition: var(--transition);
        }
        
        .password-toggle:hover {
            color: var(--primary);
        }
        
        .submit-btn {
            width: 100%;
            padding: 0.875rem;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 0.5rem;
            box-shadow: 0 4px 15px rgba(58, 134, 255, 0.3);
        }
        
        .submit-btn:hover {
            background: linear-gradient(90deg, var(--primary-dark), var(--secondary));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(58, 134, 255, 0.4);
        }
        
        .submit-btn:active {
            transform: translateY(0);
        }
        
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
        }
        
        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .login-link a:hover {
            color: var(--secondary);
            text-decoration: underline;
        }
        
        .password-strength {
            margin-top: 0.5rem;
            height: 4px;
            background-color: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .strength-meter {
            height: 100%;
            width: 0;
            transition: width 0.3s ease;
        }
        
        .strength-weak {
            background-color: var(--danger);
            width: 33%;
        }
        
        .strength-medium {
            background-color: var(--warning);
            width: 66%;
        }
        
        .strength-strong {
            background-color: var(--success);
            width: 100%;
        }
        
        .password-hints {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        
        @media (max-width: 576px) {
            .registration-container {
                padding: 1.5rem;
            }
            
            h2 {
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <div class="logo">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
            </svg>
            <h1>CMRx</h1>
        </div>
        
        <h2>Hospital Registration</h2>
        
        <?php if ($registration_success): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> Hospital registered successfully! You can now login.
            </div>
        <?php elseif (isset($errors['general'])): ?>
            <div class="error-message-general">
                <i class="fas fa-exclamation-circle"></i> <?php echo $errors['general']; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="registrationForm" novalidate>
            <div class="form-group">
                <label for="hospital_name">Hospital Name *</label>
                <input type="text" id="hospital_name" name="hospital_name" required 
                       placeholder="Enter hospital name" value="<?php echo htmlspecialchars($hospital_name); ?>">
                <?php if (!empty($errors['hospital_name'])): ?>
                    <span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['hospital_name']; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="address">Address *</label>
                <textarea id="address" name="address" rows="4" required 
                          placeholder="Enter full address"><?php echo htmlspecialchars($address); ?></textarea>
                <?php if (!empty($errors['address'])): ?>
                    <span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['address']; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="registration_no">Registration Number *</label>
                <input type="text" id="registration_no" name="registration_no" required 
                       placeholder="Enter registration number" value="<?php echo htmlspecialchars($registration_no); ?>">
                <?php if (!empty($errors['registration_no'])): ?>
                    <span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['registration_no']; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="acc_password">Password *</label>
                <input type="password" id="acc_password" name="acc_password" required
                       placeholder="Create a password (min 8 characters)">
                <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                <div class="password-strength">
                    <div class="strength-meter" id="strengthMeter"></div>
                </div>
                <div class="password-hints">
                    <small>Password must contain at least 8 characters, including one uppercase letter and one number.</small>
                </div>
                <?php if (!empty($errors['acc_password'])): ?>
                    <span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['acc_password']; ?></span>
                <?php endif; ?>
            </div>
            
            <button type="submit" class="submit-btn" id="submitBtn">
                <i class="fas fa-hospital"></i> Register Hospital
            </button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="hospital_login.php">Login here</a>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('acc_password');
        const strengthMeter = document.getElementById('strengthMeter');
        
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
        
        // Password strength indicator
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Length check
            if (password.length >= 8) strength += 1;
            
            // Contains uppercase letter
            if (/[A-Z]/.test(password)) strength += 1;
            
            // Contains number
            if (/[0-9]/.test(password)) strength += 1;
            
            // Contains special character
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            
            // Update strength meter
            strengthMeter.className = 'strength-meter';
            if (password.length > 0) {
                if (strength <= 1) {
                    strengthMeter.classList.add('strength-weak');
                } else if (strength <= 2) {
                    strengthMeter.classList.add('strength-medium');
                } else {
                    strengthMeter.classList.add('strength-strong');
                }
            }
        });
        
        // Form submission indicator
        const form = document.getElementById('registrationForm');
        const submitBtn = document.getElementById('submitBtn');
        
        form.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registering...';
        });
    </script>
</body>
</html>