<?php
require_once 'db.php';
require_once 'functions.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: Interview ID is missing.");
}

$id = intval($_GET['id']);
$interview = getInterviewById($conn, $id);

if (!$interview) {
    echo "Error: Interview not found.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Interview</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        .detail-label {
            @apply text-sm font-medium text-gray-600 mb-1;
        }
        .detail-value {
            @apply text-gray-800 text-base mb-3;
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
    <!-- View Interview Card - Balanced for readability -->
    <div class="bg-white w-full max-w-md rounded-lg shadow-md overflow-hidden">
        <!-- Header -->
        <div class="bg-blue-600 px-5 py-3">
            <h2 class="text-lg font-semibold text-white">Interview Details</h2>
        </div>

        <!-- Interview Details -->
        <div class="p-5">
            <!-- Applicant Name -->
            <div class="mb-3">
                <label class="detail-label font-medium">Applicant Name:</label>
                <p class="detail-value "><?= !empty($interview['applicant_name']) ? htmlspecialchars($interview['applicant_name']) : 'No Name Provided' ?></p>
            </div>

            <!-- Email -->
            <div class="mb-3">
                <label class="detail-label font-medium">Email:</label>
                <p class="detail-value"><?= !empty($interview['applicant_email']) ? htmlspecialchars($interview['applicant_email']) : 'No Email Provided' ?></p>
            </div>

            <!-- Date & Time -->
            <div class="mb-3">
                <label class="detail-label font-medium ">Date & Time:</label>
                <p class="detail-value"><?= date("F j, Y, g:i A", strtotime($interview['scheduled_time'])) ?></p>
            </div>

            <!-- Program -->
            <div class="mb-3">
                <label class="detail-label font-medium">Program:</label>
                <p class="detail-value"><?= !empty($interview['program_college']) ? htmlspecialchars($interview['program_college']) : 'No Program Assigned' ?></p>
            </div>

            <!-- Interviewer -->
            <div class="mb-3">
                <label class="detail-label font-medium">Interviewer:</label>
                <p class="detail-value"><?= !empty($interview['interviewer_name']) ? htmlspecialchars($interview['interviewer_name']) : 'No Interviewer Assigned' ?></p>
            </div>

            <!-- Status -->
            <div class="mb-3">
                <label class="detail-label font-medium">Status:</label>
                <p class="detail-value"><?= htmlspecialchars($interview['status']) ?></p>
            </div>
            
            <!-- Meet Type -->
            <div class="mb-3">
                <label class="detail-label font-medium">Meet Type:</label>
                <p class="detail-value"><?= htmlspecialchars($interview['meet_type'] ?? 'N/A'); ?></p>
            </div>

            <!-- Created At -->
            <div class="mb-4">
                <label class="detail-label font-medium">Created at:</label>
                <p class="detail-value"><?= date('F d, Y h:i A', strtotime($interview['created_at'] ?? 'now')) ?></p>
            </div>

            <!-- Buttons -->
            <div class="flex justify-between space-x-4 pt-3">
                <a href="index.php" class="text-xs bg-red-200 text-red-700 px-3 py-1 rounded font-medium hover:bg-red-300">
                    Back 
                </a>
                <button onclick="openEditModal(<?= $interview['id'] ?>)" 
                class="bg-green-700 hover:bg-green-600 text-white px-4 py-2 rounded-md font-medium text-sm transition-colors">
                    Edit
                </button>
            </div>
        </div>
    </div>
</body>
</html>