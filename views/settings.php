<?php defined('ABSPATH') or die('No script kiddies please!'); 

// css
wp_enqueue_style('ghn-order', plugins_url('ghn-wc/assets/css/settings.css'), array(), null, 'all');

// js
wp_enqueue_script('ghn-order', plugins_url('ghn-wc/assets/js/settings.js'), array('jquery'), true);

global $ghn;

$woocommerce_store_address = get_option('woocommerce_store_address');

$ghn_options = $ghn->get_options(); // options

$ghn_provinces = $ghn->get_provinces(); // provinces
$count_provinces = count($ghn_provinces);

$ghn_districts = $ghn->get_districts(); // districts
$count_districts = count($ghn_districts);

$ghn_provinces2 = array_column($ghn_provinces, 'ProvinceName', 'ProvinceID');

$ghn_wards = $ghn->get_wards(@$ghn_options['ghn_shopdistrict']); // wards
$count_wards = count($ghn_wards); ?>

	<form class="wrap woocommerce ghn-woocommerce" method="POST">
		<h2>Tuỳ chỉnh Plugin GHN</h2>
		
		<h3>Thông tin API</h3>
		
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="ghn_token">Token API <a href="javascript:;" class="woocommerce-help-tip" title="Thông tin Token API của GHN"></a></label>
					</th>
					<td class="forminp forminp-text">
						<input name="ghn_token" type="text" value="<?php echo esc_attr(@$ghn_options['ghn_token']); ?>" placeholder="Chuỗi token API của GHN." required />
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="ghn_env">Môi trường API</label>
					</th>
					<td class="forminp forminp-text">
						<select name="ghn_env" style="min-width: 350px;" class="wc-enhanced-select">
							<option value="0" <?php echo ((int) @$ghn_options['ghn_env'] == 0) ? 'selected' : ''; ?>>Test</option>
							<option value="1" <?php echo ((int) @$ghn_options['ghn_env'] == 1) ? 'selected' : ''; ?>>Production</option>
						</select>
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="ghn_env">URL Webhook</label>
					</th>
					<td class="forminp forminp-text">
						<input type="text" value="<?php echo $this->ghn_get_hook_url(); ?>" readonly="readonly" />
					</td>
				</tr>
			</tbody>
		</table>
		
	<?php if (@$ghn_options['ghn_token'] != '') { ?>
		<h3>Thông tin cửa hàng</h3>
		
		<p><a href="javascript:;" class="button" onclick="return jQuery('#choose-form').toggle();">Chọn cửa hàng có sẵn</a> HOẶC <a href="javascript:;" class="button" onclick="return jQuery('#register-form').toggle();">Tạo cửa hàng mới</a></p>
		
		<table id="register-form" class="form-table" style="max-width: 540px;border: 1px solid #c2c2c2;display: none;">
			<tbody>				
				<tr valign="top">
					<td scope="row" class="titledesc" style="vertical-align: text-top;">
						<h4>Tạo cửa hàng mới</h4>
		
						<p class="register-name">
							<label for="new_name">Tên cửa hàng</label>
						</p>
						<p class="register-name">
							<input name="new_name" type="text" value="" placeholder="Tên cửa hàng mới." />
						</p>
						
						<p class="register-address">
							<label for="new_address">Địa chỉ</label>
						</p>
						<p class="register-address">
							<input name="new_address" type="text" value="" placeholder="Địa chỉ cửa hàng mới." />
						</p>
						
						<p class="register-tel">
							<label for="new_tel">Điện thoại</label>
						</p>
						<p class="register-tel">
							<input name="new_tel" type="text" value="" placeholder="Điện thoại cửa hàng mới." />
						</p>
											
						<p class="register-district">
							<label for="new_district">Quận/Huyện</label>
						</p>
						<p class="register-district">
						<?php $district1 = 0; ?>
							<select name="new_district" style="min-width: 350px;" class="wc-enhanced-select ghn-select2 select-ajax-district" data-placeholder="Chọn Quận/Huyện" data-targetward="new_ward">	
							<?php for($i = 0; $i < $count_districts; $i++) {
								if ($i == 0) $district1 = $ghn_districts[$i]->DistrictID;
								
								$district_name = $ghn_districts[$i]->DistrictName.' - '.@$ghn_provinces2[$ghn_districts[$i]->ProvinceID]; ?>
								<option value="<?php echo $ghn_districts[$i]->DistrictID; ?>"><?php echo $district_name; ?></option>
							<?php } ?>
							</select>
						</p>
						
						<p class="register-ward">
							<label for="new_ward">Phường/Xã</label>
						</p>
						<p class="register-ward">
						<?php $ghn_wards1 = $ghn->get_wards($district1); // wards
						$count_wards1 = count($ghn_wards1); ?>
							<select name="new_ward" style="min-width: 350px;" class="wc-enhanced-select ghn-select2" data-placeholder="Chọn Phường/Xã">
							<?php for($i = 0; $i < $count_wards1; $i++) { ?>
								<option value="<?php echo $ghn_wards1[$i]->WardCode; ?>"><?php echo $ghn_wards1[$i]->WardName; ?></option>
							<?php } ?>
							</select>
						</p>
						
						<p class="register-action">
							<a href="javascript:;" class="button" style="margin: 10px 0;" onclick="return jQuery('#register-form').hide();">Bỏ qua</a>
							<a href="javascript:;" id="register-submit" class="button button-primary" style="margin: 10px 0;">Tạo mới</a>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		
		<table id="choose-form" class="form-table" style="display: none;">
			<tbody>				
				<tr valign="top">
					<td scope="row" class="titledesc" style="vertical-align: text-top;">
						<h4>Danh sách cửa hàng</h4>
						
						<input type="hidden" name="ghn_shopname" value="<?php echo esc_attr(@$ghn_options['ghn_shopname']); ?>" />
						<input type="hidden" name="ghn_shoptel" value="<?php echo esc_attr(@$ghn_options['ghn_shoptel']); ?>" />
						<input type="hidden" name="ghn_shopaddress" value="<?php echo esc_attr(@$ghn_options['ghn_shopaddress']); ?>" />
						<input type="hidden" name="ghn_shopdistrict" value="<?php echo esc_attr(@$ghn_options['ghn_shopdistrict']); ?>" />
						<input type="hidden" name="ghn_shopward" value="<?php echo esc_attr(@$ghn_options['ghn_shopward']); ?>" />
						
					<?php $stores_data = $ghn->get_shop(); 
					$stores = (isset($stores_data['data'])) ? $stores_data['data'] : array();
					$website_store = array();
					
					if (count($stores) > 0) { ?>
						<ul class="ghn-list-stores">
						<?php foreach ($stores as $store) { 
							$store_district_name = '';
							if (@$store['_id'] == 0) continue;
							
							for($i = 0; $i < $count_districts; $i++) {	
								$district_name = $ghn_districts[$i]->DistrictName.' - '.@$ghn_provinces2[$ghn_districts[$i]->ProvinceID];
								
								if ($store['district_id'] == $ghn_districts[$i]->DistrictID) {
									$store_district_name = $district_name;
									break;
								}
							}
							
							if ((int) @$ghn_options['ghn_shopid'] == $store['_id']) {
								$website_store = $store;
								$website_store['store_district_name'] = $store_district_name;
							} ?>
							<li class="ghn-list-store-item <?php echo ((int) @$ghn_options['ghn_shopid'] == $store['_id']) ? 'ghn-list-store-item-active' : ''; ?>" data-id="<?php echo esc_attr($store['_id']); ?>" data-address="<?php echo esc_attr($store['address']); ?>" data-tel="<?php echo esc_attr($store['phone']); ?>" data-name="<?php echo esc_attr($store['name']); ?>" data-district_id="<?php echo esc_attr($store['district_id']); ?>" data-district_name="<?php echo esc_attr($store_district_name); ?>" data-ward_code="<?php echo esc_attr($store['ward_code']); ?>">
								<label>
									<input type="radio" name="ghn_shopid" value="<?php echo esc_attr($store['_id']); ?>" <?php echo ((int) @$ghn_options['ghn_shopid'] == $store['_id']) ? 'checked' : ''; ?> />
									<strong><?php echo $store['name']; ?></strong>	
									<br/><small><strong>Địa chỉ:</strong> <?php echo $store['address']; ?></small>
									<br/><small><strong>Điện thoại:</strong> <?php echo $store['phone']; ?></small>
									<br/><small><?php echo $store_district_name; ?></small>							
								</label>
							</li>
						<?php } ?>
						</ul>
					<?php } ?>
					</td>
				</tr>
			</tbody>
		</table>
				
		<table id="makeup-form" class="form-table" style="<?php echo ((int) @$ghn_options['ghn_shopid'] == 0) ? 'display: none;' : ''; ?>">
			<tbody>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label>Tên cửa hàng</label>
					</th>
					<td class="forminp forminp-text">
						<input id="view_ghn_shopname" type="text" value="<?php echo @$website_store['name']; ?>" readonly="readonly" />
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label>Điện thoại</label>
					</th>
					<td class="forminp forminp-text">
						<input id="view_ghn_shoptel" type="text" value="<?php echo @$website_store['phone']; ?>" readonly="readonly" />
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label>Địa chỉ</label>
					</th>
					<td class="forminp forminp-text">
						<input id="view_ghn_shopaddress" type="text" value="<?php echo @$website_store['address']; ?>" readonly="readonly" />
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label>Quận/Huyện</label>
					</th>
					<td class="forminp forminp-text">
						<input id="view_ghn_shopdistrict" type="text" value="<?php echo @$website_store['store_district_name']; ?>" readonly="readonly" />
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label>Phường/Xã</label>
					</th>
					<td class="forminp forminp-text">
					<?php $ghn_wards2 = $ghn->get_wards(); // wards
					$count_wards2 = count($ghn_wards2); ?>
						<select id="view_ghn_shopward" style="min-width: 350px;" class="wc-enhanced-select" data-placeholder="Chọn Phường/Xã" disable="disable">
						<?php for($i = 0; $i < $count_wards2; $i++) { ?>
							<option value="<?php echo $ghn_wards2[$i]->WardCode; ?>" <?php echo (@$website_store['ward_code'] == $ghn_wards2[$i]->WardCode) ? 'selected' : ''; ?>><?php echo $ghn_wards2[$i]->WardName; ?></option>
						<?php } ?>
						</select>
					</td>
				</tr>
			</tbody>
		</table>
	<?php } ?>
		
		<button name="ghn_action" class="button-primary woocommerce-save-button" type="submit" value="ghn_save_opt">Lưu thay đổi</button>
	<form>