<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit();
}

// Overall system attendance stats
$totalSessions = $pdo->query("SELECT COUNT(*) FROM sessions")->fetchColumn();
$totalAttendance = $pdo->query("SELECT COUNT(*) FROM attendance WHERE status = 'present'")->fetchColumn();

// Per unit attendance summary
$unitStats = $pdo->query("
    SELECT 
        units.unit_code,
        units.unit_name,
        courses.course_name,
        units.year,
        units.semester,
        users.full_name AS lecturer_name,
        COUNT(DISTINCT sessions.session_id) AS total_sessions,
        COUNT(DISTINCT attendance.attendance_id) AS total_present
    FROM units
    JOIN courses ON units.course_id = courses.course_id
    JOIN users ON units.lecturer_id = users.user_id
    LEFT JOIN sessions ON sessions.unit_id = units.unit_id
    LEFT JOIN attendance ON attendance.session_id = sessions.session_id AND attendance.status = 'present'
    GROUP BY units.unit_id
    ORDER BY courses.course_name, units.year, units.semester
")->fetchAll();

// Students below 75% in any unit
$atRiskStudents = $pdo->query("
    SELECT 
        u.full_name,
        u.university_id,
        un.unit_name,
        un.unit_code,
        COUNT(DISTINCT s.session_id) AS total_sessions,
        COUNT(DISTINCT CASE WHEN a.status = 'present' THEN a.attendance_id END) AS attended,
        ROUND(COUNT(DISTINCT CASE WHEN a.status = 'present' THEN a.attendance_id END) / 
              NULLIF(COUNT(DISTINCT s.session_id), 0) * 100, 1) AS percentage
    FROM users u
    JOIN roles r ON u.role_id = r.role_id
    JOIN units un ON un.course_id = u.course_id AND un.year = u.current_year AND un.semester = u.current_semester
    LEFT JOIN sessions s ON s.unit_id = un.unit_id
    LEFT JOIN attendance a ON a.session_id = s.session_id AND a.student_id = u.user_id
    WHERE r.role_name = 'student'
    GROUP BY u.user_id, un.unit_id
    HAVING percentage < 75 AND total_sessions > 0
    ORDER BY percentage ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports - AttendEase</title>
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
        .section-title { font-size: 13px; font-weight: 500; color: #8b949e; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 10px; }
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
        <?php include 'sidebar.php'; ?>
        <div class="flex-grow-1 p-4">
            <h4 class="mb-4">Attendance Reports</h4>

            <!-- Quick stats -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card p-3">
                        <div class="text-secondary small">Total Sessions Held</div>
                        <div class="fs-3 fw-medium" style="color:#58a6ff;"><?php echo $totalSessions; ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card p-3">
                        <div class="text-secondary small">Total Attendances Recorded</div>
                        <div class="fs-3 fw-medium" style="color:#7ee787;"><?php echo $totalAttendance; ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card p-3">
                        <div class="text-secondary small">Students At Risk</div>
                        <div class="fs-3 fw-medium" style="color:#e3b341;"><?php echo count($atRiskStudents); ?></div>
                    </div>
                </div>
            </div>

            <!-- Per unit summary -->
            <div class="section-title">Attendance by Unit</div>
            <div class="stat-card p-3 mb-4">
                <table class="table table-borderless mb-0">
                    <thead>
                        <tr><th>Code</th><th>Unit</th><th>Course</th><th>Yr/Sem</th><th>Lecturer</th><th>Sessions</th><th>Attendances</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($unitStats as $u): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($u['unit_code']); ?></td>
                            <td><?php echo htmlspecialchars($u['unit_name']); ?></td>
                            <td><?php echo htmlspecialchars($u['course_name']); ?></td>
                            <td>Y<?php echo $u['year']; ?> S<?php echo $u['semester']; ?></td>
                            <td><?php echo htmlspecialchars($u['lecturer_name']); ?></td>
                            <td><?php echo $u['total_sessions']; ?></td>
                            <td><?php echo $u['total_present']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- At risk students -->
            <div class="section-title">Students Below 75% Threshold</div>
            <div class="stat-card p-3">
                <?php if (empty($atRiskStudents)): ?>
                    <p class="text-secondary mb-0">No students currently below the 75% threshold.</p>
                <?php else: ?>
                <table class="table table-borderless mb-0">
                    <thead>
                        <tr><th>Student</th><th>University ID</th><th>Unit</th><th>Sessions</th><th>Attended</th><th>%</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($atRiskStudents as $s): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($s['university_id']); ?></td>
                            <td><?php echo htmlspecialchars($s['unit_code'] . ' - ' . $s['unit_name']); ?></td>
                            <td><?php echo $s['total_sessions']; ?></td>
                            <td><?php echo $s['attended']; ?></td>
                            <td style="color:#e3b341;"><?php echo $s['percentage']; ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>