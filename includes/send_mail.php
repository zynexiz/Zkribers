<?php
// Setup SMTP for wp_mail()
add_action( 'phpmailer_init', 'send_smtp_email' );
add_action( 'es_cron_jobbs' , 'es_cron_function' );

function es_cron_function() {
	global $wpdb;
	$sql = "SELECT * FROM {$wpdb->prefix}es_subscribers";
	$query = $wpdb->get_results($sql, 'ARRAY_A');
	$opt = get_option('es_options');

	foreach ($query as $subscriber) {
		// Purge old unverified subscribers after a week
		if (!empty($subscriber['purge_date']) && $subscriber['purge_date'] < date('Y-m-d H:i:s',current_time('timestamp'))) {
			$wpdb->delete(
				"{$wpdb->prefix}es_subscribers",
				['id' => $subscriber['id']],
				['%d']
			);
		}
		
		// Get subscribers and send out e-mail if new posts been published
		if ($subscriber['verified'] == "2") {
			$to[] = array('name' => $subscriber['name'], 'email' => $subscriber['email']);
		}
	}
	if (date('Y-m-d H:i:s', current_time('timestamp')) > date('Y-m-d H:i:s',strtotime($opt['last_send'] . $opt['send_interval']))) {
		es_sendmail($to , 'PT');
		$opt['last_send'] = current_time('mysql');
		update_option( 'es_options', $opt);
	}	
}

/**
 * Function for sending out e-mails from different templates
 *
 * @param array $to
 * @param string $template_slug
 */

function es_sendmail( $to, $template_slug) {
	global $wpdb;

	$sql = "SELECT * FROM {$wpdb->prefix}es_templates WHERE slug='$template_slug'";			
	$templates = $wpdb->get_results($sql, 'ARRAY_A');
	
	// If template is disabled, exit. 
	if (!$templates[0]['active']) { return; }

	if ($template_slug == 'PT') {
		$opt = get_option('es_options');
		$args = array(
			'post_type' => $opt['post_type'],
			'post_status' => array ('publish'),
			'date_query'     => array( 'after' => $opt['last_send'] ),
		);
		$query = new WP_Query( $args );
		$new_posts = $query->found_posts;

		if ( !$query->have_posts() ) { return; }
	
		// Extract content before and after the loop block
		$loop_start = strpos($templates[0]['template'],"#loopstart#");
		
		// If #loopstart# is missing, just get the last post
		if (!$loop_start) {
			$main_loop = $templates[0]['template'];
			$body = '';
			$post_loop = '';
		} else {
			$loop_end = strpos($templates[0]['template'],"#loopend#");
			$body = substr($templates[0]['template'], 0 , $loop_start);
			$main_loop = substr($templates[0]['template'], $loop_start+11, $loop_end-$loop_start-11);
			$post_loop = substr($templates[0]['template'], $loop_end+9);
		}

		// The loop, replaces shortcodes with actual content
		do {
			$query->the_post();
			$bodytags = array("#posttitle#" => get_the_title(),
							"#postexcerpt#" => get_the_excerpt(),
							"#postcontent#" => get_the_content(),
							"#postdate#" => get_the_date(),
							"#postlink#" => get_permalink(),
							"#postimage#" => get_the_post_thumbnail_url(NULL, 'thumbnail'),
							"#postedby#" => get_the_author_meta('display_name'));
			$body .= strtr($main_loop,$bodytags);
		} while ( $query->have_posts() && $loop_start);
		
		$bodytags = array("#sitename#" => get_option('blogname'),
						"#siteurl#" => site_url(),
						"#sitedescription#" => get_option('blogdescription'),
						"#newposts#" => $new_posts);
		$subjecttags = array("#sitename#" => get_option('blogname'),"#newposts#" => $new_posts);
						
		$body .= $post_loop;
		$meassage = strtr($body,$bodytags);
		$subject = strtr($templates[0]['subject'],$subjecttags);

		/* Restore original Post Data */
		wp_reset_postdata();
	} else {
		// If not 'Post templete' is called
		$bodytags = array("#sitename#" => get_option('blogname'),
						"#siteurl#" => site_url(),
						"#sitedescription#" => get_option('blogdescription'));

		$subjecttags = array("#sitename#" => get_option('blogname'));
	
		$meassage = strtr($templates[0]['template'],$bodytags);
		$subject = strtr($templates[0]['subject'],$subjecttags);
	}

	$headers = array('Content-Type: text/html; charset=UTF-8');

	// Loop thru all subscribers and send the e-mails
	foreach ($to as $subscriber) {
		$uuid = wp_generate_uuid4();
		$tags = array("#subscribername#" => $subscriber['name'],
					  "#subscriberemail#" => $subscriber['email'],
					  "#unsubscribelink#" => site_url() . "/wp-content/plugins/email_subscribers/unsubscribe.php?uuid=".$uuid,
					  "#verifylink#" => site_url() . "/wp-content/plugins/email_subscribers/verify.php?uuid=".$uuid);
		$body = strtr($meassage,$tags);
		$result = wp_mail( $subscriber['name'] . '<'.$subscriber['email'].'>', $subject, $body, $headers);

		// Update UUID for user
		$wpdb->update( $wpdb->prefix . 'es_subscribers',
						array('uuid' => $uuid),
						array( 'email' => $subscriber['email'] ),
						array( '%s') );
	}
}

/**
 * Setup the use of SMTP for wp_mail()
 *
 */

function send_smtp_email( $phpmailer ) {
	$opt = get_option('es_options');
	
	$phpmailer->isSMTP();
	$phpmailer->Timeout    = 15;
	$phpmailer->Host       = $opt['hostname'];
	$phpmailer->Port       = $opt['port'];
	$phpmailer->SMTPSecure = ($opt['protocol'] == 'none') ? '' : $opt['protocol'];
	$phpmailer->From       = $opt['email'];
	$phpmailer->FromName   = $opt['name'];
	$phpmailer->SMTPAuth   = $opt['authentication'];
	if ($opt['authentication']) {
		$phpmailer->Username   = $opt['login'];
		$phpmailer->Password   = base64_decode($opt['pwd']);
	}
}
?>