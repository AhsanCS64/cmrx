<?php
// Database connection settings
$host    = 'sql310.infinityfree.com';
$db      = 'if0_43424129_cmrx';
$user    = 'if0_43424129';
$pass    = 'Kushal11199';
$port    = 3306;
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
   PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
   PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check if a valid MED_ID is provided
if (isset($_GET['med_id']) && is_numeric($_GET['med_id'])) {
    $med_id = $_GET['med_id'];

    // Retrieve patient details
    $stmt = $pdo->prepare("SELECT Name, Gender, Blood_Group FROM patient_details WHERE MED_ID = ?");
    $stmt->execute([$med_id]);
    $patient = $stmt->fetch();

    if (!$patient) {
        echo "No patient found with MED_ID: " . htmlspecialchars($med_id);
        exit;
    }

    // Retrieve patient allergies
    $stmt = $pdo->prepare("SELECT Allergy_Name FROM patient_allergies WHERE MED_ID = ?");
    $stmt->execute([$med_id]);
    $allergies = $stmt->fetchAll();

    // Retrieve disease history
    $stmt = $pdo->prepare("SELECT Disease_Name, Present_Condition FROM disease_history WHERE MED_ID = ?");
    $stmt->execute([$med_id]);
    $diseases = $stmt->fetchAll();
} else {
    echo "Please provide a valid MED_ID.";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Patient Details</title>
    <style>
        .patient-box {
            border: 1px solid #ccc;
            padding: 15px;
            margin-bottom: 20px;
            background-color: #f9f9f9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
           border: 1px solid #ccc;
        }
        th, td {
           padding: 8px;
           text-align: left;
        }
    </style>
    <link rel="icon" type="image/webp" href="assets/cmrx-logo.webp">
    <link rel="stylesheet" href="assets/css/presentation.css">
</head>
<body>
<header><a href="Homepage.php"><span class="page-logo"><img src="assets/cmrx-logo.webp" width="420" height="280" alt="CMRx"></span></a></header>
    <h1>Patient Details for MED_ID: <?php echo htmlspecialchars($med_id); ?></h1>
    
    <!-- Patient Details Box -->
    <div class="patient-box">
        <h2>Basic Details</h2>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($patient['Name']); ?></p>
        <p><strong>Gender:</strong> <?php echo htmlspecialchars($patient['Gender']); ?></p>
        <p><strong>Blood Group:</strong> <?php echo htmlspecialchars($patient['Blood_Group']); ?></p>
    </div>

    <!-- Patient Allergies Table -->
    <div class="allergies">
        <h2>Allergies</h2>
        <?php if (!empty($allergies)) { ?>
            <table>
                <tr>
                    <th>Allergy Name</th>
                </tr>
                <?php foreach ($allergies as $allergy) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($allergy['Allergy_Name']); ?></td>
                    </tr>
                <?php } ?>
            </table>
        <?php } else { ?>
            <p>No allergies found.</p>
        <?php } ?>
    </div>

    <!-- Disease History Table -->
    <div class="disease-history">
        <h2>Disease History</h2>
        <?php if (!empty($diseases)) { ?>
            <table>
                <tr>
                    <th>Disease Name</th>
                    <th>Present Condition</th>
                </tr>
                <?php foreach ($diseases as $disease) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($disease['Disease_Name']); ?></td>
                        <td><?php echo htmlspecialchars($disease['Present_Condition']); ?></td>
                    </tr>
                <?php } ?>
            </table>
        <?php } else { ?>
            <p>No disease history found.</p>
        <?php } ?>
    </div>
</body>
</html>
