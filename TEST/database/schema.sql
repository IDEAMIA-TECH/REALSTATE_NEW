-- System Settings Table
CREATE TABLE IF NOT EXISTS `system_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 