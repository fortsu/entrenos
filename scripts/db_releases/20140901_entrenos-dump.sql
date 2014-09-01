-- MySQL dump 10.13  Distrib 5.5.37, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: entrenos
-- ------------------------------------------------------
-- Server version	5.5.37-0ubuntu0.13.10.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `athlete_data`
--

DROP TABLE IF EXISTS `athlete_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `athlete_data` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `weight` decimal(5,2) unsigned DEFAULT NULL,
  `height` tinyint(3) unsigned NOT NULL,
  `body_fat` decimal(4,2) unsigned DEFAULT NULL,
  `body_water` decimal(4,2) unsigned DEFAULT NULL,
  `body_muscle` decimal(4,2) unsigned DEFAULT NULL,
  `rest_beats` tinyint(3) unsigned NOT NULL,
  `max_beats` tinyint(3) unsigned NOT NULL,
  `vo2_max` decimal(5,2) unsigned DEFAULT NULL,
  `date` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `equipment`
--

DROP TABLE IF EXISTS `equipment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `equipment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `active` tinyint(1) unsigned NOT NULL,
  `name` varchar(32) NOT NULL,
  `description` text NOT NULL,
  `expected_life` mediumint(8) unsigned NOT NULL,
  `prior_usage` mediumint(8) unsigned NOT NULL,
  `usage` float NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `goal_records`
--

DROP TABLE IF EXISTS `goal_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `goal_records` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `goal_id` int(10) unsigned NOT NULL,
  `record_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `goals`
--

DROP TABLE IF EXISTS `goals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `goals` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `name` varchar(32) NOT NULL,
  `goal_date` date DEFAULT NULL,
  `goal_time` varchar(8) NOT NULL COMMENT 'hh:mm:ss',
  `description` tinytext NOT NULL,
  `report_enabled` tinyint(1) NOT NULL,
  `report_url` varchar(128) DEFAULT NULL,
  `last_report` varchar(48) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `laps`
--

DROP TABLE IF EXISTS `laps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `laps` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `record_id` int(10) unsigned NOT NULL,
  `lap_number` tinyint(4) unsigned NOT NULL,
  `start_time` datetime NOT NULL,
  `start_lat` float NOT NULL,
  `start_lon` float NOT NULL,
  `end_lat` float NOT NULL,
  `end_lon` float NOT NULL,
  `distance` int(10) unsigned NOT NULL COMMENT 'mm',
  `duration` int(10) unsigned NOT NULL COMMENT 'ms',
  `pace` float NOT NULL COMMENT 'min/km',
  `max_pace` float NOT NULL COMMENT 'min/km',
  `speed` float NOT NULL COMMENT 'km/h',
  `max_speed` float NOT NULL,
  `beats` tinyint(3) unsigned NOT NULL,
  `min_beats` tinyint(3) unsigned NOT NULL,
  `max_beats` tinyint(3) unsigned NOT NULL,
  `calories` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`),
  CONSTRAINT `laps_parent_record` FOREIGN KEY (`record_id`) REFERENCES `records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pcookies`
--

DROP TABLE IF EXISTS `pcookies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pcookies` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `hash` varchar(128) NOT NULL,
  `expiration` datetime NOT NULL,
  `salt` varchar(64) NOT NULL,
  `remote_ip` varchar(15) NOT NULL,
  `user_agent` varchar(128) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `record_equipment`
--

DROP TABLE IF EXISTS `record_equipment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `record_equipment` (
  `record_id` int(10) unsigned NOT NULL,
  `equipment_id` int(10) unsigned NOT NULL,
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`),
  CONSTRAINT `record_equipment_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `records` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `records`
--

DROP TABLE IF EXISTS `records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `records` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `start_time` datetime NOT NULL,
  `sport_id` tinyint(2) unsigned NOT NULL,
  `distance` int(10) unsigned NOT NULL COMMENT 'mm',
  `distance_old` int(10) unsigned NOT NULL,
  `duration` int(10) unsigned NOT NULL COMMENT 'ms',
  `duration_old` int(10) unsigned NOT NULL,
  `comments` text,
  `beats` float NOT NULL,
  `max_beats` float NOT NULL,
  `speed` float NOT NULL COMMENT 'km/h',
  `max_speed` float NOT NULL COMMENT 'km/h',
  `pace` float NOT NULL COMMENT 'min/km',
  `max_pace` float NOT NULL COMMENT 'min/km',
  `calories` float NOT NULL COMMENT 'Calories (kcal)',
  `upositive` smallint(5) unsigned NOT NULL COMMENT 'meters',
  `unegative` smallint(5) unsigned NOT NULL COMMENT 'meters',
  `title` varchar(32) NOT NULL,
  `visibility` tinyint(1) unsigned NOT NULL,
  `prev_act` int(10) unsigned DEFAULT NULL,
  `next_act` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `records_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sports`
--

DROP TABLE IF EXISTS `sports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `name` varchar(32) NOT NULL,
  `extra_weight` float NOT NULL,
  `met` float NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tag_records`
--

DROP TABLE IF EXISTS `tag_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tag_records` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tag_id` int(10) unsigned NOT NULL,
  `record_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`),
  CONSTRAINT `tag_records_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `records` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `name` varchar(32) NOT NULL,
  `report_enabled` tinyint(1) NOT NULL,
  `report_url` varchar(128) DEFAULT NULL,
  `last_report` varchar(48) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tokens`
--

DROP TABLE IF EXISTS `tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tokens` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(16) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `expiration` datetime NOT NULL,
  `remote_ip` varchar(15) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(32) NOT NULL,
  `password` varchar(128) DEFAULT NULL,
  `name` varchar(32) DEFAULT NULL,
  `surname` varchar(32) DEFAULT NULL,
  `email` varchar(32) NOT NULL,
  `remember` tinyint(1) NOT NULL,
  `maps` enum('osm','gmaps','bing') NOT NULL DEFAULT 'osm',
  `fb_id` varchar(16) NOT NULL COMMENT 'Facebook ID',
  `fb_access_token` varchar(128) NOT NULL COMMENT 'Facebook access token',
  `creation` datetime NOT NULL,
  `enabled` tinyint(1) unsigned NOT NULL,
  `last_access` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2014-09-01  9:34:20
