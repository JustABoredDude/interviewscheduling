<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'db.php';
require_once 'vendor/autoload.php'; 

function getApplicantName($conn, $applicant_id) {
    $stmt = $conn->prepare("SELECT name FROM applicants WHERE id = ?");
    $stmt->bind_param("i", $applicant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['name'];
    }
    return 'Unknown Applicant';
}

/**
 * Get interviewer name by ID
 */
function getInterviewerName($conn, $interviewer_id) {
    $stmt = $conn->prepare("SELECT name FROM interviewers WHERE id = ?");
    $stmt->bind_param("i", $interviewer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['name'];
    }
    return 'Unknown Interviewer';
}

/**
 * Get applicant email by ID
 */
function getApplicantEmail($conn, $applicant_id) {
    $stmt = $conn->prepare("SELECT email FROM applicants WHERE id = ?");
    $stmt->bind_param("i", $applicant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['email'];
    }
    return null;
}

/**
 * Get interviewer email by ID
 */
function getInterviewerEmail($conn, $interviewer_id) {
    $stmt = $conn->prepare("SELECT email FROM interviewers WHERE id = ?");
    $stmt->bind_param("i", $interviewer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['email'];
    }
    return null;
}

/**
 * Send interview email to applicant and interviewer
 */
function sendInterviewEmail($applicant_email, $interviewer_email, $interview_details) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'jovincepro09@gmail.com';  
        $mail->Password   = 'ryswdgxvypblhpzb';    
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('jovincepro09@gmail.com', 'Baliwag Institute of Technology - Admission Office');
        $mail->addAddress($applicant_email);
        $mail->addCC($interviewer_email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Interview Schedule Notification: Baliwag Institute of Technology';
        $mail->Body    = "
            <!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #4DD691; color: white; padding: 10px; text-align: center; }
                    .content { padding: 20px; }
                    .footer { font-size: 0.8em; color: #666; text-align: center; padding: 10px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Interview Schedule Notification</h1>
                    </div>
                    <div class='content'>
                        <p>Good day {$interview_details['applicant_name']},</p>
                        
                        <p>We are pleased to inform you that your interview for admission at Baliwag Institute of Technology has been scheduled. Below are the details of your interview:</p>
                        
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr>
                                <td style='padding: 10px; border: 1px solid #ddd;'><strong>Date:</strong></td>
                                <td style='padding: 10px; border: 1px solid #ddd;'>{$interview_details['date']}</td>
                            </tr>
                            <tr>
                                <td style='padding: 10px; border: 1px solid #ddd;'><strong>Time:</strong></td>
                                <td style='padding: 10px; border: 1px solid #ddd;'>{$interview_details['time']}</td>
                            </tr>
                            <tr>
                                <td style='padding: 10px; border: 1px solid #ddd;'><strong>Interviewer:</strong></td>
                                <td style='padding: 10px; border: 1px solid #ddd;'>{$interview_details['interviewer']}</td>
                            </tr>
                            <tr>
                                <td style='padding: 10px; border: 1px solid #ddd;'><strong>Mode of Interview:</strong></td>
                                <td style='padding: 10px; border: 1px solid #ddd;'>{$interview_details['meet_type']}</td>
                            </tr>
                        </table>
                        
                        <p>Please ensure that you are available at the scheduled time. If the interview is online, kindly check your internet connection and prepare any necessary documents in advance.</p>
                        
                        <p>If you have any questions or need to reschedule, please contact our admission office.</p>
                        
                        <p>Best regards,<br>
                        Admission Admin<br>
                        Baliwag Institute of Technology</p>
                    </div>
                    <div class='footer'>
                        <p>This is an automated email. Please do not reply.</p>
                    </div>
                </div>
            </body>
            </html>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Fetch interviews with optional filtering
 */
function getInterviews($conn, $program = null, $status = 'scheduled', $search = '', $sort = 'date_desc') {
    $sql = "SELECT 
                i.id, 
                a.name AS applicant, 
                p.name AS program, 
                v.name AS interviewer, 
                i.scheduled_time, 
                i.status,
                i.cancelled_date
            FROM interviews i
            LEFT JOIN applicants a ON i.applicant_id = a.id
            LEFT JOIN interviewers v ON i.interviewer_id = v.id
            LEFT JOIN programs p ON i.program_id = p.id
            WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if ($status) {
        $sql .= " AND i.status = ?";
        $params[] = $status;
        $types .= "s";
    }

    if ($program) {
        $sql .= " AND p.name = ?";
        $params[] = $program;
        $types .= "s";
    }

    if (!empty($search)) {
        $sql .= " AND (a.name LIKE ? OR v.name LIKE ? OR p.name LIKE ?)";
        $searchTerm = "%$search%";
        array_push($params, $searchTerm, $searchTerm, $searchTerm);
        $types .= "sss";
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

/**
 * Fetch all applicants from the database
 */
function getApplicants($conn) {
    $sql = "SELECT * FROM applicants";
    $result = $conn->query($sql);

    if (!$result) {
        die("Error fetching applicants: " . $conn->error);
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Fetch all programs from the database
 */
function getPrograms($conn) {
    $sql = "SELECT id, name, college FROM programs";
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Fetch interview by ID
 */
function getInterviewById($conn, $id) {
    $sql = "SELECT 
                i.id, 
                a.name AS applicant_name, 
                a.email AS applicant_email, 
                ir.name AS interviewer_name, 
                p.name AS program_name,  
                p.college AS program_college,  
                i.scheduled_time,
                i.meet_type, 
                i.status, 
                i.created_at,
                i.feedback
            FROM interviews i
            JOIN applicants a ON i.applicant_id = a.id
            JOIN interviewers ir ON i.interviewer_id = ir.id
            JOIN programs p ON i.program_id = p.id
            WHERE i.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Delete an interview from the database
 */
function deleteInterview($conn, $id) {
    $stmt = $conn->prepare("DELETE FROM interviews WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    $success = $stmt->execute();
    $error = $stmt->error;
    
    $stmt->close();
    
    return [
        "success" => $success,
        "error" => $error
    ];
}

/**
 * Move an interview to trash
 */
function moveToTrash($conn, $id) {
    $stmt = $conn->prepare("UPDATE interviews SET status = 'trash', cancelled_date = NOW() WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    $success = $stmt->execute();
    $error = $stmt->error;
    
    $stmt->close();
    
    return [
        "success" => $success,
        "error" => $error
    ];
}

/**
 * Restore an interview from trash
 */
function restoreInterview($conn, $id) {
    $stmt = $conn->prepare("UPDATE interviews SET status = 'scheduled', cancelled_date = NULL WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    $success = $stmt->execute();
    $error = $stmt->error;
    
    $stmt->close();
    
    return [
        "success" => $success,
        "error" => $error
    ];
}

/**
 * Empty trash (delete all trashed interviews permanently)
 */
function emptyTrash($conn) {
    $stmt = $conn->prepare("DELETE FROM interviews WHERE status = 'trash'");
    
    $success = $stmt->execute();
    $error = $stmt->error;
    
    $stmt->close();
    
    return [
        "success" => $success,
        "error" => $error
    ];
}
?>