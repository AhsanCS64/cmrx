<?php
// Database connection parameters
$servername = "sql310.infinityfree.com";
$username = "if0_43424129";
$password = "Kushal11199";
$port = 3306;
$dbname = "if0_43424129_cmrx";

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT COUNT(MED_ID) as total_patients FROM patient_details";
$result = $conn->query($sql);
$total_patients = 0;
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $total_patients = $row['total_patients'];
}

$doctor_count = $conn->query("SELECT COUNT(*) AS total FROM doctor_details WHERE Doc_Name <> ''")->fetch_assoc()['total'];
$hospital_count = $conn->query("SELECT COUNT(*) AS total FROM hospital")->fetch_assoc()['total'];
$patient_count = $conn->query("SELECT COUNT(*) AS total FROM patient_details")->fetch_assoc()['total'];
$hospital_sql = "SELECT Hospital_Name, Address, Registration_No FROM hospital";
$hospital_result = $conn->query($hospital_sql);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>CMRx | Centralized Medical Records</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #2563eb;
      --primary-strong: #1d4ed8;
      --primary-soft: #dbeafe;
      --secondary: #14b8a6;
      --ink: #0f172a;
      --muted: #475569;
      --line: rgba(148,163,184,0.28);
      --surface: #f8fafc;
      --card: #ffffff;
      --shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
      --soft-shadow: 0 12px 28px rgba(37, 99, 235, 0.12);
      --max: 1200px;
    }

    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
      margin: 0;
      font-family: 'Inter', sans-serif;
      background: linear-gradient(180deg, #f8fbff 0%, #eef6ff 100%);
      color: var(--ink);
      line-height: 1.6;
    }
    a { text-decoration: none; }
    img { max-width: 100%; display: block; }
    h1, h2, h3, h4 { margin: 0; line-height: 1.1; }
    .container { width: min(var(--max), calc(100% - 2rem)); margin: 0 auto; }
    .btn {
      display: inline-flex; align-items: center; justify-content: center; gap: 0.6rem;
      border-radius: 999px; padding: 0.9rem 1.5rem; font-weight: 700; transition: 0.25s ease; border: 1px solid transparent;
    }
    .btn:hover { transform: translateY(-1px); }
    .btn-primary { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-strong) 100%); color: white; box-shadow: var(--soft-shadow); }
    .btn-secondary { background: white; color: var(--primary); border-color: rgba(37, 99, 235, 0.2); }
    .btn-ghost { background: rgba(255,255,255,0.12); color: white; border-color: rgba(255,255,255,0.2); }

    header {
      position: sticky; top: 0; z-index: 20; backdrop-filter: blur(12px);
      background: rgba(248, 250, 252, 0.85); border-bottom: 1px solid rgba(148, 163, 184, 0.2);
    }
    .nav-wrap { display: flex; align-items: center; justify-content: space-between; min-height: 80px; }
    .brand {
      display: inline-flex; align-items: center; gap: 0.8rem; color: var(--primary);
      font-size: clamp(1.5rem, 2vw, 2rem); font-weight: 800; letter-spacing: -0.05em;
    }
    .brand i {
      width: 42px; height: 42px; display: grid; place-items: center; border-radius: 12px;
      background: linear-gradient(135deg, var(--primary-soft), #dff8f7); color: var(--primary); font-size: 1.1rem;
    }
    .nav-links { display: flex; align-items: center; gap: 1.5rem; list-style: none; margin: 0; padding: 0; }
    .nav-links a { color: var(--muted); font-weight: 600; transition: color 0.2s ease; }
    .nav-links a:hover { color: var(--primary); }
    .nav-actions { display: flex; align-items: center; gap: 0.8rem; }
    .menu-toggle { display: none; border: 0; background: transparent; color: var(--primary); font-size: 1.5rem; cursor: pointer; }

    .hero {
      position: relative; background: radial-gradient(circle at top left, rgba(37,99,235,0.12), transparent 36%), radial-gradient(circle at bottom right, rgba(20,184,166,0.14), transparent 30%), linear-gradient(180deg, #f8fbff 0%, #ebf3ff 100%); padding: 5rem 0 4.5rem; overflow: hidden;
    }
    .hero-grid { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 3rem; align-items: center; }
    .eyebrow {
      display: inline-flex; align-items: center; gap: 0.45rem; padding: 0.5rem 0.8rem; border-radius: 999px;
      background: rgba(37, 99, 235, 0.08); color: var(--primary); font-size: 0.8rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; margin-bottom: 1rem;
    }
    .hero h1 { font-size: clamp(2.6rem, 4vw, 4.3rem); font-weight: 800; letter-spacing: -0.06em; color: var(--ink); max-width: 620px; }
    .hero h1 span { color: var(--primary); }
    .hero p { color: var(--muted); font-size: 1.08rem; max-width: 620px; margin: 1.3rem 0 2rem; }
    .hero-actions { display: flex; flex-wrap: wrap; gap: 0.9rem; margin-bottom: 2.2rem; }
    .hero-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; max-width: 650px; }
    .stat-card {
      background: rgba(255,255,255,0.7); border: 1px solid rgba(148,163,184,0.18); border-radius: 18px; padding: 1rem 1rem 0.9rem; box-shadow: 0 10px 24px rgba(15,23,42,0.03);
    }
    .stat-icon {
      width: 46px; height: 46px; display: grid; place-items: center; background: linear-gradient(135deg, var(--primary-soft), rgba(20,184,166,0.12)); border-radius: 12px; color: var(--primary); margin-bottom: 0.75rem; font-size: 1.1rem;
    }
    .stat-card strong { color: var(--primary-strong); font-size: 1.6rem; font-weight: 800; display: block; line-height: 1.2; }
    .stat-card span { color: var(--muted); font-size: 0.85rem; display: block; margin-top: 0.2rem; }

    .hero-visual { position: relative; display: grid; place-items: center; }
    .visual-card {
      position: relative; width: min(100%, 520px); background: linear-gradient(135deg, rgba(15, 23, 42, 0.96), rgba(37, 99, 235, 0.92)); border-radius: 32px; box-shadow: var(--shadow); padding: 1.2rem; color: #fff;
    }
    .visual-card::before { content: ''; position: absolute; inset: 20px auto auto 20px; width: 140px; height: 140px; border-radius: 50%; background: rgba(255,255,255,0.08); filter: blur(8px); }
    .visual-image { position: relative; border-radius: 24px; overflow: hidden; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.04); }
    .visual-image img { width: 100%; height: 360px; object-fit: cover; }
    .floating-badge {
      position: absolute; display: flex; align-items: center; gap: 0.7rem; background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.2); color: white; backdrop-filter: blur(8px); border-radius: 16px; padding: 0.8rem 1rem; box-shadow: 0 16px 32px rgba(11, 18, 32, 0.18); font-weight: 700;
    }
    .badge-top { top: 26px; right: -12px; }
    .badge-bottom { bottom: 26px; left: -18px; }

    .section { padding: 5rem 0; }
    .section-head { margin: 0 auto 2.5rem; text-align: center; max-width: 700px; }
    .section-head h2 { font-size: clamp(2rem, 3vw, 3rem); letter-spacing: -0.05em; color: var(--ink); margin-bottom: 0.8rem; }
    .section-head p { margin: 0; color: var(--muted); font-size: 1.04rem; }
    .feature-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1.5rem; }
    .feature-card {
      background: rgba(255,255,255,0.72); border: 1px solid rgba(148,163,184,0.16); border-radius: 22px; padding: 2rem 1.5rem 1.5rem; box-shadow: 0 16px 30px rgba(15,23,42,0.04); transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    .feature-card:hover { transform: translateY(-4px); box-shadow: 0 22px 34px rgba(15,23,42,0.06); }
    .feature-icon { width: 58px; height: 58px; display: grid; place-items: center; border-radius: 16px; margin-bottom: 1rem; background: linear-gradient(135deg, var(--primary-soft), rgba(20,184,166,0.12)); color: var(--primary); font-size: 1.4rem; }
    .feature-card h3 { font-size: 1.3rem; color: var(--ink); margin-bottom: 0.6rem; }
    .feature-card p { margin: 0; color: var(--muted); font-size: 0.97rem; }

    .about-wrap { display: grid; grid-template-columns: 1fr 1fr; gap: 2.2rem; align-items: center; }
    .about-panel { background: var(--card); border: 1px solid var(--line); border-radius: 26px; box-shadow: var(--soft-shadow); overflow: hidden; }
    .about-panel img { width: 100%; height: 100%; min-height: 420px; object-fit: cover; }
    .about-copy {
      background: rgba(255,255,255,0.7); border: 1px solid rgba(148,163,184,0.12); border-radius: 26px; padding: 2rem; box-shadow: 0 16px 28px rgba(15,23,42,0.04);
    }
    .about-copy h3 { font-size: clamp(1.8rem, 2vw, 2.4rem); letter-spacing: -0.04em; color: var(--ink); margin-bottom: 1rem; }
    .about-copy p { color: var(--muted); margin: 0 0 1.5rem; font-size: 1rem; }
    .check-list { list-style: none; padding: 0; margin: 0; display: grid; gap: 0.8rem; }
    .check-list li { display: flex; align-items: center; gap: 0.7rem; color: var(--ink); font-weight: 600; }
    .check-list li::before { content: '\f00c'; font-family: 'Font Awesome 6 Free'; font-weight: 900; color: var(--secondary); background: rgba(20,184,166,0.12); width: 28px; height: 28px; display: inline-grid; place-items: center; border-radius: 50%; }

    .modules-wrap { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1.5rem; }
    .module-card { background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%); border: 1px solid rgba(148,163,184,0.18); border-radius: 22px; overflow: hidden; box-shadow: 0 16px 30px rgba(15,23,42,0.04); }
    .module-card img { width: 100%; height: 220px; object-fit: cover; }
    .module-card .content { padding: 1.3rem 1.2rem 1.5rem; }
    .module-card h3 { font-size: 1.25rem; color: var(--ink); margin-bottom: 0.5rem; }
    .module-card p { margin: 0; color: var(--muted); font-size: 0.96rem; }

    .hospital-box { background: rgba(255,255,255,0.75); border: 1px solid rgba(148,163,184,0.2); border-radius: 28px; box-shadow: var(--shadow); overflow: hidden; }
    .hospital-header { background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); padding: 1.4rem 1.5rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; border-bottom: 1px solid rgba(148,163,184,0.26); }
    .hospital-header h3 { font-size: clamp(1.5rem, 2vw, 2rem); color: var(--ink); display: flex; align-items: center; gap: 0.7rem; }
    .hospital-header h3 i { color: var(--primary); }
    .toggle-btn { border: 0; background: var(--primary); color: white; font-weight: 700; border-radius: 999px; padding: 0.75rem 1.1rem; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; box-shadow: 0 10px 20px rgba(37,99,235,0.18); }
    .hospital-list { list-style: none; padding: 1.5rem; margin: 0; display: grid; gap: 1rem; }
    .hospital-item { display: block; background: linear-gradient(135deg, #f8fbff 0%, #eef6ff 100%); border: 1px solid rgba(96,165,250,0.4); border-radius: 18px; padding: 1.15rem 1.2rem; box-shadow: 0 8px 22px rgba(37,99,235,0.08); }
    .hospital-item h4 { color: var(--ink); margin-bottom: 0.35rem; font-size: 1.12rem; }
    .hospital-item p { margin: 0; color: var(--muted); display: flex; align-items: center; gap: 0.6rem; }
    .hospital-item i { color: var(--primary); }

    .cta { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-strong) 100%); color: white; padding: 4rem 0; text-align: center; }
    .cta h2 { font-size: clamp(2rem, 3vw, 3rem); letter-spacing: -0.05em; margin-bottom: 0.8rem; }
    .cta p { margin: 0 auto 1.7rem; max-width: 700px; opacity: 0.9; font-size: 1.05rem; }
    .cta-actions { display: flex; justify-content: center; gap: 0.9rem; flex-wrap: wrap; }

    footer { background: #0f172a; color: rgba(255,255,255,0.9); padding: 4rem 0 1.5rem; }
    .footer-grid { display: grid; grid-template-columns: 1.2fr 0.8fr 0.9fr 1fr; gap: 2rem; margin-bottom: 2.5rem; }
    .footer-brand { display: inline-flex; align-items: center; gap: 0.8rem; color: white; font-size: 1.8rem; font-weight: 800; letter-spacing: -0.05em; margin-bottom: 1rem; }
    .footer-brand i { width: 44px; height: 44px; display: grid; place-items: center; border-radius: 14px; background: rgba(96,165,250,0.18); color: #93c5fd; font-size: 1.1rem; }
    .footer-col p, .footer-col li, .footer-col a { color: rgba(255,255,255,0.7); }
    .footer-col h4 { color: white; margin-bottom: 1rem; font-size: 1.1rem; }
    .footer-links { list-style: none; margin: 0; padding: 0; display: grid; gap: 0.7rem; }
    .footer-links a { display: inline-flex; align-items: center; gap: 0.5rem; }
    .socials { display: flex; gap: 0.8rem; margin-top: 1.2rem; }
    .socials a { width: 40px; height: 40px; display: grid; place-items: center; border-radius: 50%; background: rgba(148,163,184,0.12); color: white; transition: transform 0.2s ease, background 0.2s ease; }
    .socials a:hover { transform: translateY(-2px); background: rgba(37,99,235,0.7); }
    .contact-list { list-style: none; margin: 0; padding: 0; display: grid; gap: 0.8rem; }
    .contact-list li { display: flex; gap: 0.8rem; align-items: flex-start; color: rgba(255,255,255,0.75); }
    .contact-list i { color: #93c5fd; margin-top: 0.1rem; }
    .footer-bottom { border-top: 1px solid rgba(148,163,184,0.18); padding-top: 1.2rem; text-align: center; color: rgba(255,255,255,0.66); font-size: 0.9rem; }

    @media (max-width: 980px) {
      .hero-grid, .about-wrap, .feature-grid, .modules-wrap, .footer-grid { grid-template-columns: 1fr; }
      .hero-stats { grid-template-columns: 1fr; }
      .nav-links { display: none; }
      .menu-toggle { display: block; }
    }
    @media (max-width: 700px) {
      .hero { padding-top: 4rem; }
      .hero-actions, .cta-actions { flex-direction: column; }
      .btn { width: 100%; }
      .nav-actions .btn { display: none; }
      .hospital-header { flex-direction: column; align-items: flex-start; }
      .hospital-header .toggle-btn { width: 100%; justify-content: center; }
    }
  </style>
</head>
<body>
  <header>
    <div class="container nav-wrap">
      <a href="#" class="brand"><i class="fas fa-book-medical"></i> CMRx</a>
      <ul class="nav-links">
        <li><a href="#">Home</a></li>
        <li><a href="login_doc.php">Doctor Profile</a></li>
        <li><a href="login.php">Patient Profile</a></li>
        <li><a href="#departments">Departments</a></li>
        <li><a href="#contact">Contact</a></li>
      </ul>
      <div class="nav-actions">
        <a href="hospital_signup.php" class="btn btn-secondary">Book Hospital</a>
        <button class="menu-toggle" aria-label="Toggle navigation"><i class="fas fa-bars"></i></button>
      </div>
    </div>
  </header>

  <section class="hero">
    <div class="container hero-grid">
      <div>
        <div class="eyebrow"><i class="fas fa-stethoscope"></i> Trusted digital care</div>
        <h1>Advanced <span>centralized</span> medical records.</h1>
        <p>CMRx brings patient data, doctor records, hospital access, prescriptions, and disease tracking into one secure and modern healthcare platform.</p>
        <div class="hero-actions">
          <a href="hospital_login.php" class="btn btn-primary">Hospital Access</a>
          <a href="#services" class="btn btn-secondary">Explore Services</a>
        </div>
        <div class="hero-stats">
          <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-user-md"></i></div>
            <strong><?php echo $doctor_count; ?></strong>
            <span>Doctors</span>
          </div>
          <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-hospital"></i></div>
            <strong><?php echo $hospital_count; ?></strong>
            <span>Hospitals</span>
          </div>
          <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <strong><?php echo $patient_count; ?></strong>
            <span>Patients</span>
          </div>
        </div>
      </div>
      <div class="hero-visual">
        <div class="visual-card">
          <div class="floating-badge badge-top"><i class="fas fa-shield-alt"></i> 24/7 secure access</div>
          <div class="floating-badge badge-bottom"><i class="fas fa-file-medical"></i> Real-time health data</div>
          <div class="visual-image">
            <img src="https://www.ostomy.org/wp-content/uploads/2018/09/Medical-record_-web-1030x704.jpg" alt="Medical records dashboard" />
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="section" id="services">
    <div class="container">
      <div class="section-head">
        <h2>Healthcare built around trust</h2>
        <p>Designed to reduce complexity, improve collaboration, and keep every patient history accessible in one place.</p>
      </div>
      <div class="feature-grid">
        <article class="feature-card">
          <div class="feature-icon"><i class="fas fa-file-medical"></i></div>
          <h3>Patient Records</h3>
          <p>Keep complete and organized patient histories available whenever treatment decisions matter most.</p>
        </article>
        <article class="feature-card">
          <div class="feature-icon"><i class="fas fa-user-md"></i></div>
          <h3>Doctor Portal</h3>
          <p>Give clinical teams quick access to patient information, conditions, and prescription records.</p>
        </article>
        <article class="feature-card">
          <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
          <h3>Secure Access</h3>
          <p>Protect patient data with dependable centralized access built for healthcare workflows.</p>
        </article>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container about-wrap">
      <div class="about-panel">
        <img src="https://images.unsplash.com/photo-1538108149393-fbbd81895977?auto=format&fit=crop&w=1200&q=80" alt="Doctors collaborating" />
      </div>
      <div class="about-copy">
        <h3>Connected care, smarter decisions.</h3>
        <p>CMRx helps unify fragmented healthcare information so hospitals, clinics, and doctors can act faster with better visibility across every stage of a patient’s care journey.</p>
        <ul class="check-list">
          <li>Unified patient profiles</li>
          <li>Prescription and disease tracking</li>
          <li>Hospital and doctor coordination</li>
          <li>Improved continuity of care</li>
        </ul>
      </div>
    </div>
  </section>

  <section class="section" id="departments">
    <div class="container">
      <div class="section-head">
        <h2>Key modules</h2>
        <p>Purpose-built digital tools to keep your healthcare operations organized and responsive.</p>
      </div>
      <div class="modules-wrap">
        <article class="module-card">
          <img src="https://images.unsplash.com/photo-1576091160550-2173dba999ef?auto=format&fit=crop&w=900&q=80" alt="Patient management" />
          <div class="content">
            <h3>Patient Management</h3>
            <p>Track patient information, appointments, and clinical history in a single place.</p>
          </div>
        </article>
        <article class="module-card">
          <img src="https://images.unsplash.com/photo-1584515933487-779824d29309?auto=format&fit=crop&w=900&q=80" alt="Doctor portal" />
          <div class="content">
            <h3>Doctor Portal</h3>
            <p>Review patient data and issue updated treatment plans with accurate, accessible records.</p>
          </div>
        </article>
        <article class="module-card">
          <img src="https://images.unsplash.com/photo-1516549655169-df83a0774514?auto=format&fit=crop&w=900&q=80" alt="Hospital coordination" />
          <div class="content">
            <h3>Hospital Coordination</h3>
            <p>Support smooth operations across departments with integrated care management tools.</p>
          </div>
        </article>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="hospital-box">
        <div class="hospital-header">
          <h3><i class="fas fa-hospital"></i> Registered Hospitals</h3>
          <button class="toggle-btn" type="button" id="toggleHospitalsBtn" aria-expanded="true">
            <span class="toggle-label">Hide List</span>
            <i class="fas fa-chevron-up"></i>
          </button>
        </div>

        <?php if ($hospital_result && $hospital_result->num_rows > 0): ?>
          <ul class="hospital-list" id="hospitalList">
            <?php while ($hospital = $hospital_result->fetch_assoc()): ?>
              <li class="hospital-item">
                <h4><?php echo htmlspecialchars($hospital['Hospital_Name']); ?></h4>
                <?php if (!empty($hospital['Address'])): ?>
                  <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($hospital['Address']); ?></p>
                <?php endif; ?>
              </li>
            <?php endwhile; ?>
          </ul>
        <?php else: ?>
          <div style="padding:2rem; text-align:center; color:var(--muted);">
            <i class="fas fa-hospital" style="font-size:2rem; color:#93c5fd; display:block; margin-bottom:0.75rem;"></i>
            No hospitals registered yet.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="cta">
    <div class="container">
      <h2>Ready to join us?</h2>
      <p>Build patient trust, improve clinical visibility, and simplify healthcare operations with a secure digital system.</p>
      <div class="cta-actions">
        <a href="signup.php" class="btn btn-secondary">Patient Register</a>
        <a href="signup_doc.php" class="btn btn-ghost">Doctor Register</a>
      </div>
    </div>
  </section>

  <footer id="contact">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-col">
          <div class="footer-brand"><i class="fas fa-book-medical"></i> CMRx</div>
          <p>Delivering clean, secure, and connected healthcare systems that help patient care work smoothly from intake to treatment.</p>
          <div class="socials">
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-linkedin-in"></i></a>
          </div>
        </div>
        <div class="footer-col">
          <h4>Quick Links</h4>
          <ul class="footer-links">
            <li><a href="#"><i class="fas fa-chevron-right"></i> Home</a></li>
            <li><a href="login_doc.php"><i class="fas fa-chevron-right"></i> Doctor Portal</a></li>
            <li><a href="login.php"><i class="fas fa-chevron-right"></i> Patient Portal</a></li>
            <li><a href="hospital_login.php"><i class="fas fa-chevron-right"></i> Hospital Portal</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Core Modules</h4>
          <ul class="footer-links">
            <li><a href="#"><i class="fas fa-chevron-right"></i> Patient Records</a></li>
            <li><a href="#"><i class="fas fa-chevron-right"></i> Doctor Portal</a></li>
            <li><a href="#"><i class="fas fa-chevron-right"></i> Disease Tracking</a></li>
            <li><a href="#"><i class="fas fa-chevron-right"></i> Prescription History</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Contact</h4>
          <ul class="contact-list">
            <li><i class="fas fa-map-marker-alt"></i><span>Niribili, Savar</span></li>
            <li><i class="fas fa-phone"></i><span>+880-17-1221-1284</span></li>
            <li><i class="fas fa-envelope"></i><span>kushalahsan@gmail.com</span></li>
            <li><i class="fas fa-clock"></i><span>24/7 Emergency Services</span></li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">&copy; 2025 CMRx Hospital. All Rights Reserved.</div>
    </div>
  </footer>

  <script>
    const menuToggle = document.querySelector('.menu-toggle');
    const navLinks = document.querySelector('.nav-links');
    if (menuToggle && navLinks) {
      menuToggle.addEventListener('click', () => {
        const visible = navLinks.style.display === 'flex';
        navLinks.style.display = visible ? 'none' : 'flex';
        navLinks.style.position = 'absolute';
        navLinks.style.top = '76px';
        navLinks.style.right = '1rem';
        navLinks.style.flexDirection = 'column';
        navLinks.style.background = 'white';
        navLinks.style.padding = '1rem';
        navLinks.style.borderRadius = '16px';
        navLinks.style.boxShadow = '0 24px 60px rgba(15, 23, 42, 0.12)';
        navLinks.style.minWidth = '220px';
      });
    }

    const toggleHospitalsBtn = document.getElementById('toggleHospitalsBtn');
    const hospitalList = document.getElementById('hospitalList');
    const toggleLabel = document.querySelector('.toggle-label');
    if (toggleHospitalsBtn && hospitalList && toggleLabel) {
      toggleHospitalsBtn.addEventListener('click', () => {
        const isHidden = hospitalList.style.display === 'none';
        hospitalList.style.display = isHidden ? 'grid' : 'none';
        toggleLabel.textContent = isHidden ? 'Hide List' : 'Show List';
        toggleHospitalsBtn.setAttribute('aria-expanded', String(isHidden));
      });
    }

    window.addEventListener('resize', () => {
      if (window.innerWidth > 980 && navLinks) {
        navLinks.style.display = 'flex';
        navLinks.style.position = 'static';
        navLinks.style.flexDirection = 'row';
        navLinks.style.background = 'transparent';
        navLinks.style.padding = '0';
        navLinks.style.boxShadow = 'none';
        navLinks.style.minWidth = 'auto';
      }
    });
  </script>
</body>
</html>
