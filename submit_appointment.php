<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

// Check if student is logged in
if (!isset($_SESSION['studentID'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$studentID = $_SESSION['studentID'];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

$doctorID = $input['doctorID'] ?? '';
$appointmentDate = $input['appointmentDate'] ?? '';
$appointmentTime = $input['appointmentTime'] ?? '';
$reason = $input['reason'] ?? '';

// Validate required fields
if (empty($doctorID) || empty($appointmentDate) || empty($appointmentTime) || empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Validate date format and ensure it's not in the past
$dateObj = DateTime::createFromFormat('Y-m-d', $appointmentDate);
if (!$dateObj || $dateObj->format('Y-m-d') !== $appointmentDate) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit;
}

$today = new DateTime();
$today->setTime(0, 0, 0);
if ($dateObj < $today) {
    echo json_encode(['success' => false, 'message' => 'Cannot book appointments for past dates']);
    exit;
}

// Get the day of the week
$dayOfWeek = $dateObj->format('l');

try {
    // Start transaction
    $conn->autocommit(false);

    // UPDATED: Check if doctor has blocked this date
    $blockCheck = "SELECT BlockID, Reason FROM blocked_dates WHERE DoctorID = ? AND BlockedDate = ?";
    $blockStmt = $conn->prepare($blockCheck);
    $blockStmt->bind_param("ss", $doctorID, $appointmentDate);
    $blockStmt->execute();
    $blockResult = $blockStmt->get_result();
    
    if ($blockResult->num_rows > 0) {
        $blockData = $blockResult->fetch_assoc();
        $reason = $blockData['Reason'] ? $blockData['Reason'] : 'unavailable';
        $conn->rollback();
        echo json_encode([
            'success' => false, 
            'message' => "Sorry, Dr. is not available on " . $dateObj->format('F d, Y') . " (Reason: $reason). Please select another date."
        ]);
        exit;
    }
    $blockStmt->close();

    // Parse the time range to get start and end times
    $timeRange = explode('-', $appointmentTime);
    if (count($timeRange) !== 2) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Invalid time format']);
        exit;
    }

    $startTime = trim($timeRange[0]);
    $endTime = trim($timeRange[1]);

    // Find the corresponding time slot
    $slotQuery = "SELECT SlotID FROM timeslots 
                  WHERE DoctorID = ? 
                    AND AvailableDay = ? 
                    AND StartTime = ? 
                    AND EndTime = ? 
                    AND IsAvailable = 1";
    
    $slotStmt = $conn->prepare($slotQuery);
    $slotStmt->bind_param("ssss", $doctorID, $dayOfWeek, $startTime, $endTime);
    $slotStmt->execute();
    $slotResult = $slotStmt->get_result();

    if ($slotResult->num_rows === 0) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Selected time slot is not available']);
        exit;
    }

    $slotData = $slotResult->fetch_assoc();
    $slotID = $slotData['SlotID'];
    $slotStmt->close();

    // Check if slot is already booked for this date
    $existingQuery = "SELECT AppointmentID FROM appointments 
                      WHERE DoctorID = ? 
                        AND SlotID = ? 
                        AND AppointmentDate = ? 
                        AND statusID IN (1, 2, 3)";
    
    $existingStmt = $conn->prepare($existingQuery);
    $existingStmt->bind_param("sis", $doctorID, $slotID, $appointmentDate);
    $existingStmt->execute();
    $existingResult = $existingStmt->get_result();

    if ($existingResult->num_rows > 0) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'This time slot is already booked. Please choose another time.']);
        exit;
    }
    $existingStmt->close();

    // Check if student already has an appointment on this date
    $studentQuery = "SELECT AppointmentID FROM appointments 
                     WHERE StudentID = ? 
                       AND AppointmentDate = ? 
                       AND statusID IN (1, 2, 3)";
    
    $studentStmt = $conn->prepare($studentQuery);
    $studentStmt->bind_param("ss", $studentID, $appointmentDate);
    $studentStmt->execute();
    $studentResult = $studentStmt->get_result();

    if ($studentResult->num_rows > 0) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'You already have an appointment booked for this date.']);
        exit;
    }
    $studentStmt->close();

    // Insert the appointment with status 1 (Pending)
    $insertQuery = "INSERT INTO appointments (StudentID, DoctorID, SlotID, AppointmentDate, Reason, statusID) 
                    VALUES (?, ?, ?, ?, ?, 1)";
    
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bind_param("ssiss", $studentID, $doctorID, $slotID, $appointmentDate, $reason);

    if (!$insertStmt->execute()) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to book appointment. Please try again.']);
        exit;
    }

    $appointmentID = $conn->insert_id;
    $insertStmt->close();

    // Get doctor and student information for notification
    $doctorQuery = "SELECT FirstName, LastName FROM doctors WHERE DoctorID = ?";
    $doctorStmt = $conn->prepare($doctorQuery);
    $doctorStmt->bind_param("s", $doctorID);
    $doctorStmt->execute();
    $doctorResult = $doctorStmt->get_result();
    $doctorInfo = $doctorResult->fetch_assoc();
    $doctorStmt->close();

    $studentQuery = "SELECT firstName, lastName FROM students WHERE studentID = ?";
    $studentStmt = $conn->prepare($studentQuery);
    $studentStmt->bind_param("s", $studentID);
    $studentStmt->execute();
    $studentResult = $studentStmt->get_result();
    $studentInfo = $studentResult->fetch_assoc();
    $studentStmt->close();

    // Create notification for the student
    $notificationMessage = "Your appointment with Dr. " . $doctorInfo['FirstName'] . " " . $doctorInfo['LastName'] . 
                          " on " . $dateObj->format('F d, Y') . " has been submitted and is pending approval.";
    
    $notifQuery = "INSERT INTO notifications (studentID, message, is_read, created_at) VALUES (?, ?, 0, NOW())";
    $notifStmt = $conn->prepare($notifQuery);
    $notifStmt->bind_param("ss", $studentID, $notificationMessage);
    $notifStmt->execute();
    $notifStmt->close();

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true, 
        'message' => "Appointment successfully booked with Dr. " . $doctorInfo['FirstName'] . " " . $doctorInfo['LastName'] . 
                    " on " . $dateObj->format('F d, Y') . " from " . date('g:i A', strtotime($startTime)) . 
                    " to " . date('g:i A', strtotime($endTime)) . ". Your appointment is pending approval.",
        'appointmentID' => $appointmentID
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Error in submit_appointment.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred. Please try again.']);
}

$conn->autocommit(true);
$conn->close();
?>