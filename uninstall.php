<?php
	// if uninstall.php is not called by WordPress, die
	if (!defined('WP_UNINSTALL_PLUGIN')) {
		die;
	}
	
	// Remove options
	delete_option('es_options');
	delete_option('widget_es_widget');
	
	// Remove databases
	global $wpdb;
	$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}es_subscribers");
	$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}es_templates");
?>
