<?php defined('ABSPATH') or die('No script kiddies please!');

global $post, $ghn, $ghn_order_code, $ghn_order_detail;

$post_id = $post->ID;
$param_wc_order_id = !empty($_GET['wc_order_id']) ? $_GET['wc_order_id'] : 0;

if (@$post->post_parent > 0) $param_wc_order_id = @$post->post_parent;

// order detail
$ghn_order_code = $this->ghn_get_ghn_order_code($post_id);
$ghn_order_fee = $ghn->get_order_fee($ghn_order_code);

$ghn_status_chk = new GHN_Status();
$ghn_status_chk->set_status(@$ghn_order_detail['status']);
$ghn_editable_fields = $ghn_status_chk->get_editable_fields(); ?>

	<div class="wrap woocommerce ghn-woocommerce">
		<table class="form-table">
			<tbody>				
				<tr valign="top">
					<td scope="row" class="titledesc" style="vertical-align: text-top;">
					<!-- coupon -->
						<p>
							<label for="coupon">Mã khuyến mãi</label>
						</p>					
						<p>
						<?php if ($ghn_status_chk->editable('coupon')) { ?>		
							<input name="coupon" type="text" value="<?php echo esc_attr(@$ghn_order_detail['coupon']); ?>" />
						<?php } else { ?>	
							<input name="coupon" type="hidden" value="<?php echo esc_attr(@$ghn_order_detail['coupon']); ?>" />	
							<input id="view_coupon" type="text" value="<?php echo esc_attr(@$ghn_order_detail['coupon']); ?>" readonly="readonly" />
						<?php } ?>
							<br/><small>Mỗi gói hàng chỉ được áp dụng 1 mã giảm giá.</small>
						</p>
					<!-- coupon -->
					</td>
				</tr>				
				<tr valign="top">
					<td scope="row" class="titledesc" style="vertical-align: text-top;">
					<!-- payment_type_id -->
						<p>
							<label for="payment_type_id">Tuỳ chọn thanh toán</label>
						</p>						
						<p>
						<?php if ($ghn_status_chk->editable('payment_type_id')) { ?>
							<select name="payment_type_id" class="wc-enhanced-select" style="max-width: 100%;">
								<option value="1" <?php echo (@$ghn_order_detail['payment_type_id'] == 1) ? 'selected' : ''; ?>>Bên gửi trả phí</option>
								<option value="2" <?php echo (@$ghn_order_detail['payment_type_id'] == 2) ? 'selected' : ''; ?>>Bên nhận trả phí</option>
							</select>
						<?php } else {
						$payment_type_name = (@$ghn_order_detail['payment_type_id'] == 1) ? 'Bên gửi trả phí' : 'Bên nhận trả phí'; ?>	
							<input id="view_payment_type_id" type="text" value="<?php echo esc_attr($payment_type_name); ?>" readonly="readonly" />
						<?php } ?>	
						</p>
					<!-- payment_type_id -->
					</td>
				</tr>				
				<tr valign="top">
					<td scope="row" class="titledesc" style="vertical-align: text-top;">
						<p>
							<label for="">Chi phí</label>
						</p>
						<div class="fee-result">	
						<?php
							if(@$ghn_order_fee['detail'] != null) {
								echo $this->get_order_fees(@$ghn_order_fee['detail']);
							} else {
								echo "<div id='fee-result-content'></div>";
							}
						?>
						</div>
					</td>
				</tr>				
			</tbody>
		</table>
	</div>