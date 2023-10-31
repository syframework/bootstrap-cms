-- ----------------------------
-- Records of t_page
-- ----------------------------
INSERT IGNORE INTO `t_page` (id, title) VALUES ('content', 'Content');

-- ----------------------------
-- Records of t_user_permission
-- ----------------------------
INSERT IGNORE INTO `t_user_permission` VALUES ('content-read', 'Read private content page');
INSERT IGNORE INTO `t_user_permission` VALUES ('content-create', 'Create a new content page');
INSERT IGNORE INTO `t_user_permission` VALUES ('content-delete', 'Delete a content page');
INSERT IGNORE INTO `t_user_permission` VALUES ('content-code', 'Update content page source code');
INSERT IGNORE INTO `t_user_permission` VALUES ('content-update', 'Update a content page settings');
INSERT IGNORE INTO `t_user_permission` VALUES ('content-update-inline', 'Update a content page with inline edition (need content-update)');
INSERT IGNORE INTO `t_user_permission` VALUES ('content-history-view', 'View an old version from content history');
INSERT IGNORE INTO `t_user_permission` VALUES ('content-history-restore', 'Restore an old version from content history');

-- ----------------------------
-- Records of t_user_role
-- ----------------------------
INSERT IGNORE INTO `t_user_role` VALUES ('content-admin', 'Content administrator');
INSERT IGNORE INTO `t_user_role` VALUES ('content-manager', 'Content manager');

-- ----------------------------
-- Records of t_user_role_has_permission
-- ----------------------------
INSERT IGNORE INTO `t_user_role_has_permission` VALUES ('content-admin', 'content-read');
INSERT IGNORE INTO `t_user_role_has_permission` VALUES ('content-admin', 'content-create');
INSERT IGNORE INTO `t_user_role_has_permission` VALUES ('content-admin', 'content-delete');
INSERT IGNORE INTO `t_user_role_has_permission` VALUES ('content-admin', 'content-update');
INSERT IGNORE INTO `t_user_role_has_permission` VALUES ('content-admin', 'content-update-inline');
INSERT IGNORE INTO `t_user_role_has_permission` VALUES ('content-admin', 'content-code');
INSERT IGNORE INTO `t_user_role_has_permission` VALUES ('content-admin', 'content-history-view');
INSERT IGNORE INTO `t_user_role_has_permission` VALUES ('content-admin', 'content-history-restore');

INSERT IGNORE INTO `t_user_role_has_permission` VALUES ('content-manager', 'content-read');
INSERT IGNORE INTO `t_user_role_has_permission` VALUES ('content-manager', 'content-create');
INSERT IGNORE INTO `t_user_role_has_permission` VALUES ('content-manager', 'content-update');
INSERT IGNORE INTO `t_user_role_has_permission` VALUES ('content-manager', 'content-update-inline');
INSERT IGNORE INTO `t_user_role_has_permission` VALUES ('content-manager', 'content-history-view');
INSERT IGNORE INTO `t_user_role_has_permission` VALUES ('content-manager', 'content-history-restore');

INSERT IGNORE INTO `t_user_role_has_permission` VALUES ('admin', 'content-read');
INSERT IGNORE INTO `t_user_role_has_permission` VALUES ('admin', 'content-create');
INSERT IGNORE INTO `t_user_role_has_permission` VALUES ('admin', 'content-delete');
INSERT IGNORE INTO `t_user_role_has_permission` VALUES ('admin', 'content-update');
INSERT IGNORE INTO `t_user_role_has_permission` VALUES ('admin', 'content-update-inline');
INSERT IGNORE INTO `t_user_role_has_permission` VALUES ('admin', 'content-code');
INSERT IGNORE INTO `t_user_role_has_permission` VALUES ('admin', 'content-history-view');
INSERT IGNORE INTO `t_user_role_has_permission` VALUES ('admin', 'content-history-restore');

-- ----------------------------
-- Set the translation key to case sensitive
-- ----------------------------
ALTER TABLE `t_content_translation`
MODIFY `key` VARCHAR(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;

-- ----------------------------
-- Set the visibility option
-- ----------------------------
ALTER TABLE `t_content`
MODIFY COLUMN `visibility` enum('private','public','protected') NOT NULL DEFAULT 'private' COMMENT 'select';