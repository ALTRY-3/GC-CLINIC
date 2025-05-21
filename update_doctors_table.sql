-- First, drop the foreign key constraints
ALTER TABLE appointments
DROP FOREIGN KEY appointments_ibfk_2;

ALTER TABLE timeslots
DROP FOREIGN KEY timeslots_ibfk_1;

-- Then modify the columns
ALTER TABLE doctors MODIFY DoctorID VARCHAR(15) NOT NULL;
ALTER TABLE appointments MODIFY DoctorID VARCHAR(15);
ALTER TABLE timeslots MODIFY DoctorID VARCHAR(15);

-- Finally, add the foreign key constraints back
ALTER TABLE appointments
ADD CONSTRAINT fk_appointments_doctor
FOREIGN KEY (DoctorID) REFERENCES doctors(DoctorID);

ALTER TABLE timeslots
ADD CONSTRAINT fk_timeslots_doctor
FOREIGN KEY (DoctorID) REFERENCES doctors(DoctorID); 