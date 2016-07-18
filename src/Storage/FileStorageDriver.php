<?php

namespace Giga\Storage;
/**
 * File Storage Driver for Messenger Bot
 *
 * Storage brings fluent API for you to interact with database without hassle.
 * This class provide a set of methods for you to work with flat file database.
 * For WordPress, use WordPressStorageDriver
 *
 * Class FileStorageDriver
 * @package FMB
 */
class FileStorageDriver implements StorageInterface
{
	/**
	 * File path to be store
	 *
	 * @var string
	 */
	private $file;

	public function __construct()
	{
		$this->file = GIGA_CACHE_PATH . 'data.json';
	}

	/**
	 * Read file content
	 *
	 * @return string
	 */
	private function readFile()
	{
		return json_decode(file_get_contents($this->file), true);
	}

	/**
	 * Get User Info. If provided user
	 *
	 * @param string $user_id If not provided, load all users. Otherwise, load specified user.
	 * @param string $key If not provided. load all fields. Otherwise, load specified field.
	 * @param mixed $default Default value.
	 *
	 * @return bool|null|string
	 */
	public function get($user_id = '', $key = '', $default = null)
	{
		$users = $this->readFile();

		if (empty($user_id) || empty($users))
			return $users;

		if (empty($users[$user_id]))
			return false;

		$user = $users[$user_id];

		if (empty($key))
			return $user;

		return isset($user[$key]) ? $user[$key] : $default;
	}

	public function set($user, $key = '', $value = '')
	{
		$users = $this->readFile();

		if (is_string($user))
		{
			if (is_array($key))
			{
				$key['user_id'] = $user;

				return $this->set($key);
			} else
			{
				$users[$user][$key] = $value;
			}
		}

		if (is_array($user) && isset($user['user_id']))
		{
			$id = $user['user_id'];
			unset($user['user_id']);

			if ( ! isset($users[$id]))
				$users[$id] = array();

			foreach ($user as $key => $value)
			{
				$users[$id][$key] = $value;
			}
		}

		$users = json_encode($users);

		return file_put_contents($this->file, $users);
	}

	public function has($user_id, $key = '')
	{
		$users = $this->readFile();

		return $key === '' ? isset($users[$user_id]) : isset($users[$user_id][$key]);
	}

	public function search($terms, $relation = 'and')
	{
		$users = $this->get();

		foreach ($users as $index => $user)
		{
			$correct = $relation === 'and';

			foreach ($terms as $field => $value)
			{
				if (!isset($user[$field]))
					$user[$field] = false;

				$correct = $relation === 'and' ?
					$correct && $user[$field] == $value :
					$correct || $user[$field] == $value;
			}

			if ( ! $correct)
				unset($users[$index]);
		}

		return $users;
	}
}