<?php defined('ABSPATH') or die('No script kiddies please!'); 

// css
wp_enqueue_style('ghn-order', plugins_url('ghn-wc/assets/css/order.css'), array(), null, 'all');

// js
wp_enqueue_script('ghn-order', plugins_url('ghn-wc/assets/js/order.js'), array('jquery'), true);

global $post, $ghn, $ghn_order_code, $ghn_order_detail;

$post_id = $post->ID;
$param_wc_order_id = (int) @$_GET['wc_order_id'];

if (@$post->post_parent == 0) {
	if ($param_wc_order_id == 0) {
		wp_redirect(admin_url('edit.php?post_type=shop_order'));
		exit;
	}
} else {
	$param_wc_order_id = @$post->post_parent;
}

$woocommerce_store_address = get_option('woocommerce_store_address');
$ghn_options = $ghn->get_options(); // options

$ghn_stations = $ghn->get_stations(); // stations

$ghn_provinces = $ghn->get_provinces(); // provinces
$count_provinces = count($ghn_provinces);

$ghn_districts = $ghn->get_districts(); // districts
$count_districts = count($ghn_districts);

$ghn_provinces2 = array_column($ghn_provinces, 'ProvinceName', 'ProvinceID');

// order detail
$ghn_order_code = $this->ghn_get_ghn_order_code($post_id);
$ghn_order_detail = $ghn->get_order($ghn_order_code);
update_post_meta($post_id, 'ghn_order_status', @$ghn_order_detail['status']);

$ghn_status_chk = new GHN_Status();
$ghn_status_chk->set_status(@$ghn_order_detail['status']);
$ghn_editable_fields = $ghn_status_chk->get_editable_fields(); ?>
	
	<div class="wrap woocommerce ghn-woocommerce">
		<h3>Bên gửi</h3>
		
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<td scope="row" class="titledesc" style="vertical-align: text-top;">
						<h4 style="margin: 0;margin-bottom:.6em">Thông tin cửa hàng</h4>
						
						<code><?php echo @$ghn_options['ghn_shopname']; ?></code> -
						<code><?php echo @$ghn_options['ghn_shoptel']; ?></code><br/>
						<code><?php echo empty(@$ghn_options['ghn_shopaddress']) ? $woocommerce_store_address : $ghn_options['ghn_shopaddress']; ?></code>
					</td>
				
					<td scope="row" class="titledesc" style="vertical-align: text-top;">
					<?php if (@$ghn_order_detail['status'] != '') { ?>
						<h4 style="margin: 0;margin-bottom:.6em;">Mã đơn hàng</h4>
						
						<code><?php echo @$ghn_order_detail['order_code']; ?></code><br/>
						
						<h4 style="margin: 0;margin-bottom:.6em;margin-top:1em;">Trạng thái đơn hàng</h4>
						
						<code><?php echo $ghn_status_chk->get_status_name(); ?></code><br/>
						
						<h4 style="margin: 0;margin-bottom:.6em;margin-top:1em;">Ngày tạo</h4>
						
						<code><?php echo date('H:i d/m/Y', strtotime(@$ghn_order_detail['created_date'])); ?></code><br/>
					<?php } ?>
					</td>
				</tr>
				
				<tr valign="top">
					<td scope="row" class="titledesc" style="vertical-align: text-top;">	
					<!-- pick_station_id -->
					<?php if ($ghn_status_chk->editable('pick_station_id')) { ?>				
						<label>
							<input type="checkbox" name="form_station" value="<?php echo (int) @$ghn_order_detail['pick_station_id']; ?>" <?php echo (@$ghn_order_detail['pick_station_id'] > 0) ? 'checked' : ''; ?> /> 
							Gửi hàng tại điểm giao nhận GHN
						</label>
					
						<a href="javascript:;" class="station-edit" style="<?php echo (@$ghn_order_detail['pick_station_id'] > 0) ? '' : 'display: none'; ?>;text-decoration: none;" title="Sửa điểm giao nhận GHN">
							<span class="dashicons dashicons-edit"></span>
						</a>
						
						<ul class="ghn-list-items ghn-list-items-station-masked" style="<?php echo (@$ghn_order_detail['pick_station_id'] > 0) ? '' : 'display: none'; ?>">
						<?php if (@$ghn_order_detail['pick_station_id'] > 0) {
							for ($i = 0; $i < count($ghn_stations); $i++) {							
								if ($ghn_stations[$i]->locationId != @$ghn_order_detail['pick_station_id']) continue; ?>
									<li class="ghn-list-item" data-id="<?php echo $ghn_stations[$i]->locationId; ?>">
										<strong style="font-size: 1.2em;"><?php echo $ghn_stations[$i]->locationName; ?></strong
										><br/><?php echo $ghn_stations[$i]->address; ?>
									</li>
								<?php if ($ghn_stations[$i]->locationId == @$ghn_order_detail['pick_station_id']) break;
							}
						} ?>
						</ul>
						
						<?php if (count($ghn_stations) > 0) { ?>
							<ul class="ghn-list-items ghn-list-items-station" style="display: none;">
							<?php foreach ($ghn_stations as $i => $station) { ?>
								<li class="ghn-list-item <?php echo ($ghn_stations[$i]->locationId == $ghn_order_detail['pick_station_id']) ? 'ghn-list-item-active' : ''; ?>" data-id="<?php echo $station->locationId; ?>">
									<strong style="font-size: 1.2em;"><?php echo $station->locationName; ?></strong>
									<a href="javascript:;" class="button" title="Chọn <?php echo esc_attr($station->locationName); ?>" data-name="<?php echo esc_attr($station->locationName); ?>" data-address="<?php echo esc_attr($station->address); ?>" data-id="<?php echo $station->locationId; ?>">
										<span class="dashicons dashicons-location-alt"></span>
									</a>
									<br/><?php echo $station->address; ?>
								</li>
							<?php } ?>
							</ul>
						<?php } else { ?>
							<ul class="ghn-list-items ghn-list-items-station" style="display: none;">
								<li class="ghn-list-item-no">Không tìm thấy điểm giao nhận GHN.</li>
							</ul>
						<?php } ?>
					
					<?php } else { ?>				
						<label>
							<input type="checkbox" name="form_station" value="<?php echo (int) @$ghn_order_detail['pick_station_id']; ?>" <?php echo (@$ghn_order_detail['pick_station_id'] > 0) ? 'checked' : ''; ?> disabled /> 
							Gửi hàng tại điểm giao nhận GHN
						</label>						
						
						<ul class="ghn-list-items ghn-list-items-station-masked" style="">
						<?php if (@$ghn_order_detail['pick_station_id'] > 0) {
							for ($i = 0; $i < count($ghn_stations); $i++) {							
								if ($ghn_stations[$i]->locationId != @$ghn_order_detail['pick_station_id']) continue; ?>
									<li class="ghn-list-item" data-id="<?php echo $ghn_stations[$i]->locationId; ?>">
										<strong style="font-size: 1.2em;"><?php echo $ghn_stations[$i]->locationName; ?></strong
										><br/><?php echo $ghn_stations[$i]->address; ?>
									</li>
								<?php if ($ghn_stations[$i]->locationId == @$ghn_order_detail['pick_station_id']) break;
							}
						} ?>
						</ul>
					<?php } ?>
					<!-- pick_station_id -->
					</td>
					
					
					<td scope="row" class="titledesc" style="vertical-align: text-top;">					
						<label>
							<input type="checkbox" name="form_return" value="1" <?php echo (@$ghn_order_detail['return_district_id'] > 0) ? 'checked' : ''; ?> <?php echo ($ghn_order_code == '') ? '' : 'disabled'; ?> /> Thêm địa chỉ trả hàng chuyển hoàn 
						</label>
						
						<div class="form_return-fields" style="<?php echo (@$ghn_order_detail['return_district_id'] > 0) ? '' : 'display: none'; ?>;">
						<!-- return_phone -->
							<p>
								<label for="return_phone">Điện thoại</label>
							</p>						
							<p>
							<?php if ($ghn_status_chk->editable('return_phone')) { ?>		
								<input class="return-field" name="return_phone" type="text" value="<?php echo esc_attr(@$ghn_order_detail['return_phone']); ?>" placeholder="Thông tin số điện thoại." />
							<?php } else { ?>	
								<input type="text" value="<?php echo esc_attr(@$ghn_order_detail['return_phone']); ?>" readonly="readonly" />
							<?php } ?>
							</p>
						<!-- return_phone -->
						
						<!-- return_address -->
							<p>
								<label for="return_address">Địa chỉ</label>
							</p>
							<p>	
							<?php if ($ghn_status_chk->editable('return_address')) { ?>	
								<input class="return-field" name="return_address" type="text" value="<?php echo esc_attr(@$ghn_order_detail['return_address']); ?>" placeholder="Thông tin địa chỉ." />
							<?php } else { ?>
								<input type="text" value="<?php echo esc_attr(@$ghn_order_detail['return_address']); ?>" readonly="readonly" />
							<?php } ?>
							</p>
						<!-- return_address -->
							
						<!-- return_district_id -->
						<?php $district1 = 0; ?> 
							<p>
								<label for="return_district_id">Quận/Huyện</label>
							</p>
							<p>
							<?php if ($ghn_status_chk->editable('return_district_id')) { ?>	
								<select name="return_district_id" class="return-field wc-enhanced-select ghn-select2 select-ajax-district" style="min-width: 350px;" data-placeholder="Chọn Quận/Huyện" data-targetward="return_ward_code">
								<?php for ($i = 0; $i < $count_districts; $i++) {
									if ($i == 0) $district1 = $ghn_districts[$i]->DistrictID;
							
									$district_name = $ghn_districts[$i]->DistrictName.' - '.@$ghn_provinces2[$ghn_districts[$i]->ProvinceID]; ?>
									<option value="<?php echo $ghn_districts[$i]->DistrictID; ?>" <?php echo (@$ghn_order_detail['return_district_id'] == $ghn_districts[$i]->DistrictID) ? 'selected' : ''; ?>>
									<?php echo $district_name; ?>
									</option>
								<?php } ?>
								</select>
							<?php } else {
								for ($i = 0; $i < $count_districts; $i++) { 
									if (@$ghn_order_detail['return_district_id'] == $ghn_districts[$i]->DistrictID) {
										$return_district_name = $ghn_districts[$i]->DistrictName.' - '.@$ghn_provinces2[$ghn_districts[$i]->ProvinceID];
										break;
									}
								} ?>
								<input type="text" value="<?php echo esc_attr(@$return_district_name); ?>" readonly="readonly" />
							<?php } ?>
							</p>
						<!-- return_district_id -->
							
						<!-- return_ward_code -->
							<p>
								<label for="return_ward_code">Phường/Xã</label>
							</p>	
							<p>
							<?php $district1 = (@$ghn_order_detail['return_district_id'] == 0) ? $district1 : @$ghn_order_detail['return_district_id'];
							$ghn_return_wards = $ghn->get_wards($district1); 
							$count_return_wards = count($ghn_return_wards);
							
							if ($ghn_status_chk->editable('return_ward_code')) { ?>
								<select name="return_ward_code" class="return-field wc-enhanced-select ghn-select2" style="min-width: 350px;" data-placeholder="Chọn Phường/Xã">
								<?php for ($i = 0; $i < $count_return_wards; $i++) { ?>
									<option value="<?php echo $ghn_return_wards[$i]->WardCode; ?>" <?php echo (@$ghn_order_detail['return_ward_code'] == $ghn_return_wards[$i]->WardCode) ? 'selected' : ''; ?>>
									<?php echo $ghn_return_wards[$i]->WardName; ?>
									</option>
								<?php } ?>
								</select>
							<?php } else {
								for ($i = 0; $i < $count_return_wards; $i++) {
									if (@$ghn_order_detail['return_ward_code'] == $ghn_return_wards[$i]->WardCode) {
										$return_ward_name = $ghn_return_wards[$i]->WardName;
										break;
									}
								} ?>	
								<input type="text" value="<?php echo esc_attr(@$return_ward_name); ?>" readonly="readonly" />
							<?php } ?>
							</p>	
						<!-- return_ward_code -->
						</div>
					</td>
				</tr>
			</tbody>
		</table>
				
		<hr/>
		<h3>Bên nhận</h3>
		
		<table class="form-table">
		<?php $_billing_first_name = get_post_meta($param_wc_order_id, '_billing_first_name', true);
		$_billing_last_name = get_post_meta($param_wc_order_id, '_billing_last_name', true);
		$_billing_phone = get_post_meta($param_wc_order_id, '_billing_phone', true);
		$_billing_address_1 = get_post_meta($param_wc_order_id, '_billing_address_1', true); ?>
			<tbody>				
				<tr valign="top">
					<td scope="row" class="titledesc" style="vertical-align: text-top;">
					<!-- to_phone -->
						<p>
							<label for="to_phone">Điện thoại</label>
						</p>
						<p>
						<?php if ($ghn_status_chk->editable('to_phone')) { ?>	
							<input name="to_phone" type="text" value="<?php echo (@$ghn_order_detail['to_phone'] == '') ? esc_attr($_billing_phone) : esc_attr(@$ghn_order_detail['to_phone']); ?>" placeholder="Thông tin số điện thoại." required />
						<?php } else { ?>
							<input type="text" value="<?php echo esc_attr(@$ghn_order_detail['to_phone']); ?>" readonly="readonly" />
						<?php } ?>
						</p>
					<!-- to_phone -->
						
					<!-- to_name -->
						<p>
							<label for="to_name">Họ tên</label>
						</p>
						<p>
						<?php if ($ghn_status_chk->editable('to_name')) { ?>	
							<input name="to_name" type="text" value="<?php echo (@$ghn_order_detail['to_name'] == '') ? esc_attr($_billing_last_name.' '.$_billing_first_name) : esc_attr(@$ghn_order_detail['to_name']); ?>" placeholder="Thông tin họ tên bên nhận." required />
						<?php } else { ?>	
							<input type="text" value="<?php echo esc_attr(@$ghn_order_detail['to_name']); ?>" readonly="readonly" />
						<?php } ?>
						</p>
					<!-- to_name -->
					</td>
					<td scope="row" class="titledesc" style="vertical-align: text-top;">
					<!-- to_address -->
						<p>
							<label for="to_address">Địa chỉ</label>
						</p>					
						<p>	
						<?php if ($ghn_status_chk->editable('to_address')) { ?>	
							<input name="to_address" type="text" value="<?php echo (@$ghn_order_detail['to_address'] == '') ? $_billing_address_1 : esc_attr(@$ghn_order_detail['to_address']); ?>" placeholder="Thông tin địa chỉ." required />
						<?php } else { ?>	
							<input type="text" value="<?php echo esc_attr(@$ghn_order_detail['to_address']); ?>" readonly="readonly" />
						<?php } ?>
						</p>
					<!-- to_address -->
						
					<!-- to_district_id -->
					<?php $district2 = 0; ?> 
						<p>
							<label for="to_district_id">Quận/Huyện</label>
						</p>							
						<p>
						<?php if ($ghn_status_chk->editable('to_district_id')) { ?>	
							<select name="to_district_id" class="wc-enhanced-select ghn-select2 select-ajax-district" style="min-width: 350px;" data-placeholder="Chọn Quận/Huyện" data-targetward="to_ward_code">
							<?php for ($i = 0; $i < $count_districts; $i++) {
								if ($i == 0) $district2 = $ghn_districts[$i]->DistrictID;
									
								$district_name = $ghn_districts[$i]->DistrictName.' - '.@$ghn_provinces2[$ghn_districts[$i]->ProvinceID]; ?>
								<option value="<?php echo $ghn_districts[$i]->DistrictID; ?>" <?php echo (@$ghn_order_detail['to_district_id'] == $ghn_districts[$i]->DistrictID) ? 'selected' : ''; ?>>
								<?php echo $district_name; ?>
								</option>
							<?php } ?>
							</select>
						<?php } else {
							for ($i = 0; $i < $count_districts; $i++) { 
								if (@$ghn_order_detail['to_district_id'] == $ghn_districts[$i]->DistrictID) {
									$to_district_name = $ghn_districts[$i]->DistrictName.' - '.@$ghn_provinces2[$ghn_districts[$i]->ProvinceID];
									break;
								}
							} ?>	
							<input name="to_district_id" type="hidden" value="<?php echo (int) @$ghn_order_detail['to_district_id']; ?>" />
							<input type="text" value="<?php echo esc_attr(@$to_district_name); ?>" readonly="readonly" />
						<?php } ?>
						</p>
					<!-- to_district_id -->
						
					<!-- to_ward_code -->
						<p>
							<label for="to_ward_code">Phường/Xã</label>
						</p>						
						<p>
						<?php $district2 = (@$ghn_order_detail['to_district_id'] == 0) ? $district2 : @$ghn_order_detail['to_district_id'];
						$ghn_to_wards = $ghn->get_wards($district2); 
						$count_to_wards = count($ghn_to_wards);
						
						if ($ghn_status_chk->editable('to_ward_code')) { ?>	
							<select name="to_ward_code" class="wc-enhanced-select ghn-select2" style="min-width: 350px;" data-placeholder="Chọn Phường/Xã">
							<?php for ($i = 0; $i < $count_to_wards; $i++) { ?>
								<option value="<?php echo $ghn_to_wards[$i]->WardCode; ?>" <?php echo (@$ghn_order_detail['to_ward_code'] == $ghn_to_wards[$i]->WardCode) ? 'selected' : ''; ?>>
								<?php echo $ghn_to_wards[$i]->WardName; ?>
								</option>
							<?php } ?>
							</select>
						<?php } else {
							for ($i = 0; $i < $count_to_wards; $i++) {
								if (@$ghn_order_detail['to_ward_code'] == $ghn_to_wards[$i]->WardCode) {
									$to_ward_name = $ghn_to_wards[$i]->WardName;
									break;
								}
							} ?>
							<input name="to_ward_code" type="hidden" value="<?php echo @$ghn_order_detail['to_ward_code']; ?>" />
							<input type="text" value="<?php echo esc_attr(@$to_ward_name); ?>" readonly="readonly" />	
						<?php } ?>
						</p>
					<!-- to_ward_code -->
					</td>
				</tr>
			</tbody>
		</table>
		
		<hr/>
		<h3>Hàng hoá</h3>
		
		<table class="form-table">
			<tbody>				
				<tr valign="top">
					<td scope="row" class="titledesc" style="vertical-align: text-top;">
					<!-- weight -->
						<p>
							<label for="weight">Khối lượng (gram)</label>
						</p>					
						<p>	
						<?php if ($ghn_status_chk->editable('weight')) { ?>	
							<input name="weight" type="number" min="0" value="<?php echo (int) @$ghn_order_detail['weight']; ?>" placeholder="" required />
						<?php } else { ?>	
							<input name="weight" type="hidden" value="<?php echo (int) @$ghn_order_detail['weight']; ?>" />
							<input type="number" value="<?php echo esc_attr(@$ghn_order_detail['weight']); ?>" readonly="readonly" />
						<?php } ?>
						</p>
					<!-- weight -->
					</td>
					<td scope="row" class="titledesc" style="vertical-align: text-top;">
						<p>
							<label for="">Kích thước (cm)</label>
						</p>						
						<p>
						<!-- length -->
						<?php if ($ghn_status_chk->editable('length')) { ?>
							<input name="length" type="number" min="0" value="<?php echo (int) @$ghn_order_detail['length']; ?>" style="width: 23%;margin-right:2%;" required />
						<?php } else { ?>		
							<input name="weight" type="hidden" value="<?php echo (int) @$ghn_order_detail['length']; ?>" />
							<input type="number" value="<?php echo esc_attr(@$ghn_order_detail['length']); ?>" readonly="readonly" style="width: 23%;margin-right:2%;" />
						<?php } ?>
						<!-- length -->
						
						<!-- width -->
						<?php if ($ghn_status_chk->editable('width')) { ?>
							<input name="width" type="number" min="0" value="<?php echo (int) @$ghn_order_detail['width']; ?>" style="width: 23%;margin-right:2%;" required />
						<?php } else { ?>	
							<input name="weight" type="hidden" value="<?php echo (int) @$ghn_order_detail['width']; ?>" />	
							<input type="number" value="<?php echo esc_attr(@$ghn_order_detail['width']); ?>" readonly="readonly" style="width: 23%;margin-right:2%;" />
						<?php } ?>
						<!-- width -->
						
						<!-- height -->
						<?php if ($ghn_status_chk->editable('height')) { ?>
							<input name="height" type="number" min="0" value="<?php echo (int) @$ghn_order_detail['height']; ?>" style="width: 23%;" required />
						<?php } else { ?>		
							<input name="weight" type="hidden" value="<?php echo (int) @$ghn_order_detail['height']; ?>" />
							<input type="number" value="<?php echo esc_attr(@$ghn_order_detail['height']); ?>" readonly="readonly" style="width: 23%;" />
						<?php } ?>
						<!-- height -->
						
							<br/><br/>
							<small>Khối lượng quy đổi (kg): 
								<strong class="drc"><?php echo ((int) @$ghn_order_detail['converted_weight'] > 0) ? ($ghn_order_detail['converted_weight'] / 1000) : ''; ?></strong>
							</small>
						</p>
					</td>
				</tr>		
				<tr valign="top">
					<td scope="row" class="titledesc" style="vertical-align: text-top;">
					<!-- cod_amount -->
						<p>
							<label for="cod_amount">Thu hộ tiền COD</label>
						</p>			
						<p>		
						<?php if ($ghn_status_chk->editable('cod_amount')) { ?>		
							<input name="cod_amount" type="number" min="0" value="<?php echo (int) @$ghn_order_detail['cod_amount']; ?>" />
						<?php } else { ?>	
							<input name="cod_amount" type="hidden" value="<?php echo (int) @$ghn_order_detail['cod_amount']; ?>" />
							<input type="number" value="<?php echo esc_attr(@$ghn_order_detail['cod_amount']); ?>" readonly="readonly" />
						<?php } ?>
						</p>
					<!-- cod_amount -->
					</td>
					<td scope="row" class="titledesc" style="vertical-align: text-top;">
					<!-- insurance_value -->
						<p>
							<label for="insurance_value">Giá trị hàng hoá</label>
						</p>						
						<p>	
						<?php if ($ghn_status_chk->editable('insurance_value')) { ?>		
							<input name="insurance_value" type="number" min="0" value="<?php echo (int) @$ghn_order_detail['insurance_value']; ?>" required />
						<?php } else { ?>
							<input name="insurance_value" type="hidden" value="<?php echo (int) @$ghn_order_detail['insurance_value']; ?>" />
							<input type="number" value="<?php echo esc_attr(@$ghn_order_detail['insurance_value']); ?>" readonly="readonly" />
						<?php } ?>
							
							<br/><small><a href="https://ghn.vn/pages/quy-dinh-ve-khieu-nai-cua-ghn" target="_blank">Qui trình</a> & <a href="https://ghn.vn/pages/chinh-sach-boi-thuong-cua-ghn" target="_blank">Chính sách xử lý đền bù</a></small>
						</p>
					<!-- insurance_value -->
					</td>
				</tr>
			</tbody>
		</table>
				
		<hr/>
		<h3>Gói cước <span class="feeweight"></span></h3>
		
		<!-- service_id -->
		<ul class="ghn-list-items ghn-list-items-fee">
		<?php if (@$ghn_order_detail['service_id'] > 0) {
			$servicefees = $this->ghn_get_servicefees(
				$ghn, 
				$ghn_options,
				array(
					'to_district_id' => @$ghn_order_detail['to_district_id'],
					'to_ward_code' => @$ghn_order_detail['to_ward_code'],
					'height' => @$ghn_order_detail['height'],
					'length' => @$ghn_order_detail['length'],
					'weight' => @$ghn_order_detail['weight'],
					'width' => @$ghn_order_detail['width'],
					'insurance_fee' => @$ghn_order_detail['insurance_value'],
					'coupon' => @$ghn_order_detail['coupon'],
				)
			);
			
			for ($i = 0; $i < count($servicefees); $i++) { ?>
				<li class="ghn-list-item" data-service_id="<?php echo @$servicefees[$i]['service_id']; ?>" data-service_type_id="<?php echo @$servicefees[$i]['service_type_id']; ?>" data-total="<?php echo @$servicefees[$i]['data_fee']->total; ?>" data-service_fee="<?php echo @$servicefees[$i]['data_fee']->service_fee; ?>">
					<strong style="font-size: 1.2em;">
						<label>
						<?php if ($ghn_status_chk->editable('service_id')) { ?>	
							<input type="radio" name="form_service" value="<?php echo @$servicefees[$i]['service_id']; ?>" <?php echo (@$ghn_order_detail['service_id'] == @$servicefees[$i]['service_id']) ? 'checked' : ''; ?> /> 
						<?php } else { ?>
							<input type="radio" name="form_service" value="<?php echo @$servicefees[$i]['service_id']; ?>" <?php echo (@$ghn_order_detail['service_id'] == @$servicefees[$i]['service_id']) ? 'checked' : ''; ?> disabled /> 
						<?php } ?>
							
							<?php echo @$servicefees[$i]['short_name']; ?>
							<br/> <?php echo @$servicefees[$i]['data_fee']->service_fee; ?> VNĐ
						</label>
					</strong>
				</li>
			<?php }
		} ?>
		</ul>
		<!-- service_id -->
				
		<hr/>
		<h3>Lưu ý - Ghi chú</h3>
		
		<table class="form-table">
			<tbody>				
				<tr valign="top">
					<td scope="row" class="titledesc" style="vertical-align: text-top;">
					<!-- required_note -->
						<p>
							<label for="required_note">Lưu ý giao hàng</label>
						</p>						
						<p>
						<?php if ($ghn_status_chk->editable('required_note')) { ?>	
							<select name="required_note" class="wc-enhanced-select" style="min-width: 350px;">
								<option value="KHONGCHOXEMHANG" <?php echo (@$ghn_order_detail['required_note'] == 'KHONGCHOXEMHANG') ? 'selected' : ''; ?>>Không cho xem hàng</option>
								<option value="CHOXEMHANGKHONGTHU" <?php echo (@$ghn_order_detail['required_note'] == 'CHOXEMHANGKHONGTHU') ? 'selected' : ''; ?>>Cho xem hàng không cho thử</option>
								<option value="CHOTHUHANG" <?php echo (@$ghn_order_detail['required_note'] == 'CHOTHUHANG') ? 'selected' : ''; ?>>Cho thử hàng</option>
							</select>
						<?php } else { 
							switch (@$ghn_order_detail['required_note']) {
								case 'KHONGCHOXEMHANG':
									$required_note = 'Không cho xem hàng';
									break;
									
								case 'CHOXEMHANGKHONGTHU':
									$required_note = 'Cho xem hàng không cho thử';
									break;
									
								case 'CHOTHUHANG':
									$required_note = 'Cho thử hàng';
									break;
							} ?>	
							<input type="text" value="<?php echo esc_attr(@$required_note); ?>" readonly="readonly" />
						<?php } ?>
						</p>
					<!-- required_note -->
						<p>
							<label for="required_note">Mã đơn khách hàng</label>
						</p>
						<p>
							<input type="hidden" name="post_id" value="<?php echo $post_id; ?>" />
							<input type="hidden" name="client_order_code" value="<?php echo $param_wc_order_id; ?>" />
							
						<?php $client_order_code_custom = (@$ghn_order_detail['client_order_code']) ? $ghn_order_detail['client_order_code'] : $param_wc_order_id;
						
						if ($ghn_status_chk->editable('client_order_code')) { ?>	
							<input type="text" name="client_order_code_custom" value="<?php echo $client_order_code_custom; ?>" />
						<?php } else { ?>
							<input type="text" value="<?php echo esc_attr(@$client_order_code_custom); ?>" readonly="readonly" />
						<?php } ?>
						</p>
					</td>
					<td scope="row" class="titledesc" style="vertical-align: text-top;">
					<!-- note -->
						<p>
							<label for="note">Ghi chú</label>
						</p>						
						<p>
						<?php if ($ghn_status_chk->editable('note')) { ?>	
							<textarea name="note" placeholder="Nhập ghi chú"><?php echo esc_attr(@$ghn_order_detail['note']); ?></textarea>
						<?php } else { ?>
							<textarea readonly="readonly"><?php echo esc_attr(@$ghn_order_detail['note']); ?></textarea>
						<?php } ?>
						</p>
					<!-- note -->
					</td>
				</tr>
			</tbody>
		</table>
		
		<hr/>
	
	<?php if ($post->post_status != 'publish') { ?>
		<a href="<?php echo admin_url('edit.php?post_type=shop_order'); ?>" class="button">Trở lại danh sách đơn hàng</a>
		<a href="javascript:;" class="button button-primary" id="ghn-create">Tạo đơn hàng</a>
	<?php } else { ?>
		<a href="<?php echo admin_url('edit.php?post_type=shop_order'); ?>" class="button">Trở lại đơn hàng #<?php echo $param_wc_order_id; ?></a>
				
		<?php if ($ghn_status_chk->is_cancelable()) { ?>	
			<a href="javascript:;" class="button" id="ghn-cancel" style="border-color: #f26522;color: #f26522;margin: 0 5px;" data-id="<?php echo $post_id; ?>">Huỷ đơn hàng</a>
		<?php } ?>
				
		<?php if ($ghn_status_chk->is_returnable()) { ?>	
			<a href="javascript:;" class="button" id="ghn-return" style="border-color: #f26522;color: #f26522;margin: 0 5px;" data-id="<?php echo $post_id; ?>">Huỷ giao và chuyển hoàn</a>
		<?php } ?>
				
		<?php if ($ghn_status_chk->is_deliverable()) { ?>	
			<a href="javascript:;" class="button" id="ghn-delivery-again" style="border-color: #f26522;color: #f26522;margin: 0 5px;" data-id="<?php echo $post_id; ?>">Giao lại</a>
		<?php } ?>
		
		<?php if (count($ghn_editable_fields) > 0) { ?>
			<a href="javascript:;" class="button button-primary" id="ghn-update">Sửa đơn hàng</a>
		<?php } ?>
		
		<a href="javascript:;" class="button" id="ghn-print" style="float: right;" data-id="<?php echo $post_id; ?>">In đơn hàng</a>
	<?php } ?>
	</div>
	
	<script>
	jQuery(document).ready(function($) {
		if ($('[name=coupon]').length) 
			$('[name=coupon]').val(<?php echo '"'.@$ghn_order_detail['coupon'].'"'; ?>);
		
		if ($('#view_coupon').length) 
			$('#view_coupon').val(<?php echo '"'.@$ghn_order_detail['coupon'].'"'; ?>);
		
		if ($('[name=payment_type_id]').length) 
			$('[name=payment_type_id]').val(<?php echo '"'.@$ghn_order_detail['payment_type_id'].'"'; ?>);
		
		if ($('#view_payment_type_id').length) 
			$('#view_payment_type_id').val(<?php echo '"'.((@$ghn_order_detail['payment_type_id'] == 1) ? 'Bên gửi trả phí' : 'Bên nhận trả phí').'"'; ?>);
	});
	</script>