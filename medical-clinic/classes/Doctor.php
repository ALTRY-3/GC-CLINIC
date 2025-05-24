<?php
class Doctor {
    private $id;
    private $name;
    private $specialization;
    private $contactNumber;
    private $email;

    public function __construct($id, $name, $specialization, $contactNumber, $email) {
        $this->id = $id;
        $this->name = $name;
        $this->specialization = $specialization;
        $this->contactNumber = $contactNumber;
        $this->email = $email;
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getSpecialization() {
        return $this->specialization;
    }

    public function getContactNumber() {
        return $this->contactNumber;
    }

    public function getEmail() {
        return $this->email;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setSpecialization($specialization) {
        $this->specialization = $specialization;
    }

    public function setContactNumber($contactNumber) {
        $this->contactNumber = $contactNumber;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function save() {
        // Logic to save doctor details to the database
    }

    public function update() {
        // Logic to update doctor details in the database
    }

    public function delete() {
        // Logic to delete doctor from the database
    }

    public static function find($id) {
        // Logic to find a doctor by ID from the database
    }

    public static function all() {
        // Logic to retrieve all doctors from the database
    }
}
?>