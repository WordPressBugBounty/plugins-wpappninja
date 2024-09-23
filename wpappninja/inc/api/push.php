<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

// FIREBASE FUNCTIONS
function wpmobile_base64UrlEncode($text)
{
    return str_replace(
        ['+', '/', '='],
        ['-', '_', ''],
        base64_encode($text)
    );
}
function wpmobile_getAuthConfig() {

    if (get_option('wpmobile_firebase_config', '') == '') {
        return;
    }
    
    $authConfigString = @file_get_contents(get_option('wpmobile_firebase_config', ''));
    $authConfig = json_decode($authConfigString);
    return $authConfig->project_id;
}
function wpmobile_getOauthToken() {

    if (get_option('wpmobile_firebase_config', '') == '') {
        return;
    }
    
    $authConfigString = @file_get_contents(get_option('wpmobile_firebase_config', ''));
    $authConfig = json_decode($authConfigString);
    $secret = openssl_get_privatekey($authConfig->private_key);
    $header = json_encode([
        'typ' => 'JWT',
        'alg' => 'RS256'
    ]);

    $time = time();
    $payload = json_encode([
        "iss" => $authConfig->client_email,
        "scope" => "https://www.googleapis.com/auth/firebase.messaging",
        "aud" => "https://oauth2.googleapis.com/token",
        "exp" => $time + 180,
        "iat" => $time
    ]);

    $base64UrlHeader = wpmobile_base64UrlEncode($header);
    $base64UrlPayload = wpmobile_base64UrlEncode($payload);
    $result = openssl_sign($base64UrlHeader . "." . $base64UrlPayload, $signature, $secret, OPENSSL_ALGO_SHA256);
    $base64UrlSignature = wpmobile_base64UrlEncode($signature);
    $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

    $options = array('http' => array(
        'method'  => 'POST',
        'content' => 'grant_type=urn:ietf:params:oauth:grant-type:jwt-bearer&assertion='.$jwt,
        'header'  =>
            "Content-Type: application/x-www-form-urlencoded"
    ));
    $context  = stream_context_create($options);
    $responseText = file_get_contents("https://oauth2.googleapis.com/token", false, $context);

    $responseOAuth = json_decode($responseText, true);
    return $responseOAuth['access_token'];
}

// PROCESS PUSH
function wpappninja_send_push($ids, $title, $content, $image, $postID, $permalink, $custom_category, $pushID) {

	if ($postID == "0") {
		$postID = get_bloginfo('url') . "/?wpappninja_read_enhanced=-welcome";
	}

	if (get_wpappninja_option('speed') == '1') {
		$postID = get_bloginfo('url') . "/wpmobileapp-shortcode/?wpappninja_read_push=" . $pushID;
		if (get_wpappninja_option('redirection_type', '1') == '1' && strpos($permalink, "http") !== false) {
			$postID = wpappninja_cache_friendly($permalink);
		}
		$permalink = $postID;
	}

	if ($postID == "-1") {
		$postID = get_bloginfo('url') . "/?wpappninja_read_enhanced=-" . $pushID;
	}
	$query_postID = parse_url($postID, PHP_URL_QUERY);
	if ($query_postID) {
		$postID .= '&wpmobile_from_push=' . uniqid();
	} else {
    	$postID .= '?wpmobile_from_push=' . uniqid();
    }
	
	global $wpdb;
	$_wpappninja_ids = $ids;

	$title = stripslashes($title);
	$content = stripslashes($content);
	
	$silent = array();
	$categories = get_the_category($postID);
	$catID = '';

	$response	= '';
	
	$certFile = get_option('wpappninja_pem_file', '');

	$iostitle = wpappninja_nice_cut($title, 30);
	$ioscontent = wpappninja_nice_cut($content, 128);

	if (get_wpappninja_option('iosjusttitle', 'off') == 'on') {
		$iostitle = wpappninja_nice_cut(wpappninja_get_appname(true), 30);
		$ioscontent = wpappninja_nice_cut($title, 128);
	}

    // iOS notifications
	if ($certFile != '') {

		$idsIOS = [];
  
  		foreach ($ids as $key => $ios_id) {
			if (substr($ios_id, 0, 5) == "_IOS_") { // ios style
				unset($ids[$key]);
                $idsIOS[] = str_replace('_IOS_', '', $ios_id);
			}
		}
  
  		$mini_ios 	= array_chunk($idsIOS, 10000);
		for($i=0;$i<count($mini_ios);$i++) {

            $bypass = wp_remote_post( "https://push.wpmobile.app/", array(
                'method' => 'POST',
                'timeout' => 10,
                'redirection' => 1,
                'body' => array( 'url'=>get_bloginfo('url') . '/', 'postID'=> strval($postID), 'title'=>$iostitle, 'content'=>$ioscontent, 'certFile' => file_get_contents($certFile),  'ids' => json_encode($mini_ios[$i]) ),
                )
            );
            usleep(30000);
        }
	} else {
    	foreach ($ids as $key => $ios_id) {
			if (substr($ios_id, 0, 5) == "_IOS_") {
				unset($ids[$key]);
			}
		}
	}
	
	// Android notifications
	if(count($ids) > 0 && get_option('wpmobile_firebase_config', '') != '' && get_wpappninja_option('sdk2019') == '1') {

		$intro = wpappninja_nice_cut($content, 99);
		$msg = wpappninja_nice_cut($content, 254);
		
		$url    = 'https://fcm.googleapis.com/v1/projects/'.wpmobile_getAuthConfig().'/messages:send';
  
        if (get_transient( "wpmobileAndroidTopic") == true) {
        
            $message = array(
                "title" => $title,
				"body" => $msg,
            );

            $fields = array(
                'message' => array(
                        'android' => array('priority'=>'high'),
                        'topic'=>'main',
                        'data'=>array(
                            "title" => $title,
                            "body" => $msg,
                            "link" => $postID,
                        )
                    ),
                );

             $headers = array('Authorization' => 'Bearer ' . wpmobile_getOauthToken(), 'Content-Type' => 'application/json');
             $result = wp_remote_post( $url, array(
					'method' => 'POST',
					'timeout' => 10,
					'redirection' => 1,
					'headers' => $headers,
					'body' => json_encode($fields)
                ));
        
        } else {
            $oAuthTokenPush = wpmobile_getOauthToken();
            foreach($ids as $id) {

                $message = array(
							"title" => $title,
							"body" => $msg,
						);

                $fields = array(
                    'message' => array(
                        'android' => array('priority'=>'high'),
                        'token' => $id,
                        'data'=>array(
                            "title" => $title,
                            "body" => $msg,
                            "link" => $postID,
                        )
                    ),
                );

                $headers = array('Authorization' => 'Bearer ' . $oAuthTokenPush, 'Content-Type' => 'application/json');
                $result = wp_remote_post( $url, array(
					'method' => 'POST',
					'timeout' => 10,
					'redirection' => 1,
					'headers' => $headers,
					'body' => json_encode($fields)
                ));
                
                usleep(3000);
            }
        }
	}
	
	return $response;
}

// SUBSCRIBE TO PUSH
function wpappninja_push_register() {

	global $wpdb;

	if (isset($_GET['wpmobile_sdk2019_id']) && isset($_GET['wpmobile_sdk2019_token'])) {

		$_POST['u'] = $_GET['wpmobile_sdk2019_id'];
		$_POST['regId'] = $_GET['wpmobile_sdk2019_token'];

		$user_id = $_POST['u'];
		$user_bdd_id = $wpdb->get_row($wpdb->prepare("SELECT `id` FROM {$wpdb->prefix}wpappninja_push_perso WHERE `user_id` = %s", $user_id));

		if (!isset($user_bdd_id->id)) {
			$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}wpappninja_push_perso (`user_id`) VALUES (%s)", $user_id));
			$lastid = $wpdb->insert_id;
		} else {
			$lastid = $user_bdd_id->id;
		}

        setcookie( "HTTP_X_WPAPPNINJA_ID", $lastid, time() + 8640000, COOKIEPATH, COOKIE_DOMAIN );
	}

	if (!isset($_POST['regId']) || !isset($_POST['u'])) {
		return;
	}
	
	$lang = wpappninja_get_lang();

	$id = sanitize_text_field($_POST['regId']);
	$device = sanitize_text_field($_POST['u']);

	if (substr($id, 0, 5) == "_IOS_" && isset($_SERVER['HTTP_X_WPAPPNINJA_ID'])) {
		$device = $_SERVER['HTTP_X_WPAPPNINJA_ID'];
	}

	$device_sha = sha1($device);
	$install = $wpdb->get_results($wpdb->prepare("SELECT `device_id` FROM {$wpdb->prefix}wpappninja_installs WHERE `device_id` = %s", $device_sha));

	if (count($install) == 0) {
		wpappninja_stats_log('install', 1);
			
		$device_type = 0; // android
		if (substr($id, 0, 5) == "_IOS_") {
			$device_type = 1;
		}

		$wpdb->query($wpdb->prepare("INSERT IGNORE INTO {$wpdb->prefix}wpappninja_installs (`device_id`, `device_type`) VALUES (%s, %d)", $device_sha, $device_type));
	}
	
	if ($id != '' AND $device != '') {
		$registered = $wpdb->get_results($wpdb->prepare("SELECT `device_id` FROM {$wpdb->prefix}wpappninja_ids WHERE `device_id` = %s", $device));

		if (isset($registered[0]) && $registered[0]->device_id != "") {
			$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}wpappninja_ids SET `registration_id` = %s, `lang` = %s WHERE `device_id` = %s", $id, $lang, $device));
		} else {
			$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}wpappninja_ids (`registration_id`, `device_id`, `lang`) VALUES (%s, %s, %s)", $id, $device, $lang));
		}
	}
 
    if (substr($id, 0, 5) != "_IOS_" && get_option('wpmobile_firebase_config', '') != '') {
        $fields = array(
            'to' => '/topics/main',
            'registration_tokens' => array($id)
        );
        $headers = array('Authorization' => 'Bearer ' . wpmobile_getOauthToken(), 'access_token_auth' => 'true', 'Content-Type' => 'application/json');
        $result = wp_remote_post( 'https://iid.googleapis.com/iid/v1:batchAdd', array(
            'method' => 'POST',
			'timeout' => 10,
			'redirection' => 1,
			'headers' => $headers,
			'body' => json_encode($fields)
        ));
   }
}

// SEND PUSH
function wpmobileapp_push($title, $message, $image, $link, $lang_2letters = 'all', $send_timestamp = '', $user_email = '') {

	global $wpdb;

	$title = wp_strip_all_tags($title);
	$title = strip_shortcodes($title);

	$lang_array = wpappninja_available_lang();

	$post_id = $link;
	if (!preg_match('#^http#', $link)) {
		$post_id = "0";
	}

	$content = wp_strip_all_tags($message);
	$content = strip_shortcodes($content);
	$content = preg_replace('/[ \t]+/', ' ', preg_replace('/\s*$^\s*/m', "\n", $content));
	$content = trim(preg_replace('/\s+/', ' ', $content));

	if (!preg_match('#^http#', $image)) {
		$image = ' ';
	}

	$timestamp = $send_timestamp;
	if ($timestamp == "") {
		$timestamp = current_time('timestamp');
	}

	$category = $user_email;
	$lang = $lang_2letters;
	$post_id = apply_filters('wpmobile_push_url', $post_id);
	$title = apply_filters('wpmobile_push_title', $title);
	$content = apply_filters('wpmobile_push_text', $content);
	$image = apply_filters('wpmobile_push_image', $image);

	$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}wpappninja_push (`id_post`, `titre`, `message`, `image`, `send_date`, `lang`, `category`) VALUES (%s, %s, %s, %s, %s, %s, %s)", $post_id, $title, $content, $image, $timestamp, $lang, $category));
}

// NEW MAIL
add_filter( 'wp_mail', 'wpmobileapp_send_push_mail', 1 );
function wpmobile_get_first_url_with_bloginfo_url($content) {
	$bloginfo_url = get_bloginfo('url');
	$pattern = '/(' . preg_quote($bloginfo_url, '/') . '[^\s"\']+)/i';
	if (preg_match($pattern, $content, $matches)) {
		return $matches[0];
	}
	return "";
}
function wpmobileapp_send_push_mail( $args ) {

	if (get_wpappninja_option('wpmobile_auto_mail') == '1') {
		$emailsto = $args['to'];

		$all_recipients = array();

		if (isset($args['headers']) && is_array($args['headers'])) {
			foreach ($args['headers'] as $header) {
				if (strpos($header, 'Bcc:') !== false) {
					$bcc_emails = explode(',', str_replace('Bcc:', '', $header));
					foreach ($bcc_emails as $bcc_email) {
						$bcc_email = trim($bcc_email);
						if (is_email($bcc_email)) {
							$all_recipients[] = $bcc_email;
						}
					}
				}
				if (strpos($header, 'Cc:') !== false) {
					$bcc_emails = explode(',', str_replace('Cc:', '', $header));
					foreach ($bcc_emails as $bcc_email) {
						$bcc_email = trim($bcc_email);
						if (is_email($bcc_email)) {
							$all_recipients[] = $bcc_email;
						}
					}
				}
			}
		}

		if (!is_array($emailsto)) {
			$emailsto = explode(',', $emailsto);
		}
        
		foreach ($emailsto as $emailto) {
			if (is_email($emailto)) {
				$all_recipients[] = $emailto;
			}
		}
        
		if ($args['subject'] != "" && $args['message'] != "") {
			$permalink = wpmobile_get_first_url_with_bloginfo_url(wp_strip_all_tags($args['message']));
    		foreach ($all_recipients as $recipient) {
				wpmobileapp_push($args['subject'], wp_strip_all_tags($args['message']), "", $permalink, 'all', '', $recipient);
			}
		}
	}
	
	return $args;
}

// NEW POST
add_action( 'new_to_publish', 'wpmobileapp_send_push_post', 10, 1 );
add_action( 'draft_to_publish', 'wpmobileapp_send_push_post', 10, 1 );
add_action( 'auto-draft_to_publish', 'wpmobileapp_send_push_post', 10, 1 );
add_action( 'private_to_publish', 'wpmobileapp_send_push_post', 10, 1 );
add_action( 'trash_to_publish', 'wpmobileapp_send_push_post', 10, 1 );
add_action( 'pending_to_publish', 'wpmobileapp_send_push_post', 10, 1 );
add_action( 'future_to_publish', 'wpmobileapp_send_push_post', 10, 1 );
function wpmobileapp_send_push_post($post) {

	if (get_wpappninja_option('wpmobile_auto_post') == '1' && !get_transient("wpmobile_push_slow_down")) {

		set_transient( 'wpmobile_push_slow_down', true, 30 );

		$posttype = get_post_type($post);

		if (in_array($posttype, array('post'))) {

			$ID = $post->ID;
		    $title = $post->post_title;
	    	$permalink = get_permalink( $ID );
	    	$image = wpappninja_get_image($ID);
	    	$content = get_the_excerpt($ID);

			if ($title != "" && $permalink != "") {

				$already_sent = get_option('wpmobile_auto_push_sent', array());

				if (!in_array($ID, $already_sent)) {
					$already_sent[] = $ID;
					update_option('wpmobile_auto_push_sent', $already_sent);
					wpmobileapp_push($title, $content, $image, $permalink, 'all', '', '');
				}
			}
		}
	}
}

// NEW POST
add_action( 'publish_post', 'wpmobileapp_send_push_post_update', PHP_INT_MAX, 2 );
function wpmobileapp_send_push_post_update($ID, $post) {

	if (get_wpappninja_option('wpmobile_auto_post_update') == '1' && !get_transient("wpmobile_push_slow_down")) {

		set_transient( 'wpmobile_push_slow_down', true, 30 );

	    $title = $post->post_title;
	    $permalink = get_permalink( $ID );
	    $image = wpappninja_get_image($ID);
	    $content = get_the_excerpt($ID);

		$posttype = get_post_type($post);

		if (in_array($posttype, array('post'))) {

			if ($title != "" && $permalink != "") {

				$already_sent = get_option('wpmobile_auto_push_sent', array());
                $already_sent[] = $ID;
                update_option('wpmobile_auto_push_sent', $already_sent);

                wpmobileapp_push($title, $content, $image, $permalink, 'all', '', '');
			}
		}
	}
}

// WOOCOMMERCE ORDER UPDATE
add_action('woocommerce_order_status_changed', 'wpmobileapp_send_push_wc', 10, 3);
function wpmobileapp_send_push_wc($order_id,$old_status,$new_status) {

    $wc_translation = array(
    	'pending'    => _x( 'Pending payment', 'Order status', 'wpappninja' ),
    	'processing' => _x( 'Processing', 'Order status', 'wpappninja' ),
    	'on-hold'    => _x( 'On hold', 'Order status', 'wpappninja' ),
    	'completed'  => _x( 'Completed', 'Order status', 'wpappninja' ),
    	'cancelled'  => _x( 'Cancelled', 'Order status', 'wpappninja' ),
    	'refunded'   => _x( 'Refunded', 'Order status', 'wpappninja' ),
    	'failed'     => _x( 'Failed', 'Order status', 'wpappninja' ),
    );

    if ($wc_translation[$old_status] != "") {
    	$old_status = $wc_translation[$old_status];
    }

    if ($wc_translation[$new_status] != "") {
    	$new_status = $wc_translation[$new_status];
    }

	if (get_wpappninja_option('wpmobile_auto_wc') == '1') {

		$title = sprintf( __( 'Order status changed from %s to %s', 'wpappninja' ), $old_status, $new_status );

		$order = new WC_Order( $order_id );
		$user_email = $order->get_billing_email();

		if ($user_email != "" && $title != "") {
			$message = '#' . $order_id . ' ' . __( 'Order updates', 'wpappninja' );
			$image = ' ';
			$link = $order->get_view_order_url();

			wpmobileapp_push($title, $message, $image, $link, 'all', '', $user_email);
		}
	}
}

// WOOCOMMERCE NOTE
add_action('woocommerce_new_customer_note', 'wpmobileapp_woo_note', 10, 1 );
function wpmobileapp_woo_note($array) {

    if (get_wpappninja_option('wpmobile_auto_wc') == '1') {

        $order_id = $array['order_id'];
        $title = $array['customer_note'];

        $order = new WC_Order( $order_id );
        $user_email = $order->get_billing_email();

        if ($user_email != "" && $title != "") {
            $message = '#' . $order_id . ' ' . __( 'Order updates', 'woocommerce' );
            $image = ' ';
            $link = $order->get_view_order_url();

            wpmobileapp_push($title, $message, $image, $link, $lang_2letters = 'all', $send_timestamp = '', $user_email);
        }
    }
}

// BETTERMESSAGES
add_action('better_messages_message_sent', 'wpmobileapp_bettermessages', 10, 1 );
function wpmobileapp_bettermessages( $message ){

	if (get_wpappninja_option('wpmobile_auto_bm') == '1') {
		$thread_id = $message->thread_id;
		$thread    = Better_Messages()->functions->get_thread( $thread_id );

		$send_push = isset( $message->send_push ) ? $message->send_push : false;

		if ( ! $send_push ) {
			return;
		}

		$recipients = array_keys( $message->recipients );
		$pushsent   = array();

		$online_users = [];
		if( Better_Messages()->websocket ){
			$online_users = Better_Messages()->websocket->get_online_users();
		}

		foreach ( $recipients as $user_id ) {

			if ( in_array( $user_id, $pushsent ) ) {
				continue;
			}

			if( in_array( $user_id, $online_users ) ) {
				continue;
			}

			$pushsent[] = $user_id;

			$muted_threads = Better_Messages()->functions->get_user_muted_threads( $user_id );
			if ( isset( $muted_threads[ $thread_id ] ) ) {
				continue;
			}

			if ( $message->sender_id == $user_id ) {
				continue;
			}

			$url           = Better_Messages()->functions->get_user_thread_url( $thread_id, $user_id );
			$subject       = sprintf( __( 'New message from %s', 'bp-better-messages' ), Better_Messages()->functions->get_name( $message->sender_id ) );
			$content       = sprintf( __( 'You have new message from %s', 'bp-better-messages' ), Better_Messages()->functions->get_name( $message->sender_id ) );
			$sender_avatar = htmlspecialchars_decode( Better_Messages_Functions()->get_avatar( $message->sender_id, 40, [ 'html' => false ] ) );
			$user_info     = get_userdata( $user_id );
			$user_email    = $user_info->user_email;

			if ($subject != "" AND $content != "" AND $user_email != "") {
				wpmobileapp_push( $subject, $content, $sender_avatar, $url, 'all', '', $user_email );
			}
		}
	}
}

// BUDDYPRESS
add_action('bp_notification_after_save', 'wpmobileapp_send_push_bp');
function wpmobileapp_send_push_bp( BP_Notifications_Notification $n ) {

	if (get_wpappninja_option('wpmobile_auto_bp') == '1') {

		$user_id = $n->user_id;
		$user_info = get_userdata($user_id);
		$user_email = $user_info->user_email;

		$bp           = buddypress();
		$notification = $n;

		if ( isset( $bp->{ $notification->component_name }->notification_callback ) && is_callable( $bp->{ $notification->component_name }->notification_callback ) ) {
			
			$content = call_user_func( $bp->{ $notification->component_name }->notification_callback, $notification->component_action, $notification->item_id, $notification->secondary_item_id, 1, 'array', $notification->id );
		}

		$title = $content['text'];

		if ($user_email != "" && $title != "") {
			$message = "";

			if (function_exists("bb_notification_avatar_url")) {
				$image = bb_notification_avatar_url( $notification );
			} else {
				$image = " ";
			}

			$link = $content['link'];

			wpmobileapp_push($title, $message, $image, $link, 'all', '', $user_email);
		}
	}
}

// GFORM
add_action('gform_pre_send_email', 'wpmobileapp_send_push_gravity', 10, 4);
function wpmobileapp_send_push_gravity( $email, $message_format, $notification, $entry ) {

    if (get_wpappninja_option('wpmobile_auto_gravity') == '1') {

        $user_email = $email['to'];
        $title = $email['subject'];

        if ($user_email != "" && $title != "") {
            $message = $email['message'];
            $image = " ";
            $link = "";

            wpmobileapp_push($title, $message, $image, $link, 'all', '', $user_email);
        }
    }
    
    return $email;
}

// PEEPSO
add_action('peepso_notifications_data_before_add', 'wpmobileapp_send_push_peepso');
function wpmobileapp_send_push_peepso( $array ) {

    if (get_wpappninja_option('wpmobile_auto_peepso') == '1') {
        $user_id = $array['not_user_id'];
        $user_info = get_userdata($user_id);
        $user_email = $user_info->user_email;
        
        $from_id = $array['not_from_user_id'];

	    $PeepSoUser = PeepSoUser::get_instance($from_id);
	    $from_login = $PeepSoUser->get_fullname();
        
        $title = $from_login . ' ' . $array['not_message'];
        
        if(method_exists('PeepSoNotifications','parse')) {
            $title = $from_login . ' ' . PeepSoNotifications::parse($array);
        }

        if ($user_email != "" && $title != "") {
            $message = "";
            $image = " ";
            $link = get_bloginfo('url') . "/notifications/";

            wpmobileapp_push($title, $message, $image, $link, 'all', '', $user_email);
        }
    }
    
    return $array;
}
