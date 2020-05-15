<?php
/**
 * Plugin Name:			Zkribers
 * Plugin URI:			https://github.com/zynexiz/zkribers
 * Description:			Allows your subscribers to get a notification by email on new posts.
 * Version:				v0.3 BETA
 * Requires at least:	5.2
 * Requires PHP:		7.2
 * Author:				Michael Rydén
 * Author URI:			https://github.com/zynexiz
 * License:				GPLv3
 * License URI:			http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:			zkribers
 * Domain Path:			/lang/
 **/

 // If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ){ die; }

// Define database version, change on database structure change
if(!defined("ZKRIBERS_DB_VERSION")) define("ZKRIBERS_DB_VERSION", 2);

// Activities for cron jobb
require_once(dirname(__FILE__) . '/includes/send_mail.php');

// Add plugin activation and deactivation hook
register_activation_hook( __FILE__, 'zkribers_activate' );
register_deactivation_hook (__FILE__, 'zkribers_deactivate');

// Define the widget
add_action( 'widgets_init', 'zkribers_load_widget' );

if (is_admin()) {
	// Add plugin to Admin panel
	require_once(dirname(__FILE__) . '/includes/admin-functions.php');
	add_action('admin_menu', 'setup_zkribers_admin_page');
}

/**
 * Initial setup when plugin is activated
 *
 */
function zkribers_activate() {
	$opt = get_option('zkribers_options');

	if (!$opt) {

		add_option( 'zkribers_options', array(), false );

		$opt = array(
			'zkribers_db_version' => ZKRIBERS_DB_VERSION,
			'row_per_page' => 10,
			'cron_schedule' => 60,
			'post_type' => array('post','page'),
			'last_send' => current_time('mysql'),
			'send_interval' => '+1 day',
			'hostname' => '',
			'authentication' => '',
			'port' => '587',
			'login' => '',
			'pwd' => '',
			'protocol' => 'tls',
			'email' => '',
			'name' => ''
		);
		update_option( 'zkribers_options', $opt);
	}

	// Import functions for dbDelta()
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();

	// Check is databases exist or table need updating
	$table_name = $wpdb->prefix . "zkribers_subscribers";
	$db_try = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name;
	if($db_try || $opt['zkribers_db_version'] != ZKRIBERS_DB_VERSION) {
		$sql = "CREATE TABLE $table_name (
				id tinyint NOT NULL AUTO_INCREMENT,
				uuid tinytext NULL,
				name tinytext NOT NULL,
				email tinytext NOT NULL,
				time datetime NOT NULL,
				verified tinyint(1) NOT NULL,
				purge_date datetime NULL,
				UNIQUE KEY id (id)
			) $charset_collate;";
		dbDelta( $sql );
	}

	$table_name = $wpdb->prefix . "zkribers_templates";
	$db_try = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name;
	if($db_try || $opt['zkribers_db_version'] != ZKRIBERS_DB_VERSION) {
		$sql = "CREATE TABLE $table_name (
				id tinyint NOT NULL AUTO_INCREMENT,
				slug char(10) NOT NULL,
				title tinytext NOT NULL,
				subject tinytext NOT NULL,
				description tinytext NOT NULL,
				template mediumtext NOT NULL,
				active tinyint(1) NOT NULL,
				UNIQUE KEY id (id)
			) $charset_collate;";
		dbDelta( $sql );

		$templates_dir = dirname(__FILE__,1) . '/includes/templates/' . strstr(get_locale(),'_',true);
		if ($db_try) {
			$wpdb->insert( $table_name,
				array(
					'title' => 'Verification template',
					'description' => 'Template for opt-in verification before activating user',
					'active' => true,
					'subject' => base64_encode('Verify your subscription for #sitename#'),
					'template' => base64_encode(file_get_contents($templates_dir.'/verification.php')),
					'slug' => 'VT'),
				array( '%s', '%s', '%d', '%s', '%s' ) );

			$wpdb->insert( $table_name,
				array(
					'title' => 'Welcome template',
					'description' => 'Welcome message sent when user subscribed and is verified',
					'active' => true,
					'subject' => base64_encode('Welcome to #sitename#'),
					'template' => base64_encode(file_get_contents($templates_dir.'/welcome.php')),
					'slug' => 'WT'),
				array( '%s', '%s', '%d', '%s', '%s' ) );

			$wpdb->insert( $table_name,
				array(
					'title' => 'Posts template',
					'description' => 'Main template to inform about new posts that has been published',
					'active' => true,
					'subject' => base64_encode('#sitename# has #newposts# new posts'),
					'template' => base64_encode(file_get_contents($templates_dir.'/posts.php')),
					'slug' => 'PT'),
				array( '%s', '%s', '%d', '%s', '%s' ) );

			$wpdb->insert( $table_name,
				array(
					'title' => 'Unsubscribed template',
					'description' => 'Template for sending a message after user has unsubscribed',
					'active' => true,
					'subject' => base64_encode('Unsubscribe confirmaion'),
					'template' => base64_encode(file_get_contents($templates_dir.'/unsubscribed.php')),
				'slug' => 'US'),
				array( '%s', '%s', '%d', '%s', '%s' ) );
		}
	}

	// Update database version in option if changed
	if ($opt['zkribers_db_version'] != ZKRIBERS_DB_VERSION) {
		$opt['zkribers_db_version'] = ZKRIBERS_DB_VERSION;
		update_option( 'zkribers_options', $opt);
	}

	if (! wp_next_scheduled ( 'zkribers_cron_jobbs' )) {
		wp_schedule_event(time(), 'hourly', 'zkribers_cron_jobbs');
    }
}

/**
 * Clean upp after ourself when deactivated
 *
 */

function zkribers_deactivate() {
	$timestamp = wp_next_scheduled( 'zkribers_cron_jobbs' );
	wp_unschedule_event( $timestamp, 'zkribers_cron_jobbs' );
	wp_clear_scheduled_hook('zkribers_cron_jobbs');
}

/**
 * Register and load the widget
 *
 */

function zkribers_load_widget() {
    register_widget( 'zkribers_widget' );
}

/**
 * Define the widget class
 *
 */

class zkribers_widget extends WP_Widget {

	function __construct() {
		parent::__construct(

		// Widget base ID
		'zkribers_widget',

		// Widget name will appear in UI
		'Zkribers',

		// Widget description
		array( 'description' => 'Widget to let users subscribe to you posts' )
		);
	}

// Creating widget front-end

	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] );
		$description = apply_filters( 'widget_title', $instance['description'] );
		$submitbutton = apply_filters( 'widget_title', $instance['submit'] );

		// before and after widget arguments are defined by themes
		echo $args['before_widget'];
		if ( ! empty( $title ) ) { echo $args['before_title'] . $title . $args['after_title']; }

		if (isset($_POST['zkribers_submitmail'])) {
			submit_email();
			echo '<div style="font-size: 0.9em; text-align: center; "><em>Thank you!<br>Your e-mail has been added.</em></div>';
		} else {
			if ( ! empty( $title ) ) {
				echo '<div style="font-size: 0.9em; text-align: center; "><em>'.$description.'</em></div>';
				?>
				<form method="post" style="margin-top: 15px;">
					<input type="text" name="zkribers_name" placeholder="Your name" required style="text-align: center; background: rgba(0,0,0,0.1); color: black; width: 100%; border: none; outline:none; height:50px;"><br>
					<input type="email" name="zkribers_email" placeholder="E-mail" required style="text-align: center; background: rgba(0,0,0,0.1); color: black; width: 100%; margin-top: 15px; margin-bottom: 15px; border: none; outline:none; height:50px;"><br>
					<input type="submit" name="zkribers_submitmail" value="<?php echo $submitbutton;?>" style="width: 100%; height:50px; background:white; border:0px none; text-size: 80%; border-radius: 0px; text-transform:uppercase; font-weight: bold;">
				</form>
			<?php } }
		echo $args['after_widget'];
	}

// Widget Backend
	public function form( $instance ) {
		$title = ( isset( $instance[ 'title' ] ) ) ? $instance[ 'title' ] : 'Subscribe';
		$description = ( isset( $instance[ 'description' ] ) ) ? $instance[ 'description' ] : 'Enter your e-mail to subscribe';
		$submitbutton = ( isset( $instance[ 'submit' ] ) ) ? $instance[ 'submit' ] : 'Sign up now!';
		// Widget admin form
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		<label for="<?php echo $this->get_field_id( 'description' ); ?>"><?php _e( 'Description:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'description' ); ?>" name="<?php echo $this->get_field_name( 'description' ); ?>" type="text" value="<?php echo esc_attr( $description ); ?>" />
		<label for="<?php echo $this->get_field_id( 'submit' ); ?>"><?php _e( 'Submit button:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'submit' ); ?>" name="<?php echo $this->get_field_name( 'submit' ); ?>" type="text" value="<?php echo esc_attr( $submitbutton ); ?>" />
		</p>
		<?php
	}

// Updating widget replacing old instances with new
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['description'] = ( ! empty( $new_instance['description'] ) ) ? strip_tags( $new_instance['description'] ) : '';
		$instance['submit'] = ( ! empty( $new_instance['submit'] ) ) ? strip_tags( $new_instance['submit'] ) : '';
		return $instance;
	}
}

/**
 * Send out welcome or verify e-mail when new user subscribed
 *
 */
function submit_email() {
	global $wpdb;
	$sql = "SELECT active FROM {$wpdb->prefix}zkribers_templates WHERE slug='VT'";
	$query = $wpdb->get_results($sql, 'ARRAY_A');
	$verify = ($query[0]['active'] == 1) ? 'VT' : 'WT';
	$table_name = $wpdb->prefix . 'zkribers_subscribers';
	$s_name = sanitize_text_field($_POST['zkribers_name']);
	$s_mail = sanitize_email($_POST['zkribers_email']);

	$wpdb->insert( $table_name,
		array(
			'name' => $s_name,
			'email' => $s_mail,
			'time' => current_time( 'mysql' ),
			'verified' => $verify,
			'purge_date' => ($verify == 'WT') ? NULL : date('Y-m-d H:i:s',strtotime("+1 week", current_time('timestamp')))),
		array( '%s', '%s', '%s', '%d') );

		zkribers_sendmail( array(array('name' => $s_name, 'email' => $s_mail)), $verify);
}
