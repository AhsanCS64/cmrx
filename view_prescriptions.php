<?php

include 'database.php';

if (!isset($_SESSION['med_id'])) {
    die("Error: No med_id found in session. Please login first.");
}

$med_id = $_SESSION['med_id'];

if (!$con) {
    die("Database connection failed: " . mysqli_connect_error());
}

$con->set_charset("utf8mb4");

$query = "SELECT * FROM prescription WHERE MED_ID = ?";
$stmt = $con->prepare($query);

if (!$stmt) {
    die("Prepare failed: " . $con->error);
}

$stmt->bind_param("i", $med_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription Viewer | HealthPortal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  .container {
    max-width: 1200px;
    margin: 2.5rem auto;
    padding: 0 24px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: #1a237e;
    background: #f7faff;
  }

  .back-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    background: linear-gradient(135deg, #ffffff 0%, #eef4ff 100%);
    color: #1d4ed8;
    border: 1.5px solid rgba(29, 78, 216, 0.2);
    text-decoration: none;
    border-radius: 999px;
    padding: 0.8rem 1.25rem;
    font-weight: 700;
    margin: 1rem 0 1.5rem;
    box-shadow: 0 8px 22px rgba(29, 78, 216, 0.12);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  .back-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(29, 78, 216, 0.16);
  }

  .prescription-card-container {
    background: #ffffff;
    border-radius: 16px;
    box-shadow:
      0 4px 12px rgba(75, 108, 183, 0.15),
      0 1px 6px rgba(24, 40, 72, 0.07);
    overflow: hidden;
    border: 1.5px solid #4b6cb7;
  }

  .prescription-header {
    background: linear-gradient(135deg, #3f51b5 0%, #1a237e 100%);
    color: white;
    padding: 1.75rem 2.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 3px 12px rgba(29, 45, 110, 0.35);
    font-weight: 700;
    font-size: 1.9rem;
    letter-spacing: 0.02em;
  }

  .header-content {
    display: flex;
    align-items: center;
    gap: 1.25rem;
  }

  .header-content h1 {
    margin: 0;
    font-size: 2rem;
  }

  .user-info .badge {
    background: rgba(255 255 255 / 0.25);
    padding: 0.5rem 1.25rem;
    border-radius: 24px;
    font-size: 1rem;
    font-weight: 600;
    backdrop-filter: blur(6px);
    border: 1px solid rgba(255 255 255 / 0.4);
    transition: background-color 0.3s ease;
  }

  .user-info .badge:hover {
    background: rgba(255 255 255 / 0.45);
  }

  .prescription-count {
    font-size: 1rem;
    opacity: 0.85;
    font-weight: 500;
  }

  .prescription-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 2rem;
    padding: 2.5rem 3rem;
  }

  .modern-prescription-card {
    background: #ffffff;
    border-radius: 14px;
    box-shadow:
      0 8px 16px rgba(75, 108, 183, 0.12),
      0 3px 8px rgba(24, 40, 72, 0.06);
    overflow: hidden;
    transition:
      transform 0.35s cubic-bezier(0.4, 0, 0.2, 1),
      box-shadow 0.35s cubic-bezier(0.4, 0, 0.2, 1),
      border-color 0.3s ease;
    border: 2px solid transparent;
    cursor: pointer;
  }

  .modern-prescription-card:hover {
    transform: translateY(-8px);
    box-shadow:
      0 14px 32px rgba(75, 108, 183, 0.25),
      0 6px 16px rgba(24, 40, 72, 0.12);
    border-color: #3f51b5;
  }

  .card-top {
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #e0e7ff;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .prescription-number {
    font-weight: 700;
    color: #1a237e;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 0.6rem;
  }

  .prescription-number i {
    color: #3f51b5;
    font-size: 1.3rem;
  }

  .prescription-date {
    color: #5c6bc0;
    font-size: 0.95rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.4rem;
  }

  .card-middle {
    padding: 1.8rem 2.5rem;
  }

  .file-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
  }

  .file-info i {
    color: #3f51b5;
    font-size: 1.4rem;
  }

  .filename {
    font-size: 1rem;
    color: #303f9f;
    word-break: break-word;
    font-weight: 600;
  }

  .status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.55rem 1.3rem;
    border-radius: 28px;
    font-size: 0.9rem;
    font-weight: 700;
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    color: #0d47a1;
    border: 1.5px solid #90caf9;
    box-shadow:
      0 2px 6px rgba(33, 150, 243, 0.3);
    user-select: none;
  }

  .status-badge.active {
    background: linear-gradient(135deg, #bbdefb 0%, #64b5f6 100%);
    color: #0d47a1;
    border-color: #42a5f5;
    box-shadow:
      0 3px 10px rgba(66, 165, 245, 0.5);
  }

  .card-bottom {
    padding: 1.3rem 2rem;
    background: #f0f4ff;
    border-top: 1px solid #c5cae9;
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
  }

  .action-buttons {
    display: flex;
    gap: 1rem;
  }

  .view-btn, .download-btn {
    padding: 0.65rem 1.6rem;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    text-decoration: none;
    transition: all 0.25s ease;
    box-shadow: 0 2px 8px rgba(75, 108, 183, 0.2);
    user-select: none;
  }

  .view-btn {
    background: #ffffff;
    color: #3f51b5;
    border: 2px solid #3f51b5;
  }

  .view-btn:hover {
    background: #e8f0ff;
    box-shadow: 0 4px 14px rgba(63, 81, 181, 0.4);
  }

  .download-btn {
    background: #3f51b5;
    color: white;
    border: 2px solid #3f51b5;
  }

  .download-btn:hover {
    background: #283593;
    box-shadow: 0 5px 18px rgba(40, 53, 147, 0.6);
  }

  .empty-prescriptions {
    grid-column: 1 / -1;
    text-align: center;
    padding: 4rem 2rem;
    color: #7986cb;
  }

  .empty-icon {
    font-size: 3.5rem;
    color: #9fa8da;
    margin-bottom: 1.75rem;
  }

  .empty-prescriptions h3 {
    color: #5c6bc0;
    margin-bottom: 0.75rem;
    font-weight: 700;
  }

  .empty-prescriptions p {
    color: #7986cb;
    margin-bottom: 2rem;
    font-size: 1rem;
  }

  .refresh-btn {
    background: #3f51b5;
    color: white;
    border: none;
    padding: 0.8rem 1.8rem;
    border-radius: 8px;
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.7rem;
    transition: background 0.3s ease, box-shadow 0.3s ease;
    box-shadow: 0 3px 14px rgba(63, 81, 181, 0.45);
  }

  .refresh-btn:hover {
    background: #283593;
    box-shadow: 0 6px 20px rgba(40, 53, 147, 0.75);
  }
</style>

</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-btn" aria-label="Go back to dashboard">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>

        <div class="prescription-card-container">
            <div class="prescription-header">
                <div class="header-content">
                    <h1><i class="fas fa-pills"></i> My Prescriptions</h1>
                    <div class="user-info">
                        <span class="badge"><i class="fas fa-id-card"></i> MED ID: <?php echo htmlspecialchars($med_id); ?></span>
                    </div>
                </div>
                <div class="prescription-count">
                    <span><?php echo $result->num_rows; ?> prescription(s) found</span>
                </div>
            </div>

            <div class="prescription-grid">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="modern-prescription-card">
                            <div class="card-top">
                                <div class="prescription-number">
                                    <i class="fas fa-prescription-bottle-alt"></i>
                                    <span>RX-<?php echo htmlspecialchars($row['Prescription_Serial']); ?></span>
                                </div>
                                <div class="prescription-date">
                                    <i class="far fa-calendar-alt"></i>
                                    <?php echo date('M d, Y', strtotime($row['Prescription_Date'])); ?>
                                </div>
                            </div>
                            
                            <div class="card-middle">
                                <div class="file-info">
                                    <i class="fas fa-file-medical"></i>
                                    <span class="filename"><?php echo basename(htmlspecialchars($row['Pres_File_Path'])); ?></span>
                                </div>
                                <div class="status-badge active">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Active</span>
                                </div>
                            </div>
                            
                            <div class="card-bottom">
                                <div class="action-buttons">
                                    <a href="<?php echo htmlspecialchars($row['Pres_File_Path']); ?>" target="_blank" class="view-btn">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="<?php echo htmlspecialchars($row['Pres_File_Path']); ?>" download class="download-btn">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-prescriptions">
                        <div class="empty-icon">
                            <i class="fas fa-prescription-bottle-alt"></i>
                        </div>
                        <h3>No Prescriptions Available</h3>
                        <p>Your prescription history will appear here once available.</p>
                        <button class="refresh-btn" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>