-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 26, 2025 at 08:15 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `interviewsched`
--

-- --------------------------------------------------------

--
-- Table structure for table `applicants`
--

CREATE TABLE `applicants` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `program` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicants`
--

INSERT INTO `applicants` (`id`, `name`, `email`, `program`) VALUES
(14, 'lance', 'lance@gmail.com', ''),
(15, 'lucas', 'gabriel@gmail.com', ''),
(16, 'larissa', 'larissa@gmail.com', ''),
(17, 'uluru', 'uluru@gmail', ''),
(18, 'shi', 'shi@gmail.com', ''),
(19, 'lol', 'lol@gmail.com', ''),
(20, 'neko', 'neko@gmail.com', ''),
(21, 'uluru', 'uluru@gmail.com', ''),
(22, 'fafs', 'trt@gmail.com', ''),
(23, 'sdsa', 'dsdnd@gmail.com', ''),
(24, 'bhb', 'fdf@gmail.com', ''),
(25, 'sdas', 'njsnds@gmail.com', ''),
(26, 'sdsmk', 'rtokrk@gmail.com', ''),
(27, 'dab', 'dab@gmail.com', ''),
(28, 'shcjshd', 'dsdnj@gmail.com', ''),
(29, 'sdmskd', 'dsds@gmail.com', ''),
(30, 'neko', 'tnccuphunter09@gmail.com', ''),
(31, 'jowv', 'jovincepro09@gmail.com', ''),
(32, 'jean', 'jeeaaannnns@gmail.com', '');

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `id` int(11) NOT NULL,
  `interview_id` int(11) NOT NULL,
  `feedback_score` int(11) NOT NULL,
  `feedback_comments` text NOT NULL,
  `feedback_timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`id`, `interview_id`, `feedback_score`, `feedback_comments`, `feedback_timestamp`) VALUES
(4, 95, 20, 'xd', '2025-03-26 14:32:30'),
(5, 113, 12, 'type', '2025-03-26 14:43:44');

-- --------------------------------------------------------

--
-- Table structure for table `interviewers`
--

CREATE TABLE `interviewers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `program_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `interviewers`
--

INSERT INTO `interviewers` (`id`, `name`, `email`, `program_id`) VALUES
(16, 'zee', 'lucas@gmail.com', 1),
(17, 'zee', 'zee@gmail.com', 2),
(18, 'leonil', 'leonil@gmail.com', 1),
(19, 'laica', 'laica@gmail.com', 1),
(20, 'dab', 'dab@gmail.com', 1),
(21, 'dan', 'dan@gmail.com', 2),
(22, 'nako', 'nako@gmail.com', 1),
(23, 'jdsndsj', 'mkrtr@gmail.com', 8),
(24, 'dnsaj', 'fnjdnf@gmail.com', 6),
(25, 'dmsad', 'dsamd@gmail.com', 8),
(26, 'neko', 'neko@gmail.com', 2),
(27, 'jtrijt', 'oo@gmail.com', 1),
(28, 'fdmfk', 'mre@gmail.com', 8),
(29, 'mkerm', 'bwebh@gmail.com', 4),
(30, 'jowv', 'jovincepro09@gmail.com', 6),
(31, 'jowv', 'jovincepro9@gmail.com', 7);

-- --------------------------------------------------------

--
-- Table structure for table `interviews`
--

CREATE TABLE `interviews` (
  `id` int(11) NOT NULL,
  `interviewer_id` int(11) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `scheduled_time` datetime NOT NULL,
  `status` enum('scheduled','cancelled','trash') DEFAULT 'scheduled',
  `program` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `program_id` int(11) NOT NULL,
  `cancelled_date` date DEFAULT NULL,
  `meet_type` enum('Online','F2F') NOT NULL,
  `rating` int(11) NOT NULL,
  `feedback` varchar(255) NOT NULL DEFAULT 'Not Specified',
  `comments` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `interviews`
--

INSERT INTO `interviews` (`id`, `interviewer_id`, `applicant_id`, `scheduled_time`, `status`, `program`, `created_at`, `program_id`, `cancelled_date`, `meet_type`, `rating`, `feedback`, `comments`) VALUES
(91, 17, 15, '2025-03-21 09:43:00', 'trash', 0, '2025-03-07 08:39:57', 3, NULL, 'F2F', 0, 'Not Specified', ''),
(92, 17, 15, '2025-02-19 07:46:00', 'cancelled', 0, '2025-03-07 08:44:30', 1, NULL, 'Online', 0, 'Not Specified', ''),
(93, 17, 15, '2025-03-27 09:47:00', 'scheduled', 0, '2025-03-07 08:45:31', 2, NULL, 'Online', 0, 'Not Specified', ''),
(95, 20, 15, '2025-03-25 09:50:00', '', 0, '2025-03-07 08:46:36', 3, NULL, 'Online', 0, 'Not Specified', ''),
(96, 17, 15, '2025-03-20 08:00:00', 'cancelled', 0, '2025-03-07 08:56:56', 2, NULL, 'Online', 0, 'Not Specified', ''),
(98, 19, 16, '2025-03-07 07:07:00', 'cancelled', 0, '2025-03-07 09:04:45', 2, NULL, 'Online', 0, 'Not Specified', ''),
(101, 20, 19, '2025-07-09 12:00:00', 'cancelled', 0, '2025-03-18 01:31:44', 1, NULL, 'F2F', 0, 'Not Specified', ''),
(102, 21, 20, '2025-08-09 12:00:00', 'scheduled', 0, '2025-03-18 02:03:47', 2, NULL, 'Online', 0, 'Not Specified', ''),
(103, 20, 21, '2025-07-09 12:00:00', 'scheduled', 0, '2025-03-19 09:55:27', 1, NULL, 'Online', 0, 'Not Specified', ''),
(104, 22, 21, '2025-07-09 12:00:00', 'scheduled', 0, '2025-03-19 10:30:12', 1, NULL, 'Online', 0, 'Not Specified', ''),
(105, 23, 22, '2025-08-09 11:00:00', 'scheduled', 0, '2025-03-22 07:12:01', 8, NULL, 'F2F', 0, 'Not Specified', ''),
(106, 24, 23, '2026-07-09 00:00:00', 'cancelled', 0, '2025-03-25 13:39:06', 6, NULL, 'Online', 0, 'Not Specified', ''),
(107, 25, 24, '2025-08-10 16:30:00', 'scheduled', 0, '2025-03-25 15:18:41', 8, NULL, 'F2F', 0, 'Not Specified', ''),
(108, 26, 25, '2025-09-09 17:00:00', 'scheduled', 0, '2025-03-25 15:26:08', 2, NULL, 'F2F', 0, 'Not Specified', ''),
(109, 27, 26, '2025-10-10 23:00:00', 'scheduled', 0, '2025-03-25 15:30:54', 1, NULL, 'F2F', 0, 'Not Specified', ''),
(110, 28, 27, '2025-12-12 12:00:00', 'cancelled', 0, '2025-03-25 15:56:02', 8, NULL, 'F2F', 0, '', ''),
(111, 29, 28, '2025-12-12 14:00:00', 'scheduled', 0, '2025-03-25 15:56:46', 4, NULL, 'F2F', 0, '', ''),
(112, 20, 29, '2027-12-12 14:00:00', 'scheduled', 0, '2025-03-25 16:17:10', 2, NULL, 'Online', 0, 'cubao ', ''),
(113, 30, 30, '2026-12-12 12:00:00', '', 0, '2025-03-25 23:40:54', 6, NULL, 'F2F', 0, 'cubao 2nd floor', ''),
(114, 30, 30, '2025-04-23 12:00:00', 'scheduled', 0, '2025-03-26 00:20:58', 9, NULL, 'F2F', 0, 'cubao ibabaw', ''),
(115, 31, 31, '2026-02-25 16:00:00', 'scheduled', 0, '2025-03-26 13:25:39', 7, NULL, 'F2F', 0, 'jan lang', ''),
(116, 30, 32, '2025-03-27 13:00:00', 'scheduled', 0, '2025-03-26 13:32:33', 3, NULL, 'F2F', 0, 'sana suspended', ''),
(117, 31, 32, '2025-03-27 13:00:00', 'scheduled', 0, '2025-03-26 15:27:22', 1, NULL, 'F2F', 0, 'plv walang pasok', ''),
(118, 31, 32, '2025-03-27 13:00:00', 'scheduled', 0, '2025-03-26 15:28:54', 1, NULL, 'F2F', 0, 'plv walang pasok', '');

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `college` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `name`, `college`) VALUES
(1, 'BSCS', 'College of Technology'),
(2, 'BSCE', 'College of Technology'),
(3, 'BSIT', 'College of Technology'),
(4, 'BSED - ELEM', 'College of Education'),
(5, 'BSED - SEC', 'College of Education'),
(6, 'BSED - SPED', 'College of Education'),
(7, 'BSA', 'College of Business Accountancy'),
(8, 'BSHRM', 'College of Business Accountancy'),
(9, 'BSFM', 'College of Business Accountancy');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `password_hash` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `created_at`, `password_hash`) VALUES
(1, 'laurencianamuel@gmail.com', '', '$2y$10$pYvNw8ZCGMaYz676aGPZnOFO7AaNxn/vgbV9B7S.bqKYDp8IdSIKe', '2025-03-02 14:04:47', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applicants`
--
ALTER TABLE `applicants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_interview_feedback` (`interview_id`);

--
-- Indexes for table `interviewers`
--
ALTER TABLE `interviewers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `interviews`
--
ALTER TABLE `interviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `interviewer_id` (`interviewer_id`),
  ADD KEY `applicant_id` (`applicant_id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applicants`
--
ALTER TABLE `applicants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `interviewers`
--
ALTER TABLE `interviewers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `interviews`
--
ALTER TABLE `interviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=119;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `faculty`
--
ALTER TABLE `faculty`
  ADD CONSTRAINT `faculty_ibfk_1` FOREIGN KEY (`interview_id`) REFERENCES `interviews` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `interviews`
--
ALTER TABLE `interviews`
  ADD CONSTRAINT `interviews_ibfk_1` FOREIGN KEY (`interviewer_id`) REFERENCES `interviewers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `interviews_ibfk_2` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
