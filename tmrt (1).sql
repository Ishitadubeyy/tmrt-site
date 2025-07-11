-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 11, 2025 at 05:56 PM
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
-- Database: `tmrt`
--

-- --------------------------------------------------------

--
-- Table structure for table `password_history`
--

CREATE TABLE `password_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `hashed_password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_history`
--

INSERT INTO `password_history` (`id`, `user_id`, `hashed_password`, `created_at`) VALUES
(1, 2, '$2y$10$JKk7uqtZ5gLXiPJszc2i9.h2hc10VogfxI2HKWAALZQeUOWaLepfS', '2025-05-31 18:56:01'),
(2, 3, '$2y$10$NLgwIzP0pcXK8tfL0aYFUOxTmZb3tOSll6TlthBAXc1cB5hu5ofHO', '2025-06-04 15:03:11');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `password_last_changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `contact`, `password`, `password_last_changed_at`, `created_at`, `reset_token`, `reset_expires`) VALUES
(1, 'Ishita Dubey', 'ishita.dubey@somaiya.edu', '9969004931', '$2y$10$hH3.0URkuAcKX7dlFRlhYehrQ9uvy5cc4xc6zUqx6n1lOgdfKFwsm', '2025-05-31 18:54:47', '2025-05-21 13:20:30', NULL, NULL),
(2, 'Ishita Dubey', 'hellobrother@vampire.com', '8023289621', '$2y$10$JKk7uqtZ5gLXiPJszc2i9.h2hc10VogfxI2HKWAALZQeUOWaLepfS', '2025-05-31 15:26:00', '2025-05-31 18:56:00', NULL, NULL),
(3, 'Ishita Dubey', 'ishitadubey2808@gmail.com', '8080232896', '$2y$10$NLgwIzP0pcXK8tfL0aYFUOxTmZb3tOSll6TlthBAXc1cB5hu5ofHO', '2025-06-04 11:33:11', '2025-06-04 15:03:11', 'ed3c54f72067ccc3bc5ae4807a0269f19e47f0d53c7793fd239cfbbf881639763eb75824207047d6549925e424d6b19a5ede', '2025-06-04 18:03:21');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `password_history`
--
ALTER TABLE `password_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `password_history`
--
ALTER TABLE `password_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `password_history`
--
ALTER TABLE `password_history`
  ADD CONSTRAINT `password_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
