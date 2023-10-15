-- ----------------------------
-- Table structure for t_content_translation
-- ----------------------------
CREATE TABLE `t_content_translation` (
  `lang` varchar(3) NOT NULL,
  `key` varchar(512) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`lang`, `key`) USING BTREE
);