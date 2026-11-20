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

    .team-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1.25rem; align-items: stretch; }
    .team-card { overflow: hidden; background: rgba(255,255,255,0.86); border: 1px solid rgba(148,163,184,0.2); border-radius: 22px; box-shadow: 0 16px 30px rgba(15,23,42,0.05); transition: transform 0.25s ease, box-shadow 0.25s ease; }
    .team-card:hover { transform: translateY(-5px); box-shadow: 0 24px 40px rgba(37,99,235,0.11); }
    .team-photo { position: relative; overflow: hidden; background: linear-gradient(135deg, #dbeafe, #dff8f7); }
    .team-photo img { width: 100%; height: 230px; object-fit: cover; object-position: center 28%; }
    .team-card:nth-child(1) .team-photo img { object-position: center 38%; }
    .team-card:nth-child(2) .team-photo img { object-position: center 24%; }
    .team-card:nth-child(3) .team-photo img { object-position: center 25%; }
    .team-card:nth-child(4) .team-photo img { object-position: center 25%; }
    .team-content { padding: 1.25rem; }
    .team-role { display: inline-flex; margin-bottom: 0.75rem; padding: 0.35rem 0.65rem; color: var(--primary-strong); border-radius: 999px; background: rgba(37,99,235,0.08); font-size: 0.72rem; font-weight: 700; line-height: 1.35; }
    .team-content h3 { color: var(--ink); font-size: 1.16rem; letter-spacing: -0.025em; margin-bottom: 0.65rem; }
    .team-bio { min-height: 5.2rem; margin: 0; color: var(--muted); font-size: 0.88rem; }
    .team-contribution { margin-top: 1rem; padding-top: 0.9rem; border-top: 1px solid rgba(148,163,184,0.2); color: var(--muted); font-size: 0.84rem; }
    .team-contribution strong { display: block; margin-bottom: 0.3rem; color: var(--ink); font-size: 0.75rem; letter-spacing: 0.04em; text-transform: uppercase; }
    .team-link { display: inline-flex; align-items: center; gap: 0.45rem; margin-top: 0.8rem; color: var(--primary-strong); font-size: 0.82rem; font-weight: 700; }

    .modules-wrap { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1.5rem; }
    .module-card { background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%); border: 1px solid rgba(148,163,184,0.18); border-radius: 22px; overflow: hidden; box-shadow: 0 16px 30px rgba(15,23,42,0.04); }
    .module-card img { width: 100%; height: 220px; object-fit: cover; }
    .module-card .content { padding: 1.3rem 1.2rem 1.5rem; }
    .module-card h3 { font-size: 1.25rem; color: var(--ink); margin-bottom: 0.5rem; }
    .module-card p { margin: 0; color: var(--muted); font-size: 0.96rem; }

    .hospital-box { position: relative; z-index: 1; background: rgba(255,255,255,0.75); border: 1px solid rgba(148,163,184,0.2); border-radius: 28px; box-shadow: var(--shadow); }
    .hospital-box.dropdown-open { z-index: 10; }
    .hospital-header { background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); padding: 1.4rem 1.5rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; border-radius: 28px; }
    .hospital-header h3 { font-size: clamp(1.5rem, 2vw, 2rem); color: var(--ink); display: flex; align-items: center; gap: 0.7rem; }
    .hospital-header h3 i { color: var(--primary); }
    .hospital-heading p { margin: 0.45rem 0 0; color: var(--muted); font-size: 0.9rem; }
    .toggle-btn { border: 0; background: var(--primary); color: white; font-weight: 700; border-radius: 999px; padding: 0.75rem 1.1rem; cursor: pointer; display: inline-flex; align-items: center; gap: 0.65rem; box-shadow: 0 10px 20px rgba(37,99,235,0.18); white-space: nowrap; }
    .toggle-btn:focus-visible, .hospital-search:focus-visible { outline: 3px solid rgba(37,99,235,0.3); outline-offset: 3px; }
    .toggle-btn .fa-chevron-down { transition: transform 0.2s ease; }
    .toggle-btn[aria-expanded="true"] .fa-chevron-down { transform: rotate(180deg); }
    .hospital-dropdown { position: absolute; z-index: 20; top: calc(100% + 0.75rem); right: 0; display: flex; flex-direction: column; width: min(560px, calc(100vw - 2rem)); max-height: min(460px, calc(100vh - 2rem)); overflow: hidden; border: 1px solid rgba(148,163,184,0.28); border-radius: 18px; background: var(--card); box-shadow: 0 24px 60px rgba(15,23,42,0.2); }
    .hospital-dropdown[hidden] { display: none; }
    .hospital-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 0.8rem; padding: 1rem; border-bottom: 1px solid var(--line); background: #fff; }
    .hospital-search { width: 100%; min-width: 0; padding: 0.7rem 0.85rem; border: 1px solid rgba(148,163,184,0.35); border-radius: 10px; color: var(--ink); background: #f8fbff; font: inherit; }
    .hospital-result-count { flex: 0 0 auto; color: var(--muted); font-size: 0.78rem; white-space: nowrap; }
    .hospital-list { flex: 1; min-height: 0; overflow-y: auto; overscroll-behavior: contain; list-style: none; padding: 0.65rem; margin: 0; display: grid; gap: 0.45rem; }
    .hospital-list[hidden] { display: none; }
    .hospital-item { display: block; padding: 0.8rem 0.9rem; border: 1px solid rgba(148,163,184,0.16); border-radius: 12px; background: linear-gradient(135deg, #fbfdff 0%, #f2f7ff 100%); }
    .hospital-item[hidden], .hospital-empty[hidden] { display: none; }
    .hospital-empty { padding: 1.5rem 1rem; color: var(--muted); text-align: center; font-size: 0.9rem; }
    .hospital-item h4 { color: var(--ink); margin-bottom: 0.25rem; font-size: 1rem; }
    .hospital-item p { margin: 0; color: var(--muted); display: flex; align-items: center; gap: 0.6rem; font-size: 0.88rem; }
    .hospital-item i { color: var(--primary); }
    .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }

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
      .hero-grid, .feature-grid, .modules-wrap, .footer-grid { grid-template-columns: 1fr; }
      .team-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .hero-stats { grid-template-columns: 1fr; }
      .nav-links { display: none; }
      .menu-toggle { display: block; }
    }
    @media (max-width: 700px) {
      .hero { padding-top: 4rem; }
      .hero-actions, .cta-actions { flex-direction: column; }
      .btn { width: 100%; }
      .nav-actions .btn { display: none; }
      .team-grid { grid-template-columns: 1fr; }
      .team-bio { min-height: 0; }
      .hospital-header { flex-direction: column; align-items: flex-start; }
      .hospital-header .toggle-btn { width: 100%; justify-content: center; }
      .hospital-dropdown { left: 0; right: auto; width: 100%; }
      .hospital-toolbar { align-items: stretch; flex-direction: column; }
      .hospital-result-count { align-self: flex-end; }
    }
  </style>
<link rel="stylesheet" href="assets/css/presentation.css">
    <link rel="icon" type="image/webp" href="assets/cmrx-logo.webp">
</head>
<body class="public-site">
  <header class="site-header">
    <div class="container nav-wrap">
      <a href="Homepage.php" class="brand site-brand">
        <img src="assets/cmrx-logo.webp" width="420" height="280" alt="CMRx home">
      </a>
      <button type="button" class="menu-toggle" aria-label="Toggle navigation" aria-controls="site-navigation" aria-expanded="false">
        <i class="fas fa-bars" aria-hidden="true"></i>
      </button>
      <nav class="site-navigation" id="site-navigation" aria-label="Main navigation">
        <ul class="nav-links">
          <li><a href="#departments">Departments</a></li>
          <li><a href="#team">Our team</a></li>
          <li><a href="#contact">Contact</a></li>
          <li><a href="hospital_login.php">Hospital login</a></li>
          <li><a href="login_doc.php">Doctor login</a></li>
          <li><a class="nav-patient" href="login.php">Patient login</a></li>
        </ul>
      </nav>
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

  <section class="section" id="team">
    <div class="container">
      <div class="section-head">
        <h2>Meet the CMRx team</h2>
        <p>The people behind the system, bringing together healthcare workflows, thoughtful design, and dependable software.</p>
      </div>
      <div class="team-grid">
        <article class="team-card">
          <div class="team-photo">
            <img src="Photos/kushal.jpg" alt="H. M. Ahsan Habib Kushal" loading="lazy" />
          </div>
          <div class="team-content">
            <span class="team-role">CSE student · Aspiring AI/ML engineer &amp; researcher</span>
            <h3>H. M. Ahsan Habib Kushal</h3>
            <p class="team-bio">Interested in machine learning, deep learning, computer vision, data science, and software development.</p>
            <div class="team-contribution">
              <strong>Project contribution</strong>
              Led the database design and implementation and shaped the system architecture.
            </div>
            <a class="team-link" href="https://www.linkedin.com/in/h-m-ahsan-habib-kushal-0263731a1/" target="_blank" rel="noopener noreferrer">
              <i class="fab fa-linkedin-in" aria-hidden="true"></i> LinkedIn profile
            </a>
          </div>
        </article>

        <article class="team-card">
          <div class="team-photo">
            <img src="Photos/heme.jpg" alt="Homayra Binte Harun Heme" loading="lazy" />
          </div>
          <div class="team-content">
            <span class="team-role">CSE student at NITER · MERN Stack Developer</span>
            <h3>Homayra Binte Harun Heme</h3>
            <p class="team-bio">Works with React, Node.js, Express, MongoDB, and Next.js to build modern web applications.</p>
            <div class="team-contribution">
              <strong>Project contribution</strong>
              Helped develop the database architecture and shape the front-end design.
            </div>
            <a class="team-link" href="https://www.linkedin.com/in/homayra-heme/" target="_blank" rel="noopener noreferrer">
              <i class="fab fa-linkedin-in" aria-hidden="true"></i> LinkedIn profile
            </a>
          </div>
        </article>

        <article class="team-card">
          <div class="team-photo">
            <img class="adnan-portrait" src="data:image/jpeg;base64,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" width="800" height="800" alt="Adnan Hassan" loading="lazy" />
          </div>
          <div class="team-content">
            <span class="team-role">Software Developer Intern · Efficient ERP Limited</span>
            <h3>Adnan Hassan</h3>
            <p class="team-bio">Focused on software development, with professional experience as an intern at Efficient ERP Limited.</p>
            <div class="team-contribution">
              <strong>Project contribution</strong>
              Developed the backend and integrated the project’s features.
            </div>
            <a class="team-link" href="https://www.linkedin.com/in/adnan-hasanncs/" target="_blank" rel="noopener noreferrer">
              <i class="fab fa-linkedin-in" aria-hidden="true"></i> LinkedIn profile
            </a>
          </div>
        </article>

        <article class="team-card">
          <div class="team-photo">
            <img src="Photos/suraya.jpg" alt="Suraya Akter Bonna" loading="lazy" />
          </div>
          <div class="team-content">
            <span class="team-role">Software requirements · Database design</span>
            <h3>Suraya Akter Bonna</h3>
            <p class="team-bio">Contributed to the planning and documentation that helped define the project’s requirements.</p>
            <div class="team-contribution">
              <strong>Project contribution</strong>
              Contributed to partial database design and prepared the Software Requirements Specification (SRS).
            </div>
            <a class="team-link" href="https://www.linkedin.com/in/suraya-akter-bonna-145865257/" target="_blank" rel="noopener noreferrer">
              <i class="fab fa-linkedin-in" aria-hidden="true"></i> LinkedIn profile
            </a>
          </div>
        </article>
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
          <div class="hospital-heading">
            <h3><i class="fas fa-hospital"></i> Registered Hospitals</h3>
            <p><strong><?php echo (int)$hospital_count; ?></strong> facilities in the CMRx directory</p>
          </div>
          <button class="toggle-btn" type="button" id="toggleHospitalsBtn" aria-expanded="false" aria-controls="hospitalDropdown" aria-label="Browse registered hospitals">
            <span class="toggle-label">Browse hospitals</span>
            <i class="fas fa-chevron-down" aria-hidden="true"></i>
          </button>
        </div>

        <div class="hospital-dropdown" id="hospitalDropdown" hidden>
          <?php if ($hospital_result && $hospital_result->num_rows > 0): ?>
            <div class="hospital-toolbar">
              <label class="sr-only" for="hospitalSearch">Search hospitals</label>
              <input class="hospital-search" type="search" id="hospitalSearch" placeholder="Search by hospital or address" autocomplete="off">
              <span class="hospital-result-count" id="hospitalResultCount" aria-live="polite"><?php echo (int)$hospital_count; ?> hospitals</span>
            </div>
            <ul class="hospital-list" id="hospitalList" tabindex="0" aria-label="Registered hospitals">
              <?php while ($hospital = $hospital_result->fetch_assoc()): ?>
                <li class="hospital-item">
                  <h4><?php echo htmlspecialchars($hospital['Hospital_Name'], ENT_QUOTES, 'UTF-8'); ?></h4>
                  <?php if (!empty($hospital['Address'])): ?>
                    <p><i class="fas fa-map-marker-alt" aria-hidden="true"></i> <?php echo htmlspecialchars($hospital['Address'], ENT_QUOTES, 'UTF-8'); ?></p>
                  <?php endif; ?>
                </li>
              <?php endwhile; ?>
              <li class="hospital-empty" id="hospitalNoResults" hidden>No hospitals match your search.</li>
            </ul>
          <?php else: ?>
            <div class="hospital-empty">No hospitals registered yet.</div>
          <?php endif; ?>
        </div>
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
    const navLinks = document.getElementById('site-navigation');
    if (menuToggle && navLinks) {
      const setMenu = (open) => {
        navLinks.classList.toggle('is-open', open);
        menuToggle.setAttribute('aria-expanded', String(open));
      };
      menuToggle.addEventListener('click', () => setMenu(!navLinks.classList.contains('is-open')));
      navLinks.addEventListener('click', (event) => {
        if (event.target.closest('a')) setMenu(false);
      });
      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && navLinks.classList.contains('is-open')) {
          setMenu(false);
          menuToggle.focus();
        }
      });
      matchMedia('(max-width: 1024px)').addEventListener('change', () => setMenu(false));
    }

    const toggleHospitalsBtn = document.getElementById('toggleHospitalsBtn');
    const hospitalBox = document.querySelector('.hospital-box');
    const hospitalDropdown = document.getElementById('hospitalDropdown');
    const hospitalList = document.getElementById('hospitalList');
    const hospitalSearch = document.getElementById('hospitalSearch');
    const hospitalItems = hospitalList ? Array.from(hospitalList.querySelectorAll('.hospital-item')) : [];
    const hospitalNoResults = document.getElementById('hospitalNoResults');
    const hospitalResultCount = document.getElementById('hospitalResultCount');
    const toggleLabel = document.querySelector('.toggle-label');
    function setHospitalDropdownOpen(isOpen) {
      if (!toggleHospitalsBtn || !hospitalDropdown || !toggleLabel) return;
      hospitalDropdown.hidden = !isOpen;
      toggleHospitalsBtn.setAttribute('aria-expanded', String(isOpen));
      toggleHospitalsBtn.setAttribute('aria-label', isOpen ? 'Close hospital directory' : 'Browse registered hospitals');
      toggleLabel.textContent = isOpen ? 'Close directory' : 'Browse hospitals';
      hospitalBox.classList.toggle('dropdown-open', isOpen);
      if (isOpen && hospitalSearch) hospitalSearch.focus();
    }

    if (toggleHospitalsBtn && hospitalDropdown && toggleLabel) {
      toggleHospitalsBtn.addEventListener('click', () => {
        setHospitalDropdownOpen(toggleHospitalsBtn.getAttribute('aria-expanded') !== 'true');
      });
    }

    if (hospitalSearch && hospitalItems.length && hospitalResultCount) {
      const filterHospitals = () => {
        const query = hospitalSearch.value.trim().toLocaleLowerCase();
        let visibleCount = 0;
        hospitalItems.forEach((item) => {
          const matches = item.textContent.toLocaleLowerCase().includes(query);
          item.hidden = !matches;
          if (matches) visibleCount++;
        });
        hospitalResultCount.textContent = `${visibleCount} of ${hospitalItems.length} hospitals`;
        if (hospitalNoResults) hospitalNoResults.hidden = visibleCount > 0;
      };
      hospitalSearch.addEventListener('input', filterHospitals);
      filterHospitals();
    }

    document.addEventListener('click', (event) => {
      if (toggleHospitalsBtn && toggleHospitalsBtn.getAttribute('aria-expanded') === 'true' &&
          hospitalBox && !hospitalBox.contains(event.target)) {
        setHospitalDropdownOpen(false);
      }
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && toggleHospitalsBtn &&
          toggleHospitalsBtn.getAttribute('aria-expanded') === 'true') {
        setHospitalDropdownOpen(false);
        toggleHospitalsBtn.focus();
      }
    });

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
