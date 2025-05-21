-- Create doctors table
CREATE TABLE IF NOT EXISTS doctors (
    DoctorID INT AUTO_INCREMENT PRIMARY KEY,
    FirstName VARCHAR(50) NOT NULL,
    LastName VARCHAR(50) NOT NULL
);

-- Add some sample doctors
INSERT INTO doctors (FirstName, LastName) VALUES 
('John', 'Smith'),
('Sarah', 'Johnson'),
('Michael', 'Williams'),
('Emily', 'Brown'); 