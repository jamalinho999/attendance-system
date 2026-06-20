<?php
session_start();
require_once '../includes/db.php';

// Protect this page — only admins allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit();
}

// Get some quick stats
$studentCount = $pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.role_id WHERE r.role_name = 'student'")->fetchColumn();
$lecturerCount = $pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.role_id WHERE r.role_name = 'lecturer'")->fetchColumn();
$unitCount = $pdo->query("SELECT COUNT(*) FROM units")->fetchColumn();

// Recent users
$recentUsers = $pdo->query("
    SELECT u.full_name, u.university_id, r.role_name, u.created_at 
    FROM users u JOIN roles r ON u.role_id = r.role_id 
    ORDER BY u.created_at DESC LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - AttendEase</title>
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
        .table td { border-color: #1a1f27; }
        .badge-role { font-size: 0.75rem; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand px-3">
        <span class="navbar-brand" style="color:#58a6ff;">AttendEase</span>
        <div class="ms-auto d-flex align-items-center">
            <span class="badge bg-primary me-3">Admin</span>
            <span class="text-secondary me-3"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            <a href="../pages/logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
        </div>
    </nav>
    <div class="d-flex">
        <div class="sidebar p-3" style="width: 200px;">
          <a href="dashboard.php" class="nav-link active rounded p-2 mb-1">Dashboard</a>
<a href="courses.php" class="nav-link rounded p-2 mb-1">Courses</a>
<a href="units.php" class="nav-link rounded p-2 mb-1">Units</a>
<a href="#" class="nav-link rounded p-2 mb-1">Reports</a>  
        </div>
        <div class="flex-grow-1 p-4">
            <h4 class="mb-4">System overview</h4>
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stat-card p-3">
                        <div class="text-secondary small">Total students</div>
                        <div class="fs-3 fw-medium" style="color:#58a6ff;"><?php echo $studentCount; ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card p-3">
                        <div class="text-secondary small">Lecturers</div>
                        <div class="fs-3 fw-medium"><?php echo $lecturerCount; ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card p-3">
                        <div class="text-secondary small">Active units</div>
                        <div class="fs-3 fw-medium"><?php echo $unitCount; ?></div>
                    </div>
                </div>
            </div>
            <h5 class="mb-3">Recent registrations</h5>
            <div class="stat-card p-3">
                <table class="table table-borderless mb-0">
                    <thead>
                        <tr><th>Name</th><th>University ID</th><th>Role</th><th>Joined</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentUsers as $u): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($u['university_id']); ?></td>
                            <td><span class="badge bg-secondary badge-role"><?php echo htmlspecialchars($u['role_name']); ?></span></td>
                            <td><?php echo date('d M', strtotime($u['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>