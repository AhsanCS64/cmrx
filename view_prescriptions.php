<?php

include 'database.php';

if (!isset($_SESSION['med_id'])) {
    header("Location: login.php");
    exit;
}

if (!$con) {
    die("Database connection failed.");
}

$con->set_charset("utf8mb4");

$med_id = (int)$_SESSION['med_id'];

function vp_escape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function vp_scalar($con, $sql, $types = '', $params = [], $key = 'total')
{
    $stmt = $con->prepare($sql);
    if (!$stmt) {
        return 0;
    }
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        return 0;
    }
    $value = $row[$key] ?? 0;
    return (int)$value;
}

$search = isset($_GET['search']) && is_string($_GET['search']) ? trim($_GET['search']) : '';

$totalPrescriptions = vp_scalar($con, "SELECT COUNT(*) AS total FROM prescription WHERE MED_ID = ?", 'i', [$med_id]);

if ($search !== '') {
    $query = "SELECT * FROM prescription WHERE MED_ID = ? AND (Prescription_Serial LIKE ? OR Pres_File_Path LIKE ? OR Prescription_Date LIKE ?) ORDER BY Prescription_Date DESC, Prescription_Serial DESC";
    $searchLike = "%" . $search . "%";
    $prescriptions = [];
    $stmt = $con->prepare($query);
    if ($stmt) {
        $stmt->bind_param("isss", $med_id, $searchLike, $searchLike, $searchLike);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $prescriptions[] = $row;
        }
        $stmt->close();
    }
} else {
    $query = "SELECT * FROM prescription WHERE MED_ID = ? ORDER BY Prescription_Date DESC, Prescription_Serial DESC";
    $prescriptions = [];
    $stmt = $con->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $med_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $prescriptions[] = $row;
        }
        $stmt->close();
    }
}

$filteredCount = count($prescriptions);
$connClosed = $con->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>My Prescriptions | CMRx</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
  --ink: #0f172a;
  --muted: #64748b;
  --line: #e2e8f0;
  --surface: #ffffff;
  --bg: #f8fafc;
  --primary: #2563eb;
  --primary-strong: #1d4ed8;
  --primary-soft: #dbeafe;
  --success: #059669;
  --success-light: #ecfdf5;
  --radius: 16px;
  --shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
  --soft-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
  --transition: 300ms cubic-bezier(0.4, 0, 0.2, 1);
  --max: 1000px;
}
*, *::before, *::after { box-sizing: border-box; }
html { scroll-behavior: smooth; }
body {
  margin: 0;
  font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
  background: var(--bg);
  color: var(--ink);
  line-height: 1.6;
}
a { color: inherit; text-decoration: none; }
img { max-width: 100%; display: block; }
h1, h2, h3 { margin: 0; line-height: 1.12; }
.container { width: min(var(--max), calc(100% - 2rem)); margin: 0 auto; }
.section { padding: 3rem 0 4rem; }
.section-head { margin: 0 0 2rem; text-align: center; max-width: 720px; margin-left: auto; margin-right: auto; }
.section-head h1 { font-size: clamp(2rem, 3vw, 3rem); letter-spacing: -0.04em; color: var(--ink); margin-bottom: 0.6rem; }
.section-head p { margin: 0; color: var(--muted); font-size: 1.05rem; }
.btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 0.6rem;
  border-radius: 10px; padding: 0.8rem 1.3rem; font-weight: 700; font-size: 0.92rem;
  border: 1px solid transparent; transition: var(--transition); cursor: pointer;
}
.btn:hover { transform: translateY(-2px); }
.btn-primary {
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-strong) 100%);
  color: #ffffff; box-shadow: 0 10px 20px rgba(37, 99, 235, 0.18);
}
.btn-secondary {
  background: var(--surface); color: var(--primary-strong); border-color: var(--line);
}
.btn-secondary:hover { background: color-mix(in srgb, var(--ink) 4%, var(--surface)); }
.btn-success {
  background: var(--success); color: #ffffff; box-shadow: 0 10px 20px rgba(5, 150, 105, 0.18);
}
.btn-ghost {
  background: rgba(255, 255, 255, 0.14); color: #ffffff; border-color: rgba(255, 255, 255, 0.22);
}
.btn-ghost:hover { background: rgba(255, 255, 255, 0.22); }
.btn-full { width: 100%; }
header {
  position: sticky; top: 0; z-index: 40;
  background: color-mix(in srgb, var(--surface) 82%, transparent);
  backdrop-filter: blur(14px); border-bottom: 1px solid var(--line);
}
.nav-wrap { display: flex; align-items: center; justify-content: space-between; min-height: 68px; }
.brand {
  display: inline-flex; align-items: center; gap: 0.7rem; color: var(--primary-strong);
  font-size: clamp(1.4rem, 2vw, 1.8rem); font-weight: 800; letter-spacing: -0.04em;
}
.brand i { width: 38px; height: 38px; display: grid; place-items: center; border-radius: 12px;
  background: var(--primary-soft); color: var(--primary); font-size: 1.1rem; }
.nav-actions { display: flex; align-items: center; gap: 0.7rem; }
.sr-only {
  position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
  overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;
}
.header-strip {
  display: flex; align-items: center; justify-content: space-between; gap: 1rem;
  margin-bottom: 1.5rem; flex-wrap: wrap;
}
.header-actions { display: flex; gap: 0.6rem; flex-wrap: wrap; }
.stat-band {
  display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;
}
.stat-pill {
  display: flex; align-items: center; gap: 0.65rem; padding: 0.75rem 1rem;
  border-radius: 12px; border: 1px solid var(--line); background: var(--surface);
  box-shadow: var(--soft-shadow);
}
.stat-pill .stat-icon {
  width: 34px; height: 34px; display: grid; place-items: center; border-radius: 10px;
  background: var(--primary-soft); color: var(--primary); font-size: 0.95rem;
}
.stat-pill strong { color: var(--ink); font-weight: 800; font-size: 1.15rem; }
.stat-pill span { color: var(--muted); font-size: 0.82rem; font-weight: 600; }
.card {
  background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius);
  padding: 1.5rem; box-shadow: var(--soft-shadow); transition: var(--transition);
}
.card:hover { box-shadow: 0 20px 32px rgba(15, 23, 42, 0.05); }
.card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; gap: 0.75rem; }
.card-header .card-icon {
  width: 40px; height: 40px; display: grid; place-items: center; border-radius: 12px;
  background: var(--primary-soft); color: var(--primary); font-size: 1.2rem; flex-shrink: 0;
}
.card-header h2 { font-size: 1.25rem; color: var(--ink); margin: 0; }
.search-bar {
  position: relative; max-width: 420px; width: 100%;
}
.search-bar input {
  width: 100%; min-height: 44px; padding: 0 1rem 0 2.8rem; border-radius: 10px;
  border: 1px solid var(--line); background: var(--surface); color: var(--ink);
  font-size: 0.92rem; transition: var(--transition);
  appearance: none;
}
.search-bar input::-webkit-search-cancel-button,
.search-bar input::-webkit-search-decoration,
.search-bar input::-webkit-search-results-button,
.search-bar input::-webkit-search-results-decoration {
  display: none;
}
.search-bar input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 4px color-mix(in srgb, var(--primary) 12%, transparent); }
.search-bar i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 0.95rem; }
.clear-btn {
  position: absolute; right: 0.5rem; top: 50%; transform: translateY(-50%);
  background: none; border: none; color: var(--muted); cursor: pointer; font-size: 1.1rem;
  padding: 0.3rem; border-radius: 6px; transition: color 0.2s ease;
}
.clear-btn:hover { color: var(--ink); }
.prescription-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.25rem;
}
.prescription-card {
  background: var(--surface); border: 1px solid var(--line); border-radius: 20px;
  overflow: hidden; box-shadow: var(--soft-shadow);
  transition: transform 0.25s ease, box-shadow 0.25s ease; display: flex; flex-direction: column;
}
.prescription-card:hover { transform: translateY(-4px); box-shadow: 0 20px 32px rgba(15, 23, 42, 0.05); }
.rx-header {
  background: linear-gradient(135deg, var(--primary-soft), #e3f2fd);
  padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;
}
.rx-header .rx-badge {
  width: 40px; height: 40px; display: grid; place-items: center; border-radius: 10px;
  background: color-mix(in srgb, var(--primary) 15%, transparent); color: var(--primary-strong);
  font-size: 1.25rem; flex-shrink: 0;
}
.rx-header .rx-info strong { color: var(--ink); font-size: 1.05rem; font-weight: 800; display: block; }
.rx-header .rx-info span { color: var(--muted); font-size: 0.85rem; }
.rx-date { font-size: 0.85rem; font-weight: 700; color: var(--primary-strong); white-space: nowrap; }
.rx-body { padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem; flex: 1; }
.rx-file {
  display: flex; align-items: center; gap: 0.75rem; padding: 0.9rem 1rem;
  border-radius: 12px; border: 1px solid var(--line); background: var(--bg);
}
.rx-file i { color: var(--primary); font-size: 1.2rem; flex-shrink: 0; }
.rx-file .filename { flex: 1; font-size: 0.92rem; color: var(--ink); font-weight: 600;
  overflow-wrap: break-word; word-break: break-all; }
.rx-file .file-meta { font-size: 0.78rem; color: var(--muted); }
.rx-actions {
  display: flex; gap: 0.6rem; padding: 1rem 1.25rem; border-top: 1px solid var(--line);
  background: color-mix(in srgb, var(--ink) 3%, var(--surface));
}
.rx-actions .btn { flex: 1; }
.empty-state {
  grid-column: 1 / -1; text-align: center; padding: 4rem 1.5rem; color: var(--muted);
}
.empty-state i { font-size: 3rem; color: color-mix(in srgb, var(--ink) 30%, transparent);
  margin-bottom: 1rem; display: block; opacity: 0.6; }
.empty-state h3 { color: var(--ink); font-size: 1.25rem; font-weight: 800; margin-bottom: 0.5rem; }
.empty-state p { margin: 0 0 1.5rem; font-size: 0.98rem; }
.filtered-note {
  margin-top: 1rem; font-size: 0.88rem; color: var(--muted); text-align: center;
}
@media (max-width: 700px) {
  .container { padding: 0 1rem; }
  .section { padding: 2rem 0 3rem; }
  .header-strip { flex-direction: column; align-items: stretch; gap: 1rem; }
  .header-actions { justify-content: center; }
  .stat-band { grid-template-columns: 1fr 1fr; display: grid; }
  .stat-pill { justify-content: center; }
  .search-bar { max-width: 100%; }
  .prescription-grid { grid-template-columns: 1fr; }
  .rx-actions { flex-direction: column; }
  .empty-state { padding: 3rem 1rem; }
}
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after { transition-duration: 0.01ms !important; animation-duration: 0.01ms !important; scroll-behavior: auto !important; }
}
</style>
<link rel="stylesheet" href="assets/css/presentation.css">
    <link rel="icon" type="image/webp" href="assets/cmrx-logo.webp">
</head>
<body class="prescription-view">
<header>
  <div class="container nav-wrap">
    <a href="dashboard.php" class="brand" aria-label="Back to patient dashboard"><i class="fas fa-arrow-left" aria-hidden="true"></i><span class="page-logo"><img src="assets/cmrx-logo.webp" width="420" height="280" alt="CMRx"></span></a>
    <div class="nav-actions">
      <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-home"></i> Dashboard</a>
    </div>
  </div>
</header>

<div class="container section">
  <div class="section-head">
    <h1><i class="fas fa-prescription-bottle-medical" style="margin-right:0.6rem;color:var(--primary);"></i>My Prescriptions</h1>
    <p>View and download your issued prescription documents.</p>
  </div>

  <div class="header-strip">
    <div class="stat-band">
      <div class="stat-pill">
        <div class="stat-icon"><i class="fas fa-file-medical"></i></div>
        <div><strong><?php echo vp_escape($filteredCount); ?></strong><span> Prescriptions</span></div>
      </div>
<div class="stat-pill">
  <div class="stat-icon"><i class="fas fa-id-card"></i></div>
  <div><strong>Your Medical ID </strong><span><?php echo vp_escape($med_id); ?></span></div>
</div>
    </div>
    <div class="header-actions">
      <a href="javascript:window.print()" class="btn btn-ghost" title="Print prescriptions"><i class="fas fa-print"></i> Print</a>
      <form action="view_prescriptions.php" method="get" class="search-bar">
        <i class="fas fa-search"></i>
        <input type="search" name="search" id="prescriptionSearch" placeholder="Search by RX number, filename, or date…" value="<?php echo vp_escape($search); ?>" autocomplete="off">
        <button type="button" id="clearSearch" class="clear-btn" aria-label="Clear search"<?php echo $search === '' ? ' style="display:none"' : ''; ?>>×</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-icon"><i class="fas fa-prescription-bottle-medical"></i></div>
      <h2>Prescription History</h2>
    </div>
    <div class="prescription-grid">
      <?php if ($filteredCount > 0): ?>
        <?php foreach ($prescriptions as $row): ?>
          <div class="prescription-card">
            <div class="rx-header">
              <div class="rx-badge"><i class="fas fa-hashtag"></i></div>
              <div class="rx-info">
                <strong>RX-<?php echo vp_escape($row['Prescription_Serial']); ?></strong>
                <span>Prescription record</span>
              </div>
              <div class="rx-date"><?php echo date('M d, Y', strtotime($row['Prescription_Date'])); ?></div>
            </div>
            <div class="rx-body">
              <div class="rx-file">
                <i class="fas fa-file-medical"></i>
                <div>
                  <span class="filename"><?php echo vp_escape(basename($row['Pres_File_Path'] ?? '')); ?></span>
                  <span class="file-meta">Uploaded · <?php echo date('M d, Y', strtotime($row['Prescription_Date'])); ?></span>
                </div>
              </div>
            </div>
            <div class="rx-actions">
              <a href="<?php echo vp_escape($row['Pres_File_Path'] ?? ''); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary">
                <i class="fas fa-eye"></i> View
              </a>
              <a href="<?php echo vp_escape($row['Pres_File_Path'] ?? ''); ?>" download class="btn btn-success">
                <i class="fas fa-download"></i> Download
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-state">
          <i class="fas fa-prescription-bottle-medical"></i>
          <h3>No Prescriptions Found</h3>
          <p><?php echo $search !== '' ? 'Try a different search term.' : 'Your prescription history will appear here once your doctor issues one.'; ?></p>
          <a href="dashboard.php" class="btn btn-primary btn-full"><i class="fas fa-home"></i> Back to Dashboard</a>
        </div>
      <?php endif; ?>
    </div>
    <?php if ($search !== ''): ?>
      <div class="filtered-note">
        Showing <?php echo vp_escape($filteredCount); ?> of <?php echo vp_escape($totalPrescriptions); ?> total prescriptions matching "<strong><?php echo vp_escape($search); ?></strong>".
        <a href="view_prescriptions.php" style="color:var(--primary-strong);font-weight:700;margin-left:0.5rem">Clear search</a>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
(function () {
  const searchInput = document.getElementById('prescriptionSearch');
  const clearBtn = document.getElementById('clearSearch');
  if (!searchInput) return;

  searchInput.addEventListener('input', function () {
    clearBtn.style.display = this.value.trim() !== '' ? 'block' : 'none';
  });

  clearBtn.addEventListener('click', function () {
    searchInput.value = '';
    clearBtn.style.display = 'none';
    searchInput.form.submit();
  });
})();
</script>
</body>
</html>
