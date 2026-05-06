-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 05, 2026 at 07:33 AM
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
-- Database: `itpm_talentscoutai`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `application`
--

CREATE TABLE `application` (
  `application_id` int(11) NOT NULL,
  `job_post_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `status` enum('Pending','Interview Scheduled','Offer Received','Rejected') DEFAULT 'Pending',
  `employer_feedback` varchar(255) DEFAULT NULL,
  `application_date` date DEFAULT NULL,
  `hire_status` enum('none','offered','accepted','rejected') DEFAULT 'none',
  `hire_offer_message` text DEFAULT NULL,
  `hire_offer_date` timestamp NULL DEFAULT NULL,
  `hire_response_date` timestamp NULL DEFAULT NULL,
  `is_anonymous` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `application`
--

INSERT INTO `application` (`application_id`, `job_post_id`, `employee_id`, `status`, `employer_feedback`, `application_date`, `hire_status`, `hire_offer_message`, `hire_offer_date`, `hire_response_date`, `is_anonymous`) VALUES
(1, 6, 9, 'Pending', NULL, '2026-04-30', 'none', NULL, NULL, NULL, 0),
(2, 7, 9, 'Pending', NULL, '2026-04-30', 'none', NULL, NULL, NULL, 0),
(3, 2, 9, 'Pending', NULL, '2026-04-30', 'none', NULL, NULL, NULL, 0),
(4, 1, 9, 'Pending', NULL, '2026-04-30', 'none', NULL, NULL, NULL, 0),
(5, 5, 9, 'Pending', NULL, '2026-04-30', 'none', NULL, NULL, NULL, 0),
(6, 4, 9, 'Pending', NULL, '2026-04-30', 'none', NULL, NULL, NULL, 0),
(7, 7, 10, 'Pending', NULL, '2026-05-03', 'none', NULL, NULL, NULL, 0),
(8, 6, 10, 'Pending', NULL, '2026-05-03', 'none', NULL, NULL, NULL, 0),
(9, 8, 10, 'Interview Scheduled', NULL, '2026-05-03', 'accepted', '', '2026-05-03 19:43:00', '2026-05-03 19:43:05', 0),
(10, 5, 10, 'Pending', NULL, '2026-05-03', 'none', NULL, NULL, NULL, 0),
(11, 4, 10, 'Pending', NULL, '2026-05-03', 'none', NULL, NULL, NULL, 0),
(12, 3, 10, 'Pending', NULL, '2026-05-03', 'none', NULL, NULL, NULL, 0),
(13, 1, 10, 'Pending', NULL, '2026-05-03', 'none', NULL, NULL, NULL, 0),
(14, 11, 10, 'Offer Received', NULL, '2026-05-05', 'accepted', NULL, NULL, '2026-05-05 00:22:13', 0),
(15, 10, 10, 'Interview Scheduled', NULL, '2026-05-05', 'accepted', '', '2026-05-05 00:28:28', '2026-05-05 00:28:32', 0),
(16, 9, 10, 'Interview Scheduled', NULL, '2026-05-05', 'none', NULL, NULL, NULL, 0),
(17, 2, 10, 'Pending', NULL, '2026-05-05', 'none', NULL, NULL, NULL, 0),
(18, 10, 5, 'Pending', NULL, '2026-05-05', 'none', NULL, NULL, NULL, 0),
(19, 8, 5, 'Interview Scheduled', NULL, '2026-05-05', 'accepted', 'See u boi', '2026-05-05 03:40:03', '2026-05-05 03:40:24', 1),
(20, 1, 5, 'Pending', NULL, '2026-05-05', 'none', NULL, NULL, NULL, 0),
(21, 7, 5, 'Pending', NULL, '2026-05-05', 'none', NULL, NULL, NULL, 0),
(22, 9, 5, 'Pending', NULL, '2026-05-05', 'none', NULL, NULL, NULL, 0),
(23, 5, 5, 'Pending', NULL, '2026-05-05', 'none', NULL, NULL, NULL, 0),
(24, 4, 5, 'Pending', NULL, '2026-05-05', 'none', NULL, NULL, NULL, 0),
(25, 6, 5, 'Pending', NULL, '2026-05-05', 'none', NULL, NULL, NULL, 0),
(26, 11, 5, 'Pending', NULL, '2026-05-05', 'none', NULL, NULL, NULL, 0),
(27, 11, 6, 'Pending', NULL, '2026-05-05', 'none', NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `employee_id` int(11) NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`employee_id`, `first_name`, `last_name`, `email`, `password`, `address`, `is_active`) VALUES
(1, 'Juan', 'Dela Cruz', 'juan@email.com', '123456', 'Quezon City', 1),
(2, 'Maria', 'Santos', 'maria@email.com', '123456', 'Manila', 1),
(3, 'Jose', 'Reyes', 'jose@email.com', '123456', 'Laguna', 1),
(4, 'Ana', 'Villanueva', 'ana@email.com', '123456', 'Cavite', 1),
(5, 'Carlo', 'Mendoza', 'carlo@email.com', '123456', 'Bulacan', 1),
(6, 'Mac Millan', 'Abrenica', '0918millan@gmail.com', '$2y$10$VZ6jlb0x74hEh1KGPFAL.ukDwFzNXi3M33uo1XkPZWLoeFmmLiz/O', 'Kaylaway', 1),
(7, 'Andre', 'Cahola', 'andre@gmail.com', '$2y$10$4mAnaPAI2qPLiX1gtMdIdOjKv42Er2rTf14Rrw0AUSsI5MiV8osuq', 'Wawa', 1),
(8, 'Felman', 'Elepango', 'felman@gmail.com', '$2y$10$0ujQAXuY1NtbYNJ2x4M.PeVIj1.E0i3jFO4aHwDUy7Ud30NPj.cp2', 'Bucana', 1),
(9, 'Aeron', 'Salanguit', 'saeronmarc@gmail.com', '$2y$10$kdzTpn/kGtxoZi9dvqYlm.7rp4KANmdnJWD.1St/TiH2CNXq3kF0C', 'Wawa', 1),
(10, 'Taga', 'Don', 'tagadon@sakalye.com', '$2y$10$V3IRgrJyTn9fYrQVMATLmujYlNKwCp.z.C408K.CR5Pm9iiuerE4C', 'Wawa', 1);

-- --------------------------------------------------------

--
-- Table structure for table `employee_additional_info`
--

CREATE TABLE `employee_additional_info` (
  `info_id` int(11) NOT NULL,
  `resume_id` int(11) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_additional_info`
--

INSERT INTO `employee_additional_info` (`info_id`, `resume_id`, `description`) VALUES
(4, 1, 'Certifications: Professional Engineer (PE), PMP'),
(5, 1, 'Awards: Active participant in engineering community projects.'),
(6, 1, 'Language: Filipino, Bisaya, English'),
(13, 6, 'Certifications: Professional Engineer (PE), PMP'),
(14, 6, 'Awards: Active participant in engineering community projects.'),
(15, 7, 'Certifications: Professional Engineer (PE), PMP'),
(16, 7, 'Awards: Active participant in engineering community projects.'),
(17, 2, 'Certifications: Professional Engineer (PE), PMP'),
(18, 2, 'Awards: Active participant in engineering community projects.'),
(23, 10, 'Certifications: Professional Engineer (PE), PMP'),
(24, 10, 'Awards: Active participant in engineering community projects.');

-- --------------------------------------------------------

--
-- Table structure for table `employee_education`
--

CREATE TABLE `employee_education` (
  `education_id` int(11) NOT NULL,
  `resume_id` int(11) NOT NULL,
  `degree` varchar(255) DEFAULT NULL,
  `school` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT 0,
  `details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_education`
--

INSERT INTO `employee_education` (`education_id`, `resume_id`, `degree`, `school`, `start_date`, `end_date`, `is_current`, `details`) VALUES
(2, 1, 'Bachelor of Mechanical Engineering with Honors', 'University of Engineering Excellence', '2016-08-01', '2019-10-01', 0, 'Major in Automotive Technology. Thesis on technological advancements in mechatronics.'),
(6, 6, 'Bachelor of Mechanical Engineering with Honors', 'University of Engineering Excellence', '2016-08-01', '2019-10-01', 0, 'Major in Automotive Technology. Thesis on technological advancements in mechatronics.'),
(7, 7, 'Bachelor of Mechanical Engineering with Honors', 'University of Engineering Excellence', '2016-08-01', '2019-10-01', 0, 'Major in Automotive Technology. Thesis on technological advancements in mechatronics.'),
(8, 2, 'Bachelor of Mechanical Engineering with Honors', 'University of Engineering Excellence', '2016-08-01', '2019-10-01', 0, 'Major in Automotive Technology. Thesis on technological advancements in mechatronics.'),
(10, 8, '', '', NULL, NULL, 1, ''),
(13, 9, '', '', NULL, NULL, 1, ''),
(16, 10, 'Bachelor of Mechanical Engineering with Honors', 'University of Engineering Excellence', '2016-08-01', '2019-10-01', 0, 'Major in Automotive Technology. Thesis on technological advancements in mechatronics.');

-- --------------------------------------------------------

--
-- Table structure for table `employee_experience`
--

CREATE TABLE `employee_experience` (
  `experience_id` int(11) NOT NULL,
  `resume_id` int(11) NOT NULL,
  `job_title` varchar(255) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_present` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_experience`
--

INSERT INTO `employee_experience` (`experience_id`, `resume_id`, `job_title`, `company_name`, `start_date`, `end_date`, `is_present`) VALUES
(3, 1, 'Mechatronics Engineer', 'Borcelle Technologies', '2023-01-01', NULL, 1),
(4, 1, 'System Engineer', 'Arrowai Industries', '2021-02-01', '2022-12-01', 0),
(11, 6, 'It', 'Borcelle Technologies', '2023-01-01', NULL, 1),
(12, 6, 'Engineer', 'Arrowai Industries', '2021-02-01', '2022-12-01', 0),
(13, 7, 'Mechatronics Engineer', 'Borcelle Technologies', '2023-01-01', NULL, 1),
(14, 7, 'System Engineer', 'Arrowai Industries', '2021-02-01', '2022-12-01', 0),
(15, 7, 'Valorant Player', 'jan lang', '2022-02-01', NULL, 1),
(16, 2, 'Mechatronics Engineer', 'Borcelle Technologies', '2023-01-01', NULL, 1),
(17, 2, 'System Engineer', 'Arrowai Industries', '2021-02-01', '2022-12-01', 0),
(19, 8, '', '', NULL, NULL, 1),
(22, 9, '', '', NULL, NULL, 1),
(27, 10, 'Mechatronics Engineer', 'Borcelle Technologies', '2023-01-01', NULL, 1),
(28, 10, 'System Engineer', 'Arrowai Industries', '2021-02-01', '2022-12-01', 0);

-- --------------------------------------------------------

--
-- Table structure for table `employee_skill`
--

CREATE TABLE `employee_skill` (
  `employee_skill_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `skill_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_skill`
--

INSERT INTO `employee_skill` (`employee_skill_id`, `employee_id`, `skill_name`) VALUES
(1, 1, 'Java'),
(2, 1, 'MySQL'),
(3, 2, 'UI/UX Design'),
(4, 2, 'Figma'),
(5, 3, 'Python'),
(6, 3, 'Data Analysis'),
(7, 4, 'Project Management'),
(8, 4, 'Documentation'),
(9, 5, 'Web Development'),
(10, 5, 'JavaScript'),
(11, 7, 'valo'),
(12, 7, 'ml'),
(14, 8, 'test skill'),
(15, 8, 'skill 2'),
(17, 6, 'Java'),
(18, 6, 'Front End'),
(19, 6, 'Python'),
(20, 6, 'bago'),
(21, 9, 'javascript'),
(22, 9, 'javascript'),
(23, 10, 'javascript'),
(24, 10, 'html'),
(26, 10, 'javascript'),
(27, 10, 'html'),
(28, 10, 'CSS'),
(29, 10, 'Java'),
(30, 10, 'javascript'),
(31, 10, 'html'),
(32, 10, 'java'),
(33, 10, 'css'),
(37, 5, 'Java'),
(38, 5, 'Mechatronics System Integration'),
(39, 5, 'Automotive Engineering Technology'),
(40, 5, 'Robotics and Automation'),
(41, 5, 'CAD for Mechatronics'),
(42, 5, 'Project Management'),
(45, 5, 'Mechatronics System Integration'),
(46, 5, 'Automotive Engineering Technology'),
(47, 5, 'Robotics and Automation'),
(48, 5, 'CAD for Mechatronics'),
(49, 5, 'Project Management'),
(50, 5, 'Web Development'),
(51, 5, 'JavaScript'),
(52, 5, 'Java'),
(60, 5, 'Mechatronics System Integration'),
(61, 5, 'Automotive Engineering Technology'),
(62, 5, 'Robotics and Automation'),
(63, 5, 'CAD for Mechatronics'),
(64, 5, 'Project Management'),
(65, 5, 'Web Development'),
(66, 5, 'JavaScript'),
(67, 5, 'Java');

-- --------------------------------------------------------

--
-- Table structure for table `employer`
--

CREATE TABLE `employer` (
  `employer_id` int(11) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `business_reg_cert` varchar(255) DEFAULT NULL,
  `mayor_permit` varchar(255) DEFAULT NULL,
  `bir_registration` varchar(255) DEFAULT NULL,
  `dole_registration` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employer`
--

INSERT INTO `employer` (`employer_id`, `company_name`, `email`, `password`, `address`, `status`) VALUES
(1, 'BayanTech Solutions', 'hr@bayantech.ph', '123456', 'Quezon City', 'active'),
(2, 'Lakbay Digital Corp', 'jobs@lakbay.ph', '123456', 'Makati City', 'active'),
(3, 'AniHarvest Inc.', 'careers@aniharvest.ph', '123456', 'Batangas', 'active'),
(4, 'BuildPro Construction', 'apply@buildpro.ph', '123456', 'Cebu City', 'active'),
(5, 'SariSari Systems', 'hr@sarisari.ph', '123456', 'Davao City', 'active'),
(6, 'a', 'a', 'a', 'a', 'a'),
(7, 'Bawal Tamad', 'tagadon@sakalye.com', '$2y$10$xlz.Jijk5OXRBsPOwH7mJu5m2Ngpijzp6CxS7hQmVZZjPrA4QPwsq', 'Genggeng St.', 'active'),
(8, 'Bawal Tamad', 'tagadoon@sakalye.com', '$2y$10$1mJf7BI2cVfdrK5PmVS3MOutxLChett8b/pCmooTKYVmsFqz745m.', 'Genggeng St.', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `experience_bullets`
--

CREATE TABLE `experience_bullets` (
  `bullet_id` int(11) NOT NULL,
  `experience_id` int(11) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `experience_bullets`
--

INSERT INTO `experience_bullets` (`bullet_id`, `experience_id`, `description`) VALUES
(7, 3, 'Led development of an advanced automation system, achieving a 15% increase in operational efficiency.'),
(8, 3, 'Streamlined manufacturing processes, reducing production costs by 10%.'),
(9, 3, 'Implemented preventive maintenance strategies, resulting in a 20% decrease in equipment downtime.'),
(10, 4, 'Designed and optimized a robotic control system, realizing a 12% performance improvement.'),
(11, 4, 'Coordinated testing and validation, ensuring compliance with industry standards.'),
(12, 4, 'Provided technical expertise, contributing to a 15% reduction in system failures.'),
(31, 11, 'Led development of an advanced automation system, achieving a 15% increase in operational efficiency.'),
(32, 11, 'Streamlined manufacturing processes, reducing production costs by 10%.'),
(33, 11, 'Implemented preventive maintenance strategies, resulting in a 20% decrease in equipment downtime.'),
(34, 12, 'Designed and optimized a robotic control system, realizing a 12% performance improvement.'),
(35, 12, 'Coordinated testing and validation, ensuring compliance with industry standards.'),
(36, 12, 'Provided technical expertise, contributing to a 15% reduction in system failures.'),
(37, 13, 'Led development of an advanced automation system, achieving a 15% increase in operational efficiency.'),
(38, 13, 'Streamlined manufacturing processes, reducing production costs by 10%.'),
(39, 13, 'Implemented preventive maintenance strategies, resulting in a 20% decrease in equipment downtime.'),
(40, 14, 'Designed and optimized a robotic control system, realizing a 12% performance improvement.'),
(41, 14, 'Coordinated testing and validation, ensuring compliance with industry standards.'),
(42, 14, 'Provided technical expertise, contributing to a 15% reduction in system failures.'),
(43, 15, 'hehe'),
(44, 16, 'Led development of an advanced automation system, achieving a 15% increase in operational efficiency.'),
(45, 16, 'Streamlined manufacturing processes, reducing production costs by 10%.'),
(46, 16, 'Implemented preventive maintenance strategies, resulting in a 20% decrease in equipment downtime.'),
(47, 17, 'Designed and optimized a robotic control system, realizing a 12% performance improvement.'),
(48, 17, 'Coordinated testing and validation, ensuring compliance with industry standards.'),
(49, 17, 'Provided technical expertise, contributing to a 15% reduction in system failures.'),
(62, 27, 'Led development of an advanced automation system, achieving a 15% increase in operational efficiency.'),
(63, 27, 'Streamlined manufacturing processes, reducing production costs by 10%.'),
(64, 27, 'Implemented preventive maintenance strategies, resulting in a 20% decrease in equipment downtime.'),
(65, 28, 'Designed and optimized a robotic control system, realizing a 12% performance improvement.'),
(66, 28, 'Coordinated testing and validation, ensuring compliance with industry standards.'),
(67, 28, 'Provided technical expertise, contributing to a 15% reduction in system failures.');

-- --------------------------------------------------------

--
-- Table structure for table `interview`
--

CREATE TABLE `interview` (
  `interview_id` int(11) NOT NULL,
  `application_id` int(11) DEFAULT NULL,
  `scheduled_date` datetime DEFAULT NULL,
  `interview_type` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `interviews`
--

CREATE TABLE `interviews` (
  `interview_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `employer_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `scheduled_datetime` datetime NOT NULL,
  `confirmation_message` text DEFAULT NULL,
  `status` enum('scheduled','accepted','rejected','cancelled') DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `interviews`
--

INSERT INTO `interviews` (`interview_id`, `application_id`, `employer_id`, `employee_id`, `scheduled_datetime`, `confirmation_message`, `status`, `created_at`, `updated_at`) VALUES
(1, 9, 8, 10, '2026-05-07 03:42:00', '', 'accepted', '2026-05-03 19:39:13', '2026-05-03 19:42:41'),
(2, 15, 8, 5, '2026-05-07 11:11:00', 'aa', 'scheduled', '2026-05-05 00:23:03', '2026-05-05 00:23:03'),
(3, 15, 8, 5, '2026-05-29 15:16:00', 'aa', 'scheduled', '2026-05-05 00:23:12', '2026-05-05 00:23:12'),
(4, 15, 8, 5, '2026-05-29 15:16:00', 'aa', 'scheduled', '2026-05-05 00:23:15', '2026-05-05 00:23:15'),
(5, 15, 8, 10, '2026-05-20 12:26:00', 'aaa', 'accepted', '2026-05-05 00:23:31', '2026-05-05 00:23:36'),
(6, 19, 8, 5, '2026-05-27 05:39:00', 'Punta ka boi', 'accepted', '2026-05-05 03:39:11', '2026-05-05 03:39:22');

-- --------------------------------------------------------

--
-- Table structure for table `job_post`
--

CREATE TABLE `job_post` (
  `job_post_id` int(11) NOT NULL,
  `employer_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `salary` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `work_type` varchar(50) DEFAULT NULL,
  `application_deadline` date DEFAULT NULL,
  `skills` varchar(255) NOT NULL,
  `experience_level` varchar(255) NOT NULL,
  `job_category` varchar(255) NOT NULL,
  `job_post_created` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `job_status` varchar(20) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_post`
--

INSERT INTO `job_post` (`job_post_id`, `employer_id`, `title`, `description`, `salary`, `location`, `work_type`, `application_deadline`, `skills`, `experience_level`, `job_category`, `job_post_created`, `job_status`) VALUES
(1, 1, 'Junior Java Developer', 'Develop and maintain Java applications', '25000-30000', 'Quezon City', 'Full-time', '2026-06-02', 'HTML, CSS, JavaScript', 'Entry level', 'Technology', '2026-05-03 20:19:24', 'active'),
(2, 2, 'UI/UX Designer', 'Design user-friendly interfaces', '3000-4000', 'Makati City', 'Full-time', '2026-06-02', 'Java, Html', 'Mid level', 'Healthcare', '2026-05-03 20:19:24', 'active'),
(3, 3, 'Data Analyst', 'Analyze agricultural data trends', '28000.00', 'Batangas', 'Hybrid', '2026-06-02', 'HTML, CSS', '', '', '2026-05-03 20:19:24', 'active'),
(4, 4, 'Project Coordinator', 'Assist in construction project management', '27000.00', 'Cebu City', 'On-site', '2026-06-02', 'wala lang', '', '', '2026-05-03 20:19:24', 'active'),
(5, 5, 'Web Developer', 'Build and maintain web applications', '26000.00', 'Davao City', 'Remote', '2026-06-02', 'dito lang', '', '', '2026-05-03 20:19:24', 'active'),
(6, 6, 'a', 'a', '2500-3000', 'a', 'a', '2026-06-02', 'a', 'a', 'a', '2026-05-03 20:19:24', 'active'),
(7, 7, 'aa', 'aaa', '2,000', 'bucana', 'Full-time', '2026-06-02', 'javascript, java', '', 'Technology', '2026-05-03 20:19:24', 'active'),
(8, 8, 'aa', 'aa', '2,000', 'bucana', 'Part-time', '2026-06-02', 'javascript, java', '', 'Technology', '2026-05-04 22:03:19', 'closed'),
(9, 8, 'aa', 'aa', '2,000', 'wawa', 'Part-time', '2026-05-27', 'javascript, java', '', 'Healthcare', '2026-05-04 22:03:25', 'closed'),
(10, 8, 'aa', 'aa', '2,000', 'wawa', 'Part-time', '2026-05-31', 'javascript, java', '', 'IT', '2026-05-04 22:09:09', 'active'),
(11, 8, 'aaaaaaaaaaaaa', 'aaa', '2,000', 'bucana', 'Part-time', '2026-05-28', 'javascript, java', '', 'Healthcare', '2026-05-04 22:28:34', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `message`
--

CREATE TABLE `message` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `sender_type` varchar(50) DEFAULT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `receiver_type` varchar(50) DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  `application_id` int(11) DEFAULT NULL,
  `timestamp` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `message`
--

INSERT INTO `message` (`message_id`, `sender_id`, `sender_type`, `receiver_id`, `receiver_type`, `message`, `application_id`, `timestamp`) VALUES
(1, 7, 'employer', 9, 'employee', 'aa', 0, '2026-04-30 20:01:25'),
(2, 9, 'employee', 7, 'employer', 'a', 0, '2026-04-30 20:08:24'),
(3, 9, 'employee', 7, 'employer', 'aa', 0, '2026-04-30 20:08:25'),
(4, 8, 'employer', 9, 'employee', 'aa', 0, '2026-05-04 02:46:19'),
(5, 8, 'employer', 10, 'employee', 'aa', 0, '2026-05-04 02:54:10'),
(6, 10, 'employee', 8, 'employer', 'a', 9, '2026-05-04 02:54:20'),
(7, 8, 'employer', 10, 'employee', '📅 **INTERVIEW SCHEDULED** - Congratulations! We would like to schedule an interview with you. Please check your email for interview details and available time slots.', 9, '2026-05-04 02:54:36'),
(8, 8, 'employer', 10, 'employee', 'a', 9, '2026-05-04 03:05:28'),
(9, 8, 'employer', 10, 'employee', '{\"type\":\"schedule_offer\",\"interview_date\":\"2026-05-07\",\"interview_time\":\"03:07\",\"interview_type\":\"Video Call (Zoom\\/Teams)\",\"offered_by\":\"employer\",\"status\":\"pending\"}|||📅 **INTERVIEW SCHEDULED** - Proposed: 2026-05-07 at 03:07 (Video Call (Zoom/Teams))', 0, '2026-05-04 03:07:20'),
(10, 8, 'employer', 10, 'employee', '{\"type\":\"schedule_offer\",\"interview_date\":\"2026-05-07\",\"interview_time\":\"03:07\",\"interview_type\":\"Video Call (Zoom\\/Teams)\",\"offered_by\":\"employer\",\"status\":\"pending\"}|||📅 **INTERVIEW SCHEDULED** - Proposed: 2026-05-07 at 03:07 (Video Call (Zoom/Teams))', 0, '2026-05-04 03:08:01'),
(11, 8, 'employer', 10, 'employee', 'Interview scheduled for May 07, 2026 3:42 AM. Please confirm your availability.', 9, '2026-05-04 03:39:13'),
(12, 10, 'employee', 8, 'employer', 'Yes', 0, '2026-05-04 03:39:31'),
(13, 10, 'employee', 8, 'employer', '✅ I accept the scheduled interview for May 07, 2026 3:42 AM. Thank you!', 9, '2026-05-04 03:42:41'),
(14, 8, 'employer', 10, 'employee', '🎉 JOB OFFER! Congratulations! We are pleased to offer you the position. Please respond to this offer.', 9, '2026-05-04 03:43:00'),
(15, 10, 'employee', 8, 'employer', '🎉 I ACCEPT THE JOB OFFER! Thank you so much for this opportunity. I look forward to joining Bawal Tamad!', 9, '2026-05-04 03:43:05'),
(16, 8, 'employer', 5, 'employee', 'yow', 0, '2026-05-04 04:50:31'),
(17, 8, 'employer', 9, 'employee', 'aa', 0, '2026-05-05 06:09:19'),
(18, 8, 'employer', 9, 'employee', 'aa', 0, '2026-05-05 06:10:34'),
(19, 8, 'employer', 5, 'employee', 'aa', 0, '2026-05-05 06:10:39'),
(20, 8, 'employer', 5, 'employee', 'aa', 0, '2026-05-05 06:13:26'),
(21, 10, 'employee', 8, 'employer', 'Yes', 0, '2026-05-05 06:23:09'),
(22, 10, 'employee', 8, 'employer', 'Yes', 0, '2026-05-05 06:23:50'),
(23, 10, 'employee', 8, 'employer', 'Tes', 0, '2026-05-05 06:23:57'),
(24, 8, 'employer', 9, 'employee', 'aa', 0, '2026-05-05 08:09:55'),
(25, 8, 'employer', 10, 'employee', 'Your application has been received and is pending review.', 14, '2026-05-05 08:13:20'),
(26, 8, 'employer', 10, 'employee', 'Congratulations! Your application has been moved to interview stage.', 14, '2026-05-05 08:13:23'),
(27, 8, 'employer', 10, 'employee', 'Congratulations! You have been hired! Welcome aboard!', 14, '2026-05-05 08:14:11'),
(28, 8, 'employer', 10, 'employee', 'Congratulations! Your application has been moved to interview stage.', 14, '2026-05-05 08:14:15'),
(29, 8, 'employer', 10, 'employee', 'Great news! We would like to extend a job offer to you.', 14, '2026-05-05 08:14:58'),
(30, 10, 'employee', 8, 'employer', '🎉 I ACCEPT THE JOB OFFER! Thank you so much for this opportunity. I look forward to joining Bawal Tamad!', 14, '2026-05-05 08:22:13'),
(31, 8, 'employer', 5, 'employee', 'Interview scheduled for May 07, 2026 11:11 AM. aa', 15, '2026-05-05 08:23:03'),
(32, 8, 'employer', 5, 'employee', 'Interview scheduled for May 29, 2026 3:16 PM. aa', 15, '2026-05-05 08:23:12'),
(33, 8, 'employer', 5, 'employee', 'Interview scheduled for May 29, 2026 3:16 PM. aa', 15, '2026-05-05 08:23:15'),
(34, 8, 'employer', 10, 'employee', '📅 Interview Scheduled! Your application has been moved to interview stage. Please check your messages for details.', 15, '2026-05-05 08:23:31'),
(35, 10, 'employee', 8, 'employer', '✅ I accept the scheduled interview for May 20, 2026 12:26 PM. Thank you!', 15, '2026-05-05 08:23:36'),
(36, 8, 'employer', 10, 'employee', '🎉 JOB OFFER! Congratulations! We are pleased to offer you the position. Please respond to this offer.', 15, '2026-05-05 08:28:28'),
(37, 10, 'employee', 8, 'employer', '🎉 I ACCEPT THE JOB OFFER! Thank you so much for this opportunity. I look forward to joining Bawal Tamad!', 15, '2026-05-05 08:28:32'),
(38, 8, 'employer', 5, 'employee', '📅 Interview scheduled for May 27, 2026 5:39 AM. Punta ka boi', 19, '2026-05-05 11:39:11'),
(39, 5, 'employee', 8, 'employer', '✅ I accept the scheduled interview for May 27, 2026 5:39 AM. Thank you!', 19, '2026-05-05 11:39:22'),
(40, 8, 'employer', 5, 'employee', '🎉 JOB OFFER! Congratulations! See u boi', 19, '2026-05-05 11:40:03'),
(41, 5, 'employee', 8, 'employer', '🎉 I ACCEPT THE JOB OFFER! Thank you so much for this opportunity. I look forward to joining Bawal Tamad!', 19, '2026-05-05 11:40:24');

-- --------------------------------------------------------

--
-- Table structure for table `report`
--

CREATE TABLE `report` (
  `report_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `date_summary` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resumes`
--

CREATE TABLE `resumes` (
  `resume_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `photo_data_url` longtext DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(80) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resumes`
--

INSERT INTO `resumes` (`resume_id`, `employee_id`, `full_name`, `photo_data_url`, `address`, `phone`, `email`, `website`, `summary`, `created_at`, `updated_at`) VALUES
(1, 1, 'mac millan m. abrenica', 'data:image/jpeg;base64,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lQL+w8f6NO70sG0yi1KyY8mIJREzJC9acDv+ZPJUtWbQEEAEwEkAlIkUQSAAAQJiRMCQQiSCEkKATACAAARITAAJiVAt2eNvn0PNgzsRS9C+HLiq94tEE0rMDwvtfnUurS9GkAiYBCSiQBIoglATAkgmAAATEgEARMJIEwWYAEAAAABQAJQL7GvlPe9XyHqrjLTJSIx3xVtTExWQilsVaHgvV+Smq1mFggmATBC0LAAJgAAAAAQFAkEARMJIExKomAAEASKgAQAAFteknY9d4v1jPZimRKYc0VmicUZYpYrrbOhXmeDu6E2rMEABAEwJgUEAABQAASYmFEkJEBAAAUEBQQCYFBAAAAJkXe9mM7m2JjBm1gi4ZOOV4bCTaAgIBEgAAgSQoAAKACTAqQA//8QAAv/aAAwDAQACAAMAAAAh8888888888888888888888888888888888888888888888888888848w80888888888888888888888888888888880QaUQ86U48888888888888888888888888884oVPlPHRP3mww88888888888888888888888888hP3z3/+W3qSOY88888888888888888888884wPyWO/NhMV5VeeeU8888888888888888888888+qPhRqBS3AFWBjX2888888888888888888888wBOxA2JTcr3RUtmDzA888888888888888888884hBhY+P+H6+uQPIlnw88888888888888888888clqjlLOa7yGSf4/oW8888888888888888888888PhXkmgnsmaN3/ABlznPPPPPPPPPPPPPPPPPPPPKKDiovuP276vWkQZKnPPPPPPPPPPPPPPPPPPPPLBu4K1SxgYnNauIAavPPPPPPPPPPPPPPPPPPPPO+Eu1/dGQgRAgM5hfHPPPPPPPPPPPPPPPPPPPPK5j4zSpVPHYRJOVAVTvPPPPPPPPPPPPPPPPPPPB8ZBfC3hii1yO/O9vKfPPPPPPPPPPPPPPPPPPPNrp9U65nYW6XDzohiP/PPPPPPPPPPPPPPPPPPPCYBS0yjujq2x1KblAVvPPPPPPPPPPPPPPPPPPPOyuJ83Iym4MO0aIVrPvPPPPPPPPPPPPPPPPPPPPPJVuUcLoqIoTBE33PPPPPPPPPPPPPPPPPPPPPP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888888888888888888888888888888888888888888484w0888888888888888888888888888888442IdlFw+K8888888888888888888888888888posIU02YEca88888888888888888888888884Ha8kQs0oU0Yc4O088888888888888888888842w0c8QiCzumCtQo+8888888888888888888880JIMeRH0oXjUIlcE98888888888888888888889KNE4e1J7LKMEWKQAG88888888888888888888B80yK7wZsL3+vIWWQ288888888888888888888Uso1kBZNuPHXnLncc288888888888888888888cMbcplpvpH3JN93iKW88888888888888888888s2cTiRWyYm6Aicphh8888888888888888888888E8CLme0BL+L/BRhh888888888888888888888so7IJBDz0OTDksfFrn88888888888888888888rhaWFA5Cjv1TfcYPUR88888888888888888888TyyOXurQ9MomBpk/vu88888888888888888888djtijhr9OzIvPCyGIq88888888888888888888g+ncV/D6eiJnFWmRsM8888888888888888888876OIdgRUrD2eJDi/6/8888888888888888888888v0mTq/uc02AhJX888888888888888888888888lps7aEXHxqBhuk888888888888888888888888ywo0k6kkQuf7k88888888888888888888888888HXibSD8iCEmv88888888888888888888888888+0ldbZ2OmQl888888888888888888888888888XQnkCk5X6CP888888888888888888888888888NeFfGk27qunc88888888888888888888888886ENAUsiCc25wcXx088888888888888888852J9o5klZkw0yucZJttR1888888888888888rWZkVdotDQegkI6r34oEsdRoL7888888881iPod405wgVjXfMTIYmODgIUwUsRxpAj6x88brN5YkEckJAAMEfvrBwsKXVtMUIZEAExpthxEN048w4RgF9AAAAtKP3DozPb4MQgAdw4MQU8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', '381, Sampaga Balayan Batangas', '09772412273', '0918millan@gmail.com', 'millan.linkedin.com', 'Results-oriented Mechanical and Mechatronics Engineer seeking a challenging role to apply expertise in designing and implementing innovative solutions for complex engineering challenges.', '2026-03-22 06:23:14', '2026-03-22 06:23:14');
INSERT INTO `resumes` (`resume_id`, `employee_id`, `full_name`, `photo_data_url`, `address`, `phone`, `email`, `website`, `summary`, `created_at`, `updated_at`) VALUES
(2, 6, 'Mac Millan M. Abrenica', 'data:image/jpeg;base64,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lQL+w8f6NO70sG0yi1KyY8mIJREzJC9acDv+ZPJUtWbQEEAEwEkAlIkUQSAAAQJiRMCQQiSCEkKATACAAARITAAJiVAt2eNvn0PNgzsRS9C+HLiq94tEE0rMDwvtfnUurS9GkAiYBCSiQBIoglATAkgmAAATEgEARMJIEwWYAEAAAABQAJQL7GvlPe9XyHqrjLTJSIx3xVtTExWQilsVaHgvV+Smq1mFggmATBC0LAAJgAAAAAQFAkEARMJIExKomAAEASKgAQAAFteknY9d4v1jPZimRKYc0VmicUZYpYrrbOhXmeDu6E2rMEABAEwJgUEAABQAASYmFEkJEBAAAUEBQQCYFBAAAAJkXe9mM7m2JjBm1gi4ZOOV4bCTaAgIBEgAAgSQoAAKACTAqQA//8QAAv/aAAwDAQACAAMAAAAh8888888888888888888888888888888888888888888888888888848w80888888888888888888888888888888880QaUQ86U48888888888888888888888888884oVPlPHRP3mww88888888888888888888888888hP3z3/+W3qSOY88888888888888888888884wPyWO/NhMV5VeeeU8888888888888888888888+qPhRqBS3AFWBjX2888888888888888888888wBOxA2JTcr3RUtmDzA888888888888888888884hBhY+P+H6+uQPIlnw88888888888888888888clqjlLOa7yGSf4/oW8888888888888888888888PhXkmgnsmaN3/ABlznPPPPPPPPPPPPPPPPPPPPKKDiovuP276vWkQZKnPPPPPPPPPPPPPPPPPPPPLBu4K1SxgYnNauIAavPPPPPPPPPPPPPPPPPPPPO+Eu1/dGQgRAgM5hfHPPPPPPPPPPPPPPPPPPPPK5j4zSpVPHYRJOVAVTvPPPPPPPPPPPPPPPPPPPB8ZBfC3hii1yO/O9vKfPPPPPPPPPPPPPPPPPPPNrp9U65nYW6XDzohiP/PPPPPPPPPPPPPPPPPPPCYBS0yjujq2x1KblAVvPPPPPPPPPPPPPPPPPPPOyuJ83Iym4MO0aIVrPvPPPPPPPPPPPPPPPPPPPPPJVuUcLoqIoTBE33PPPPPPPPPPPPPPPPPPPPPPPODqzsEs+pdUwF1fPPPPPPPPPPPPPPPPPPPPPPPPNh80ZjxNBH3O/PPPPPPPPPPPPPPPPPPPPPPPPPPTT9kIg9AZMHvPPPPPPPPPPPPPPPPPPPPPPPPPBWyipLDaokofPPPPPPPPPPPPPPPPPPPPPPPPPPINKm4T/jKhfPPPPPPPPPPPPPPPPPPPPPPPPPPOEfsJa0ffReQNfPPPPPPPPPPPPPPPPPPPPPPONZJmQ1UXUjITGy9PPPPPPPPPPPPPPPPPPPPPEaj37gzQOd3/ABSC88/8rXzzzzzzzzzzzzzzymgc/wCu7COy9BzjGumjzjTjDfx50888888844pT/DPhPV5tGk5OLb8vG2zzrvHbrfvhQw88k0nvbbXv6MBBRqGuWk38Di72DTDKjnfnnf8AzyzDzw543vu1DQQdbropvoj8vg064w+61z9+88/8z/rJw/v49UQVKEckuFMHuiOHv6ww1tg09zzzwpz28L372aWDGPIUn+1lADutHW2//wB8bOMcMMMfifN837/aV8kmAEWqPaqrQYjh/dtf8gWMc28d/j6vP7/W98kXMM0xTx+c3YAraCNuZoWD79/7z/2n78Nz3OpagkUp4F3vSPLqgJKR7vikGt7nEFT/AOhX7zjBd8j+hA8hB9BfjD+CiCAef/hgBBdBDd8+/ejD/8QAMBAAAQQBAwMDBAICAgMBAAAAAQACAxEEBRASEyBQISIxBjBAQSMyNEIUMxVRYYD/2gAIAQEAAQcC/wDwASApNUxIncc7XhHQdrmc4r/zecm67mhD6jyUPqR6i1+ByGtYRKiyYJh5+fOxscI/UeOHFZerPyXlGeynfPcFyKinkiNwfUGQwVD9RglQZUOQ0O8zlajjYgWbrs89tc9zzf3o55YioNYyopA7A1SLN9PLarqfRBike57ie4jYV9rHnfBIH6fq8c7Ku/KajnMxInKbILySe0bfpHtPp3D52wNbkxwGYubj5Y8lnZbcWJxyMmTIkLke0bBHel8J24X77CoZpIHB2m60ZXiLx+bmx4cZdm5smU8u2Pe0oobA/om1aKYiv1sCv/ScgVypaPqplAi8bkztxonSZ+c7Mfe19367bV9l0iey0TuxxYb0nVWTsEXiyQ0E6vqMs8ro/wAG1y+zezXFpvR84ZUAb4rWM4QR9OV/P7lKlVKlSpUgiuJK4n7On5bsSdr8edmRGH+IJoE6lOZsmQn7AajGgwrguCEa6S6aDAhGujaMBQjCbFSfCnR+qc2u8FaLm8JRF4jLdxx5TM63knvATVSATY7XSTYvRcF010xa6dIMC4os9bV36FnqnsCLa78VwbLGYzcbD4fU2vdiSCUUT3UmtQYgxAIMQaqQauK4qlS4LiqRYE6FFhC4KWNEd0FdRih/6o/EZF9GRZJPUd3NCYxBqAQaqVIBDspUqXFcVxTmKqUgBCkZSpUhvjs5yMEDOETG+Hk9WPWXQmf2DaMJrVXYEPuOajGpY04IBEUd9Orrxho9B4icF0UgyAWyPHYPVRNQCpAbV9w7lPbalZxOzh6qttBi55V+IPwVn+uVL2xstRsrcfgkKlPHaPodiqX0409d58TrTeOa/sYy1HHQ3tA9gCpUq7KVd722FNHTtyvp2MCF7vE/UEVZAfsxtqKOhuXhcggQhSAQaqVdlKtqVKtrV75Edpw2paVCIcSPxX1DFcbH7Y7P2iaT579LK9yPJBzwmZL2KPJY9Bw+x7UXsRkanPXO11KTZQdnCwpm047QRGWVjIm8I2N8TrERkxHbwimjZ7OS6AQhXRXQRx10F0y0prj6JjvTsKc5GQp8ryap5XB694XqqtEOCiffospvrtosfPLb4uVgkje3Jj6c8jW/KYPaN6QCA3pFoXAJvogdynWuCEIXTCELV0WrpLposXCtsn42+no285H+KllZC3lq00M+Ryj/ALBBDa0CuYXVahKxcx2AoII7WrQQQOx2O2QPZtoUbW43PxWtzerY523RhHvHYTSfPSdNIU8Hhyl/jjY6NkzcZkxdNDRa8SC0EEEU9WnzUnZMvAvZlZUgLocqd7bZmAmmyctjtkf0O2Dl5GO0LHl60TX+IJoE5UvXyXGQe1QN92wVoi10gui1HFY5HAYUMTjxUpkezjjxzRyK/Vck1yBRKcVkSFnthdVqSOb3jGdkwRyR43VhjLHY3I8mBzEwneYWxyjZzdTIvaVpT/YWeIzX8MaQx+6QmrBTBRPYAgFXcdmoHdzQVwQCaGhe1UEWKqVop3wVjM9zlE35GA7hklviNSF4r1EPc5NCqidwgO4oncIbVtSrf12O5UEdPu+L1A6stvicxvLHlUf93L4RNuO4Q7SUT2N+yFWxXwUVA32qQKD1yW+Jyf8AHlUf+x5ofKCCCHYU4q0SrQCa1VvXcEUUUVGfauX6xf8ALb4nUXccSVN/oUHJqCCCHY/0T5PVMBK6doRpkYQaumSnMIXF1priie2tiiiudBMf7lh/5jfE6l/iSKP9tMJDrTdgm9k49FRLkwfCCJTSgV1ETyXTCLU5AoKkAqRRRR+VK6goGuPuwPXLHicxvLGlDvQ2PVuzdgghvJ6p0SaaT3kiuhkg3E54Qci4oz5FqPId8Fyq1xooIIIoo7H5TGe1Pfxbx0qO5C7xEw5RPErPkNFDZu4Q2Lt6C4hUuAKDEBs6PkhGqRC+E1BEooor/ZcqQt7ytNZxivxOfjdKQu4oikN27F1bXvWw2vuIQNFWr2KKH9lwtRxEuAiZwja3xMkbZWlsmmzAlS4GQ1jnjb9obHcb2ECNwq7ZB+2HcoqIHkUI3OWNjdP18ZM3nG9tcXEL9pu8juK/5USE7HLmg5WrQcrVrmuouasLkE5/rTdjsVhxNbC1BoHj86Pp5LkEUPhAo+ieOSmw2lQxmEqHpucuEfWaJIme1CCNdBnNDHjUWM08lJC3q8Z42xUp5pGhNbnSFRY7meobsHI7Nbze1rRTQPH6rDbBINmqqR2ItFtIClxfzDjLK4JmTIAhlO53/wA30Kjy5YwQ573yc3e9NjTWBu5+FGyt9Pj5PL/ITM6kbmubwe5u53q1wTV6bftBBicEeXIACu/59MaLpRAeR1THp3VB2B7RtSoKmqggqH2LWDDzfz8lJG2VhbPEYJXM2vvpVsO87QxGd4EcYjaG+TzsUZEZNFpI7Ah3DuJVqNpleGwQNhbXlc3/ACZO0dw2pVtatEolaYy5SfLZ3+VJ2BDYdg7HFEq99LHtefLZn+TL2godoKtWi5F3bpf/AFv8sfhZHrPJ3AoPXIK1atcgi9X3aeKh8tI7i0rLi4nl+HhGhx8rm5AZxZkN5R99KlSpBqrvHyhP0SwtcHNB8nNK2FhdLmGbNaf7MUrODz30qVb13N9XLU3cY2rSdT+Igb8lNMyBhdqWqHJdxjd/MxRG2tWVDyF/gFRN/esv/o1shatN1nhUbJWSAHxrpGMU2s4cKm+onlZWo5GUrTD/ACMWOf42L5Cni4P7R9sN5FVQWrycsirQcoM+eFQa/OxQa9A9R5mPKrB8O5zWi8nWMXH9Mj6jldYkz55iuS5rkrTf7NWMf4o01Txc2IivQj70TKUpoFZT+eRIdwVaZM9ix9YyYaWJrMM9BrmuF+ClyYYQsv6gjZYydUychF5O99gWA7ljxpu2VB/t92NtlfCzZOELy424nstWrTX0sLVpcehjapjThNc135xICyNSxscLL1+R9iXKll9SftaS/ljgNKCIsLIh4G963rtHqomIrWJeMJH2bTZC1Q6lkRKHX3hQ6viypssb/wAmXLgiU+vwssZWs5M9p8rnK/uaI/8As1qbs9gcFLCWH7cbFSeVrMlvDft2g5B6izJo1Brk7FBrePJSjyYZfwiQpc3HhCn1+Jqn1vJkUmRI9clav7ujvqempqG0kYcFJEWH7LG/7Zea/msDP6v8cxoFZ8nVyHfetclyQemZMjFBreTGodficotRxpEHtd9t88Uam1fFiU/1CVNq2VKnTPcuSv8ABwpOnkMLDYCagnODReRmucahyOQ4ui/fcxl+uUT06eKUXJszHZ8vTxy4myT+BatWuSErgotRyI1Br8zaWPrOLKEyWKRWN3PawX/y8erydcx4lPruRIpMuaRF5KtX+I30IODJ1IGFqCzp3PdwaoGNQbSkhDvX47I4/wDYlZRNJwtY8VyBa7JUUbPxrVoPpR500SdqmU8hadrZsMBDgDqOrS5LiDM9clatWr/G0aTlDQTjxYU6LkhEbUUa9QgpouYvaJn+x9VSyRaESxme5a5LyyeP5bXUtG1LqNERP5miSVI5oU5/qAE2K0GUqXFWQp2gOuNvJy/+UqT47TmUoyI2SSZMpmme8flhY0pjkafywtPk6eSxN9aWQ7+YCJvoh6bBFEWnsv0awRoBBqr1TgpVqs3QxBH+aD+aw05pxnc44y48soqMU3sPYBsPlO+ERyctXn62UR5kbaU7ljNUYvIcW/CKCO/63Pwmp3wsmQY8Ej3nm5zvNBaI+8d4hZ6ksRQR3PYNtaZ/Aj5sLRJD72Magih8/a16X2tYft35ALR3ccoJo3/e4Vd2sS88kg+Tv7AWG/pzxmI20HY7t+O6V3CNxypOpM8+bCZ+jgTdSFiGxX62Hx3apN08Z6cb860rSpqHGN3IbHYf22PYCtdk9jWnzoWA+pFjv2OzhUg7rWtScpw0+ewv+wKP0pRmwjtVv25eq5tVoq6LlnydTJkPjx9/DdUrEwewKByOzP77O9CVSbZO07+PXMhtzj4D/8QAAv/aAAwDAQACAAMAAAAQ888888888888888888888888888888888888888888888888888888484w0888888888888888888888888888888442IdlFw+K8888888888888888888888888888posIU02YEca88888888888888888888888884Ha8kQs0oU0Yc4O088888888888888888888842w0c8QiCzumCtQo+8888888888888888888880JIMeRH0oXjUIlcE98888888888888888888889KNE4e1J7LKMEWKQAG88888888888888888888B80yK7wZsL3+vIWWQ288888888888888888888Uso1kBZNuPHXnLncc288888888888888888888cMbcplpvpH3JN93iKW88888888888888888888s2cTiRWyYm6Aicphh8888888888888888888888E8CLme0BL+L/BRhh888888888888888888888so7IJBDz0OTDksfFrn88888888888888888888rhaWFA5Cjv1TfcYPUR88888888888888888888TyyOXurQ9MomBpk/vu88888888888888888888djtijhr9OzIvPCyGIq88888888888888888888g+ncV/D6eiJnFWmRsM8888888888888888888876OIdgRUrD2eJDi/6/8888888888888888888888v0mTq/uc02AhJX888888888888888888888888lps7aEXHxqBhuk888888888888888888888888ywo0k6kkQuf7k88888888888888888888888888HXibSD8iCEmv88888888888888888888888888+0ldbZ2OmQl888888888888888888888888888XQnkCk5X6CP888888888888888888888888888NeFfGk27qunc88888888888888888888888886ENAUsiCc25wcXx088888888888888888852J9o5klZkw0yucZJttR1888888888888888rWZkVdotDQegkI6r34oEsdRoL7888888881iPod405wgVjXfMTIYmODgIUwUsRxpAj6x88brN5YkEckJAAMEfvrBwsKXVtMUIZEAExpthxEN048w4RgF9AAAAtKP3DozPb4MQgAdw4MQU8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', '381, Sampaga Balayan Batangas', '09772412273', '0918millan@gmail.com', 'millan.linkedin.com', 'Results-oriented Mechanical and Mechatronics Engineer seeking a challenging role to apply expertise in designing and implementing innovative solutions for complex engineering challenges.', '2026-03-22 07:26:01', '2026-03-22 07:26:01'),
(6, 7, 'Andre A. Cachola', '', '381, Sampaga Balayan Batangas', '09772412273', 'andre@gmail.com', 'millan.linkedin.com', 'Results-oriented Mechanical and Mechatronics Engineer seeking a challenging role to apply expertise in designing and implementing innovative solutions for complex engineering challenges.', '2026-03-22 09:22:49', '2026-03-22 09:22:49'),
(7, 8, 'Felman I. Eleponga', '', '381, Sampaga Balayan Batangas', '+639772412273', 'felman@gmail.com', 'www.reallygreatsite.com', 'Results-oriented Mechanical and Mechatronics Engineer seeking a challenging role to apply expertise in designing and implementing innovative solutions for complex engineering challenges.', '2026-03-22 09:42:57', '2026-03-22 09:42:57'),
(8, 9, '', '', '', '', '', '', '', '2026-04-30 11:32:44', '2026-04-30 11:32:44'),
(9, 10, 'Aeron Marc M. Salanguit', '', '343 Luna St.', '', 'saeronmarc@gmail.com', '', '', '2026-05-04 15:04:25', '2026-05-04 15:06:56'),
(10, 5, 'Benjamin Shah', '', '123 Anywhere St., Any City', '123-456-7890', 'hello@reallygreatsite.com', 'www.reallygreatsite.com', 'Results-oriented Mechanical and Mechatronics Engineer seeking a challenging role to apply expertise in designing and implementing innovative solutions for complex engineering challenges.', '2026-05-05 05:12:37', '2026-05-05 05:12:37');

-- --------------------------------------------------------

--
-- Table structure for table `resume_skills`
--

CREATE TABLE `resume_skills` (
  `skill_id` int(11) NOT NULL,
  `resume_id` int(11) NOT NULL,
  `skill_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resume_skills`
--

INSERT INTO `resume_skills` (`skill_id`, `resume_id`, `skill_name`) VALUES
(6, 1, 'Mechatronics System Integration'),
(7, 1, 'Automotive Engineering Technology'),
(8, 1, 'Robotics and Automation'),
(9, 1, 'CAD for Mechatronics'),
(10, 1, 'Project Management'),
(24, 6, 'valo'),
(25, 6, 'ml'),
(26, 7, 'test skill'),
(27, 7, 'skill 2'),
(28, 2, 'Java'),
(29, 2, 'Front End'),
(30, 2, 'Python'),
(31, 2, 'bago'),
(33, 8, 'javascript'),
(38, 9, 'javascript'),
(39, 9, 'html'),
(40, 9, 'java'),
(41, 9, 'css'),
(55, 10, 'Mechatronics System Integration'),
(56, 10, 'Automotive Engineering Technology'),
(57, 10, 'Robotics and Automation'),
(58, 10, 'CAD for Mechatronics'),
(59, 10, 'Project Management'),
(60, 10, 'Web Development'),
(61, 10, 'JavaScript'),
(62, 10, 'Java');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `application`
--
ALTER TABLE `application`
  ADD PRIMARY KEY (`application_id`),
  ADD KEY `job_post_id` (`job_post_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`employee_id`);

--
-- Indexes for table `employee_additional_info`
--
ALTER TABLE `employee_additional_info`
  ADD PRIMARY KEY (`info_id`),
  ADD KEY `resume_id` (`resume_id`);

--
-- Indexes for table `employee_education`
--
ALTER TABLE `employee_education`
  ADD PRIMARY KEY (`education_id`),
  ADD KEY `resume_id` (`resume_id`);

--
-- Indexes for table `employee_experience`
--
ALTER TABLE `employee_experience`
  ADD PRIMARY KEY (`experience_id`),
  ADD KEY `resume_id` (`resume_id`);

--
-- Indexes for table `employee_skill`
--
ALTER TABLE `employee_skill`
  ADD PRIMARY KEY (`employee_skill_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `employer`
--
ALTER TABLE `employer`
  ADD PRIMARY KEY (`employer_id`);

--
-- Indexes for table `experience_bullets`
--
ALTER TABLE `experience_bullets`
  ADD PRIMARY KEY (`bullet_id`),
  ADD KEY `experience_id` (`experience_id`);

--
-- Indexes for table `interview`
--
ALTER TABLE `interview`
  ADD PRIMARY KEY (`interview_id`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `interviews`
--
ALTER TABLE `interviews`
  ADD PRIMARY KEY (`interview_id`),
  ADD KEY `idx_application` (`application_id`),
  ADD KEY `idx_employer` (`employer_id`),
  ADD KEY `idx_employee` (`employee_id`);

--
-- Indexes for table `job_post`
--
ALTER TABLE `job_post`
  ADD PRIMARY KEY (`job_post_id`),
  ADD KEY `employer_id` (`employer_id`);

--
-- Indexes for table `message`
--
ALTER TABLE `message`
  ADD PRIMARY KEY (`message_id`);

--
-- Indexes for table `report`
--
ALTER TABLE `report`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `resumes`
--
ALTER TABLE `resumes`
  ADD PRIMARY KEY (`resume_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `resume_skills`
--
ALTER TABLE `resume_skills`
  ADD PRIMARY KEY (`skill_id`),
  ADD KEY `resume_id` (`resume_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `application`
--
ALTER TABLE `application`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `employee`
--
ALTER TABLE `employee`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `employee_additional_info`
--
ALTER TABLE `employee_additional_info`
  MODIFY `info_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `employee_education`
--
ALTER TABLE `employee_education`
  MODIFY `education_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `employee_experience`
--
ALTER TABLE `employee_experience`
  MODIFY `experience_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `employee_skill`
--
ALTER TABLE `employee_skill`
  MODIFY `employee_skill_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `employer`
--
ALTER TABLE `employer`
  MODIFY `employer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `experience_bullets`
--
ALTER TABLE `experience_bullets`
  MODIFY `bullet_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `interview`
--
ALTER TABLE `interview`
  MODIFY `interview_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `interviews`
--
ALTER TABLE `interviews`
  MODIFY `interview_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `job_post`
--
ALTER TABLE `job_post`
  MODIFY `job_post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `message`
--
ALTER TABLE `message`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `report`
--
ALTER TABLE `report`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resumes`
--
ALTER TABLE `resumes`
  MODIFY `resume_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `resume_skills`
--
ALTER TABLE `resume_skills`
  MODIFY `skill_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `application`
--
ALTER TABLE `application`
  ADD CONSTRAINT `application_ibfk_1` FOREIGN KEY (`job_post_id`) REFERENCES `job_post` (`job_post_id`),
  ADD CONSTRAINT `application_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`employee_id`);

--
-- Constraints for table `employee_additional_info`
--
ALTER TABLE `employee_additional_info`
  ADD CONSTRAINT `employee_additional_info_ibfk_1` FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`resume_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_education`
--
ALTER TABLE `employee_education`
  ADD CONSTRAINT `employee_education_ibfk_1` FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`resume_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_experience`
--
ALTER TABLE `employee_experience`
  ADD CONSTRAINT `employee_experience_ibfk_1` FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`resume_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_skill`
--
ALTER TABLE `employee_skill`
  ADD CONSTRAINT `employee_skill_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`employee_id`);

--
-- Constraints for table `experience_bullets`
--
ALTER TABLE `experience_bullets`
  ADD CONSTRAINT `experience_bullets_ibfk_1` FOREIGN KEY (`experience_id`) REFERENCES `employee_experience` (`experience_id`) ON DELETE CASCADE;

--
-- Constraints for table `interview`
--
ALTER TABLE `interview`
  ADD CONSTRAINT `interview_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `application` (`application_id`);

--
-- Constraints for table `job_post`
--
ALTER TABLE `job_post`
  ADD CONSTRAINT `job_post_ibfk_1` FOREIGN KEY (`employer_id`) REFERENCES `employer` (`employer_id`);

--
-- Constraints for table `report`
--
ALTER TABLE `report`
  ADD CONSTRAINT `report_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`);

--
-- Constraints for table `resumes`
--
ALTER TABLE `resumes`
  ADD CONSTRAINT `resumes_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`employee_id`);

--
-- Constraints for table `resume_skills`
--
ALTER TABLE `resume_skills`
  ADD CONSTRAINT `resume_skills_ibfk_1` FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`resume_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
