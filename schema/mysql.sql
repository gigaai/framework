
-- --------------------------------------------------------

--
-- Table structure for table `bot_instances`
--

CREATE TABLE `bot_instances` (
  `id` varchar(150) NOT NULL,
  `name` varchar(255) NOT NULL,
  `meta` text,
  `status` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bot_leads`
--

CREATE TABLE `bot_leads` (
  `id` int(10) UNSIGNED NOT NULL,
  `instance_id` int(10) UNSIGNED DEFAULT NULL,
  `creator_id` int(10) UNSIGNED DEFAULT NULL,
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
  `is_payment_enabled` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `auto_stop` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bot_leads_meta`
--

CREATE TABLE `bot_leads_meta` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `meta_key` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `meta_value` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bot_messages`
--

CREATE TABLE `bot_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `instance_id` int(10) UNSIGNED DEFAULT NULL,
  `creator_id` int(10) UNSIGNED DEFAULT NULL,
  `to_lead` text,
  `to_channel` text,
  `content` text NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `wait` varchar(99) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `notification_type` varchar(20) DEFAULT 'REGULAR',
  `send_limit` varchar(10) DEFAULT '1',
  `sent_count` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `routines` varchar(255) DEFAULT NULL,
  `unique_id` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `start_at` timestamp NULL DEFAULT NULL,
  `end_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bot_nodes`
--

CREATE TABLE `bot_nodes` (
  `id` int(10) UNSIGNED NOT NULL,
  `instance_id` int(10) UNSIGNED DEFAULT NULL,
  `creator_id` int(10) UNSIGNED DEFAULT NULL,
  `pattern` text COLLATE utf8_unicode_ci,
  `answers` text COLLATE utf8_unicode_ci NOT NULL,
  `wait` varchar(99) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sources` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `type` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `notification_type` varchar(20) COLLATE utf8_unicode_ci DEFAULT 'REGULAR',
  `status` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tags` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bot_instances`
--
ALTER TABLE `bot_instances`
  ADD UNIQUE KEY `ix_instance_id` (`id`);

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
-- Indexes for table `bot_messages`
--
ALTER TABLE `bot_messages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_id` (`unique_id`);

--
-- Indexes for table `bot_nodes`
--
ALTER TABLE `bot_nodes`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

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
--
-- AUTO_INCREMENT for table `bot_messages`
--
ALTER TABLE `bot_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `bot_nodes`
--
ALTER TABLE `bot_nodes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
