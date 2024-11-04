<?php

namespace EasyViewCounter;

if (!defined('ABSPATH'))
	exit;

class Counter 
{
    private $table_name;

    public function __construct() 
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'post_views_count';

        $this->init_hooks();
    }

    /**
     * Initializes the hooks.
     */
    private function init_hooks()
    {
        add_action('wp', array($this, 'incrementViewCount'));
    }

    /**
     * Increments the view count for a single post.
     */
    public function incrementViewCount() 
    {
        if (!is_single() || get_post_type() !== 'post') {
            return;
        }

        global $wpdb, $post;

        if (empty($post) || !isset($post->ID)) {
            return;
        }
        
        $post_id = $post->ID;
        $today = current_time('Y-m-d');

        $this->updateViewCount($wpdb, $post_id, $today);
    }

    /*
     * Updates or inserts the view count for a post on a specific date.
     */
    private function updateViewCount($wpdb, $post_id, $date)
    {
        $result = $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$this->table_name} (post_id, view_date, views) VALUES (%d, %s, 1)
                ON DUPLICATE KEY UPDATE views = views + 1",
                $post_id, $date
            )
        );

        if ($result === false) {
            error_log("Failed to update view count for post ID {$post_id} on {$date}");
        }
    }
}