<?php
/**
 * Plugin Name: EA Share Count
 * Plugin URI:  https://github.com/jaredatch/EA-Share-Count
 * Description: A lean plugin that leverages SharedCount.com API to quickly retrieve, cache, and display various social sharing counts.
 * Author:      Bill Erickson & Jared Atchison
 * Version:     1.5.5
 *
 * EA Share Count is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * EA Share Count is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EA Share Count. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    EA_ShareCount
 * @author     Bill Erickson & Jared Atchison
 * @since      1.0.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2015
 */
 
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main class
 *
 * @since 1.0.0
 * @package EA_Share_Count
 */
final class EA_Share_Count {

	/**
	 * Instance of the class.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	private static $instance;

	/**
	 * Plugin version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $version = '1.5.5';

	/**
	 * Core instance
	 *
	 * @since 1.3.0
	 * @var object
	 */
	public $core;

	/**
	 * Admin instance
	 *
	 * @since 1.3.0
	 * @var object
	 */
	public $admin;

	/**
	 * Front-end instance
	 *
	 * @since 1.3.0
	 * @var object
	 */
	public $front;
	
	/** 
	 * Share Count Instance.
	 *
	 * @since 1.0.0
	 * @return EA_Share_Count
	 */
	public static function instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof EA_Share_Count ) ) {
			
			self::$instance = new EA_Share_Count;
			self::$instance->constants();
			self::$instance->includes();

			add_action( 'init', array( self::$instance, 'init' ) );
		}
		return self::$instance;
	}

	/**
	 * Define some constants.
	 *
	 * @since 1.3.0
	 */
	public function constants() {

		// Version
		define( 'EA_SHARE_COUNT_VERSION', $this->version );

		// Directory path
		define( 'EA_SHARE_COUNT_DIR', plugin_dir_path( __FILE__ ) );

		// Directory URL
		define( 'EA_SHARE_COUNT_URL', plugin_dir_url( __FILE__ ) );

		// Base name
		define( 'EA_SHARE_COUNT_BASE', plugin_basename( __FILE__ ) );
	}

	/**
	 * Load includes.
	 *
	 * @since 1.3.0
	 */
	public function includes() {

		require_once EA_SHARE_COUNT_DIR . 'includes/class-core.php';
		require_once EA_SHARE_COUNT_DIR . 'includes/class-admin.php';
		require_once EA_SHARE_COUNT_DIR . 'includes/class-front.php';

		// Plugin updater
		if ( is_admin() ) {
			require_once EA_SHARE_COUNT_DIR . 'updater/plugin-update-checker.php';
			$easc_updates = PucFactory::buildUpdateChecker(
				'http://sharecountplugin.com/plugin.json',
				__FILE__
			);
		}
	}

	/**
	 * Bootstap.
	 * 
	 * @since 1.3.0
	 */
	public function init() {

		$this->core  = new EA_Share_Count_Core;
		$this->admin = new EA_Share_Count_Admin;
		$this->front = new EA_Share_Count_Front;
	}

	/**
	 * Helper to access link method directly, for backwards compatibility.
	 *
	 * @since 1.3.0
	 * @return string
	 */
	public function link( $types = 'facebook', $id = false, $echo = true, $style = 'generic', $round = 2, $show_empty = '' ) {
		return $this->front->link( $types, $id, $echo, $style, $round, $show_empty );
	}

	/**
	 * Helper to access count method directly, for backwards compatibility.
	 *
	 * @since 1.3.0
	 * @return string
	 */
	public function count( $id = false, $type = 'facebook', $echo = false, $round = 2 ) {
		return $this->core->count( $id, $type, $echo, $round );
	}
}

/**
 * The function provides access to the sharing methods.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @since 1.0.0
 * @return object
 */
function ea_share() {
	return EA_Share_Count::instance();
}
ea_share();