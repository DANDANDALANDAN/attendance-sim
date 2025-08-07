<?php
session_start();
header('Content-Type: application/json');

require '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate input
$action = $_POST['action'] ?? '';
$id = $_POST['id'] ?? '';

if ($action !== 'delete' || empty($id) || !is_numeric($id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    // Begin transaction to ensure atomicity
    $pdo->beginTransaction();

    // Delete attendance records linked to the student
    $stmt1 = $pdo->prepare("DELETE FROM attendance WHERE student_id = ?");
    $stmt1->execute([$id]);

    // Delete the student record
    $stmt2 = $pdo->prepare("DELETE FROM students WHERE id = ?");
    $stmt2->execute([$id]);

    // Commit changes
    $pdo->commit();

    if ($stmt2->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Student and related attendance deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Student not found or already deleted']);
    }
} catch (PDOException $e) {
    // Roll back on error
    $pdo->rollBack();

    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
