<?php
session_start();
header('Content-Type: application/json');

require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$id = intval($_POST['id']);
if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid student ID']);
    exit;
}

try {
    // Set your timezone (adjust as needed)
    date_default_timezone_set('Asia/Manila');  // Example timezone

    $now = new DateTime();
    $currentHour = (int)$now->format('H');
    $currentMinute = (int)$now->format('i');

    // Fetch last attendance record for this student
    $stmt = $pdo->prepare("SELECT * FROM attendance WHERE student_id = ? ORDER BY time DESC LIMIT 1");
    $stmt->execute([$id]);
    $last = $stmt->fetch(PDO::FETCH_ASSOC);

    $newType = 'IN';  // default to IN
    if ($last) {
        $lastTime = new DateTime($last['time']);
        $lastType = $last['type'];

        $diffMinutes = ($now->getTimestamp() - $lastTime->getTimestamp()) / 60;

        // Determine IN/OUT based on last scan interval
        if ($diffMinutes < 5) {
            // If last scan was less than 5 mins ago:
            // Switch to OUT if last was IN; else back to IN
            $newType = ($lastType === 'IN') ? 'OUT' : 'IN';
        } else {
            // More than 5 minutes elapsed → default IN
            $newType = 'IN';
        }
    }

    // Override based on exact time checks:

    // Check if exactly 12:00 PM → force OUT
    if ($currentHour === 12 && $currentMinute === 0) {
        $newType = 'OUT';
    } 
    // Exactly 1:00 PM → force IN
    else if ($currentHour === 13 && $currentMinute === 0) {
        $newType = 'IN';
    }
    // Exactly 5:00 PM → force OUT
    else if ($currentHour === 17 && $currentMinute === 0) {
        $newType = 'OUT';
    }

    // Insert attendance record
    $stmtInsert = $pdo->prepare("INSERT INTO attendance (student_id, time, type) VALUES (?, ?, ?)");
    $stmtInsert->execute([$id, $now->format('Y-m-d H:i:s'), $newType]);

    // Fetch student info
    $stmtStudent = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmtStudent->execute([$id]);
    $student = $stmtStudent->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'ok',
        'name' => $student['first_name'] . ' ' . $student['last_name'],
        'section' => $student['grade_level'] . ' - ' . $student['section'],
        'time' => $now->format('h:i A'),
        'type' => $newType
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
}
