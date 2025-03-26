<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

// Set default filter to trash
$status_filter = 'trash';
$selected_program = $_GET['program'] ?? null;
$sort = $_GET['sort'] ?? 'date_desc';
$search = $_GET['search'] ?? '';

// Fetch trashed interviews
// Fetch trashed interviews
// Fetch trashed interviews
$interviews = getInterviews($conn, $selected_program, 'trash', $search, $sort);

// Move an interview to trash
if (isset($_GET['move_to_trash'])) {
    $id = $_GET['move_to_trash'];
    $result = moveToTrash($conn, $id);
    if ($result['success']) {
        $success_message = "Interview moved to trash successfully!";
    } else {
        $error_message = "Error moving interview to trash: " . $result['error'];
    }
}

// Restore an interview from trash
if (isset($_GET['restore'])) {
    $id = $_GET['restore'];
    $result = restoreInterview($conn, $id);
    if ($result['success']) {
        $success_message = "Interview restored successfully!";
    } else {
        $error_message = "Error restoring interview: " . $result['error'];
    }
}

// Empty trash
if (isset($_GET['empty_trash'])) {
    $result = emptyTrash($conn);
    if ($result['success']) {
        $success_message = "Trash emptied successfully!";
    } else {
        $error_message = "Error emptying trash: " . $result['error'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trash - Interview Scheduling</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); animation: fadeIn 0.3s; }
        .modal-content { background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 90%; max-width: 500px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); animation: slideDown 0.3s; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover, .close:focus { color: black; text-decoration: none; }
    </style>

<style>
  thead {
    background-color: #b91c1c !important; /* Equivalent to bg-red-700 */
  }
</style>

</head>
<body class="bg-gray-100 min-h-screen p-6" style="background-image: linear-gradient(rgba(173, 72, 65, 0.46),rgba(173, 72, 65, 0.46)), url('assets/plv.jpeg'); background-size: cover; background-position: center; background-repeat: no-repeat;">

<div class="half-w-6xl w-full bg-white p-6 shadow-lg rounded-lg">
    <!-- Header -->
    <header class="flex justify-between items-center border-b border-gray-300 pb-4 px-4 w-full max-w-[1200px] mx-auto">
    <h1 class="text-2xl font-bold text-red-700">Trash</h1>
    
    <div class="flex gap-4"> <!-- Adds spacing between buttons -->
        <a href="index.php" class="px-4 py-2 bg-green-100 text-green-700 border border-green-600 rounded-full hover:bg-green-200 flex items-center justify-center gap-2">
            Back to Schedule
        </a>
        
        <?php if (!empty($interviews)): ?>
            <button id="emptyTrashBtn" class="px-4 py-2 bg-red-100 text-red-700 border border-red-600 rounded-full hover:bg-red-200 flex items-center justify-center gap-2">
                Empty Trash
            </button>
        <?php endif; ?>
    </div>
</header>



    <?php if (isset($success_message)): ?>
        <div class="bg-green-100 text-green-700 p-3 my-3 rounded">
            <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="bg-red-100 text-red-700 p-3 my-3 rounded">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <!-- Updated Filters section with right-aligned buttons -->
    <div class="flex flex-col sm:flex-row justify-between items-center mt-4 mb-6 gap-4">
        <form id="searchForm" method="GET" action="trash.php" class="flex items-center">
            <div class="relative rounded-full shadow-md">
                <input type="text" name="search" class="rounded-full border border-gray-300 py-2 pl-5 pr-10 focus:outline-none w-64" placeholder="Search trash..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="absolute right-0 top-0 h-full px-4 rounded-full bg-transparent hover:bg-gray-100 text-gray-600">
                    <img src="assets/search.png" alt="Search" class="w-5 h-5">
                </button>
            </div>
        </form>

        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
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
            
            <form id="filterForm" method="GET" action="trash.php" class="p-4">
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
                
                <!-- Program -->
                <div class="mb-4">
                    <h3 class="font-medium mb-2">Program</h3>
                    <div class="space-y-2">
                        <div>
                            <input type="checkbox" id="program_bscs" name="program" value="COT" 
                            <?= ($selected_program == 'BSCS') ? 'checked' : '' ?>>
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
                            <input type="checkbox" id="program_bscs" name="program" value="COE" 
                            <?= ($selected_program == 'BSCS') ? 'checked' : '' ?>>
                            <label for="program_coed" class="ml-2 text-green-700 font-medium">College of Education</label>
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
                            <input type="checkbox" id="program_bscs" name="program" value="CBA" 
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
                                <input type="checkbox" id="program_bshrm" name="program" value="BSHRM" 
                                    <?= ($selected_program == 'BSHRM') ? 'checked' : '' ?>>
                                <label for="program_bshrm" class="ml-2">BSHRM</label>
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
            <!-- Update the reset button in your filter modal (trash.php) -->
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
            
            <form id="sortForm" method="GET" action="trash.php" class="p-4">
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
                    <button type="submit"class="bg-green-700 hover:bg-green-600 text-white px-4 py-2 rounded-md font-medium text-sm transition-colors">Apply</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
  <!-- Table -->
<div class="overflow-x-auto">
    <table class="w-full border-collapse bg-white shadow-md rounded-lg">
    <thead class="bg-red-700 text-white">            
        <tr>
                <th class="p-3 text-left" width="15%">Applicant Name</th>
                <th class="p-3 text-center" width="30%">Date Cancelled</th>
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
                                <span class="font-medium"><?= date('M d, Y', strtotime($interview['cancelled_date'] ?? 'now')); ?></span>
                            </div>
                        </td>
                        <td class="p-3 font-medium text-center">
                            <span class="px-3 py-1 bg-green-50 text-black-800 rounded-full"><?= htmlspecialchars($interview['interviewer'] ?? 'N/A'); ?></span>
                        </td>
                        <td class="p-3 text-center">
                            <span class="px-3 py-1 rounded-full font-medium bg-red-100 text-red-700">
                                <?= ucfirst($interview['status'] ?? 'Unknown'); ?>
                            </span>
                        </td>
                        <td class="p-3">
                            <div class="flex justify-center space-x-2">
                                <button onclick="deleteInterview(<?= $interview['id']; ?>)" class="p-2 bg-red-400 text-white rounded-lg hover:bg-red-500 flex items-center justify-center">
                                    <img src="assets/delete.png" alt="Delete" title="Delete" class="w-4 h-4">
                                </button>
                                <button onclick="restoreInterview(<?= $interview['id']; ?>)" class="p-2 bg-green-400 text-white rounded-lg hover:bg-green-500 flex items-center justify-center">
                                    <img src="assets/restore.png" alt="Restore" title="Restore" class="w-4 h-4">
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center text-gray-500 p-3">No interviews found in trash.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    // JavaScript for filters, modal, and trash functionality
    function updateFilter(type, value) {
        const url = new URL(window.location.href);
        url.searchParams.set(type, value);
        window.location.href = url.toString();
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

    function emptyTrash() {
        if (confirm("Are you sure you want to permanently delete all interviews in the trash? This action cannot be undone.")) {
            window.location.href = 'trash.php?empty_trash=true';
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
 

    // Modal functionality
    function openModal(modalElement) {
        modalElement.style.display = "block";
        document.body.style.overflow = "hidden";
    }

    function closeModal(modalElement) {
        modalElement.style.display = "none";
        document.body.style.overflow = "auto";
    }

    // Event handlers for empty trash button
    const emptyTrashBtn = document.getElementById("emptyTrashBtn");
    if (emptyTrashBtn) {
        emptyTrashBtn.onclick = emptyTrash;
    }

    // Event handlers for search form
    document.getElementById('searchForm').addEventListener('keypress', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            this.submit();
        }
    });

    // Close modals when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            closeModal(event.target);
        }
    };
</script>

<script>
// Reset filters function - modified to keep modal open
function resetFilterForm(event) {
    // Prevent default form submission
    event.preventDefault();
    
    // Reset date inputs
    document.getElementById('date_from').value = '';
    document.getElementById('date_to').value = '';
    
    // Reset all program checkboxes (both colleges and individual programs)
    const allProgramCheckboxes = document.querySelectorAll('#filterForm input[name="program"]');
    allProgramCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // Reset the college checkboxes
    document.querySelector('input[value="COT"]').checked = false;
    document.querySelector('input[value="COE"]').checked = false;
    document.querySelector('input[value="CBA"]').checked = false;
    
    // Don't submit the form - just clear the checkboxes
    // The modal will stay open
}
</script>
</body>
</html>
   