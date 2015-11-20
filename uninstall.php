<?php
/**
 * Uninstall EA Share Count
 *
 * @package    EA_ShareCount
 * @author     Bill Erickson & Jared Atchison
 * @since      1.2.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2015
 */
 
// Exit if accessed directly
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

global $wpdb;

// Remove all plugin options
$wpdb->query( "DELETE FROM `{$wpdb->options}` WHERE `option_name` LIKE 'ea_share_count%'" );

// Remove all plugin post_meta keys
$wpdb->query( "DELETE FROM `{$wpdb->postmeta}` WHERE `meta_key` LIKE 'ea_share_count%'" );