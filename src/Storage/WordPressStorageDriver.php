<?php

namespace GigaAI\Storage;

/**
 * WordPress Storage Driver for Messenger Bot
 *
 * Storage brings fluent API for you to interact with database without hassle.
 * This class provide a set of methods for you to work with flat file database.
 * For WordPress, use WordPressStorageDriver
 *
 * For method usage. Check the interface.
 *
 * Class FileStorageDriver
 * @package Giga
 */
class WordPressStorageDriver implements StorageInterface
{
	private $db;

	public function __construct()
	{
		global $wpdb;

		if (empty($wpdb))
			throw new \Exception('You should run WordPress to use this storage driver');

		$this->db = $wpdb;
	}

	public function get($user_id = '', $key = '', $default = '')
	{
		$user = $this->getUser($user_id);

		if (empty($user))
			return null;

		if (empty($key))
			return $user;

		return isset($user[$key]) ? $user[$key] : $default;
	}

	public function getColumn($column)
	{
		return $this->db->get_col(
			$this->db->prepare("SELECT %s FROM {$this->db}giga_users WHERE 1=1", $column),
			ARRAY_N
		);
	}

	public function allUserId()
	{
		return $this->getColumn('user_id');
	}

	public function getUser($id)
	{
		return $this->db->get_row(
			$this->db->prepare("SELECT * FROM {$this->db}giga_users WHERE user_id = %s", $id),
			ARRAY_N
		);
	}

	public function set($user, $key = '', $value = '')
	{
		if (is_string($user))
		{
			if (is_array($key)) {
				$key['user_id'] = $user;

				return $this->set($key);
			}
			else {
				return $this->db->update('giga_users', array($key => $value), array(
					'user_id' => $user
				));
			}
		}

		if (is_array($user) && isset($user['user_id']))
		{
			$id = $user['user_id'];
			unset($user['user_id']);

			return $this->db->update('giga_users', $user, array(
				'user_id' => $id
			));
		}
	}

	public function has($user_id, $key = '')
	{
		$user = $this->getUser($user_id);

		return ! $user || empty($user[$key]);
	}

	public function search($terms, $relation = 'and')
	{
		$where = '';

		foreach ($terms as $field => $value)
		{
			$where .= "{$relation} {$field}='{$value}'";
		}

		return $this->db->get_results("SELECT * FROM {$this->db}giga_users WHERE 1=1 $where");
	}
}