-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 19, 2026 at 09:37 PM
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
-- Database: `healthcare_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE `bills` (
  `id` int(10) UNSIGNED NOT NULL,
  `patient_id` varchar(20) NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('unpaid','paid') NOT NULL DEFAULT 'unpaid',
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bills`
--

INSERT INTO `bills` (`id`, `patient_id`, `description`, `amount`, `status`, `created_by`, `created_at`) VALUES
(1, 'PT-2026-327D6', 'Blood tests', 3500.00, 'unpaid', 2, '2026-07-18 14:19:43'),
(2, 'PT-2026-5B2FF', 'tablets', 500.00, 'unpaid', 13, '2026-07-19 13:04:37'),
(3, 'PT-2026-A6115', 'Medication', 1500.00, 'unpaid', 6, '2026-07-19 19:26:08');

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `dosage_instructions` varchar(150) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_qty` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`id`, `name`, `description`, `dosage_instructions`, `category`, `price`, `stock_qty`, `is_active`, `created_at`) VALUES
(1, 'Paracetamol 500mg', 'Pain relief and fever reducer, strip of 10 tablets', NULL, 'Pain Relief', 25.00, 200, 1, '2026-07-19 11:06:18'),
(2, 'Amoxicillin 250mg', 'Antibiotic capsules, strip of 10', NULL, 'Antibiotics', 60.00, 120, 1, '2026-07-19 11:06:18'),
(3, 'Cetirizine 10mg', 'Antihistamine for allergy relief, strip of 10', NULL, 'Allergy', 30.00, 150, 1, '2026-07-19 11:06:18'),
(4, 'Vitamin D3 Softgel', 'Bone health supplement, bottle of 30', NULL, 'Supplements', 180.00, 80, 1, '2026-07-19 11:06:18'),
(5, 'ORS Sachet', 'Oral rehydration salts, box of 10 sachets', NULL, 'General', 45.00, 300, 1, '2026-07-19 11:06:18'),
(6, 'Paracetamol 500mg', 'Fever and pain relief tablet, strip of 10', 'Take with water after food, every 6-8 hours', 'Fever', 25.00, 200, 1, '2026-07-19 11:07:11'),
(7, 'Paracetamol 650mg', 'Stronger fever/pain relief tablet, strip of 10', 'Take with water after food, every 6-8 hours', 'Fever', 32.00, 179, 1, '2026-07-19 11:07:11'),
(8, 'Ibuprofen 400mg', 'Fever, pain and inflammation relief, strip of 10', 'Take with water, always after food', 'Fever', 40.00, 140, 1, '2026-07-19 11:07:11'),
(9, 'Paracetamol Syrup (Children)', 'Fever relief syrup for children, 60ml bottle', 'Take with a measuring cup as directed, after food', 'Fever', 55.00, 90, 1, '2026-07-19 11:07:11'),
(10, 'Ferrous Sulfate 325mg', 'Iron supplement for anemia, strip of 10', 'Take with water or juice, preferably on an empty stomach', 'Anemia', 45.00, 130, 1, '2026-07-19 11:07:11'),
(11, 'Folic Acid 5mg', 'Supports red blood cell production, strip of 10', 'Take with water, once daily', 'Anemia', 20.00, 160, 1, '2026-07-19 11:07:11'),
(12, 'Iron + Folic Acid Combo', 'Combined iron and folic acid tablets, strip of 15', 'Take with water after food to reduce stomach upset', 'Anemia', 60.00, 100, 1, '2026-07-19 11:07:11'),
(13, 'Vitamin B12 Tablets', 'Supports nerve and blood cell health, strip of 10', 'Take with water, once daily', 'Anemia', 50.00, 110, 1, '2026-07-19 11:07:11'),
(14, 'Diclofenac 50mg', 'Pain and inflammation relief for body aches, strip of 10', 'Take with water after food', 'Pain Relief', 35.00, 150, 1, '2026-07-19 11:07:11'),
(15, 'Aceclofenac + Paracetamol', 'Combination tablet for body pain and fever, strip of 10', 'Take with water after food, twice daily', 'Pain Relief', 48.00, 120, 1, '2026-07-19 11:07:11'),
(16, 'Muscle Relaxant Tablet', 'Relieves muscle spasm and body stiffness, strip of 10', 'Take with water after food', 'Pain Relief', 55.00, 95, 1, '2026-07-19 11:07:11'),
(17, 'Pain Relief Gel', 'Topical gel for localized body pain, 30g tube', 'Apply externally on affected area, 2-3 times daily', 'Pain Relief', 70.00, 85, 1, '2026-07-19 11:07:11'),
(18, 'Ciprofloxacin 500mg', 'Antibiotic used for typhoid and bacterial infections, strip of 10', 'Take with water, avoid dairy products near dosing time', 'Typhoid', 65.00, 100, 1, '2026-07-19 11:07:11'),
(19, 'Azithromycin 500mg', 'Antibiotic commonly used for typhoid fever, strip of 3', 'Take with water, once daily on empty stomach', 'Typhoid', 85.00, 90, 1, '2026-07-19 11:07:11'),
(20, 'Ofloxacin 200mg', 'Antibiotic for typhoid and related infections, strip of 10', 'Take with water after food, twice daily', 'Typhoid', 58.00, 80, 1, '2026-07-19 11:07:11'),
(21, 'Amoxicillin 500mg', 'Antibiotic used for pneumonia and respiratory infections, strip of 10', 'Take with water after food, every 8 hours', 'Pneumonia', 70.00, 110, 1, '2026-07-19 11:07:11'),
(22, 'Azithromycin 250mg', 'Antibiotic for respiratory tract infections, strip of 6', 'Take with water, once daily on empty stomach', 'Pneumonia', 95.00, 75, 1, '2026-07-19 11:07:11'),
(23, 'Cough & Chest Congestion Syrup', 'Relieves cough and chest congestion, 100ml bottle', 'Take with a measuring cup as directed, after food', 'Pneumonia', 60.00, 100, 1, '2026-07-19 11:07:11'),
(24, 'Levothyroxine 50mcg', 'Thyroid hormone replacement for hypothyroidism, strip of 10', 'Take with water on an empty stomach, 30 mins before breakfast', 'Thyroid', 40.00, 130, 1, '2026-07-19 11:07:11'),
(25, 'Levothyroxine 100mcg', 'Higher-dose thyroid hormone replacement, strip of 10', 'Take with water on an empty stomach, 30 mins before breakfast', 'Thyroid', 48.00, 110, 1, '2026-07-19 11:07:11'),
(26, 'Methimazole 5mg', 'Used to manage hyperthyroidism, strip of 10', 'Take with water after food, as directed by physician', 'Thyroid', 55.00, 70, 1, '2026-07-19 11:07:11'),
(27, 'ORS Sachet', 'Oral rehydration salts for dehydration, box of 10 sachets', 'Mix one sachet with 1 liter clean water, drink through the day', 'General', 45.00, 250, 1, '2026-07-19 11:07:11'),
(28, 'Vitamin D3 Softgel', 'Bone health supplement, bottle of 30', 'Take with water after food, once weekly or as directed', 'Supplements', 180.00, 80, 1, '2026-07-19 11:07:11'),
(29, 'Cetirizine 10mg', 'Antihistamine for allergy relief, strip of 10', 'Take with water, preferably at night', 'Allergy', 30.00, 150, 1, '2026-07-19 11:07:11');

-- --------------------------------------------------------

--
-- Table structure for table `medicine_orders`
--

CREATE TABLE `medicine_orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `patient_id` varchar(20) NOT NULL,
  `delivery_type` enum('home_delivery','pickup') NOT NULL DEFAULT 'pickup',
  `delivery_address` varchar(255) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','paid') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `medicine_orders`
--

INSERT INTO `medicine_orders` (`id`, `patient_id`, `delivery_type`, `delivery_address`, `total_amount`, `payment_status`, `created_at`) VALUES
(1, 'PT-2026-327D6', 'pickup', NULL, 32.00, 'paid', '2026-07-19 11:08:50');

-- --------------------------------------------------------

--
-- Table structure for table `medicine_order_items`
--

CREATE TABLE `medicine_order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `medicine_id` int(10) UNSIGNED NOT NULL,
  `medicine_name` varchar(150) NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `medicine_order_items`
--

INSERT INTO `medicine_order_items` (`id`, `order_id`, `medicine_id`, `medicine_name`, `quantity`, `unit_price`, `subtotal`) VALUES
(1, 1, 7, 'Paracetamol 650mg', 1, 32.00, 32.00);

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(10) UNSIGNED NOT NULL,
  `patient_id` varchar(20) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('Male','Female','Other','Prefer not to say') NOT NULL,
  `contact_info` varchar(150) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `prescribed_medicines` text DEFAULT NULL,
  `next_appointment_date` date DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `registered_by` int(10) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `patient_id`, `full_name`, `date_of_birth`, `gender`, `contact_info`, `address`, `medical_history`, `prescribed_medicines`, `next_appointment_date`, `password_hash`, `registered_by`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'PT-2026-327D6', 'Sahil Chakane', '2007-06-15', 'Male', '7499304308', 'Nashik maharashtra', 'Illness , Anemia , selamonella typhi detection', NULL, NULL, '$2y$10$zmcPf6OIFcw.7mJqr7HYw.CEHYEjRYn3pVpF6DW/fmcwwko5mCn5e', 2, 1, '2026-07-18 13:51:03', '2026-07-18 13:51:03'),
(2, 'PT-2026-5B2FF', 'Krishna Chaudhari', '2006-04-15', 'Male', '9763508967', 'Malegaon ,  nashik , mahrashtra', 'Weakness', NULL, NULL, '$2y$10$9JtEZbP5X3XBy0LogI9Xlu4PnwBY97yFSIJMuB6hEJ22.4wYc//om', 13, 1, '2026-07-19 13:04:11', '2026-07-19 13:04:11'),
(3, 'PT-2026-A6115', 'Vipin Kumar Sharma', '1999-04-05', 'Male', '7499304305', 'greater noida ,vishwakarma aparment, flat no 6', 'Lose motions', 'Spassmonil\r\nMefamin spass\r\nstreptococus thermophilli powder', '2026-03-08', '$2y$10$ll/LjtlQCXlm9Igljc0dWOXEhm6emA.SPHVpG6GFUGWTVI609ZA.G', 6, 1, '2026-07-19 19:25:45', '2026-07-19 19:25:45');

-- --------------------------------------------------------

--
-- Table structure for table `patient_records`
--

CREATE TABLE `patient_records` (
  `id` int(10) UNSIGNED NOT NULL,
  `patient_id` varchar(20) NOT NULL,
  `record_type` enum('vital','prescription','disease_log') NOT NULL,
  `title` varchar(150) NOT NULL,
  `details` text NOT NULL,
  `recorded_by` int(10) UNSIGNED NOT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `role` enum('employee','staff','admin') NOT NULL DEFAULT 'staff',
  `specialty` varchar(100) DEFAULT NULL,
  `availability` enum('available','not_available') NOT NULL DEFAULT 'available',
  `email` varchar(120) DEFAULT NULL,
  `can_provision_credentials` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `role`, `specialty`, `availability`, `email`, `can_provision_credentials`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 'admin', '$2y$10$j1PKWMdus40LbrvmuvyPc.85AvePgI4cmM/n4qyJxrdzOhiTqlpOu', 'System Administrator', 'admin', NULL, 'available', NULL, 1, 1, '2026-07-18 13:47:26', '2026-07-18 13:47:26'),
(3, 'Ashit', '$2y$10$J9F.FYgiZcVa6iz07EeeD.YFsHgtlhQ6AhVE8biqTAlG4t2NrdiNe', 'Ashit Gajbhiye', 'employee', NULL, 'available', NULL, 1, 1, '2026-07-18 14:33:47', '2026-07-19 12:55:34'),
(4, 'Aryan', '$2y$10$joTWqH7Wjag9MB.dUpKvY.BDTsvmoCgWt0tQH9Ohj/Fyebk8HPBaK', 'Aryan Chaudhari', 'employee', NULL, 'available', NULL, 1, 1, '2026-07-18 14:33:47', '2026-07-19 12:56:46'),
(5, 'Rajdeep', '$2y$10$LvJr1rXsJI.g5rUvotm0A.UCrDCbMOq/aHQs2730CHSeSLgVx00P6', 'Rajdeep Yadav', 'employee', NULL, 'available', NULL, 1, 1, '2026-07-18 14:33:47', '2026-07-19 12:57:10'),
(6, 'Sanket', '$2y$10$3P8QNha4giuYlbTGtn6ksubOUuxHXq3vy.tPi4vkAezEmZXaYO8Ha', 'sanket Zade', 'employee', NULL, 'available', NULL, 1, 1, '2026-07-18 14:33:47', '2026-07-19 12:57:43'),
(7, 'Onkar', '$2y$10$mbLMiehiaohF3Fs36BkJZ.aK0R2GIdIml55fl6ahWM2bmG51BxUWC', 'onkar Kakde', 'employee', NULL, 'available', NULL, 1, 1, '2026-07-18 14:33:47', '2026-07-19 12:58:59'),
(8, 'Vijay', '$2y$10$3wdtfMyLBwKfwuYSQL5fm.Hey1DADF2AKQxPS.r3SxhK7RUFgIH7q', 'Nurse Sonkar', 'staff', NULL, 'available', NULL, 1, 1, '2026-07-18 14:33:47', '2026-07-19 12:59:50'),
(9, 'Jayesh', '$2y$10$EFqI1Adr36.M/r32cGzItObj.A4X7RUNvtsob2xwgWzX7J0Q4Skbq', 'Nurse Khavle', 'staff', NULL, 'available', NULL, 1, 1, '2026-07-18 14:33:47', '2026-07-19 12:59:27'),
(10, 'Sahil', '$2y$10$8iu595ztd7IRpM.rtQA64uAjQIKj/66uWr3rjrUxvYI9WgF/6fSsa', 'Nurse Chakane', 'staff', NULL, 'available', NULL, 1, 1, '2026-07-18 14:33:47', '2026-07-19 12:58:09'),
(11, 'Tanuja', '$2y$10$QMasJwEt91NJauwgHOakZusRfjXxFKBRdNgmSynLD6sqgLRjptfNS', 'nurse tanuja', 'staff', NULL, 'available', NULL, 1, 1, '2026-07-18 14:33:47', '2026-07-19 12:58:32'),
(12, 'Sakshi', '$2y$10$7fwYiy9rCxq0ufjgliF8rOrBjwoNbvHIndThVssNG5zICH3oa18/6', 'Nurse Moore', 'staff', NULL, 'available', NULL, 1, 1, '2026-07-18 14:33:47', '2026-07-18 14:33:47'),
(13, 'dr.yuvraj', '$2y$10$7SXEzs8cF/DXQPAPVOlTFeVGZoFlY8tQ3gSLYQlstOz3bxWWowmE.', 'Dr. Yuvraj Deshmukh', 'employee', 'Cardiology', 'not_available', NULL, 1, 1, '2026-07-18 14:53:33', '2026-07-19 11:38:46'),
(14, 'dr.hrishikesh', '$2y$10$c53GprmB1/nQmG8WQ7evNu1w7kEs3cqKHf3Jix/URYDtEGI2TWd6y', 'Dr. Hrishikesh Deshmukh', 'employee', NULL, 'available', NULL, 1, 1, '2026-07-18 14:56:08', '2026-07-18 15:05:56');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bills`
--
ALTER TABLE `bills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_bills_patient` (`patient_id`),
  ADD KEY `fk_bills_staff` (`created_by`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `medicine_orders`
--
ALTER TABLE `medicine_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orders_patient` (`patient_id`);

--
-- Indexes for table `medicine_order_items`
--
ALTER TABLE `medicine_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orderitems_order` (`order_id`),
  ADD KEY `fk_orderitems_medicine` (`medicine_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `patient_id` (`patient_id`),
  ADD KEY `fk_patients_registered_by` (`registered_by`);

--
-- Indexes for table `patient_records`
--
ALTER TABLE `patient_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_records_patient` (`patient_id`),
  ADD KEY `fk_records_staff` (`recorded_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bills`
--
ALTER TABLE `bills`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `medicine_orders`
--
ALTER TABLE `medicine_orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `medicine_order_items`
--
ALTER TABLE `medicine_order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `patient_records`
--
ALTER TABLE `patient_records`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bills`
--
ALTER TABLE `bills`
  ADD CONSTRAINT `fk_bills_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bills_staff` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `medicine_orders`
--
ALTER TABLE `medicine_orders`
  ADD CONSTRAINT `fk_orders_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `medicine_order_items`
--
ALTER TABLE `medicine_order_items`
  ADD CONSTRAINT `fk_orderitems_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orderitems_order` FOREIGN KEY (`order_id`) REFERENCES `medicine_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `fk_patients_registered_by` FOREIGN KEY (`registered_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `patient_records`
--
ALTER TABLE `patient_records`
  ADD CONSTRAINT `fk_records_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_records_staff` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
