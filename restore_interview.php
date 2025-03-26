<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Interview ID is required']);
    exit;
}

$interview_id = intval($_GET['id']);

try {
    // Update the interview status from 'trash' to 'scheduled'
    $stmt = $conn->prepare("UPDATE interviews SET status = 'scheduled' WHERE id = ? AND status = 'trash'");
    $stmt->bind_param("i", $interview_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Interview restored successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Interview not found or already restored']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>