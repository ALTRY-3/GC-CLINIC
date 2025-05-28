<?php
// filepath: c:\xampp\htdocs\MedicalClinic\send_appointment_notification.php
include 'config.php';
include 'send_mail.php'; // Include PHPMailer functions

function createAppointmentEmailTemplate($studentName, $message, $appointmentDetails = null) {
    $currentYear = date('Y');
    
    $template = "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Medical Clinic Notification</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; }
            .email-container { max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #2e7d32, #4caf50); color: white; padding: 30px 20px; text-align: center; }
            .header h1 { font-size: 28px; margin-bottom: 10px; }
            .header p { font-size: 16px; opacity: 0.9; }
            .content { padding: 30px 20px; }
            .appointment-card { background: #f8f9fa; border-left: 4px solid #2e7d32; padding: 20px; margin: 20px 0; border-radius: 5px; }
            .appointment-details { margin: 15px 0; }
            .appointment-details strong { color: #2e7d32; }
            .message-box { background: #e3f2fd; border-radius: 8px; padding: 20px; margin: 20px 0; border-left: 4px solid #1976d2; }
            .footer { background: #333; color: white; padding: 20px; text-align: center; font-size: 14px; }
            .footer a { color: #4caf50; text-decoration: none; }
            .btn { display: inline-block; background: #2e7d32; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; margin: 10px 0; }
            .status-pending { color: #ff9800; font-weight: bold; }
            .status-approved { color: #4caf50; font-weight: bold; }
            .status-cancelled { color: #f44336; font-weight: bold; }
            .status-completed { color: #2196f3; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='header'>
                <h1>🏥 Medical Clinic</h1>
                <p>Your Healthcare Partner</p>
            </div>
            <div class='content'>
                <h2>Hello, $studentName!</h2>
                
                <div class='message-box'>
                    <p>$message</p>
                </div>";
    
    if ($appointmentDetails) {
        $template .= "
                <div class='appointment-card'>
                    <h3>📅 Appointment Details</h3>
                    <div class='appointment-details'>
                        <p><strong>Doctor:</strong> {$appointmentDetails['doctor']}</p>
                        <p><strong>Date:</strong> {$appointmentDetails['date']}</p>
                        <p><strong>Time:</strong> {$appointmentDetails['time']}</p>
                        <p><strong>Status:</strong> <span class='status-{$appointmentDetails['status_class']}'>{$appointmentDetails['status']}</span></p>
                    </div>
                </div>";
    }
    
    $template .= "
                <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;'>
                    <p><strong>Important:</strong></p>
                    <ul style='margin: 10px 0; padding-left: 20px;'>
                        <li>Please arrive 15 minutes before your appointment time</li>
                        <li>Bring a valid ID and any relevant medical documents</li>
                        <li>Contact us if you need to reschedule or cancel</li>
                    </ul>
                </div>
                
                <div style='text-align: center; margin-top: 30px;'>
                    <p>Need help? Contact us:</p>
                    <p><strong>📞 Phone:</strong> (555) 123-4567</p>
                    <p><strong>📧 Email:</strong> info@medicalclinic.com</p>
                </div>
            </div>
            <div class='footer'>
                <p>&copy; $currentYear Medical Clinic. All rights reserved.</p>
                <p>This is an automated message. Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>";
    
    return $template;
}

function sendAppointmentNotification($studentID, $message, $appointmentID = null, $emailSubject = "Appointment Notification") {
    global $conn;
    
    // Validate student ID is not empty
    if (empty($studentID)) {
        error_log("Invalid StudentID: " . $studentID);
        return false;
    }
    
    // Verify student exists and get email
    $checkStudentQuery = "SELECT StudentID, email, firstName, lastName FROM students WHERE StudentID = ?";
    $checkStmt = $conn->prepare($checkStudentQuery);
    
    if (!$checkStmt) {
        error_log("Database prepare error: " . $conn->error);
        return false;
    }
    
    $checkStmt->bind_param("s", $studentID);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        error_log("Student not found: " . $studentID);
        $checkStmt->close();
        return false;
    }
    
    $student = $result->fetch_assoc();
    $studentEmail = $student['email'];
    $studentName = $student['firstName'] . ' ' . $student['lastName'];
    $checkStmt->close();
    
    // Validate email format
    if (!filter_var($studentEmail, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid email address for student: " . $studentID . " Email: " . $studentEmail);
        return false;
    }
    
    // Log notification creation attempt
    error_log("Creating notification for StudentID: " . $studentID . " Email: " . $studentEmail);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert notification into database first
        $query = "INSERT INTO notifications (studentID, appointmentID, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())";
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        
        $stmt->bind_param("sis", $studentID, $appointmentID, $message);
        $success = $stmt->execute();
        
        if (!$success) {
            throw new Exception("Database execute error: " . $stmt->error);
        }
        
        $stmt->close();
        
        // Create HTML email content
        $appointmentDetails = null;
        if ($appointmentID) {
            // Get appointment details for the email
            $detailsQuery = "SELECT a.AppointmentDate, d.FirstName, d.LastName, 
                                   ts.StartTime, ts.EndTime, s.status_name AS StatusName
                            FROM appointments a
                            LEFT JOIN doctors d ON a.DoctorID = d.DoctorID
                            LEFT JOIN timeslots ts ON a.SlotID = ts.SlotID
                            LEFT JOIN status s ON a.statusID = s.statusID
                            WHERE a.AppointmentID = ?";
            $detailsStmt = $conn->prepare($detailsQuery);
            if (!$detailsStmt) {
                error_log("[ERROR] Failed to prepare detailsStmt: " . $conn->error);
                return false;
            }
            $detailsStmt->bind_param("i", $appointmentID);
            $detailsStmt->execute();
            $detailsResult = $detailsStmt->get_result();
            
            if ($detailsResult->num_rows > 0) {
                $details = $detailsResult->fetch_assoc();
                $appointmentDetails = [
                    'doctor' => "Dr. " . $details['FirstName'] . " " . $details['LastName'],
                    'date' => date('F j, Y', strtotime($details['AppointmentDate'])),
                    'time' => ($details['StartTime'] && $details['EndTime']) ? 
                             date('g:i A', strtotime($details['StartTime'])) . ' - ' . date('g:i A', strtotime($details['EndTime'])) : 
                             'Time TBD',
                    'status' => $details['StatusName'] ?? 'Pending',
                    'status_class' => strtolower(str_replace(' ', '-', $details['StatusName'] ?? 'pending'))
                ];
            }
            $detailsStmt->close();
        }
        
        // Create email template
        $emailBody = createAppointmentEmailTemplate($studentName, $message, $appointmentDetails);
        
        // Send email using PHPMailer
        $emailSent = sendAppointmentEmail($studentEmail, $studentName, $emailSubject, $emailBody);
        
        if ($emailSent) {
            error_log("Email sent successfully to: " . $studentEmail);
            $conn->commit();
            return true;
        } else {
            error_log("Failed to send email to: " . $studentEmail);
            // Don't rollback transaction - keep the notification in database even if email fails
            $conn->commit();
            return false;
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Failed to create notification for StudentID: " . $studentID . " Error: " . $e->getMessage());
        return false;
    }
}

// Function to send notification when appointment status changes
function sendAppointmentStatusNotification($appointmentID, $status) {
    global $conn;
    error_log("[DEBUG] sendAppointmentStatusNotification called with appointmentID=$appointmentID, status=$status");
    
    if (!is_numeric($appointmentID) || $appointmentID <= 0) {
        error_log("[ERROR] Invalid appointment ID: " . $appointmentID);
        return false;
    }
    
    // Get appointment details with time slot information
    $query = "SELECT a.studentID, a.appointmentDate, d.FirstName, d.LastName,
                     ts.StartTime, ts.EndTime, a.Reason
              FROM appointments a 
              JOIN doctors d ON a.doctorID = d.doctorID 
              LEFT JOIN timeslots ts ON a.SlotID = ts.SlotID
              WHERE a.appointmentID = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("[ERROR] Database prepare error: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("i", $appointmentID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        error_log("[ERROR] Appointment not found: " . $appointmentID);
        $stmt->close();
        return false;
    }
    
    $row = $result->fetch_assoc();
    $stmt->close();
    
    error_log("[DEBUG] Appointment details: " . print_r($row, true));
    
    $appointmentTime = 'scheduled time';
    if ($row['StartTime'] && $row['EndTime']) {
        $appointmentTime = date('g:i A', strtotime($row['StartTime'])) . ' - ' . date('g:i A', strtotime($row['EndTime']));
    }
    
    $doctorName = "Dr. " . $row['FirstName'] . " " . $row['LastName'];
    $appointmentDate = date('F j, Y', strtotime($row['appointmentDate']));
    
    $statusMessage = "";
    $subject = "";
    
    switch (strtolower($status)) {
        case 'pending':
            $statusMessage = "has been submitted and is pending approval";
            $subject = "📋 Appointment Submitted - Pending Approval";
            break;
        case 'approved':
            $statusMessage = "has been approved and confirmed";
            $subject = "✅ Appointment Approved - " . $doctorName;
            break;
        case 'rejected':
            $statusMessage = "has been rejected. Please contact us for alternative options";
            $subject = "❌ Appointment Rejected - " . $doctorName;
            break;
        case 'cancelled':
            $statusMessage = "has been cancelled";
            $subject = "🚫 Appointment Cancelled - " . $doctorName;
            break;
        case 'cancellation_approved':
            $statusMessage = "cancellation request has been approved";
            $subject = "✅ Cancellation Request Approved - " . $doctorName;
            break;
        case 'cancellation_rejected':
            $statusMessage = "cancellation request has been rejected. Your appointment is still scheduled";
            $subject = "❌ Cancellation Request Rejected - " . $doctorName;
            break;
        case 'completed':
            $statusMessage = "has been completed. Thank you for visiting our clinic";
            $subject = "✅ Appointment Completed - " . $doctorName;
            break;
        default:
            error_log("Unknown status: " . $status);
            return false;
    }
    
    $message = "Your appointment with " . $doctorName . 
              " on " . $appointmentDate . 
              " at " . $appointmentTime . 
              " " . $statusMessage . ".";
    
    // Add additional information for specific statuses
    if (strtolower($status) === 'approved') {
        $message .= " Please arrive 15 minutes before your scheduled time and bring a valid ID.";
    } elseif (strtolower($status) === 'pending') {
        $message .= " You will receive another notification once your appointment is reviewed by the doctor.";
    } elseif (strtolower($status) === 'completed') {
        $message .= " If you have any questions about your visit or need follow-up care, please contact us.";
    }
    
    return sendAppointmentNotification($row['studentID'], $message, $appointmentID, $subject);
}

// Function to send appointment booking confirmation
function sendAppointmentBookingConfirmation($appointmentID) {
    return sendAppointmentStatusNotification($appointmentID, 'pending');
}

// Test function to check if the notification system works
function testNotificationSystem($testStudentID = null) {
    global $conn;
    
    if (!$testStudentID) {
        // Get a student ID for testing
        $query = "SELECT StudentID, firstName, lastName, email FROM students LIMIT 1";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $student = $result->fetch_assoc();
            $testStudentID = $student['StudentID'];
            echo "Testing with Student: " . $student['firstName'] . " " . $student['lastName'] . " (" . $student['email'] . ")<br>";
        } else {
            echo "No students found in database for testing.<br>";
            return false;
        }
    }
    
    $testMessage = "This is a test notification to verify that the email system is working correctly. If you receive this email, the notification system is functioning properly.";
    $testSubject = "🧪 Test Email Notification - Medical Clinic";
    
    echo "Sending test notification...<br>";
    $result = sendAppointmentNotification($testStudentID, $testMessage, null, $testSubject);
    
    if ($result) {
        echo "✅ Test notification sent successfully!<br>";
    } else {
        echo "❌ Failed to send test notification.<br>";
    }
    
    return $result;
}
?>