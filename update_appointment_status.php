<?php
include 'config.php';
require_once 'send_appointment_notification.php';
session_start();

if (!isset($_SESSION['adminID'])) {
    header('Location: admin_login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id']) && isset($_POST['status'])) {
    $appointmentID = $_POST['appointment_id'];
    $newStatus = $_POST['status'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get appointment details
        $query = "SELECT a.*, s.FirstName AS StudentFirstName, s.LastName AS StudentLastName, s.email AS StudentEmail,
                         d.FirstName AS DoctorFirstName, d.LastName AS DoctorLastName,
                         ts.StartTime, ts.EndTime
                  FROM appointments a
                  JOIN students s ON a.StudentID = s.StudentID
                  JOIN doctors d ON a.DoctorID = d.DoctorID
                  JOIN timeslots ts ON a.SlotID = ts.SlotID
                  WHERE a.AppointmentID = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $appointmentID);
        $stmt->execute();
        $result = $stmt->get_result();
        $appointment = $result->fetch_assoc();
        
        if ($appointment) {
            // Update appointment status
            $updateQuery = "UPDATE appointments SET StatusID = ? WHERE AppointmentID = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("ii", $newStatus, $appointmentID);
            $updateStmt->execute();
            
            // Send email notification for all status changes
            $statusMap = [
                1 => 'pending',
                2 => 'approved',
                3 => 'completed',
                4 => 'cancelled'
            ];
            if (isset($statusMap[$newStatus])) {
                $emailSent = sendAppointmentStatusNotification($appointmentID, $statusMap[$newStatus]);
                if (!$emailSent) {
                    error_log("Failed to send email notification for appointment ID: $appointmentID, status: $newStatus");
                }
            }
            
            // Create notification for student
            $message = "";
            switch ($newStatus) {
                case 2: // Approved
                    $message = "Your appointment with Dr. " . $appointment['DoctorLastName'] . 
                             " on " . date('F j, Y', strtotime($appointment['AppointmentDate'])) . 
                             " has been approved.";
                    break;
                case 3: // Completed
                    $message = "Your appointment with Dr. " . $appointment['DoctorLastName'] . 
                             " on " . date('F j, Y', strtotime($appointment['AppointmentDate'])) . 
                             " has been marked as completed.";
                    break;
                case 4: // Cancelled
                    $message = "Your appointment with Dr. " . $appointment['DoctorLastName'] . 
                             " on " . date('F j, Y', strtotime($appointment['AppointmentDate'])) . 
                             " has been cancelled.";
                    break;
            }
            
            if ($message) {
                $insertNotification = $conn->prepare("INSERT INTO notifications (studentID, appointmentID, message) VALUES (?, ?, ?)");
                $insertNotification->bind_param("iis", $appointment['StudentID'], $appointmentID, $message);
                $insertNotification->execute();
            }
            
            $conn->commit();
            $_SESSION['success_message'] = "Appointment status has been updated successfully.";
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error updating appointment status: " . $e->getMessage();
    }
} else {
    $_SESSION['error_message'] = "Invalid request.";
}

// Redirect back to student management
header('Location: student_management.php');
exit();
?> 