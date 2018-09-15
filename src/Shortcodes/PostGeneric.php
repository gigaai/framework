<?php

namespace GigaAI\Shortcodes;
/**
 * This shortcode was built for WordPress only!
 *
 * @package GigaAI\Shortcodes
 */
class PostGeneric
{
    /**
     * @var array
     */
    private $keys = [
        // In query
        'error',
        'm',
        'p',
        'post_parent',
        'subpost',
        'subpost_id',
        'attachment',
        'attachment_id',
        'name',
        'static',
        'pagename',
        'page_id',
        'second',
        'minute',
        'hour',
        'day',
        'monthnum',
        'year',
        'w',
        'category_name',
        'tag',
        'cat',
        'tag_id',
        'author',
        'author_name',
        'feed',
        'tb',
        'paged',
        'meta_key',
        'meta_value',
        'preview',
        's',
        'sentence',
        'fields',
        'menu_order',
        'embed',

        // Others
        'post_type',
        'cache_results',
        'comment_status',
        'comments_per_page',
        'exact',
        'ignore_sticky_posts',
        'meta_compare',
        'meta_value_num',
        'menu_order',
        'no_paging',
        'no_found_rows',
        'offset',
        'order',
        'orderby',
        'page',
        'perm',
        'ping_status',
        'post_status',

        // Only available in Giga AI
        'tax'
    ];

    public $attributes = [
        'query_args'    => [],
        'title'         => 'post_title',
        'subtitle'      => 'post_excerpt',
        'image_url'     => 'thumbnail',
        'limit'         => 6,
        'no_found_rows' => true, // Skip count the found row for better performance
        'buttons'       => 'View, web_url, permalink'
    ];

    public function output()
    {
        $bubbles = [ ];

        $atts = $this->attributes;

        $fields = [ 'title', 'subtitle', 'image_url' ];

        // Bind $this->keys to query_args if not set
        $query_args = $this->parse_query_args( $atts );

        $posts = get_posts( $query_args );

        foreach ( $posts as $post ) {
            setup_postdata( $post );

            $bubble = [ ];

            foreach ( $fields as $field ) {
                if ( ! array_key_exists( $field, $atts ) ) {
                    continue;
                }

                $field_value = $this->get_binding_field( $post, $atts[ $field ] );

                // If $field is image_url but URL is not valid. Skip it.
                if ( $field === 'image_url' && ! filter_var( $field_value, FILTER_VALIDATE_URL ) ) {
                    continue;
                }

                if ( ! empty( $field_value ) ) {
                    $bubble[ $field ] = $field_value;
                }
            }

            $bubble['buttons'] = $this->parse_buttons( $atts['buttons'], $post );

            $bubbles[] = $bubble;
        }

        wp_reset_postdata();

        return json_encode( $bubbles );
    }

    /**
     * Parse simple query args.
     *
     * @param $atts
     *
     * @return array|mixed|object
     */
    private function parse_query_args( $atts )
    {
        // If is json. Parse it!
        if ( ! is_array( $atts['query_args'] ) ) {
            $arr = @json_decode( $atts['query_args'], true );

            if ( is_array( $arr ) ) {
                $atts['query_args'] = $arr;
            }
        }

        $query_args = $atts['query_args'];

        if ( is_numeric( $atts['limit'] ) && $atts['limit'] <= 6 && $atts['limit'] > 0 && ! isset( $query_args['posts_per_page'] ) ) {
            $query_args['posts_per_page'] = $atts['limit'];
        }

        foreach ( $atts as $key => $value ) {
            if ( in_array( $key, $this->keys ) ) {
                $query_args[ $key ] = $value;
            }
        }

        return $query_args;
    }

    private function get_binding_field( $post, $field )
    {
        if ( $field === 'the_excerpt' ) {
            return get_the_excerpt( $post );
        }

        if ( $field === 'thumbnail' && has_post_thumbnail( $post->ID ) ) {
            $image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'single-post-thumbnail' );

            return $image[0];
        }

        if ( isset( $post->{$field} ) ) {
            return $post->{$field};
        }

        $post_meta = get_post_meta( $post->ID, $field, true );

        if ( isset( $post_meta ) && is_string( $post_meta ) ) {
            return $post_meta;
        }

        return '';
    }

    /**
     * Parse button from pipe string to array
     * Example: View, web_url, permalink|Buy, postback, BUY_NOW
     *
     * @param String $buttons
     * @param \WP_Post $post
     *
     * @return array
     */
    private function parse_buttons( $buttons, $post = null )
    {
        $buttons = explode( '|', $buttons );

        $buttons = array_map( function ( $button ) use ( $post ) {

            $params = array_map( 'trim', explode( ',', $button ) );

            $button = [
                'title' => $params[0],
                'type'  => $params[1],
            ];

            if ( $button['type'] === 'postback' ) {
                $button['payload'] = $params[2];
            }

            if ( $button['type'] === 'web_url' ) {
                if ( $params[2] === 'permalink' ) {
                    $params[2] = get_permalink( $post );
                }

                if ( filter_var( $params[2], FILTER_VALIDATE_URL ) ) {
                    $button['url'] = $params[2];
                }
            }

            return $button;

        }, $buttons );

        return $buttons;
    }
}