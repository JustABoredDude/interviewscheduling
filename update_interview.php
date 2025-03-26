<?php
session_start();
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id']);
    $scheduled_time = $_POST['scheduled_time'];
    $program_id = intval($_POST['program']);
    $interviewer_id = intval($_POST['interviewer']);
    $meet_type = $_POST['meet_type'] ?? '';
    $feedback = ($meet_type === 'F2F' || $meet_type === 'Online') ? ($_POST['feedback'] ?? '') : '';

    $stmt = $conn->prepare("UPDATE interviews SET scheduled_time = ?, program_id = ?, interviewer_id = ?, meet_type = ?, feedback = ? WHERE id = ?");
    $stmt->bind_param("siissi", $scheduled_time, $program_id, $interviewer_id, $meet_type, $feedback, $id);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}

?>