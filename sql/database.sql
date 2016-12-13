-- phpMyAdmin SQL Dump
-- version 4.6.5.2
-- https://www.phpmyadmin.net/
--
-- Host: mysql.lan
-- Generation Time: Dec 13, 2016 at 02:36 PM
-- Server version: 5.5.52-0+deb8u1-log
-- PHP Version: 7.0.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `send-ident`
--

-- --------------------------------------------------------

--
-- Table structure for table `sendident__accounts`
--

CREATE TABLE `sendident__accounts` (
  `ID` int(11) NOT NULL,
  `hostname` varchar(40) NOT NULL,
  `port` int(5) NOT NULL,
  `isTLS` tinyint(1) NOT NULL DEFAULT '1',
  `name` varchar(40) NOT NULL,
  `username` varchar(40) NOT NULL,
  `password` varchar(40) NOT NULL,
  `inboxName` varchar(20) DEFAULT 'INBOX',
  `inboxTemp` varchar(20) NOT NULL DEFAULT 'INBOX_TEMP',
  `status` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sendident__blacklist`
--

CREATE TABLE `sendident__blacklist` (
  `ID` int(11) NOT NULL,
  `ID_ACCOUNT` int(10) NOT NULL,
  `email` varchar(255) NOT NULL,
  `timestamp` int(40) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sendident__mails`
--

CREATE TABLE `sendident__mails` (
  `ID` int(11) NOT NULL,
  `sig` varchar(200) NOT NULL,
  `ID_ACCOUNT` int(10) NOT NULL,
  `emailFrom` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `timestamp` int(40) NOT NULL,
  `validate` tinyint(1) NOT NULL DEFAULT '0',
  `onMove` tinyint(1) NOT NULL DEFAULT '0',
  `noSpam` tinyint(1) NOT NULL DEFAULT '0',
  `reportSpam` text NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sendident__whitelist`
--

CREATE TABLE `sendident__whitelist` (
  `ID` int(11) NOT NULL,
  `ID_ACCOUNT` int(10) NOT NULL,
  `email` varchar(255) NOT NULL,
  `timestamp` int(40) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `spam`
--

CREATE TABLE `spam` (
  `spamid` int(11) NOT NULL,
  `token` varchar(500) COLLATE latin1_general_ci NOT NULL,
  `spamcount` int(11) NOT NULL DEFAULT '0',
  `hamcount` int(11) NOT NULL DEFAULT '0',
  `spamrating` double NOT NULL DEFAULT '0.4'
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `totals`
--

CREATE TABLE `totals` (
  `totalsid` int(11) NOT NULL,
  `totalspam` int(11) NOT NULL,
  `totalham` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `sendident__accounts`
--
ALTER TABLE `sendident__accounts`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `sendident__blacklist`
--
ALTER TABLE `sendident__blacklist`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `sendident__mails`
--
ALTER TABLE `sendident__mails`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `sendident__whitelist`
--
ALTER TABLE `sendident__whitelist`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `spam`
--
ALTER TABLE `spam`
  ADD PRIMARY KEY (`spamid`);

--
-- Indexes for table `totals`
--
ALTER TABLE `totals`
  ADD PRIMARY KEY (`totalsid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `sendident__accounts`
--
ALTER TABLE `sendident__accounts`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
--
-- AUTO_INCREMENT for table `sendident__blacklist`
--
ALTER TABLE `sendident__blacklist`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;
--
-- AUTO_INCREMENT for table `sendident__mails`
--
ALTER TABLE `sendident__mails`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=245;
--
-- AUTO_INCREMENT for table `sendident__whitelist`
--
ALTER TABLE `sendident__whitelist`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=120;
--
-- AUTO_INCREMENT for table `spam`
--
ALTER TABLE `spam`
  MODIFY `spamid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16769;
--
-- AUTO_INCREMENT for table `totals`
--
ALTER TABLE `totals`
  MODIFY `totalsid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
