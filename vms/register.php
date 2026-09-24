<?php






/**
 * Modern Split-Screen Registration Page
 * Visitor Management System (VMS)
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$authError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fullName = trim($_POST['full_name'] ?? '');
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';
  $confirmPassword = $_POST['confirm_password'] ?? '';

  if ($fullName === '' || $username === '' || $password === '' || $confirmPassword === '') {
    $authError = 'All fields are required.';
  } elseif (strlen($fullName) < 2) {
    $authError = 'Full name must be at least 2 characters.';
  } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
    $authError = 'Username must be 3-30 alphanumeric characters or underscores.';
  } elseif (strlen($password) < 6) {
    $authError = 'Password must be at least 6 characters long.';
  } elseif ($password !== $confirmPassword) {
    $authError = 'Password confirmation does not match.';
  } else {
    $conn = getDbConnection();
    $checkStmt = $conn->prepare('SELECT UserID FROM Users WHERE Username = ? LIMIT 1');
    $checkStmt->execute([$username]);

    if ($checkStmt->get_result()->fetch_assoc()) {
      $authError = 'Username is already taken. Please choose another one.';
    } else {
      $insertStmt = $conn->prepare('
        INSERT INTO Users (Username, Password, FullName, Role, Status, CreatedAt)
        VALUES (?, ?, ?, "user", "active", NOW())
      ');
      $insertStmt->execute([$username, password_hash($password, PASSWORD_BCRYPT), $fullName]);
      $newUser = [
        'UserID' => (int) $conn->insert_id,
        'Username' => $username,
        'FullName' => $fullName,
        'Role' => 'user'
      ];
      loginUser($newUser);
      header('Location: dashboard.php');
      exit;
    }
  }
}

// Redirect authenticated users
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
  <title>Create Account | VMS Enterprise</title>
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
        Join the <span>Front Desk</span> Team
      </h1>
      <p class="brand-hero-desc">
        Create your organization staff account to begin recording visitor check-ins, managing department host schedules, and maintaining secure visitor logs.
      </p>

      <ul class="brand-features">
        <li class="brand-feature-item">
          <span class="brand-feature-icon">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
          </span>
          <span>Seamless self-registration for authorized staff</span>
        </li>
        <li class="brand-feature-item">
          <span class="brand-feature-icon">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
          </span>
          <span>Encrypted credential storage with Bcrypt</span>
        </li>
        <li class="brand-feature-item">
          <span class="brand-feature-icon">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
          </span>
          <span>Immediate dashboard access upon registration</span>
        </li>
      </ul>
    </div>

    <div class="brand-footer">
      &copy; <?= date('Y') ?> Enterprise VMS. Internal access only.
    </div>
  </div>

  <!-- Right Form Side -->
  <div class="auth-form-side">
    <div class="auth-card">
      <div class="auth-header">
        <h2>Register Account</h2>
        <p>Create your credentials to access the visitor system</p>
      </div>

      <div id="auth-alert" class="auth-alert<?= $authError !== '' ? ' error' : '' ?>"<?= $authError !== '' ? ' style="display: flex;"' : '' ?>><?= escapeHtml($authError) ?></div>

      <form id="register-form" method="post" action="register.php" autocomplete="off">
        <div class="form-group">
          <label class="form-label" for="reg-fullname">Full Name <span class="required">*</span></label>
          <div class="input-with-icon">
            <span class="input-icon">
              <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </span>
            <input type="text" id="reg-fullname" name="full_name" class="form-control" placeholder="e.g. John Doe" required autofocus>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="reg-username">Username <span class="required">*</span></label>
          <div class="input-with-icon">
            <span class="input-icon">
              <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
            </span>
            <input type="text" id="reg-username" name="username" class="form-control" placeholder="e.g. jdoe_reception" required>
          </div>
          <div class="form-text">3-30 letters, numbers, or underscores.</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="reg-password">Password <span class="required">*</span></label>
          <div class="input-with-icon">
            <span class="input-icon">
              <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </span>
            <input type="password" id="reg-password" name="password" class="form-control" required>
            <button type="button" class="input-icon-right password-toggle-btn" data-target="reg-password" aria-label="Toggle Password Visibility">
              <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
          </div>
          <div class="strength-meter-bar">
            <div id="strength-bar" class="strength-meter-fill"></div>
          </div>
          <div id="strength-text" class="strength-text"></div>
        </div>

        <div class="form-group">
          <label class="form-label" for="reg-confirm-password">Confirm Password <span class="required">*</span></label>
          <div class="input-with-icon">
            <span class="input-icon">
              <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
            <input type="password" id="reg-confirm-password" name="confirm_password" class="form-control" placeholder="Re-type password" required>
            <button type="button" class="input-icon-right password-toggle-btn" data-target="reg-confirm-password" aria-label="Toggle Password Visibility">
              <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
          </div>
        </div>

        <button type="submit" class="btn btn-teal btn-block" style="padding: 12px; font-size: 0.95rem; margin-top: 10px;">
          Create Account
        </button>
      </form>

      <div class="auth-switch">
        Already have an account? <a href="login.php" class="font-semibold">Sign In instead</a>
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

