<?php
/**
 * Plugin Name:       es-email-subscribers
 * Description:       Allows your subscribers to get a notification by email on new posts.
 * Version:           v0.1 BETA
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Michael Rydén
 * Author URI:        https://github.com/zynexiz
 * License:						GPLv3
 * Text Domain:       es-email-subscribers
 **/

// Define database version, change on database structure change
if(!defined("ES_DB_VERSION")) define("ES_DB_VERSION", 2);

// Activities for cron jobb
require_once(dirname(__FILE__) . '/includes/send_mail.php');

// Add plugin activation and deactivation hook
register_activation_hook( __FILE__, 'es_activate' );
register_deactivation_hook (__FILE__, 'es_deactivate');

add_action( 'widgets_init', 'es_load_widget' );

if (is_admin()) {
	// Add plugin to Admin panel
	add_action('admin_menu', 'setup_es_admin_page');
}

/**
 * Initialize everything
 *
 */

function setup_es_admin_page() {
	$ES_DIR = dirname(__FILE__);
	require_once($ES_DIR . '/includes/functions.php');

	$es_page = add_options_page(
		'Subsribers', // Page title
		'Subsribers', // Menu text
		'manage_options', // Capability requirement to see the link
		'es-settings', // Slug
		'es_contents' // Call function to show the content
	);
	if ( ! class_exists( 'WP_List_Table' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	}

	wp_enqueue_script('es_tinymce', plugin_dir_url(__FILE__) . 'includes/tinymce/tinymce.min.js'); // Add TinyMCE
	add_action('load-'.$es_page, 'es_help_tab'); // Add help tab
	add_filter( "plugin_action_links_" . plugin_basename(__FILE__), 'es_add_settings_link' ); // Add quick link from plugin addons page
}

/**
 * Register and load the widget
 *
 */

function es_load_widget() {
    register_widget( 'es_widget' );
}

function submit_email () {
	global $wpdb;
	$sql = "SELECT active FROM {$wpdb->prefix}es_templates WHERE slug='VT'";
	$query = $wpdb->get_results($sql, 'ARRAY_A');
	$verify = ($query[0]['active'] == 1) ? 1 : 2;
	$table_name = $wpdb->prefix . 'es_subscribers';

	$wpdb->insert( $table_name,
		array(
			'name' => $_POST['es_name'],
			'email' => $_POST['es_email'],
			'time' => current_time( 'mysql' ),
			'verified' => $verify,
			'purge_date' => ($verify == 2) ? NULL : date('Y-m-d H:i:s',strtotime("+1 week", current_time('timestamp')))),
		array( '%s', '%s', '%s', '%d') );

	if ($verify < 2) {
		es_sendmail( array(array('name' => $_POST['es_name'], 'email' => $_POST['es_email'])), 'VT');
	} else {
		es_sendmail( array(array('name' => $_POST['es_name'], 'email' => $_POST['es_email'])), 'WT');
	}
}

/**
 * Initial setup when plugin is activated
 *
 */

function es_activate() {
	$opt = get_option('es_options');

	if (!$opt) {

		add_option( 'es_options', array(), false );

		$opt = array(
			'es_db_version' => ES_DB_VERSION,
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
		update_option( 'es_options', $opt);
	}

	// Import functions for dbDelta()
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();

	// Check is databases exist or table need updating
	$table_name = $wpdb->prefix . "es_subscribers";
	$db_try = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name;
	if($db_try || $opt['es_db_version'] != ES_DB_VERSION) {
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

	$table_name = $wpdb->prefix . "es_templates";
	$db_try = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name;
	if($db_try || $opt['es_db_version'] != ES_DB_VERSION) {
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

		$templates_dir = dirname(__FILE__,1) . '/includes/templates/sv/';
		if ($db_try) {
			$wpdb->insert( $table_name,
				array(
					'title' => 'Verification template',
					'description' => 'Template for opt-in verification before activating user',
					'active' => true,
					'subject' => 'Verify your subscription for #sitename#',
					'template' => file_get_contents($templates_dir.'verification.php'),
					'slug' => 'VT'),
				array( '%s', '%s', '%d') );

			$wpdb->insert( $table_name,
				array(
					'title' => 'Welcome template',
					'description' => 'Welcome message sent when user subscribed and is verified',
					'active' => true,
					'subject' => 'Welcome to #sitename#',
					'template' => file_get_contents($templates_dir.'welcome.php'),
					'slug' => 'WT'),
				array( '%s', '%s', '%d') );

			$wpdb->insert( $table_name,
				array(
					'title' => 'Posts template',
					'description' => 'Main template to inform about new posts that has been published',
					'active' => true,
					'subject' => '#sitename# has #newposts# new posts',
					'template' => file_get_contents($templates_dir.'posts.php'),
					'slug' => 'PT'),
				array( '%s', '%s', '%d') );

			$wpdb->insert( $table_name,
				array(
					'title' => 'Unsubscribed template',
					'description' => 'Template for sending a message after user has unsubscribed',
					'active' => true,
					'subject' => 'Unsubscribe confirmaion',
					'template' => file_get_contents($templates_dir.'unsubscribed.php'),
				'slug' => 'US'),
				array( '%s', '%s', '%d') );
		}
	}

	// Update database version in option if changed
	if ($opt['es_db_version'] != ES_DB_VERSION) {
		$opt['es_db_version'] = ES_DB_VERSION;
		update_option( 'es_options', $opt);
	}

	if (! wp_next_scheduled ( 'es_cron_jobbs' )) {
		wp_schedule_event(time(), 'hourly', 'es_cron_jobbs');
    }
}

/**
 * Clean upp after ourself when deactivated
 *
 */

function es_deactivate() {
	$timestamp = wp_next_scheduled( 'es_cron_jobbs' );
	wp_unschedule_event( $timestamp, 'es_cron_jobbs' );
	wp_clear_scheduled_hook('es_cron_jobbs');

	delete_option('es_debug');
}

/**
 * Define the widget class
 *
 */

class es_widget extends WP_Widget {

	function __construct() {
		parent::__construct(

		// Widget base ID
		'es_widget',

		// Widget name will appear in UI
		'E-mail subscribers',

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

		if (isset($_POST['es_submitmail'])) {
			submit_email();
			echo '<div style="font-size: 0.9em; text-align: center; "><em>Thank you!<br>Your e-mail has been added.</em></div>';
		} else {
			if ( ! empty( $title ) ) {
				echo '<div style="font-size: 0.9em; text-align: center; "><em>'.$description.'</em></div>';
				?>
				<form method="post" style="margin-top: 15px;">
					<input type="text" name="es_name" placeholder="Your name" required style="text-align: center; background: rgba(0,0,0,0.1); color: black; width: 100%; border: none; outline:none; height:50px;"><br>
					<input type="email" name="es_email" placeholder="E-mail" required style="text-align: center; background: rgba(0,0,0,0.1); color: black; width: 100%; margin-top: 15px; margin-bottom: 15px; border: none; outline:none; height:50px;"><br>
					<input type="submit" name="es_submitmail" value="<?php echo $submitbutton;?>" style="width: 100%; height:50px; background:white; border:0px none; text-size: 80%; border-radius: 0px; text-transform:uppercase; font-weight: bold;">
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
