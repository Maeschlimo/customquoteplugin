<?php
class WC_Settings_WATQ_Quote extends WC_Settings_Page {

    /**
    * Constructor
    */
    public function __construct() {

        $this->id    = 'watq_quote';

        add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );
        add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
        add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
        add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );

    }

    /**
    * Add plugin options tab
    *
    * @return array
    */
    public function add_settings_tab( $settings_tabs ) {
        $settings_tabs[$this->id] = __( 'Quote', WATQ );
        return $settings_tabs;
    }

    /**
    * Get sections
    *
    * @return array
    */
    public function get_sections() {
        $sections = array(
            'general'         => __( 'General Options', WATQ ),
            'messages'         => __( 'Messages', WATQ ),
            'email'         => __( 'Email', WATQ ),
        );
    return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
    }


    /**
    * Get sections
    *
    * @return array
    */
    public function get_settings( $section = null ) {

        $general = array(
            'section_title' => array(
                'name'     => __( 'General', WATQ ),
                'type'     => 'title',
                'desc'     => '',
                'id'       => 'wc_settings_quote_tab_general_section'
            ),
            'admin_email' => array(
                'name' => __( 'Admin Email', WATQ ),
                'type' => 'text',
                'desc' => __( 'leave this to use default admin email', WATQ ),
                'id'   => 'wc_settings_quote_admin_email'
            ),
            'quote_on_cart_dropdown' => array(
                'name' => __( 'Quote on Cart Page', WATQ ),
                'type' => 'select',
                'options'     => array(
                    'true' => __('Add Quote On Cart Page', WATQ ),
                    'false' => __('Remove Quote on Cart Page', WATQ )
                ),
                'desc' => __( 'wheather to show built a quote option on cart page', WATQ ),
                'id'   => 'wc_settings_quote_on_cart_select'
            ),
            'quote_to_cart_dropdown' => array(
                'name' => __( 'Convert Quote to Cart', WATQ ),
                'type' => 'select',
                'options'     => array(
                    'true' => __('Enable', WATQ ),
                    'false' => __('Disbale', WATQ )
                ),
                'desc' => __( 'wheather to show "add to cart" option on quote page', WATQ ),
                'id'   => 'wc_settings_quote_to_cart_select'
            ),
            'add_to_cart_on_detail_page_dropdown' => array(
                'name' => __( 'Add to Cart Button on Product Detail Page', WATQ ),
                'type' => 'select',
                'options'     => array(
                    'true' => __('Show', WATQ ),
                    'false' => __('Hide', WATQ )
                ),
                'desc' => __( 'wheather to show/hide "add to cart" button on product detail page', WATQ ),
                'id'   => 'wc_settings_add_to_cart_on_detail_page'
            ),
            'add_to_cart_global_dropdown' => array(
                'name' => __( 'Add to Cart Button everywhere', WATQ ),
                'type' => 'select',
                'options'     => array(
                    'true' => __('Show', WATQ ),
                    'false' => __('Hide', WATQ )
                ),
                'desc' => __( 'wheather to show/hide "add to cart" button on site', WATQ ),
                'id'   => 'wc_settings_add_to_cart_global'
            ),
            'quote_button_text' => array(
                'name' => __( 'Text for Quote Button', WATQ ),
                'type' => 'text',
                'desc' => __( 'This Text will show as notice when user successfully email quotes.', WATQ ),
                'id'   => 'wc_settings_quote_button_text'
            ),
            'allow_guest_user' => array(
                'name' => __( 'Allow Guest User', WATQ ),
                'type' => 'select',
                'options'     => array(
                    false => __('No', WATQ ),
                    true => __('Yes', WATQ )
                ),
                'desc' => __( 'if yes, it will allow guest user to get quote without registration.', WATQ ),
                'id'   => 'wc_settings_allow_guest_user'
            ),
            'empty_quote_to_cart' => array(
                'name' => __( 'Empty Quote', WATQ ),
                'type' => 'select',
                'options'     => array(
                    false => __('No', WATQ ),
                    true => __('Yes', WATQ )
                ),
                'desc' => __( 'if yes, it will empty quote after products moved to cart', WATQ ),
                'id'   => 'wc_settings_empty_quote_to_cart'
            ),
            'empty_quote_after_email' => array(
                'name' => __( 'Empty Quote After Email', WATQ ),
                'type' => 'select',
                'options'     => array(
                    false => __('No', WATQ ),
                    true => __('Yes', WATQ )
                ),
                'desc' => __( 'if yes, it will empty quote after email', WATQ ),
                'id'   => 'wc_settings_empty_quote_after_email'
            ),
            'empty_cart_to_quote' => array(
                'name' => __( 'Empty cart', WATQ ),
                'type' => 'select',
                'options'     => array(
                    false => __('No', WATQ ),
                    true => __('Yes', WATQ )
                ),
                'desc' => __( 'if yes, it will empty cart after products moved to quote', WATQ ),
                'id'   => 'wc_settings_empty_cart_to_quote'
            ),
            'section_end' => array(
                'type' => 'sectionend',
                'id' => 'wc_settings_quote_tab_general_section_end'
            )
        );

        switch( $section ) {
            case 'general' :
                $settings = $general;

            break;
            case 'messages':
                $settings = array(
                    'notices_options_title' => array(
                        'name'     => __( 'Notices Option', WATQ ),
                        'type'     => 'title',
                        'desc'     => '',
                        'id'       => 'wc_settings_quote_tab_notices_section'
                    ),
                    'success_email_quote_succes' => array(
                        'name' => __( 'Message Email Quote on Success', WATQ ),
                        'type' => 'textarea',
                        'desc' => __( 'This Text will show as notice when user successfully email quotes.', WATQ ),
                        'id'   => 'wc_settings_quote_success_email'
                    ),
                    'error_email_quote_error' => array(
                        'name' => __( 'Message Email Quote on Error', WATQ ),
                        'type' => 'textarea',
                        'desc' => __( 'This Text will show as notice when user get error on email quotes.', WATQ ),
                        'id'   => 'wc_settings_quote_error_email'
                    ),
                    'error_email_user_input' => array(
                        'name' => __( 'Message Email Quote on Error', WATQ ),
                        'type' => 'textarea',
                        'desc' => __( 'This Text will show as notice when user input wrong email address.', WATQ ),
                        'id'   => 'wc_settings_error_email_user_input'
                    ),
                    'notices_options_title_end' => array(
                        'type' => 'sectionend',
                        'id' => 'wc_settings_quote_tab_notices_section_end'
                )
            );
            break;
            case 'email':
                $settings = array(
                    'notices_options_title' => array(
                        'name'     => __( 'Customize Email Template', WATQ ),
                        'type'     => 'title',
                        'desc'     => '',
                        'id'       => 'wc_settings_quote_tab_email_section'
                    ),
                    'wc_settings_quote_email_subject' => array(
                        'name' => __( 'Subject', WATQ ),
                        'type' => 'text',
                        'desc' => __( 'Subject of Email', WATQ ),
                        'id'   => 'wc_settings_quote_email_subject'
                    ),
                    'wc_settings_quote_email_before_message' => array(
                        'name' => __( 'Text Before Quote', WATQ ),
                        'type' => 'textarea',
                        'desc' => __( 'Add Content Before the Message (html allowed)', WATQ ),
                        'id'   => 'wc_settings_quote_email_before_message'
                    ),
                    'wc_settings_quote_email_after_message' => array(
                        'name' => __( 'Text After Quote', WATQ ),
                        'type' => 'textarea',
                        'desc' => __( 'Add Content After the Message (html allowed)', WATQ ),
                        'id'   => 'wc_settings_quote_email_after_message'
                    ),
                    'notices_options_title_end' => array(
                        'type' => 'sectionend',
                        'id' => 'wc_settings_quote_tab_email_section_end'
                )
            );
            break;
            default:
                $settings = $general;
        }
    return apply_filters( 'wc_settings_tab_demo_settings', $settings, $section );
    }

    /**
    * Output the settings
    */
    public function output() {
        global $current_section;
        $settings = $this->get_settings( $current_section );
        WC_Admin_Settings::output_fields( $settings );
    }


    /**
    * Save settings
    */
    public function save() {
        global $current_section;
        $settings = $this->get_settings( $current_section );
        WC_Admin_Settings::save_fields( $settings );
    }

}
return new WC_Settings_WATQ_Quote();