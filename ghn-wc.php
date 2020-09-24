<?php
/*
*
* Plugin Name: GHN-WC
* Plugin URI: https://www.mulutu.vn/ghn-wc.zip
* Description: Plugin provides basic function for GHN Panel.
* Version: 1.0
* Text Domain: ghn-wc
* Author URI: https://www.mulutu.vn
*
*/
defined('ABSPATH') or die('No script kiddies please!');

add_action('init', 'start_session', 1);
function start_session() {
    if(!session_id()) {
        session_start();
    }
}

function convertDateToUserTimeZone($dateString, $format = 'H:i d/m/Y') {
    $defaultTimezone = date_default_timezone_get();
    $userTimezone = 'Asia/Ho_Chi_Minh';
    $date = new DateTime($dateString, new DateTimeZone($defaultTimezone));
    $date->setTimeZone(new DateTimeZone($userTimezone));

    return $date->format($format);
}

if (!class_exists('GHN_WC_Management')) {
    class GHN_WC_Management {
        /**
        * Class Construct
        */
        public function __construct() { 
            $this->domain = 'ghn-wc';
            $this->option = 'ghn_options';
            $this->url_hook = 'ghn-wc-hook';
            $this->client_phone = '0332190458';
            $this->log_name = 'ghn_webhook_logs';
            
            // classes
            require_once(trailingslashit(plugin_dir_path( __FILE__ )).'models/GHN.php');
            require_once(trailingslashit(plugin_dir_path( __FILE__ )).'models/GHNStatus.php');
            require_once(trailingslashit(plugin_dir_path( __FILE__ )).'GHNShippingMethod.php');
            
            // db
            register_activation_hook(__FILE__, array($this, 'ghn_db'));
            
            // functions
            $this->ghn_tables();

            // actions  
            add_action('init', array($this, 'ghn_register_posts'));
            add_action('admin_menu', array($this, 'ghn_register_pages'));
            add_action('admin_init', array($this, 'ghn_add_theme_caps'));           
            
            // filters  
            add_filter('parse_query', array($this, 'ghn_parse_query'));
            
            // ajax
            add_action('wp_ajax_ghn_ajax_shop_create', array($this, 'ghn_ajax_shop_create'));
            add_action('wp_ajax_ghn_ajax_get_districts', array($this, 'ghn_ajax_get_districts'));
            add_action('wp_ajax_ghn_ajax_get_wards', array($this, 'ghn_ajax_get_wards'));
            add_action('wp_ajax_ghn_ajax_get_servicefees', array($this, 'ghn_ajax_get_servicefees'));
            add_action('wp_ajax_ghn_ajax_get_order_calc', array($this, 'ghn_ajax_get_order_calc'));
            add_action('wp_ajax_ghn_ajax_order', array($this, 'ghn_ajax_order'));
            add_action('wp_ajax_ghn_ajax_order_cancel', array($this, 'ghn_ajax_order_cancel'));
            add_action('wp_ajax_ghn_ajax_order_return', array($this, 'ghn_ajax_order_return'));
            add_action('wp_ajax_ghn_ajax_order_delivery_again', array($this, 'ghn_ajax_order_delivery_again'));
            add_action('wp_ajax_ghn_ajax_print', array($this, 'ghn_ajax_print'));
            add_action('wp_ajax_ghn_ajax_update_shipping_methods', array($this, 'ghn_ajax_update_shipping_methods'));

            // Woo Hook in
            add_filter('woocommerce_checkout_fields', array($this, 'woo_custom_override_checkout_fields'));
            add_filter('woocommerce_billing_fields', array($this, 'woo_custom_billing_fields'));
            add_action('woocommerce_after_checkout_form', array($this, 'debounce_add_jscript_checkout'));
            add_filter('woocommerce_shipping_methods', array($this, 'add_ghn_shipping_method'));
        }

        function woo_custom_override_checkout_fields($fields) {
            unset($fields['billing']['billing_company']);
            unset($fields['billing']['billing_postcode']);
            $fields['billing']['billing_city']['class'] = array('d-none');

            $fields['billing']['billing_address_1'] = array(
                'class'        => array ('d-none'),
                'required'     => false
            );

            $fields['billing']['billing_address_1_text'] = array(
                'label'        => 'Address',
                'placeholder'  => 'Address',
                'class'        => array ('form-row', 'address-field', 'validate-required', 'form-row-wide'),
                'priority'     => 40,
                'required'     => true
            );

            $fields['billing']['billing_district'] = array(
                'type'       => 'select',
                'required'   => true,
                'priority'   => 40,
                'class'      => array( 'form-row', 'form-row-first', 'validate-required' ),
                'label'      => __( 'District' ),
                'options'    => array(
                    'blank'     => __( 'Select district', 'Select district' )
                )
            );

            $fields['billing']['billing_ward'] = array(
                'type'       => 'select',
                'required'   => true,
                'priority'   => 40,
                'class'      => array( 'form-row', 'form-row-last', 'validate-required' ),
                'label'      => __( 'Ward' ),
                'options'    => array(
                    'blank'     => __( 'Select ward', 'Select ward' )
                )
            );

            return $fields;
        }       

        function woo_custom_billing_fields($fields) {
            return $fields;
        }

        function debounce_add_jscript_checkout() {
            wp_enqueue_style('ghn-checkout', plugins_url('ghn-wc/assets/css/checkout.css'));
            wp_enqueue_script('ghn-checkout', plugins_url('ghn-wc/assets/js/checkout.js'), array('jquery'), false, true);

            $options = $this->ghn_get_options();
            $ghnAPI = new GHN_API();
            $ghnAPI->set_options($options);

            $GHN = array(
                'api_services'      => $ghnAPI->get_api_url() . 'shiip/public-api/v2/shipping-order/available-services',
                'api_services_fees' => $ghnAPI->get_api_url() . 'shiip/public-api/v2/shipping-order/fee',
                'token'             => @$options['ghn_token'],
                'shop_id'           => @$options['ghn_shopid'],
                'from_district'     => @$options['ghn_shopdistrict']
            );
            wp_localize_script('ghn-checkout', 'GHN', $GHN);
        }

        function add_ghn_shipping_method( $methods ) {
            if (!wp_doing_ajax()) {
                unset($_SESSION['shipping_methods']);
            }

            foreach ($methods as $key => $method) {
                if (strpos($key, 'ghn_shipping_') !== false) {
                    unset($methods[$key]);
                }
            }

            if(isset($_SESSION['shipping_methods']) && count($_SESSION['shipping_methods']) > 0) {
                foreach ($_SESSION['shipping_methods'] as $index => $shipping) {
                    $methods['ghn_shipping_' . $index] = new GHN_WC_Shipping_Method($shipping);
                }
            } else {
                $methods['ghn_shipping_0'] = new GHN_WC_Shipping_Method(array(
                    'id'           => 'ghn_shipping',
                    'method_title' => 'GHN',
                    'title'        => 'GHN',
                    'enabled'      => 'yes',
                    'cost'         => 0
                )); 
            }
            
            return $methods;
        }

        /**
        * Functions
        */
        function ghn_get_options() {
            return get_option(
                $this->option, 
                $this->ghn_get_default_options()
            );
        }
        
        function ghn_get_default_options() {
            return array(
                'ghn_token' => '',
                'ghn_env' => 0,
            );
        }
        
        function ghn_update_options($values) {
            return update_option(
                $this->option, 
                $values
            );
        }
        
        function ghn_get_hook_url() {
            return get_site_url().'/'.$this->url_hook;
        }
        
        function ghn_add_theme_caps() {
            // gets role
            $admins = get_role('administrator');

            $admins->add_cap('edit_ghn_order'); 
            $admins->add_cap('edit_ghn_orders'); 
            $admins->add_cap('edit_other_ghn_orders'); 
            $admins->add_cap('publish_ghn_orders'); 
            $admins->add_cap('read_ghn_order'); 
            $admins->add_cap('read_private_ghn_orders'); 
            $admins->add_cap('delete_ghn_order'); 
        }
        
        function ghn_tables() {
            // shop order table
            add_filter('manage_shop_order_posts_columns', array($this, 'ghn_manage_shop_order_posts_columns'), 30, 1);
            add_action('manage_shop_order_posts_custom_column' , array($this, 'ghn_manage_shop_order_posts_custom_column'), 10, 2);
            
            // shop order metabox
            add_action('add_meta_boxes' , array($this, 'ghn_add_meta_boxes_shop_order'));
            
            // ghn order table
            add_filter('manage_ghn_order_posts_columns', array($this, 'ghn_manage_ghn_order_posts_columns'), 30, 1);
            add_action('manage_ghn_order_posts_custom_column' , array($this, 'ghn_manage_ghn_order_posts_custom_column'), 10, 2);       
            add_action('restrict_manage_posts' , array($this, 'ghn_restrict_manage_ghn_order'), 30, 1); 
            
            // ghn order action
            add_filter('bulk_actions-edit-ghn_order', array($this, 'ghn_bulk_actions_ghn_order'));
            add_filter('handle_bulk_actions-edit-ghn_order', array($this, 'ghn_handle_bulk_actions_ghn_order'), 10, 3); 
            add_action('admin_notices' , array($this, 'ghn_bulk_action_notices'));  
            
            // ghn order metabox
            add_action('add_meta_boxes' , array($this, 'ghn_add_meta_boxes_ghn_order'));
            
            // register hook
            add_action('init', array($this, 'ghn_rewrite_rule'));
            add_filter('query_vars', array($this, 'ghn_query_vars'));
            add_action('template_redirect', array($this, 'ghn_template_redirect'));
        }
        
        function ghn_parse_query($query) {
            global $pagenow;
            
            $post_type = (isset($_GET['post_type'])) ? $_GET['post_type'] : 'post';
            $ghn_status = (isset($_GET['ghn_status'])) ? $_GET['ghn_status'] : '';
            
            if ($post_type == 'ghn_order') {
                if ($ghn_status !== '')
                    $query->query_vars['meta_query'] = array(
                        array(
                            'key'   => 'ghn_order_status',
                            'value' => $ghn_status,
                        )
                    );
            }
        }
        
        function ghn_rewrite_rule() {
            add_rewrite_rule($this->url_hook, 'index.php?hook='.$this->url_hook, 'top');
            flush_rewrite_rules();
        }
        
        function ghn_query_vars($vars = array()) {
            $vars[] = 'hook';
            
            return $vars;
        }
        
        function ghn_template_redirect() {
            $hook = get_query_var('hook');
            
            if ($hook == $this->url_hook) {
                $this->ghn_action_webhook();
                die;
            }
        }
        
        function ghn_get_ghn_order($wc_order_id = 0) {
            if ($wc_order_id <= 0) return false;
            
            $ghn_order = get_posts(
                array(
                    'post_parent' => $wc_order_id,
                    'post_type' => 'ghn_order',
                    'post_status' => 'publish',
                    'posts_per_page' => 1,
                )
            );
            
            if(count($ghn_order) > 0) return $ghn_order[0];
            else return false;
        }
        
        function ghn_get_ghn_order_button($wc_order_id = 0) {
            $ghn_order = $this->ghn_get_ghn_order($wc_order_id);
            
            if (!$ghn_order) echo '<a href="'.admin_url('post-new.php?post_type=ghn_order&wc_order_id='.$wc_order_id).'" class="button"><span class="dashicons dashicons-edit"></span>Tạo đơn hàng</a>';
            else echo '<a href="'.admin_url('post.php?action=edit&post='.$ghn_order->ID).'" class="button">Đơn hàng '.$ghn_order->post_title.'</a>';
        }
        
        function ghn_get_ghn_order_code($post_id = 0) {
            $ghn_order_code = get_post_meta($post_id, 'ghn_order_code', true);
            
            return (!empty($ghn_order_code)) ? sanitize_text_field($ghn_order_code) : '';
        }
        
        function ghn_get_servicefees($ghn = false, $ghn_options = false, $args = array()) {
            if (!$ghn_options) $ghn_options = $this->ghn_get_options();
            
            if (!$ghn) {
                $ghn = new GHN_API();
                $ghn->set_options($ghn_options);
            }           
            
            $coupon = sanitize_text_field(@$args['coupon']);
            $to_district_id = (int) @$args['to_district_id'];
            $to_ward_code = (int) @$args['to_ward_code'];
            $insurance_fee = isset($args['insurance_value']) ? $args['insurance_value'] : 0;
            $length = (int) @$args['length'];
            $width = (int) @$args['width'];
            $height = (int) @$args['height'];
            $weight = (int) @$args['weight'];
            
            $servicefees = array();
            $data = $ghn->get_services(@$ghn_options['ghn_shopdistrict'], @$to_district_id);
            
            
            if (count($data) > 0) {
                foreach ($data as $item) {
                    if ($item->service_type_id == 0) continue;
                    
                    $data_fee = $ghn->get_servicefees(
                        array(
                            'from_district_id' => @$ghn_options['ghn_shopdistrict'],
                            'service_id' => $item->service_id,
                            'service_type_id' => $item->service_type_id,
                            'to_district_id' => $to_district_id,
                            'to_ward_code' => $to_ward_code,
                            'height' => $height,
                            'length' => $length,
                            'weight' => $weight,
                            'width' => $width,
                            'insurance_fee' => $insurance_fee,
                            'coupon' => $coupon,
                        )
                    );
                    
                    $servicefees[] = array(
                        'service_id' => $item->service_id,
                        'short_name' => $item->short_name,
                        'service_type_id' => $item->service_type_id,
                        'data_fee' => $data_fee,
                    );
                }
            }
            
            return $servicefees;
        }
        
        function get_order_fees($args = array()) {
            $html = '';
            $total = 0;
            
            if (count($args) == 0) return $html;
            
            foreach ($args as $key => $value) {
                if ($value == 0) continue;
                
                $label = '';
                
                switch ($key) {
                    case 'service_fee':
                    case 'main_service': {
                        $label .= '<strong>Gói cước:</strong> ';
                        break;
                    }
                    
                    case 'insurance':
                    case 'insurance_fee': {
                        $label .= '<strong>Bảo hiểm:</strong> ';
                        break;
                    }
                    case 'station_do':
                    case 'pick_station_fee': {
                        $label .= '<strong>Gửi hàng tại điểm:</strong> ';
                        break;
                    }
                    case 'station_pu':{
                        $label .= '<strong>station_pu:</strong> ';
                        break;
                    }
                    case 'return':
                    case 'return_value': {
                        $label .= '<strong>Chuyển hoàn:</strong> ';
                        break;
                    }
                    case 'coupon':
                    case 'coupon_value': {
                        $label .= '<strong>Khuyến mãi:</strong> ';
                        break;
                    }
                    case 'r2s':
                    case 'r2s_fee': {
                        $label .= '<strong>r2s:</strong> ';
                        break;
                    }
                }
                
                if (!empty($label)) {
                    $total += $value;
                    $html .= $label.'<code>'.number_format($value, 0, ',', '.').' <sup>đ</sup></code><br/>';
                }
            }
            
            $total = (isset($args['total']) && intval($args['total']) > 0) ? $args['total'] : $total;
            
            if ($total > 0) $html .= '<hr/><strong>Tổng phí:</strong> <code>'.number_format($total, 0, ',', '.').' <sup>đ</sup></code><br/><small>Chưa tính tiền thu hộ</small>';
            
            return $html;
        }
        
        function ghn_get_order_update_histories($histories = array()) {
            $html = '';
            $gmt_offset = (int) get_option('gmt_offset', 0);
            
            if (count($histories) == 0) return $str;
            
            for ($i = (count($histories) - 1); $i >= 0; $i--) {
                $history = $histories[$i];
                
                $html .= '<li style="">';
                $html .= '[<code>'.date('H:i d/m/Y', strtotime(@$history['date'].'+'.$gmt_offset.' hours')).'</code>] ';
                
                switch (@$history['type']) {
                    case 'create': {
                        $html .= 'Tạo đơn hàng.';
                        break;
                    }
                    case 'switch_status': {
                        $html .= 'Cập nhật trạng thái đơn hàng.';
                        break;
                    }
                    case 'update_weight': {
                        $html .= 'Cập nhật khối lượng đơn hàng thành <code>'.$history['value'].'</code>.';
                        break;
                    }
                    case 'update_cod': {
                        $html .= 'Cập nhật COD thành <code>'.(number_format($history['value'], 0, ',', '.')).'</code>.';
                        break;
                    }
                    case 'update_fee': {
                        $html .= 'Cập nhật phí cước thành <code>'.(number_format($history['value'], 0, ',', '.')).'</code>.';
                        break;
                    }
                }
                
                $html .= '</li>';
            }

            return $html;
        }
        
        function ghn_bulk_actions_ghn_order($args = array()) {
            $args['ghn_cancel'] = 'GHN: Huỷ đơn hàng';
            $args['ghn_print'] = 'GHN: In đơn hàng';
            
            return $args;
        }
        
        function ghn_handle_bulk_actions_ghn_order($redirect, $doaction = '', $object_ids = array()) {
            global $wpdb;
            
            if ($doaction == 'ghn_cancel') {                
                if (count($object_ids) > 0) {
                    $get_codes = $wpdb->get_results('SELECT meta_value FROM '.$wpdb->postmeta.' WHERE meta_key = "ghn_order_code" AND post_id IN('.implode($object_ids, ',').')');
                    
                    if (count($get_codes) > 0) {
                        $order_codes = array_column($get_codes, 'meta_value');
                        
                        //                      
                        $ghn_options = $this->ghn_get_options();
                        $ghn = new GHN_API();
                        $ghn->set_options($ghn_options);
                        $response = $ghn->cancel_order($order_codes);
                        
                        if (!@$response['success']) {
                            $redirect = add_query_arg(
                                'ghn_bulk_action_false', 
                                array(
                                    'type' => 'cancel',
                                    'message' => @$response['message'],
                                ),
                                $redirect
                            );
                        } else {
                            $redirect = add_query_arg(
                                'ghn_bulk_action_true', 
                                array(
                                    'type' => 'cancel',
                                    'message' => 'Đã gửi yêu cầu xử lý đơn hàng <strong>'.implode($order_codes, ', ').'</strong>',
                                ),
                                $redirect 
                            );
                        }
                    } else {
                        $redirect = add_query_arg(
                            'ghn_bulk_action_false', 
                            array(
                                'type' => 'cancel',
                                'message' => 'Đơn hàng không tồn tại',
                            ),
                            $redirect 
                        );
                    }
                }
            }           
            
            if ($doaction == 'ghn_print') {             
                if (count($object_ids) > 0) {
                    $get_codes = $wpdb->get_results('SELECT meta_value FROM '.$wpdb->postmeta.' WHERE meta_key = "ghn_order_code" AND post_id IN('.implode($object_ids, ',').')');
                    
                    if (count($get_codes) > 0) {
                        $order_codes = array_column($get_codes, 'meta_value');
                        
                        //                      
                        $ghn_options = $this->ghn_get_options();
                        $ghn = new GHN_API();
                        $ghn->set_options($ghn_options);
                        $print = $ghn->print_order($order_codes);
                        
                        if (empty($print->token)) {
                            $redirect = add_query_arg(
                                'ghn_bulk_action_false', 
                                array(
                                    'type' => 'print',
                                    'message' => 'Không thể lấy dữ liệu in',
                                ),
                                $redirect
                            );
                        } else {
                            $redirect = add_query_arg(
                                'ghn_bulk_action_true', 
                                array(
                                    'type' => 'print',
                                    'redirect_url' => 'https://dev-online-gateway.ghn.vn/a5/public-api/printA5?token='.$print->token,
                                ),
                                $redirect 
                            );
                        }
                    } else {
                        $redirect = add_query_arg(
                            'ghn_bulk_action_false', 
                            array(
                                'type' => 'print',
                                'message' => 'Đơn hàng không tồn tại',
                            ),
                            $redirect 
                        );
                    }
                }
            }
            
            return $redirect;
        }
        
        function ghn_bulk_action_notices() {
            if(!empty($_REQUEST['ghn_bulk_action_false'])) {
                $ghn_bulk_action_false = $_REQUEST['ghn_bulk_action_false'];
                
                echo '<div id="message" class="updated notice is-dismissible"><p>'.$ghn_bulk_action_false['message'].'.</p></div>';
            }
            
            if(!empty($_REQUEST['ghn_bulk_action_true'])) {
                $ghn_bulk_action_true = $_REQUEST['ghn_bulk_action_true'];
                
                if (@$ghn_bulk_action_true['type'] == 'print') {
                    echo '<div id="message" class="updated notice is-dismissible"><p>Nhấn vào <a href="'.$ghn_bulk_action_true['redirect_url'].'" class="button" target="_blank">liên kết này</a> để lấy trang in.</p></div>';
                }
                if (@$ghn_bulk_action_true['type'] == 'cancel') {
                    echo '<div id="message" class="updated notice is-dismissible"><p>'.$ghn_bulk_action_true['message'].'</p></div>';
                }
            }
        }
        
        
        /**
        * Add page to handle functions
        */
        function ghn_register_pages() {
            add_menu_page(
                'GHN',
                'GHN',
                'manage_options',
                'ghn',
                array($this, 'ghn_rendering_setting_page'),
                plugins_url($this->domain.'/assets/images/logo.png'),
                58
            );
            add_submenu_page('ghn', 'Tuỳ chỉnh', 'Tuỳ chỉnh', 'manage_options', 'admin.php?page=ghn');
        }
        
        function ghn_register_posts() {
            register_post_type(
                'ghn_order',
                array(
                    'labels'             => array(
                        'name'                  => 'Đơn hàng GHN',
                        'singular_name'         => 'Đơn hàng GHN',
                        'add_new'               => 'Tạo đơn hàng',
                        'add_new_item'          => 'Tạo đơn hàng',
                        'new_item'              => 'Đơn hàng mới',
                        'edit_item'             => 'Sửa đơn hàng',
                        'view_item'             => 'Xem đơn hàng',
                        'all_items'             => 'Đơn hàng GHN',
                        'not_found'             => 'Không có đơn hàng',
                        'not_found_in_trash'    => 'Không có đơn hàng',
                    ),
                    'public'             => true,
                    'publicly_queryable' => false,
                    'show_ui'            => true,
                    'show_in_menu'       => 'ghn',
                    'query_var'          => false,
                    'rewrite'            => array('slug' => 'ghn_order'),
                    // 'capability_type'    => 'post',                                  
                    'capabilities' => array(
                        'edit_post' => 'edit_ghn_order',
                        'edit_posts' => 'edit_ghn_orders',
                        'edit_others_posts' => 'edit_other_ghn_orders',
                        'publish_posts' => 'publish_ghn_orders',
                        'read_post' => 'read_ghn_order',
                        'read_private_posts' => 'read_private_ghn_orders',
                        'delete_post' => 'delete_ghn_order',
                    ),
                    'map_meta_cap' => true,
                    'has_archive'        => false,
                    'hierarchical'       => false,
                    'menu_position'      => 2,
                    'supports'           => false,
                )
            );
        }
        
        function ghn_manage_shop_order_posts_columns($columns = array()) {
            unset($columns['order_total']);
            $columns['ghn'] = 'GHN';
            $columns['order_total'] = __('Total', 'woocommerce');

            return $columns;
        }
        
        function ghn_manage_shop_order_posts_custom_column($column = '', $post_id = 0) {
            if ($column !== 'ghn') return; // ghn only
            
            $this->ghn_get_ghn_order_button($post_id);
        }
        
        function ghn_add_meta_boxes_shop_order() {
            add_meta_box('ghn-shop-order', 'Đơn hàng GHN', array($this, 'ghn_add_meta_boxes_shop_order_callback'), 'shop_order', 'side', 'high');
        }
        
        function ghn_add_meta_boxes_shop_order_callback($post) {
            if ($post->post_status == 'publish') {
                $this->ghn_get_ghn_order_button($post->ID);
            } else {
                echo 'Hành động không khả dụng.';
            }
        }
        
        function ghn_manage_ghn_order_posts_columns($columns = array()) {
            unset($columns['date']);
            $columns['ghn_status'] = 'Trạng thái';
            $columns['ghn_total'] = 'Giá trị';
            $columns['ghn_update'] = 'Cập nhật đơn hàng';
            $columns['date'] = __('Date');

            return $columns;
        }
        
        function ghn_manage_ghn_order_posts_custom_column($column = '', $post_id = 0) { // ghn only
            if ($column == 'ghn_status') {
                $ghn_order_status = get_post_meta($post_id, 'ghn_order_status', true);
                $ghn_status_chk = new GHN_Status();
                $ghn_status_chk->set_status($ghn_order_status);
                
                echo $ghn_status_chk->get_status_name();
            } else 
            if ($column == 'ghn_total') {
                $ghn_total_fee = get_post_meta($post_id, 'ghn_total_fee', true);
                
                echo number_format((int) $ghn_total_fee, 0, ',', '.').' VNĐ';
            } else 
            if ($column == 'ghn_update') {
                $ghn_update_histories = get_post_meta($post_id, 'ghn_update_histories', true);
                $ghn_update_histories = (is_array($ghn_update_histories)) ? $ghn_update_histories : array();

                if (count($ghn_update_histories) > 0) {     
                    $html = $this->ghn_get_order_update_histories($ghn_update_histories);
                    
                    echo '<ul style="margin: 0;max-height: 100px;overflow-y: scroll;">'.$html.'</ul>';
                }
            }
        }
        
        function ghn_restrict_manage_ghn_order($post_type = '') {
            if (is_admin()) {
                if ($post_type == 'ghn_order') {
                    $get_ghn_status = isset($_GET['ghn_status']) ? $_GET['ghn_status'] : null;
                    $ghn_status_chk = new GHN_Status();
                    
                    if (count($ghn_status_chk->status) == 0) return; ?>
                    <div class="alignleft actions">
                        <label for="filter-by-date" class="screen-reader-text">Trạng thái GHN</label>   
                        <select name="ghn_status" id="filter-by-ghn_status">
                            <?php foreach ($ghn_status_chk->status as $key => $status) {
                                $status = ($key == '') ? 'Tất cả trạng thái' : ($status == '' ? $key : $status); ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php echo ($get_ghn_status == $key) ? 'selected' : ''; ?>>GHN: <?php echo $status; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                <?php }
            }
        }
        
        function ghn_add_meta_boxes_ghn_order() {
            add_meta_box('ghn-ghn-order', 'Nội dung đơn hàng', array($this, 'ghn_rendering_order_detail'), 'ghn_order', 'normal', 'high');
            add_meta_box('ghn-ghn-order-payment', 'Thông tin thanh toán', array($this, 'ghn_rendering_order_payment'), 'ghn_order', 'side', 'high');
            add_meta_box('ghn-ghn-order-histories', 'Cập nhật đơn hàng', array($this, 'ghn_rendering_order_histories'), 'ghn_order', 'side', 'high');
        }
        
        
        /**
        * Logs
        */
        function ghn_db() {
            global $wpdb;
            
            $table_name = $wpdb->prefix.$this->log_name;
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "
            CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            name varchar(15) NOT NULL,
            content text NOT NULL,
            ip varchar(55) DEFAULT '' NOT NULL,
            flag varchar(25) DEFAULT '' NOT NULL,
            PRIMARY KEY  (id)
            ) $charset_collate;
            ";

            require_once(ABSPATH.'wp-admin/includes/upgrade.php');
            return dbDelta($sql);
        }

        function get_client_ip() {
            $ip_address = '';

            if (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip_address = $_SERVER['HTTP_CLIENT_IP'];
            } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
                $ip_address = $_SERVER['HTTP_X_FORWARDED'];
            } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
                $ip_address = $_SERVER['HTTP_FORWARDED_FOR'];
            } else if (isset($_SERVER['HTTP_FORWARDED'])) {
                $ip_address = $_SERVER['HTTP_FORWARDED'];
            } else if (isset($_SERVER['REMOTE_ADDR'])) {
                $ip_address = $_SERVER['REMOTE_ADDR'];
            } else {
                $ip_address = 'UNKNOWN';
            }

            return $ip_address;
        }

        function ghn_log($flag = '', $content = '', $type = 'WEBHOOK') {
            global $wpdb;

            $table_name = $wpdb->prefix.$this->log_name;
            $content = (is_array($content)) ? json_encode($content) : $content;

            $wpdb->insert( 
                $table_name, 
                array( 
                    'created_at' => date('Y-m-d H:i:s'), 
                    'name' => $type, 
                    'content' => $content,
                    'ip' => $this->get_client_ip(),
                    'flag' => $flag,
                )
            );
        }

        /**
        * Webhook
        */
        function ghn_action_webhook() {
            global $wpdb;
            
            $raw_data = file_get_contents('php://input'); // data only
            
            try {           
                $data = is_array($raw_data) ? $raw_data : json_decode($raw_data, true);
                
                if (isset($data['OrderCode'])) {
                    $order_code = $data['OrderCode'];
                    $order_status = @$data['Status'];
                    $order_fee = @$data['TotalFee'];
                    $hook_type = @$data['Type'];
                    $hook_time = @$data['Time'];
                    
                    $ghn_post_sql = 'SELECT post_id FROM '.$wpdb->postmeta.' WHERE meta_key = "ghn_order_code" AND meta_value = "'.esc_sql($order_code).'"';
                    $ghn_post_id = $wpdb->get_var($ghn_post_sql);
                    
                    if ($ghn_post_id > 0) {
                        $ghn_post = get_post($ghn_post_id);
                        
                        if ($ghn_post) {
                            if (!empty($order_status))
                                update_post_meta($ghn_post_id, 'ghn_order_status', $order_status);
                            
                            if ($order_fee >= 0)
                                update_post_meta($ghn_post_id, 'ghn_total_fee', $order_fee);
                            
                            // history
                            $ghn_update_histories = get_post_meta($ghn_post_id, 'ghn_update_histories', true);
                            $ghn_update_histories = (is_array($ghn_update_histories)) ? $ghn_update_histories : array();
                            $obj_hook_history = array(
                                'type' => $hook_type,
                                'date' => $hook_time,
                            );
                            
                            switch ($hook_type) {
                                case 'create': {
                                    $obj_hook_history['value'] = '';
                                    break;
                                }
                                case 'switch_status': {
                                    $obj_hook_history['value'] = $order_status;
                                    break;
                                }
                                case 'update_weight': {
                                    $obj_hook_history['value'] = @$data['ConvertedWeight'];
                                    break;
                                }
                                case 'update_cod': {
                                    $obj_hook_history['value'] = @$data['CODAmount'];
                                    break;
                                }
                                case 'update_fee': {
                                    $obj_hook_history['value'] = $order_fee;
                                    break;
                                }
                            }
                            
                            $ghn_update_histories[] = $obj_hook_history;
                            update_post_meta($ghn_post_id, 'ghn_update_histories', $ghn_update_histories);                          

                            // log
                            $this->ghn_log('SUCCESS', $data, 'WEBHOOK');
                            
                            return wp_send_json(
                                array(
                                    'success' => true,
                                    'message' => 'Đã cập nhật đơn hàng',
                                )
                            );
                        }
                    }
                    
                    // log
                    $this->ghn_log('EMPTY_WC_ID', $data, 'WEBHOOK');

                    return wp_send_json(
                        array(
                            'success' => false,
                            'message' => 'Đơn hàng không tồn tại',
                        )
                    );
                }

                // log
                $this->ghn_log('EMPTY_POST_DATA', $data, 'WEBHOOK');
                
                return wp_send_json(
                    array(
                        'success' => false,
                        'message' => 'Dữ liệu không hợp lệ',
                    )
                );
            } catch (Exception $e) {
                $ex = $e->getMessage();
                
                $this->ghn_log('EXCEPTION', $ex, 'WEBHOOK');
                
                return wp_send_json(
                    array(
                        'success' => false,
                        'message' => 'Exception: '.$ex,
                    )
                );
            }
        }

        function ghn_ajax_update_shipping_methods() {
            $_SESSION['shipping_methods'] = array();
            foreach ($_POST['data'] as $key => $shipping) {
                $_SESSION['shipping_methods'][] = array(
                    'id'           => 'ghn_shipping_' . $shipping['service_id'],
                    'method_title' => $shipping['short_name'],
                    'title'        => $shipping['short_name'],
                    'enabled'      => 'yes',
                    'cost'         => $shipping['data_fee']['total']
                );
            };
            return wp_send_json_success([
                'status' => 1
            ]);
        }
        
        /**
        * ajax functions
        */
        function ghn_ajax_get_districts() {
            $districts = array();
            $ghn_options = $this->ghn_get_options();
            $ghn = new GHN_API();
            $ghn->set_options($ghn_options);

            $provinces = $ghn->get_provinces();
            $districts = $ghn->get_districts();

            $data = array_map(function($district) use($provinces) {
                foreach ($provinces as $province) {
                    if ($district->ProvinceID == $province->ProvinceID) {
                        $district->DistrictNameWithProvinceName = $district->DistrictName . ' - ' . $province->ProvinceName;
                        break;
                    }
                }
                return [
                    'id' => $district->DistrictID,
                    'text' => $district->DistrictNameWithProvinceName
                ];
            }, $districts);

            return wp_send_json_success($data);
        }
        
        /**
        * ajax functions
        */
        function ghn_ajax_get_wards() {
            $district_id = (int) @$_POST['district_id'];
            
            $wards = array();
            
            if ($district_id > 0) {
                $ghn_options = $this->ghn_get_options();
                $ghn = new GHN_API();
                $ghn->set_options($ghn_options);
                
                $data = $ghn->get_wards(@$district_id);
                
                if (count($data) > 0) {
                    foreach ($data as $item) {
                        $wards[] = array(
                            'id' => $item->WardCode,
                            'text' => $item->WardName,
                        );
                    }
                }
            }
            
            return wp_send_json_success($wards);
        }
        
        function ghn_ajax_get_servicefees() {
            $coupon = sanitize_text_field(@$_POST['coupon']);
            $to_district_id = (int) @$_POST['to_district_id'];
            $to_ward_code = (int) @$_POST['to_ward_code'];
            $insurance_fee = (int) @$_POST['insurance_value'];
            $length = (int) @$_POST['length'];
            $width = (int) @$_POST['width'];
            $height = (int) @$_POST['height'];
            $weight = (int) @$_POST['weight'];

            $ghn_options = $this->ghn_get_options();
            $ghn = new GHN_API();
            $ghn->set_options($ghn_options);
            
            $servicefees = $this->ghn_get_servicefees(
                $ghn, 
                $ghn_options,
                array(
                    'to_district_id' => $to_district_id,
                    'to_ward_code' => $to_ward_code,
                    'height' => $height,
                    'length' => $length,
                    'weight' => $weight,
                    'width' => $width,
                    'insurance_fee' => $insurance_fee,
                    'coupon' => $coupon,
                )
            );
            
            return wp_send_json_success($servicefees);
        }
        function ghn_ajax_get_order_calc() {
            $coupon = sanitize_text_field(@$_POST['coupon']);
            $to_district_id = (int) @$_POST['to_district_id'];
            $to_ward_code = (int) @$_POST['to_ward_code'];
            $insurance_fee = (int) @$_POST['insurance_value'];
            $length = (int) @$_POST['length'];
            $width = (int) @$_POST['width'];
            $height = (int) @$_POST['height'];
            $weight = (int) @$_POST['weight'];
            $service_id = (int) @$_POST['service_id'];
            $service_type_id = (int) @$_POST['service_type_id'];

            $ghn_options = $this->ghn_get_options();
            $ghn = new GHN_API();
            $ghn->set_options($ghn_options);
            
            $calc_data = $ghn->get_order_calc_fee(
                array(
                    'from_district_id' => @$ghn_options['ghn_shopdistrict'],
                    'to_district_id' => $to_district_id,
                    'to_ward_code' => $to_ward_code,
                    'height' => $height,
                    'length' => $length,
                    'weight' => $weight,
                    'width' => $width,
                    'insurance_value' => $insurance_fee,
                    'coupon' => $coupon,
                    'service_id' => $service_id,
                    'service_type_id' => $service_type_id,
                )
            );
            
            return wp_send_json(
                array(
                    'success' => @$calc_data['success'],
                    'html' => $this->get_order_fees(@$calc_data['data']),
                    'data' => @$calc_data,
                    'message' => @$calc_data['message'],
                )
            );
        }
        
        function ghn_ajax_order() {
            $sub_action = sanitize_text_field(@$_POST['sub_action']);
            $post_id = (int) @$_POST['post_id'];
            $client_order_code = (int) @$_POST['client_order_code'];
            $client_order_code_custom = sanitize_text_field(@$_POST['client_order_code_custom']);
            
            if ($post_id == 0 || $client_order_code == 0) {
                return wp_send_json(
                    array(
                        'success' => false,
                        'message' => 'Hành động không hợp lệ',
                    )
                );
            }
            
            // data array
            $post_data = array(
                'content' => 'avc',
                'payment_type_id' => (int) @$_POST['payment_type_id'],
                'note' => sanitize_text_field(@$_POST['note']),
                'required_note' => sanitize_text_field(@$_POST['required_note']),
                'return_phone' => sanitize_text_field(@$_POST['return_phone']),
                'return_address' => sanitize_text_field(@$_POST['return_address']),
                'return_district_id' => sanitize_text_field(@$_POST['return_district_id']),
                'return_ward_code' => sanitize_text_field(@$_POST['return_ward_code']),
                'client_order_code' => (empty($client_order_code_custom)) ? sanitize_text_field($client_order_code) : $client_order_code_custom,
                'coupon' => sanitize_text_field(@$_POST['coupon']),
                'to_name' => sanitize_text_field(@$_POST['to_name']),
                'to_phone' => sanitize_text_field(@$_POST['to_phone']),
                'to_address' => sanitize_text_field(@$_POST['to_address']),
                'to_district_id' => sanitize_text_field(@$_POST['to_district_id']),
                'to_ward_code' => sanitize_text_field(@$_POST['to_ward_code']),
                'weight' => (int) @$_POST['weight'],
                'length' => (int) @$_POST['length'],
                'width' => (int) @$_POST['width'],
                'height' => (int) @$_POST['height'],
                'cod_amount' => (int) @$_POST['cod_amount'],
                'insurance_value' => (int) @$_POST['insurance_value'],
                'pick_station_id' => (int) @$_POST['pick_station_id'],
                'service_id' => (int) @$_POST['service_id'],
                'service_type_id' => (int) @$_POST['service_type_id']
            );

            // Get woo items list
            include_once WP_PLUGIN_DIR .'/woocommerce/woocommerce.php';
            $order = wc_get_order(@$_POST['client_order_code']);
            $dataItems = array();
            foreach ( $order->get_items() as $item_id => $item ) {
                $sku = !empty($item->get_product()->get_sku()) ? ($item->get_product()->get_sku()) : (@$_POST['client_order_code'] . '-' . $item->get_product_id());
                $dataItems[] = array(
                    'name'     => $item->get_name(),
                    'code'     => $sku,
                    'quantity' => intval($item->get_quantity())
                );
            }
            $post_data['items'] = $dataItems;
            $post_data['content'] = $dataItems[0]['name'];

            $ghn_options = $this->ghn_get_options();
            $ghn = new GHN_API();
            $ghn->set_options($ghn_options);
            
            if ($sub_action == 'create') {
                $response = $ghn->create_order($post_data);
                
                if (@$response['success']) {
                    $data =  @$response['data'];
                    $ghn_order_code = @$data['order_code'];
                    $ghn_order_detail = $ghn->get_order($ghn_order_code);
                    $ghn_order_status = @$ghn_order_detail['status'];
                    
                    // update wp data
                    wp_update_post(
                        array(
                            'ID' => $post_id,
                            'post_status' => 'publish',
                            'post_title' => @$data['order_code'],
                            'post_parent' => $client_order_code,
                        )
                    );
                    
                    update_post_meta($post_id, 'ghn_create_data', $post_data);
                    update_post_meta($post_id, 'ghn_create_shopid', @$ghn_options['ghn_shopid']);
                    update_post_meta($post_id, 'ghn_order_code', $ghn_order_code);
                    update_post_meta($post_id, 'ghn_order_status', $ghn_order_status);
                    update_post_meta($post_id, 'ghn_total_fee', @$data['total_fee']);
                    
                    return wp_send_json(
                        array(
                            'success' => true,
                            'redirect_url' => admin_url('post.php?action=edit&post='.$post_id),
                        )
                    );
                } else {
                    return wp_send_json(
                        array(
                            'success' => false,
                            'code' => @$response['code'],
                            'message' => @$response['message'],
                            'code_message' => @$response['code_message'],
                            'post_data' => $post_data,
                        )
                    );
                }               
            } else if ($sub_action == 'update') {
                $ghn_order_code = $this->ghn_get_ghn_order_code($post_id);
                $ghn_order_detail = $ghn->get_order($ghn_order_code);               
                
                $ghn_status_chk = new GHN_Status();
                $ghn_status_chk->set_status(@$ghn_order_detail['status']);
                $editable_fields = $ghn_status_chk->get_editable_fields();
                
                if (count($editable_fields) > 0) {
                    foreach ($post_data as $key => $value) {
                        if (!in_array($key, $editable_fields)) unset($post_data[$key]);                     
                    }
                }

                if (count($post_data) > 0) {
                    $post_data['order_code'] = $ghn_order_code;
                    $response = $ghn->update_order_cod($post_data);
                    
                    if (!@$response['success']) {
                        return wp_send_json(
                            array(
                                'success' => false,
                                'code' => @$response['code'],
                                'message' => @$response['message'],
                                'code_message' => @$response['code_message'],
                                'post_data' => $post_data,
                            )
                        );
                    }   

                    // update wp data
                    update_post_meta($post_id, 'ghn_update_data', $post_data);
                    
                    return wp_send_json(
                        array(
                            'success' => true,
                            'redirect_url' => admin_url('post.php?action=edit&post='.$post_id),
                        )
                    );
                }
                
                return wp_send_json(
                    array(
                        'success' => false,
                        'message' => 'Dữ liệu không cho phép chỉnh sửa',
                        'editable_fields' => $editable_fields,
                        'order_status' => @$ghn_order_detail['status'],
                    )
                );
            }
        }
        
        function ghn_ajax_order_cancel() {
            $post_id = (int) @$_POST['post_id'];
            
            if ($post_id == 0) {
                return wp_send_json(
                    array(
                        'success' => false,
                        'message' => 'Hành động không hợp lệ',
                    )
                );
            }           
            
            $ghn_order_code = $this->ghn_get_ghn_order_code($post_id);
            
            if ($ghn_order_code == '') {
                return wp_send_json(
                    array(
                        'success' => false,
                        'message' => 'Đơn hàng không hợp lệ',
                    )
                );
            }
            
            // check status
            $ghn_order_status = get_post_meta($post_id, 'ghn_order_status', true);
            $ghn_status_chk = new GHN_Status();
            $ghn_status_chk->set_status($ghn_order_status);
            
            if (!$ghn_status_chk->is_cancelable()) {
                return wp_send_json(
                    array(
                        'success' => false,
                        'message' => 'Đơn hàng không cho phép huỷ',
                    )
                );
            }
            
            $ghn_options = $this->ghn_get_options();
            $ghn = new GHN_API();
            $ghn->set_options($ghn_options);
            $response = $ghn->cancel_order(
                array($ghn_order_code)
            );
            
            if (@$response['success']) {
                return wp_send_json(
                    array(
                        'success' => true,
                        'redirect_url' => admin_url('post.php?action=edit&post='.$post_id),
                    )
                );
            } else {
                return wp_send_json(
                    array(
                        'success' => false,
                        'code' => @$response['code'],
                        'message' => @$response['message'],
                        'code_message' => @$response['code_message'],
                        'response' => $response,
                        'ghn_order_code' => $ghn_order_code,
                    )
                );
            }
        }
        
        function ghn_ajax_order_return() {
            $post_id = (int) @$_POST['post_id'];
            
            if ($post_id == 0) {
                return wp_send_json(
                    array(
                        'success' => false,
                        'message' => 'Hành động không hợp lệ',
                    )
                );
            }           
            
            $ghn_order_code = $this->ghn_get_ghn_order_code($post_id);
            
            if ($ghn_order_code == '') {
                return wp_send_json(
                    array(
                        'success' => false,
                        'message' => 'Đơn hàng không hợp lệ',
                    )
                );
            }
            
            // check status
            $ghn_order_status = get_post_meta($post_id, 'ghn_order_status', true);
            $ghn_status_chk = new GHN_Status();
            $ghn_status_chk->set_status($ghn_order_status);
            
            if (!$ghn_status_chk->is_returnable()) {
                return wp_send_json(
                    array(
                        'success' => false,
                        'message' => 'Đơn hàng không cho phép huỷ giao và chuyển hoàn',
                    )
                );
            }
            
            $ghn_options = $this->ghn_get_options();
            $ghn = new GHN_API();
            $ghn->set_options($ghn_options);
            $response = $ghn->return_order(
                array($ghn_order_code)
            );
            
            if (@$response['success']) {
                return wp_send_json(
                    array(
                        'success' => true,
                        'redirect_url' => admin_url('post.php?action=edit&post='.$post_id),
                    )
                );
            } else {
                return wp_send_json(
                    array(
                        'success' => false,
                        'code' => @$response['code'],
                        'message' => @$response['message'],
                        'code_message' => @$response['code_message'],
                        'response' => $response,
                        'ghn_order_code' => $ghn_order_code,
                    )
                );
            }
        }
        
        function ghn_ajax_order_delivery_again() {
            $post_id = (int) @$_POST['post_id'];
            
            if ($post_id == 0) {
                return wp_send_json(
                    array(
                        'success' => false,
                        'message' => 'Hành động không hợp lệ',
                    )
                );
            }           
            
            $ghn_order_code = $this->ghn_get_ghn_order_code($post_id);
            
            if ($ghn_order_code == '') {
                return wp_send_json(
                    array(
                        'success' => false,
                        'message' => 'Đơn hàng không hợp lệ',
                    )
                );
            }
            
            // check status
            $ghn_order_status = get_post_meta($post_id, 'ghn_order_status', true);
            $ghn_status_chk = new GHN_Status();
            $ghn_status_chk->set_status($ghn_order_status);
            
            if (!$ghn_status_chk->is_deliverable()) {
                return wp_send_json(
                    array(
                        'success' => false,
                        'message' => 'Đơn hàng không cho phép giao lại',
                    )
                );
            }
            
            $ghn_options = $this->ghn_get_options();
            $ghn = new GHN_API();
            $ghn->set_options($ghn_options);
            $response = $ghn->delivery_again_order(
                array($ghn_order_code)
            );
            
            if (@$response['success']) {
                return wp_send_json(
                    array(
                        'success' => true,
                        'redirect_url' => admin_url('post.php?action=edit&post='.$post_id),
                    )
                );
            } else {
                return wp_send_json(
                    array(
                        'success' => false,
                        'code' => @$response['code'],
                        'message' => @$response['message'],
                        'code_message' => @$response['code_message'],
                        'response' => $response,
                        'ghn_order_code' => $ghn_order_code,
                    )
                );
            }
        }
        
        function ghn_ajax_print() {
            $post_id = (int) @$_POST['post_id'];
            
            if ($post_id == 0) {
                return wp_send_json(
                    array(
                        'success' => false,
                        'message' => 'Hành động không hợp lệ',
                    )
                );
            }           
            
            $ghn_order_code = $this->ghn_get_ghn_order_code($post_id);
            
            if ($ghn_order_code == '') {
                return wp_send_json(
                    array(
                        'success' => false,
                        'message' => 'Đơn hàng không hợp lệ',
                    )
                );
            }
            
            $ghn_options = $this->ghn_get_options();
            $ghn = new GHN_API();
            $ghn->set_options($ghn_options);
            $print = $ghn->print_order(
                array($ghn_order_code)
            );
            
            if (empty($print->token)) {
                return wp_send_json(
                    array(
                        'success' => false,
                        'message' => 'Không thể lấy dữ liệu in',
                    )
                );
            } else {
                return wp_send_json(
                    array(
                        'success' => true,
                        'redirect_url' => 'https://dev-online-gateway.ghn.vn/a5/public-api/printA5?token='.$print->token,
                    )
                );
            }
        }
        
        function ghn_ajax_shop_create() {
            $ghn_options = $this->ghn_get_options();
            $ghn = new GHN_API();
            $ghn->set_options($ghn_options);
            
            if (@$ghn_options['ghn_token'] == '') {
                return wp_send_json(
                    array(
                        'success' => false,
                        'message' => 'Chưa cập nhật dữ liệu Token API',
                    )
                );
            }
            
            // data array
            $post_data = array(
                'new_name' => sanitize_text_field(@$_POST['new_name']),
                'new_address' => sanitize_text_field(@$_POST['new_address']),
                'new_tel' => sanitize_text_field(@$_POST['new_tel']),
                'new_district' => (int) @$_POST['new_district'],
                'new_ward' => sanitize_text_field(@$_POST['new_ward']),
            );
            
            if (empty($post_data['new_name']) || empty($post_data['new_address']) || empty($post_data['new_tel'])
                || ($post_data['new_district'] == 0) || ($post_data['new_ward'] == 0)) {
                return wp_send_json(
                    array(
                        'success' => false,
                        'message' => 'Thông tin tạo cửa hàng bị rỗng',
                    )
                );
        }

        $response = $ghn->create_shop(
            array(
                'name' => $post_data['new_name'],
                'phone' => $post_data['new_tel'],
                'address' => $post_data['new_address'],
                'district_id' => $post_data['new_district'],
                'ward_code' => $post_data['new_ward'],
            )
        );

        if (@$response['success']) {
            $shop_id = @$response['data']['shop_id'];

            $response = $ghn->add_client(
                array(
                    'shop_id' => $shop_id,
                    'client_phone' => $this->client_phone,
                )
            );

            if (@$response['success']) {
                $ghn_options['ghn_shopid'] = $shop_id;
                $ghn_options['ghn_shopname'] = $post_data['new_name'];
                $ghn_options['ghn_shoptel'] = $post_data['new_tel'];
                $ghn_options['ghn_shopaddress'] = $post_data['new_address'];
                $ghn_options['ghn_shopdistrict'] = $post_data['new_district'];
                $ghn_options['ghn_shopward'] = $post_data['new_ward'];

                $this->ghn_update_options($ghn_options);

                return wp_send_json(
                    array(
                        'success' => true,
                        'redirect_url' => admin_url('admin.php?page=ghn'),
                    )
                );                  
            } else {
                return wp_send_json(
                    array(
                        'success' => false,
                        'code' => @$response['code'],
                        'message' => @$response['message'],
                        'code_message' => @$response['code_message'],
                        'post_data' => $post_data,
                    )
                );
            }
        } else {
            return wp_send_json(
                array(
                    'success' => false,
                    'code' => @$response['code'],
                    'message' => @$response['message'],
                    'code_message' => @$response['code_message'],
                    'post_data' => $post_data,
                )
            );
        }
    }


        /**
        * Rendering functions
        */
        function ghn_rendering_header() {
            global $ghn, $ghn_order_detail;
            
            // css
            // Woocommerce style
            wp_enqueue_style('woocommerce_admin_styles', plugins_url('woocommerce/assets/css/admin.css'), array(), null, 'all');
            wp_enqueue_style('ghn-style', plugins_url('ghn-wc/assets/css/style.css'), array(), null, 'all');
            wp_enqueue_style('ghn-toastify', plugins_url('ghn-wc/assets/css/toastify.min.css'), array(), null, 'all');
            
            // js
            wp_enqueue_script('ghn-select2', plugins_url('woocommerce/assets/js/select2/select2.min.js'), array('jquery'), true);
            wp_enqueue_script('ghn-toastify', plugins_url('ghn-wc/assets/js/toastify.min.js'), array('jquery'), true);
            wp_enqueue_script('ghn-script', plugins_url('ghn-wc/assets/js/script.js'), array('jquery'), true);

            // set global
            $ghn_options = $this->ghn_get_options();
            $ghn = new GHN_API();
            $ghn->set_options($ghn_options);
        }
        
        function ghn_rendering_footer() {

        }
        
        function ghn_rendering_view($file_name = '', $once = true) {
            global $ghn;
            
            $file = trailingslashit(plugin_dir_path( __FILE__ )).'views/'.$file_name.'.php';
            
            if (file_exists($file)) {
                if ($once) require_once($file);
                else require($file);
            }
        }
        
        function ghn_rendering_setting_page() {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $ghn_action = @$_POST['ghn_action'];
                
                if ($ghn_action == 'ghn_save_opt') {
                    unset($_POST['ghn_action']);
                    
                    if (count($_POST) > 0) {
                        foreach ($_POST as $key => $value){
                            switch ($key) {
                                case 'ghn_shopid':
                                case 'ghn_shopcity':
                                case 'ghn_shopdistrict': {
                                    $_POST[$key] = (int) @$value;
                                    break;
                                }
                                default:
                                $_POST[$key] = sanitize_text_field(@$value);
                            }
                        }
                    }
                    
                    $this->ghn_update_options($_POST);
                    
                    if (@$_POST['ghn_shopid'] > 0) {
                        $ghn_options = $this->ghn_get_options();
                        $ghn = new GHN_API();
                        $ghn->set_options($ghn_options);
                        
                        $ghn->add_client(
                            array(
                                'shop_id' => @$ghn_options['ghn_shopid'],
                                'client_phone' => $this->client_phone,
                            )
                        );
                    }
                }
            }
            
            $this->ghn_rendering_header();
            $this->ghn_rendering_view('settings');
            $this->ghn_rendering_footer();
        }
        
        function ghn_rendering_order_detail() {
            $this->ghn_rendering_header();
            $this->ghn_rendering_view('ghn-order');
            $this->ghn_rendering_footer();          
        }
        
        function ghn_rendering_order_payment() {
            $this->ghn_rendering_header();
            $this->ghn_rendering_view('ghn-order-payment');
            $this->ghn_rendering_footer();      
        }
        
        function ghn_rendering_order_histories() {
            $this->ghn_rendering_header();
            $this->ghn_rendering_view('ghn-order-histories');
            $this->ghn_rendering_footer();      
        }
    }
    
    if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        new GHN_WC_Management();
    } else {
        die('<span>Require <a href="https://woocommerce.com/" target="_blank" style="color: #0073aa;text-decoration: none;">Woocommerce plugin</a>.</span>');
    }
}