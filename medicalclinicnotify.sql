-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 07, 2025 at 12:12 PM
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
  `adminID` int(11) NOT NULL,
  `password` varchar(255) NOT NULL,
  `adminEmail` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `adminName` varchar(100) NOT NULL,
  `adminLastName` varchar(100) NOT NULL,
  `adminMiddleInitial` char(1) DEFAULT NULL,
  `id_verified` tinyint(1) DEFAULT 0,
  `contactNumber` varchar(15) NOT NULL,
  `profilePhoto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`adminID`, `password`, `adminEmail`, `created_at`, `adminName`, `adminLastName`, `adminMiddleInitial`, `id_verified`, `contactNumber`, `profilePhoto`) VALUES
(15, '$2y$10$lDvkMlyXDOqZcYoHNLlgOuviCAJ23W4H66JaKnKc6Ee/ysnIGhFNG', 'WAYNE@GMAUIL.COM', '2024-12-11 05:49:55', 'WAYNE', 'GOMEZ', 'W', 1, '0928373764', NULL),
(18, '$2y$10$d6.3aPdVxzkaxt2kFQhYrOzOSHv24RY7p9R4NnICDOAxdLq7SDDYW', 'uni@gmail.com', '2025-03-03 12:04:31', 'unies', 'garnerd', 'R', -1, '09346262728', NULL),
(19, '$2y$10$mwFBCntDR6ZEcUMvWqVGuupDycgxLjfNI1zCHwI.J.NXBficfr642', 'ani@gmail.com', '2025-04-01 09:31:58', 'ani', 'niw', 'w', -1, '0987654321', NULL),
(654321, '$2y$10$QIDCoFiuvitKnWyMJuPZjuCGvDwqI9DOBsOsOC.S1l4xJ15AlS4vu', 'CHANTAL@GMAIL.COM', '2025-04-01 09:37:24', 'Chantal', 'alvarez', 'R', -1, '091435252', NULL),
(202511173, '$2y$10$fRf/c7VDAJdVtmF4Cf2Ohuk6wT5rv2ww6ulzi3eqdyYX7LNISEb9e', 'az@gmail.com', '2025-04-06 12:49:10', 'az', 'garica', 'v', 1, '09501027871', NULL),
(202511184, '$2y$10$0AXWS9ONC1f1jehRKmf6fuEnuEy6EZcDdoZFo9P1lB1xfZ40q067G', 'eunicegardner26@gmail.com', '2025-05-07 10:09:09', 'Eunice', 'Gardner', 'B', 1, '09357708539', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `AppointmentID` int(11) NOT NULL,
  `StudentID` int(11) DEFAULT NULL,
  `DoctorID` int(11) DEFAULT NULL,
  `SlotID` int(11) DEFAULT NULL,
  `AppointmentDate` date DEFAULT NULL,
  `Reason` varchar(50) DEFAULT NULL,
  `statusID` int(11) DEFAULT NULL,
  `TestResultFile` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`AppointmentID`, `StudentID`, `DoctorID`, `SlotID`, `AppointmentDate`, `Reason`, `statusID`, `TestResultFile`) VALUES
(44, 202366611, 10, 28, '2024-12-12', 'Examination', 1, 'uploads/results/680740e32354f.pdf'),
(46, 202322211, 5, 5, '2024-12-14', 'CheckUp', 3, NULL),
(47, 202322211, 7, 12, '2024-12-14', 'Certificate', 2, NULL),
(48, 202366611, 8, 38, '2024-12-19', 'Examination', 1, NULL),
(52, 202311143, 5, 6, '2024-12-21', 'CheckUp', 3, NULL),
(53, 202311143, 8, 17, '2024-12-21', 'Certificate', 2, NULL),
(54, 202366611, 9, 23, '2025-03-06', 'CheckUp', 2, NULL),
(56, 10101010, 7, 11, '2025-04-11', 'CheckUp', 1, 'uploads/results/6807421b3ee39.pdf'),
(57, 10101010, 9, 20, '2025-04-15', 'CheckUp', 3, 'uploads/results/6807412022d19.pdf'),
(58, 654321, 5, 3, '2025-04-23', 'CheckUp', 3, NULL),
(59, 654321, 8, 15, '2025-04-25', 'Examination', 2, NULL),
(60, 654321, 8, 18, '2025-05-03', 'Certificate', 2, NULL),
(61, 654321, 9, 25, '2025-05-03', 'Examination', 2, NULL),
(62, 654321, 10, 31, '2025-05-03', 'Examination', 2, NULL),
(63, 654321, 11, 37, '2025-05-03', 'CheckUp', 3, NULL),
(64, 654321, 10, 26, '2025-05-06', 'CheckUp', 3, 'uploads/results/681740a400dc0.pdf'),
(65, 78899990, 11, 32, '2025-05-06', 'Examination', 1, 'uploads/results/6819c25136cfa.pdf'),
(66, 45456774, 11, 33, '2025-05-07', 'CheckUp', 3, 'uploads/results/6819c624be4f6.pdf'),
(67, 45456774, 9, 24, '2025-05-08', 'CheckUp', 2, 'uploads/results/681b316ad76f8.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `DoctorID` int(11) NOT NULL,
  `FirstName` varchar(100) DEFAULT NULL,
  `LastName` varchar(100) DEFAULT NULL,
  `ContactNumber` varchar(15) DEFAULT NULL,
  `Email` varchar(255) DEFAULT NULL,
  `ImageFile` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`DoctorID`, `FirstName`, `LastName`, `ContactNumber`, `Email`, `ImageFile`) VALUES
(5, 'John', 'Doe', '09155103906', 'johndoe@gmail.com', 'images/pic-1.jpg'),
(7, 'Will', 'Smith', '09771234567', 'willsmith@yahoo.com', 'images/pic-3.png'),
(8, 'Jane', 'Willer', '09876543212', 'janewiller@yahoo.com', 'images/pic-2.jpg'),
(9, 'William', 'Andrews', '091231231234', 'willandrew@yahoo.com', 'images/pic-4.jfif'),
(10, 'Stephen', 'Strange', '09616616616', 'stevenstrange@hotmail.com', 'images/pic-5.png'),
(11, 'Kim', 'Lee', '09123765412', 'leekim@gmail.com', 'images/pic-6.jpg');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notificationID`, `studentID`, `appointmentID`, `message`, `is_read`, `created_at`) VALUES
(1, 654321, 58, 'Your appointment scheduled for April 23, 2025 has been approved.', 1, '2025-04-22 09:08:42'),
(2, 654321, 59, 'Your appointment scheduled for April 25, 2025 has been approved.', 1, '2025-04-22 09:32:06'),
(3, 654321, 59, 'Your appointment scheduled for April 25, 2025 has been approved.', 0, '2025-04-28 01:28:48'),
(4, 10101010, 56, 'Your appointment scheduled for April 11, 2025 has been approved.', 0, '2025-05-03 08:08:08'),
(5, 654321, 60, 'Your appointment scheduled for May 3, 2025 has been approved.', 0, '2025-05-03 08:08:15'),
(6, 10101010, 56, 'Your appointment scheduled for April 11, 2025 has been approved.', 0, '2025-05-03 08:08:17'),
(7, 654321, 59, 'Your appointment scheduled for April 25, 2025 has been approved.', 0, '2025-05-03 09:34:01'),
(8, 654321, 62, 'Your appointment scheduled for May 3, 2025 has been approved.', 0, '2025-05-04 10:29:28'),
(9, 654321, 61, 'Your appointment scheduled for May 3, 2025 has been approved.', 0, '2025-05-04 10:29:34'),
(10, 78899990, 65, 'Your appointment scheduled for May 6, 2025 has been approved.', 0, '2025-05-06 08:02:52'),
(11, 78899990, 65, 'Your appointment scheduled for May 6, 2025 has been approved.', 0, '2025-05-06 08:09:48'),
(12, 78899990, 65, 'Your appointment scheduled for May 6, 2025 has been approved.', 0, '2025-05-06 08:10:16'),
(13, 45456774, 66, 'Your appointment scheduled for May 7, 2025 has been approved.', 0, '2025-05-06 08:18:50'),
(14, 45456774, 66, 'Your appointment scheduled for May 7, 2025 has been approved.', 0, '2025-05-06 08:18:50'),
(15, 45456774, 66, 'Your appointment scheduled for May 7, 2025 has been approved.', 0, '2025-05-06 08:18:50'),
(16, 45456774, 66, 'Your appointment scheduled for May 7, 2025 has been approved.', 0, '2025-05-06 08:18:50'),
(17, 45456774, 66, 'Your appointment scheduled for May 7, 2025 has been approved.', 0, '2025-05-07 07:14:38'),
(18, 45456774, 66, 'Congratulations! Your appointment with Dr. Kim Lee on May 7, 2025 has been completed. Please check for your results or follow-up instructions.', 0, '2025-05-07 07:14:52'),
(19, 45456774, 67, 'Your appointment scheduled for May 8, 2025 has been approved.', 0, '2025-05-07 10:09:34');

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
(4, 'Unavailable');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `StudentID` varchar(20) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `middleInitial` varchar(2) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `ocr_result` text DEFAULT NULL,
  `dob` date NOT NULL,
  `GENDER` enum('Male','Female') NOT NULL,
  `ContactNumber` varchar(20) NOT NULL,
  `parentGuardian` varchar(100) NOT NULL,
  `parentContact` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `idUpload` varchar(255) DEFAULT NULL,
  `id_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`StudentID`, `FirstName`, `LastName`, `middleInitial`, `Password`, `ocr_result`, `dob`, `GENDER`, `ContactNumber`, `parentGuardian`, `parentContact`, `email`, `idUpload`, `id_verified`, `created_at`, `updated_at`) VALUES
('11111', 'EUNICE', 'GARDNER', 'B', '$2y$10$rNuAmEKpetKruqSfMNexf.A13Sr4VZcVBJ.g43OTrmvePPPZ0YoyK', NULL, '2024-08-13', '', '9787686858', 'ZUEY Z ', '978978789789', '', NULL, 0, '2025-04-27 07:40:42', '2025-04-27 07:40:42'),
('1234567891011213', 'DELA CRUZ', 'JUAN', 'MA', '$2y$10$zE3iZSYhj5SAiyVvD6hgPOMc9se7gZkB3.wl7LL6wSFOA13qSGzXW', '', '1990-01-01', 'Male', '0973426171', 'mMarites', '0998276263653', '', NULL, 0, '2025-05-04 23:41:13', '2025-05-04 23:41:13'),
('1238383883838', 'DELA CRUZ', 'JUAN', 'MA', '$2y$10$l0IJd2XChkct5SC5qR6I/u5.Q.g0mSi1YU0fAikZ6AATVbm8P2Cge', '', '1990-01-01', 'Male', '0973426171', 'mMarites', '0998276263653', '', NULL, 0, '2025-05-04 23:51:09', '2025-05-04 23:51:09'),
('20200199', 'Luiz', 'Reyes', 'X', '$2y$10$wuULM3SDUPtR5pZFEy9Kge/yNwlrxJZgNcbppRfUeH/UKKnkyS9.C', NULL, '2025-02-12', '', '08743222', 'Lina Reyes', '09987655', '', NULL, 0, '2025-04-27 07:40:42', '2025-04-27 07:40:42'),
('202311143', 'Andy', 'Reyes', 'V', '$2y$10$9xH5RFnu09bk8hglnbFfOOBLzOvMU2GuiaIswxEY0scC04x9KB/9a', NULL, '2005-04-22', 'Female', '09999887', 'Lina Reyes', '09827763', '', NULL, 0, '2025-04-27 07:40:42', '2025-04-27 07:40:42'),
('202311173', 'eunice', 'gardner', 'B', '$2y$10$7W1ikop3C/BClmo6nbJ6.epBsAtEmdbAfpgfArzMWAAmhdRUZcf4i', NULL, '2024-08-15', 'Female', '09357708539', 'zuey', '0098875454', '', NULL, 0, '2025-04-27 07:40:42', '2025-04-27 07:40:42'),
('2023111745', 'Eunice', 'Gardner', 'B', '$2y$10$NLWBXtGC2iJGqc7VC029Ou1Ta4gP0ZAz9inQTKt6mjfFml1HPGsEC', '', '2005-08-11', 'Female', '09357708539', 'zueyy', '09987655', '', NULL, 0, '2025-05-04 23:30:04', '2025-05-04 23:30:04'),
('202311178', 'Eunice', 'Gardner', 'B', '$2y$10$1tK4evrKY3Y0f7SKFosysOUPpT2LyJs43SLQLvoQrbRlm7wC1nOPu', NULL, '2005-08-11', 'Female', '09827373747', 'Zoey Gardner', '09501923748', '', NULL, 0, '2025-04-27 07:40:42', '2025-04-27 07:40:42'),
('202322211', 'Diana', 'Rossy', 'S', '$2y$10$8aiE4eBcp8vR4NpxBdkfDeAioVFd2bzfAM9cckztg5YimbI.t48ca', NULL, '2007-06-11', 'Female', '000999888822', 'TITA', '09218276262', '', NULL, 0, '2025-04-27 07:40:42', '2025-04-27 07:40:42'),
('202366611', 'Red', 'Smith', 'F', '$2y$10$JILkRhC4/V1Ja/0xEjAbR.uHlrCyPvv74aFhgswDWEpLnl4JczcZ6', NULL, '2010-06-07', 'Female', '08887', 'zueyy', '202366611', '', NULL, 0, '2025-04-27 07:40:42', '2025-04-27 07:40:42'),
('202411173', 'nicole', 'esconde', 'n', '$2y$10$wQK4O58XfIFvUpgvWwn.hei.yBnwWAXnnfXauZZT38t2LWmUHlBC6', NULL, '2020-07-12', 'Female', '09887277828', 'GOJO', '099283873737', '', NULL, 0, '2025-04-27 07:40:42', '2025-04-27 07:40:42'),
('333777', 'JUAN', 'JUAN', 'MA', '$2y$10$HOxblKOPkJ75QfjzkyjyH.IX3fRqEs0lcySdpbufbiy7gVz4pflBa', '', '1990-01-01', 'Male', '973426171', 'MANG KANOR', '0999877Y', '', NULL, 0, '2025-05-05 00:31:24', '2025-05-05 00:31:24'),
('4444444444444', 'Eunice', 'Gardner', 'B', '$2y$10$Gqw6ohaXmG1UpDsBdtga8eGerFpmIzUWM6d97P1/y4SIncpjvfmha', 'REPUBLIKA NG PILIPINAS\nRepublic of the Philippines\nPAMBANSANG PAGKAKAKILANLAN\nPhilippine Identification Card:\n1234-5678-9101-1213\nApelyido/Last Name\nDELA CRUZ\nMga Pangalan/Given Names\nJUAN\nGitnang Apelyido/Middle Name\nMARTINEZ\nPetsa ng Kapanganakan/Date of Birth\nJANUARY 01, 1990\nTirahan/Address\n833 SISA ST., BRGY 526, ZONE 52 SAMPALOK, MANILA\nCITY, METRO MANILA\nPHL', '2025-05-06', 'Female', '09357708539', 'MANG KANOR', '33344334', '', NULL, 0, '2025-05-05 00:44:36', '2025-05-05 00:44:36'),
('45456774', 'JUAN', 'DELA CRUZ', 'MA', '$2y$10$FXFuNWRVncRI62G/4Jk8SuS4.J3gXgE1K535MYYUiSGSBE0Iz/IUy', 'REPUBLIKA NG PILIPINAS\nRepublic of the Philippines\nPAMBANSANG PAGKAKAKILANLAN\nPhilippine Identification Card:\n1234-5678-9101-1213\nApelyido/Last Name\nDELA CRUZ\nMga Pangalan/Given Names\nJUAN\nGitnang Apelyido/Middle Name\nMARTINEZ\nPetsa ng Kapanganakan/Date of Birth\nJANUARY 01, 1990\nTirahan/Address\n833 SISA ST., BRGY 526, ZONE 52 SAMPALOK, MANILA\nCITY, METRO MANILA\nPHL', '2025-05-07', 'Male', '973426171', 'MANG KANOR', '33344334', 'eunicegardner26@gmail.com', NULL, 1, '2025-05-06 07:25:34', '2025-05-06 07:25:34'),
('45656566', 'EUNICE', 'GARDNER', 'BA', '$2y$10$oh7ED0Rv0DTtj7jR/XyIL.T3mscI.AYX2Ml./dcRdo3MozK.RQ8aK', '', '2005-08-11', 'Female', '0973426171', 'Zoey Gardner', '33344334', '', NULL, 0, '2025-05-05 00:28:11', '2025-05-05 00:28:11'),
('654321', 'Luiz', 'Gardner', 'X', '$2y$10$CWP4lzJNa/pKoT2bXXBrnuxyhTiRwUzlEdc48.C5bEPQFdJ0cD1gW', NULL, '2025-04-02', '', '08743222', 'Lina Reyes', '09987655', '', NULL, 0, '2025-04-27 07:40:42', '2025-05-04 08:00:27'),
('666666666', 'EUNICE', 'GARDNER', 'BA', '$2y$10$HKyXW952hOQ9UR/qbwcxDumr53UHk26CS5ufYnoitmybUsk73Vm/i', '', '2005-08-11', 'Female', '0973426171', 'Zoey Gardner', '33344334', '', NULL, 0, '2025-05-04 23:56:27', '2025-05-04 23:56:27'),
('7777777777777777', 'Eunice', 'Gardner', 'B', '$2y$10$pAnmhsZMsE6e4T4/eXWWIOY2HwestBImOw/URUNjOHW1tgiyucpfu', 'REPUBLIKA NG PILIPINAS\nRepublic of the Philippines\nPAMBANSANG PAGKAKAKILANLAN\nPhilippine Identification Card:\n1234-5678-9101-1213\nApelyido/Last Name\nDELA CRUZ\nMga Pangalan/Given Names\nJUAN\nGitnang Apelyido/Middle Name\nMARTINEZ\nPetsa ng Kapanganakan/Date of Birth\nJANUARY 01, 1990\nTirahan/Address\n833 SISA ST., BRGY 526, ZONE 52 SAMPALOK, MANILA\nCITY, METRO MANILA\nPHL', '2025-05-06', 'Female', '09357708539', 'MANG KANOR', '33344334', '', NULL, 0, '2025-05-05 00:46:31', '2025-05-05 00:46:31'),
('7788', 'DELA CRUZ', 'JUAN', 'MA', '$2y$10$s.Upw49I7WOFMJJNgF9N1..loONsU7QnAEkImcGJCjU7lu3P.NThu', '', '1990-01-01', 'Male', '0973426171', 'mMarites', '0998276263653', '', NULL, 0, '2025-05-04 23:54:10', '2025-05-04 23:54:10'),
('77880004', 'Eunice', 'Gardner', 'B', '$2y$10$v2Oe3SzAM7/acFFAkPxfMOcD/qmVyZHUHcqqDUkhXDic.o3K6xiSm', 'REPUBLIKA NG PILIPINAS\nRepublic of the Philippines\nPAMBANSANG PAGKAKAKILANLAN\nPhilippine Identification Card:\n1234-5678-9101-1213\nApelyido/Last Name\nDELA CRUZ\nMga Pangalan/Given Names\nJUAN\nGitnang Apelyido/Middle Name\nMARTINEZ\nPetsa ng Kapanganakan/Date of Birth\nJANUARY 01, 1990\nTirahan/Address\n833 SISA ST., BRGY 526, ZONE 52 SAMPALOK, MANILA\nCITY, METRO MANILA\nPHL', '2025-05-06', 'Female', '09357708539', 'MANG KANOR', '33344334', '', NULL, 0, '2025-05-05 00:43:03', '2025-05-05 00:43:03'),
('7789999', 'JUAN', 'JUAN', 'MA', '$2y$10$erctmavT3QeemFjWB4Hz6uvCcCT8JtH7pWhwP/MIShPjmUtSgWdSW', 'REPUBLIKA NG PILIPINAS\nRepublic of the Philippines\nPAMBANSANG PAGKAKAKILANLAN\nPhilippine Identification Card:\n1234-5678-9101-1213\nApelyido/Last Name\nDELA CRUZ\nMga Pangalan/Given Names\nJUAN\nGitnang Apelyido/Middle Name\nMARTINEZ\nPetsa ng Kapanganakan/Date of Birth\nJANUARY 01, 1990\nTirahan/Address\n833 SISA ST., BRGY 526, ZONE 52 SAMPALOK, MANILA\nCITY, METRO MANILA\nPHL', '1990-01-01', 'Male', '973426171', 'MANG KANOR', '0999877Y', '', NULL, 1, '2025-05-05 00:33:25', '2025-05-05 00:33:25'),
('78899990', 'JUAN', 'JUAN', 'MA', '$2y$10$tMvu51XJ1GODUKibo/Q5Oe8uhYnak1E8e2AQLSHnr7Na/yu.Cbk.C', 'REPUBLIKA NG PILIPINAS\nRepublic of the Philippines\nPAMBANSANG PAGKAKAKILANLAN\nPhilippine Identification Card:\n1234-5678-9101-1213\nApelyido/Last Name\nDELA CRUZ\nMga Pangalan/Given Names\nJUAN\nGitnang Apelyido/Middle Name\nMARTINEZ\nPetsa ng Kapanganakan/Date of Birth\nJANUARY 01, 1990\nTirahan/Address\n833 SISA ST., BRGY 526, ZONE 52 SAMPALOK, MANILA\nCITY, METRO MANILA\nPHL', '1990-01-01', 'Male', '973426171', 'MANG KANOR', '0999877Y', '', NULL, 1, '2025-05-05 00:38:29', '2025-05-05 00:38:29'),
('88888888888888888888', 'Eunice', 'GARDNER', 'BA', '$2y$10$32Kh2d5oD/ZRcvh1B5k7u.z8V/A4BxknmY1JR7TEEZJKTgfxdRUSy', 'REPUBLIKA NG PILIPINAS\nRepublic of the Philippines\nPAMBANSANG PAGKAKAKILANLAN\nPhilippine Identification Card:\n1234-5678-9101-1213\nApelyido/Last Name\nDELA CRUZ\nMga Pangalan/Given Names\nJUAN\nGitnang Apelyido/Middle Name\nMARTINEZ\nPetsa ng Kapanganakan/Date of Birth\nJANUARY 01, 1990\nTirahan/Address\n833 SISA ST., BRGY 526, ZONE 52 SAMPALOK, MANILA\nCITY, METRO MANILA\nPHL', '2025-05-07', 'Female', '973426171', 'MANG KANOR', '33344334', 'eunicegardner26@gmail.com', NULL, 0, '2025-05-07 10:05:57', '2025-05-07 10:05:57'),
('88888888888899999999', 'JUAN', 'DELA CRUZ', 'MA', '$2y$10$a.nZfxaJNXzeV5fleL1EvOQaPhExwKqeFkoEs6rU2fh8VEILwdj.2', 'REPUBLIKA NG PILIPINAS\nRepublic of the Philippines\nPAMBANSANG PAGKAKAKILANLAN\nPhilippine Identification Card:\n1234-5678-9101-1213\nApelyido/Last Name\nDELA CRUZ\nMga Pangalan/Given Names\nJUAN\nGitnang Apelyido/Middle Name\nMARTINEZ\nPetsa ng Kapanganakan/Date of Birth\nJANUARY 01, 1990\nTirahan/Address\n833 SISA ST., BRGY 526, ZONE 52 SAMPALOK, MANILA\nCITY, METRO MANILA\nPHL', '2025-05-07', 'Female', '973426171', 'MANG KANOR', '33344334', 'eunicegardner26@gmail.com', NULL, 1, '2025-05-07 09:31:11', '2025-05-07 09:31:11'),
('899999990000000', 'Eunice', 'DELA CRUZ', 'BA', '$2y$10$EK.wwSiV/FO0DatSjRSfCeIKdhkS1JMe.oLnuml5h2s1D46RFkV/m', 'REPUBLIKA NG PILIPINAS\nRepublic of the Philippines\nPAMBANSANG PAGKAKAKILANLAN\nPhilippine Identification Card\n3617-8019-4163-8256\nApleyido / Last Name\nGARDNER\nMga Pangalan / Given Names\nEUNICE\nG/tnang Apelyido / Middle Name\nBALEROS\nPetsäng Kapanganakani / Oate of Birth\nAUGUST 11,\n2005\nTirahan / Address\n15 NIEVES ST, MABAYUAN, CITY OF OLONGAPO, ZAMBALES, PHILIPPINES,\n2200\nPHL', '2025-05-07', 'Female', '973426171', 'MANG KANOR', '33344334', 'eunicegardner26@gmail.com', NULL, 0, '2025-05-07 09:32:09', '2025-05-07 09:32:09'),
('999999999999999999', 'Eunice', 'Gardner', 'B', '$2y$10$u6jBtMMVVFhGG0YEztUDNuOPx.4XISYyAIjWucLhYjiKJAKaJdfM6', 'REPUBLIKA NG PILIPINAS\nRepublic of the Philippines\nPAMBANSANG PAGKAKAKILANLAN\nPhilippine Identification Card:\n1234-5678-9101-1213\nApelyido/Last Name\nDELA CRUZ\nMga Pangalan/Given Names\nJUAN\nGitnang Apelyido/Middle Name\nMARTINEZ\nPetsa ng Kapanganakan/Date of Birth\nJANUARY 01, 1990\nTirahan/Address\n833 SISA ST., BRGY 526, ZONE 52 SAMPALOK, MANILA\nCITY, METRO MANILA\nPHL', '2025-05-06', 'Male', '09357708539', 'MANG KANOR', '33344334', '', NULL, 0, '2025-05-06 07:01:18', '2025-05-06 07:01:18');

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
(1, 44, 'uploads/results/680740e32354f.pdf', 'gardner_ef.pdf', '2025-04-22 07:10:27'),
(2, 57, 'uploads/results/6807412022d19.pdf', 'Medical Clinic Notify.pdf', '2025-04-22 07:11:28'),
(3, 56, 'uploads/results/6807421b3ee39.pdf', 'AbanesAljhunAureoSeanRomeoGardnerEunice-40281ITC221-1-1.pdf', '2025-04-22 07:15:39'),
(4, 64, 'uploads/results/681740a400dc0.pdf', 'GARDNER,EUNICE-BSIT2B_TRANSPORTATION .pdf', '2025-05-04 10:25:40'),
(5, 65, 'uploads/results/6819c25136cfa.pdf', '681740a400dc0.pdf', '2025-05-06 08:03:29'),
(6, 66, 'uploads/results/6819c624be4f6.pdf', '681740a400dc0.pdf', '2025-05-06 08:19:48'),
(7, 67, 'uploads/results/681b316ad76f8.pdf', 'AbanesAljhunAureoSeanRomeoGardnerEunice-40281ITC221-1-1 (1).pdf', '2025-05-07 10:09:46');

-- --------------------------------------------------------

--
-- Table structure for table `timeslots`
--

CREATE TABLE `timeslots` (
  `SlotID` int(11) NOT NULL,
  `DoctorID` int(11) DEFAULT NULL,
  `AvailableDay` varchar(10) DEFAULT NULL,
  `StartTime` time DEFAULT NULL,
  `EndTime` time DEFAULT NULL,
  `IsAvailable` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timeslots`
--

INSERT INTO `timeslots` (`SlotID`, `DoctorID`, `AvailableDay`, `StartTime`, `EndTime`, `IsAvailable`) VALUES
(1, 5, 'Monday', '08:00:00', '10:00:00', 0),
(2, 5, 'Monday', '10:00:00', '12:00:00', 0),
(3, 5, 'Tuesday', '10:00:00', '12:00:00', 0),
(4, 5, 'Tuesday', '08:00:00', '10:00:00', 1),
(5, 5, 'Friday', '13:00:00', '15:00:00', 0),
(6, 5, 'Friday', '15:00:00', '17:00:00', 0),
(7, 7, 'Monday', '07:00:00', '09:00:00', 0),
(8, 7, 'Monday', '09:00:00', '11:00:00', 0),
(9, 7, 'Tuesday', '07:00:00', '09:00:00', 1),
(10, 7, 'Tuesday', '09:00:00', '11:00:00', 1),
(11, 7, 'Thursday', '08:00:00', '10:00:00', 0),
(12, 7, 'Friday', '08:00:00', '10:00:00', 0),
(13, 8, 'Wednesday', '10:00:00', '12:00:00', 0),
(14, 8, 'Wednesday', '13:00:00', '15:00:00', 1),
(15, 8, 'Thursday', '07:00:00', '09:00:00', 0),
(16, 8, 'Thursday', '10:00:00', '12:00:00', 1),
(17, 8, 'Friday', '13:00:00', '15:00:00', 0),
(18, 8, 'Friday', '15:00:00', '17:00:00', 0),
(19, 9, 'Monday', '13:00:00', '15:00:00', 0),
(20, 9, 'Monday', '15:00:00', '17:00:00', 0),
(21, 9, 'Tuesday', '13:00:00', '15:00:00', 1),
(22, 9, 'Tuesday', '15:00:00', '17:00:00', 1),
(23, 9, 'Wednesday', '07:00:00', '09:00:00', 0),
(24, 9, 'Wednesday', '10:00:00', '12:00:00', 0),
(25, 9, 'Friday', '07:00:00', '09:00:00', 0),
(26, 10, 'Monday', '08:00:00', '10:00:00', 0),
(27, 10, 'Tuesday', '15:00:00', '17:00:00', 1),
(28, 10, 'Wednesday', '07:00:00', '09:00:00', 0),
(29, 10, 'Thursday', '07:00:00', '09:00:00', 1),
(30, 10, 'Friday', '08:00:00', '10:00:00', 0),
(31, 10, 'Friday', '10:00:00', '12:00:00', 0),
(32, 11, 'Monday', '10:00:00', '12:00:00', 0),
(33, 11, 'Tuesday', '13:00:00', '15:00:00', 0),
(34, 11, 'Wednesday', '08:00:00', '10:00:00', 1),
(35, 11, 'Thursday', '13:00:00', '15:00:00', 0),
(36, 11, 'Thursday', '15:00:00', '17:00:00', 1),
(37, 11, 'Friday', '08:00:00', '10:00:00', 0),
(38, 8, 'Wednesday', '15:00:00', '13:00:00', 0),
(39, 10, 'Wednesday', '08:00:00', '10:00:00', 1),
(40, 5, 'Thursday', '09:00:00', '11:00:00', 0),
(41, 9, 'Thursday', '13:00:00', '15:00:00', 1);

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
  ADD PRIMARY KEY (`StudentID`);

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
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `adminID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=202511185;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `AppointmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `DoctorID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notificationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `otp_verification`
--
ALTER TABLE `otp_verification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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
  MODIFY `statusID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `test_results`
--
ALTER TABLE `test_results`
  MODIFY `ResultID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `timeslots`
--
ALTER TABLE `timeslots`
  MODIFY `SlotID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33333;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`DoctorID`) REFERENCES `doctors` (`DoctorID`),
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`SlotID`) REFERENCES `timeslots` (`SlotID`),
  ADD CONSTRAINT `appointments_ibfk_4` FOREIGN KEY (`statusID`) REFERENCES `status` (`statusID`);

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
  ADD CONSTRAINT `test_results_ibfk_1` FOREIGN KEY (`AppointmentID`) REFERENCES `appointments` (`AppointmentID`);

--
-- Constraints for table `timeslots`
--
ALTER TABLE `timeslots`
  ADD CONSTRAINT `timeslots_ibfk_1` FOREIGN KEY (`DoctorID`) REFERENCES `doctors` (`DoctorID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
