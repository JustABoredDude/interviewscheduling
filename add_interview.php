<?php
require_once 'db.php';

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Generate a unique token for this form submission
    if (!isset($_POST['form_token'])) {
        $_SESSION['form_token'] = bin2hex(random_bytes(32));
    }
    
    $form_token = $_POST['form_token'] ?? '';
    
    // Verify the form token to prevent duplicate submissions
    if (!isset($_SESSION['form_token']) || !hash_equals($_SESSION['form_token'], $form_token)) {
        die("Invalid form submission");
    }
    
    // Clear the token so the form can't be submitted again
    unset($_SESSION['form_token']);

    // Check if all required fields are filled
    $required_fields = ['interviewer_name', 'interviewer_email', 'program', 'applicant_name', 'applicant_email', 'scheduled_time', 'meet_type'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        $error = "Please fill in all required fields: " . implode(', ', $missing_fields);
    } else {
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
            // Check if this interview already exists to prevent duplicates
            $check_stmt = $conn->prepare("SELECT i.id FROM interviews i
                                        JOIN applicants a ON i.applicant_id = a.id
                                        JOIN interviewers ir ON i.interviewer_id = ir.id
                                        WHERE a.email = ? AND ir.email = ? AND i.scheduled_time = ?");
            $check_stmt->bind_param("sss", $applicant_email, $interviewer_email, $scheduled_time);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error = "This interview already exists in the system.";
            } else {
                // Start transaction to ensure all operations complete successfully
                $conn->begin_transaction();
                
                try {
                    // Check if interviewer exists
                    $stmt = $conn->prepare("SELECT id, name FROM interviewers WHERE email = ? FOR UPDATE");
                    $stmt->bind_param("s", $interviewer_email);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        $interviewer_id = $row['id'];
                        // Update interviewer name if it has changed
                        if ($row['name'] !== $interviewer_name) {
                            $stmt = $conn->prepare("UPDATE interviewers SET name = ? WHERE id = ?");
                            $stmt->bind_param("si", $interviewer_name, $interviewer_id);
                            $stmt->execute();
                        }
                    } else {
                        // Insert new interviewer
                        $stmt = $conn->prepare("INSERT INTO interviewers (name, email, program_id) VALUES (?, ?, ?)");
                        $stmt->bind_param("ssi", $interviewer_name, $interviewer_email, $program_id);
                        $stmt->execute();
                        $interviewer_id = $conn->insert_id;
                    }

                    // Check if applicant exists
                    $stmt = $conn->prepare("SELECT id, name FROM applicants WHERE email = ? FOR UPDATE");
                    $stmt->bind_param("s", $applicant_email);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        $applicant_id = $row['id'];
                        // Update applicant name if it has changed
                        if ($row['name'] !== $applicant_name) {
                            $stmt = $conn->prepare("UPDATE applicants SET name = ? WHERE id = ?");
                            $stmt->bind_param("si", $applicant_name, $applicant_id);
                            $stmt->execute();
                        }
                    } else {
                        // Insert new applicant
                        $stmt = $conn->prepare("INSERT INTO applicants (name, email) VALUES (?, ?)");
                        $stmt->bind_param("ss", $applicant_name, $applicant_email);
                        $stmt->execute();
                        $applicant_id = $conn->insert_id;
                    }

                    // Insert new interview
                    $stmt = $conn->prepare("INSERT INTO interviews (applicant_id, interviewer_id, program_id, scheduled_time, status, meet_type, feedback) 
                                           VALUES (?, ?, ?, ?, 'scheduled', ?, ?)");
                    $stmt->bind_param("iiisss", $applicant_id, $interviewer_id, $program_id, $scheduled_time, $meet_type, $feedback);

                    if ($stmt->execute()) {
                        $conn->commit();
                        $newId = mysqli_insert_id($conn);
                        // Redirect to index.php with success message
                        $_SESSION['success'] = "Interview scheduled successfully!";
                        header("Location: index.php");
                        exit();
                    } else {
                        $conn->rollback();
                        $error = "Error adding interview: " . mysqli_error($conn);
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "An error occurred: " . $e->getMessage();
                }
            }
        }
    }
}

// Generate a new token for the form
$_SESSION['form_token'] = bin2hex(random_bytes(32));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Interviewer & Applicant</title>
    <link rel="stylesheet" href="style.css">
    <script>
        // Client-side validation and duplicate submission prevention
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            let isSubmitting = false;
            
            form.addEventListener('submit', function(e) {
                if (isSubmitting) {
                    e.preventDefault();
                    alert('Your form is already being submitted. Please wait...');
                    return;
                }
                
                let isValid = true;
                const requiredFields = form.querySelectorAll('[required]');
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.style.borderColor = 'red';
                    } else {
                        field.style.borderColor = '';
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                } else {
                    isSubmitting = true;
                    // Disable the submit button to prevent double clicks
                    const submitBtn = form.querySelector('button[type="submit"]');
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Submitting...';
                }
            });
            
            // Show/hide feedback field based on meet type
            const meetTypeSelect = document.getElementById('meet_type');
            const feedbackGroup = document.querySelector('.feedback-group');
            
            meetTypeSelect.addEventListener('change', function() {
                if (this.value === 'F2F' || this.value === 'Online') {
                    feedbackGroup.style.display = 'block';
                } else {
                    feedbackGroup.style.display = 'none';
                }
            });
        });
    </script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white shadow-md rounded-lg p-6 w-96 border border-border-gray-200">
        
        <!-- Header -->
        <div class="bg-green-100 text-center py-2 rounded-t-lg">
            <h2 class="text-lg font-semibold text-black">New Interview</h2>
        </div>

        <?php if(isset($error)): ?>
            <div class="bg-red-100 text-red-700 p-2 my-2 rounded">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="add_interview.php" method="POST" class="space-y-4 mt-4">
            <input type="hidden" name="form_token" value="<?= htmlspecialchars($_SESSION['form_token']) ?>">
            
            <!-- Applicant -->
            <div>
                <label for="applicant_name" class="block text-sm font-medium text-gray-700">Applicant Name</label>
                <input type="text" id="applicant_name" name="applicant_name" required 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 focus:outline-none focus:ring focus:ring-green-300">
            </div>

            <div>
                <label for="applicant_email" class="block text-sm font-medium text-gray-700">Applicant Email</label>
                <input type="email" id="applicant_email" name="applicant_email" required 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 focus:outline-none focus:ring focus:ring-green-300">
            </div>
            
            <div>
                <label for="scheduled_time" class="block text-sm font-medium text-gray-700">Date & Time</label>
                <input type="datetime-local" id="scheduled_time" name="scheduled_time" required 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 focus:outline-none focus:ring focus:ring-green-300">
            </div>
            
            <div>
                <label for="program" class="block text-sm font-medium text-gray-700">Program</label>
                <select id="program" name="program" required 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 focus:outline-none focus:ring focus:ring-green-300">
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
            
            <div>
                <label for="interviewer_name" class="block text-sm font-medium text-gray-700">Interviewer Name</label>
                <input type="text" id="interviewer_name" name="interviewer_name" required 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 focus:outline-none focus:ring focus:ring-green-300">
            </div>

            <div>
                <label for="interviewer_email" class="block text-sm font-medium text-gray-700">Interviewer Email</label>
                <input type="email" id="interviewer_email" name="interviewer_email" required 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 focus:outline-none focus:ring focus:ring-green-300">
            </div>
     
            <!-- Meet Type -->
            <div class="form-group" style="margin-bottom: 10px;">
                <label for="meet_type" class="block text-sm font-medium text-gray-700">Meet Type</label>
                <select id="meet_type" name="meet_type" required class="form-control" style="padding: 6px;">
                    <option value="" disabled selected>Select Meet Type</option>
                    <option value="F2F">F2F (Face-to-Face)</option>
                    <option value="Online">Online</option>
                </select>
            </div>

            <!-- Feedback Field -->
            <div class="form-group feedback-group" style="margin-bottom: 10px; display: none;">
                <label for="feedback" class="block text-sm font-medium text-gray-700">Feedback (Optional)</label>
                <textarea id="feedback" name="feedback" class="form-control" style="padding: 6px; min-height: 80px;" placeholder="Add your feedback here..."></textarea>
            </div>
       
            <!-- Buttons -->
            <div class="flex justify-between mt-4">
                <a href="index.php" class="px-4 py-2 bg-red-100 text-red-700 rounded-md hover:bg-red-200 font-semibold">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-green-700 text-white rounded-md hover:bg-green-800 font-semibold">Confirm</button>
            </div>
        </form>
    </div>
</body>
</html>