<?php
/**
 * Modern Split-Screen Login Page
 * Visitor Management System (VMS)
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$authError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($username === '' || $password === '') {
    $authError = 'Please enter both your username and password.';
  } else {
    $conn = getDbConnection();
    $stmt = $conn->prepare('SELECT UserID, Username, Password, FullName, Role, Status FROM Users WHERE Username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user || !password_verify($password, $user['Password'])) {
      $authError = 'Invalid username or password.';
    } elseif ($user['Status'] === 'blocked') {
      $authError = 'Your account has been deactivated/blocked. Please contact an administrator.';
    } else {
      loginUser($user);
      header('Location: dashboard.php');
      exit;
    }
  }
}

// Redirect authenticated users directly to dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In | VMS Enterprise</title>
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>

<div class="auth-wrapper">

  <!-- Left Branded Showcase Side -->
  <div class="auth-brand-side">
    <div class="brand-header">
      <div class="brand-logo-icon">
        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
      </div>
      <div>
        <span class="brand-title">VMS</span>
        <span class="brand-badge">Enterprise</span>
      </div>
    </div>

    <div class="brand-hero-content">
      <h1 class="brand-hero-title">
        Streamlined & Secure <span>Visitor Management</span>
      </h1>
      <p class="brand-hero-desc">
        Welcome guests, track check-ins in real time, generate instant compliance reports, and uphold premise security with a unified corporate platform.
      </p>

      <ul class="brand-features">
        <li class="brand-feature-item">
          <span class="brand-feature-icon">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
          </span>
          <span>Instant Visitor Check-In & Digital Badge Logging</span>
        </li>
        <li class="brand-feature-item">
          <span class="brand-feature-icon">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
          </span>
          <span>Strict Role-Based Access Control (Admin & Staff)</span>
        </li>
        <li class="brand-feature-item">
          <span class="brand-feature-icon">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
          </span>
          <span>Automated Daily & Monthly Audit Reporting</span>
        </li>
      </ul>
    </div>

    <div class="brand-footer">
      &copy; <?= date('Y') ?> Enterprise VMS. All rights reserved. Encrypted & Secure.
    </div>
  </div>

  <!-- Right Form Side -->
  <div class="auth-form-side">
    <div class="auth-card">
      <div class="auth-header">
        <h2>Sign In to Account</h2>
        <p>Enter your credentials to access the Visitor Management Portal</p>
      </div>

      <div id="auth-alert" class="auth-alert<?= $authError !== '' ? ' error' : '' ?>"<?= $authError !== '' ? ' style="display: flex;"' : '' ?>><?= escapeHtml($authError) ?></div>

      <form id="login-form" method="post" action="login.php" autocomplete="off">
        <div class="form-group">
          <label class="form-label" for="login-username">Username <span class="required">*</span></label>
          <div class="input-with-icon">
            <span class="input-icon">
              <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </span>
            <input type="text" id="login-username" name="username" class="form-control" placeholder="e.g. admin" required autofocus>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="login-password">Password <span class="required">*</span></label>
          <div class="input-with-icon">
            <span class="input-icon">
              <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </span>
            <input type="password" id="login-password" name="password" class="form-control" placeholder="********" required>
            <button type="button" class="input-icon-right password-toggle-btn" data-target="login-password" aria-label="Toggle Password Visibility">
              <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block" style="padding: 12px; font-size: 0.95rem; margin-top: 10px;">
          Sign In
        </button>
      </form>

      <!-- Demo Credentials Helper -->
      <div class="demo-accounts-box">
        <div class="demo-box-header">
          <span class="demo-box-title">âš¡ Quick Demo Logins</span>
          <span style="font-size: 0.72rem; color: var(--text-subtle);">Click to auto-fill</span>
        </div>
        <div class="demo-accounts-grid">
          <div class="demo-account-item" data-user="admin" data-pass="Admin@123">
            <div class="demo-account-role">Administrator</div>
            <div class="demo-account-creds">admin / Admin@123</div>
          </div>
          <div class="demo-account-item" data-user="user" data-pass="User@123">
            <div class="demo-account-role">Staff User</div>
            <div class="demo-account-creds">user / User@123</div>
          </div>
        </div>
      </div>

      <div class="auth-switch">
        Don't have an account yet? <a href="register.php" class="font-semibold">Register Staff Account</a>
      </div>
    </div>
  </div>

</div>

<!-- Toast Container -->
<div id="toast-container"></div>

<script src="assets/js/ui.js"></script>
<script src="assets/js/auth.js"></script>

</body>
</html>

