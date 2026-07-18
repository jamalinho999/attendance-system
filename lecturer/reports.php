<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lecturer') {
    header("Location: ../pages/login.php");
    exit();
}

$lecturer_id = $_SESSION['user_id'];

// Get all units for this lecturer
$units = $pdo->prepare("SELECT * FROM units WHERE lecturer_id = ?");
$units->execute([$lecturer_id]);
$units = $units->fetchAll();

// Selected unit filter
$selected_unit = $_GET['unit_id'] ?? ($units[0]['unit_id'] ?? null);

$sessionRecords = [];
$studentSummary = [];

if ($selected_unit) {
    // Get all sessions for selected unit
    $sessionRecords = $pdo->prepare("
        SELECT sessions.*,
            COUNT(CASE WHEN attendance.status = 'present' THEN 1 END) AS present_count,
            COUNT(CASE WHEN attendance.status = 'absent' THEN 1 END) AS absent_count
        FROM sessions
        LEFT JOIN attendance ON attendance.session_id = sessions.session_id
        WHERE sessions.unit_id = ?
        GROUP BY sessions.session_id
        ORDER BY sessions.session_date DESC
    ");
    $sessionRecords->execute([$selected_unit]);
    $sessionRecords = $sessionRecords->fetchAll();

    // Get per-student summary for selected unit
    $studentSummary = $pdo->prepare("
        SELECT 
            u.full_name,
            u.university_id,
            COUNT(DISTINCT s.session_id) AS total_sessions,
            COUNT(DISTINCT CASE WHEN a.status = 'present' THEN a.attendance_id END) AS attended,
            ROUND(COUNT(DISTINCT CASE WHEN a.status = 'present' THEN a.attendance_id END) /
                  NULLIF(COUNT(DISTINCT s.session_id), 0) * 100, 1) AS percentage
        FROM users u
        JOIN roles r ON u.role_id = r.role_id
        JOIN units un ON un.course_id = u.course_id 
            AND un.year = u.current_year 
            AND un.semester = u.current_semester
            AND un.unit_id = ?
        LEFT JOIN sessions s ON s.unit_id = un.unit_id
        LEFT JOIN attendance a ON a.session_id = s.session_id AND a.student_id = u.user_id
        WHERE r.role_name = 'student'
        GROUP BY u.user_id
        ORDER BY percentage ASC
    ");
    $studentSummary->execute([$selected_unit]);
    $studentSummary = $studentSummary->fetchAll();
}
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
        .nav-link.active { color: #7ee787 !important; background-color: #161b22; }
        .stat-card { background-color: #161b22; border: 1px solid #2a2d35; border-radius: 8px; }
        .table { color: #c9d1d9; }
        .table th { color: #8b949e; border-color: #2a2d35; }
        .table td { border-color: #1a1f27; vertical-align: middle; }
        .form-select { background-color: #0d1117; color: #e6edf3; border: 1px solid #30363d; }
        .form-select:focus { background-color: #0d1117; color: #e6edf3; border-color: #58a6ff; box-shadow: none; }
        .section-title { font-size: 13px; font-weight: 500; color: #8b949e; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 10px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand px-3 no-print">
        <span class="navbar-brand" style="color:#58a6ff;">AttendEase</span>
        <div class="ms-auto d-flex align-items-center">
            <span class="badge bg-success me-3">Lecturer</span>
            <span class="text-secondary me-3"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            <a href="../pages/logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
        </div>
    </nav>
    <div class="d-flex">
        <?php include 'sidebar.php'; ?>
        <div class="flex-grow-1 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">Attendance Reports</h4>
                <button onclick="window.print()" class="btn btn-sm btn-outline-secondary no-print">Print / Save PDF</button>
            </div>

            <!-- Unit selector -->
            <div class="stat-card p-3 mb-4 no-print">
                <form method="GET" class="d-flex align-items-center gap-3">
                    <label class="text-secondary mb-0">Select Unit:</label>
                    <select name="unit_id" class="form-select" style="width:auto;" onchange="this.form.submit()">
                        <?php foreach ($units as $u): ?>
                            <option value="<?php echo $u['unit_id']; ?>" <?php echo ($u['unit_id'] == $selected_unit) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($u['unit_code'] . ' - ' . $u['unit_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <?php if ($selected_unit): ?>

            <!-- Session summary -->
            <div class="section-title">Sessions Held</div>
            <div class="stat-card p-3 mb-4">
                <?php if (empty($sessionRecords)): ?>
                    <p class="text-secondary mb-0">No sessions held for this unit yet.</p>
                <?php else: ?>
                <table class="table table-borderless mb-0">
                    <thead>
                        <tr><th>Date</th><th>Code</th><th>Present</th><th>Absent</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessionRecords as $s): ?>
                        <tr>
                            <td><?php echo date('d M Y', strtotime($s['session_date'])); ?></td>
                            <td><?php echo htmlspecialchars($s['session_code']); ?></td>
                            <td style="color:#7ee787;"><?php echo $s['present_count']; ?></td>
                            <td style="color:#f85149;"><?php echo $s['absent_count']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <!-- Student summary -->
            <div class="section-title">Student Attendance Summary</div>
            <div class="stat-card p-3">
                <?php if (empty($studentSummary)): ?>
                    <p class="text-secondary mb-0">No students enrolled in this unit yet.</p>
                <?php else: ?>
                <table class="table table-borderless mb-0">
                    <thead>
                        <tr><th>Student</th><th>University ID</th><th>Sessions</th><th>Attended</th><th>%</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($studentSummary as $s): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($s['university_id']); ?></td>
                            <td><?php echo $s['total_sessions']; ?></td>
                            <td><?php echo $s['attended']; ?></td>
                            <td style="color:<?php echo $s['percentage'] >= 75 ? '#7ee787' : '#e3b341'; ?>">
                                <?php echo $s['percentage']; ?>%
                            </td>
                            <td>
                                <span class="badge" style="background-color:<?php echo $s['percentage'] >= 75 ? '#1a3a2a' : '#2a2215'; ?>; color:<?php echo $s['percentage'] >= 75 ? '#7ee787' : '#e3b341'; ?>">
                                    <?php echo $s['percentage'] >= 75 ? 'Eligible' : 'At Risk'; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <?php endif; ?>
        </div>
    </div>
</body>
</html>