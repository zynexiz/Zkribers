<?php
/**
 * Setup the content
 *
 */

function es_contents() {
	$tab = isset($_GET[ 'tab' ]) ? $_GET[ 'tab' ] : 'subscribers';

	// Create a header in the default WordPress 'wrap' container
	echo '<div class="wrap">
		  <div id="icon-themes" class="icon32"></div>
		  <h2>E-mail subscribers options</h2>';

	// Create the tabs and show content
	es_tabed_layout( $tab );

	echo '</div>';
}

/**
 * Define the help drop down menu
 *
 */
function es_help_tab() {
	/* Get current screen */
	$screen = get_current_screen();

	/* Add tabs to help section */
   $screen->add_help_tab( array(
        'id'		=> 'es_help_subscibers',
        'title'		=> __('Subscribers'),
        'content'	=> "<p><strong>Subscribers page</strong></p>
						Here you manage you subscribers. Users that have registered will show up here. You can also add subscribers manually here.<br>
						Unverified e-mails will be deleted after one week if the mail isn't verified. Hovering over the yellow dot will show when the subscriber will be deleted.<br><br>
						If a subscriber is disabled, that user will be excluded when sending out new e-mails.",
    ) );
    
   $screen->add_help_tab( array(
        'id'		=> 'es_help_templates',
        'title'		=> __('Templates'),
        'content'	=> "<p><strong>Templates page</strong></p>
						Here you can modify you templates look. The templates support HTML-styles so you can design your templates look and feel to your liking.
						The shortcodes you can insert into your template are integrated into TinyMCE editor and will be processed when sending out the e-mails.<br><br>
						If you disable a template, that corresponding function will also be disabled, fx. if you disable 'Verification template', no verification will
						be sent out to the subscriber. Activating it, will also enable two-step verification.",
    ) );

    $screen->add_help_tab( array(
        'id'		=> 'es_help_smtp',
        'title'		=> __('SMTP options'),
        'content'	=> "<p><strong>Send options page</strong></p>
						Here you set up various options required to send out your e-mails. Note that you must have access to a SMTP server in order to use this plugin.
						Most providers require authentication before you can relay any e-mails. Usually this is the same as your normal login.<br><br>
						<strong>Note!</strong> The authentication is somewhat obscured in the database, but can be retrieved if your system is hacked!",
    ) );

    $screen->add_help_tab( array(
        'id'		=> 'es_help_options',
        'title'		=> __('Options'),
        'content'	=> "<p><strong>Options page</strong></p>
						Gerenall options for E-mail subscriber. You can set the number of rows to be shown in subscriber table list and select which post types you want to include when sending out e-mails.
						The WordPress default post types are posts and pages, but this plugin also support custom post types. All supported post types should be shown in the list.",
    ) );

    $screen->add_help_tab( array(
        'id'		=> 'es_help_about',
        'title'		=> __('About'),
        'content'	=> "<p><strong>About</strong></p>E-mail subscribers is a simple addon for Wordpress to manage e-mail subscriptions, and setup automatic e-mail notification for new posts.
						The plugin only support two-step verification if activated and standard SMTP servers. Third party e-mail services like Mailchimp is not supported.<br><br>
						This plugin is released under GPLv3 and is free to use and modify. For bug reports and more info please visit GitHub.",
    ) );
     
    /* Sidebar in the help section */
    $screen->set_help_sidebar( '<b>For more info</b><br><br>This plugin is released under GPLv3 and is free to use and modify. For bug reports and more info please visit GitHub.' );
}

/**
 * Define tabs and show the content
 *
 * @param string $current - What tab to display
 */

function es_tabed_layout( $current = 'subscribers' ) {
	$ES_DIR = dirname(__FILE__,2);
	require_once($ES_DIR . '/pages/subscribers.php');
	require_once($ES_DIR . '/pages/mail_templates.php');
	require_once($ES_DIR . '/pages/smtp_options.php');
	require_once($ES_DIR . '/pages/options.php');

	// Define the tabs
	$tabs = array(	'subscribers' => 'Subscribers', 
					'mail_templates' => 'Mail templates',
					'smtp_options' => 'SMTP options',
					'options' => __('Options'));

	echo '<div id="icon-themes" class="icon32"><br></div>';
	echo '<h2 class="nav-tab-wrapper">';
	foreach( $tabs as $tab => $name ){
		$class = ( $tab == $current ) ? 'nav-tab-active' : '';
		 echo "<a class='nav-tab $class' href='?page=es-settings&tab=$tab'>$name</a>";
	}
	echo '</h2>';
	
	// Show the content for the tab
	switch ($current) {
		case 'subscribers':
			call_user_func('subscribers');
			break;
		case 'mail_templates':
			call_user_func('mail_templates');
			break;
		case 'smtp_options':
			call_user_func('smtp_options');
			break;
		case 'options':
			call_user_func('options');
			break;
		default:
			echo '<h2>Error, page do not exist.</h2>';
			return;
	}
	return $current;
}

/**
 * Register the hook to admin menu
 *
 */
 
function es_add_settings_link( $links ) {
    $settings_link = '<a href="options-general.php?page=es-settings">' . __( 'Settings' ) . '</a>';
    array_push( $links, $settings_link );
  	return $links;
}

/**
 * Function to redirect to main plugin page
 *
 * @param string $option
 */

function redirect( $option = '') {
	$location = admin_url('options-general.php?page='.$_GET['page'].$option);
	echo '<p>Please wait, redirecting..</p>';
	echo "<meta http-equiv='refresh' content='0;url=$location' />";
	exit;
}

?>
