<?php
class Appointment {
    private $conn;
    private $table = 'appointments';

    public $id;
    public $patient_id;
    public $doctor_id;
    public $appointment_date;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " (patient_id, doctor_id, appointment_date, status) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);

        $this->appointment_date = htmlspecialchars(strip_tags($this->appointment_date));
        $this->status = htmlspecialchars(strip_tags($this->status));

        $stmt->bind_param("iiss", $this->patient_id, $this->doctor_id, $this->appointment_date, $this->status);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function read() {
        $query = "SELECT * FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        return null;
    }

    public function update() {
        $query = "UPDATE " . $this->table . " SET patient_id = ?, doctor_id = ?, appointment_date = ?, status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);

        $this->appointment_date = htmlspecialchars(strip_tags($this->appointment_date));
        $this->status = htmlspecialchars(strip_tags($this->status));

        $stmt->bind_param("iisii", $this->patient_id, $this->doctor_id, $this->appointment_date, $this->status, $this->id);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->id);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function getAllAppointments() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY appointment_date DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->get_result();
    }
}
?>