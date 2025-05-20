-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 20, 2025 at 08:08 AM
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
-- Database: `property`
--

-- --------------------------------------------------------

--
-- Table structure for table `propertyforrent`
--

CREATE TABLE `propertyforrent` (
  `propertyNo` char(4) NOT NULL,
  `street` varchar(25) NOT NULL DEFAULT '',
  `city` varchar(20) NOT NULL DEFAULT '',
  `postcode` char(7) NOT NULL DEFAULT '',
  `pType` varchar(18) NOT NULL DEFAULT ' ',
  `rooms` tinyint(3) UNSIGNED DEFAULT NULL,
  `rent` smallint(5) UNSIGNED DEFAULT NULL,
  `ownerNo` char(4) NOT NULL,
  `staffNo` char(4) DEFAULT NULL,
  `branchNo` char(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `propertyforrent`
--

INSERT INTO `propertyforrent` (`propertyNo`, `street`, `city`, `postcode`, `pType`, `rooms`, `rent`, `ownerNo`, `staffNo`, `branchNo`) VALUES
('PB00', 'caheum', 'bandung', '40983', 'villa', 3, 999, 'CO01', NULL, 'B002'),
('PB01', 'caheum', 'bandung', '40983', 'villa', 3, 999, 'CO01', NULL, 'B002'),
('PB03', 'caheum', 'bandung', '40983', 'kos', 1, 999, 'CO01', NULL, 'B003');

--
-- Triggers `propertyforrent`
--
DELIMITER $$
CREATE TRIGGER `before_insert_property` BEFORE INSERT ON `propertyforrent` FOR EACH ROW BEGIN
    DECLARE cityInitial CHAR(1);
    DECLARE newCounter INT;
    DECLARE formattedNo CHAR(2);

    SET cityInitial = UPPER(LEFT(NEW.city, 1));

    INSERT INTO CityPropertyCounter (city, counter)
    VALUES (NEW.city, 0)
    ON DUPLICATE KEY UPDATE counter = counter + 1;

    SELECT counter INTO newCounter FROM CityPropertyCounter WHERE city = NEW.city;

    SET formattedNo = LPAD(newCounter, 2, '0');

    SET NEW.propertyNo = CONCAT('P', cityInitial, formattedNo);
END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `propertyforrent`
--
ALTER TABLE `propertyforrent`
  ADD PRIMARY KEY (`propertyNo`),
  ADD KEY `ownerNo` (`ownerNo`),
  ADD KEY `staffNo` (`staffNo`),
  ADD KEY `branchNo` (`branchNo`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `propertyforrent`
--
ALTER TABLE `propertyforrent`
  ADD CONSTRAINT `propertyforrent_ibfk_1` FOREIGN KEY (`ownerNo`) REFERENCES `privateowner` (`ownerNo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `propertyforrent_ibfk_2` FOREIGN KEY (`staffNo`) REFERENCES `staff` (`staffNo`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `propertyforrent_ibfk_3` FOREIGN KEY (`branchNo`) REFERENCES `branch` (`branchNo`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
