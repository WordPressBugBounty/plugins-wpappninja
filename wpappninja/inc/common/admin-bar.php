<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/**
 * Add WPMobile.App menu in the admin bar
 *
 * @since 1.0
 */
add_action( 'admin_bar_menu', '_wpappninja_admin_bar', PHP_INT_MAX );
function _wpappninja_admin_bar( $wp_admin_bar ) {
	
	if (!current_user_can( wpappninja_get_right() ) && !current_user_can( wpappninja_get_right("push") )) {
		return;
	}
	
	$count = wpappninja_check_icon();

	if (!get_option('wpappninja_app_published') || 1>0) {


		if (current_user_can( wpappninja_get_right() )) {
			// Parent
			$wp_admin_bar->add_menu( array(
				'id'    => WPAPPNINJA_SLUG,
				'title' => '<span class="ab-icon"></span> ' . WPAPPNINJA_NAME . $count,
				'href'  => admin_url('admin.php?page=' . WPAPPNINJA_HOME_SLUG),
			) );
		}

		if (current_user_can( wpappninja_get_right("push") )) {

			$wp_admin_bar->add_menu(array(
				'id'     => WPAPPNINJA_PUSH_SLUG,
				'title'  => '<span class="ab-icon"></span> ' . __( 'Push notification', 'wpappninja' ),
				'href'   => admin_url('admin.php?page=' . WPAPPNINJA_PUSH_SLUG),
			) );
		}

	} else {

		// Parent
		$wp_admin_bar->add_menu( array(
			'id'    => WPAPPNINJA_SLUG,
			'title' => '<span class="ab-icon"></span> ' . WPAPPNINJA_NAME . $count,
			'href'   => admin_url('admin.php?page=' . WPAPPNINJA_STATS_SLUG),
		) );

		$wp_admin_bar->add_menu(array(
			'parent' => WPAPPNINJA_SLUG,
			'id'     => WPAPPNINJA_PUBLISH_SLUG,
			'title'  => __( 'My app', 'wpappninja' ),
			'href'   => admin_url('admin.php?page=' . WPAPPNINJA_PUBLISH_SLUG),
		) );

		$wp_admin_bar->add_menu(array(
			'parent' => WPAPPNINJA_SLUG,
			'id'     => WPAPPNINJA_SETTINGS_SLUG . 'menu',
			'title'  => __( 'Content', 'wpappninja' ),
			'href'   => admin_url('admin.php?page=' . WPAPPNINJA_SLUG . '&onlymenu=true'),
		) );
	
		// Configurator
		$wp_admin_bar->add_menu(array(
			'parent' => WPAPPNINJA_SLUG,
			'id'     => WPAPPNINJA_AUTO_SLUG,
			'title'  => __( 'Design', 'wpappninja' ),
			'href'   => admin_url('admin.php?page=' . WPAPPNINJA_AUTO_SLUG),
		) );

		// Stats
		$wp_admin_bar->add_menu(array(
			'parent' => WPAPPNINJA_SLUG,
			'id'     => WPAPPNINJA_STATS_SLUG,
			'title'  => __( 'Statistics', 'wpappninja' ),
			'href'   => admin_url('admin.php?page=' . WPAPPNINJA_STATS_SLUG),
		) );

		// Push
		$postID = '';
		if (is_single()){$postID = get_the_ID();}
		$wp_admin_bar->add_menu(array(
			'parent' => WPAPPNINJA_SLUG,
			'id'     => WPAPPNINJA_PUSH_SLUG,
			'title'  => __( 'Notifications', 'wpappninja' ),
			'href'   => admin_url('admin.php?page=' . WPAPPNINJA_PUSH_SLUG . '&postID=' . $postID),
		) );

		// QRCode
		$wp_admin_bar->add_menu(array(
			'parent' => WPAPPNINJA_SLUG,
			'id'     => WPAPPNINJA_QRCODE_SLUG,
			'title'  => __( 'QR Code', 'wpappninja' ),
			'href'   => admin_url('admin.php?page=' . WPAPPNINJA_QRCODE_SLUG),
		) );

		// Adserver
		$wp_admin_bar->add_menu(array(
			'parent' => WPAPPNINJA_SLUG,
			'id'     => WPAPPNINJA_ADSERVER_SLUG,
			'title'  => __( 'Adserver', 'wpappninja' ),
			'href'   => admin_url('admin.php?page=' . WPAPPNINJA_ADSERVER_SLUG),
		) );



		// Promote
		/*$wp_admin_bar->add_menu(array(
			'parent' => WPAPPNINJA_SLUG,
			'id'     => WPAPPNINJA_PROMOTE_SLUG,
			'title'  => __( 'Promote', 'wpappninja' ),
			'href'   => admin_url('admin.php?page=' . WPAPPNINJA_PROMOTE_SLUG),
		) );*/

	}
	// Publish
	/*$wp_admin_bar->add_menu(array(
		'parent' => WPAPPNINJA_SLUG,
		'id'     => WPAPPNINJA_PUBLISH_SLUG,
		'title'  => __( 'Publication', 'wpappninja' ),
		'href'   => admin_url('admin.php?page=' . WPAPPNINJA_PUBLISH_SLUG),
	) );	*/
	
	if (1<0) {
	// Settings
	$wp_admin_bar->add_menu(array(
		'parent' => WPAPPNINJA_SLUG,
		'id'     => WPAPPNINJA_SETTINGS_SLUG,
		'title'  => __( 'Settings', 'wpappninja' ),
		'href'   => admin_url('admin.php?page=' . WPAPPNINJA_SLUG),
	) );
	}
}

/**
 * Add an icon to the admin menu bar
 *
 * @since 1.0
 */
add_action( 'admin_print_styles', '_wpappninja_admin_bar_css', 100 );
add_action( 'wp_print_styles', '_wpappninja_admin_bar_css', 100 );
function _wpappninja_admin_bar_css() {

	if (current_user_can( wpappninja_get_right() )) {
		wp_register_style(
			'wpappninja-admin-bar',
			WPAPPNINJA_ASSETS_CSS_URL . 'admin-bar.min.css',
			array(),
			WPAPPNINJA_VERSION
		);

		wp_enqueue_style( 'wpappninja-admin-bar' );
	}
}

/** Ask for a review **/
add_action('admin_notices', function () {

    return;

	if (!current_user_can('manage_options')) {return;}

	$user_id = get_current_user_id();
	$meta_key = 'wpmobileapp_rateme_dismissed003';

	if (get_user_meta($user_id, $meta_key, true)) {
		return;
	}

	global $wpdb;
	$sub = $wpdb->get_results("SELECT COUNT(id) as sub FROM {$wpdb->prefix}wpappninja_ids");
	if (@round($sub[0]->sub) > 1000) {
		?>
		<div class="notice notice-success is-dismissible" id="wpmobileapp-rateme-notice">

			<?php
			if (get_user_locale() === 'fr_FR') { ?>
				<p><big>🎉 Félicitations, votre application a dépassé les <b><?php echo @round(floor($sub[0]->sub / 100) * 100);?> téléchargements</b> ! 🚀</big><br/>Ce serait génial si vous pouviez <a class="notice-dismiss-link" href="https://wordpress.org/support/plugin/wpappninja/reviews/" target="_blank">laisser un avis</a> ! ⭐️⭐️⭐️⭐️⭐️ 4.7</p>
			<?php } else { ?>
				<p><big>🎉 Congratulations, your application has exceeded <b><?php echo @round(floor($sub[0]->sub / 100) * 100);?> downloads</b>! 🚀</big><br/>It will be awesome if you can <a class="notice-dismiss-link" href="https://wordpress.org/support/plugin/wpappninja/reviews/" target="_blank">leave a review</a>! ⭐️⭐️⭐️⭐️⭐️ 4.7</p>
			<?php } ?>
		</div>
		<script>
            jQuery(document).on('click', '#wpmobileapp-rateme-notice .notice-dismiss', function () {
                jQuery.post(ajaxurl, {
                    action: 'wpmobileapp_rateme_dismiss_notice',
                    security: '<?php echo wp_create_nonce("wpmobileapp_rateme_dismiss_notice"); ?>'
                });
            });
		</script>
		<?php
	}
});

add_action('wp_ajax_wpmobileapp_rateme_dismiss_notice', function () {
	check_ajax_referer('wpmobileapp_rateme_dismiss_notice', 'security');
	update_user_meta(get_current_user_id(), 'wpmobileapp_rateme_dismissed003', 1);
	wp_die();
});