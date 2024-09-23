<?php
if( !defined( 'ABSPATH' ) ) exit;

class AutomatorWP_Wpmobile_Send_Push extends AutomatorWP_Integration_Action {

    public $integration = 'wpmobile';
    public $action = 'wpmobile_send_push';

    public $thread_id;
    public $message_id;

    /**
     * Register the trigger
     *
     * @since 1.0.0
     */
    public function register() {
        automatorwp_register_action( $this->action, array(
            'integration'       => $this->integration,
            'label'             => __( 'Send <strong>a push notification</strong> to the user', 'wpappninja' ),
            'select_option'     => __( 'Send <strong>a push notification</strong> to the user', 'wpappninja' ),

            'edit_label'        => sprintf( __( 'Send a %1$s to the user', 'wpapppninja' ), '{push_notification}' ),

            'log_label'         => sprintf( __( 'Send a %1$s to the user', 'wpapppninja' ), '{push_notification}' ),
            'options'           => array(
                'push_notification' => array(
                    'default' => __( 'a push notification', 'wpappninja' ),
                    'fields' => array(
                    
                        'recipient' => array(
                            'name' => __( 'Recipient', 'wpappninja' ),
                            'desc' => __( 'Email of the user', 'wpappninja'  ),
                            'type' => 'text',
                            'required'  => true,
                            'default' => '{user_email}'
                        ),
                        'subject' => array(
                            'name' => __( 'Subject', 'wpappninja' ),
                            'type' => 'text',
                            'required'  => true,
                            'default' => ''
                        ),
                        'content' => array(
                            'name' => __( 'Content', 'wpappninja' ),
                            'type' => 'textarea',
                            'required'  => true,
                            'default' => ''
                        ),
                        'link' => array(
                            'name' => __( 'Link', 'wpappninja' ),
                            'type' => 'text',
                            'required'  => true,
                            'default' => ''
                        )
                    )
                )
            ),
        ) );

    }

    /**
     * Action execution function
     *
     * @since 1.0.0
     *
     * @param stdClass  $action             The action object
     * @param int       $user_id            The user ID
     * @param array     $action_options     The action's stored options (with tags already passed)
     * @param stdClass  $automation         The action's automation object
     */
    public function execute( $action, $user_id, $action_options, $automation ) {

        $recipient  = $action_options['recipient'];
        $subject    = $action_options['subject'];
        $content    = $action_options['content'];
        $link       = $action_options['link'];

        wpmobileapp_push($subject, $content, " ", $link, 'all', '', $recipient);
    }

    /**
     * Register required hooks
     *
     * @since 1.0.0
     */
    public function hooks() {
        parent::hooks();
    }

}

new AutomatorWP_Wpmobile_Send_Push();
