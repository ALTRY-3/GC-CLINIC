-- Create doctors table if it doesn't exist
CREATE TABLE IF NOT EXISTS doctors (
    DoctorID VARCHAR(20) PRIMARY KEY,
    FirstName VARCHAR(50) NOT NULL,
    LastName VARCHAR(50) NOT NULL,
    Specialization VARCHAR(100) NOT NULL,
    Email VARCHAR(100),
    ContactNumber VARCHAR(20),
    Status ENUM('Active', 'Inactive') DEFAULT 'Active'
);

-- Create schedules table
CREATE TABLE IF NOT EXISTS schedules (
    ScheduleID INT AUTO_INCREMENT PRIMARY KEY,
    DoctorID VARCHAR(20),
    DayOfWeek INT NOT NULL, -- 0 = Sunday, 6 = Saturday
    ScheduleTime TIME NOT NULL,
    Status ENUM('Available', 'Unavailable') DEFAULT 'Available',
    FOREIGN KEY (DoctorID) REFERENCES doctors(DoctorID) ON DELETE CASCADE
);

-- Create appointments table
CREATE TABLE IF NOT EXISTS appointments (
    AppointmentID INT AUTO_INCREMENT PRIMARY KEY,
    StudentID VARCHAR(20),
    DoctorID VARCHAR(20),
    AppointmentDate DATE NOT NULL,
    AppointmentTime TIME NOT NULL,
    Status ENUM('Pending', 'Confirmed', 'Cancelled', 'Completed') DEFAULT 'Pending',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (StudentID) REFERENCES students(StudentID) ON DELETE CASCADE,
    FOREIGN KEY (DoctorID) REFERENCES doctors(DoctorID) ON DELETE CASCADE
);

-- Insert sample doctor data
INSERT INTO doctors (DoctorID, FirstName, LastName, Specialization, Email, ContactNumber) VALUES
('DOC001', 'John', 'Smith', 'General Medicine', 'john.smith@clinic.com', '09123456789'),
('DOC002', 'Maria', 'Garcia', 'Pediatrics', 'maria.garcia@clinic.com', '09234567890'),
('DOC003', 'Robert', 'Chen', 'Dermatology', 'robert.chen@clinic.com', '09345678901');

-- Insert sample schedule data
INSERT INTO schedules (DoctorID, DayOfWeek, ScheduleTime) VALUES
-- Monday (1)
('DOC001', 1, '09:00:00'),
('DOC001', 1, '10:00:00'),
('DOC001', 1, '11:00:00'),
('DOC002', 1, '13:00:00'),
('DOC002', 1, '14:00:00'),
('DOC002', 1, '15:00:00'),
-- Tuesday (2)
('DOC001', 2, '13:00:00'),
('DOC001', 2, '14:00:00'),
('DOC001', 2, '15:00:00'),
('DOC003', 2, '09:00:00'),
('DOC003', 2, '10:00:00'),
('DOC003', 2, '11:00:00'),
-- Wednesday (3)
('DOC002', 3, '09:00:00'),
('DOC002', 3, '10:00:00'),
('DOC002', 3, '11:00:00'),
('DOC003', 3, '13:00:00'),
('DOC003', 3, '14:00:00'),
('DOC003', 3, '15:00:00'),
-- Thursday (4)
('DOC001', 4, '09:00:00'),
('DOC001', 4, '10:00:00'),
('DOC001', 4, '11:00:00'),
('DOC002', 4, '13:00:00'),
('DOC002', 4, '14:00:00'),
('DOC002', 4, '15:00:00'),
-- Friday (5)
('DOC001', 5, '13:00:00'),
('DOC001', 5, '14:00:00'),
('DOC001', 5, '15:00:00'),
('DOC003', 5, '09:00:00'),
('DOC003', 5, '10:00:00'),
('DOC003', 5, '11:00:00'); 