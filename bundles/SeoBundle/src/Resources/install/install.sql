CREATE TABLE IF NOT EXISTS `http_error_log` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `uri` varchar(1024) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
    `uriHash` binary(20) DEFAULT NULL,
    `code` int(3) DEFAULT NULL,
    `parametersGet` longtext,
    `date` int(11) unsigned DEFAULT NULL,
    `count` bigint(20) unsigned DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uriHash` (`uriHash`),
    KEY `uri` (`uri`),
    KEY `code` (`code`),
    KEY `date` (`date`),
    KEY `count` (`count`)
) DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `redirects` (
     `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
     `type` ENUM('entire_uri','path_query','path','auto_create','domain') NOT NULL,
     `source` varchar(1024) DEFAULT NULL,
     `sourceSite` int(11) DEFAULT NULL,
     `target` varchar(1024) DEFAULT NULL,
     `targetSite` int(11) DEFAULT NULL,
     `statusCode` varchar(3) DEFAULT NULL,
     `priority` int(2) DEFAULT '0',
     `regex` tinyint(1) DEFAULT NULL,
     `passThroughParameters` tinyint(1) DEFAULT NULL,
     `passThroughPath` tinyint(1) NOT NULL DEFAULT 0,
     `active` tinyint(1) DEFAULT NULL,
     `protected` tinyint(1) NOT NULL DEFAULT 0,
     `validFrom` int(11) unsigned DEFAULT NULL,
     `expiry` int(11) unsigned DEFAULT NULL,
     `creationDate` int(11) unsigned DEFAULT '0',
     `modificationDate` int(11) unsigned DEFAULT '0',
     `userOwner` int(11) unsigned DEFAULT NULL,
     `userModification` int(11) unsigned DEFAULT NULL,
     PRIMARY KEY (`id`),
     KEY `priority` (`priority`),
     KEY `source` (`source`(191))
) DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `redirect_hits` (
     `redirectId` int(11) unsigned NOT NULL,
     `hits` bigint(20) unsigned NOT NULL DEFAULT 0,
     `lastHit` int(11) unsigned DEFAULT NULL,
     PRIMARY KEY (`redirectId`),
     CONSTRAINT `fk_redirect_hits__redirectId` FOREIGN KEY (`redirectId`) REFERENCES `redirects` (`id`) ON DELETE CASCADE
) DEFAULT CHARSET=utf8mb4;
