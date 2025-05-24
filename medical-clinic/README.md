# Medical Clinic Project

## Overview
This project is a web-based application for managing a medical clinic. It provides functionalities for patients, doctors, and administrators to interact with the system efficiently.

## Project Structure
The project is organized into several directories, each serving a specific purpose:

- **config/**: Contains configuration files for database connections and application settings.
- **public/**: The entry point of the application, including stylesheets, JavaScript files, and images.
- **includes/**: Contains reusable components such as headers, footers, and sidebars.
- **auth/**: Handles user authentication functionalities like login, logout, and registration.
- **admin/**: Contains files for admin functionalities, including managing doctors, patients, and appointments.
- **patient/**: Contains files for patient functionalities, including dashboards and medical history.
- **doctor/**: Contains files for doctor functionalities, including patient management and consultations.
- **classes/**: Contains class definitions for handling various functionalities related to users, patients, doctors, and appointments.
- **uploads/**: Directories for storing uploaded profile images and documents.
- **sql/**: Contains SQL scripts for database setup.

## Setup Instructions
1. **Clone the repository**:
   ```
   git clone <repository-url>
   cd medical-clinic
   ```

2. **Configure the database**:
   - Update the database connection settings in `config/database.php` with your database credentials.

3. **Import the database schema**:
   - Execute the SQL script located in `sql/database.sql` to set up the database structure.

4. **Run the application**:
   - Use a local server environment (like XAMPP or WAMP) to serve the `public/index.php` file.

## Usage
- **Admin Users**: Access the admin dashboard to manage doctors, patients, and appointments.
- **Patients**: Log in to view and manage your profile, appointments, and medical history.
- **Doctors**: Log in to manage your patients and consultations.

## Contributing
Contributions are welcome! Please submit a pull request or open an issue for any suggestions or improvements.

## License
This project is licensed under the MIT License. See the LICENSE file for more details.