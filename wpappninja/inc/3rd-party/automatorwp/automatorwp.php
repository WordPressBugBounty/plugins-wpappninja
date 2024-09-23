<?php

function automatorwp_register_wpmobile_integration() {
    automatorwp_register_integration( 'wpmobile', array(
        'label' => 'WPMobile.App',
        'icon'  => plugin_dir_url( __FILE__ ) . 'assets/icon.png',
    ) );

    require_once('actions/send-push.php');
}

if( function_exists('automatorwp_register_integration') && function_exists('automatorwp_register_action') ){
    automatorwp_register_wpmobile_integration();
} else {
    add_action('automatorwp_init', 'automatorwp_register_wpmobile_integration', 1);
}
