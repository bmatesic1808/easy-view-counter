<?php
/*
Plugin Name: Easy View Counter
Description: This simple plugin offers a straightforward way to count and track post views, with the added capability of retroactively checking past views.
Version: 1.0
Author: Bruno Matešić
*/

use EasyViewCounter\Database;
use EasyViewCounter\Counter;
use EasyViewCounter\Frontend;

if (!defined('ABSPATH'))
	exit;

/**
 * Define plugin constants
 */
define('EASY_VIEW_COUNTER_VERSION', '1.0');
define('EASY_VIEW_COUNTER_URL', plugin_dir_url(__FILE__));
define('EASY_VIEW_COUNTER_PLUGIN_PATH', plugin_dir_path(__FILE__));

require_once (EASY_VIEW_COUNTER_PLUGIN_PATH . '/includes/database.php');
require_once (EASY_VIEW_COUNTER_PLUGIN_PATH . '/includes/counter.php');
require_once (EASY_VIEW_COUNTER_PLUGIN_PATH . '/includes/frontend.php');

class EasyViewCounter 
{
	public function __construct()
	{
		$this->setup();
	}

	/**
	 * Instance all required classes and register activation and deactivation hooks for Database class.
	 */
	private function setup() 
	{
		new Database();
		new Counter();
		new Frontend();

		register_activation_hook(__FILE__, ['EasyViewCounter\Database', 'activate']);
		register_deactivation_hook(__FILE__, ['EasyViewCounter\Database', 'deactivate']);
	}
}

/**
 * Initialize the plugin.
 */
new EasyViewCounter();

