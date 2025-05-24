-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 24, 2025 at 07:18 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `medicalclinicnotify`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `adminID` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `adminEmail` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `adminName` varchar(100) NOT NULL,
  `adminLastName` varchar(100) NOT NULL,
  `adminMiddleInitial` char(1) DEFAULT NULL,
  `id_verified` tinyint(1) DEFAULT 0,
  `contactNumber` varchar(15) NOT NULL,
  `profilePhoto` varchar(255) DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `ocr_result` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`adminID`, `password`, `adminEmail`, `created_at`, `adminName`, `adminLastName`, `adminMiddleInitial`, `id_verified`, `contactNumber`, `profilePhoto`, `reset_token`, `reset_expires`, `ocr_result`) VALUES
('15', '$2y$10$lDvkMlyXDOqZcYoHNLlgOuviCAJ23W4H66JaKnKc6Ee/ysnIGhFNG', 'WAYNE@GMAUIL.COM', '2024-12-11 05:49:55', 'WAYNE', 'GOMEZ', 'W', 1, '0928373764', NULL, NULL, NULL, NULL),
('18', '$2y$10$d6.3aPdVxzkaxt2kFQhYrOzOSHv24RY7p9R4NnICDOAxdLq7SDDYW', 'uni@gmail.com', '2025-03-03 12:04:31', 'unies', 'garnerd', 'R', -1, '09346262728', NULL, NULL, NULL, NULL),
('19', '$2y$10$mwFBCntDR6ZEcUMvWqVGuupDycgxLjfNI1zCHwI.J.NXBficfr642', 'jerabi2677@jazipo.com', '2025-04-01 09:31:58', 'ani', 'niw', 'w', -1, '0987654321', NULL, '606c7436b0ebf7e404e0f39a5af75f95602d8281112f808ccbc01adc2fc5b87d', '2025-05-09 13:55:17', NULL),
('202511173', '$2y$10$fRf/c7VDAJdVtmF4Cf2Ohuk6wT5rv2ww6ulzi3eqdyYX7LNISEb9e', 'az@gmail.com', '2025-04-06 12:49:10', 'az', 'garica', 'v', 1, '09501027871', NULL, NULL, NULL, NULL),
('202511184', '$2y$10$0AXWS9ONC1f1jehRKmf6fuEnuEy6EZcDdoZFo9P1lB1xfZ40q067G', 'eunicegardner26@gmail.com', '2025-05-07 10:09:09', 'Eunice', 'Gardner', 'B', 1, '09357708539', NULL, NULL, NULL, NULL),
('202511185', '$2y$10$4A1KZ45FJ2PcH265iIJySeAqULvbcL9Y8it5yfPavlDV9u.kmLE06', 'nebona9006@inkight.com', '2025-05-07 16:58:00', 'Jhonny', 'Maestro', 'A', 0, '090989723', NULL, '754d4b0c766cebe544603a6f20902447b322499940527e8063281b8765cd81fd', '2025-05-09 13:52:38', NULL),
('202511189', '$2y$10$aoTQwVb6/L4/R0RvLWYltOQQBAOaooVTlRIxVwWAVibN4P09WxD2m', 'ssuiaz2nsx@knmcadibav.com', '2025-05-09 11:40:32', 'Pra', 'Med', 'A', 0, '09098048238', NULL, NULL, NULL, NULL),
('202511190', '$2y$10$tOnyjC2/wmMEagJJjE9oB.IYHTIOpNDKiQe94817osKUh091HCbH.', '4vownge0dq@qacmjeq.com', '2025-05-09 12:01:26', 'Sha', 'Ky', 'S', 0, '09098048238', NULL, NULL, NULL, NULL),
('202511191', '$2y$10$kQXl6.ooXsaHdzgd9K9ocuC0wrp9evMhInsoJ7bbhVOc/bhp18I6i', 'pocoh66427@inkight.com', '2025-05-09 16:31:29', 'Man', 'Girl', 'A', 0, '09098023132', NULL, NULL, NULL, NULL),
('202511192', '$2y$10$7X3sZ91aZM6EUTI7LDaNp.ZYzxDJbnDUUoaLyPh3IOpK1x2xsn3ry', 'pepeli6426@inkight.com', '2025-05-09 19:19:05', 'Pat', 'Ouer', 'A', 0, '0909802313', NULL, NULL, NULL, NULL),
('202511193', '$2y$10$wUu2IjdKZ7HOg0jZqE4acOXCXVZ/Dm50QSKMCh4yvb1oTygRHv.xu', 'mehibiy245@jazipo.com', '2025-05-09 19:26:46', 'Hui', 'Lklas', 'A', 0, '09098025621', NULL, NULL, NULL, NULL),
('654321', '$2y$10$QIDCoFiuvitKnWyMJuPZjuCGvDwqI9DOBsOsOC.S1l4xJ15AlS4vu', 'CHANTAL@GMAIL.COM', '2025-04-01 09:37:24', 'Chantal', 'alvarez', 'R', -1, '091435252', NULL, NULL, NULL, NULL),
('ADM-2025-0001', '$2y$10$gIGZ8CTZbCBuZ6Vd2cDbIOQBfEJdZrkIUDgP4N7FHtc51KDAhTG8e', 'medicalclinicnotify@gmail.com', '2025-05-12 14:35:56', 'Juan', 'Dela Cruz', 'M', 1, '09098048238', NULL, NULL, NULL, 'REPUBLIKA NG PILIPINAS\nRepublic of the Philippines\n\nPANIBANSANG PAGKAKAKILAN N\n\nPhilippine Identification ie\n\n ApelyidoLast Me ae \n DELA CRUZ\n\nMga PangalanGiven Names\n\n JUAN\n\nGitnang ApelyidoMiddle Name\nMARTINEZ\n\nYetsa ng KapanganakanDate of Birth \n\na ge 1990\n\nTirahanAddress y\n833 SISA ST BRGY 526 ZONE4 52 SAMPALOK MANILA\nCITY METRO MANILA\n\nJannd');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `AppointmentID` int(11) NOT NULL,
  `StudentID` int(11) DEFAULT NULL,
  `DoctorID` varchar(15) DEFAULT NULL,
  `SlotID` int(11) DEFAULT NULL,
  `AppointmentDate` date DEFAULT NULL,
  `Reason` varchar(50) DEFAULT NULL,
  `statusID` int(11) DEFAULT NULL,
  `TestResultFile` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`AppointmentID`, `StudentID`, `DoctorID`, `SlotID`, `AppointmentDate`, `Reason`, `statusID`, `TestResultFile`, `notes`) VALUES
(101, 0, 'DOC-2025-0003', 33354, '2025-05-19', 'naeat ako', 3, 'uploads/results/68220251dbe27.png', NULL),
(102, 0, 'DOC-2025-0003', 33355, '2025-05-12', 'email', 2, NULL, NULL),
(103, 0, 'DOC-2025-0004', 33356, '2025-05-14', 'ebaks', 4, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `blocked_dates`
--

CREATE TABLE `blocked_dates` (
  `BlockID` int(11) NOT NULL,
  `DoctorID` int(11) NOT NULL,
  `BlockedDate` date NOT NULL,
  `Reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `DoctorID` varchar(15) NOT NULL,
  `FirstName` varchar(100) DEFAULT NULL,
  `LastName` varchar(100) DEFAULT NULL,
  `ContactNumber` varchar(15) DEFAULT NULL,
  `Email` varchar(255) DEFAULT NULL,
  `ImageFile` varchar(255) DEFAULT NULL,
  `Specialization` varchar(100) DEFAULT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `Status` varchar(20) DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`DoctorID`, `FirstName`, `LastName`, `ContactNumber`, `Email`, `ImageFile`, `Specialization`, `Phone`, `Status`) VALUES
('10', 'Stephen', 'Strange', '09616616616', 'stevenstrange@hotmail.com', 'images/pic-5.png', NULL, NULL, 'Active'),
('11', 'Kim', 'Lee', '09123765412', 'leekim@gmail.com', 'images/pic-6.jpg', NULL, NULL, 'Active'),
('5', 'John', 'Doe', '09098080808', 'johndoe@gmail.com', 'images/pic-1.jpg', 'Doctor', NULL, 'Active'),
('7', 'Will', 'Smith', '09771234567', 'willsmith@yahoo.com', 'images/pic-3.png', NULL, NULL, 'Active'),
('8', 'Jane', 'Willer', '09876543212', 'janewiller@yahoo.com', 'images/pic-2.jpg', NULL, NULL, 'Active'),
('9', 'William', 'Andrews', '091231231234', 'willandrew@yahoo.com', 'images/pic-4.jfif', 'Neuro', NULL, 'Inactive'),
('DOC-2025-0001', 'Naruto', 'Sasuke', '09096776677', 'naruto@gmail.com', NULL, 'Cardiologist', NULL, 'Active'),
('DOC-2025-0002', 'Naruto', 'Sasuke', '09096776677', 'naruto@gmail.com', NULL, 'Cardiologist', NULL, 'Active'),
('DOC-2025-0003', 'Naruto', 'Sasuke', '09096776677', 'naruto@gmail.com', NULL, 'Cardiologist', NULL, 'Active'),
('DOC-2025-0004', 'Naruto', 'Sasuke', '09096776677', 'naruto@gmail.com', NULL, 'Cardiologist', NULL, 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notificationID` int(11) NOT NULL,
  `studentID` int(11) NOT NULL,
  `appointmentID` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_admin_notification` tinyint(1) DEFAULT 0,
  `cancellation_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notificationID`, `studentID`, `appointmentID`, `message`, `is_read`, `created_at`, `is_admin_notification`, `cancellation_reason`) VALUES
(149, 0, 101, 'Your appointment with Dr. Dea on May 19, 2025 at 12:00 AM - 2:00 PM has been approved.', 1, '2025-05-12 14:04:37', 0, NULL),
(150, 0, 101, 'Your appointment with Dr. Dea on May 19, 2025 at 12:00 AM - 2:00 PM has been approved.', 1, '2025-05-12 14:05:26', 0, NULL),
(151, 0, 101, 'Cancellation requested for appointment with Dr. Noer on May 19, 2025', 1, '2025-05-12 14:08:44', 0, 'di na ko natate'),
(152, 0, 101, 'Your cancellation request for the appointment with Dr. Dea on May 19, 2025 has been approved.', 1, '2025-05-12 14:09:24', 0, NULL),
(153, 0, 101, 'Your appointment with Dr. Dea on May 19, 2025 at 12:00 AM - 2:00 PM has been approved.', 0, '2025-05-12 14:10:28', 0, NULL),
(154, 0, 102, 'Your appointment with Dr. Dea on May 12, 2025 at 12:32 AM - 1:31 PM has been approved.', 0, '2025-05-12 14:11:28', 0, NULL),
(155, 0, 102, 'Your appointment with Dr. Dea on May 12, 2025 at 12:32 AM - 1:31 PM has been cancelled.', 0, '2025-05-12 14:13:17', 0, NULL),
(156, 0, 101, 'Your appointment with Dr. Dea on May 19, 2025 at 12:00 AM - 2:00 PM has been marked as completed.', 0, '2025-05-12 14:14:45', 0, NULL),
(157, 0, 102, 'Your appointment with Dr. Dea on May 12, 2025 at 12:32 AM - 1:31 PM has been approved.', 0, '2025-05-12 14:16:28', 0, NULL),
(158, 0, 103, 'Your appointment with Dr. Sasuke on May 14, 2025 at 12:34 AM - 5:00 AM has been approved.', 0, '2025-05-12 14:25:06', 0, NULL),
(159, 0, 103, 'Your appointment with Dr. Sasuke on May 14, 2025 at 12:34 AM - 5:00 AM has been cancelled.', 0, '2025-05-12 14:26:53', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `otp_verification`
--

CREATE TABLE `otp_verification` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `otp_expiry` datetime NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `otp_verification`
--

INSERT INTO `otp_verification` (`id`, `email`, `otp`, `otp_expiry`, `created_at`) VALUES
(14, 'pocoh66427@inkight.com', '387378', '2025-05-10 02:59:34', '2025-05-10 02:49:34'),
(15, 'pocoh66427@inkight.com', '341145', '2025-05-10 03:02:08', '2025-05-10 02:52:08'),
(16, 'pocoh66427@inkight.com', '868405', '2025-05-10 03:03:40', '2025-05-10 02:53:40'),
(19, 'niyeloy702@inkight.com', '350722', '2025-05-10 19:00:47', '2025-05-10 18:50:47'),
(20, 'niyeloy702@inkight.com', '308058', '2025-05-10 19:28:23', '2025-05-10 19:18:23'),
(21, 'niyeloy702@inkight.com', '808352', '2025-05-10 19:31:18', '2025-05-10 19:21:18'),
(22, 'niyeloy702@inkight.com', '110802', '2025-05-10 19:35:40', '2025-05-10 19:25:40'),
(23, 'xehatif148@jazipo.com', '999494', '2025-05-10 19:46:53', '2025-05-10 19:36:53'),
(24, 'xehatif148@jazipo.com', '528384', '2025-05-10 19:50:49', '2025-05-10 19:40:49'),
(27, 'xehatif148@jazipo.com', '682551', '2025-05-10 20:09:09', '2025-05-10 19:59:09');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `staff_name` varchar(100) NOT NULL,
  `role` varchar(100) NOT NULL,
  `availability` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_appointments`
--

CREATE TABLE `staff_appointments` (
  `StaffAppointmentID` int(11) NOT NULL,
  `AppointmentID` int(11) NOT NULL,
  `StaffID` int(11) NOT NULL,
  `StaffRole` varchar(100) NOT NULL,
  `AppointmentDate` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `status`
--

CREATE TABLE `status` (
  `statusID` int(11) NOT NULL,
  `status_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `status`
--

INSERT INTO `status` (`statusID`, `status_name`) VALUES
(1, 'Pending'),
(2, 'Approved'),
(3, 'Completed'),
(4, 'Cancelled'),
(5, 'Cancellation Requested');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `studentID` bigint(20) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `parentGuardian` varchar(100) DEFAULT NULL,
  `contactNumber` varchar(20) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `dateOfBirth` date DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `lastLogin` datetime DEFAULT NULL,
  `lastUpdated` datetime DEFAULT NULL,
  `otpCode` varchar(10) DEFAULT NULL,
  `otpExpiry` datetime DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `yearLevel` varchar(20) DEFAULT NULL,
  `year` varchar(10) DEFAULT NULL,
  `profilePhoto` varchar(255) DEFAULT NULL,
  `firstName` varchar(100) DEFAULT NULL,
  `lastName` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`studentID`, `name`, `address`, `parentGuardian`, `contactNumber`, `course`, `dateOfBirth`, `email`, `gender`, `lastLogin`, `lastUpdated`, `otpCode`, `otpExpiry`, `password`, `yearLevel`, `year`, `profilePhoto`, `firstName`, `lastName`) VALUES
(202310704, 'Aljhun A. Abanes', '#15 Nieves st., Mabayuan Olongapo City', 'Mae A. Abanes', '09123456789', 'Bachelor of Science in Information Technology', '2005-08-11', '202310704@gordoncollege.edu.ph', 'Male', '2025-05-19 22:42:52', '0000-00-00 00:00:00', '490461', '2025-05-15 22:59:27', 'Abanes2023', '2nd Year', NULL, NULL, 'Aljhun', 'A. Abanes'),
(202311173, 'Eunice Gardner', '#15 Nieves st., Mabayuan Olongapo City', 'Zuey Z. Gardner', '09123456789', 'Bachelor of Science in Information Technology', '2005-08-11', '202311173@gordoncollege.edu.ph', 'Female', '2025-05-25 00:47:57', '2025-05-15 22:41:58', '490461', '2025-05-15 22:59:27', 'Gardner2023', '2nd Year', NULL, NULL, 'Eunice', 'Gardner');

-- --------------------------------------------------------

--
-- Table structure for table `test_results`
--

CREATE TABLE `test_results` (
  `ResultID` int(11) NOT NULL,
  `AppointmentID` int(11) NOT NULL,
  `FilePath` varchar(255) NOT NULL,
  `FileName` varchar(255) NOT NULL,
  `UploadDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `test_results`
--

INSERT INTO `test_results` (`ResultID`, `AppointmentID`, `FilePath`, `FileName`, `UploadDate`) VALUES
(12, 101, 'uploads/results/68220251dbe27.png', 'PhilID-specimen-Front_highres1-1024x576 (1).png', '2025-05-12 14:14:41');

-- --------------------------------------------------------

--
-- Table structure for table `timeslots`
--

CREATE TABLE `timeslots` (
  `SlotID` int(11) NOT NULL,
  `DoctorID` varchar(15) DEFAULT NULL,
  `AvailableDay` varchar(10) DEFAULT NULL,
  `StartTime` time DEFAULT NULL,
  `EndTime` time DEFAULT NULL,
  `IsAvailable` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timeslots`
--

INSERT INTO `timeslots` (`SlotID`, `DoctorID`, `AvailableDay`, `StartTime`, `EndTime`, `IsAvailable`) VALUES
(1, '5', 'Monday', '08:00:00', '10:00:00', 0),
(2, '5', 'Monday', '10:00:00', '12:00:00', 0),
(3, '5', 'Tuesday', '10:00:00', '12:00:00', 0),
(4, '5', 'Tuesday', '08:00:00', '10:00:00', 0),
(5, '5', 'Friday', '13:00:00', '15:00:00', 0),
(6, '5', 'Friday', '15:00:00', '17:00:00', 0),
(9, '7', 'Tuesday', '07:00:00', '09:00:00', 0),
(10, '7', 'Tuesday', '09:00:00', '11:00:00', 0),
(11, '7', 'Thursday', '08:00:00', '10:00:00', 0),
(12, '7', 'Friday', '08:00:00', '10:00:00', 0),
(13, '8', 'Wednesday', '10:00:00', '12:00:00', 0),
(14, '8', 'Wednesday', '13:00:00', '15:00:00', 0),
(15, '8', 'Thursday', '07:00:00', '09:00:00', 0),
(16, '8', 'Thursday', '10:00:00', '12:00:00', 0),
(17, '8', 'Friday', '13:00:00', '15:00:00', 0),
(18, '8', 'Friday', '15:00:00', '17:00:00', 0),
(21, '9', 'Tuesday', '13:00:00', '15:00:00', 0),
(22, '9', 'Tuesday', '15:00:00', '17:00:00', 0),
(23, '9', 'Wednesday', '07:00:00', '09:00:00', 0),
(24, '9', 'Wednesday', '10:00:00', '12:00:00', 0),
(25, '9', 'Friday', '07:00:00', '09:00:00', 0),
(26, '10', 'Monday', '08:00:00', '10:00:00', 0),
(27, '10', 'Tuesday', '15:00:00', '17:00:00', 0),
(28, '10', 'Wednesday', '07:00:00', '09:00:00', 0),
(29, '10', 'Thursday', '07:00:00', '09:00:00', 0),
(30, '10', 'Friday', '08:00:00', '10:00:00', 0),
(31, '10', 'Friday', '10:00:00', '12:00:00', 0),
(32, '11', 'Monday', '10:00:00', '12:00:00', 0),
(33, '11', 'Tuesday', '13:00:00', '15:00:00', 0),
(34, '11', 'Wednesday', '08:00:00', '10:00:00', 0),
(35, '11', 'Thursday', '13:00:00', '15:00:00', 0),
(36, '11', 'Thursday', '15:00:00', '17:00:00', 0),
(37, '11', 'Friday', '08:00:00', '10:00:00', 0),
(38, '8', 'Wednesday', '15:00:00', '13:00:00', 0),
(39, '10', 'Wednesday', '08:00:00', '10:00:00', 0),
(40, '5', 'Thursday', '09:00:00', '11:00:00', 0),
(41, '9', 'Thursday', '13:00:00', '15:00:00', 0),
(33333, 'DOC-2025-0001', 'Monday', '00:21:00', '13:21:00', 0),
(33334, 'DOC-2025-0001', 'Tuesday', '02:21:00', '13:12:00', 0),
(33335, 'DOC-2025-0001', 'Thursday', '14:00:00', '17:00:00', 0),
(33336, 'DOC-2025-0001', 'Sunday', '10:00:00', '17:00:00', 0),
(33337, 'DOC-2025-0001', 'Monday', '12:23:00', '13:13:00', 0),
(33338, '9', 'Monday', '00:23:00', '12:31:00', 0),
(33340, 'DOC-2025-0002', 'Thursday', '00:23:00', '12:31:00', 0),
(33342, 'DOC-2025-0002', 'Wednesday', '12:31:00', '13:21:00', 0),
(33346, 'DOC-2025-0002', 'Monday', '12:31:00', '01:13:00', 0),
(33349, 'DOC-2025-0001', 'Sunday', '13:12:00', '00:33:00', 0),
(33350, 'DOC-2025-0002', 'Wednesday', '12:33:00', '13:13:00', 0),
(33354, 'DOC-2025-0003', 'Monday', '00:00:00', '14:00:00', 0),
(33355, 'DOC-2025-0003', 'Monday', '00:32:00', '13:31:00', 0),
(33356, 'DOC-2025-0004', 'Wednesday', '00:34:00', '05:00:00', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`adminID`),
  ADD UNIQUE KEY `email` (`adminEmail`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`AppointmentID`),
  ADD KEY `StudentID` (`StudentID`),
  ADD KEY `DoctorID` (`DoctorID`),
  ADD KEY `SlotID` (`SlotID`),
  ADD KEY `statusID` (`statusID`);

--
-- Indexes for table `blocked_dates`
--
ALTER TABLE `blocked_dates`
  ADD PRIMARY KEY (`BlockID`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`DoctorID`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notificationID`),
  ADD KEY `studentID` (`studentID`),
  ADD KEY `appointmentID` (`appointmentID`);

--
-- Indexes for table `otp_verification`
--
ALTER TABLE `otp_verification`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`);

--
-- Indexes for table `staff_appointments`
--
ALTER TABLE `staff_appointments`
  ADD PRIMARY KEY (`StaffAppointmentID`),
  ADD KEY `AppointmentID` (`AppointmentID`),
  ADD KEY `StaffID` (`StaffID`);

--
-- Indexes for table `status`
--
ALTER TABLE `status`
  ADD PRIMARY KEY (`statusID`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`studentID`);

--
-- Indexes for table `test_results`
--
ALTER TABLE `test_results`
  ADD PRIMARY KEY (`ResultID`),
  ADD KEY `AppointmentID` (`AppointmentID`);

--
-- Indexes for table `timeslots`
--
ALTER TABLE `timeslots`
  ADD PRIMARY KEY (`SlotID`),
  ADD KEY `DoctorID` (`DoctorID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `AppointmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `blocked_dates`
--
ALTER TABLE `blocked_dates`
  MODIFY `BlockID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notificationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=160;

--
-- AUTO_INCREMENT for table `otp_verification`
--
ALTER TABLE `otp_verification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_appointments`
--
ALTER TABLE `staff_appointments`
  MODIFY `StaffAppointmentID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `status`
--
ALTER TABLE `status`
  MODIFY `statusID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `test_results`
--
ALTER TABLE `test_results`
  MODIFY `ResultID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `timeslots`
--
ALTER TABLE `timeslots`
  MODIFY `SlotID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33357;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`SlotID`) REFERENCES `timeslots` (`SlotID`),
  ADD CONSTRAINT `appointments_ibfk_4` FOREIGN KEY (`statusID`) REFERENCES `status` (`statusID`),
  ADD CONSTRAINT `fk_appointments_doctor` FOREIGN KEY (`DoctorID`) REFERENCES `doctors` (`DoctorID`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`appointmentID`) REFERENCES `appointments` (`AppointmentID`);

--
-- Constraints for table `staff_appointments`
--
ALTER TABLE `staff_appointments`
  ADD CONSTRAINT `staff_appointments_ibfk_1` FOREIGN KEY (`AppointmentID`) REFERENCES `appointments` (`AppointmentID`),
  ADD CONSTRAINT `staff_appointments_ibfk_2` FOREIGN KEY (`StaffID`) REFERENCES `staff` (`staff_id`);

--
-- Constraints for table `test_results`
--
ALTER TABLE `test_results`
  ADD CONSTRAINT `test_results_ibfk_1` FOREIGN KEY (`AppointmentID`) REFERENCES `appointments` (`AppointmentID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `timeslots`
--
ALTER TABLE `timeslots`
  ADD CONSTRAINT `fk_timeslots_doctor` FOREIGN KEY (`DoctorID`) REFERENCES `doctors` (`DoctorID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
