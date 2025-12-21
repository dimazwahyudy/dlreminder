-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 21, 2025 at 02:40 PM
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
-- Database: `FinalProject`
--

-- --------------------------------------------------------

--
-- Table structure for table `dosen`
--

CREATE TABLE `dosen` (
  `user_id` int(11) NOT NULL,
  `no_induk` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dosen`
--

INSERT INTO `dosen` (`user_id`, `no_induk`) VALUES
(11, '12345');

-- --------------------------------------------------------

--
-- Table structure for table `event`
--

CREATE TABLE `event` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `created_by` int(11) NOT NULL,
  `google_event_id` varchar(255) DEFAULT NULL,
  `visibility` varchar(32) NOT NULL DEFAULT 'self'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event`
--

INSERT INTO `event` (`id`, `title`, `description`, `start_time`, `end_time`, `created_by`, `google_event_id`, `visibility`) VALUES
(115, 'Dummy 1', '', '2025-12-01 09:00:00', '2025-12-03 10:00:00', 10, 'nj35b7gsbmmeej5p7oifnt39nk', 'all'),
(116, 'Dummy2', 'Test Dummy2', '2025-12-08 09:00:00', '2025-12-09 10:00:00', 10, '5al0r4srh3j53qnjioqf0gludc', 'all');

-- --------------------------------------------------------

--
-- Table structure for table `event_google_map`
--

CREATE TABLE `event_google_map` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `google_event_id` varchar(255) DEFAULT NULL,
  `pending_action` varchar(16) DEFAULT '',
  `pending_payload` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_google_map`
--

INSERT INTO `event_google_map` (`id`, `event_id`, `user_id`, `google_event_id`, `pending_action`, `pending_payload`) VALUES
(2, 114, 10, 'cphjccb5c9ij2bb674p6ab9k6lgj6b9p75h38b9m64q3adr1copj4cr368_20260923T023000Z', '', NULL),
(3, 115, 10, 'nj35b7gsbmmeej5p7oifnt39nk', '', NULL),
(4, 115, 11, '3oksfum6p55u38l1c6jg62tf24', '', NULL),
(5, 115, 12, NULL, '', NULL),
(6, 115, 13, NULL, '', NULL),
(7, 116, 10, '5al0r4srh3j53qnjioqf0gludc', '', NULL),
(8, 116, 11, 'cehptk2bu7io577vjim0108jv4', '', NULL),
(9, 116, 12, NULL, '', NULL),
(10, 116, 13, NULL, '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `google_token`
--

CREATE TABLE `google_token` (
  `user_id` int(11) NOT NULL,
  `access_token` text DEFAULT NULL,
  `refresh_token` text DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kelas`
--

CREATE TABLE `kelas` (
  `id` int(11) NOT NULL,
  `kode_kelas` varchar(45) NOT NULL,
  `nama_kelas` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kelas`
--

INSERT INTO `kelas` (`id`, `kode_kelas`, `nama_kelas`) VALUES
(1, 'IF24A', 'IF24A'),
(2, 'IF24B', 'IF24B'),
(3, 'IF24C', 'IF24C'),
(4, 'IF24D', 'IF24D'),
(5, 'IF24E', 'IF24E'),
(6, 'IF24F', 'IF24F'),
(7, 'SI24A', 'SI24A');

-- --------------------------------------------------------

--
-- Table structure for table `kelas_dosen`
--

CREATE TABLE `kelas_dosen` (
  `id` int(11) NOT NULL,
  `dosen_user_id` int(11) NOT NULL,
  `kelas_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kelas_dosen`
--

INSERT INTO `kelas_dosen` (`id`, `dosen_user_id`, `kelas_id`) VALUES
(1, 11, 1),
(2, 11, 2),
(3, 11, 3),
(4, 11, 4),
(5, 11, 5),
(6, 11, 6),
(7, 11, 7);

-- --------------------------------------------------------

--
-- Table structure for table `mahasiswa`
--

CREATE TABLE `mahasiswa` (
  `user_id` int(11) NOT NULL,
  `nim` varchar(45) NOT NULL,
  `prodi` varchar(45) DEFAULT NULL,
  `kelas_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mahasiswa`
--

INSERT INTO `mahasiswa` (`user_id`, `nim`, `prodi`, `kelas_id`) VALUES
(12, '12341', 'Informatika', 1),
(13, '12341231', 'Informatika', 1);

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `id` int(11) NOT NULL,
  `message` text NOT NULL,
  `sent_at` datetime NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `otp_requests`
--

CREATE TABLE `otp_requests` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `name`, `email`, `password`) VALUES
(10, 'Super Admin', 'dlreminderfp@gmail.com', '$2y$10$9D.EXNfSdj7foQoSUky.DeD2Njw8onX2UvSTjWDALIn33gPsBSi/e'),
(11, 'Dosen', 'if24.dimazwahyudy@mhs.ubpkarawang.ac.id', '$2y$10$JGIC9sPNRSDcJhdWCopxZeentyeHcL0on9ToPD6mPyzyTFyfYNbeO'),
(12, 'Mahasiswa1', 'if24.septianputra@mhs.ubpkarawang.ac.id', '$2y$10$ryMENmmWmGRMHwXicFLqFeacZq2aX9K5CcX7MZHmsvaXn.SYBoVJa'),
(13, 'Mahasiswa2', 'if24.nurmukhlisin@mhs.ubpkarawang.ac.id', '$2y$10$4bGlAOkNaGKOjmny6pRjXeMfuN7caaKxN0O9Isu.kScY87DiT3Wf2');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dosen`
--
ALTER TABLE `dosen`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `no_induk` (`no_induk`);

--
-- Indexes for table `event`
--
ALTER TABLE `event`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_event_user` (`created_by`);

--
-- Indexes for table `event_google_map`
--
ALTER TABLE `event_google_map`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_event_user` (`event_id`,`user_id`);

--
-- Indexes for table `google_token`
--
ALTER TABLE `google_token`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_kelas` (`kode_kelas`);

--
-- Indexes for table `kelas_dosen`
--
ALTER TABLE `kelas_dosen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dosen_user_id` (`dosen_user_id`,`kelas_id`),
  ADD KEY `fk_kelas_dosen_kelas` (`kelas_id`);

--
-- Indexes for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `nim` (`nim`),
  ADD KEY `fk_mahasiswa_kelas` (`kelas_id`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notification_user` (`receiver_id`),
  ADD KEY `fk_notification_event` (`event_id`);

--
-- Indexes for table `otp_requests`
--
ALTER TABLE `otp_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `event`
--
ALTER TABLE `event`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- AUTO_INCREMENT for table `event_google_map`
--
ALTER TABLE `event_google_map`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `kelas_dosen`
--
ALTER TABLE `kelas_dosen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `otp_requests`
--
ALTER TABLE `otp_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dosen`
--
ALTER TABLE `dosen`
  ADD CONSTRAINT `fk_dosen_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event`
--
ALTER TABLE `event`
  ADD CONSTRAINT `fk_event_user` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `google_token`
--
ALTER TABLE `google_token`
  ADD CONSTRAINT `fk_google_token_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kelas_dosen`
--
ALTER TABLE `kelas_dosen`
  ADD CONSTRAINT `fk_kelas_dosen_dosen` FOREIGN KEY (`dosen_user_id`) REFERENCES `dosen` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_kelas_dosen_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD CONSTRAINT `fk_mahasiswa_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`),
  ADD CONSTRAINT `fk_mahasiswa_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `fk_notification_event` FOREIGN KEY (`event_id`) REFERENCES `event` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notification_user` FOREIGN KEY (`receiver_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
