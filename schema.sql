-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 29, 2026 at 08:51 PM
-- Server version: 10.6.19-MariaDB
-- PHP Version: 8.1.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bigfoot1_ranking`
--

-- --------------------------------------------------------

--
-- Table structure for table `aliases`
--

CREATE TABLE `aliases` (
  `name` varchar(60) NOT NULL,
  `alias` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clubs`
--

CREATE TABLE `clubs` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `shortname` text NOT NULL,
  `evname` text NOT NULL,
  `state` text NOT NULL,
  `country` text NOT NULL,
  `isreal` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `control`
--

CREATE TABLE `control` (
  `name` varchar(30) NOT NULL,
  `value` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eventorEvents`
--

CREATE TABLE `eventorEvents` (
  `id` int(7) NOT NULL COMMENT 'Eventor event id',
  `raceid` int(7) NOT NULL DEFAULT 0 COMMENT 'Eventor race id (0 if no sub-race)',
  `processed` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Has event been processed'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(140) NOT NULL,
  `date` date NOT NULL,
  `url` varchar(250) NOT NULL,
  `scaling` decimal(5,2) NOT NULL DEFAULT 1.00
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE `results` (
  `id` int(10) UNSIGNED NOT NULL,
  `runnerid` int(10) UNSIGNED NOT NULL,
  `eventid` int(10) UNSIGNED NOT NULL,
  `points` int(4) UNSIGNED NOT NULL,
  `class` varchar(15) NOT NULL DEFAULT '',
  `sprint` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `runners`
--

CREATE TABLE `runners` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(60) NOT NULL,
  `yob_ceiling` int(4) NOT NULL DEFAULT 0,
  `yob_floor` int(4) NOT NULL DEFAULT 0,
  `gender` varchar(1) NOT NULL DEFAULT ' ',
  `current_ranking` int(4) NOT NULL DEFAULT 0,
  `current_score` int(4) NOT NULL DEFAULT 0,
  `best_score` int(4) NOT NULL DEFAULT 0,
  `current_sd` int(5) NOT NULL DEFAULT 0,
  `clubid` int(10) UNSIGNED NOT NULL,
  `class` varchar(6) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stats`
--

CREATE TABLE `stats` (
  `runnerid` int(11) NOT NULL,
  `average` decimal(10,0) NOT NULL,
  `stddev` decimal(10,0) NOT NULL,
  `count` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `aliases`
--
ALTER TABLE `aliases`
  ADD PRIMARY KEY (`name`);

--
-- Indexes for table `clubs`
--
ALTER TABLE `clubs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `control`
--
ALTER TABLE `control`
  ADD PRIMARY KEY (`name`);

--
-- Indexes for table `eventorEvents`
--
ALTER TABLE `eventorEvents`
  ADD PRIMARY KEY (`id`, `raceid`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `eventid` (`eventid`),
  ADD KEY `runnerid` (`runnerid`);

--
-- Indexes for table `runners`
--
ALTER TABLE `runners`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`,`clubid`),
  ADD KEY `clubid` (`clubid`);

--
-- Indexes for table `stats`
--
ALTER TABLE `stats`
  ADD PRIMARY KEY (`runnerid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clubs`
--
ALTER TABLE `clubs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `results`
--
ALTER TABLE `results`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `runners`
--
ALTER TABLE `runners`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `results_ibfk_2` FOREIGN KEY (`eventid`) REFERENCES `events` (`id`),
  ADD CONSTRAINT `results_ibfk_3` FOREIGN KEY (`runnerid`) REFERENCES `runners` (`id`);

--
-- Constraints for table `runners`
--
ALTER TABLE `runners`
  ADD CONSTRAINT `runners_ibfk_1` FOREIGN KEY (`clubid`) REFERENCES `clubs` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
