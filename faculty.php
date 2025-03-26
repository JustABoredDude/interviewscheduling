<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

// Get search and sort parameters
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'date_desc';

// Get interviews with all needed fields
$interviews = getFacultyInterviews($conn, null, 'scheduled', $search, $sort);

function getFacultyInterviews($conn, $program = null, $status = 'scheduled', $search = '', $sort = 'date_desc') {
    $sql = "SELECT 
                i.id, 
                a.name AS applicant, 
                i.scheduled_time, 
                i.meet_type,
                i.feedback,
                p.name AS program,
                f.feedback_score,
                f.feedback_comments
            FROM interviews i
            JOIN applicants a ON i.applicant_id = a.id
            JOIN programs p ON i.program_id = p.id
            LEFT JOIN faculty f ON i.id = f.interview_id
            WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if ($status) {
        $sql .= " AND i.status = ?";
        $params[] = $status;
        $types .= "s";
    }

    if (!empty($search)) {
        $sql .= " AND (a.name LIKE ? OR p.name LIKE ?)";
        $searchTerm = "%$search%";
        array_push($params, $searchTerm, $searchTerm);
        $types .= "ss";
    }

    // Add ORDER BY clause based on sort parameter
    switch ($sort) {
        case 'date_asc':
            $sql .= " ORDER BY i.scheduled_time ASC";
            break;
        case 'name_asc':
            $sql .= " ORDER BY a.name ASC";
            break;
        case 'name_desc':
            $sql .= " ORDER BY a.name DESC";
            break;
        case 'date_desc':
        default:
            $sql .= " ORDER BY i.scheduled_time DESC";
            break;
    }

    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview Scheduling</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Modal Styles */
        .modal { 
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0; 
            top: 0; 
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.5); 
            animation: fadeIn 0.3s; 
        }
        .modal-content { 
            background-color: #fefefe; 
            margin: 15% auto; 
            padding: 20px; 
            border: 1px solid #888; 
            width: 90%; 
            max-width: 400px; 
            border-radius: 8px; 
            box-shadow: 0 4px 8px rgba(0,0,0,0.1); 
            animation: slideDown 0.3s; 
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .close { 
            color: #aaa; 
            float: right; 
            font-size: 28px; 
            font-weight: bold; 
            cursor: pointer; 
        }
        .close:hover, .close:focus { 
            color: black; 
            text-decoration: none; 
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen p-6" style="background-image: linear-gradient(rgba(0, 100, 0, 0.5), rgba(0, 100, 0, 0.5)), url('assets/plv.jpeg'); background-size: cover; background-position: center; background-repeat: no-repeat;">

<div class="half-w-6xl w-full bg-white p-6 shadow-lg rounded-lg">
    <!-- Header -->
    <header class="border-b border-gray-300 pb-4">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-bold text-green-700">Interview Scheduling</h1>
            <a href="index.php" class="text-sm text-blue-600 hover:text-blue-800">Admin View</a>
        </div>
    </header>

    <!-- Search and Sort section -->
    <div class="flex flex-col sm:flex-row justify-between items-center mt-4 mb-6 gap-4">
        <form id="searchForm" method="GET" action="faculty.php" class="flex items-center">
            <div class="relative rounded-full shadow-md">
                <input type="text" name="search" class="rounded-full border border-gray-300 py-2 pl-5 pr-10 focus:outline-none w-64" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="absolute right-0 top-0 h-full px-4 rounded-full bg-transparent hover:bg-gray-100 text-gray-600">
                    <img src="assets/search.png" alt="Search" class="w-5 h-5">
                </button>
            </div>
        </form>

        <button id="sortBtn" class="border rounded-lg px-4 py-2 bg-white hover:bg-gray-100 flex items-center gap-2">
            <img src="assets/sort.png" alt="Sort" class="w-4 h-4">
            <span>Sort</span>
        </button>
    </div>

    <!-- Sort Modal -->
    <div id="sortModal" class="modal">
        <div class="modal-content" style="max-width: 350px; padding: 0;">
            <div class="p-3 flex justify-between items-center border-b">
                <span class="font-medium">Sort By</span>
                <span class="close" onclick="closeSortModal()">&times;</span>
            </div>
            
            <form id="sortForm" method="GET" action="faculty.php" class="p-4">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                
                <div class="space-y-3">
                    <div>
                        <input type="radio" id="sort_date_asc" name="sort" value="date_asc" 
                            <?= ($_GET['sort'] ?? '') == 'date_asc' ? 'checked' : '' ?>>
                        <label for="sort_date_asc" class="ml-2">Date (Oldest First)</label>
                    </div>
                    <div>
                        <input type="radio" id="sort_date_desc" name="sort" value="date_desc" 
                            <?= ($_GET['sort'] ?? 'date_desc') == 'date_desc' ? 'checked' : '' ?>>
                        <label for="sort_date_desc" class="ml-2">Date (Newest First)</label>
                    </div>
                    <div>
                        <input type="radio" id="sort_name_asc" name="sort" value="name_asc" 
                            <?= ($_GET['sort'] ?? '') == 'name_asc' ? 'checked' : '' ?>>
                        <label for="sort_name_asc" class="ml-2">Applicant Name (A-Z)</label>
                    </div>
                    <div>
                        <input type="radio" id="sort_name_desc" name="sort" value="name_desc" 
                            <?= ($_GET['sort'] ?? '') == 'name_desc' ? 'checked' : '' ?>>
                        <label for="sort_name_desc" class="ml-2">Applicant Name (Z-A)</label>
                    </div>
                </div>
                
                <div class="flex justify-end pt-3 mt-3 border-t">
                    <button type="submit" class="bg-green-700 hover:bg-green-600 text-white px-4 py-2 rounded-md font-medium text-sm transition-colors">Apply</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Interview Details Modal -->
    <div id="interviewModal" class="modal">
        <div class="modal-content" style="max-width: 350px;">
            <div class="flex justify-between items-center border-b pb-3 mb-3">
                <h2 class="text-lg font-bold text-green-700">Interview Feedbacks</h2>
                <span class="close" onclick="closeInterviewModal()">&times;</span>
            </div>
            
            <div id="interviewModalContent">
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">Applicant: <span id="modalApplicant"></span></label>
                    <label class="block text-gray-700 font-medium mb-2">Program: <span id="modalProgram"></span></label>
                </div>

                <form id="feedbackForm">
                    <div class="mb-4">
                        <label for="score" class="block text-gray-700 font-medium mb-2">Score</label>
                        <input type="number" id="score" name="score" min="0" max="100" class="w-full p-2 border rounded-md" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="comments" class="block text-gray-700 font-medium mb-2">Comments</label>
                        <textarea id="comments" name="comments" rows="4" class="w-full p-2 border rounded-md" required></textarea>
                    </div>
                    
                    <div class="flex justify-between">
                        <button type="button" class="bg-gray-300 hover:bg-gray-400 text-black px-4 py-2 rounded-md" onclick="closeInterviewModal()">Cancel</button>
                        <button type="submit" class="bg-green-700 hover:bg-green-600 text-white px-4 py-2 rounded-md">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="w-full border-collapse bg-white shadow-md rounded-lg">
            <thead class="bg-green-600 text-white">
                <tr>
                    <th class="p-3 text-left">Applicant Name</th>
                    <th class="p-3 text-center">Date & Time</th>
                    <th class="p-3 text-center">Mode of interview</th>
                    <th class="p-3 text-center">Room/link</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($interviews)): ?>
                    <?php foreach ($interviews as $interview): ?>
                        <tr class="border-b hover:bg-gray-100 cursor-pointer interview-row" 
                            data-applicant="<?= htmlspecialchars($interview['applicant'] ?? 'N/A'); ?>" 
                            data-program="<?= htmlspecialchars($interview['program'] ?? 'N/A'); ?>"
                            data-interview-id="<?= htmlspecialchars($interview['id'] ?? ''); ?>">
                            <td class="p-3 font-medium text-black-700"><?= htmlspecialchars($interview['applicant'] ?? 'N/A'); ?></td>
                            <td class="p-3 text-center">
                                <div class="flex flex-col items-center">
                                    <span class="font-medium"><?= date('M d, Y', strtotime($interview['scheduled_time'])); ?></span>
                                    <span class="text-gray-600">
                                        <?= date('h:i A', strtotime($interview['scheduled_time'])); ?> - 
                                        <?= date('h:i A', strtotime($interview['scheduled_time'] . ' +30 minutes')); ?>
                                    </span>
                                </div>
                            </td>
                            <td class="p-3 text-center">
                                <?php 
                                $meet_type = $interview['meet_type'] ?? 'N/A';
                                echo htmlspecialchars($meet_type === 'F2F' ? 'Face-to-Face' : ($meet_type === 'Online' ? 'Online' : 'N/A')); 
                                ?>
                            </td>
                            <td class="p-3 text-center">
                                <?php if (!empty($interview['feedback'])): ?>
                                    <?php if (($interview['meet_type'] ?? '') === 'F2F'): ?>
                                        <?= htmlspecialchars($interview['feedback']); ?>
                                    <?php else: ?>
                                        <?php 
                                        $feedback = $interview['feedback'];
                                        if (filter_var($feedback, FILTER_VALIDATE_URL)): ?>
                                            <?= htmlspecialchars($feedback); ?>
                                        <?php else: ?>
                                            <span class="text-gray-500">Invalid link</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-gray-500">Not provided</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center text-gray-500 p-3">No interviews found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // JavaScript for modal functionality
    const sortModal = document.getElementById("sortModal");
    const sortBtn = document.getElementById("sortBtn");
    const interviewModal = document.getElementById("interviewModal");
    const modalApplicant = document.getElementById("modalApplicant");
    const modalProgram = document.getElementById("modalProgram");
    const feedbackForm = document.getElementById("feedbackForm");

    // Open sort modal
    sortBtn.onclick = () => {
        sortModal.style.display = "block";
        document.body.style.overflow = "hidden";
    };

    // Close sort modal
    function closeSortModal() {
        sortModal.style.display = "none";
        document.body.style.overflow = "auto";
    }

    // Open interview modal
    document.querySelectorAll('.interview-row').forEach(row => {
    row.addEventListener('click', function() {
        const applicant = this.getAttribute('data-applicant');
        const program = this.getAttribute('data-program');
        const interviewId = this.getAttribute('data-interview-id');

        console.log("Interview ID: " + interviewId); // Debugging

        if (!interviewId) {
            console.error("Interview ID is missing!");
            return;
        }
        
        modalApplicant.textContent = applicant;
        modalProgram.textContent = program;
        
        // Store the interview ID for form submission
        feedbackForm.dataset.interviewId = interviewId;
        
        interviewModal.style.display = "block";
        document.body.style.overflow = "hidden";
    });
});

    // Close interview modal
    function closeInterviewModal() {
        interviewModal.style.display = "none";
        document.body.style.overflow = "auto";
        feedbackForm.reset();
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
        if (event.target == sortModal) {
            closeSortModal();
        }
        if (event.target == interviewModal) {
            closeInterviewModal();
        }
    };

    document.addEventListener('keydown', (event) => { 
        if (event.key === "Escape") {
            closeSortModal();
            closeInterviewModal();
        }
    });

    // Form submission handlers
    document.getElementById('searchForm').addEventListener('keypress', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            this.submit();
        }
    });

    // Optional: Handle feedback form submission 
    feedbackForm.addEventListener('submit', function(event) {
    event.preventDefault();
    
    // const interviewId = this.dataset.interviewId;
    const interviewId = this.dataset.interviewId;
    const score = document.getElementById('score').value;
    const comments = document.getElementById('comments').value;
    
    const formData = new FormData();
    formData.append('interview_id', interviewId);
    formData.append('score', score);
    formData.append('comments', comments);

    fetch('submit_faculty_feedback.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Feedback submitted successfully');
            closeInterviewModal();
            // Optional: Reload the page or update the row dynamically
            location.reload();
        } else {
            alert('Error submitting feedbacsjssjsjsjsjsjsjsjjsk: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while submitting feedback');
    });
});
</script>
</body>
</html>