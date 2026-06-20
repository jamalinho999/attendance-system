<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lecturer') {
    header("Location: ../pages/login.php");
    exit();
}

$lecturer_id = $_SESSION['user_id'];

// Get units taught by this lecturer
$units = $pdo->prepare("SELECT * FROM units WHERE lecturer_id = ?");
$units->execute([$lecturer_id]);
$units = $units->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lecturer Dashboard - AttendEase</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0d1117; color: #e6edf3; }
        .navbar { background-color: #161b22; border-bottom: 1px solid #2a2d35; }
        .sidebar { background-color: #0d1117; border-right: 1px solid #2a2d35; min-height: calc(100vh - 56px); }
        .nav-link { color: #8b949e; }
        .nav-link.active { color: #58a6ff !important; background-color: #161b22; }
        .stat-card { background-color: #161b22; border: 1px solid #2a2d35; border-radius: 8px; }
        .table { color: #c9d1d9; }
        .table th { color: #8b949e; border-color: #2a2d35; }
        .table td { border-color: #1a1f27; vertical-align: middle; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand px-3">
        <span class="navbar-brand" style="color:#58a6ff;">AttendEase</span>
        <div class="ms-auto d-flex align-items-center">
            <span class="badge bg-success me-3">Lecturer</span>
            <span class="text-secondary me-3"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            <a href="../pages/logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
        </div>
    </nav>
    <div class="d-flex">
        <div class="sidebar p-3" style="width: 200px;">
            <a href="dashboard.php" class="nav-link active rounded p-2 mb-1">Dashboard</a>
            <a href="open_session.php" class="nav-link rounded p-2 mb-1">Open Session</a>
            <a href="#" class="nav-link rounded p-2 mb-1">Reports</a>
        </div>
        <div class="flex-grow-1 p-4">
            <h4 class="mb-4">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h4>
            <h6 class="text-secondary mb-3">Your units</h6>
            <div class="stat-card p-3">
                <table class="table table-borderless mb-0">
                    <thead>
                        <tr><th>Code</th><th>Unit Name</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($units as $u): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($u['unit_code']); ?></td>
                            <td><?php echo htmlspecialchars($u['unit_name']); ?></td>
                            <td><a href="open_session.php" class="btn btn-sm btn-primary">Open Attendance</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>