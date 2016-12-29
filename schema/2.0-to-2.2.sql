/** Add Soft Delete to bot_nodes and bot_messages table **/
ALTER TABLE `bot_nodes` ADD `deleted_at` TIMESTAMP NULL AFTER `updated_at`;
ALTER TABLE `bot_messages` ADD `deleted_at` timestamp NULL DEFAULT NULL;

/** Add creator_id to all tables **/
ALTER TABLE `bot_instances` ADD `creator_id` int(10) UNSIGNED DEFAULT NULL;
ALTER TABLE `bot_nodes` ADD `creator_id` int(10) UNSIGNED DEFAULT NULL;
ALTER TABLE `bot_messages` ADD `creator_id` int(10) UNSIGNED DEFAULT NULL;
ALTER TABLE `bot_leads` ADD `creator_id` int(10) UNSIGNED DEFAULT NULL;

/** Add wait field to bot_messages table **/
ALTER TABLE `bot_messages` ADD `wait` varchar(255) DEFAULT NULL;