<?php $content_post = get_post(); ?>
<?php get_header(); ?>

<div class="posts">
	<?php if (isset($_GET['wpapp_shortcode'])) {

		if ($_GET['wpapp_shortcode'] == 'wpapp_push' || $_GET['wpapp_shortcode'] == 'wpapp_config' || $_GET['wpapp_shortcode'] == 'wpapp_login') {
			echo '<div class="post main-post">
			<div class="wpapp-post-content">';
		}

        if ($_GET['wpapp_shortcode'] == 'wpapp_recent') {
            echo wpappninja_widget('list-top');
        }

		$allowed_shortcodes = [
			'wpmobile_notification_badge',
			'wpapp_recent',
			'wpapp_home',
			'wpapp_home_configure',
			'wpapp_qrcode',
			'wpmobile_qrcode',
			'wpmobile_qrcode_2',
			'wpapp_history',
			'wpapp_share',
			'wpapp_social',
			'wpapp_search',
			'wpapp_config',
			'wpapp_push',
			'wpapp_lang_selector',
			'wpapp_login',
			'wpappninja_push_config'
		];

		$shortcode = isset($_GET['wpapp_shortcode']) ? $_GET['wpapp_shortcode'] : '';

		if (in_array($shortcode, $allowed_shortcodes, true)) {
			echo '<div data-instant>' . do_shortcode('[' . esc_html($shortcode) . ']') . '</div>';
		}
        
        if ($_GET['wpapp_shortcode'] == 'wpapp_recent') {
            echo wpappninja_widget('list-bottom');
        }

		if ($_GET['wpapp_shortcode'] == 'wpapp_push' || $_GET['wpapp_shortcode'] == 'wpapp_config' || $_GET['wpapp_shortcode'] == 'wpapp_login') {
			echo '</div>
			</div>';
		}

	} else if (isset($_GET['wpappninja_read_push'])) {

		echo wpappninja_show_push();

	} else {
		
		while ( have_posts() ) : the_post();
			$content_post = get_post();
			wpappninja_show_content($content_post);
		endwhile;

	} ?>

</div>
<?php
if(get_wpappninja_option('infinitescroll', '0') !== "0" && !wpappninja_is_custom_home($content_post) && !isset($_GET['wpappninja_read_push']) && is_single() && !isset($_GET['wpapp_shortcode'])) {
	wpappninja_show_previous_next($content_post); 
}

get_footer();
