<?php
include 'config.php';

// Add timeslot
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['doctorID'], $_POST['AvailableDay'], $_POST['StartTime'], $_POST['EndTime'])) {
    $doctorID = $_POST['doctorID'];
    $day = $_POST['AvailableDay'];
    $start = $_POST['StartTime'];
    $end = $_POST['EndTime'];
    $stmt = $conn->prepare("INSERT INTO timeslots (DoctorID, AvailableDay, StartTime, EndTime, IsAvailable) VALUES (?, ?, ?, ?, 1)");
    $stmt->bind_param("ssss", $doctorID, $day, $start, $end);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit;
}

// Delete timeslot
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'], $_POST['SlotID'])) {
    $slotID = $_POST['SlotID'];
    $stmt = $conn->prepare("DELETE FROM timeslots WHERE SlotID = ?");
    $stmt->bind_param("i", $slotID);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit;
}

// List timeslots
if (isset($_GET['doctorID'])) {
    $doctorID = $_GET['doctorID'];
    $stmt = $conn->prepare("SELECT * FROM timeslots WHERE DoctorID = ? ORDER BY FIELD(AvailableDay, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), StartTime");
    $stmt->bind_param("s", $doctorID);
    $stmt->execute();
    $result = $stmt->get_result();
    $timeslots = [];
    while ($row = $result->fetch_assoc()) {
        $timeslots[] = $row;
    }
    echo json_encode(['timeslots' => $timeslots]);
    exit;
}
?>
