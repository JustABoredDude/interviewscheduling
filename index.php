<?php
session_start();
require_once 'db.php';
require_once 'functions.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Get filters and search
$selected_program = $_GET['program'] ?? null;
$status_filter = $_GET['status'] ?? 'scheduled';
$sort = $_GET['sort'] ?? 'date_desc';
$search = $_GET['search'] ?? '';
$is_modal = isset($_GET['modal']) && $_GET['modal'] == 'true';
// Fetch interviews
$interviews = getInterviews($conn, $selected_program, $status_filter, $search, $sort);

// Handle form submission for adding a new interview
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_interview'])) {
    $interviewer_name = $_POST['interviewer_name'];
    $interviewer_email = $_POST['interviewer_email'];
    $program_id = intval($_POST['program']);
    $applicant_name = $_POST['applicant_name'];
    $applicant_email = $_POST['applicant_email'];
    $scheduled_time = $_POST['scheduled_time'];
    $meet_type = $_POST['meet_type'];
    $feedback = $_POST['feedback'] ?? '';

    // Validate scheduled time
    if (DateTime::createFromFormat('Y-m-d\TH:i', $scheduled_time) === false) {
        $error = "Invalid date and time format.";
    } else {
        // Get or create interviewer
        $interviewer_id = getOrCreateRecord($conn, 'interviewers', $interviewer_name, $interviewer_email, $program_id);
        // Get or create applicant
        $applicant_id = getOrCreateRecord($conn, 'applicants', $applicant_name, $applicant_email);

        // Insert interview
        if (insertInterview($conn, $applicant_id, $interviewer_id, $program_id, $scheduled_time, $meet_type, $feedback)) {
            header("Location: index.php");
            exit();
        } else {
            $error = "Error adding interview: " . mysqli_error($conn);
        }
    }
}

/**
 * Get or create a record in the database (interviewer or applicant).
 */
function getOrCreateRecord($conn, $table, $name, $email, $program_id = null) {
    $stmt = $conn->prepare("SELECT id, name FROM $table WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['name'] !== $name) {
            $stmt = $conn->prepare("UPDATE $table SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $name, $row['id']);
            $stmt->execute();
        }
        return $row['id'];
    } else {
        $query = ($table === 'interviewers') 
            ? "INSERT INTO $table (name, email, program_id) VALUES (?, ?, ?)"
            : "INSERT INTO $table (name, email) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        if ($table === 'interviewers') {
            $stmt->bind_param("ssi", $name, $email, $program_id);
        } else {
            $stmt->bind_param("ss", $name, $email);
        }
        $stmt->execute();
        return $conn->insert_id;
    }
}


/**
 * Insert a new interview into the database.
 */
function insertInterview($conn, $applicant_id, $interviewer_id, $program_id, $scheduled_time, $meet_type, $feedback) {
    $stmt = $conn->prepare("INSERT INTO interviews (applicant_id, interviewer_id, program_id, scheduled_time, status, meet_type, feedback) VALUES (?, ?, ?, ?, 'scheduled', ?, ?)");
    $stmt->bind_param("iiisss", $applicant_id, $interviewer_id, $program_id, $scheduled_time, $meet_type, $feedback);
    
    if ($stmt->execute()) {
        // Fetch applicant and interviewer details for email
        $interview_details = [
            'applicant_name' => getApplicantName($conn, $applicant_id),
            'date' => date('M d, Y', strtotime($scheduled_time)),
            'time' => date('h:i A', strtotime($scheduled_time)),
            'interviewer' => getInterviewerName($conn, $interviewer_id),
            'meet_type' => $meet_type
        ];
        
        // Get emails
        $applicant_email = getApplicantEmail($conn, $applicant_id);
        $interviewer_email = getInterviewerEmail($conn, $interviewer_id);
        
        // Send email
        sendInterviewEmail($applicant_email, $interviewer_email, $interview_details);
        
        return true;
    }
    return false;
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
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); animation: fadeIn 0.3s; }
        .modal-content { background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 90%; max-width: 500px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); animation: slideDown 0.3s; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover, .close:focus { color: black; text-decoration: none; }

        /* Form Styles */
        .form-group { margin-bottom: 15px; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background-color: #f9f9f9; }
        .btn-container { display: flex; justify-content: space-between; margin-top: 20px; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen p-6" style="background-image: linear-gradient(rgba(0, 100, 0, 0.5), rgba(0, 100, 0, 0.5)), url('assets/plv.jpeg'); background-size: cover; background-position: center; background-repeat: no-repeat;">

<div class="half-w-6xl w-full bg-white p-6 shadow-lg rounded-lg">
    <!-- Header -->
    <header class="border-b border-gray-300 pb-4">
         <h1 class="text-2xl font-bold text-green-700">Interview Scheduling</h1>
    </header>


    <!-- Updated Filters section with right-aligned buttons -->
    <div class="flex flex-col sm:flex-row justify-between items-center mt-4 mb-6 gap-4">

    <div class="flex items-center gap-2">
        <button id="openModalBtn" class="px-4 py-2 bg-green-100 text-green-700 border border-green-600 rounded-full hover:bg-green-200 flex items-center justify-center gap-2">
            <span>Add Schedule</span><span>+</span>
        </button>
        <form id="searchForm" method="GET" action="index.php" class="flex items-center">
            <div class="relative rounded-full shadow-md">
                <input type="text" name="search" class="rounded-full border border-gray-300 py-2 pl-5 pr-10 focus:outline-none w-64" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="absolute right-0 top-0 h-full px-4 rounded-full bg-transparent hover:bg-gray-100 text-gray-600">
                    <img src="assets/search.png" alt="Search" class="w-5 h-5">
                </button>
            </div>
        </form>
</div>

<div class="flex flex-col sm:flex-row justify-between items-center mt-4 mb-6 gap-4">
        <button id="sortBtn" class="border rounded-lg px-4 py-2 bg-white hover:bg-gray-100 flex items-center gap-2">
            <img src="assets/sort.png" alt="Sort" class="w-4 h-4">
            <span>Sort</span>
        </button>
        <button id="filterBtn" class="border rounded-lg px-4 py-2 bg-white hover:bg-gray-100 flex items-center gap-2">
            <img src="assets/filter.png" alt="Filter" class="w-4 h-4">
            <span>Filter</span>
        </button>
    </div>
</div>

<!-- Filter Modal -->
<div id="filterModal" class="modal">
    <div class="modal-content" style="max-width: 350px; padding: 0;">
        <div class="p-3 flex justify-between items-center border-b">
            <span class="font-medium">Filter</span>
            <span class="close" onclick="closeFilterModal()">&times;</span>
        </div>
        
        <form id="filterForm" method="GET" action="index.php" class="p-4">
            <!-- Date Range -->
            <div class="mb-4">
                <h3 class="font-medium mb-2">Date Range</h3>
                <div class="flex gap-2 items-center">
                    <div>
                        <label for="date_from" class="block text-sm text-gray-600">From:</label>
                        <input type="date" id="date_from" name="date_from" class="border rounded p-2 w-full" 
                            value="<?= $_GET['date_from'] ?? '' ?>">
                    </div>
                    <div>
                        <label for="date_to" class="block text-sm text-gray-600">To:</label>
                        <input type="date" id="date_to" name="date_to" class="border rounded p-2 w-full" 
                            value="<?= $_GET['date_to'] ?? '' ?>">
                    </div>
                </div>
            </div>
            
            <!-- Status -->
            <div class="mb-4">
                <h3 class="font-medium mb-2">Status</h3>
                <div class="space-y-2">
                    <div>
                        <input type="radio" id="status_scheduled" name="status" value="scheduled" 
                            <?= ($status_filter == 'scheduled') ? 'checked' : '' ?>>
                        <label for="status_scheduled" class="ml-2">Scheduled</label>
                    </div>
                    <div>
                        <input type="radio" id="status_cancelled" name="status" value="cancelled" 
                            <?= ($status_filter == 'cancelled') ? 'checked' : '' ?>>
                        <label for="status_cancelled" class="ml-2">Cancelled</label>
                    </div>
                </div>
            </div>
            
            <!-- Program -->
          <!-- Program -->
<div class="mb-4">
    <h3 class="font-medium mb-2">Program</h3>
    <div class="space-y-2">
        <div>
            <input type="checkbox" id="program_cot" name="program" value="COT" 
                <?= ($selected_program == 'COT') ? 'checked' : '' ?>>
            <label for="program_cot" class="ml-2 text-green-700 font-medium">College of Technology</label>
        </div>
        <div class="ml-6 space-y-1">
            <div>
                <input type="checkbox" id="program_bscs" name="program" value="BSCS" 
                    <?= ($selected_program == 'BSCS') ? 'checked' : '' ?>>
                <label for="program_bscs" class="ml-2">BSCS</label>
            </div>
            <div>
                <input type="checkbox" id="program_bsce" name="program" value="BSCE" 
                    <?= ($selected_program == 'BSCE') ? 'checked' : '' ?>>
                <label for="program_bsce" class="ml-2">BSCE</label>
            </div>
            <div>
                <input type="checkbox" id="program_bsit" name="program" value="BSIT" 
                    <?= ($selected_program == 'BSIT') ? 'checked' : '' ?>>
                <label for="program_bsit" class="ml-2">BSIT</label>
            </div>
        </div>
        
        <div>
            <input type="checkbox" id="program_coe" name="program" value="COE" 
                <?= ($selected_program == 'COE') ? 'checked' : '' ?>>
            <label for="program_coe" class="ml-2 text-green-700 font-medium">College of Education</label>
        </div>
        <div class="ml-6 space-y-1">
            <div>
                <input type="checkbox" id="program_elem" name="program" value="BSED-ELEM" 
                    <?= ($selected_program == 'BSED-ELEM') ? 'checked' : '' ?>>
                <label for="program_elem" class="ml-2">BSED - ELEM</label>
            </div>
            <div>
                <input type="checkbox" id="program_sec" name="program" value="BSED-SEC" 
                    <?= ($selected_program == 'BSED-SEC') ? 'checked' : '' ?>>
                <label for="program_sec" class="ml-2">BSED - SEC</label>
            </div>
            <div>
                <input type="checkbox" id="program_sped" name="program" value="BSED-SPED" 
                    <?= ($selected_program == 'BSED-SPED') ? 'checked' : '' ?>>
                <label for="program_sped" class="ml-2">BSED - SPED</label>
            </div>
        </div>
        
        <div>
            <input type="checkbox" id="program_cba" name="program" value="CBA" 
                <?= ($selected_program == 'CBA') ? 'checked' : '' ?>>
            <label for="program_cba" class="ml-2 text-green-700 font-medium">College of Business Accountancy</label>
        </div>
        <div class="ml-6 space-y-1">
            <div>
                <input type="checkbox" id="program_bsa" name="program" value="BSA" 
                    <?= ($selected_program == 'BSA') ? 'checked' : '' ?>>
                <label for="program_bsa" class="ml-2">BSA</label>
            </div>
            <div>
            <input type="checkbox" id="program_bshrdm" name="program" value="BSHRDM" 
                    <?= ($selected_program == 'BSHRDM') ? 'checked' : '' ?>>
                <label for="program_bshrdm" class="ml-2">BSHRDM</label>
            </div>
            <div>
                <input type="checkbox" id="program_bsfm" name="program" value="BSFM" 
                    <?= ($selected_program == 'BSFM') ? 'checked' : '' ?>>
                <label for="program_bsfm" class="ml-2">BSFM</label>
            </div>
        </div>
    </div>
</div>
            
            <!-- Button controls -->
            <div class="flex justify-between pt-3 border-t">
            <button type="button" onclick="resetFilterForm(event)" style="background-color: #bdbdbd; color: black; padding: 8px 16px; border-radius: 4px; border: none; font-weight: 500;  transition: background-color 0.3s;" 
    onmouseover="this.style.backgroundColor='#a5a5a5'" 
    onmouseout="this.style.backgroundColor='#bdbdbd'">
    Reset
</button>
    <button type="submit" class="bg-green-700 hover:bg-green-600 text-white px-4 py-2 rounded-md font-medium text-sm transition-colors">Confirm</button>
</div>
        </form>
    </div>
</div>

<!-- Sort Modal -->
<div id="sortModal" class="modal">
    <div class="modal-content" style="max-width: 350px; padding: 0;">
        <div class="p-3 flex justify-between items-center border-b">
            <span class="font-medium">Sort By</span>
            <span class="close" onclick="closeSortModal()">&times;</span>
        </div>
        
        <form id="sortForm" method="GET" action="index.php" class="p-4">
            <!-- Hidden fields to preserve other filters -->
            <input type="hidden" name="status" value="<?= $status_filter ?>">
            <input type="hidden" name="program" value="<?= $selected_program ?>">
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
            
            <!-- Button controls -->
            <div class="flex justify-end pt-3 mt-3 border-t">
                <button type="submit" class="bg-green-700 hover:bg-green-600 text-white px-4 py-2 rounded-md font-medium text-sm transition-colors">Apply</button>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="overflow-x-auto">
    <table class="w-full border-collapse bg-white shadow-md rounded-lg">
        <thead class="<?= ($status_filter == 'trash') ? 'bg-red-500 text-white' : 'bg-green-600 text-white'; ?>">
            <tr>
                <th class="p-3 text-left" width="15%">Applicant Name</th>
                <th class="p-3 text-center" width="30%"><?= ($status_filter == 'trash') ? 'Date Cancelled' : 'Date & Time'; ?></th>
                <th class="p-3 text-center" width="20%">Faculty/Interviewer</th>
                <th class="p-3 text-center" width="15%">Status</th>
                <th class="p-3 text-center" width="20%">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($interviews)): ?>
                <?php foreach ($interviews as $interview): ?>
                    <tr class="border-b hover:bg-gray-100">
                        <td class="p-3 font-medium text-black-700"><?= htmlspecialchars($interview['applicant'] ?? 'N/A'); ?></td>
                        <td class="p-3 text-center">
                            <div class="flex flex-col items-center">
                                <?php if ($status_filter == 'trash'): ?>
                                    <span class="font-medium"><?= date('M d, Y', strtotime($interview['cancelled_date'] ?? 'now')); ?></span>
                                <?php else: ?>
                                    <span class="font-medium"><?= date('M d, Y', strtotime($interview['scheduled_time'])); ?></span>
                                    <span class="text-gray-600">
                                        <?= date('h:i A', strtotime($interview['scheduled_time'])); ?> - 
                                        <?= date('h:i A', strtotime($interview['scheduled_time'] . ' +30 minutes')); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="p-3 font-medium text-center">
                            <span class="px-3 py-1 bg-green-50 text-black-800 rounded-full"><?= htmlspecialchars($interview['interviewer'] ?? 'N/A'); ?></span>
                        </td>
                        <td class="p-3 text-center">
                            <span class="px-3 py-1 rounded-full font-medium 
                                <?= ($status_filter == 'trash') ? 'bg-red-100 text-red-700' : 
                                    (($interview['status'] === 'cancelled') ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'); ?>">
                                <?= ucfirst($interview['status'] ?? 'Unknown'); ?>
                            </span>
                        </td>
                        <td class="p-3">
                            <div class="flex justify-center space-x-2">
                                <?php if ($status_filter === 'trash'): ?>
                                    <button onclick="deleteInterview(<?= $interview['id']; ?>)" class="p-2 bg-red-400 text-white rounded-lg hover:bg-red-500 flex items-center justify-center">
                                    <img src="assets/delete.png" alt="Delete" title="Delete" class="w-4 h-4">
                                    </button>
                                    <button onclick="restoreInterview(<?= $interview['id']; ?>)" class="p-2 bg-green-400 text-white rounded-lg hover:bg-green-500 flex items-center justify-center">
                                        <img src="assets/restore.png" alt="Restore" title="Restore" class="w-4 h-4">
                                    </button>
                                <?php elseif ($status_filter === 'cancelled'): ?>
                                    <button onclick="moveToTrash(<?= $interview['id']; ?>)" class="p-2 bg-red-400 text-white rounded-lg hover:bg-red-500 flex items-center justify-center">
                                        <img src="assets/delete.png" alt="Delete" title="Delete" class="w-4 h-4">
                                    </button>
                                <?php else: ?>
                                    <button onclick="cancelInterview(<?= $interview['id']; ?>)" class="p-2 bg-red-400 text-white rounded-lg hover:bg-red-500 flex items-center justify-center">
                                        <img src="assets/cancel.png" alt="Cancel" title="Cancel" class="w-4 h-4">
                                    </button>
                                <?php endif; ?>
                                <?php if ($status_filter !== 'trash'): ?>
                                    <a href="edit_interview.php?id=<?= $interview['id']; ?>" class="p-2 bg-blue-400 text-white rounded-lg hover:bg-blue-500 flex items-center justify-center">
                                        <img src="assets/edit.png" alt="Edit" title="Edit" class="w-4 h-4">
                                    </a>
                                    <a href="view_interview.php?id=<?= $interview['id']; ?>" class="p-2 bg-green-400 text-white rounded-lg hover:bg-green-500 flex items-center justify-center">
                                        <img src="assets/view.png" alt="View" title="View" class="w-4 h-4">
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center text-gray-500 p-3">No interviews found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="flex flex-col sm:flex-row justify-between items-center mt-4 mb-6 gap-4">
    <a href="trash.php" class="border rounded-lg px-4 py-2 bg-red-100 text-red-700 border border-red-600 rounded-full hover:bg-red-200 flex items-center justify-center gap-2">   
       <img src="assets/trash.png" alt="Trash" class="w-4 h-4">
       <span>Trash</span>
       </a>
</div>

<!-- Add Schedule Modal -->
<div id="addScheduleModal" class="modal">
    <div class="modal-content" style="max-width: 450px; padding: 15px;">
        <span class="close">&times;</span>
        <div class="bg-green-100 text-center py-1 rounded-t-lg mb-3">
            <h2 class="text-lg font-semibold text-black">New Interview</h2>
        </div>
        <?php if(isset($error)): ?>
            <div class="bg-red-100 text-red-700 p-2 my-2 rounded"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form action="index.php" method="POST" class="space-y-3">
            <input type="hidden" name="add_interview" value="1">
            <div class="form-group" style="margin-bottom: 10px;">
                <label for="applicant_name" class="block text-sm font-medium text-gray-700">Applicant Name</label>
                <input type="text" id="applicant_name" name="applicant_name" required class="form-control" style="padding: 6px;">
            </div>
            <div class="form-group" style="margin-bottom: 10px;">
                <label for="applicant_email" class="block text-sm font-medium text-gray-700">Applicant Email</label>
                <input type="email" id="applicant_email" name="applicant_email" required class="form-control" style="padding: 6px;">
            </div>
            <div class="form-group" style="margin-bottom: 10px;">
                <label for="scheduled_time" class="block text-sm font-medium text-gray-700">Date & Time</label>
                <input type="datetime-local" id="scheduled_time" name="scheduled_time" required class="form-control" style="padding: 6px;">
            </div>
            <div class="form-group" style="margin-bottom: 10px;">
                
            <div class="form-group" style="margin-bottom: 10px;">
    <label for="program" class="block text-sm font-medium text-gray-700">Program</label>
    <select id="program-select" name="program" required class="form-control" style="padding: 6px;">
        <option value="" disabled selected>Select Program</option>
        <?php
        $programs = getPrograms($conn);
        $colleges = [];
        foreach ($programs as $program) {
            $colleges[$program['college']][] = $program;
        }
        foreach ($colleges as $college => $courses) {
            echo "<optgroup label='$college'>";
            foreach ($courses as $course) {
                echo "<option value='{$course['id']}'>{$course['name']}</option>";
            }
            echo "</optgroup>";
        }
        ?>
    </select>
</div>
            </div>
            <div class="form-group" style="margin-bottom: 10px;">
                <label for="meet_type" class="block text-sm font-medium text-gray-700">Meet Type</label>
                <select id="meet_type" name="meet_type" required class="form-control" style="padding: 6px;">
                <option value="" disabled selected>Select Meet Type</option>    
                <option value="F2F">F2F (Face-to-Face)</option>
                <option value="Online">Online</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 10px;">
                <label for="interviewer_name" class="block text-sm font-medium text-gray-700">Interviewer Name</label>
                <input type="text" id="interviewer_name" name="interviewer_name" required class="form-control" style="padding: 6px;">
            </div>
            <div class="form-group" style="margin-bottom: 10px;">
                <label for="interviewer_email" class="block text-sm font-medium text-gray-700">Interviewer Email</label>
                <input type="email" id="interviewer_email" name="interviewer_email" required class="form-control" style="padding: 6px;">
            </div>
     <!-- Feedback Field -->
<div class="form-group feedback-group" style="margin-bottom: 10px;">
    <label for="feedback" class="block text-sm font-medium text-gray-700">Feedback (Optional)</label>
    <textarea id="feedback" name="feedback" class="form-control" style="padding: 6px; min-height: 80px;" placeholder="Add your feedback here..."></textarea>
</div>
            <div class="btn-container" style="margin-top: 12px;">
                <button type="button" class="close-modal px-3 py-1 bg-red-100 text-red-700 rounded-md hover:bg-red-200 font-semibold">Cancel</button>
                <button type="submit" class="bg-green-700 hover:bg-green-600 text-white px-4 py-2 rounded-md font-medium text-sm transition-colors">Confirm</button>
            </div>
        </form>
    </div>
</div>


<script>
    
    // JavaScript for filters, modal, and cancel functionality
function updateFilter(type, value) {
    const url = new URL(window.location.href);
    url.searchParams.set(type, value);
    window.location.href = url.toString();
}

function cancelInterview(interviewId) {
    if (confirm("Are you sure you want to cancel this interview?")) {
        window.location.href = `cancel_interview.php?id=${interviewId}`;
    }
}

function moveToTrash(interviewId) {
    if (confirm("Are you sure you want to move this interview to trash? You can still restore it if you want to.")) {
        fetch(`trash_interview.php?id=${interviewId}`, {
            method: 'GET'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Interview moved to trash successfully!');
                window.location.reload();
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Request failed.');
        });
    }
}

function restoreInterview(interviewId) {
    if (confirm("Are you sure you want to restore this interview? It will be moved back to scheduled status.")) {
        fetch(`restore_interview.php?id=${interviewId}`, {
            method: 'GET'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Interview restored successfully!');
                window.location.reload();
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Request failed.');
        });
    }
}

function deleteInterview(interviewId) {
    if (confirm("Are you sure you want to permanently delete this interview?")) {
        fetch(`delete_interview.php?id=${interviewId}`, {
            method: 'GET'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Interview deleted successfully!');
                window.location.reload(); // Reload the page to update the table
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Request failed.');
        });
    }
}

// Filter and Sort modal functionality
const filterModal = document.getElementById("filterModal");
const sortModal = document.getElementById("sortModal");
const filterBtn = document.getElementById("filterBtn");
const sortBtn = document.getElementById("sortBtn");

// Open filter modal
filterBtn.onclick = () => {
    openModal(filterModal);
    if (sortModal.style.display === "block") {
        closeModal(sortModal);
    }
};

// Open sort modal
sortBtn.onclick = () => {
    openModal(sortModal);
    if (filterModal.style.display === "block") {
        closeModal(filterModal);
    }
};

// Close filter modal
function closeFilterModal() {
    closeModal(filterModal);
}

// Close sort modal
function closeSortModal() {
    closeModal(sortModal);
}

// Reset filters
function resetFilterForm(event) {
    // Prevent the default form submission
    event.preventDefault();
    
    // Reset date range inputs
    document.getElementById('date_from').value = '';
    document.getElementById('date_to').value = '';
    
    // Reset status radio buttons
    const statusRadios = document.querySelectorAll('input[name="status"]');
    statusRadios.forEach(radio => {
        radio.checked = false;
    });
    
    // Set default status to 'scheduled'
    document.getElementById('status_scheduled').checked = true;
    
    // Reset all program and college checkboxes
    const programCheckboxes = document.querySelectorAll('input[name="program"]');
    programCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // Reset college checkboxes (COT, COE, CBA)
    const collegeCheckboxes = [
        document.querySelector('input[value="COT"]'),
        document.querySelector('input[value="COE"]'),
        document.querySelector('input[value="CBA"]')
    ];
    collegeCheckboxes.forEach(checkbox => {
        if (checkbox) checkbox.checked = false;
    });
    
    // Submit the form to reset all filters
    const filterForm = document.getElementById('filterForm');
    
    // Optional: Create a hidden input to ensure 'scheduled' status is applied
    const statusInput = document.createElement('input');
    statusInput.type = 'hidden';
    statusInput.name = 'status';
    statusInput.value = 'scheduled';
    
    filterForm.appendChild(statusInput);
    filterForm.submit();
}

// Update your existing college/program checkbox logic to handle reset:
document.addEventListener('DOMContentLoaded', function() {
    const collegeCheckboxes = {
        'COT': document.querySelector('input[value="COT"]'),
        'COE': document.querySelector('input[value="COE"]'),
        'CBA': document.querySelector('input[value="CBA"]')
    };
    
    const programCheckboxes = {
        'COT': [
            document.querySelector('input[value="BSCS"]'),
            document.querySelector('input[value="BSCE"]'),
            document.querySelector('input[value="BSIT"]')
        ],
        'COE': [
            document.querySelector('input[value="BSED-ELEM"]'),
            document.querySelector('input[value="BSED-SEC"]'),
            document.querySelector('input[value="BSED-SPED"]')
        ],
        'CBA': [
            document.querySelector('input[value="BSA"]'),
            document.querySelector('input[value="BSHRDM"]'),
            document.querySelector('input[value="BSFM"]')
        ]
    };
    
    // Add event listeners to college checkboxes
    Object.keys(collegeCheckboxes).forEach(college => {
        collegeCheckboxes[college].addEventListener('change', function() {
            const isChecked = this.checked;
            programCheckboxes[college].forEach(programCheckbox => {
                programCheckbox.checked = isChecked;
            });
        });
    });
    
    // Add event listeners to program checkboxes
    Object.keys(programCheckboxes).forEach(college => {
        programCheckboxes[college].forEach(programCheckbox => {
            programCheckbox.addEventListener('change', function() {
                const allChecked = programCheckboxes[college].every(checkbox => checkbox.checked);
                collegeCheckboxes[college].checked = allChecked;
            });
        });
    });
});

// Modal functionality
const modal = document.getElementById("addScheduleModal");
const openBtn = document.getElementById("openModalBtn");
const closeBtn = document.querySelector(".close");
const closeBtns = document.querySelectorAll(".close-modal");

// Improved modal functions
function openModal(modalElement) {
    modalElement.style.display = "block";
    document.body.style.overflow = "hidden";
}

function closeModal(modalElement) {
    modalElement.style.display = "none";
    document.body.style.overflow = "auto";
}

// Event handlers for add schedule modal
// Event handlers for add schedule modal
document.getElementById('openModalBtn').onclick = () => openModal(document.getElementById('addScheduleModal'));
document.querySelector('#addScheduleModal .close').onclick = () => closeModal(document.getElementById('addScheduleModal'));
document.querySelectorAll('.close-modal').forEach(btn => btn.onclick = () => closeModal(document.getElementById('addScheduleModal')));

document.addEventListener('keydown', (event) => { 
    if (event.key === "Escape") {
        if (modal.style.display === "block") closeModal(modal);
        if (document.getElementById("editInterviewModal").style.display === "block") closeEditModal();
        if (document.getElementById("viewInterviewModal").style.display === "block") closeViewModal();
        if (filterModal.style.display === "block") closeFilterModal();
        if (sortModal.style.display === "block") closeSortModal();
    }
});

document.addEventListener('DOMContentLoaded', function() {
  const collegeCheckboxes = {
    'COT': document.querySelector('input[value="COT"]'),
    'COE': document.querySelector('input[value="COE"]'),
    'CBA': document.querySelector('input[value="CBA"]')
  };
  
  // Get all program checkboxes grouped by college
  const programCheckboxes = {
    'COT': [
      document.querySelector('input[value="BSCS"]'),
      document.querySelector('input[value="BSCE"]'),
      document.querySelector('input[value="BSIT"]')
    ],
    'COE': [
      document.querySelector('input[value="BSED-ELEM"]'),
      document.querySelector('input[value="BSED-SEC"]'),
      document.querySelector('input[value="BSED-SPED"]')
    ],
    'CBA': [
      document.querySelector('input[value="BSA"]'),
      document.querySelector('input[value="BSHRDM"]'),
      document.querySelector('input[value="BSFM"]')
    ]
  };
  
  // Add event listeners to college checkboxes
  Object.keys(collegeCheckboxes).forEach(college => {
    collegeCheckboxes[college].addEventListener('change', function() {
      // When college is checked/unchecked, update all its programs
      const isChecked = this.checked;
      programCheckboxes[college].forEach(programCheckbox => {
        programCheckbox.checked = isChecked;
      });
    });
  });
  
  // Add event listeners to program checkboxes
  Object.keys(programCheckboxes).forEach(college => {
    programCheckboxes[college].forEach(programCheckbox => {
      programCheckbox.addEventListener('change', function() {
        // Check if all programs in this college are checked
        const allChecked = programCheckboxes[college].every(checkbox => checkbox.checked);
        // If any program is unchecked, uncheck the college
        collegeCheckboxes[college].checked = allChecked;
      });
    });
  });
});

// Create modals for edit and view
document.body.insertAdjacentHTML('beforeend', `
<!-- Edit Interview Modal -->
<div id="editInterviewModal" class="modal">
    <div class="modal-content" style="max-width: 450px; padding: 15px;">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <div class="bg-green-100 text-center py-1 rounded-t-lg mb-3">
            <h2 class="text-lg font-semibold text-black">Edit Interview</h2>
        </div>
        <div id="editModalContent">
            <!-- Content will be loaded here -->
            <div class="text-center py-4">Loading...</div>
        </div>
    </div>
</div>

<!-- View Interview Modal -->
<div id="viewInterviewModal" class="modal">
    <div class="modal-content" style="max-width: 450px; padding: 15px;">
        <span class="close" onclick="closeViewModal()">&times;</span>
        <div class="bg-green-100 text-center py-1 rounded-t-lg mb-3">
            <h2 class="text-lg font-semibold text-black">View Interview</h2>
        </div>
        <div id="viewModalContent">
            <!-- Content will be loaded here -->
            <div class="text-center py-4">Loading...</div>
        </div>
    </div>
</div>
`);

// Function to open the edit modal
function openEditModal(interviewId) {
    const modal = document.getElementById("editInterviewModal");
    const modalContent = document.getElementById("editModalContent");
    
    // Show the modal and disable background
    openModal(modal);
    
    // Fetch the edit form content
    fetch(`edit_interview.php?id=${interviewId}&modal=true`)
        .then(response => response.text())
        .then(data => {
            // Insert the form into the modal
            modalContent.innerHTML = data;
            
            // Check if the feedback field exists, if not, add it
            const form = modalContent.querySelector('form');
            if (form) {
                const existingFeedback = form.querySelector('#feedback');
                if (!existingFeedback) {
                    // Find a good place to insert the feedback field (before the button container)
                    const btnContainer = form.querySelector('.btn-container');
                    if (btnContainer) {
                        const feedbackDiv = document.createElement('div');
                        feedbackDiv.className = 'form-group';
                        feedbackDiv.style.marginBottom = '10px';
                        feedbackDiv.innerHTML = `
                            <label for="feedback" class="block text-sm font-medium text-gray-700">Feedback (Optional)</label>
                            <textarea id="feedback" name="feedback" class="form-control" style="padding: 6px; min-height: 80px;" placeholder="Add your feedback here...">${modalContent.querySelector('[name="feedback"]')?.value || ''}</textarea>
                        `;
                        btnContainer.parentNode.insertBefore(feedbackDiv, btnContainer);
                    }
                }
                
                // Add event listener to the form
                form.addEventListener('submit', function(event) {
                    event.preventDefault();
                    
                    const formData = new FormData(this);
                    
                    fetch("update_interview.php", {
                        method: "POST",
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        if (data.trim() === "success") {
                            alert("Interview updated successfully!");
                            closeEditModal();
                            window.location.reload(); // Reload the page to show updated data
                        } else {
                            alert("Error: " + data);
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                        alert("An error occurred while updating the interview.");
                    });
                });
            }
        })
        .catch(error => {
            console.error("Error:", error);
            modalContent.innerHTML = `<div class="text-red-500 py-4">Error loading content</div>`;
        });
}

// Function to close the edit modal
function closeEditModal() {
    const modal = document.getElementById("editInterviewModal");
    closeModal(modal);
}

// Function to open the view modal
// In your index.php script, update the openViewModal function:
function openViewModal(interviewId) {
    const modal = document.getElementById("viewInterviewModal");
    const modalContent = document.getElementById("viewModalContent");
    
    // Show the modal and disable background
    openModal(modal);
    
    // Fetch the view content
    fetch(`view_interview.php?id=${interviewId}&modal=true`)
        .then(response => response.text())
        .then(data => {
            // Insert the content into the modal
            modalContent.innerHTML = data;
            
            // Add click handler for any edit buttons in the view modal
            const editBtn = modalContent.querySelector('button[onclick^="openEditModal"]');
            if (editBtn) {
                editBtn.addEventListener('click', function() {
                    closeViewModal();
                    openEditModal(interviewId);
                });
            }
        })
        .catch(error => {
            console.error("Error:", error);
            modalContent.innerHTML = `<div class="text-red-500 py-4">Error loading content</div>`;
        });
}
// Function to close the view modal
function closeViewModal() {
    const modal = document.getElementById("viewInterviewModal");
    closeModal(modal);
}

// Update the click handlers in the table
document.addEventListener('DOMContentLoaded', function() {
    // Add pointer-events-none class for background content when modal is active
    const style = document.createElement('style');
    style.textContent = `
        .pointer-events-none {
            pointer-events: none !important;
        }
    `;
    document.head.appendChild(style);
    
    // Get all edit buttons and update their click handlers
    const editButtons = document.querySelectorAll('a[href^="edit_interview.php"]');
    editButtons.forEach(button => {
        const href = button.getAttribute('href');
        const id = href.split('=')[1];
        
        button.setAttribute('href', 'javascript:void(0)');
        button.setAttribute('onclick', `openEditModal(${id})`);
    });
    
    // Get all view buttons and update their click handlers
    const viewButtons = document.querySelectorAll('a[href^="view_interview.php"]');
    viewButtons.forEach(button => {
        const href = button.getAttribute('href');
        const id = href.split('=')[1];
        
        button.setAttribute('href', 'javascript:void(0)');
        button.setAttribute('onclick', `openViewModal(${id})`);
    });
    
    // Fix for close buttons in filter and sort modals
    const filterCloseBtn = document.querySelector("#filterModal .close");
    if (filterCloseBtn) {
        filterCloseBtn.onclick = closeFilterModal;
    }
    
    const sortCloseBtn = document.querySelector("#sortModal .close");
    if (sortCloseBtn) {
        sortCloseBtn.onclick = closeSortModal;
    }
});

document.getElementById('searchForm').addEventListener('keypress', function (event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        this.submit();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const meetTypeSelect = document.getElementById('meet_type');
    const feedbackGroup = document.querySelector('.feedback-group'); // Wrap feedback in a div with this class
    const feedbackLabel = document.querySelector('label[for="feedback"]');
    const feedbackTextarea = document.getElementById('feedback');

    // Hide feedback field initially
    feedbackGroup.style.display = 'none';

    meetTypeSelect.addEventListener('change', function() {
        if (this.value === 'Online' || this.value === 'F2F') {
            // Show feedback field
            feedbackGroup.style.display = 'block';

            // Update label and placeholder based on meet type
            if (this.value === 'Online') {
                feedbackLabel.textContent = 'Interview Meeting Link';
                feedbackTextarea.placeholder = 'Enter the online meeting link...';
            } else if (this.value === 'F2F') {
                feedbackLabel.textContent = 'Room/Interview Meeting Link';
                feedbackTextarea.placeholder = 'Enter the room number or meeting link...';
            }
        } else {
            // Hide feedback field and clear its value if no meet type is selected
            feedbackGroup.style.display = 'none';
            feedbackTextarea.value = '';
        }
    });
});
</script>

</body>
</html>