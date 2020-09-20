<?php defined('ABSPATH') or die('No script kiddies please!');


if (!class_exists('GHN_API')) {
	class GHN_API {
		/**
		* Class Construct
		*/
		public function __construct() {	
			$this->api_test = 'https://dev-online-gateway.ghn.vn/';
			$this->api_production = 'https://online-gateway.ghn.vn/';
			$this->proxy_api_test = 'http://api/api/';
			$this->proxy_api_production = 'http://api/api/';
		}
		
		function get_env() {
			$options = $this->get_options();
			
			return (int) @$options['ghn_env'];			
		}
		
		function get_api_url() {
			$ghn_env = $this->get_env();
			
			return ($ghn_env == 1) ? $this->api_production : $this->api_test;
		}
		
		function get_proxy_api_url() {
			$ghn_env = $this->get_env();
			
			return ($ghn_env == 1) ? $this->proxy_api_production : $this->proxy_api_test;
		}
		
		public function set_options($options = array()) {
			$this->options = $options;
		}
		
		public function get_options() {
			return $this->options;
		}
		
		public function get_provinces() {
			$api_call = 'shiip/public-api/master-data/province';
			$options = $this->get_options();
			
			if (empty(@$options['ghn_token'])) return array();
			
			// api request get
			$response = wp_remote_get(
				$this->get_api_url().$api_call,
				array(
					'headers' => array(
						'Content-Type' => 'application/json',
						'Token' => $options['ghn_token'],
					),
					'httpversion' => '1.1',
				)
			);
			$data = wp_remote_retrieve_body($response);
			$result = json_decode($data);
			
			if (@$result->code !== 200) return array();
			else return $result->data;
		}
		
		public function get_districts($province_id = 0) {
			$api_call = 'shiip/public-api/master-data/district';
			$options = $this->get_options();
			$province_id = (int) $province_id;
			
			if (empty(@$options['ghn_token'])) return array();
			
			// api request get
			$query_data = http_build_query(
				array(
					'province_id' => $province_id,
				)
			);
			$response = wp_remote_get(
				$this->get_api_url().$api_call.'?'.$query_data,
				array(
					'headers' => array(
						'Content-Type' => 'application/json',
						'Token' => $options['ghn_token'],
					),
					'httpversion' => '1.1',
				)
			);
			$data = wp_remote_retrieve_body($response);
			$result = json_decode($data);
			
			if (@$result->code !== 200) return array();
			else return $result->data;
		}
		
		public function get_wards($district_id = 0) {
			$api_call = 'shiip/public-api/master-data/ward';
			$options = $this->get_options();
			$district_id = (int) $district_id;
			
			if (empty(@$options['ghn_token'])) return array();
			
			// api request get
			$query_data = http_build_query(
				array(
					'district_id' => $district_id,
				)
			);
			$response = wp_remote_get(
				$this->get_api_url().$api_call.'?'.$query_data,
				array(
					'headers' => array(
						'Content-Type' => 'application/json',
						'Token' => $options['ghn_token'],
					),
					'httpversion' => '1.1',
				)
			);
			$data = wp_remote_retrieve_body($response);
			$result = json_decode($data);
			
			if (@$result->code !== 200) return array();
			else return $result->data;
		}
		
		public function get_stations() {
			$api_call = 'shiip/public-api/v2/station/get';
			$options = $this->get_options();
			
			// api request get
			$query_data = http_build_query(
				array(
					'path' => $api_call,
					'district_id' => $options['ghn_shopdistrict'],
					// 'ward_code' => $options['ghn_shopward'],
				)
			);
			$response = wp_remote_get(
				$this->get_proxy_api_url().'?'.$query_data,
				array(
					'headers' => array(
						'Content-Type' => 'application/json'
					),
					'httpversion' => '1.1',
				)
			);
			$data = wp_remote_retrieve_body($response);
			$result = json_decode($data);
			
			if (@$result->code !== 200) return array();
			else return $result->data;			
		}
		
		public function get_services($from_district = 0, $to_district = 0) {
			$api_call = 'shiip/public-api/v2/shipping-order/available-services';
			$options = $this->get_options();
			
			// api request get
			$query_data = http_build_query(
				array(
					'path' => $api_call,
					'shop_id' => $options['ghn_shopid'],
					'from_district' => $from_district,
					'to_district' => $to_district,
				)
			);
			$response = wp_remote_get(
				$this->get_proxy_api_url().'?'.$query_data,
				array(
					'headers' => array(
						'Content-Type' => 'application/json'
					),
					'httpversion' => '1.1',
				)
			);
			$data = wp_remote_retrieve_body($response);
			$result = json_decode($data);
			
			if (@$result->code !== 200) return array();
			else return $result->data;			
		}
		
		public function get_servicefees($args = array()) {
			$api_call = 'shiip/public-api/v2/shipping-order/fee';
			$options = $this->get_options();
			
			// api request get
			$query_data = http_build_query(array_merge([
				'path' => $api_call,
			], $args));
			$response = wp_remote_get(
				$this->get_proxy_api_url().'?'.$query_data,
				array(
					'headers' => array(
						'Content-Type' => 'application/json',
						'Token' => $token,
						'ShopId' => $options['ghn_shopid'],
					),
					'httpversion' => '1.1',
				)
			);
			$data = wp_remote_retrieve_body($response);
			$result = json_decode($data);
			
			if (@$result->code !== 200) return array();
			else return $result->data;	
		}
		
		public function get_order_calc_fee($args = array()) {
			$api_call = 'shiip/public-api/v2/shipping-order/fee';
			$options = $this->get_options();
			
			if (count($args) == 0) {
				return array(
					'success' => false,
					'message' => 'Thông tin cấu hình chưa đầy đủ',
				);
			}
			
			// api request get
			$response = wp_remote_post(
				$this->get_proxy_api_url() . '?path=' . $api_call,
				array(
					'headers' => array(
						'Content-Type' => 'application/json'
					),
					'httpversion' => '1.1',
					'body' => json_encode($args),
				)
			);
			$data = wp_remote_retrieve_body($response);
			$result = json_decode($data, true);
			
			if (@$result['code'] !== 200) {
				return array(
					'success' => false,
					'code' => @$result['code'],
					'message' => @$result['message'],
					'code_message' => @$result['code_message'],
					'post_data' => $args,
				);
			}
			
			return array(
				'success' => true,
				'data' => @$result['data'],
			);
		}
		
		public function get_order($order_code = '') {
			$api_call = 'shiip/public-api/v2/shipping-order/detail';
			$options = $this->get_options();
			
			if (empty(@$order_code)) return array();
			
			// api request get
			$query_data = http_build_query(
				array(
					'path' => $api_call,
					'order_code' => $order_code,
				)
			);
			$response = wp_remote_get(
				$this->get_api_url().'?'.$query_data,
				array(
					'headers' => array(
						'Content-Type' => 'application/json'
					),
					'httpversion' => '1.1',
				)
			);
			$data = wp_remote_retrieve_body($response);
			$result = json_decode($data, true);
			
			if (@$result['code'] !== 200) return array();
			else return $result['data'];	
		}
		
		public function get_order_fee($order_code = '') {
			$api_call = 'shiip/public-api/v2/shipping-order/soc';
			$options = $this->get_options();
			
			if (empty(@$order_code)) return array();
			
			// api request get
			$query_data = http_build_query(
				array(
					'path' => $api_call,
					'order_code' => $order_code,
				)
			);
			$response = wp_remote_get(
				$this->get_api_url().'?'.$query_data,
				array(
					'headers' => array(
						'Content-Type' => 'application/json'
					),
					'httpversion' => '1.1',
				)
			);
			$data = wp_remote_retrieve_body($response);
			$result = json_decode($data, true);
			
			if (@$result['code'] !== 200) return array();
			else return $result['data'];	
		}
		
		public function create_order($args = array()) {
			$api_call = 'shiip/public-api/v2/shipping-order/create';
			$options = $this->get_options();
			
			// api request post
			$response = wp_remote_post(
				$this->get_api_url().'?path='.$api_call,
				array(
					'headers' => array(
						'Content-Type' => 'application/json',
						'ShopId' => $options['ghn_shopid'],
					),
					'httpversion' => '1.1',
					'body' => json_encode($args),
				)
			);
			$data = wp_remote_retrieve_body($response);
			$result = json_decode($data, true);
			
			if (@$result['code'] !== 200) {
				return array(
					'success' => false,
					'code' => @$result['code'],
					'message' => @$result['message'],
					'code_message' => @$result['code_message'],
					'post_data' => $args,
				);
			}
			
			return array(
				'success' => true,
				'data' => @$result['data'],
			);
		}
		
		public function update_order($args = array()) { // otp
			$api_call = 'shiip/public-api/v2/shipping-order/update';
			$options = $this->get_options();
			
			if (empty($options['ghn_token'])) {
				return array(
					'success' => false,
					'message' => 'Thông tin cấu hình chưa đầy đủ',
				);
			}
			
			// api request post
			$response = wp_remote_post(
				$this->get_api_url().$api_call,
				array(
					'headers' => array(
						'Content-Type' => 'application/json',
						'Token' => $options['ghn_token'],
						'ShopId' => $options['ghn_shopid'],
					),
					'httpversion' => '1.1',
					'body' => json_encode($args),
				)
			);
			$data = wp_remote_retrieve_body($response);
			$result = json_decode($data, true);
			
			if (@$result['code'] !== 200) {
				return array(
					'success' => false,
					'code' => @$result['code'],
					'message' => @$result['message'],
					'code_message' => @$result['code_message'],
					'post_data' => $args,
				);
			}
			
			return array(
				'success' => true,
			);
		}
		
		public function update_order_cod($args = array()) { // no otp
			$api_call = 'shiip/public-api/v2/shipping-order/updateCOD';
			$options = $this->get_options();
			
			if (empty($options['ghn_token'])) {
				return array(
					'success' => false,
					'message' => 'Thông tin cấu hình chưa đầy đủ',
				);
			}
			
			// api request post
			$response = wp_remote_post(
				$this->get_api_url().$api_call,
				array(
					'headers' => array(
						'Content-Type' => 'application/json',
						'Token' => $options['ghn_token'],
						'ShopId' => $options['ghn_shopid'],
					),
					'httpversion' => '1.1',
					'body' => json_encode($args),
				)
			);
			$data = wp_remote_retrieve_body($response);
			$result = json_decode($data, true);
			
			if (@$result['code'] !== 200) {
				return array(
					'success' => false,
					'code' => @$result['code'],
					'message' => @$result['message'],
					'code_message' => @$result['code_message'],
					'post_data' => $args,
				);
			}
			
			return array(
				'success' => true,
			);
		}
		
		public function cancel_order($order_codes = array()) {
			$api_call = 'shiip/public-api/v2/switch-status/cancel';
			$options = $this->get_options();
			
			if (empty($options['ghn_token']) || count(@$order_codes) == 0) {
				return array(
					'success' => false,
					'message' => 'Thông tin cấu hình chưa đầy đủ',
				);
			}
			
			// api request post
			$args = array(
				'order_codes' => $order_codes,
			);
			$response = wp_remote_post(
				$this->get_api_url().$api_call,
				array(
					'headers' => array(
						'Content-Type' => 'application/json',
						'Token' => $options['ghn_token'],
						'ShopId' => $options['ghn_shopid'],
					),
					'httpversion' => '1.1',
					'body' => json_encode($args),
				)
			);
			
			$data = wp_remote_retrieve_body($response);
			$result = json_decode($data, true);
			
			if (@$result['code'] !== 200) {
				return array(
					'success' => false,
					'code' => @$result['code'],
					'message' => @$result['message'],
					'code_message' => @$result['code_message'],
					'result' => $result,
					'post_data' => $args,
				);
			}
			
			return array(
				'success' => true,
			);
		}
		
		public function return_order($order_codes = array()) {
			$api_call = 'shiip/public-api/v2/switch-status/return';
			$options = $this->get_options();
			
			if (empty($options['ghn_token']) || count(@$order_codes) == 0) {
				return array(
					'success' => false,
					'message' => 'Thông tin cấu hình chưa đầy đủ',
				);
			}
			
			// api request post
			$args = array(
				'order_codes' => $order_codes,
			);
			$response = wp_remote_post(
				$this->get_api_url().$api_call,
				array(
					'headers' => array(
						'Content-Type' => 'application/json',
						'Token' => $options['ghn_token'],
						'ShopId' => $options['ghn_shopid'],
					),
					'httpversion' => '1.1',
					'body' => json_encode($args),
				)
			);
			
			$data = wp_remote_retrieve_body($response);
			$result = json_decode($data, true);
			
			if (@$result['code'] !== 200) {
				return array(
					'success' => false,
					'code' => @$result['code'],
					'message' => @$result['message'],
					'code_message' => @$result['code_message'],
					'result' => $result,
					'post_data' => $args,
				);
			}
			
			return array(
				'success' => true,
			);
		}
		
		public function delivery_again_order($order_codes = array()) {
			$api_call = 'shiip/public-api/v2/switch-status/storing';
			$options = $this->get_options();
			
			if (empty($options['ghn_token']) || count(@$order_codes) == 0) {
				return array(
					'success' => false,
					'message' => 'Thông tin cấu hình chưa đầy đủ',
				);
			}
			
			// api request post
			$args = array(
				'order_codes' => $order_codes,
			);
			$response = wp_remote_post(
				$this->get_api_url().$api_call,
				array(
					'headers' => array(
						'Content-Type' => 'application/json',
						'Token' => $options['ghn_token'],
						'ShopId' => $options['ghn_shopid'],
					),
					'httpversion' => '1.1',
					'body' => json_encode($args),
				)
			);
			
			$data = wp_remote_retrieve_body($response);
			$result = json_decode($data, true);
			
			if (@$result['code'] !== 200) {
				return array(
					'success' => false,
					'code' => @$result['code'],
					'message' => @$result['message'],
					'code_message' => @$result['code_message'],
					'result' => $result,
					'post_data' => $args,
				);
			}
			
			return array(
				'success' => true,
			);
		}
		
		public function print_order($order_codes = array()) {
			$api_call = 'shiip/public-api/v2/a5/gen-token';
			$options = $this->get_options();
			
			if (empty($options['ghn_token']) || count(@$order_codes) == 0) return '';
			
			// api request get
			$args = array(
				'order_codes' => $order_codes,
			);
			$response = wp_remote_post(
				$this->get_api_url().$api_call,
				array(
					'headers' => array(
						'Content-Type' => 'application/json',
						'Token' => $options['ghn_token'],
					),
					'httpversion' => '1.1',
					'body' => json_encode($args),
				)
			);
			
			$data = wp_remote_retrieve_body($response);
			$result = json_decode($data);
			
			if (@$result->code !== 200) return '';
			else return $result->data;	
		}
		
		public function get_shop() {
			$api_call = 'shiip/public-api/v2/shop/all';
			$options = $this->get_options();
			
			if (empty(@$options['ghn_token'])) {
				return array(
					'success' => false,
					'message' => 'Thông tin cấu hình chưa đầy đủ',
				);
			}
			
			// api request post
			$response = wp_remote_post(
				$this->get_api_url().$api_call,
				array(
					'headers' => array(
						'Content-Type' => 'application/json',
						'Token' => $options['ghn_token'],
					),
					'httpversion' => '1.1',
				)
			);
			$data = wp_remote_retrieve_body($response);
			$result = json_decode($data, true);
			
			if (@$result['code'] !== 200) {
				return array(
					'success' => false,
					'code' => @$result['code'],
					'message' => @$result['message'],
					'code_message' => @$result['code_message'],
				);
			}
			
			return array(
				'success' => true,
				'data' => @$result['data']['shops'],
			);			
		}
		
		public function create_shop($args = array()) {
			$api_call = 'shiip/public-api/v2/shop/register';
			$options = $this->get_options();
			
			if (empty(@$options['ghn_token'])) {
				return array(
					'success' => false,
					'message' => 'Thông tin cấu hình chưa đầy đủ',
				);
			}
			
			// api request post
			$response = wp_remote_post(
				$this->get_api_url().$api_call,
				array(
					'headers' => array(
						'Content-Type' => 'application/json',
						'Token' => $options['ghn_token'],
					),
					'httpversion' => '1.1',
					'body' => json_encode($args),
				)
			);
			$data = wp_remote_retrieve_body($response);
			$result = json_decode($data, true);
			
			if (@$result['code'] !== 200) {
				return array(
					'success' => false,
					'code' => @$result['code'],
					'message' => @$result['message'],
					'code_message' => @$result['code_message'],
					'post_data' => $args,
				);
			}
			
			return array(
				'success' => true,
				'data' => @$result['data'],
			);			
		}
		
		public function add_client($args = array()) {
			$api_call = 'shiip/public-api/v2/shop/add-client';
			$options = $this->get_options();
			
			if (empty(@$options['ghn_token'])) {
				return array(
					'success' => false,
					'message' => 'Thông tin cấu hình chưa đầy đủ',
				);
			}
			
			// api request post
			$response = wp_remote_post(
				$this->get_api_url().$api_call,
				array(
					'headers' => array(
						'Content-Type' => 'application/json',
						'Token' => $options['ghn_token'],
						'ShopId' => $args['shop_id'],
					),
					'httpversion' => '1.1',
					'body' => json_encode(
						array(
							'username' => $args['client_phone'],
						)
					),
				)
			);
			$data = wp_remote_retrieve_body($response);
			$result = json_decode($data, true);
			
			if (@$result['code'] !== 200) {
				return array(
					'success' => false,
					'code' => @$result['code'],
					'message' => @$result['message'],
					'code_message' => @$result['code_message'],
					'post_data' => $args,
				);
			}
			
			return array(
				'success' => true,
				'data' => @$result['data'],
			);			
		}
	}
}