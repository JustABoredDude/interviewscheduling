<?php
session_start();
require_once 'db.php';

// Ensure user is authenticated (add your authentication logic)
// if (!isset($_SESSION['user']) || $_SESSION['user_type'] !== 'faculty') {
//     die(json_encode(['success' => false, 'message' => 'Unauthorized']));
// }

header('Content-Type: application/json');

// Validate input
// $interview_id = $_POST['interview_id'] ?? null;
$interview_id = $_POST['interview_id'] ?? null;
$score = $_POST['score'] ?? null;
$comments = $_POST['comments'] ?? null;

if (!$interview_id || $score === null || !$comments) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Prepare SQL to insert feedback into faculty table
$sql = "INSERT INTO faculty (interview_id, feedback_score, feedback_comments, feedback_timestamp) 
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
        feedback_score = ?, 
        feedback_comments = ?, 
        feedback_timestamp = NOW()";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "iisss", 
    $interview_id, 
    $score, 
    $comments, 
    $score, 
    $comments
);

if ($stmt->execute()) {
    // Optionally update interview status
    $update_status_sql = "UPDATE interviews SET status = 'completed' WHERE id = ?";
    $status_stmt = $conn->prepare($update_status_sql);
    $status_stmt->bind_param("i", $interview_id);
    $status_stmt->execute();

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>