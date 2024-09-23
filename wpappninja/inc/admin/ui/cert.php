<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );


function importFromKeyToOauth() {

    if (!isset($_GET['migratePushTokens']) || get_wpappninja_option('apipush', '') == '') {
        return;
    }

    global $wpdb;
    
    $row = $wpdb->get_results($wpdb->prepare("SELECT registration_id FROM {$wpdb->prefix}wpappninja_ids WHERE `lang` != %s", "xxx"));
    $ids = array();
	foreach($row as $r) {
		$ids[] = $r->registration_id;
    }
    
    $mini_ios 	= array_chunk($ids, 1000);
    $oAuthTokenMigrate = wpmobile_getOauthToken();
    
    for($i=0;$i<count($mini_ios);$i++) {
        echo "Migrate " . count($mini_ios[$i]) . " tokens...<br/>";
        
        $fields = array(
            'to' => '/topics/main',
            'registration_tokens' => $mini_ios[$i]
        );
        $headers = array('Authorization' => 'Bearer ' . $oAuthTokenMigrate, 'access_token_auth' => 'true', 'Content-Type' => 'application/json');
        $result = wp_remote_post( 'https://iid.googleapis.com/iid/v1:batchAdd', array(
            'method' => 'POST',
			'timeout' => 10,
			'redirection' => 1,
			'headers' => $headers,
			'body' => json_encode($fields)
        ));
                
        usleep(30000);
    }
    
    echo "<b>Done.</b>";
    
    $option = get_option(WPAPPNINJA_SLUG);
	$option['apipush'] = "";
    update_option(WPAPPNINJA_SLUG, $option);
}

/**
 * Import the iOS pem certificate for notifications.
 *
 * @since 3.8
 */
function _wpappninja_display_cert_page() {
	?>
	<style type="text/css">
									input[type="text"], select {
    width: 350px;
    border: 2px solid #eee!important;
    box-shadow: 0 0 0 #fff!important;
    font-size: 20px;
    background: #fdfdfd;
    box-sizing: content-box;
    padding: 2px;
}textarea {
    padding: 2px 6px;
    line-height: 1.4;
    width: 360px!important;
    border: 2px solid #eee;
    box-shadow: 0 0 0 #fff;
    font-family: courier;
    font-size: 19px;
}input#submitme {
    font-size: 25px!important;
    box-sizing: initial;
    height: auto;
    line-height: initial!important;
    width: 320px!important;
    padding: 10px 150px!important;
    margin: 25px auto;
    display: block;
}		h2{color:#555;font-size:33px;}
#wpappninja_app_store_data{max-width:100%!important;}
					.wpappninja-builder {
						padding:15px;
						font-size: 18px;
						background:#fff;
						border-bottom:1px solid #eee;
					}
					.wpappninja-builder:hover {
						background: #ffe;
					}
					.wpappninja-builder-left {
						width: 300px;
						padding: 6px;
						float: left;
					}
					.wpappninja-builder-right {
						float:left;
					}

					.selectapptype label {
					    display: inline-block;
						background:#f9f9f9;
					    padding: 25px;
                        width:49%;box-sizing: border-box;
					}

					.selectapptype label:hover{
						background:#fff;
						box-shadow:0 0 4px #eee;
					}

					</style>
     
     <?php
     if (isset($_GET['migratePushTokens']) && get_wpappninja_option('apipush', '') != '') { ?>
     <div style="background:#333;color:white;font-family:courier;padding:20px;">
     <?php
     echo importFromKeyToOauth();
     ?>
     </div>
     <?php } ?>
     
     <?php
     if (get_wpappninja_option('apipush', '') != '' && get_option('wpmobile_firebase_config', '') != '') {
     
        echo '<a style="display: inline-block; background: rgb(240, 230, 96); padding: 8px; text-decoration: unset; text-transform: uppercase; margin: 0; border: 1px solid rgb(208, 208, 0);" href="?page=' . WPAPPNINJA_PUSH_SLUG . '&settings&migratePushTokens=true">Start the migration of the tokens to the new system</a>';
     
     } ?>
     
     
     
     
     
     
    <form method="post" enctype="multipart/form-data" action="" accept-charset="utf-8" >
        <?php wp_nonce_field('wpappninja_cert'); ?>

        <input type="hidden" name="wpappninjacertform" value="1" />

<h2 style="background: #f5f5f5;padding: 15px;cursor: pointer;"  onclick="jQuery('#pushgeneral').toggle();"><span class="dashicons dashicons-arrow-down-alt2"></span> <?php _e('General', 'wpappninja');?></h2>
<div class="wpappninja_div" id="pushgeneral" style="display:none">
<div class="wpappninja-builder">
	<div class="wpappninja-builder-left">
		<?php _e('Comma separated category', 'wpappninja');?>
	</div>
	<div class="wpappninja-builder-right">

			<input type="text" name="push_category" value="<?php echo get_wpappninja_option('push_category', '');?>" />

	</div>
	<div class="clear"></div>
</div>

<div class="wpappninja-builder">
	<div class="wpappninja-builder-left">
		<?php _e('Action on notification click', 'wpappninja');?>
	</div>
	<div class="wpappninja-builder-right">

			<select name="wpappninja_redirection_type"><option value="1"><?php _e('Open the linked page', 'wpappninja');?></option><option value="0" <?php if (get_wpappninja_option('redirection_type', '1') != '1'){echo 'selected';}?>><?php _e('Display the notification message', 'wpappninja');?></option></select>

	</div>
	<div class="clear"></div>
</div>

<div class="wpappninja-builder" style="display:none">
	<div class="wpappninja-builder-left">
		<?php _e('Show the box to send push on page editor', 'wpappninja');?>
	</div>
	<div class="wpappninja-builder-right">

			<select name="wpappninja_disable_autopush"><option value="1"><?php _e('No', 'wpappninja');?></option><option value="0" <?php if (!get_option('wpappninja_disable_autopush')){echo 'selected';}?>><?php _e('Yes', 'wpappninja');?></option></select>

	</div>
	<div class="clear"></div>
</div>

</div>








<h2 style="background: #f5f5f5;padding: 15px;cursor: pointer;"  onclick="jQuery('#pushautomatic').toggle();"><span class="dashicons dashicons-arrow-down-alt2"></span> <?php _e('Automatic push', 'wpappninja');?></h2>
<div class="wpappninja_div" id="pushautomatic" style="display:none">


<div class="wpappninja-builder">
	<div class="wpappninja-builder-left">
		<?php _e('When a post is published', 'wpappninja');?>
	</div>
	<div class="wpappninja-builder-right">

		<select name="wpmobile_auto_post"><option value="1"><?php _e('Yes', 'wpappninja');?></option><option value="0" <?php if (get_wpappninja_option('wpmobile_auto_post', '0') == '0'){echo 'selected';}?>><?php _e('No', 'wpappninja');?></option></select>

	</div>
	<div class="clear"></div>
</div>

<div class="wpappninja-builder">
	<div class="wpappninja-builder-left">
		<?php _e('When a post is published or edited or updated', 'wpappninja');?>
	</div>
	<div class="wpappninja-builder-right">

		<select name="wpmobile_auto_post_update"><option value="1"><?php _e('Yes', 'wpappninja');?></option><option value="0" <?php if (get_wpappninja_option('wpmobile_auto_post_update', '0') == '0'){echo 'selected';}?>><?php _e('No', 'wpappninja');?></option></select>

	</div>
	<div class="clear"></div>
</div>

<div class="wpappninja-builder">
	<div class="wpappninja-builder-left">
		<?php _e('When a WooCommerce order status change', 'wpappninja');?>
	</div>
	<div class="wpappninja-builder-right">

		<select name="wpmobile_auto_wc"><option value="1"><?php _e('Yes', 'wpappninja');?></option><option value="0" <?php if (get_wpappninja_option('wpmobile_auto_wc', '0') == '0'){echo 'selected';}?>><?php _e('No', 'wpappninja');?></option></select>

	</div>
	<div class="clear"></div>
</div>

    <div class="wpappninja-builder">
        <div class="wpappninja-builder-left">
			<?php _e('When a Better Messages notification is triggered', 'wpappninja');?>
        </div>
        <div class="wpappninja-builder-right">

            <select name="wpmobile_auto_bm"><option value="1"><?php _e('Yes', 'wpappninja');?></option><option value="0" <?php if (get_wpappninja_option('wpmobile_auto_bm', '0') == '0'){echo 'selected';}?>><?php _e('No', 'wpappninja');?></option></select>

        </div>
        <div class="clear"></div>
    </div>

<div class="wpappninja-builder">
	<div class="wpappninja-builder-left">
		<?php _e('When a BuddyPress notification is sent', 'wpappninja');?>
	</div>
	<div class="wpappninja-builder-right">

		<select name="wpmobile_auto_bp"><option value="1"><?php _e('Yes', 'wpappninja');?></option><option value="0" <?php if (get_wpappninja_option('wpmobile_auto_bp', '0') == '0'){echo 'selected';}?>><?php _e('No', 'wpappninja');?></option></select>

	</div>
	<div class="clear"></div>
</div>
    
    
    <div class="wpappninja-builder">
        <div class="wpappninja-builder-left">
            <?php _e('When a Peepso notification is sent', 'wpappninja');?>
        </div>
        <div class="wpappninja-builder-right">

            <select name="wpmobile_auto_peepso"><option value="1"><?php _e('Yes', 'wpappninja');?></option><option value="0" <?php if (get_wpappninja_option('wpmobile_auto_peepso', '0') == '0'){echo 'selected';}?>><?php _e('No', 'wpappninja');?></option></select>

        </div>
        <div class="clear"></div>
    </div>
    <div class="wpappninja-builder">
        <div class="wpappninja-builder-left">
            <?php _e('When a Gravity Form notification is sent', 'wpappninja');?>
        </div>
        <div class="wpappninja-builder-right">

            <select name="wpmobile_auto_gravity"><option value="1"><?php _e('Yes', 'wpappninja');?></option><option value="0" <?php if (get_wpappninja_option('wpmobile_auto_gravity', '0') == '0'){echo 'selected';}?>><?php _e('No', 'wpappninja');?></option></select>

        </div>
        <div class="clear"></div>
    </div>

<div class="wpappninja-builder">
	<div class="wpappninja-builder-left">
		<?php _e('When a mail is sent to an user', 'wpappninja');?>
	</div>
	<div class="wpappninja-builder-right">

		<select name="wpmobile_auto_mail"><option value="1"><?php _e('Yes', 'wpappninja');?></option><option value="0" <?php if (get_wpappninja_option('wpmobile_auto_mail', '0') == '0'){echo 'selected';}?>><?php _e('No', 'wpappninja');?></option></select>

	</div>
	<div class="clear"></div>
</div>

</div>








<h2 style="background: #f5f5f5;padding: 15px;cursor: pointer;" onclick="jQuery('#pushios').toggle();"><span class="dashicons dashicons-arrow-down-alt2"></span> <?php _e('iOS', 'wpappninja');?></h2>
<div class="wpappninja_div" id="pushios" <?php if (!isset($_GET['needConfiguration'])){?>style="display:none"<?php }?>>
<div class="wpappninja-builder">

	<?php if (get_option('wpappninja_packagenameInt', '') == '' && 0>1) { ?>
	<div class="wpappninja-builder-left">
		<?php _e('You need to buy and publish your app before the iOS configuration.', 'wpappninja'); ?>
		<input type="file" name="wpappninja_ios_cert[]" size="35" style="display:none" />
	</div>
	<?php }else{ ?>
	<div class="wpappninja-builder-left">
		<?php _e('Certificate', 'wpappninja');?>
	</div>
	<div class="wpappninja-builder-right">

			<?php
			if (get_option('wpappninja_pem_file', '') == '') {$data = '';}else{$data = @openssl_x509_parse(@file_get_contents(get_option('wpappninja_pem_file', '')));}

			if (is_array($data)) {
				$validTo = date('Y-m-d H:i:s', $data['validTo_time_t']);

				if ($data['validTo_time_t'] > current_time('timestamp')) {
					echo '<b style="color:darkgreen">' . __('Uploaded', 'wpappninja') . '</b><br/>' . __('Valid until', 'wpappninja') . ' <b>' . $validTo . '</b><br/><br/>';
				} else {

					$paxk = get_option('wpappninja_packagenameInt', '');

					echo '<b style="color:darkred">' . __('Expired', 'wpappninja') . ' <a href="https://wpmobile.app/" target="_blank">Request an update</a></b><br/><br/>';
				}
			} else {
					echo '<b style="color:darkred">' . __('Missing', 'wpappninja') . '</b><br/><br/>';
				}
			?>
			<input type="file" name="wpappninja_ios_cert[]" size="35" />

	</div>
	<?php } ?>
	<div class="clear"></div>
</div>


<div class="wpappninja-builder">
	<div class="wpappninja-builder-left">
		<?php _e('Content of the push', 'wpappninja');?>
	</div>
	<div class="wpappninja-builder-right">

			<select name="iosjusttitle"><option value="off"><?php _e('Title + Text', 'wpappninja');?></option><option value="on" <?php if (get_wpappninja_option('iosjusttitle') === "on"){echo 'selected';}?>><?php _e('Title', 'wpappninja');?></option></select>

	</div>
	<div class="clear"></div>
</div>


</div>


<h2 style="background: #f5f5f5;padding: 15px;cursor: pointer;"  onclick="jQuery('#pushandroid').toggle();"><span class="dashicons dashicons-arrow-down-alt2"></span> <?php _e('Android', 'wpappninja');?></h2>
<div class="wpappninja_div" id="pushandroid" <?php if (!isset($_GET['needConfiguration'])){?>style="display:none"<?php }?>>

		<?php
		$appdata = get_wpappninja_option('app');
		?>


<?php if (get_wpappninja_option('sdk2019') == '1') { ?>
<p class="wpappninja_help"><?php _e('Follow <a href="https://support.wpmobile.app/article/265-get-the-google-services-json-file-and-api-key?lang=en" target="_blank">this guide</a> to get the google-services.json file and API Key', 'wpappninja'); ?><br/><br/><?php _e('Your package name is', 'wpappninja');?>: <b><?php echo get_wpappninja_option('package', wpappninja_fake_package());?></b></p>

<?php } else { ?>
<p class="wpappninja_help"><?php _e('Follow <a href="https://support.wpmobile.app/article/248-how-to-get-an-api-key-for-android-notifications?lang=en" target="_blank">this guide</a> to get the Google API Key', 'wpappninja'); ?></p>
<?php } ?>


<?php if (get_wpappninja_option('sdk2019') == '1') { ?>
<div>
<?php } else { ?>
<div style="display:none">
<?php } ?>
<div class="wpappninja-builder">
	<div class="wpappninja-builder-left">

			<?php _e('google-services.json file', 'wpappninja');?>

	</div>
	<div class="wpappninja-builder-right">

		<?php
        $googleJsonFile = "";
        $projectIdLink = "";
        if (get_option('wpappninja_google_json', '') != '') {
	        $googleJsonFile     = @file_get_contents( get_option( 'wpappninja_google_json', '' ) );
	        $googleJsonFileJson = json_decode( $googleJsonFile, true );
        }
		if  (get_option('wpappninja_google_json', '') != '' && $googleJsonFile != "") {

            $projectIdLink = $googleJsonFileJson['project_info']['project_id'];
            if ($googleJsonFileJson['client'][0]['client_info']['android_client_info']['package_name'] == get_wpappninja_option('package', wpappninja_fake_package())) {
	            echo '<b style="color:darkgreen">' . __( 'Uploaded', 'wpappninja' ) . '</b>';
            } elseif ($googleJsonFileJson['client'][0]['client_info']['android_client_info']['package_name'] != get_wpappninja_option('package', wpappninja_fake_package())) {
	            echo '<b style="color:darkred">' . __( 'Package name didnt match', 'wpappninja' ) . '</b>';
            }

echo '<br/>Project: <b>'.$googleJsonFileJson['project_info']['project_id'].'</b><br/>Package name: <b>'.$googleJsonFileJson['client'][0]['client_info']['android_client_info']['package_name'].'</b><br/><a style="font-size:11px" href="https://console.firebase.google.com/project/'.$googleJsonFileJson['project_info']['project_id'].'/settings/general/android:'.$googleJsonFileJson['client'][0]['client_info']['android_client_info']['package_name'].'" target="_blank">get it on firebase.com</a><br/><br/>';
		} else {echo '<b style="color:darkred">' . __('Missing', 'wpappninja') . '</b><br/><br/>';}?>

				<input type="file" name="wpappninja_google_json[]" size="35" />

	</div>
	<div class="clear"></div>
</div>


<div class="wpappninja-builder">
	<div class="wpappninja-builder-left">

			<?php _e('Account service json file', 'wpappninja');?>

	</div>
	<div class="wpappninja-builder-right">

		<?php

        $firebaseFile = "";
        if (get_option('wpmobile_firebase_config', '') != '') {
	        $firebaseFile     = @file_get_contents( get_option( 'wpmobile_firebase_config', '' ) );
	        $firebaseFileJson = json_decode( $firebaseFile );
        }

        if  (get_option('wpmobile_firebase_config', '') != '' && $firebaseFile != "") {

	        if ($firebaseFileJson->project_id == $googleJsonFileJson['project_info']['project_id']) {
		        echo '<b style="color:darkgreen">' . __( 'Uploaded', 'wpappninja' ) . '</b>';
	        } elseif ($firebaseFileJson->project_id != $googleJsonFileJson['project_info']['project_id']) {
		        echo '<b style="color:darkred">' . __( 'Firebase project didnt match', 'wpappninja' ) . '</b>';
	        }

echo '<br/>Project: <b>'.$firebaseFileJson->project_id.'</b><br/><a style="font-size:11px" href="https://console.firebase.google.com/project/'.$firebaseFileJson->project_id.'/settings/serviceaccounts/adminsdk" target="_blank">get it on firebase.com</a><br/><br/>';
		} else {echo '<b style="color:darkred">' . __('Missing', 'wpappninja') . '</b><br/><br/>';
  
  
  if (get_option('wpappninja_google_json', '') != '' && $googleJsonFile != "") {
  echo "Generate a new <b>private key</b> from <a href='";
  if ($projectIdLink != "") { echo "https://console.firebase.google.com/project/".$projectIdLink."/settings/serviceaccounts/adminsdk";}
  else{echo "https://console.firebase.google.com/";}
  echo "' target='_blank'>firebase.com</a> and upload the file here.<br/><br/>";
}
        }?>

				<input type="file" name="wpmobile_firebase_config[]" size="35" />

	</div>
	<div class="clear"></div>
</div>


</div>


<div class="wpappninja-builder" style="display:none">
	<div class="wpappninja-builder-left">

			<?php _e('Server API Key', 'wpappninja');?>

	</div>
	<div class="wpappninja-builder-right">



				<input  type="text" name="apipush" value="<?php echo get_wpappninja_option('apipush');?>" />

	</div>
	<div class="clear"></div>
</div>


<?php if (get_wpappninja_option('sdk2019') != '1') { ?>
<div>
<?php } else { ?>
<div style="display:none">
<?php } ?>


<div class="wpappninja-builder">
	<div class="wpappninja-builder-left">

			<?php _e('Sender ID', 'wpappninja');?>

	</div>
	<div class="wpappninja-builder-right">




				<input placeholder="00000000000" type="text" name="project" value="<?php echo get_wpappninja_option('project');?>" />
	</div>
	<div class="clear"></div>
</div>



<div class="wpappninja-builder">
	<div class="wpappninja-builder-left">

			<?php _e('Notification icon', 'wpappninja');?>

	</div>
	<div class="wpappninja-builder-right">




		<?php // icon
		echo '<div class="label_iconic" id="label_iconic">';
		$files = array();//$files = glob(WPAPPNINJA_ICONS_PATH . '*.png', GLOB_BRACE);
		$app_data = get_wpappninja_option('app');
		$app_logo = isset($app_data['logo']) ? esc_url($app_data['logo']) : "";

		echo '<label style="background:#757575"><input style="display:none" type="radio" name="customiconnotif" value="icon_notif" ';if (get_wpappninja_option('customiconnotif', 'icon_notif') == "icon_notif"){echo 'checked';}echo ' /><img width="24" height="24" style="filter: brightness(0) invert(1);" src="'.$app_logo.'" /></label>';

		foreach($files as $file) {
			$file = preg_replace('#.*\/([a-z_]+)\.png$#', '$1', $file);
			echo '<label style="background:#757575"><input style="display:none" type="radio" name="customiconnotif" value="'.$file.'" ';if (get_wpappninja_option('customiconnotif', 'icon_notif') == $file){echo 'checked';}echo ' /><img width="24" height="24" style="filter: brightness(0) invert(1);" src="' . WPAPPNINJA_ASSETS_IMG_URL . 'icons/'.$file.'.png" /></label>';
		}
		echo '</div>';
				
		// edit icon link
		echo '<div style="float: left;margin-left: 100px;margin-top: -29px;"><a href="#" onclick="jQuery(\'#label_iconic label\').css(\'display\', \'inline-block\');return false">'.__('Edit').'</a></div>'; ?>

		<script type="text/javascript">
		function wpappninja_label() {
			jQuery(".label_iconic label").css('display','none');
			jQuery(".label_iconic label:has(input[type=\"radio\"]:checked)").css('display','block');
		}
		wpappninja_label();
		jQuery('.label_iconic label').click(wpappninja_label);
		</script>

	</div>
	<div class="clear"></div>
</div>
</div>
</div>




<h2 style="background: #f5f5f5;padding: 15px;cursor: pointer;display:none"  onclick="jQuery('#pushwelcome').toggle();"><span class="dashicons dashicons-arrow-down-alt2"></span> <?php _e('Welcome notification', 'wpappninja');?></h2>
<div class="wpappninja_div" id="pushwelcome" style="display:none">
<div class="wpappninja-builder">
	<div class="wpappninja-builder-left">
		<?php _e('Send a welcome notification', 'wpappninja');?>
	</div>
	<div class="wpappninja-builder-right">

			<select name="send_welcome_push"><option value="1"><?php _e('Yes', 'wpappninja');?></option><option value="0" <?php if (get_wpappninja_option('send_welcome_push') === "0"){echo 'selected';}?>><?php _e('No', 'wpappninja');?></option></select>

	</div>
	<div class="clear"></div>
</div>

<div class="wpappninja-builder">
	<div class="wpappninja-builder-left">
		<?php _e('Title', 'wpappninja');?>
	</div>
	<div class="wpappninja-builder-right">

			<input type="text" name="welcome_title" value="<?php echo str_replace('\\', '', stripslashes(get_wpappninja_option('welcome_titre_speed')));?>" />


	</div>
	<div class="clear"></div>
</div>

<div class="wpappninja-builder">
	<div class="wpappninja-builder-left">
		<?php _e('Sub title', 'wpappninja');?>
	</div>
	<div class="wpappninja-builder-right">

		<input type="text" name="welcome_subtitle" value="<?php echo str_replace('\\', '', stripslashes(get_wpappninja_option('welcome_speed')));?>" />


	</div>
	<div class="clear"></div>
</div>

<div class="wpappninja-builder">
	<div class="wpappninja-builder-left">
		<?php _e('Content', 'wpappninja');?>
	</div>
	<div class="wpappninja-builder-right">



								<textarea style="width:100%;height:350px;" name="welcome_content"><?php echo str_replace('\\', '', stripslashes(get_wpappninja_option('bienvenue_speed')));?></textarea>

	</div>
	<div class="clear"></div>
</div>

</div>



					<br/><br/>
					<input type="submit" id="submitme" class="button button-primary button-large" />
<br/>    </form>

<?php }
