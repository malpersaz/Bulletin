-- View log table.
CREATE TABLE `sys_view_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `table_name` varchar(50) NOT NULL,
  `item_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `created_at` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_view` (`table_name`,`item_id`,`ip_address`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
