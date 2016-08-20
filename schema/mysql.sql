/**
 * Dump table bot_answers
 */
CREATE TABLE `bot_answers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pattern` text,
  `answer` text NOT NULL,
  `sources` varchar(255),
  `type` varchar(50),
  `status` VARCHAR(20),
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/** Dump table bot_leads **/
CREATE TABLE `bot_leads` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `source` VARCHAR(50) DEFAULT 'facebook',
  `source_id` VARCHAR(100) NOT NULL,
	`first_name` varchar(255),
	`last_name` varchar(255),
  `profile_pic` VARCHAR(500),
  `locale` VARCHAR(50),
  `timezone` VARCHAR(10),
	`gender` varchar(255),
	`email` VARCHAR (255),
	`phone` VARCHAR (255),
	`country` VARCHAR (255),
	`location` VARCHAR(255),
  `wait`  VARCHAR (255),
  `linked_account` VARCHAR (255),
  `subscribe` TINYINT,
  `auto_stop` VARCHAR(10),
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/** Dump table bot_leads_meta **/
CREATE TABLE `bot_leads_meta` (
  `id` int(0) UNSIGNED NOT NULL  AUTO_INCREMENT,
  `lead_id` INT(10) UNSIGNED NOT NULL,
  `meta_key` VARCHAR(255),
  `meta_value` LONGTEXT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
