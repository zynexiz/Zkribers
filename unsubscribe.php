<?php
// Check if param UUID is valid
$uuid = (isset($_GET['uuid'])) ? $_GET['uuid'] : '' ;
$UUIDv4 = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';
preg_match($UUIDv4, $uuid) or die('Not valid UUID');

// Include WordPress core
require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php' );
require_once( dirname(__FILE__) . '/includes/send_mail.php' );

global $wpdb;

// Get the user from the UUID
$sql = "SELECT * FROM {$wpdb->prefix}es_subscribers WHERE uuid='$uuid'";
$subscriber = $wpdb->get_results($sql, 'ARRAY_A');

!empty($subscriber) or die('UUID has expired');

// Delete the subscriber and send unsubscribe e-mail if activated
$subscriber = $subscriber[0];
$wpdb->delete("{$wpdb->prefix}es_subscribers", ['uuid' => $uuid] );
es_sendmail( array(array('name' => $subscriber['name'], 'email' => $subscriber['email'])), 'US');
echo  $subscriber['email'] .  ' unsubscribed from mail list';
?>
