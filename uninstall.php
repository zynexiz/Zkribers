<?php
	// if uninstall.php is not called by WordPress, die
	if (!defined('WP_UNINSTALL_PLUGIN')) {
		die;
	}

	// Remove options
	delete_option('zkribers_options');
	delete_option('widget_zkribers_widget');

	// Remove databases
	global $wpdb;
	$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}zkribers_subscribers");
	$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}zkribers_templates");
?>
