<?php

namespace EasyViewCounter;

if (!defined('ABSPATH'))
	exit;

class Database 
{
    private $table_name;
    private $charset_collate;

    public function __construct() 
    {
        global $wpdb;

        $this->table_name = $wpdb->prefix . 'post_views_count';
        $this->charset_collate = $wpdb->get_charset_collate();
    }

    /**
     * Plugin activation function to create the necessary database table.
     */
    public static function activate() 
    {
        $instance = new self();
        $instance->createDatabaseTable();
    }
    
    /**
     * Plugin deactivation function to delete the database table.
     */
    public static function deactivate() 
    {
        $instance = new self();
        $instance->deleteDatabaseTable();
    }

    /**
     * Creates the database table for storing post views and dates.
     */
    private function createDatabaseTable() 
    {
        $query = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id INT(11) NOT NULL AUTO_INCREMENT,
            post_id INT(11) NOT NULL,
            view_date DATE NOT NULL,
            views BIGINT(20) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY post_date (post_id, view_date)
        ) {$this->charset_collate};";

        require_once(ABSPATH . '/wp-admin/includes/upgrade.php');

        dbDelta($query);
    }

    /**
     * Deletes the database table for post views.
     * Ideally, we should ask user if he wants to delete database upon plugin deletion,
     * but for this example we only delete the table without alert
     */
    private function deleteDatabaseTable() 
    {
        global $wpdb;

        $query = "DROP TABLE IF EXISTS {$this->table_name};";
        $wpdb->query($query);
    }
}