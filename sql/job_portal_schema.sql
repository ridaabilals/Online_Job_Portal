-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 03, 2025 at 06:35 PM
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
-- Database: `job_portal`
--

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `cover_letter` text DEFAULT NULL,
  `resume_path` varchar(255) DEFAULT NULL,
  `status` varchar(32) DEFAULT 'pending',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `job_id`, `user_id`, `cover_letter`, `resume_path`, `status`, `applied_at`, `updated_at`) VALUES
(4, 23, 26, 'give me job', 'assets/uploads/resumes/resume_6928593a00d54.pdf', 'rejected', '2025-11-27 13:59:22', '2025-11-27 15:16:04'),
(10, 28, 30, 'give me jobb', 'assets/uploads/resumes/resume_69288b5f3d953.pdf', 'rejected', '2025-11-27 17:33:19', '2025-11-29 17:35:28'),
(11, 28, 30, 'give me job', 'assets/uploads/resumes/resume_69288b70beb03.docx', 'rejected', '2025-11-27 17:33:36', '2025-11-29 15:33:31'),
(12, 24, 32, 'need job', 'assets/uploads/resumes/resume_692b0718b5e9d.pdf', 'pending', '2025-11-29 14:45:44', '2025-11-29 14:45:44'),
(13, 28, 32, '', 'assets/uploads/resumes/resume_692b07ac28c9f.pdf', 'shortlisted', '2025-11-29 14:48:12', '2025-11-29 15:31:43'),
(14, 65, 32, 'need Job', 'assets/uploads/resumes/resume_692e7fbe1075c.pdf', 'pending', '2025-12-02 05:57:18', '2025-12-02 05:57:18'),
(15, 65, 32, 'yy', 'assets/uploads/resumes/resume_69305794120ec.pdf', 'hired', '2025-12-03 15:30:28', '2025-12-03 15:38:58'),
(16, 32, 32, 'jn', 'assets/uploads/resumes/resume_693057a33d44e.docx', 'pending', '2025-12-03 15:30:43', '2025-12-03 15:30:43');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
