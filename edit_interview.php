<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$interview_id = $_GET['id'];

// Fetch interview details
$sql = "SELECT i.id, i.interviewer_id, i.applicant_id, i.scheduled_time, 
               i.status, ir.name AS interviewer_name, a.name AS applicant_name, 
               i.program_id, p.name AS program_name, i.meet_type, i.feedback
        FROM interviews i
        JOIN interviewers ir ON i.interviewer_id = ir.id
        JOIN applicants a ON i.applicant_id = a.id
        JOIN programs p ON i.program_id = p.id
        WHERE i.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $interview_id);
$stmt->execute();
$result = $stmt->get_result();
$interview = $result->fetch_assoc();

if (!$interview) {
    header("Location: index.php");
    exit();
}

$interviewers = getInterviewers($conn);
$programs = getPrograms($conn);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Interview</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-50">
    <div class="container mx-auto p-4 max-w-md">
        <!-- Edit Form -->
        <form id="editForm" action="update_interview.php" method="POST" class="space-y-2">
            <input type="hidden" name="id" value="<?= $interview['id'] ?>">

            <!-- Applicant Name -->
            <div>
                <label class="block text-xs font-medium text-gray-600">Applicant Name:</label>
                <input type="text" value="<?= htmlspecialchars($interview['applicant_name']); ?>" class="w-full p-1 text-sm border rounded bg-gray-50" readonly>
            </div>

            <!-- Date & Time -->
            <div>
                <label class="block text-xs font-medium text-gray-600">Date & Time:</label>
                <input type="datetime-local" name="scheduled_time" value="<?= date('Y-m-d\TH:i', strtotime($interview['scheduled_time'])) ?>" class="w-full p-1 text-sm border rounded" required>
            </div>

            <!-- Program -->
            <div>
                <label class="block text-xs font-medium text-gray-600">Program:</label>
                <select name="program" class="w-full p-1 text-sm border rounded bg-white" required>
                    <option value="" hidden>Select a Program</option>
                    <?php
                    $programs = getPrograms($conn);
                    $colleges = [];
                    foreach ($programs as $program) {
                        $colleges[$program['college']][] = $program;
                    }
                    foreach ($colleges as $college => $courses) {
                        echo "<optgroup label='$college'>";
                        foreach ($courses as $course) {
                            $selected = ($course['id'] == $interview['program_id']) ? 'selected' : '';
                            echo "<option value='{$course['id']}' $selected>{$course['name']}</option>";
                        }
                        echo "</optgroup>";
                    }
                    ?>
                </select>
            </div>

            <!-- Interviewer -->
            <div>
                <label class="block text-xs font-medium text-gray-600">Interviewer:</label>
                <select name="interviewer" class="w-full p-1 text-sm border rounded bg-white" required>
                    <?php foreach ($interviewers as $interviewer): ?>
                        <option value="<?= $interviewer['id'] ?>" <?= ($interviewer['id'] == $interview['interviewer_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($interviewer['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Meet Type -->
            <div>
                <label class="block text-xs font-medium text-gray-600">Meet Type:</label>
                <select id="meet_type" name="meet_type" class="w-full p-1 text-sm border rounded bg-white" required>
                    <option value="" disabled>Select Meet Type</option>
                    <option value="F2F" <?= (isset($interview['meet_type']) && $interview['meet_type'] === 'F2F') ? 'selected' : '' ?>>F2F (Face-to-Face)</option>
                    <option value="Online" <?= (isset($interview['meet_type']) && $interview['meet_type'] === 'Online') ? 'selected' : '' ?>>Online</option>
                </select>
            </div>

            <!-- Feedback Field -->
            <div class="feedback-group">
                <label for="feedback" class="block text-xs font-medium text-gray-600">
                    <?= (isset($interview['meet_type']) && $interview['meet_type'] === 'F2F') ? 'Room/Interview Meeting Link' : 'Interview Meeting Link'; ?>
                </label>
                <textarea id="feedback" name="feedback" class="w-full p-1 text-sm border rounded" rows="2" placeholder="<?= (isset($interview['meet_type']) && $interview['meet_type'] === 'F2F') ? 'Enter the room number or meeting link...' : 'Enter the online meeting link...'; ?>"><?= htmlspecialchars($interview['feedback'] ?? ''); ?></textarea>
            </div>

            <!-- Buttons -->
            <div class="flex justify-between pt-2">
                <a href="index.php" class="text-xs bg-red-200 text-red-700 px-3 py-1 rounded font-medium hover:bg-red-00">Cancel</a>
                <button type="submit" class="text-xs bg-green-700 text-white px-3 py-1 rounded font-medium hover:bg-green-600">Confirm</button>
            </div>
        </form>
    </div>

    <script>
        document.querySelector("form").addEventListener("submit", function(event) {
            event.preventDefault(); // Prevent default form submission

            const formData = new FormData(this);

            fetch("update_interview.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                if (data.trim() === "success") {
                    alert("Interview updated successfully!");
                    window.location.href = "index.php"; // Redirect to index.php
                } else {
                    alert("Error: " + data);
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("An error occurred while updating the interview.");
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const meetTypeSelect = document.getElementById('meet_type');
            const feedbackGroup = document.querySelector('.feedback-group');
            const feedbackLabel = feedbackGroup.querySelector('label');
            const feedbackTextarea = document.getElementById('feedback');

            // Show/hide feedback field based on meet type
            function updateFeedbackField() {
                if (meetTypeSelect.value === 'F2F' || meetTypeSelect.value === 'Online') {
                    feedbackGroup.style.display = 'block';
                    feedbackLabel.textContent = meetTypeSelect.value === 'F2F' ? 'Room/Interview Meeting Link' : 'Interview Meeting Link';
                    feedbackTextarea.placeholder = meetTypeSelect.value === 'F2F' ? 'Enter the room number or meeting link...' : 'Enter the online meeting link...';
                } else {
                    feedbackGroup.style.display = 'none';
                    feedbackTextarea.value = '';
                }
            }

            // Initial update
            updateFeedbackField();

            // Update on meet type change
            meetTypeSelect.addEventListener('change', updateFeedbackField);
        });
    </script>
</body>
</html>