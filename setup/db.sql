-- phpMyAdmin SQL Dump
-- version 4.5.0.2
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Oct 06, 2015 at 08:46 PM
-- Server version: 5.6.21
-- PHP Version: 5.6.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `betonhomework`
--
CREATE DATABASE IF NOT EXISTS `betonhomework` DEFAULT CHARACTER SET utf8 COLLATE utf8_hungarian_ci;
USE `betonhomework`;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` tinytext CHARACTER SET utf8 COLLATE utf8_hungarian_ci NOT NULL,
  `password` tinytext CHARACTER SET utf8 COLLATE utf8_hungarian_ci NOT NULL,
  `session` tinytext CHARACTER SET utf8 COLLATE utf8_hungarian_ci NOT NULL,
  `realname` tinytext CHARACTER SET utf8 COLLATE utf8_hungarian_ci NOT NULL,
  `active` int(11) NOT NULL DEFAULT '1',
  `priv` tinytext CHARACTER SET utf8 COLLATE utf8_hungarian_ci NOT NULL COMMENT 'schooladmin,sysadmin'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `class`
--

CREATE TABLE `class` (
  `id` int(11) NOT NULL,
  `classid` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `active` int(11) NOT NULL DEFAULT '1',
  `school` int(11) NOT NULL,
  `pairweek` tinytext COLLATE utf8_hungarian_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ext_connections`
--

CREATE TABLE `ext_connections` (
  `id` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `provider` enum('google','facebook','twitter','') COLLATE utf8_hungarian_ci NOT NULL,
  `account_id` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `name` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `picture` text COLLATE utf8_hungarian_ci NOT NULL,
  `email` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `active` int(11) NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `global_settings`
--

CREATE TABLE `global_settings` (
  `id` int(11) NOT NULL,
  `key` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `value` tinytext COLLATE utf8_hungarian_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `id` int(11) NOT NULL,
  `classid` int(11) NOT NULL,
  `name` varchar(15) COLLATE utf8_hungarian_ci NOT NULL,
  `theme` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `group_members`
--

CREATE TABLE `group_members` (
  `id` int(11) NOT NULL,
  `classid` int(11) NOT NULL,
  `groupid` int(11) NOT NULL,
  `userid` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `group_themes`
--

CREATE TABLE `group_themes` (
  `id` int(11) NOT NULL,
  `name` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `classid` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `homeworks`
--

CREATE TABLE `homeworks` (
  `id` int(11) NOT NULL,
  `lesson` int(11) NOT NULL,
  `text` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `author` int(11) NOT NULL,
  `week` int(11) NOT NULL,
  `classid` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hw_markdone`
--

CREATE TABLE `hw_markdone` (
  `id` int(11) NOT NULL,
  `homework` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `classid` int(11) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `invitations`
--

CREATE TABLE `invitations` (
  `id` int(11) NOT NULL,
  `invitation` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `email` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `name` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `classid` int(11) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `inviter` int(11) NOT NULL,
  `active` int(11) NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lessons`
--

CREATE TABLE `lessons` (
  `id` int(11) NOT NULL,
  `classid` int(11) NOT NULL,
  `name` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `teacherid` int(11) NOT NULL,
  `color` tinytext COLLATE utf8_hungarian_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log_central`
--

CREATE TABLE `log_central` (
  `id` int(11) NOT NULL,
  `action` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `db` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `user` int(11) NOT NULL,
  `sublogid` int(11) NOT NULL,
  `errorcode` int(11) NOT NULL,
  `useragent` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `ipaddr` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log_lesson_add`
--

CREATE TABLE `log_lesson_add` (
  `id` int(11) NOT NULL,
  `e_id` int(11) NOT NULL,
  `classid` int(11) NOT NULL,
  `name` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `teacherid` int(11) NOT NULL,
  `color` tinytext COLLATE utf8_hungarian_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log_lesson_del`
--

CREATE TABLE `log_lesson_del` (
  `id` int(11) NOT NULL,
  `e_id` int(11) NOT NULL,
  `classid` int(11) NOT NULL,
  `name` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `teacherid` int(11) NOT NULL,
  `color` tinytext COLLATE utf8_hungarian_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log_lesson_edit`
--

CREATE TABLE `log_lesson_edit` (
  `id` int(11) NOT NULL,
  `e_id` int(11) NOT NULL,
  `classid` int(11) NOT NULL,
  `name` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `teacherid` int(11) NOT NULL,
  `color` tinytext COLLATE utf8_hungarian_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log_login`
--

CREATE TABLE `log_login` (
  `id` int(11) NOT NULL,
  `username` tinytext COLLATE utf8_hungarian_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log_user_add`
--

CREATE TABLE `log_user_add` (
  `id` int(11) NOT NULL,
  `e_id` int(11) NOT NULL,
  `username` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `realname` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `classid` int(11) NOT NULL,
  `priv` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `active` int(11) NOT NULL,
  `email` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `birthday` date NOT NULL,
  `phone` tinytext COLLATE utf8_hungarian_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log_user_del`
--

CREATE TABLE `log_user_del` (
  `id` int(11) NOT NULL,
  `e_id` int(11) NOT NULL,
  `username` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `realname` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `classid` int(11) NOT NULL,
  `priv` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `active` int(11) NOT NULL,
  `email` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `birthday` date NOT NULL,
  `phone` tinytext COLLATE utf8_hungarian_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log_user_edit`
--

CREATE TABLE `log_user_edit` (
  `id` int(11) NOT NULL,
  `e_id` int(11) NOT NULL,
  `username` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `realname` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `classid` int(11) NOT NULL,
  `priv` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `active` int(11) NOT NULL,
  `email` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `birthday` date NOT NULL,
  `phone` tinytext COLLATE utf8_hungarian_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pw_reset`
--

CREATE TABLE `pw_reset` (
  `id` int(11) NOT NULL,
  `hash` binary(64) NOT NULL,
  `userid` int(11) NOT NULL,
  `expires` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `school`
--

CREATE TABLE `school` (
  `id` int(11) NOT NULL,
  `name` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `active` int(11) NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` int(11) NOT NULL,
  `session` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `userid` int(11) NOT NULL,
  `ip` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `useragent` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `classid` int(11) NOT NULL,
  `short` varchar(10) CHARACTER SET utf8 COLLATE utf8_hungarian_ci NOT NULL DEFAULT '',
  `name` tinytext CHARACTER SET utf8 COLLATE utf8_hungarian_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `timetable`
--

CREATE TABLE `timetable` (
  `id` int(11) NOT NULL,
  `classid` int(11) NOT NULL,
  `week` enum('a','b') COLLATE utf8_hungarian_ci NOT NULL,
  `day` tinyint(3) NOT NULL,
  `lesson` tinyint(3) NOT NULL,
  `lessonid` int(11) NOT NULL,
  `groupid` int(11) NOT NULL,
  `start` time NOT NULL,
  `end` time NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `password` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `realname` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `classid` int(11) NOT NULL,
  `priv` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `active` int(11) NOT NULL DEFAULT '1',
  `email` tinytext COLLATE utf8_hungarian_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `class`
--
ALTER TABLE `class`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ext_connections`
--
ALTER TABLE `ext_connections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `global_settings`
--
ALTER TABLE `global_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `group_members`
--
ALTER TABLE `group_members`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `group_themes`
--
ALTER TABLE `group_themes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `homeworks`
--
ALTER TABLE `homeworks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hw_markdone`
--
ALTER TABLE `hw_markdone`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invitations`
--
ALTER TABLE `invitations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `log_central`
--
ALTER TABLE `log_central`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `log_lesson_add`
--
ALTER TABLE `log_lesson_add`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `log_lesson_del`
--
ALTER TABLE `log_lesson_del`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `log_lesson_edit`
--
ALTER TABLE `log_lesson_edit`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `log_login`
--
ALTER TABLE `log_login`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `log_user_add`
--
ALTER TABLE `log_user_add`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `log_user_del`
--
ALTER TABLE `log_user_del`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `log_user_edit`
--
ALTER TABLE `log_user_edit`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pw_reset`
--
ALTER TABLE `pw_reset`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `school`
--
ALTER TABLE `school`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `timetable`
--
ALTER TABLE `timetable`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `class`
--
ALTER TABLE `class`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `ext_connections`
--
ALTER TABLE `ext_connections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `global_settings`
--
ALTER TABLE `global_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `group_members`
--
ALTER TABLE `group_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `group_themes`
--
ALTER TABLE `group_themes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `homeworks`
--
ALTER TABLE `homeworks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `hw_markdone`
--
ALTER TABLE `hw_markdone`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `invitations`
--
ALTER TABLE `invitations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `lessons`
--
ALTER TABLE `lessons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `log_central`
--
ALTER TABLE `log_central`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `log_lesson_add`
--
ALTER TABLE `log_lesson_add`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `log_lesson_del`
--
ALTER TABLE `log_lesson_del`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `log_lesson_edit`
--
ALTER TABLE `log_lesson_edit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `log_login`
--
ALTER TABLE `log_login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `log_user_add`
--
ALTER TABLE `log_user_add`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `log_user_del`
--
ALTER TABLE `log_user_del`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `log_user_edit`
--
ALTER TABLE `log_user_edit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `pw_reset`
--
ALTER TABLE `pw_reset`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `school`
--
ALTER TABLE `school`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
