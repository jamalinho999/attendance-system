<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lecturer') {
    header("Location: ../pages/login.php");
    exit();
}

$lecturer_id = $_SESSION['user_id'];
$error = "";
$activeSession = null;

// Get units taught by this lecturer
$units = $pdo->prepare("SELECT * FROM units WHERE lecturer_id = ?");
$units->execute([$lecturer_id]);
$units = $units->fetchAll();

// Handle Open Session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['open_session'])) {
    $unit_id = $_POST['unit_id'];
    $duration_minutes = 15; // session stays open for 15 minutes

    // Generate random 4-digit code
    $code = strval(random_int(1000, 9999));
    $expires_at = date('Y-m-d H:i:s', strtotime("+$duration_minutes minutes"));

    $insert = $pdo->prepare("INSERT INTO sessions (unit_id, session_date, session_code, expires_at) VALUES (?, CURDATE(), ?, ?)");
    $insert->execute([$unit_id, $code, $expires_at]);

    $_SESSION['active_session_id'] = $pdo->lastInsertId();
}

// Check for an active session (not expired) belonging to this lecturer
if (isset($_SESSION['active_session_id'])) {
    $check = $pdo->prepare("
        SELECT sessions.*, units.unit_name, units.unit_code 
        FROM sessions 
        JOIN units ON sessions.unit_id = units.unit_id
        WHERE sessions.session_id = ? AND units.lecturer_id = ? AND sessions.expires_at > NOW()
    ");
    $check->execute([$_SESSION['active_session_id'], $lecturer_id]);
    $activeSession = $check->fetch();

    if (!$activeSession) {
        unset($_SESSION['active_session_id']); // session expired, clear it
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Open Session - AttendEase</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0d1117; color: #e6edf3; }
        .navbar { background-color: #161b22; border-bottom: 1px solid #2a2d35; }
        .stat-card { background-color: #161b22; border: 1px solid #2a2d35; border-radius: 8px; }
        .form-select, .form-control { background-color: #0d1117; color: #e6edf3; border: 1px solid #30363d; }
        .code-display { font-size: 4rem; font-weight: 700; letter-spacing: 0.3em; color: #58a6ff; text-align: center; }
        .timer { text-align: center; font-size: 1.1rem; color: #e3b341; }
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
   
    <div class="container py-5">
        <?php if ($activeSession): ?>
            <div class="stat-card p-5 mx-auto" style="max-width: 500px;">
                <h5 class="text-center text-secondary mb-1"><?php echo htmlspecialchars($activeSession['unit_name']); ?></h5>
                <p class="text-center text-secondary mb-4">Show this code to your class</p>
                <div class="code-display"><?php echo htmlspecialchars($activeSession['session_code']); ?></div>
                <p class="timer mt-3" id="countdown" data-expires="<?php echo $activeSession['expires_at']; ?>"></p>
                <p class="text-center mt-4">
                    <a href="open_session.php" class="btn btn-outline-secondary btn-sm">Refresh</a>
                </p>
            </div>
            <script>
                const expiresAt = new Date("<?php echo $activeSession['expires_at']; ?>").getTime();
                function updateCountdown() {
                    const now = new Date().getTime();
                    const diff = expiresAt - now;
                    if (diff <= 0) {
                        document.getElementById('countdown').innerText = "Session expired";
                        return;
                    }
                    const mins = Math.floor(diff / 60000);
                    const secs = Math.floor((diff % 60000) / 1000);
                    document.getElementById('countdown').innerText = `Expires in ${mins}m ${secs}s`;
                    setTimeout(updateCountdown, 1000);
                }
                updateCountdown();
            </script>
        <?php else: ?>
            <div class="stat-card p-4 mx-auto" style="max-width: 450px;">
                <h5 class="mb-3">Open an attendance session</h5>
                <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="text-secondary">Select Unit</label>
                        <select name="unit_id" class="form-select" required>
                            <?php foreach ($units as $u): ?>
                                <option value="<?php echo $u['unit_id']; ?>"><?php echo htmlspecialchars($u['unit_code'] . ' - ' . $u['unit_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="open_session" class="btn btn-primary w-100">Open Session</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>