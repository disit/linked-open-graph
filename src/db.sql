CREATE DATABASE `lograph` /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci */;

DROP TABLE IF EXISTS `lograph`.`graph`;
CREATE TABLE  `lograph`.`graph` (
  `id` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `mail` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `sparql_endpoint` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `initial_uri` varchar(300) COLLATE utf8_unicode_ci DEFAULT NULL,
  `config` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `parent_id` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `readwrite_id` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `lograph`.`graph` ADD INDEX `rw_id`(`readwrite_id`);

2014/04/11
ALTER TABLE `lograph`.`graph` ADD COLUMN `list_endpoints` VARCHAR(500) NOT NULL AFTER `readwrite_id`; 

2014/04/28
ALTER TABLE `lograph`.`graph` ADD COLUMN `status` MEDIUMTEXT NOT NULL AFTER `list_endpoints`;

2014/05/19
CREATE TABLE `lograph`.`class_cache` (
  `sparql_endpoint` VARCHAR(200) NOT NULL,
  `classes` LONGTEXT NOT NULL,
  `timestamp` INTEGER NOT NULL,
  PRIMARY KEY (`sparql_endpoint`)
)
ENGINE = InnoDB;

2014/6/3
ALTER TABLE `lograph`.`graph` ADD COLUMN `title` VARCHAR(200) NOT NULL AFTER `status`,
 ADD COLUMN `description` TEXT NOT NULL AFTER `title`;

2014/11/10
ALTER TABLE `lograph`.`graph` ADD COLUMN `type` VARCHAR(45) AFTER `description`;

DROP TABLE IF EXISTS `lograph`.`endpoints`;
CREATE TABLE  `lograph`.`endpoints` (
  `url` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `active` tinyint(1) NOT NULL,
  `order` int(10) unsigned NOT NULL,
  `uri_associated` mediumtext COLLATE utf8_unicode_ci,
  `limit` int(10) unsigned NOT NULL DEFAULT '1000000',
  `search_type` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `examples` mediumtext COLLATE utf8_unicode_ci,
  `blank_node` tinyint(1) NOT NULL DEFAULT '0',
  `suggest` mediumtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `lograph`.`user`;
CREATE TABLE  `lograph`.`user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `nicename` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_n` (`username`),
  UNIQUE KEY `user_e` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;