<?php
/**
 * Common App Layout Header & Navigation
 * Visitor Management System (VMS)
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

// Enforce login for all protected pages using this header
requireLogin(false);

$currentUser = getCurrentUser();
$isAdmin = isAdmin();
$activePage = $activePage ?? 'dashboard';
$pageTitle = $pageTitle ?? 'Dashboard';

// Generate user initials for avatar
$nameParts = explode(' ', trim($currentUser['full_name']));
$initials = strtoupper(substr($nameParts[0] ?? 'U', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= escapeHtml($pageTitle) ?> | VMS Enterprise</title>
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="stylesheet" href="assets/css/dashboard.css">
  <?php if (!empty($extraCss)): ?>
    <link rel="stylesheet" href="<?= escapeHtml($extraCss) ?>">
  <?php endif; ?>
</head>
<body data-user-role="<?= escapeHtml($currentUser['role']) ?>" data-user-id="<?= (int) $currentUser['user_id'] ?>">

<div class="sidebar-overlay"></div>

<div class="app-container">

  <!-- Sidebar -->
  <aside class="app-sidebar">
    <div class="sidebar-brand">
      <div class="sidebar-brand-icon">
        <svg width="24" height="24" fill="none" stroke="#ffffff" stroke-width="2.2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>
      </div>
      <div style="display: flex; align-items: baseline; gap: 6px;">
        <span style="font-size: 1.35rem; font-weight: 800; color: #ffffff; letter-spacing: -0.02em;">VMS</span>
        <span style="font-size: 0.92rem; color: #94a3b8; font-weight: 400;">Enterprise</span>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="sidebar-section-title">ADMIN COMMAND</div>
      
      <a href="dashboard.php" class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>">
        <span class="nav-icon">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
        </span>
        <span>Admin Dashboard</span>
      </a>

      <a href="visitors.php" class="nav-link <?= $activePage === 'visitors' ? 'active' : '' ?>">
        <span class="nav-icon">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        </span>
        <span>Visitor Directory</span>
        <span class="nav-badge" id="nav-checked-in-badge" style="display:none;">0</span>
      </a>

      <a href="reports.php" class="nav-link <?= $activePage === 'reports' ? 'active' : '' ?>">
        <span class="nav-icon">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </span>
        <span>Audit Reports</span>
      </a>

      <?php if ($isAdmin): ?>
      <div class="sidebar-section-title">ADMINISTRATION</div>
      
      <a href="users.php" class="nav-link <?= $activePage === 'users' ? 'active' : '' ?>">
        <span class="nav-icon">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        </span>
        <span>User Accounts</span>
      </a>
      <?php endif; ?>

      <div class="sidebar-section-title">SUPPORT</div>

      <a href="help.php" class="nav-link <?= $activePage === 'help' ? 'active' : '' ?>">
        <span class="nav-icon">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </span>
        <span>Help & Manual</span>
      </a>

      <a href="logout.php" class="nav-link logout-link" style="color: #f87171;">
        <span class="nav-icon">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        </span>
        <span>Logout</span>
      </a>
    </nav>

    <div class="sidebar-footer">
      <div class="user-card-mini">
        <div class="user-avatar-circle admin-avatar" style="background: #0284c7; width: 44px; height: 44px; font-weight: 800; font-size: 0.95rem; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><?= escapeHtml($initials) ?></div>
        <div class="user-meta-mini">
          <div class="admin-name" style="color: #ffffff; font-weight: 700; font-size: 0.92rem; line-height: 1.2;"><?= escapeHtml($currentUser['full_name']) ?></div>
          <div class="admin-sub" style="color: #38bdf8; font-size: 0.78rem; font-weight: 500; margin-top: 2px;"><?= $isAdmin ? 'Administrator' : 'Operator' ?></div>
        </div>
      </div>
    </div>
  </aside>

  <!-- Main Content Wrapper -->
  <main class="app-main">
    
    <!-- Topbar -->
    <header class="app-topbar">
      <div class="topbar-left">
        <button class="mobile-menu-btn" id="mobile-menu-btn" type="button" aria-label="Toggle Menu">
          <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div class="topbar-title-wrap">
          <h1><?= escapeHtml($pageTitle) ?></h1>
        </div>
      </div>

      <div class="topbar-right">
        <div class="topbar-clock">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"/></svg>
          <span id="topbar-live-clock"><?= date('D, M d, Y') ?></span>
        </div>

        <?php if ($isAdmin): ?>
          <span class="badge badge-admin">Admin</span>
        <?php else: ?>
          <span class="badge badge-user">Staff</span>
        <?php endif; ?>

        <a href="logout.php" class="btn btn-sm btn-secondary" title="Sign Out" style="padding: 7px 10px;">
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        </a>
      </div>
    </header>

    <!-- Page Body Starts -->
    <div class="app-body">

