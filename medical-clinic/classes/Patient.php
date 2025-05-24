<?php
class Patient {
    private $conn;
    private $table = 'patients';

    public $id;
    public $name;
    public $email;
    public $contactNumber;
    public $address;
    public $gender;
    public $profilePhoto;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " (name, email, contactNumber, address, gender, profilePhoto) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);

        $stmt->bind_param("ssssss", $this->name, $this->email, $this->contactNumber, $this->address, $this->gender, $this->profilePhoto);

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

        return $result->fetch_assoc();
    }

    public function update() {
        $query = "UPDATE " . $this->table . " SET name = ?, email = ?, contactNumber = ?, address = ?, gender = ?, profilePhoto = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);

        $stmt->bind_param("ssssssi", $this->name, $this->email, $this->contactNumber, $this->address, $this->gender, $this->profilePhoto, $this->id);

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
}
?>