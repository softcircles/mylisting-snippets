-- phpMyAdmin SQL Dump
-- version 5.1.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 29, 2022 at 07:59 AM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 7.4.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `my-listing`
--

-- --------------------------------------------------------

--
-- Table structure for table `wp_mylisting_locations`
--

CREATE TABLE `wp_mylisting_locations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `listing_id` bigint(20) UNSIGNED NOT NULL,
  `address` varchar(300) NOT NULL,
  `lat` decimal(8,5) NOT NULL,
  `lng` decimal(8,5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `wp_mylisting_workhours`
--

CREATE TABLE `wp_mylisting_workhours` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `listing_id` bigint(20) UNSIGNED NOT NULL,
  `start` smallint(5) UNSIGNED NOT NULL,
  `end` smallint(5) UNSIGNED NOT NULL,
  `timezone` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `wp_mylisting_locations`
--
ALTER TABLE `wp_mylisting_locations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `listing_id` (`listing_id`),
  ADD KEY `lat` (`lat`),
  ADD KEY `lng` (`lng`);

--
-- Indexes for table `wp_mylisting_workhours`
--
ALTER TABLE `wp_mylisting_workhours`
  ADD PRIMARY KEY (`id`),
  ADD KEY `listing_id` (`listing_id`),
  ADD KEY `start` (`start`),
  ADD KEY `end` (`end`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `wp_mylisting_locations`
--
ALTER TABLE `wp_mylisting_locations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=124;

--
-- AUTO_INCREMENT for table `wp_mylisting_workhours`
--
ALTER TABLE `wp_mylisting_workhours`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `wp_mylisting_locations`
--
ALTER TABLE `wp_mylisting_locations`
  ADD CONSTRAINT `wp_mylisting_locations_ibfk_1` FOREIGN KEY (`listing_id`) REFERENCES `wp_posts` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `wp_mylisting_workhours`
--
ALTER TABLE `wp_mylisting_workhours`
  ADD CONSTRAINT `wp_mylisting_workhours_ibfk_1` FOREIGN KEY (`listing_id`) REFERENCES `wp_posts` (`ID`) ON DELETE CASCADE;
COMMIT;
