SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `crawler`;
CREATE TABLE `crawler` (
  `id` varchar(100) NOT NULL,
  `remote_ip` varchar(100) NOT NULL,
  `listen_addr` varchar(100) NOT NULL,
  `listen_addr2` varchar(100) NOT NULL,
  `rpc_address2` varchar(100) NOT NULL,
  `rpc_address` varchar(100) NOT NULL,
  `moniker` varchar(100) NOT NULL,
  `tx_index` int(1) NOT NULL,
  `voting_power` int(10) NOT NULL,
  `address` varchar(100) NOT NULL,
  `firstseen` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lastseen` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `rpcpublic` int(1) NOT NULL,
  `validator` int(1) NOT NULL,
  `netinfo` longtext NOT NULL,
  `rpchome` longtext NOT NULL,
  `network` varchar(100) NOT NULL,
  `version` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `network` (`network`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
