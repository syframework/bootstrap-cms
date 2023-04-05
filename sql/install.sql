SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE `t_content` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `lang` varchar(3) NOT NULL DEFAULT '' COMMENT 'none',
  `alias` varchar(128) NOT NULL DEFAULT '',
  `title` varchar(128) NOT NULL DEFAULT '',
  `description` varchar(512) NOT NULL DEFAULT '',
  `html` mediumtext NOT NULL COMMENT 'none',
  `scss` text NOT NULL COMMENT 'none',
  `css` text NOT NULL COMMENT 'none',
  `js` text NOT NULL COMMENT 'none',
  `creator_id` int UNSIGNED NULL DEFAULT NULL COMMENT 'none',
  `updator_id` int UNSIGNED NULL DEFAULT NULL COMMENT 'none',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'none',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'none',
  PRIMARY KEY (`id`, `lang`),
  UNIQUE INDEX `alias`(`alias` ASC) USING BTREE,
  FOREIGN KEY (`creator_id`) REFERENCES `t_user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`updator_id`) REFERENCES `t_user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE `t_content_history` (
  `id` int UNSIGNED NOT NULL,
  `lang` varchar(3) NOT NULL,
  `crc32` bigint NOT NULL,
  `alias` varchar(128) NOT NULL,
  `title` varchar(128) NOT NULL,
  `description` varchar(512) NOT NULL,
  `html` mediumtext NOT NULL,
  `scss` text NOT NULL,
  `css` text NOT NULL,
  `js` text NOT NULL,
  `updator_id` int UNSIGNED NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`, `lang`, `crc32`),
  FOREIGN KEY (`updator_id`) REFERENCES `t_user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`id`, `lang`) REFERENCES `t_content` (`id`, `lang`) ON DELETE CASCADE ON UPDATE CASCADE
);

-- ----------------------------
-- Records of t_user_permission
-- ----------------------------
INSERT INTO `t_user_permission` VALUES ('content-create', 'Create a new content page');
INSERT INTO `t_user_permission` VALUES ('content-delete', 'Delete a content page');
INSERT INTO `t_user_permission` VALUES ('content-code', 'Update content page source code');
INSERT INTO `t_user_permission` VALUES ('content-update', 'Update a content page settings');
INSERT INTO `t_user_permission` VALUES ('content-update-inline', 'Update a content page with inline edition (need content-update)');

-- ----------------------------
-- Records of t_user_role
-- ----------------------------
INSERT INTO `t_user_role` VALUES ('content-admin', 'Content administrator');

-- ----------------------------
-- Records of t_user_role_has_permission
-- ----------------------------
INSERT INTO `t_user_role_has_permission` VALUES ('content-admin', 'content-create');
INSERT INTO `t_user_role_has_permission` VALUES ('content-admin', 'content-delete');
INSERT INTO `t_user_role_has_permission` VALUES ('content-admin', 'content-update');
INSERT INTO `t_user_role_has_permission` VALUES ('content-admin', 'content-update-inline');
INSERT INTO `t_user_role_has_permission` VALUES ('content-admin', 'content-code');

SET FOREIGN_KEY_CHECKS=1;