<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../pages/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$error = "";
$success = "";

// Kabarak University main campus coordinates
define('CAMPUS_LAT', -0.1671);
define('CAMPUS_LNG', 35.9660);
define('CAMPUS_RADIUS', 500); // metres

// Haversine formula
function haversineDistance($lat1, $lng1, $lat2, $lng2) {
    $earthRadius = 6371000;
    $latDiff = deg2rad($lat2 - $lat1);
    $lngDiff = deg2rad($lng2 - $lng1);
    $a = sin($latDiff/2) * sin($latDiff/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($lngDiff/2) * sin($lngDiff/2);
    return $earthRadius * 2 * atan2(sqrt($a), sqrt(1-$a));
}

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_attendance'])) {
    $code = trim($_POST['code']);
    $lat = floatval($_POST['lat']);
    $lng = floatval($_POST['lng']);

    // Check if GPS coords were actually received
    if ($lat == 0 && $lng == 0) {
        $error = "Location could not be determined. Please allow location access and try again.";
    } else {
        // Check distance from campus
        $distance = haversineDistance($lat, $lng, CAMPUS_LAT, CAMPUS_LNG);

        if ($distance > CAMPUS_RADIUS) {
            $error = "You must be on campus to mark attendance. Your location is " . round($distance) . "m away from campus.";
        } else {
            // Find matching active session with this code
            $stmt = $pdo->prepare("
                SELECT sessions.*, units.unit_name 
                FROM sessions 
                JOIN units ON sessions.unit_id = units.unit_id
                WHERE sessions.session_code = ? AND sessions.expires_at > NOW()
            ");
            $stmt->execute([$code]);
            $session = $stmt->fetch();

            if (!$session) {
                $error = "Invalid or expired code. Please check with your lecturer.";
            } else {
                // Check student is enrolled in this unit's course/year/semester
                $enrollCheck = $pdo->prepare("
                    SELECT units.unit_id FROM units
                    JOIN users ON users.course_id = units.course_id
                    AND users.current_year = units.year
                    AND users.current_semester = units.semester
                    WHERE units.unit_id = ? AND users.user_id = ?
                ");
                $enrollCheck->execute([$session['unit_id'], $student_id]);

                if (!$enrollCheck->fetch()) {
                    $error = "You are not enrolled in this unit.";
                } else {
                    // Check if already marked for this session
                    $alreadyMarked = $pdo->prepare("
                        SELECT * FROM attendance 
                        WHERE session_id = ? AND student_id = ?
                    ");
                    $alreadyMarked->execute([$session['session_id'], $student_id]);

                    if ($alreadyMarked->fetch()) {
                        $error = "You have already marked attendance for this session.";
                    } else {
                        // All checks passed — record attendance
                        $insert = $pdo->prepare("
                            INSERT INTO attendance (session_id, student_id, status) 
                            VALUES (?, ?, 'present')
                        ");
                        $insert->execute([$session['session_id'], $student_id]);
                        $success = "Attendance marked successfully for " . htmlspecialchars($session['unit_name']) . "!";
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mark Attendance - AttendEase</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0d1117; color: #e6edf3; }
        .navbar { background-color: #161b22; border-bottom: 1px solid #2a2d35; }
        .stat-card { background-color: #161b22; border: 1px solid #2a2d35; border-radius: 8px; }
        .form-control { background-color: #0d1117; color: #e6edf3; border: 1px solid #30363d; }
        .form-control:focus { background-color: #0d1117; color: #e6edf3; border-color: #58a6ff; box-shadow: none; }
        label { color: #8b949e; font-size: 0.85rem; }
        .code-input { font-size: 2rem; text-align: center; letter-spacing: 0.5em; font-weight: 700; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand px-3">
        <span class="navbar-brand" style="color:#58a6ff;">AttendEase</span>
        <div class="ms-auto d-flex align-items-center">
            <span class="badge bg-success me-3" style="background-color:#1a3a2a !important; color:#7ee787;">Student</span>
            <span class="text-secondary me-3"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            <a href="../pages/logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
        </div>
    </nav>

    <div class="container py-5">
        <div class="stat-card p-4 mx-auto" style="max-width: 400px;">
            <h5 class="mb-1 text-center">Mark Attendance</h5>
            <p class="text-secondary text-center small mb-4">Enter the code shown by your lecturer</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST" id="attendanceForm">
                <div class="mb-4">
                    <input type="text" name="code" id="codeInput" class="form-control code-input" 
                           maxlength="4" placeholder="0000" required autocomplete="off">
                </div>
                <!-- Hidden GPS fields filled by JavaScript -->
                <input type="hidden" name="lat" id="lat" value="0">
                <input type="hidden" name="lng" id="lng" value="0">
                <button type="submit" name="submit_attendance" id="submitBtn" class="btn btn-primary w-100" disabled>
                    Getting your location...
                </button>
            </form>

            <script>
                // Get GPS coordinates as soon as page loads
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            document.getElementById('lat').value = position.coords.latitude;
                            document.getElementById('lng').value = position.coords.longitude;
                            document.getElementById('submitBtn').disabled = false;
                            document.getElementById('submitBtn').innerText = 'Mark Present';
                        },
                        function(error) {
                            document.getElementById('submitBtn').disabled = false;
                            document.getElementById('submitBtn').innerText = 'Mark Present (No GPS)';
                            document.getElementById('submitBtn').classList.add('btn-warning');
                            document.getElementById('submitBtn').classList.remove('btn-primary');
                        }
                    );
                } else {
                    document.getElementById('submitBtn').disabled = false;
                    document.getElementById('submitBtn').innerText = 'Mark Present (GPS not supported)';
                }
            </script>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>