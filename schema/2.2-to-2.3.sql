ALTER TABLE `bot_instances` ADD `page_id` VARCHAR(99) NULL AFTER `id`, ADD `instance_type` VARCHAR(30) NOT NULL AFTER `page_id`, ADD UNIQUE `unique_bot_instances_page_id` (`page_id`);
