<?php
/**
 * Initialize everything for admin interface
 *
 */
function setup_zkribers_admin_page() {
	$zkribers_page = add_options_page(
		'Subsribers', // Page title
		'Subsribers', // Menu text
		'manage_options', // Capability requirement to see the link
		'zkribers-settings', // Slug
		'zkribers_contents' // Call function to show the content
	);
	if ( ! class_exists( 'WP_List_Table' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	}

	wp_enqueue_script('zkribers_tinymce', plugin_dir_url(__FILE__) . 'tinymce/tinymce.min.js'); // Add TinyMCE
	add_action('load-'.$zkribers_page, 'zkribers_help_tab'); // Add help tab
	add_filter( "plugin_action_links_" . plugin_basename(__FILE__), 'zkribers_add_settings_link' ); // Add quick link from plugin addons page
}

/**
 * Setup the content
 *
 */

function zkribers_contents() {
	// Create a header in the default WordPress 'wrap' container
	echo '<div class="wrap">
		  <div id="icon-themes" class="icon32"></div>
		  <h2>Zkribers</h2>';

	// Create the tabs and show content
	$ZKRIBERS_DIR = dirname(__FILE__,2);
	$page = isset($_GET[ 'tab' ]) ? verify_data($_GET[ 'tab' ], 'tabs') : 'subscribers';
	require_once($ZKRIBERS_DIR . '/pages/subscribers.php');
	require_once($ZKRIBERS_DIR . '/pages/mail_templates.php');
	require_once($ZKRIBERS_DIR . '/pages/smtp_options.php');
	require_once($ZKRIBERS_DIR . '/pages/options.php');

	// Define the tabs
	$tabs = array(	'subscribers' => 'Subscribers',
					'mail_templates' => 'Mail templates',
					'smtp_options' => 'SMTP options',
					'options' => __('Options'));

	echo '<div id="icon-themes" class="icon32"><br></div>';
	echo '<h2 class="nav-tab-wrapper">';
	foreach( $tabs as $tab => $name ){
		$class = ( $tab == $page ) ? 'nav-tab-active' : '';
		 echo "<a class='nav-tab $class' href='?page=zkribers-settings&tab=$tab'>$name</a>";
	}
	echo '</h2>';

	call_user_func(verify_data( $page, 'tabs'));
	echo '</div>';
}

/**
 * Define the help drop down menu
 *
 */
function zkribers_help_tab() {
	/* Get current screen */
	$screen = get_current_screen();

	/* Add tabs to help section */
   $screen->add_help_tab( array(
        'id'		=> 'zkribers_help_subscibers',
        'title'		=> __('Subscribers'),
        'content'	=> "<p><strong>Subscribers page</strong></p>
						Here you manage you subscribers. Users that have registered will show up here. You can also add subscribers manually here.<br>
						Unverified e-mails will be deleted after one week if the mail isn't verified. Hovering over the yellow dot will show when the subscriber will be deleted.<br><br>
						If a subscriber is disabled, that user will be excluded when sending out new e-mails.",
    ) );

   $screen->add_help_tab( array(
        'id'		=> 'zkribers_help_templates',
        'title'		=> __('Templates'),
        'content'	=> "<p><strong>Templates page</strong></p>
						Here you can modify you templates look. The templates support HTML-styles so you can design your templates look and feel to your liking.
						The shortcodes you can insert into your template are integrated into TinyMCE editor and will be processed when sending out the e-mails.<br><br>
						If you disable a template, that corresponding function will also be disabled, fx. if you disable 'Verification template', no verification will
						be sent out to the subscriber. Activating it, will also enable two-step verification.",
    ) );

    $screen->add_help_tab( array(
        'id'		=> 'zkribers_help_smtp',
        'title'		=> __('SMTP options'),
        'content'	=> "<p><strong>Send options page</strong></p>
						Here you set up various options required to send out your e-mails. Note that you must have access to a SMTP server in order to use this plugin.
						Most providers require authentication before you can relay any e-mails. Usually this is the same as your normal login.<br><br>
						<strong>Note!</strong> The authentication is somewhat obscured in the database, but can be retrieved if your system is hacked!",
    ) );

    $screen->add_help_tab( array(
        'id'		=> 'zkribers_help_options',
        'title'		=> __('Options'),
        'content'	=> "<p><strong>Options page</strong></p>
						Gerenall options for Zkribers. You can set the number of rows to be shown in subscriber table list and select which post types you want to include when sending out e-mails.
						The WordPress default post types are posts and pages, but this plugin also support custom post types. All supported post types should be shown in the list.",
    ) );

    $screen->add_help_tab( array(
        'id'		=> 'zkribers_help_about',
        'title'		=> __('About'),
        'content'	=> "<p><strong>About</strong></p>Zkribers is a simple addon for Wordpress to manage e-mail subscriptions, and setup automatic e-mail notification for new posts.
						The plugin only support two-step verification if activated and standard SMTP servers. Third party e-mail services like Mailchimp is not supported.<br><br>
						This plugin is released under GPLv3 and is free to use and modify. For bug reports and more info please visit GitHub.
						To support the development, consider to subscribe or donate on <a href='https://liberapay.com/zynex'>Liberapay</a> or <a href='https://www.paypal.com/pools/c/8ldXVJfKHq'>Paypal</a>.",
    ) );

    /* Sidebar in the help section */
    $screen->set_help_sidebar( '<b>For more info</b><br><br>This plugin is released under GPLv3 and is free to use and modify. For bug reports and more info please visit <a href="https://github.com/zynexiz/email-subscribers">GitHub</a>.' );
}

/**
 * Add quick link to settings from plugin addons page
 *
 * @param string $link
 */

function zkribers_add_settings_link( $link ) {
    $settings_link = '<a href="options-general.php?page=zkribers-settings">' . __( 'Settings' ) . '</a>';
    array_push( $link, $settings_link );
  	return $link;
}

/**
 * Function to redirect to main plugin page
 *
 * @param string $option
 */

function redirect( $option = '') {
	$location = admin_url('options-general.php?page=zkribers-settings'.$option);
	echo '<p>Please wait, redirecting..</p>';
	echo "<meta http-equiv='refresh' content='0;url=$location' />";
	exit;
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
?>
