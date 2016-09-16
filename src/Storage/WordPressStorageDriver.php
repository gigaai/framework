<?php
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

namespace GigaAI\Storage;

class WordPressStorageDriver implements StorageInterface
{
	private $db;

	private $fillable = array('user_id', 'first_name', 'last_name', 'profile_pic',
		'locale', 'timezone', 'gender', 'email', 'phone', 'country', 'location', '_wait',
		'linked_account', 'subscribe', 'auto_stop');

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
		return $this->db->get_col("SELECT {$column} FROM bot_leads");
	}

	public function allUserId()
	{
		return $this->getColumn('user_id');
	}

	public function getUser($id)
	{
		$user = $this->db->get_row(
			$this->db->prepare("SELECT * FROM bot_leads WHERE source = 'facebook' AND user_id = %s", $id),
			ARRAY_A
		);

		return $user;
	}

	public function set($user, $key = '', $value = '')
	{
		if (is_string($user))
		{
			if (is_array($key)) {
				$key['user_id'] = $user;

				return $this->set($key);
			}

			$user = array(
				'user_id' => $user,
				$key => $value
			);
		}

		if (is_array($user) && isset($user['user_id']))
			return $this->insertOrUpdateUser($user);
	}

	private function insertOrUpdateUser($user)
	{
		$meta = array();

		foreach ($user as $key => $value)
		{
			if ( ! in_array($key, $this->fillable)) {
				$meta[$key] = $value;

				unset($user[$key]);
			}
		}

		if ( ! $this->has($user))
			$this->db->insert('bot_leads', $user);
		else
			$this->db->update('bot_leads', $user, array(
				'user_id' => $user['user_id']
			));

		if ( ! empty( $meta ))
		{
			foreach ($meta as $key => $value)
			{

				$this->db->replace('bot_leads_meta', array(
					'user_id'       => $user['user_id'],
					'meta_key'      => $key,
					'meta_value'    => $value
				) );
			}
		}
	}

	public function has($user_id, $key = '')
	{
		$user = $this->getUser($user_id);

		return $user || ! empty($user[$key]);
	}

	public function search($terms, $relation = 'and')
	{
		$where = '';

		foreach ($terms as $field => $value)
		{
			$where .= "{$relation} {$field}='{$value}'";
		}

		return $this->db->get_results("SELECT * FROM bot_leads WHERE 1=1 $where");
	}

	/**
	 * Add Answer to the database
	 *
	 * @param $answer
	 * @param $node_type
	 * @param string $ask
	 */
	public function addAnswer($answers, $node_type, $ask = '' )
	{
		$row = $this->db->get_var("SELECT id FROM bot_answers WHERE type = '$node_type' AND pattern = '$ask' LIMIT 1");

		if ($row <= 0) {
			return $this->db->insert( 'bot_answers', array(
				'pattern' => $ask,
				'type'    => $node_type,
				'answers'  => json_encode($answers)
			) );
		} else {
			return $this->db->update('bot_answers', array(
				'answers' => json_encode($answers)
			), array(
				'pattern' => $ask,
				'type'    => $node_type,
			));
		}
	}

    public function getAnswers( $node_type = '', $ask = '' )
    {

        $where = '1 = 1';

        if ( ! empty($node_type))
            $where .= " AND type = '$node_type'";

        if ( ! empty( $ask ) ) {
            if ($ask[0] === '@')
                $where = " AND pattern = '$ask'";
            else
                $where .= " AND ($ask RLIKE pattern OR $ask LIKE pattern)";
        }

        $nodes = $this->db->get_results("SELECT `type`, `pattern`, `answers` FROM bot_answers WHERE $where", ARRAY_A);

        $output = array();

        foreach ($nodes as $node)
        {
            if ( ! empty($node['answers']))
            {
                $answers = json_decode($node['answers'], true);

                $answers = array_map( function ( $answer )
                {
                    if ( is_string( $answer ) && strpos( $answer, '[post-generic' ) >= 0 ) {

                        $shortcode = json_decode( do_shortcode( $answer ), true );

                        if ( ! empty( $shortcode ) )
                            return $shortcode;
                    }

                    return $answer;

                }, $answers);
            }

            // If default, then return only first row fetched!
            if ($node_type === 'default' && $node['type'] === 'default')
                return array('default' => $answers);

            if ($node['type'] === 'default') {
                $output['default'] = $answers;

                continue;
            }

            if ( ! isset($output[$node['type']]))
                $output[$node['type']] = array();

            if ( ! isset( $output[$node['type']][$node['pattern']]))
                $output[$node['type']][$node['pattern']] = array();

            $output[$node['type']][$node['pattern']] = $answers;
        }

        return $output;
    }

    public function removeAnswers($node_type, $ask)
    {

    }
}