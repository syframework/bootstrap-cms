SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS `t_content` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `alias` varchar(128) NOT NULL DEFAULT '',
  `title` varchar(128) NOT NULL DEFAULT '',
  `description` varchar(512) NOT NULL DEFAULT '' COMMENT 'textarea',
  `html` mediumtext NOT NULL COMMENT 'none',
  `scss` text NOT NULL COMMENT 'none',
  `css` text NOT NULL COMMENT 'none',
  `js` text NOT NULL COMMENT 'none',
  `visibility` enum('private','public') NOT NULL DEFAULT 'private' COMMENT 'select',
  `creator_id` int UNSIGNED NULL DEFAULT NULL COMMENT 'none',
  `updator_id` int UNSIGNED NULL DEFAULT NULL COMMENT 'none',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'none',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'none',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `alias`(`alias` ASC) USING BTREE,
  FOREIGN KEY (`creator_id`) REFERENCES `t_user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`updator_id`) REFERENCES `t_user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS `t_content_history` (
  `id` int UNSIGNED NOT NULL,
  `crc32` bigint NOT NULL,
  `title` varchar(128) NOT NULL,
  `description` varchar(512) NOT NULL,
  `html` mediumtext NOT NULL,
  `scss` text NOT NULL,
  `css` text NOT NULL,
  `js` text NOT NULL,
  `updator_id` int UNSIGNED NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`, `crc32`),
  FOREIGN KEY (`updator_id`) REFERENCES `t_user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`id`) REFERENCES `t_content` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

DROP TRIGGER IF EXISTS `t_content_history_insert`;
CREATE TRIGGER `t_content_history_insert` BEFORE UPDATE ON `t_content` FOR EACH ROW
BEGIN
DECLARE crc32 bigint;
DECLARE newContent bigint;
DECLARE oldContent bigint;
SET newContent = CRC32(CONCAT(NEW.title, NEW.description, NEW.html, NEW.scss, NEW.css, NEW.js));
SET oldContent = CRC32(CONCAT(OLD.title, OLD.description, OLD.html, OLD.scss, OLD.css, OLD.js));

IF newContent = oldContent AND NEW.alias = OLD.alias AND NEW.visibility = OLD.visibility THEN
	SIGNAL SQLSTATE '45000'
	SET MESSAGE_TEXT = 'No change';
ELSEIF NEW.alias = OLD.alias AND NEW.visibility = OLD.visibility THEN
	SET crc32 = CRC32(CONCAT(OLD.title, OLD.description, OLD.html, OLD.scss, OLD.css, OLD.js, OLD.updated_at));
	INSERT INTO `t_content_history` (`id`, `crc32`, `title`, `description`, `html`, `scss`, `css`, `js`, `updator_id`, `updated_at`)
	VALUES (OLD.id, crc32, OLD.title, OLD.description, OLD.html, OLD.scss, OLD.css, OLD.js, OLD.updator_id, OLD.updated_at);
END IF;
END;

CREATE OR REPLACE VIEW `v_content_history` AS
SELECT
	`t_content_history`.`id` AS `id`,
	`t_content_history`.`crc32` AS `crc32`,
	`t_content_history`.`title` AS `title`,
	`t_content_history`.`description` AS `description`,
	`t_content_history`.`html` AS `html`,
	`t_content_history`.`scss` AS `scss`,
	`t_content_history`.`css` AS `css`,
	`t_content_history`.`js` AS `js`,
	`t_content_history`.`updator_id` AS `updator_id`,
	`t_content_history`.`updated_at` AS `updated_at`,
	concat( `t_user`.`firstname`, ' ', `t_user`.`lastname` ) AS `username`,
	`t_user`.`email` AS `email`
FROM `t_content_history`
LEFT JOIN `t_user` ON `t_content_history`.`updator_id` = `t_user`.`id`;

INSERT IGNORE INTO `t_content` (`id`, `alias`, `title`, `description`, `html`, `scss`, `css`, `js`) VALUES (1, '', 'Home page', 'This is the home page', '<div class="container">Hello world!</div>', '', '', '');

SET FOREIGN_KEY_CHECKS=1;