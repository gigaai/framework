<?php

namespace GigaAI\Storage;

interface StorageInterface
{
	/**
	 * Set User data
	 *
	 * @param mixed $user_id User ID. Or whole user info by passing array.
	 * @param mixed $key field to set. or whole user field by passing array
	 * @param $value $value. User field's value
	 *
	 * @return void
	 */
	public function set($user_id, $key, $value);

	/**
	 * Get User Info. If provided user
	 *
	 * @param string $user_id If not provided, load all users. Otherwise, load specified user.
	 * @param string $key If not provided. load all fields. Otherwise, load specified field.
	 * @param mixed $default Default value.
	 *
	 * @return bool|null|string
	 */
	public function get($user_id = '', $key = '', $default = '');

	/**
	 * Check if user or field of an user exists
	 *
	 * @param $user_id. User ID to be check.
	 * @param string $key If key is provided. Check if user field exists. Otherwise, check if user exists
	 *
	 * @return bool
	 */
	public function has($user_id, $key = '');

	/**
	 * Search in collection
	 *
	 * @param $terms
	 * @param string $relation
	 * @return mixed
	 */
	public function search($terms, $relation = 'and');

	public function addAnswer($answer, $node_type, $ask = '');

	public function getAnswers($node_type, $ask = '');
}