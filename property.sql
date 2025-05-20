-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 20, 2025 at 08:22 AM
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
-- Table structure for table `branch`
--

CREATE TABLE `branch` (
  `branchNo` char(4) NOT NULL,
  `street` varchar(25) NOT NULL DEFAULT '',
  `city` varchar(20) NOT NULL DEFAULT '',
  `postcode` char(7) NOT NULL DEFAULT ''
) ;

--
-- Dumping data for table `branch`
--

INSERT INTO `branch` (`branchNo`, `street`, `city`, `postcode`) VALUES
('B001', '123 Main St', 'Jakarta', '12345'),
('B002', '456 Sunset Ave', 'Bandung', '67890'),
('B003', '789 Ocean Rd', 'Surabaya', '54321');

-- --------------------------------------------------------

--
-- Table structure for table `cclient`
--

CREATE TABLE `cclient` (
  `clientNo` char(4) NOT NULL,
  `fName` varchar(50) NOT NULL DEFAULT '',
  `lName` varchar(50) NOT NULL DEFAULT '',
  `password` varchar(256) NOT NULL,
  `telNo` varchar(14) NOT NULL,
  `prefType` varchar(18) NOT NULL DEFAULT ' ',
  `maxRent` smallint(5) UNSIGNED DEFAULT NULL,
  `eMail` varchar(50) NOT NULL
) ;

--
-- Dumping data for table `cclient`
--

INSERT INTO `cclient` (`clientNo`, `fName`, `lName`, `password`, `telNo`, `prefType`, `maxRent`, `eMail`) VALUES
('CR01', 'test', 'tes', '$2y$10$lADDrNrsrr9bded8CA9bH.k2vEIi9YlQiLXpSMq8C6ATVfbb2rmOO', '', ' ', NULL, 'tes@gmail.com');

--
-- Triggers `cclient`
--
DELIMITER $$
CREATE TRIGGER `before_insert_CClient` BEFORE INSERT ON `cclient` FOR EACH ROW BEGIN
    DECLARE newCounter INT;
    SET @prefix := 'CR';

    UPDATE ClientCounter SET counter = counter + 1 WHERE prefix = @prefix;
    SELECT counter INTO newCounter FROM ClientCounter WHERE prefix = @prefix;

    SET NEW.clientNo = CONCAT(@prefix, LPAD(newCounter, 2, '0'));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `citypropertycounter`
--

CREATE TABLE `citypropertycounter` (
  `city` varchar(20) NOT NULL,
  `counter` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `citypropertycounter`
--

INSERT INTO `citypropertycounter` (`city`, `counter`) VALUES
('bandung', 6);

-- --------------------------------------------------------

--
-- Table structure for table `clientcounter`
--

CREATE TABLE `clientcounter` (
  `prefix` char(2) NOT NULL,
  `counter` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clientcounter`
--

INSERT INTO `clientcounter` (`prefix`, `counter`) VALUES
('CR', 1);

-- --------------------------------------------------------

--
-- Table structure for table `ownercounter`
--

CREATE TABLE `ownercounter` (
  `prefix` char(2) NOT NULL,
  `counter` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ownercounter`
--

INSERT INTO `ownercounter` (`prefix`, `counter`) VALUES
('CO', 1);

-- --------------------------------------------------------

--
-- Table structure for table `privateowner`
--

CREATE TABLE `privateowner` (
  `ownerNo` char(4) NOT NULL,
  `fName` varchar(50) NOT NULL DEFAULT '',
  `lName` varchar(50) NOT NULL DEFAULT '',
  `password` varchar(256) NOT NULL,
  `street` varchar(25) NOT NULL DEFAULT '',
  `city` varchar(20) NOT NULL DEFAULT '',
  `postcode` char(7) NOT NULL DEFAULT '',
  `telNo` varchar(14) NOT NULL,
  `eMail` varchar(50) NOT NULL
) ;

--
-- Dumping data for table `privateowner`
--

INSERT INTO `privateowner` (`ownerNo`, `fName`, `lName`, `password`, `street`, `city`, `postcode`, `telNo`, `eMail`) VALUES
('CO01', 'tes', 'owner', '$2y$10$yQGM6JJbym0jX5B1Ab8j3eXUAFMcUIBXsXlmH8KWchSaCmiGZr0YG', '', '', '', '', 'owner@gmail.com');

--
-- Triggers `privateowner`
--
DELIMITER $$
CREATE TRIGGER `before_insert_PrivateOwner` BEFORE INSERT ON `privateowner` FOR EACH ROW BEGIN
    DECLARE newCounter INT;
    SET @prefix := 'CO';

    UPDATE OwnerCounter SET counter = counter + 1 WHERE prefix = @prefix;
    SELECT counter INTO newCounter FROM OwnerCounter WHERE prefix = @prefix;

    SET NEW.ownerNo = CONCAT(@prefix, LPAD(newCounter, 2, '0'));
END
$$
DELIMITER ;

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

-- --------------------------------------------------------

--
-- Table structure for table `propertyimage`
--

CREATE TABLE `propertyimage` (
  `propertyNo` char(4) NOT NULL,
  `image` varchar(64) NOT NULL DEFAULT ' '
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `propertyimage`
--

INSERT INTO `propertyimage` (`propertyNo`, `image`) VALUES
('PB01', 'property_682c0ffcd3bd7_logo.png'),
('PB03', 'property_682c10fc735a1_Screenshot 2025-01-09 225731.png');

-- --------------------------------------------------------

--
-- Table structure for table `registration`
--

CREATE TABLE `registration` (
  `clientNo` char(4) NOT NULL,
  `branchNo` char(4) DEFAULT NULL,
  `staffNo` char(4) DEFAULT NULL,
  `dateJoined` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staffNo` char(4) NOT NULL,
  `fName` varchar(50) NOT NULL DEFAULT '',
  `lName` varchar(50) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL,
  `password` varchar(256) NOT NULL,
  `sPosition` varchar(15) NOT NULL DEFAULT '',
  `sex` char(1) DEFAULT NULL,
  `DOB` date DEFAULT NULL,
  `salary` int(11) DEFAULT NULL,
  `branchNo` char(4) NOT NULL
) ;

--
-- Triggers `staff`
--
DELIMITER $$
CREATE TRIGGER `before_insert_Staff` BEFORE INSERT ON `staff` FOR EACH ROW BEGIN
    DECLARE newCounter INT;
    DECLARE randomAlphabet CHAR(1);
    SET randomAlphabet = CHAR(FLOOR(65 + (RAND() * 26))); -- Random Alphabet from A-Z
    
    UPDATE StaffCounter SET counter = counter + 1 WHERE prefix = randomAlphabet;
    SELECT counter INTO newCounter FROM StaffCounter WHERE prefix = randomAlphabet;

    SET NEW.staffNo = CONCAT('S', randomAlphabet, LPAD(newCounter, 2, '0'));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `staffcounter`
--

CREATE TABLE `staffcounter` (
  `prefix` char(1) NOT NULL,
  `counter` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staffcounter`
--

INSERT INTO `staffcounter` (`prefix`, `counter`) VALUES
('A', 0);

-- --------------------------------------------------------

--
-- Table structure for table `viewing`
--

CREATE TABLE `viewing` (
  `clientNo` char(4) NOT NULL,
  `propertyNo` char(4) NOT NULL,
  `viewDate` datetime NOT NULL DEFAULT current_timestamp(),
  `vComment` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `branch`
--
ALTER TABLE `branch`
  ADD PRIMARY KEY (`branchNo`);

--
-- Indexes for table `cclient`
--
ALTER TABLE `cclient`
  ADD PRIMARY KEY (`clientNo`),
  ADD KEY `lName` (`lName`);

--
-- Indexes for table `citypropertycounter`
--
ALTER TABLE `citypropertycounter`
  ADD PRIMARY KEY (`city`);

--
-- Indexes for table `clientcounter`
--
ALTER TABLE `clientcounter`
  ADD PRIMARY KEY (`prefix`);

--
-- Indexes for table `ownercounter`
--
ALTER TABLE `ownercounter`
  ADD PRIMARY KEY (`prefix`);

--
-- Indexes for table `privateowner`
--
ALTER TABLE `privateowner`
  ADD PRIMARY KEY (`ownerNo`),
  ADD KEY `lName` (`lName`),
  ADD KEY `postcode` (`postcode`);

--
-- Indexes for table `propertyforrent`
--
ALTER TABLE `propertyforrent`
  ADD PRIMARY KEY (`propertyNo`),
  ADD KEY `ownerNo` (`ownerNo`),
  ADD KEY `staffNo` (`staffNo`),
  ADD KEY `branchNo` (`branchNo`);

--
-- Indexes for table `propertyimage`
--
ALTER TABLE `propertyimage`
  ADD PRIMARY KEY (`propertyNo`,`image`);

--
-- Indexes for table `registration`
--
ALTER TABLE `registration`
  ADD PRIMARY KEY (`clientNo`),
  ADD KEY `branchNo` (`branchNo`),
  ADD KEY `staffNo` (`staffNo`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staffNo`),
  ADD KEY `branchNo` (`branchNo`);

--
-- Indexes for table `staffcounter`
--
ALTER TABLE `staffcounter`
  ADD PRIMARY KEY (`prefix`);

--
-- Indexes for table `viewing`
--
ALTER TABLE `viewing`
  ADD PRIMARY KEY (`clientNo`,`propertyNo`,`viewDate`),
  ADD KEY `propertyNo` (`propertyNo`);

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

--
-- Constraints for table `propertyimage`
--
ALTER TABLE `propertyimage`
  ADD CONSTRAINT `propertyimage_ibfk_1` FOREIGN KEY (`propertyNo`) REFERENCES `propertyforrent` (`propertyNo`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `registration`
--
ALTER TABLE `registration`
  ADD CONSTRAINT `registration_ibfk_1` FOREIGN KEY (`clientNo`) REFERENCES `cclient` (`clientNo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `registration_ibfk_2` FOREIGN KEY (`branchNo`) REFERENCES `branch` (`branchNo`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `registration_ibfk_3` FOREIGN KEY (`staffNo`) REFERENCES `staff` (`staffNo`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`branchNo`) REFERENCES `branch` (`branchNo`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `viewing`
--
ALTER TABLE `viewing`
  ADD CONSTRAINT `viewing_ibfk_1` FOREIGN KEY (`clientNo`) REFERENCES `cclient` (`clientNo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `viewing_ibfk_2` FOREIGN KEY (`propertyNo`) REFERENCES `propertyforrent` (`propertyNo`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
