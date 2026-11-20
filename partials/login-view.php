<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $loginRole ?> login | CMRx</title>
  <link rel="icon" type="image/webp" href="assets/cmrx-logo.webp">
  <style>
* { box-sizing: border-box; }
.sign-in-page {
  margin: 0;
  min-height: 100vh;
  min-height: 100svh;
  display: grid;
  place-items: center;
  padding: clamp(16px, 5vw, 64px);
  background: #edf5f5;
  color: #10283c;
  font: 1rem/1.5 Inter, "Segoe UI", system-ui, sans-serif;
}
.sign-in {
  width: min(100%, 930px);
  display: grid;
  grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr);
  border: 1px solid #d4e3e6;
  border-radius: 24px;
  background: #fff;
  box-shadow: 0 24px 70px #10283c16;
}
.sign-in-brand {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: clamp(24px, 6vw, 72px);
  padding: clamp(24px, 4vw, 48px);
  border-radius: 23px 0 0 23px;
  background: radial-gradient(ellipse at 0 100%, #116b67, transparent 70%), #102c43;
  color: #fff;
}
.sign-in-home {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 164px;
  height: 64px;
  overflow: hidden;
  border-radius: 10px;
  background: white;
}
.sign-in-home img { display: block; width: 100%; height: auto; transform: scale(1.15); }
.sign-in-context p { margin: 0; color: #d7ebed; }
.sign-in-context .sign-in-portal {
  margin-bottom: 12px;
  color: white;
  font-size: clamp(2rem, 4vw, 3.4rem);
  font-weight: 750;
  letter-spacing: -.05em;
  line-height: 1.04;
}
.sign-in-back { align-self: flex-start; color: #cef5ed; font-weight: 600; }
.sign-in-content { min-width: 0; padding: clamp(24px, 5vw, 56px); }
.sign-in-content h1 { margin: 0; font-size: clamp(1.7rem, 3vw, 2.4rem); letter-spacing: -.04em; line-height: 1.15; }
.sign-in-help { margin: 10px 0 26px; color: #496475; }
.sign-in-error { margin-bottom: 20px; padding: 12px; border: 1px solid #efc1c1; border-radius: 9px; background: #fff3f3; color: #862525; }
.sign-in-field { margin-bottom: 18px; }
.sign-in-field label { display: block; margin-bottom: 7px; font-weight: 650; }
.sign-in-field input {
  width: 100%;
  min-height: 48px;
  padding: 10px 12px;
  border: 1px solid #aec4cb;
  border-radius: 9px;
  background: #f8fbfb;
  color: inherit;
  font: inherit;
}
.sign-in :focus-visible { outline: 3px solid #179f90; outline-offset: 3px; }
.account-password { position: relative; }
.sign-in-field .account-password input { padding-right: 76px; }
.account-password-toggle {
  position: absolute;
  inset: 4px 4px 4px auto;
  min-width: 60px;
  border: 0;
  border-radius: 6px;
  background: #e2f2ee;
  color: #07594f;
  font: inherit;
  font-size: .875rem;
  font-weight: 700;
  cursor: pointer;
}
.sign-in-submit {
  width: 100%;
  min-height: 48px;
  border: 0;
  border-radius: 9px;
  background: #08786d;
  color: #fff;
  font: inherit;
  font-weight: 750;
  cursor: pointer;
}
.sign-in-submit:hover { background: #056055; }
.sign-in-register { margin: 18px 0; }
.sign-in-content a { color: #056055; font-weight: 650; }
.sign-in-switch { display: flex; flex-wrap: wrap; gap: 8px 20px; padding-top: 12px; border-top: 1px solid #dbe7e9; }
.sign-in-switch a { display: inline-flex; align-items: center; min-height: 44px; }
@media (max-width: 700px) {
  .sign-in { grid-template-columns: minmax(0, 1fr); }
  .sign-in-brand { border-radius: 23px 23px 0 0; }
  .sign-in-context { display: none; }
  .sign-in-brand { flex-direction: row; flex-wrap: wrap; align-items: center; gap: 12px; }
  .sign-in-back { align-self: center; }
}


.sign-in a { text-underline-offset: .22em; }
.sign-in-home { text-decoration: none; }
.sign-in-back {
  display: inline-flex;
  align-items: center;
  min-height: 44px;
  padding: 0 12px;
  border: 1px solid #ffffff45;
  border-radius: 8px;
  text-decoration: none;
}
.sign-in-back:hover { background: #ffffff17; }
.sign-in-switch a {
  padding-inline: 12px;
  border: 1px solid #d2e4e2;
  border-radius: 8px;
  background: #f3f9f8;
  text-decoration: none;
}
.sign-in-switch a:hover { background: #dff2ee; }
.sign-in-field input:focus { border-color: #08786d; }
@media (max-width: 400px) {
  .sign-in-brand { padding: 16px; }
  .sign-in-home { width: 114px; height: 49px; }
  .sign-in-back { font-size: .8rem; padding-inline: 8px; }
}
  </style>
  <script src="assets/js/account.js" defer></script>
</head>
<body class="account-page sign-in-page">
  <main class="sign-in">
    <aside class="sign-in-brand">
      <a class="sign-in-home" href="Homepage.php" aria-label="CMRx home">
        <img src="assets/cmrx-logo.webp" width="420" height="280" alt="CMRx">
      </a>
      <div class="sign-in-context">
        <p class="sign-in-portal"><?= $loginRole ?> portal</p>
        <p><?= $loginDescription ?></p>
      </div>
      <a class="sign-in-back" href="Homepage.php">← Back to homepage</a>
    </aside>

    <section class="sign-in-content" aria-labelledby="login-heading">
      <h1 id="login-heading"><?= $loginRole ?> sign-in</h1>
      <p class="sign-in-help">Enter your <?= strtolower($loginIdentifierLabel) ?> and password.</p>
      <?php if ($loginFeedback !== ''): ?>
        <div class="sign-in-error" role="alert"><?= htmlspecialchars($loginFeedback, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="sign-in-field">
          <label for="<?= $loginIdentifier ?>"><?= $loginIdentifierLabel ?></label>
          <input type="text" name="<?= $loginIdentifier ?>" id="<?= $loginIdentifier ?>" autocomplete="username" autocapitalize="none" spellcheck="false" required value="<?= htmlspecialchars(is_string($_POST[$loginIdentifier] ?? null) ? $_POST[$loginIdentifier] : '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="sign-in-field">
          <label for="<?= $loginPasswordField ?>">Password</label>
          <input type="password" name="<?= $loginPasswordField ?>" id="<?= $loginPasswordField ?>" autocomplete="current-password" required>
        </div>
        <button class="sign-in-submit" type="submit">Sign in</button>
      </form>

      <p class="sign-in-register">New to this portal? <a href="<?= $loginRegistration ?>">Create an account</a></p>
      <nav class="sign-in-switch" aria-label="Other CMRx portals">
        <?php foreach (['Patient' => 'login.php', 'Doctor' => 'login_doc.php', 'Hospital' => 'hospital_login.php'] as $role => $link): ?>
          <?php if ($role !== $loginRole): ?>
            <a href="<?= $link ?>"><?= $role ?> login</a>
          <?php endif; ?>
        <?php endforeach; ?>
      </nav>
    </section>
  </main>
</body>
</html>
