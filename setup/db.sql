-- phpMyAdmin SQL Dump
-- version 4.4.8
-- http://www.phpmyadmin.net
--
-- Gép: 127.0.0.1
-- Létrehozás ideje: 2015. Sze 10. 20:36
-- Kiszolgáló verziója: 5.6.21
-- PHP verzió: 5.6.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Adatbázis: `betonhomework`
--
CREATE DATABASE IF NOT EXISTS `betonhomework` DEFAULT CHARACTER SET utf8 COLLATE utf8_hungarian_ci;
USE `betonhomework`;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `admins`
--

CREATE TABLE IF NOT EXISTS `admins` (
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
-- Tábla szerkezet ehhez a táblához `class`
--

CREATE TABLE IF NOT EXISTS `class` (
  `id` int(11) NOT NULL,
  `classid` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `active` int(11) NOT NULL DEFAULT '1',
  `school` int(11) NOT NULL,
  `pairweek` tinytext COLLATE utf8_hungarian_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `ext_connections`
--

CREATE TABLE IF NOT EXISTS `ext_connections` (
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
-- Tábla szerkezet ehhez a táblához `groups`
--

CREATE TABLE IF NOT EXISTS `groups` (
  `id` int(11) NOT NULL,
  `classid` int(11) NOT NULL,
  `name` varchar(15) COLLATE utf8_hungarian_ci NOT NULL,
  `theme` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `group_members`
--

CREATE TABLE IF NOT EXISTS `group_members` (
  `id` int(11) NOT NULL,
  `classid` int(11) NOT NULL,
  `groupid` int(11) NOT NULL,
  `userid` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `group_themes`
--

CREATE TABLE IF NOT EXISTS `group_themes` (
  `id` int(11) NOT NULL,
  `name` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `classid` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `homeworks`
--

CREATE TABLE IF NOT EXISTS `homeworks` (
  `id` int(11) NOT NULL,
  `lesson` int(11) NOT NULL,
  `text` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `author` int(11) NOT NULL,
  `week` int(11) NOT NULL,
  `classid` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `invitations`
--

CREATE TABLE IF NOT EXISTS `invitations` (
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
-- Tábla szerkezet ehhez a táblához `lessons`
--

CREATE TABLE IF NOT EXISTS `lessons` (
  `id` int(11) NOT NULL,
  `classid` int(11) NOT NULL,
  `name` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `teacherid` int(11) NOT NULL,
  `color` tinytext COLLATE utf8_hungarian_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `log_central`
--

CREATE TABLE IF NOT EXISTS `log_central` (
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
-- Tábla szerkezet ehhez a táblához `log_lesson_add`
--

CREATE TABLE IF NOT EXISTS `log_lesson_add` (
  `id` int(11) NOT NULL,
  `e_id` int(11) NOT NULL,
  `classid` int(11) NOT NULL,
  `name` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `teacherid` int(11) NOT NULL,
  `color` tinytext COLLATE utf8_hungarian_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `log_lesson_del`
--

CREATE TABLE IF NOT EXISTS `log_lesson_del` (
  `id` int(11) NOT NULL,
  `e_id` int(11) NOT NULL,
  `classid` int(11) NOT NULL,
  `name` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `teacherid` int(11) NOT NULL,
  `color` tinytext COLLATE utf8_hungarian_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `log_lesson_edit`
--

CREATE TABLE IF NOT EXISTS `log_lesson_edit` (
  `id` int(11) NOT NULL,
  `e_id` int(11) NOT NULL,
  `classid` int(11) NOT NULL,
  `name` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `teacherid` int(11) NOT NULL,
  `color` tinytext COLLATE utf8_hungarian_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `log_login`
--

CREATE TABLE IF NOT EXISTS `log_login` (
  `id` int(11) NOT NULL,
  `username` tinytext COLLATE utf8_hungarian_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `log_user_add`
--

CREATE TABLE IF NOT EXISTS `log_user_add` (
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
-- Tábla szerkezet ehhez a táblához `log_user_del`
--

CREATE TABLE IF NOT EXISTS `log_user_del` (
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
-- Tábla szerkezet ehhez a táblához `log_user_edit`
--

CREATE TABLE IF NOT EXISTS `log_user_edit` (
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
-- Tábla szerkezet ehhez a táblához `school`
--

CREATE TABLE IF NOT EXISTS `school` (
  `id` int(11) NOT NULL,
  `name` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `active` int(11) NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `teachers`
--

CREATE TABLE IF NOT EXISTS `teachers` (
  `id` int(11) NOT NULL,
  `classid` int(11) NOT NULL,
  `short` varchar(10) CHARACTER SET utf8 COLLATE utf8_hungarian_ci NOT NULL DEFAULT '',
  `name` tinytext CHARACTER SET utf8 COLLATE utf8_hungarian_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `timetable`
--

CREATE TABLE IF NOT EXISTS `timetable` (
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
-- Tábla szerkezet ehhez a táblához `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL,
  `username` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `password` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `session` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `realname` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `classid` int(11) NOT NULL,
  `priv` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `active` int(11) NOT NULL DEFAULT '1',
  `email` tinytext COLLATE utf8_hungarian_ci NOT NULL,
  `birthday` date NOT NULL,
  `phone` varchar(12) COLLATE utf8_hungarian_ci NOT NULL,
  `google_address` tinytext COLLATE utf8_hungarian_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

--
-- Indexek a kiírt táblákhoz
--

--
-- A tábla indexei `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- A tábla indexei `class`
--
ALTER TABLE `class`
  ADD PRIMARY KEY (`id`);

--
-- A tábla indexei `ext_connections`
--
ALTER TABLE `ext_connections`
  ADD PRIMARY KEY (`id`);

--
-- A tábla indexei `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`);

--
-- A tábla indexei `group_members`
--
ALTER TABLE `group_members`
  ADD PRIMARY KEY (`id`);

--
-- A tábla indexei `group_themes`
--
ALTER TABLE `group_themes`
  ADD PRIMARY KEY (`id`);

--
-- A tábla indexei `homeworks`
--
ALTER TABLE `homeworks`
  ADD PRIMARY KEY (`id`);

--
-- A tábla indexei `invitations`
--
ALTER TABLE `invitations`
  ADD PRIMARY KEY (`id`);

--
-- A tábla indexei `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`id`);

--
-- A tábla indexei `log_central`
--
ALTER TABLE `log_central`
  ADD PRIMARY KEY (`id`);

--
-- A tábla indexei `log_lesson_add`
--
ALTER TABLE `log_lesson_add`
  ADD PRIMARY KEY (`id`);

--
-- A tábla indexei `log_lesson_del`
--
ALTER TABLE `log_lesson_del`
  ADD PRIMARY KEY (`id`);

--
-- A tábla indexei `log_lesson_edit`
--
ALTER TABLE `log_lesson_edit`
  ADD PRIMARY KEY (`id`);

--
-- A tábla indexei `log_login`
--
ALTER TABLE `log_login`
  ADD PRIMARY KEY (`id`);

--
-- A tábla indexei `log_user_add`
--
ALTER TABLE `log_user_add`
  ADD PRIMARY KEY (`id`);

--
-- A tábla indexei `log_user_del`
--
ALTER TABLE `log_user_del`
  ADD PRIMARY KEY (`id`);

--
-- A tábla indexei `log_user_edit`
--
ALTER TABLE `log_user_edit`
  ADD PRIMARY KEY (`id`);

--
-- A tábla indexei `school`
--
ALTER TABLE `school`
  ADD PRIMARY KEY (`id`);

--
-- A tábla indexei `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`);

--
-- A tábla indexei `timetable`
--
ALTER TABLE `timetable`
  ADD PRIMARY KEY (`id`);

--
-- A tábla indexei `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- A kiírt táblák AUTO_INCREMENT értéke
--

--
-- AUTO_INCREMENT a táblához `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT a táblához `class`
--
ALTER TABLE `class`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT a táblához `ext_connections`
--
ALTER TABLE `ext_connections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT a táblához `groups`
--
ALTER TABLE `groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT a táblához `group_members`
--
ALTER TABLE `group_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT a táblához `group_themes`
--
ALTER TABLE `group_themes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT a táblához `homeworks`
--
ALTER TABLE `homeworks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT a táblához `invitations`
--
ALTER TABLE `invitations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT a táblához `lessons`
--
ALTER TABLE `lessons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT a táblához `log_central`
--
ALTER TABLE `log_central`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT a táblához `log_lesson_add`
--
ALTER TABLE `log_lesson_add`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT a táblához `log_lesson_del`
--
ALTER TABLE `log_lesson_del`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT a táblához `log_lesson_edit`
--
ALTER TABLE `log_lesson_edit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT a táblához `log_login`
--
ALTER TABLE `log_login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT a táblához `log_user_add`
--
ALTER TABLE `log_user_add`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT a táblához `log_user_del`
--
ALTER TABLE `log_user_del`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT a táblához `log_user_edit`
--
ALTER TABLE `log_user_edit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT a táblához `school`
--
ALTER TABLE `school`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT a táblához `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT a táblához `timetable`
--
ALTER TABLE `timetable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT a táblához `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
