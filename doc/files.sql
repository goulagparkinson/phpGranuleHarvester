-- phpMyAdmin SQL Dump
-- version 3.4.11.1deb1
-- http://www.phpmyadmin.net
--
-- Client: localhost
-- Généré le: Sam 19 Janvier 2013 à 18:11
-- Version du serveur: 5.5.28
-- Version de PHP: 5.4.6-1ubuntu1.1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données: `granuleHarvester`
--

-- --------------------------------------------------------

--
-- Structure de la table `files`
--

CREATE TABLE IF NOT EXISTS `files` (
  `sha1_path` varchar(40) NOT NULL,
  `name` varchar(4096) NOT NULL,
  `path` varchar(4096) NOT NULL,
  `product_id` varchar(255) DEFAULT NULL,
  `size` int(11) DEFAULT NULL,
  `md5sum` varchar(32) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `insert_datetime` datetime NOT NULL,
  `update_datetime` datetime NOT NULL,
  `delete_datetime` datetime DEFAULT NULL,
  `start_datetime` datetime DEFAULT NULL,
  `stop_datetime` datetime DEFAULT NULL,
  `metadata` longtext,
  PRIMARY KEY (`sha1_path`),
  KEY `name` (`name`(767),`path`(767),`product_id`,`status`,`insert_datetime`,`update_datetime`,`delete_datetime`,`start_datetime`,`stop_datetime`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
