--
-- Table structure for table `bot_answers`
--

CREATE TABLE `bot_answers` (
  `id` int(10) UNSIGNED NOT NULL,
  `pattern` text COLLATE utf8_unicode_ci,
  `answers` text COLLATE utf8_unicode_ci NOT NULL,
  `sources` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `type` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bot_leads`
--

CREATE TABLE `bot_leads` (
  `id` int(10) UNSIGNED NOT NULL,
  `source` varchar(50) COLLATE utf8_unicode_ci DEFAULT 'facebook',
  `user_id` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `first_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `profile_pic` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locale` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `timezone` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `gender` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `_wait` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `_quick_save` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `linked_account` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `subscribe` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `auto_stop` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bot_leads_meta`
--

CREATE TABLE `bot_leads_meta` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `meta_key` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `meta_value` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bot_answers`
--
ALTER TABLE `bot_answers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bot_leads`
--
ALTER TABLE `bot_leads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `source` (`source`,`user_id`);

--
-- Indexes for table `bot_leads_meta`
--
ALTER TABLE `bot_leads_meta`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lead_id` (`user_id`,`meta_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bot_answers`
--
ALTER TABLE `bot_answers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `bot_leads`
--
ALTER TABLE `bot_leads`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `bot_leads_meta`
--
ALTER TABLE `bot_leads_meta`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

/** Since 1.2 **/
RENAME TABLE `bot_answers` TO `bot_nodes`;
ALTER TABLE `bot_nodes` ADD `wait` INT UNSIGNED NULL AFTER `answers`;
ALTER TABLE `bot_nodes` ADD `instance_id` INT UNSIGNED NULL AFTER `id`;
