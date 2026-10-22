-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 22, 2026 at 07:47 AM
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
-- Database: `bytebite_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `appliances`
--

CREATE TABLE `appliances` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `appliance_name` varchar(50) NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `appliances`
--

INSERT INTO `appliances` (`id`, `user_id`, `appliance_name`, `is_available`, `created_at`) VALUES
(1, 1, 'Rice Cooker', 1, '2026-10-20 13:27:16'),
(2, 1, 'Air Fryer', 1, '2026-10-20 13:27:16'),
(3, 1, 'Blender', 1, '2026-10-20 13:27:16'),
(4, 1, 'Microwave', 1, '2026-10-20 13:27:16'),
(5, 2, 'Electric Stove', 1, '2026-10-20 13:27:16'),
(6, 2, 'Refrigerator', 1, '2026-10-20 13:27:16');

-- --------------------------------------------------------

--
-- Table structure for table `health_profiles`
--

CREATE TABLE `health_profiles` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `dietary_goal` varchar(100) NOT NULL DEFAULT 'High Protein',
  `portion_size` enum('Small','Medium','Large') NOT NULL DEFAULT 'Medium',
  `daily_calorie_target` int(10) UNSIGNED NOT NULL DEFAULT 2000,
  `protein_target` decimal(8,2) NOT NULL DEFAULT 150.00,
  `carbs_target` decimal(8,2) NOT NULL DEFAULT 200.00,
  `fat_target` decimal(8,2) NOT NULL DEFAULT 70.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `health_profiles`
--

INSERT INTO `health_profiles` (`id`, `user_id`, `dietary_goal`, `portion_size`, `daily_calorie_target`, `protein_target`, `carbs_target`, `fat_target`, `created_at`, `updated_at`) VALUES
(1, 1, 'Balanced Diet', 'Medium', 2000, 150.00, 200.00, 70.00, '2026-10-20 13:27:16', '2026-10-20 13:27:16'),
(2, 2, 'Muscle Gain', 'Large', 2500, 180.00, 280.00, 80.00, '2026-10-20 13:27:16', '2026-10-20 13:27:16');

-- --------------------------------------------------------

--
-- Table structure for table `ingredients`
--

CREATE TABLE `ingredients` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(20) NOT NULL,
  `category` enum('Vegetables','Fruits','Meat','Dairy','Grains','Pantry') NOT NULL DEFAULT 'Pantry',
  `expiry_date` date DEFAULT NULL,
  `low_stock` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ingredients`
--

INSERT INTO `ingredients` (`id`, `user_id`, `name`, `quantity`, `unit`, `category`, `expiry_date`, `low_stock`, `created_at`, `updated_at`) VALUES
(1, 1, 'Eggs', 12.00, 'pcs', 'Pantry', '2026-10-30', 0, '2026-10-20 13:27:16', '2026-10-20 13:27:16'),
(2, 1, 'Chicken Breast', 1.00, 'kg', 'Pantry', '2026-10-25', 0, '2026-10-20 13:27:16', '2026-10-20 13:27:16'),
(3, 1, 'Broccoli', 2.00, 'pcs', 'Pantry', '2026-10-23', 1, '2026-10-20 13:27:16', '2026-10-20 13:27:16'),
(4, 1, 'Milk', 1.00, 'liter', 'Pantry', '2026-10-28', 0, '2026-10-20 13:27:16', '2026-10-20 13:27:16'),
(5, 1, 'Rice', 5.00, 'kg', 'Pantry', NULL, 0, '2026-10-20 13:27:16', '2026-10-20 13:27:16'),
(6, 1, 'Garlic', 3.00, 'pcs', 'Pantry', '2026-11-05', 0, '2026-10-20 13:27:16', '2026-10-20 13:27:16'),
(7, 1, 'Onion', 4.00, 'pcs', 'Pantry', '2026-11-01', 0, '2026-10-20 13:27:16', '2026-10-20 13:27:16'),
(8, 2, 'Tomatoes', 5.00, 'pcs', 'Pantry', '2026-10-25', 0, '2026-10-20 13:27:16', '2026-10-20 13:27:16'),
(9, 2, 'Carrots', 3.00, 'pcs', 'Pantry', '2026-10-27', 0, '2026-10-20 13:27:16', '2026-10-20 13:27:16'),
(10, 2, 'Spinach', 2.00, 'bunches', 'Pantry', '2026-10-24', 1, '2026-10-20 13:27:16', '2026-10-20 13:27:16'),
(11, 1, 'Egg', 1.00, 'pcs', 'Pantry', '2026-12-16', 0, '2026-10-21 04:14:53', '2026-10-21 04:14:53');

-- --------------------------------------------------------

--
-- Table structure for table `nutrition_logs`
--

CREATE TABLE `nutrition_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `log_date` date NOT NULL,
  `calories` decimal(10,2) NOT NULL DEFAULT 0.00,
  `protein` decimal(10,2) NOT NULL DEFAULT 0.00,
  `carbs` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fat` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `nutrition_logs`
--

INSERT INTO `nutrition_logs` (`id`, `user_id`, `log_date`, `calories`, `protein`, `carbs`, `fat`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-10-18', 1850.00, 125.00, 210.00, 55.00, '2026-10-20 13:27:16', '2026-10-20 13:27:16'),
(2, 1, '2026-10-19', 1950.00, 140.00, 220.00, 60.00, '2026-10-20 13:27:16', '2026-10-20 13:27:16'),
(3, 1, '2026-10-20', 1200.00, 85.00, 130.00, 35.00, '2026-10-20 13:27:16', '2026-10-20 13:27:16'),
(4, 2, '2026-10-18', 2300.00, 170.00, 260.00, 75.00, '2026-10-20 13:27:16', '2026-10-20 13:27:16'),
(5, 2, '2026-10-19', 2450.00, 175.00, 275.00, 78.00, '2026-10-20 13:27:16', '2026-10-20 13:27:16'),
(6, 2, '2026-10-20', 1600.00, 120.00, 180.00, 45.00, '2026-10-20 13:27:16', '2026-10-20 13:27:16');

-- --------------------------------------------------------

--
-- Table structure for table `recipes`
--

CREATE TABLE `recipes` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `recipe_name` varchar(200) NOT NULL,
  `instructions` text DEFAULT NULL,
  `appliance_name` varchar(50) DEFAULT NULL,
  `portion_size` enum('Small','Medium','Large') NOT NULL DEFAULT 'Medium',
  `calories` decimal(10,2) NOT NULL DEFAULT 0.00,
  `protein` decimal(10,2) NOT NULL DEFAULT 0.00,
  `carbs` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fat` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `recipes`
--

INSERT INTO `recipes` (`id`, `user_id`, `recipe_name`, `instructions`, `appliance_name`, `portion_size`, `calories`, `protein`, `carbs`, `fat`, `created_at`) VALUES
(1, 1, 'Chicken and Broccoli Bowl', 'Cook the chicken thoroughly. Steam the broccoli and serve with cooked rice.', 'Rice Cooker', 'Medium', 450.00, 38.00, 52.00, 10.00, '2026-10-20 13:27:16'),
(2, 1, 'Scrambled Eggs', 'Beat the eggs and cook in a pan until fully set.', 'Electric Stove', 'Small', 180.00, 12.00, 2.00, 13.00, '2026-10-20 13:27:16'),
(3, 2, 'Tomato Carrot Salad', 'Wash and chop the tomatoes and carrots, then mix together.', NULL, 'Medium', 120.00, 3.00, 22.00, 3.00, '2026-10-20 13:27:16');

-- --------------------------------------------------------

--
-- Table structure for table `recipe_ingredients`
--

CREATE TABLE `recipe_ingredients` (
  `id` int(10) UNSIGNED NOT NULL,
  `recipe_id` int(10) UNSIGNED NOT NULL,
  `ingredient_id` int(10) UNSIGNED DEFAULT NULL,
  `ingredient_name` varchar(150) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `recipe_ingredients`
--

INSERT INTO `recipe_ingredients` (`id`, `recipe_id`, `ingredient_id`, `ingredient_name`, `quantity`, `unit`) VALUES
(1, 1, 2, 'Chicken Breast', 200.00, 'g'),
(2, 1, 3, 'Broccoli', 1.00, 'pcs'),
(3, 1, 5, 'Rice', 100.00, 'g'),
(4, 1, 6, 'Garlic', 2.00, 'cloves'),
(5, 2, 1, 'Eggs', 2.00, 'pcs'),
(6, 3, 8, 'Tomatoes', 2.00, 'pcs'),
(7, 3, 9, 'Carrots', 1.00, 'pcs');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(100) NOT NULL DEFAULT 'ByteBite User',
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `email_verification_code_hash` varchar(255) DEFAULT NULL,
  `email_verification_expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `created_at`, `updated_at`, `email_verified`, `email_verification_code_hash`, `email_verification_expires_at`) VALUES
(1, 'Admin', 'admin@bytebite.com', '$2y$10$npj4DmjoRwP7D8FB7HqcwOWNpZ9dzEsHs2ZaX7/cRyAiLqugOyYHS', '2026-10-20 13:27:16', '2026-10-20 14:20:55', 0, NULL, NULL),
(2, 'Juan Dela Cruz', 'juan@example.com', '$2y$10$5m.P5gDWdFFZBBHep2ye8eVmApHw3dNChUwuvXKzFlvLbllZpt8Cy', '2026-10-20 13:27:16', '2026-10-20 14:20:55', 0, NULL, NULL),
(3, 'Jazz Martin Escala', 'escalajazzmartin@gmail.com', '$2y$10$3mbxHUIFupuWgugSSlA5ouIFWuMj0Dm/KJZHQSGlpG0hkOXbCDP/.', '2026-10-21 05:39:45', '2026-10-21 05:50:54', 1, NULL, NULL),
(4, 'Rose Calingo', 'rosecalingo05@gmail.com', '$2y$10$L3pTovNoa5ca0RlB7P2Ed.eYy7E1xb1lU9.dFggCyKTgE1ETztQwm', '2026-10-21 05:53:15', '2026-10-21 05:53:15', 0, '$2y$10$bUIgDfwWigyK28MGS5wev.VrkmaVHq6uRuS.agOeqwNoDM6Q9fnrq', '2026-10-21 08:03:15');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appliances`
--
ALTER TABLE `appliances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_appliance` (`user_id`,`appliance_name`);

--
-- Indexes for table `health_profiles`
--
ALTER TABLE `health_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `ingredients`
--
ALTER TABLE `ingredients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ingredient_name` (`name`),
  ADD KEY `idx_ingredient_expiry` (`expiry_date`),
  ADD KEY `idx_ingredient_low_stock` (`low_stock`),
  ADD KEY `idx_ingredient_user` (`user_id`);

--
-- Indexes for table `nutrition_logs`
--
ALTER TABLE `nutrition_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_nutrition_date` (`user_id`,`log_date`);

--
-- Indexes for table `recipes`
--
ALTER TABLE `recipes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_recipe_user` (`user_id`);

--
-- Indexes for table `recipe_ingredients`
--
ALTER TABLE `recipe_ingredients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_recipe_ingredient_recipe` (`recipe_id`),
  ADD KEY `fk_recipe_ingredient_inventory` (`ingredient_id`);

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
-- AUTO_INCREMENT for table `appliances`
--
ALTER TABLE `appliances`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `health_profiles`
--
ALTER TABLE `health_profiles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ingredients`
--
ALTER TABLE `ingredients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `nutrition_logs`
--
ALTER TABLE `nutrition_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `recipes`
--
ALTER TABLE `recipes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `recipe_ingredients`
--
ALTER TABLE `recipe_ingredients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appliances`
--
ALTER TABLE `appliances`
  ADD CONSTRAINT `fk_appliance_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `health_profiles`
--
ALTER TABLE `health_profiles`
  ADD CONSTRAINT `fk_health_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ingredients`
--
ALTER TABLE `ingredients`
  ADD CONSTRAINT `fk_ingredients_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nutrition_logs`
--
ALTER TABLE `nutrition_logs`
  ADD CONSTRAINT `fk_nutrition_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `recipes`
--
ALTER TABLE `recipes`
  ADD CONSTRAINT `fk_recipe_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `recipe_ingredients`
--
ALTER TABLE `recipe_ingredients`
  ADD CONSTRAINT `fk_recipe_ingredient_inventory` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_recipe_ingredient_recipe` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
