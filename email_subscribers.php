<?php
/**
 * Plugin Name:			E-mail subscribers for SMTP
 * Plugin URI:			https://github.com/zynexiz/email-subscribers
 * Description:			Allows your subscribers to get a notification by email on new posts.
 * Version:				v0.1 BETA
 * Requires at least:	5.2
 * Requires PHP:		7.2
 * Author:				Michael Rydén
 * Author URI:			https://github.com/zynexiz
 * License:				GPLv3
 * License URI:			http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:			es-email-subscribers
 * Domain Path:			/lang/
 **/

 // If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ){ die; }

// Define database version, change on database structure change
if(!defined("ES_DB_VERSION")) define("ES_DB_VERSION", 2);

// Activities for cron jobb
require_once(dirname(__FILE__) . '/includes/send_mail.php');

// Add plugin activation and deactivation hook
register_activation_hook( __FILE__, 'es_activate' );
register_deactivation_hook (__FILE__, 'es_deactivate');

// Define the widget
add_action( 'widgets_init', 'es_load_widget' );

if (is_admin()) {
	// Add plugin to Admin panel
	add_action('admin_menu', 'setup_es_admin_page');

}

/**
 * Verify data from post or get type and return the value
 *
 * @param mixed $data
 * @param string $type
 * @param bool $abort_on_error
 * @param string $result
 */
 function verify_data( $data, $type, $abort_on_error = true) {
	switch ($type) {
		case 'tabs':
			$error_body = 'Page not found, error 404.';
			$regex = '/^(subscribers|mail_templates|smtp_options|options)$/';
			break;
		case 'email':
			$regex = '/^(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22))(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\]))$/iD';
			break;
		case 'int':
			$regex = '/^[0-9]*$/';
			break;
		case 'name':
			$regex = '/^[^(){}:;+#?$^"%*!&£=\/~@0123456789]+$/';
			break;
		case 'order':
			$regex = '/^(name|email|verified|time|asc|desc)$/';
			break;
		case 'hostname';
			$regex = '/^((\*)|((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)|((\*\.)?([a-zA-Z0-9-]+\.){0,5}[a-zA-Z0-9-][a-zA-Z0-9-]+\.[a-zA-Z]{2,63}?))$/';
			break;
		case 'post_type';
			$post_types = implode('|',array_merge(array('post' => 'post', 'page' => 'page'), get_post_types( array('public'   => true, '_builtin' => false) )));
			$regex = '/\b(?:'.$post_types.')\b/';
			$data = implode(' ', $data);
			break;
		default:
			show_infobox('It look like there was a problem.', 'Verification set for \'' .$type. '\' not defined.', '#e5b61e');
			return false;
	}

	if (!preg_match_all($regex, $data, $result) && $abort_on_error) {
		show_infobox('It look like there was a problem.', (isset($error_body)) ? $error_body : 'Data verification test failed. Aborting.', '#F98A89');
		die;
	}

	return (isset($result[0])?implode(',',$result[0]):false);
}

/**
 * Show information box in a customized $color
 *
 * @param string $title
 * @param string $body_text
 * @param string $hexColor
 */
function show_infobox($title, $body_text, $hexColor) {
	$lightColor = ltrim($hexColor, '#');

	if (strlen($lightColor) == 3) {
		$lightColor = $lightColor[0] . $lightColor[0] . $lightColor[1] . $lightColor[1] . $lightColor[2] . $lightColor[2];
	}

	$lightColor = array_map('hexdec', str_split($lightColor, 2));

	foreach ($lightColor as & $color) {
		$adjustableLimit = 0.85 < 0 ? $color : 255 - $color;
		$adjustAmount = ceil($adjustableLimit * 0.85);

		$color = str_pad(dechex($color + $adjustAmount), 2, '0', STR_PAD_LEFT);
	}
	$lightColor = '#' . implode($lightColor);

	echo '<table cellspacing="0" cellpadding="0" style="width: 98%; margin-top: 10px; margin-bottom: 10px; margin-left: 1%; margin-right: 1%;">
		<tr><td style="border-radius: 4px 0px 0px 4px; border: 1px solid '.$hexColor.'; background-color: '.$hexColor.'; width: 50px; text-align: center; vertical-align: top; padding-top: 15px;"><span style="font-size: 22px; color: white;">🛈</span></td>
		<td style="border-radius: 0px 4px 4px 0px; border: 1px solid '.$hexColor.'; background-color: '.$lightColor.'; padding: 15px;">
			<span style="font-size: 16px; color: '.$hexColor.'; font-weight: bold;">'.$title.'</span><br style="margin-bottom: 10px;">
			<span style="font-size: 14px; color: black;">'.$body_text.'</span>
		</td></tr>
	</table>';
}

/**
 * Initialize everything for admin interface
 *
 */
function setup_es_admin_page() {
	$ES_DIR = dirname(__FILE__);
	require_once($ES_DIR . '/includes/admin-functions.php');

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

		$templates_dir = dirname(__FILE__,1) . '/includes/templates/en/';
		if ($db_try) {
			$wpdb->insert( $table_name,
				array(
					'title' => 'Verification template',
					'description' => 'Template for opt-in verification before activating user',
					'active' => true,
					'subject' => 'Verify your subscription for #sitename#',
					'template' => base64_encode(file_get_contents($templates_dir.'verification.php')),
					'slug' => 'VT'),
				array( '%s', '%s', '%d', '%s', '%s' ) );

			$wpdb->insert( $table_name,
				array(
					'title' => 'Welcome template',
					'description' => 'Welcome message sent when user subscribed and is verified',
					'active' => true,
					'subject' => 'Welcome to #sitename#',
					'template' => base64_encode(file_get_contents($templates_dir.'welcome.php')),
					'slug' => 'WT'),
				array( '%s', '%s', '%d', '%s', '%s' ) );

			$wpdb->insert( $table_name,
				array(
					'title' => 'Posts template',
					'description' => 'Main template to inform about new posts that has been published',
					'active' => true,
					'subject' => '#sitename# has #newposts# new posts',
					'template' => base64_encode(file_get_contents($templates_dir.'posts.php')),
					'slug' => 'PT'),
				array( '%s', '%s', '%d', '%s', '%s' ) );

			$wpdb->insert( $table_name,
				array(
					'title' => 'Unsubscribed template',
					'description' => 'Template for sending a message after user has unsubscribed',
					'active' => true,
					'subject' => 'Unsubscribe confirmaion',
					'template' => base64_encode(file_get_contents($templates_dir.'unsubscribed.php')),
				'slug' => 'US'),
				array( '%s', '%s', '%d', '%s', '%s' ) );
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

/**
 * Send out welcome or verify e-mail when new user subscribed
 *
 */
function submit_email() {
	global $wpdb;
	$sql = "SELECT active FROM {$wpdb->prefix}es_templates WHERE slug='VT'";
	$query = $wpdb->get_results($sql, 'ARRAY_A');
	$verify = ($query[0]['active'] == 1) ? 'VT' : 'WT';
	$table_name = $wpdb->prefix . 'es_subscribers';
	$s_name = sanitize_text_field($_POST['es_name']);
	$s_mail = sanitize_email($_POST['es_email']);

	$wpdb->insert( $table_name,
		array(
			'name' => $s_name,
			'email' => $s_mail,
			'time' => current_time( 'mysql' ),
			'verified' => $verify,
			'purge_date' => ($verify == 'WT') ? NULL : date('Y-m-d H:i:s',strtotime("+1 week", current_time('timestamp')))),
		array( '%s', '%s', '%s', '%d') );

		es_sendmail( array(array('name' => $s_name, 'email' => $s_mail)), $verify);
}
