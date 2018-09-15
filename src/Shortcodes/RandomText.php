<?php

namespace GigaAI\Shortcodes;

/**
 * The [random-text] shortcode.
 *
 * @package GigaAI\Shortcodes
 */
class RandomText
{
    public $attributes = null;

    public $content = '';

    public function output()
    {
        $rows = preg_split('/\r\n|[\r\n]/', $this->content);

        foreach ($rows as $n => $row) {

            if (empty($row)) {
                unset($rows[$n]);
                continue;
            }

            preg_match_all('/\((.*?)\)/', $row, $matches);

            if ( ! empty($matches[1])) {
                foreach ($matches[1] as $index => $patterns) {
                    $patterns = explode('|', $patterns);

                    $pick = $patterns[array_rand($patterns)];

                    $row = str_replace($matches[0][$index], $pick, $row);
                }
            }

            $rows[$n] = $row;
        }

        // Pick a random string from source
        return $rows[array_rand($rows)];
    }
}