<?php defined('ABSPATH') or die('No script kiddies please!');

global $post, $ghn, $ghn_order_code, $ghn_order_detail;

$post_id = $post->ID;
$ghn_update_histories = get_post_meta($post_id, 'ghn_update_histories', true);
$ghn_update_histories = (is_array($ghn_update_histories)) ? $ghn_update_histories : array();

if (count($ghn_update_histories) > 0) {		
	$html = $this->ghn_get_order_update_histories($ghn_update_histories);
	
	echo '<ul style="margin: 0;">'.$html.'</ul>';
} else {
	echo 'Chưa có dữ liệu';
}