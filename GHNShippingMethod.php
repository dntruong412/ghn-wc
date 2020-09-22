<?php
/*
Plugin Name: GHN Shipping plugin
Plugin URI: https://woocommerce.com/
Description: GHN shipping method plugin
Version: 1.0.0
Author: WooThemes
Author URI: https://woocommerce.com/
*/

/**
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    function ghn_shipping_method_init() {
        if ( ! class_exists( 'GHN_WC_Shipping_Method' ) ) {
            class GHN_WC_Shipping_Method extends WC_Shipping_Method {
                /**
                 * Constructor for GHN shipping class
                 *
                 * @access public
                 * @return void
                 */
                public function __construct() {
                    $this->id                 = 'ghn_shipping_method'; // Id for ghn shipping method. Should be uunique.
                    $this->method_title       = __( 'GHN Shipping Method' );  // Title shown in admin
                    $this->method_description = __( 'Description of GHN shipping method' ); // Description shown in admin

                    $this->enabled            = "yes"; // This can be added as an setting but for this example its forced enabled
                    $this->title              = "GHN"; // This can be added as an setting but for this example its forced.

                    $this->cost               = 0;

                    $this->init();
                }

                /**
                 * Init settings
                 *
                 * @access public
                 * @return void
                 */
                function init() {
                    // Load the settings API
                    $this->init_form_fields(); // This is part of the settings API. Override the method to add own settings
                    $this->init_settings(); // This is part of the settings API. Loads settings you previously init.

                    // Save settings in admin if you have any defined
                    add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
                }

                /**
                 * calculate_shipping function.
                 *
                 * @access public
                 * @param mixed $package
                 * @return void
                 */
                public function calculate_shipping( $package = array() ) {
                    $rate = array(
                        'label' => $this->title,
                        'cost' => $this->cost,
                        'calc_tax' => 'per_order'
                    );

                    // Register the rate
                    $this->add_rate( $rate );
                }
            }
        }
    }

    add_action( 'woocommerce_shipping_init', 'ghn_shipping_method_init' );

    class GHNShippingMethods {
        public static $methods = array();
    }
}