<?php
/**
 * Install class.
 *
 * Contains functionality for when the plugin is first installed
 *
 * @package    EA_ShareCount
 * @author     Bill Erickson & Jared Atchison
 * @since      1.7.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2015
 */
class EA_Share_Count_Install {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.7.0
	 */
	public function __construct() {

		// When activated, trigger install method.
		register_activation_hook( EA_SHARE_COUNT_FILE, array( $this, 'install' ) );
	}

	/**
	 * Things to do on installation
	 *
	 * @since 1.7.0
	 */
	public function install() {

		do_action( 'ea_share_count_install' );

		// Set current version, to be referenced in future updates.
		update_option( 'ea_share_count_version', EA_SHARE_COUNT_VERSION );
	}
}
