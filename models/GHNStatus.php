<?php defined('ABSPATH') or die('No script kiddies please!');


if (!class_exists('GHN_Status')) {
	class GHN_Status {
		/**
		* Class Construct
		*/
		public function __construct() {	
			$this->order_status = '';
			
			$this->editable_status = array(
				'ready_to_pick' => array(
					'from_name',
					'from_phone',
					'from_address',
					'from_district_id',
					'from_ward_code',
					'pick_station_id',
					'payment_type_id',
					'insurance_value',
					'order_value',
					'service_type_id',
					'service_id',
					'to_name',
					'to_phone',
					'to_address',
					'to_district_id',
					'to_ward_code',
					'weight',
					'length',
					'width',
					'height',
					'converted_weight',
					'content',
					'required_note',
					'note',
					'cod_amount',
					'return_name',
					'return_phone',
					'return_address',
					'return_district_id',
					'return_ward_code',
					'coupon',
				),
				'picking' => array(
					'payment_type_id',
					'insurance_value',
					'order_value',
					'service_type_id',
					'service_id',
					'to_name',
					'to_phone',
					'to_address',
					'to_district_id',
					'to_ward_code',
					'weight',
					'length',
					'width',
					'height',
					'converted_weight',
					'content',
					'required_note',
					'note',
					'cod_amount',
					'return_name',
					'return_phone',
					'return_address',
					'return_district_id',
					'return_ward_code',
					'coupon',
				),
				'money_collect_picking' => array(
					'order_value',
					'to_name',
					'to_phone',
					'content',
					'required_note',
					'note',
					'cod_amount',
					'return_name',
					'return_phone',
					'return_address',
					'return_district_id',
					'return_ward_code',
					'coupon',
				),
				'picked' => array(
					'service_type_id',
					'service_id',
					'to_name',
					'to_phone',
					'to_address',
					'to_district_id',
					'to_ward_code',
					'weight',
					'length',
					'width',
					'height',
					'converted_weight',
					'content',
					'required_note',
					'note',
					'cod_amount',
					'return_name',
					'return_phone',
					'return_address',
					'return_district_id',
					'return_ward_code',
					'coupon',
				),
				'cancel' => array(''),
				'storing' => array(
					'service_type_id',
					'service_id',
					'to_name',
					'to_phone',
					'to_address',
					'to_district_id',
					'to_ward_code',
					'weight',
					'length',
					'width',
					'height',
					'converted_weight',
					'content',
					'required_note',
					'note',
					'cod_amount',
					'return_name',
					'return_phone',
					'return_address',
					'return_district_id',
					'return_ward_code',
					'coupon',
				),
				'transporting' => array(
					'service_type_id',
					'service_id',
					'to_name',
					'to_phone',
					'to_address',
					'to_district_id',
					'to_ward_code',
					'weight',
					'length',
					'width',
					'height',
					'converted_weight',
					'content',
					'required_note',
					'note',
					'cod_amount',
					'return_name',
					'return_phone',
					'return_address',
					'return_district_id',
					'return_ward_code',
					'coupon',
				),
				'sorting' => array(
					'service_type_id',
					'service_id',
					'to_name',
					'to_phone',
					'to_address',
					'to_district_id',
					'to_ward_code',
					'weight',
					'length',
					'width',
					'height',
					'converted_weight',
					'content',
					'required_note',
					'note',
					'cod_amount',
					'return_name',
					'return_phone',
					'return_address',
					'return_district_id',
					'return_ward_code',
					'coupon',
				),
				'delivering' => array(
					'service_type_id',
					'service_id',
					'to_name',
					'to_phone',
					'to_address',
					'to_district_id',
					'to_ward_code',
					'cod_amount',
					'required_note',
					'return_name',
					'return_phone',
					'return_address',
					'return_district_id',
					'return_ward_code',
				),
				'money_collect_delivering' => array(''),
				'delivered' => array(''),
				'delivery_fail' => array(
					'service_type_id',
					'service_id',
					'to_name',
					'to_phone',
					'to_address',
					'to_district_id',
					'to_ward_code',
					'weight',
					'length',
					'width',
					'height',
					'converted_weight',
					'content',
					'required_note',
					'note',
					'cod_amount',
					'return_name',
					'return_phone',
					'return_address',
					'return_district_id',
					'return_ward_code',
				),
				'waiting_to_return' => array(
					'service_type_id',
					'service_id',
					'to_name',
					'to_phone',
					'to_address',
					'to_district_id',
					'to_ward_code',
					'weight',
					'length',
					'width',
					'height',
					'converted_weight',
					'content',
					'required_note',
					'note',
					'cod_amount',
					'return_name',
					'return_phone',
					'return_address',
					'return_district_id',
					'return_ward_code',
				),
				'return' => array(
					'return_name',
					'return_phone',
				),
				'return_transporting' => array(
					'return_name',
					'return_phone',
				),
				'return_sorting' => array(
					'return_name',
					'return_phone',
				),
				'returning' => array(''),
				'return_fail' => array(
					'return_name',
					'return_phone',
					'return_address',
					'return_district_id',
					'return_ward_code',
				),
				'returned' => array(''),
				'exception' => array(''),
				'lost' => array(''),
				'damage' => array(''),
			);
			
			$this->cancelable = array(
				'money_collect_picking',
				'ready_to_pick',
				'picking',
			);
			
			$this->returnable = array(
				'picked',
				'return_sorting',
				'return_transporting',
				'storing',
				'waiting_to_return',
			);			
			
			$this->deliverable = array(
				'waiting_to_return',
			); // giao lại
			
			$this->status = array(
				'' => 'Không xác định',
				'ready_to_pick' => 'Đơn hàng mới tạo',
				'picking' => 'Đang lấy hàng',
				'money_collect_picking' => 'Đang thu tiền người gửi',
				'picked' => 'Lấy hàng thành công',
				'cancel' => 'Hủy đơn hàng',
				'storing' => 'Lưu kho',
				'transporting' => 'Đang luân chuyển kho',
				'sorting' => 'Đang được phân loại',
				'delivering' => 'Đang giao hàng',
				'money_collect_delivering' => 'Đang thu tiền người nhận',
				'delivered' => 'Giao thành công',
				'delivery_fail' => 'Giao hàng thất bại',
				'waiting_to_return' => 'Đang chờ trả hàng',
				'return' => 'Trả hàng',
				'return_transporting' => 'Luân chuyển kho trả',
				'return_sorting' => 'Phân loại hàng trả',
				'returning' => 'Đang trả hàng',
				'return_fail' => 'Trả hàng thất bại',
				'returned' => 'Trả hàng thành công',
				'exception' => 'Hàng ngoại lệ',
				'lost' => 'Hàng bị mất',
				'damage' => 'Hàng bị vỡ hoặc hư hỏng',
			);
		}
		
		public function set_status($order_status = '') {
			$this->order_status = $order_status;
		}
		
		public function get_status() {
			return $this->order_status;
		}
		
		public function get_status_name($order_status = '') {
			if (empty($this->order_status)) $this->order_status = $order_status;
				
			if ($this->order_status == '') return $this->order_status;
			
			if (!isset($this->status[$this->order_status])) return $this->order_status;
			
			$status_name = $this->status[$this->order_status];
			
			return (!empty($status_name)) ? $status_name : '';
		}
		
		public function get_editable_fields($order_status = '') {
			if (empty($this->order_status)) $this->order_status = $order_status;
			
			if ($this->order_status == '') return array();
			
			if (!isset($this->editable_status[$this->order_status])) return array();
			
			return $this->editable_status[$this->order_status];
		}
		
		public function editable($field_name = '', $order_status = '') {
			if (empty($this->order_status)) $this->order_status = $order_status;
			
			if ($this->order_status == '' || $field_name == '') return true;
			
			if (!isset($this->editable_status[$this->order_status])) return false;
			
			$editable_status = $this->editable_status[$this->order_status];
			
			return (in_array($field_name, $editable_status));
		}
		
		public function is_cancelable($order_status = '') {
			if (empty($this->order_status)) $this->order_status = $order_status;
			
			if ($this->order_status == '') return false;
			
			return in_array($this->order_status, $this->cancelable);
		}
		
		public function is_returnable($order_status = '') {
			if (empty($this->order_status)) $this->order_status = $order_status;
			
			if ($this->order_status == '') return false;
			
			return in_array($this->order_status, $this->returnable);
		}
		
		public function is_deliverable($order_status = '') {
			if (empty($this->order_status)) $this->order_status = $order_status;
			
			if ($this->order_status == '') return false;
			
			return in_array($this->order_status, $this->deliverable);
		}
	}
}