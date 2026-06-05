<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Check admin permissions
function hasPermission(string $perm): bool {
    if (!isset($_SESSION['admin_id'])) return false;
    if ($_SESSION['admin_permissions'] === 'all') return true;
    return in_array($perm, explode(',', $_SESSION['admin_permissions'] ?? ''));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — Admin | ' . SITE_NAME : 'Admin Panel | ' . SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; }

        .admin-sidebar {
            width: 250px;
            min-height: 100vh;
            background: var(--dark);
            position: fixed;
            top: 0; left: 0;
            z-index: 100;
            display: flex;
            flex-direction: column;
        }
        .admin-sidebar .brand {
            padding: 20px 22px;
            font-family: 'Poppins',sans-serif;
            font-weight: 700;
            font-size: 1.2rem;
            color: #fff;
            border-bottom: 1px solid rgba(255,255,255,.1);
        }
        .admin-sidebar .brand span { color: var(--accent); }
        .admin-sidebar .nav-label {
            font-size: 10px;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: rgba(255,255,255,.35);
            padding: 18px 22px 6px;
        }
        .admin-sidebar a.nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 22px;
            color: rgba(255,255,255,.70);
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all .18s;
            border-left: 3px solid transparent;
        }
        .admin-sidebar a.nav-item:hover,
        .admin-sidebar a.nav-item.active {
            color: #fff;
            background: rgba(255,255,255,.08);
            border-left-color: var(--accent);
        }
        .admin-sidebar a.nav-item i { width: 18px; }

        .admin-topbar {
            position: fixed;
            top: 0; left: 250px;
            right: 0;
            height: 60px;
            background: #fff;
            border-bottom: 1px solid #e9ecef;
            z-index: 99;
            display: flex;
            align-items: center;
            padding: 0 24px;
            justify-content: space-between;
        }
        .admin-content {
            margin-left: 250px;
            margin-top: 60px;
            padding: 28px;
            min-height: calc(100vh - 60px);
        }
        .admin-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,.07);
            overflow: hidden;
        }
        .admin-card-header {
            padding: 16px 20px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .admin-card-header h5 { margin: 0; font-family:'Poppins',sans-serif; font-size:15px; }
        .admin-stat {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,.07);
        }
        @media (max-width: 768px) {
            .admin-sidebar { display: none; }
            .admin-topbar  { left: 0; }
            .admin-content { margin-left: 0; }
        }
    </style>
</head>
<body>